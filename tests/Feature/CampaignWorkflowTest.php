<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignMap;
use App\Models\MapPin;
use App\Models\SessionNote;
use App\Models\User;
use App\Models\WikiPage;
use App\Models\WikiPageRevision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CampaignWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_create_campaign_as_gm(): void
    {
        $response = $this->post('/register', [
            'name' => 'Elia GM',
            'email' => 'elia@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        $user = User::where('email', 'elia@example.com')->first();
        $this->assertNotNull($user);

        // Create campaign
        $createResponse = $this->actingAs($user)->post('/campaigns', [
            'name' => 'La Notte dei Sussurri',
            'description' => 'Un\'avventura oscura nei boschi di Daggerheart.',
            'system' => 'Daggerheart',
        ]);

        $campaign = Campaign::where('name', 'La Notte dei Sussurri')->first();
        $this->assertNotNull($campaign);
        $createResponse->assertRedirect(route('campaigns.show', $campaign));

        // Creator should be owner and GM
        $this->assertEquals($user->id, $campaign->owner_id);
        $this->assertTrue($campaign->isGm($user));
        $this->assertNotNull($campaign->invite_code_player);
        $this->assertNotNull($campaign->invite_code_gm);
    }

    public function test_player_can_join_via_invite_code(): void
    {
        $gm = User::factory()->create();
        $campaign = Campaign::create([
            'owner_id' => $gm->id,
            'name' => 'Campagna Epica',
        ]);
        $campaign->users()->attach($gm->id, ['role' => 'gm']);

        $player = User::factory()->create();

        // Join via player code
        $response = $this->actingAs($player)->post('/campaigns/join', [
            'code' => $campaign->invite_code_player,
        ]);

        $response->assertRedirect(route('campaigns.show', $campaign));
        $this->assertTrue($campaign->isMember($player));
        $this->assertFalse($campaign->isGm($player));
    }

    public function test_wiki_creation_revisions_and_rollback(): void
    {
        $gm = User::factory()->create();
        $campaign = Campaign::create([
            'owner_id' => $gm->id,
            'name' => 'Sopravvivenza',
        ]);
        $campaign->users()->attach($gm->id, ['role' => 'gm']);

        $player = User::factory()->create();
        $campaign->users()->attach($player->id, ['role' => 'player']);

        // Player creates wiki page
        $this->actingAs($player)->post(route('wiki.store', $campaign), [
            'title' => 'Spada della Speranza',
            'category' => 'Oggetti & Reliquie',
            'content' => 'Una spada magica forgiata dagli antichi.',
        ]);

        $wiki = WikiPage::where('title', 'Spada della Speranza')->first();
        $this->assertNotNull($wiki);
        $this->assertEquals(1, $wiki->revisions()->count());

        // GM updates wiki page
        $this->actingAs($gm)->put(route('wiki.update', [$campaign, $wiki]), [
            'title' => 'Spada della Speranza Rinforzata',
            'category' => 'Oggetti & Reliquie',
            'content' => 'Una spada magica con lama fiammeggiante.',
            'summary' => 'Aggiunto effetto fiammeggiante',
        ]);

        $wiki->refresh();
        $this->assertEquals('Spada della Speranza Rinforzata', $wiki->title);
        $this->assertEquals(2, $wiki->revisions()->count());

        // Rollback to first revision
        $firstRevision = $wiki->revisions()->oldest()->first();
        $this->actingAs($player)->post(route('wiki.restoreRevision', [$campaign, $wiki, $firstRevision]));

        $wiki->refresh();
        $this->assertEquals('Spada della Speranza', $wiki->title);
        $this->assertEquals(3, $wiki->revisions()->count());
    }

    public function test_custom_map_image_upload_and_pin_placement(): void
    {
        Storage::fake('public');

        $gm = User::factory()->create();
        $campaign = Campaign::create([
            'owner_id' => $gm->id,
            'name' => 'Mappa Custom Test',
        ]);
        $campaign->users()->attach($gm->id, ['role' => 'gm']);

        // 1. Upload custom map image
        $file = UploadedFile::fake()->image('dungeon_livello_1.png', 1600, 1000);
        $uploadResponse = $this->actingAs($gm)->post(route('maps.store', $campaign), [
            'title' => 'Sotterranei di Kar-Drak',
            'image' => $file,
        ]);
        $uploadResponse->assertRedirect();

        $map = CampaignMap::where('title', 'Sotterranei di Kar-Drak')->first();
        $this->assertNotNull($map);
        $this->assertTrue($map->is_default);
        Storage::disk('public')->assertExists($map->image_path);

        // 2. Place a pin with percentage coordinates
        $pinResponse = $this->actingAs($gm)->postJson(route('pins.store', $campaign), [
            'title' => 'Sala del Trono Spezzato',
            'description' => 'Presidiata da tre spettri.',
            'campaign_map_id' => $map->id,
            'x' => 45.5,
            'y' => 60.2,
            'color' => '#f59e0b',
        ]);
        $pinResponse->assertOk();
        $pinResponse->assertJson(['success' => true]);

        $pin = MapPin::where('title', 'Sala del Trono Spezzato')->first();
        $this->assertNotNull($pin);
        $this->assertEquals(45.5, $pin->x);
        $this->assertEquals(60.2, $pin->y);
        $this->assertEquals($map->id, $pin->campaign_map_id);

        // 3. Update pin position (simulating drag and drop)
        $updateResponse = $this->actingAs($gm)->putJson(route('pins.update', [$campaign, $pin]), [
            'x' => 48.0,
            'y' => 62.5,
        ]);
        $updateResponse->assertOk();

        $pin->refresh();
        $this->assertEquals(48.0, $pin->x);
        $this->assertEquals(62.5, $pin->y);
    }

    public function test_create_wiki_page_from_map_pin(): void
    {
        $gm = User::factory()->create();
        $campaign = Campaign::create([
            'owner_id' => $gm->id,
            'name' => 'Campagna Pin to Wiki',
        ]);
        $campaign->users()->attach($gm->id, ['role' => 'gm']);

        // Create a pin on the map
        $pin = $campaign->mapPins()->create([
            'title' => 'Torre del Silenzio',
            'description' => 'Un\'antica torre di guardia che domina la vallata.',
            'x' => 30.0,
            'y' => 40.0,
            'created_by' => $gm->id,
        ]);
        $this->assertNull($pin->wiki_page_id);

        // Access the create wiki page route passing pin_id
        $formResponse = $this->actingAs($gm)->get(route('wiki.create', ['campaign' => $campaign, 'pin_id' => $pin->id]));
        $formResponse->assertOk();
        $formResponse->assertSee('Torre del Silenzio');

        // Submit creation of wiki page linked to this pin
        $createResponse = $this->actingAs($gm)->post(route('wiki.store', $campaign), [
            'title' => 'Torre del Silenzio',
            'category' => 'Luoghi & Città',
            'content' => 'Dettagli completi sui tesori e i segreti della torre.',
            'pin_id' => $pin->id,
        ]);

        $wiki = WikiPage::where('title', 'Torre del Silenzio')->first();
        $this->assertNotNull($wiki);
        $createResponse->assertRedirect(route('wiki.show', [$campaign, $wiki]));

        // Verify the pin is now automatically linked to this new wiki page!
        $pin->refresh();
        $this->assertEquals($wiki->id, $pin->wiki_page_id);
    }

    public function test_player_cannot_empty_trash_or_compact_revisions_but_gm_can(): void
    {
        $gm = User::factory()->create();
        $campaign = Campaign::create([
            'owner_id' => $gm->id,
            'name' => 'Controllo Accessi',
        ]);
        $campaign->users()->attach($gm->id, ['role' => 'gm']);

        $player = User::factory()->create();
        $campaign->users()->attach($player->id, ['role' => 'player']);

        // Soft delete a wiki page
        $wiki = $campaign->wikiPages()->create([
            'title' => 'Da Eliminare',
            'content' => 'Contenuto temporaneo',
            'created_by' => $player->id,
        ]);
        $wiki->delete();

        // Player tries to empty trash -> forbidden
        $playerTrash = $this->actingAs($player)->post(route('campaigns.emptyTrash', $campaign));
        $playerTrash->assertForbidden();

        // Player tries to compact revisions -> forbidden
        $playerCompact = $this->actingAs($player)->post(route('campaigns.compactRevisions', $campaign));
        $playerCompact->assertForbidden();

        // GM empties trash -> success
        $gmTrash = $this->actingAs($gm)->post(route('campaigns.emptyTrash', $campaign));
        $gmTrash->assertRedirect();
        $this->assertEquals(0, WikiPage::withTrashed()->where('id', $wiki->id)->count());

        // GM compacts revisions -> success
        $gmCompact = $this->actingAs($gm)->post(route('campaigns.compactRevisions', $campaign));
        $gmCompact->assertRedirect();
    }

    public function test_export_campaign_markdown(): void
    {
        $gm = User::factory()->create();
        $campaign = Campaign::create([
            'owner_id' => $gm->id,
            'name' => 'Grimorio Completo',
            'description' => 'Descrizione compendio',
            'system' => 'Daggerheart',
        ]);
        $campaign->users()->attach($gm->id, ['role' => 'gm']);

        $campaign->wikiPages()->create([
            'title' => 'Foresta Nebbiosa',
            'category' => 'Luoghi & Città',
            'content' => 'Un fitto bosco avvolto da nebbia perenne.',
            'created_by' => $gm->id,
        ]);

        $campaign->sessionNotes()->create([
            'title' => 'Sessione 1',
            'content' => 'Il gruppo ha esplorato la radura.',
            'user_id' => $gm->id,
        ]);

        $response = $this->actingAs($gm)->get(route('campaigns.export', $campaign));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');
        $this->assertStringContainsString('Grimorio Completo', $response->getContent());
        $this->assertStringContainsString('Foresta Nebbiosa', $response->getContent());
    }
}
