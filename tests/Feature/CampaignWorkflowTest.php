<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignMap;
use App\Models\MapPin;
use App\Models\User;
use App\Models\WikiPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
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

    public function test_wiki_pages_can_be_created_and_updated_with_parent_pages(): void
    {
        $gm = User::factory()->create();
        $campaign = Campaign::create(['owner_id' => $gm->id, 'name' => 'Cronache']);
        $campaign->users()->attach($gm->id, ['role' => 'gm']);
        $parent = $campaign->wikiPages()->create([
            'title' => 'La città',
            'category' => 'Luoghi & Città',
            'content' => 'La città principale.',
        ]);
        $otherParent = $campaign->wikiPages()->create([
            'title' => 'La regione',
            'category' => 'Luoghi & Città',
            'content' => 'La regione circostante.',
        ]);

        $createResponse = $this->actingAs($gm)->post(route('wiki.store', $campaign), [
            'title' => 'La taverna',
            'category' => 'Luoghi & Città',
            'parent_id' => $parent->id,
            'content' => 'Una taverna affollata.',
        ]);

        $child = $campaign->wikiPages()->where('title', 'La taverna')->firstOrFail();
        $createResponse->assertRedirect(route('wiki.show', [$campaign, $child]));
        $this->assertDatabaseHas('wiki_pages', [
            'id' => $child->id,
            'parent_id' => $parent->id,
        ]);

        $this->actingAs($gm)->put(route('wiki.update', [$campaign, $child]), [
            'title' => $child->title,
            'category' => $child->category,
            'parent_id' => $otherParent->id,
            'content' => $child->content,
            'summary' => '',
        ])->assertRedirect(route('wiki.show', [$campaign, $child]));

        $this->assertDatabaseHas('wiki_pages', [
            'id' => $child->id,
            'parent_id' => $otherParent->id,
        ]);
    }

    public function test_wiki_pages_reject_parent_pages_from_other_campaigns_and_circular_hierarchies(): void
    {
        $gm = User::factory()->create();
        $campaign = Campaign::create(['owner_id' => $gm->id, 'name' => 'Prima campagna']);
        $campaign->users()->attach($gm->id, ['role' => 'gm']);
        $otherCampaign = Campaign::create(['owner_id' => $gm->id, 'name' => 'Seconda campagna']);
        $otherCampaign->users()->attach($gm->id, ['role' => 'gm']);
        $page = $campaign->wikiPages()->create([
            'title' => 'Pagina principale',
            'category' => 'Lore & Storia',
            'content' => 'Contenuto.',
        ]);
        $child = $campaign->wikiPages()->create([
            'title' => 'Pagina figlia',
            'category' => 'Lore & Storia',
            'parent_id' => $page->id,
            'content' => 'Contenuto.',
        ]);
        $foreignParent = $otherCampaign->wikiPages()->create([
            'title' => 'Pagina estranea',
            'category' => 'Lore & Storia',
            'content' => 'Contenuto.',
        ]);

        $this->actingAs($gm)->post(route('wiki.store', $campaign), [
            'title' => 'Pagina non valida',
            'category' => 'Lore & Storia',
            'parent_id' => $foreignParent->id,
            'content' => 'Contenuto.',
        ])->assertSessionHasErrors('parent_id');

        $this->actingAs($gm)->put(route('wiki.update', [$campaign, $page]), [
            'title' => $page->title,
            'category' => $page->category,
            'parent_id' => $child->id,
            'content' => $page->content,
        ])->assertSessionHasErrors('parent_id');

        $this->assertDatabaseMissing('wiki_pages', ['title' => 'Pagina non valida']);
        $this->assertDatabaseHas('wiki_pages', [
            'id' => $page->id,
            'parent_id' => null,
        ]);
    }

    public function test_wiki_pages_are_sorted_by_category_and_title_in_a_collapsible_tree(): void
    {
        $gm = User::factory()->create();
        $campaign = Campaign::create(['owner_id' => $gm->id, 'name' => 'Ordinamento Wiki']);
        $campaign->users()->attach($gm->id, ['role' => 'gm']);

        $zeta = $campaign->wikiPages()->create([
            'title' => 'Zeta',
            'category' => 'Altro',
            'content' => 'Contenuto.',
        ]);
        $aquila = $campaign->wikiPages()->create([
            'title' => 'Aquila',
            'category' => 'Altro',
            'content' => 'Contenuto.',
        ]);
        $parent = $campaign->wikiPages()->create([
            'title' => 'Torre',
            'category' => 'Luoghi & Città',
            'content' => 'Contenuto.',
        ]);
        $child = $campaign->wikiPages()->create([
            'title' => 'Guardiani',
            'category' => 'Fazioni & Gilde',
            'parent_id' => $parent->id,
            'content' => 'Contenuto.',
        ]);
        $grandchild = $campaign->wikiPages()->create([
            'title' => 'Sentinelle',
            'category' => 'Fazioni & Gilde',
            'parent_id' => $child->id,
            'content' => 'Contenuto.',
        ]);

        $response = $this->actingAs($gm)->get(route('campaigns.show', $campaign));

        $response->assertOk();
        preg_match_all('/data-wiki-page-id="(\d+)"/', $response->getContent(), $pageIds);
        $this->assertSame(
            [(string) $aquila->id, (string) $zeta->id, (string) $parent->id],
            $pageIds[1]
        );
        $response->assertSee('filteredPages.length > 0 && hasActiveFilter()', false);
        $response->assertSee('x-for="page in filteredPages"', false);
        $response->assertDontSee('hasMatchingDescendant', false);
        $response->assertSee('<dialog', false);
        $response->assertSee('openChildPages(page.id)', false);
        $response->assertSee('children_ids', false);
        $response->assertSee('grid-cols-1 gap-4 overflow-y-auto p-4 sm:grid-cols-2', false);
        $response->assertSeeInOrder(['Leggi', 'Sottopagine'], false);
        $response->assertSee('Leggi', false);
        $response->assertDontSee('Apri scheda', false);
        $response->assertDontSee('Esplora', false);
        $response->assertSee(":class=\"{ 'wiki-card-stack': page.children_ids.length > 0 }\"", false);
        $response->assertDontSee('-right-1 -bottom-1', false);
        $response->assertSee($child->title, false);
        $response->assertSee($grandchild->title, false);
    }

    private function uploadMapInChunks(User $user, Campaign $campaign, string $title, string $contents, int $chunkSize = 1000, ?int $skipIndex = null): TestResponse
    {
        $uploadId = (string) Str::uuid();
        $chunks = str_split($contents, $chunkSize);

        foreach ($chunks as $index => $chunk) {
            if ($index === $skipIndex) {
                continue;
            }

            $this->actingAs($user)->postJson(route('maps.storeChunk', $campaign), [
                'upload_id' => $uploadId,
                'index' => $index,
                'chunk' => UploadedFile::fake()->createWithContent('chunk', $chunk),
            ])->assertOk();
        }

        return $this->actingAs($user)->postJson(route('maps.store', $campaign), [
            'title' => $title,
            'upload_id' => $uploadId,
            'total_chunks' => count($chunks),
        ]);
    }

    public function test_map_upload_with_missing_chunk_is_rejected(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $gm = User::factory()->create();
        $campaign = Campaign::create(['owner_id' => $gm->id, 'name' => 'Upload Incompleto']);
        $campaign->users()->attach($gm->id, ['role' => 'gm']);

        $image = UploadedFile::fake()->image('mappa.png', 800, 600);
        $contents = file_get_contents($image->getRealPath());

        $this->uploadMapInChunks($gm, $campaign, 'Mappa rotta', $contents, 500, 1)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');

        $this->assertSame(0, $campaign->maps()->count());
    }

    public function test_map_upload_rejects_files_that_are_not_images(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $gm = User::factory()->create();
        $campaign = Campaign::create(['owner_id' => $gm->id, 'name' => 'Upload Non Immagine']);
        $campaign->users()->attach($gm->id, ['role' => 'gm']);

        $this->uploadMapInChunks($gm, $campaign, 'Falso', str_repeat('<?php echo 1; ?>', 200))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');

        $this->assertSame(0, $campaign->maps()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_non_members_cannot_upload_map_chunks(): void
    {
        Storage::fake('local');

        $gm = User::factory()->create();
        $outsider = User::factory()->create();
        $campaign = Campaign::create(['owner_id' => $gm->id, 'name' => 'Solo Membri']);
        $campaign->users()->attach($gm->id, ['role' => 'gm']);

        $this->actingAs($outsider)->postJson(route('maps.storeChunk', $campaign), [
            'upload_id' => (string) Str::uuid(),
            'index' => 0,
            'chunk' => UploadedFile::fake()->createWithContent('chunk', 'abc'),
        ])->assertForbidden();
    }

    public function test_prune_command_removes_only_stale_chunk_uploads(): void
    {
        Storage::fake('local');
        $disk = Storage::disk('local');

        $disk->put('map-chunks/1/1/stale/0', 'a');
        $disk->put('map-chunks/1/1/fresh/0', 'b');
        touch($disk->path('map-chunks/1/1/stale/0'), now()->subDays(2)->getTimestamp());

        $this->artisan('maps:prune-chunks')->assertSuccessful();

        $disk->assertMissing('map-chunks/1/1/stale/0');
        $disk->assertExists('map-chunks/1/1/fresh/0');
    }

    public function test_map_pin_wiki_link_can_be_changed_removed_and_is_limited_to_the_campaign(): void
    {
        $gm = User::factory()->create();
        $campaign = Campaign::create(['owner_id' => $gm->id, 'name' => 'Link Wiki']);
        $campaign->users()->attach($gm->id, ['role' => 'gm']);
        $otherCampaign = Campaign::create(['owner_id' => $gm->id, 'name' => 'Altra Campagna']);

        $firstPage = WikiPage::create(['campaign_id' => $campaign->id, 'title' => 'Prima', 'category' => 'Luoghi & Città', 'content' => 'a', 'created_by' => $gm->id]);
        $secondPage = WikiPage::create(['campaign_id' => $campaign->id, 'title' => 'Seconda', 'category' => 'Luoghi & Città', 'content' => 'b', 'created_by' => $gm->id]);
        $foreignPage = WikiPage::create(['campaign_id' => $otherCampaign->id, 'title' => 'Estranea', 'category' => 'Altro', 'content' => 'c', 'created_by' => $gm->id]);

        $pin = MapPin::create([
            'campaign_id' => $campaign->id,
            'created_by' => $gm->id,
            'title' => 'Torre',
            'x' => 10,
            'y' => 10,
            'wiki_page_id' => $firstPage->id,
        ]);
        $url = route('pins.update', [$campaign, $pin]);

        $this->actingAs($gm)->putJson($url, ['wiki_page_id' => $secondPage->id])->assertOk();
        $this->assertSame($secondPage->id, $pin->fresh()->wiki_page_id);

        $this->actingAs($gm)->putJson($url, ['wiki_page_id' => $foreignPage->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('wiki_page_id');
        $this->assertSame($secondPage->id, $pin->fresh()->wiki_page_id);

        $this->actingAs($gm)->putJson($url, ['wiki_page_id' => null])->assertOk();
        $this->assertNull($pin->fresh()->wiki_page_id);
    }

    public function test_custom_map_image_upload_and_pin_placement(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $gm = User::factory()->create();
        $campaign = Campaign::create([
            'owner_id' => $gm->id,
            'name' => 'Mappa Custom Test',
        ]);
        $campaign->users()->attach($gm->id, ['role' => 'gm']);

        // 1. Upload custom map image in chunks
        $file = UploadedFile::fake()->image('dungeon_livello_1.png', 1600, 1000);
        $uploadResponse = $this->uploadMapInChunks($gm, $campaign, 'Sotterranei di Kar-Drak', file_get_contents($file->getRealPath()));
        $uploadResponse->assertOk()->assertJson(['success' => true]);

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

    public function test_locked_map_pin_cannot_be_moved_until_it_is_unlocked(): void
    {
        $gm = User::factory()->create();
        $campaign = Campaign::create([
            'owner_id' => $gm->id,
            'name' => 'Campagna Pin Bloccato',
        ]);
        $campaign->users()->attach($gm->id, ['role' => 'gm']);
        $pin = $campaign->mapPins()->create([
            'title' => 'Portale Antico',
            'x' => 35,
            'y' => 42,
        ]);

        $lockResponse = $this->actingAs($gm)->putJson(route('pins.update', [$campaign, $pin]), [
            'locked' => true,
        ]);

        $lockResponse->assertOk();
        $this->assertDatabaseHas('map_pins', [
            'id' => $pin->id,
            'locked' => true,
        ]);

        $moveResponse = $this->putJson(route('pins.update', [$campaign, $pin]), [
            'x' => 60,
            'y' => 70,
        ]);

        $moveResponse->assertUnprocessable();
        $moveResponse->assertJsonValidationErrors('locked');
        $this->assertDatabaseHas('map_pins', [
            'id' => $pin->id,
            'x' => 35,
            'y' => 42,
            'locked' => true,
        ]);

        $unlockResponse = $this->putJson(route('pins.update', [$campaign, $pin]), [
            'locked' => false,
        ]);

        $unlockResponse->assertOk();
        $this->assertDatabaseHas('map_pins', [
            'id' => $pin->id,
            'locked' => false,
        ]);

        $moveAfterUnlockResponse = $this->putJson(route('pins.update', [$campaign, $pin]), [
            'x' => 60,
            'y' => 70,
        ]);

        $moveAfterUnlockResponse->assertOk();
        $this->assertDatabaseHas('map_pins', [
            'id' => $pin->id,
            'x' => 60,
            'y' => 70,
            'locked' => false,
        ]);
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

    public function test_wiki_links_are_resolved_within_the_campaign_and_html_is_stripped(): void
    {
        $gm = User::factory()->create();
        $campaign = Campaign::create(['owner_id' => $gm->id, 'name' => 'Rete di Conoscenza']);
        $campaign->users()->attach($gm->id, ['role' => 'gm']);

        $otherCampaign = Campaign::create(['owner_id' => $gm->id, 'name' => 'Altra Campagna']);
        $target = WikiPage::create(['campaign_id' => $campaign->id, 'title' => 'Kar-Drak', 'content' => 'x']);
        WikiPage::create(['campaign_id' => $otherCampaign->id, 'title' => 'Segreto Altrui', 'content' => 'x']);

        $html = $campaign->fresh()->renderMarkdown(
            "Vai a [[kar-drak]], [[Kar-Drak|la cittadella]], [[Segreto Altrui]] e `[[Kar-Drak]]`.\n\n<script>alert(1)</script>"
        );

        $url = route('wiki.show', [$campaign, $target]);
        $this->assertStringContainsString('<a href="'.$url.'" class="wikilink">kar-drak</a>', $html);
        $this->assertStringContainsString('class="wikilink">la cittadella</a>', $html);
        $this->assertStringContainsString('<span class="wikilink-missing" title="Pagina non trovata">Segreto Altrui</span>', $html);
        $this->assertStringContainsString('<code>[[Kar-Drak]]</code>', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_wiki_page_view_renders_links_to_other_pages(): void
    {
        $gm = User::factory()->create();
        $campaign = Campaign::create(['owner_id' => $gm->id, 'name' => 'Collegamenti']);
        $campaign->users()->attach($gm->id, ['role' => 'gm']);

        $target = WikiPage::create(['campaign_id' => $campaign->id, 'title' => 'Alleanza', 'content' => 'x']);
        $parent = WikiPage::create(['campaign_id' => $campaign->id, 'title' => 'Concilio', 'content' => 'x']);
        $source = WikiPage::create([
            'campaign_id' => $campaign->id,
            'parent_id' => $parent->id,
            'title' => 'Trattato',
            'content' => 'Vedi [[Alleanza]].',
        ]);
        $child = WikiPage::create([
            'campaign_id' => $campaign->id,
            'parent_id' => $source->id,
            'title' => 'Protocollo',
            'content' => 'x',
        ]);

        $this->actingAs($gm)->get(route('wiki.show', [$campaign, $source]))
            ->assertOk()
            ->assertSee('href="'.route('wiki.show', [$campaign, $target]).'"', false)
            ->assertSee('href="'.route('wiki.show', [$campaign, $parent]).'"', false)
            ->assertSee('href="'.route('wiki.show', [$campaign, $child]).'"', false)
            ->assertSee('aria-label="Navigazione tra schede correlate"', false)
            ->assertSee('<details', false)
            ->assertSee('Schede figlie', false)
            ->assertSeeInOrder(['Vedi', '</article>', 'aria-label="Navigazione tra schede correlate"'], false);
    }

    public function test_deleting_a_map_removes_its_pins_from_the_campaign(): void
    {
        $gm = User::factory()->create();
        $campaign = Campaign::create(['owner_id' => $gm->id, 'name' => 'Mappe']);
        $campaign->users()->attach($gm->id, ['role' => 'gm']);

        $map = CampaignMap::create(['campaign_id' => $campaign->id, 'created_by' => $gm->id, 'title' => 'Regno', 'image_path' => 'https://example.com/a.png', 'is_default' => true]);
        $otherMap = CampaignMap::create(['campaign_id' => $campaign->id, 'created_by' => $gm->id, 'title' => 'Isola', 'image_path' => 'https://example.com/b.png']);
        MapPin::create(['campaign_id' => $campaign->id, 'campaign_map_id' => $map->id, 'title' => 'Torre', 'x' => 10, 'y' => 10]);
        $kept = MapPin::create(['campaign_id' => $campaign->id, 'campaign_map_id' => $otherMap->id, 'title' => 'Faro', 'x' => 20, 'y' => 20]);
        // Pin orphaned by a map deleted before pins were removed with it
        $orphan = MapPin::create(['campaign_id' => $campaign->id, 'campaign_map_id' => $otherMap->id, 'title' => 'Orfano', 'x' => 5, 'y' => 5]);

        $this->actingAs($gm)->delete(route('maps.destroy', [$campaign, $map]))->assertRedirect();

        $this->assertSoftDeleted('map_pins', ['title' => 'Torre']);
        $this->assertNotSoftDeleted('map_pins', ['id' => $kept->id]);

        $otherMap->delete();
        $this->assertSame([], $campaign->fresh()->mapPins()->whereHas('campaignMap')->pluck('id')->all());

        $this->actingAs($gm)->get(route('campaigns.show', $campaign))->assertOk()->assertDontSee('Orfano');
    }
}
