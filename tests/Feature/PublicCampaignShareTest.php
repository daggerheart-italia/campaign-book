<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\User;
use App\Models\WikiPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCampaignShareTest extends TestCase
{
    use RefreshDatabase;

    private function campaignWithGm(array $attributes = []): array
    {
        $gm = User::factory()->create();
        $campaign = Campaign::create(['owner_id' => $gm->id, 'name' => 'Saga', ...$attributes]);
        $campaign->users()->attach($gm->id, ['role' => 'gm']);

        return [$gm, $campaign];
    }

    public function test_gm_can_set_and_clear_public_slug(): void
    {
        [$gm, $campaign] = $this->campaignWithGm();

        $this->actingAs($gm)->put(route('campaigns.updateSharing', $campaign), ['public_slug' => 'Mia-Saga'])
            ->assertRedirect(route('campaigns.edit', $campaign));
        $this->assertSame('mia-saga', $campaign->fresh()->public_slug);

        $this->actingAs($gm)->put(route('campaigns.updateSharing', $campaign), ['public_slug' => ''])
            ->assertSessionHasNoErrors();
        $this->assertNull($campaign->fresh()->public_slug);
    }

    public function test_slug_must_be_valid_and_unique(): void
    {
        [$gm, $campaign] = $this->campaignWithGm();
        $this->campaignWithGm(['public_slug' => 'occupato']);

        $this->actingAs($gm)->put(route('campaigns.updateSharing', $campaign), ['public_slug' => 'occupato'])
            ->assertSessionHasErrors('public_slug');
        $this->actingAs($gm)->put(route('campaigns.updateSharing', $campaign), ['public_slug' => 'no spazi!'])
            ->assertSessionHasErrors('public_slug');
    }

    public function test_player_cannot_change_sharing(): void
    {
        [, $campaign] = $this->campaignWithGm();
        $player = User::factory()->create();
        $campaign->users()->attach($player->id, ['role' => 'player']);

        $this->actingAs($player)->put(route('campaigns.updateSharing', $campaign), ['public_slug' => 'hack'])
            ->assertForbidden();
        $this->assertNull($campaign->fresh()->public_slug);
    }

    public function test_guest_can_read_public_campaign_and_wiki_page(): void
    {
        [$gm, $campaign] = $this->campaignWithGm(['public_slug' => 'saga']);
        $page = WikiPage::create([
            'campaign_id' => $campaign->id,
            'title' => 'Eldoria',
            'content' => 'Un regno. Vedi [[Eldoria]].',
            'created_by' => $gm->id,
            'updated_by' => $gm->id,
        ]);

        $this->get(route('public.show', 'saga'))->assertOk()->assertSee('Eldoria')->assertDontSee('Modifica');
        $this->get(route('public.wiki', ['saga', $page]))
            ->assertOk()
            ->assertSee('Un regno.')
            ->assertSee(route('public.wiki', ['saga', $page]), false);
    }

    public function test_unknown_or_disabled_slug_and_foreign_wiki_page_return_404(): void
    {
        [$gm, $campaign] = $this->campaignWithGm(['public_slug' => 'saga']);
        [$otherGm, $other] = $this->campaignWithGm();
        $foreign = WikiPage::create([
            'campaign_id' => $other->id,
            'title' => 'Segreto',
            'content' => 'x',
            'created_by' => $otherGm->id,
            'updated_by' => $otherGm->id,
        ]);

        $this->get('/share/inesistente')->assertNotFound();
        $this->get(route('public.wiki', ['saga', $foreign]))->assertNotFound();

        $campaign->update(['public_slug' => null]);
        $this->get('/share/saga')->assertNotFound();
    }
}
