<?php

use App\Models\Campaign;
use App\Models\CampaignMap;
use App\Models\MapPin;
use App\Models\SessionNote;
use App\Models\User;
use App\Models\WikiPage;

it('visita tutte le rotte autenticate principali senza errori js', function () {
    $user = User::factory()->create(['name' => 'Test GM']);

    $campaign = Campaign::factory()->create([
        'owner_id' => $user->id,
        'name' => 'Campagna Smoke Test',
        'system' => 'Daggerheart',
    ]);
    $campaign->users()->attach($user->id, ['role' => 'gm']);

    $map = CampaignMap::factory()->create([
        'campaign_id' => $campaign->id,
        'title' => 'Mappa Principale',
    ]);

    $pin = MapPin::factory()->create([
        'campaign_id' => $campaign->id,
        'campaign_map_id' => $map->id,
        'title' => 'Pin Test',
        'created_by' => $user->id,
    ]);

    $wiki = WikiPage::factory()->create([
        'campaign_id' => $campaign->id,
        'title' => 'Pagina Test',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $note = SessionNote::factory()->create([
        'campaign_id' => $campaign->id,
        'user_id' => $user->id,
        'title' => 'Nota Test',
    ]);

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('Entra nella Campagna')
        ->assertPathIs('/campaigns')
        ->assertNoJavascriptErrors();

    $routes = [
        '/campaigns',
        "/campaigns/{$campaign->id}",
        "/campaigns/{$campaign->id}/edit",
        "/campaigns/{$campaign->id}/export",
        "/campaigns/{$campaign->id}/pins",
        "/campaigns/{$campaign->id}/wiki/create",
        "/campaigns/{$campaign->id}/wiki/{$wiki->id}",
        "/campaigns/{$campaign->id}/wiki/{$wiki->id}/edit",
        "/campaigns/{$campaign->id}/wiki/{$wiki->id}/revisions",
        "/campaigns/{$campaign->id}/maps/{$map->id}/print",
    ];

    foreach ($routes as $route) {
        visit($route)
            ->assertNoJavascriptErrors();
    }
});
