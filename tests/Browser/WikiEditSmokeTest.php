<?php

use App\Models\Campaign;
use App\Models\User;

it('apre la pagina di modifica wiki senza errori js', function () {
    $user = User::factory()->create(['name' => 'Alice']);

    $campaign = Campaign::create([
        'owner_id' => $user->id,
        'name' => 'Campagna Wiki Edit',
        'system' => 'Daggerheart',
    ]);
    $campaign->users()->attach($user->id, ['role' => 'gm']);

    $wiki = $campaign->wikiPages()->create([
        'title' => 'Pagina da modificare',
        'category' => 'Lore & Storia',
        'content' => 'Contenuto iniziale.',
    ]);

    visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('Entra nella Campagna')
        ->assertPathIs('/campaigns')
        ->navigate("/campaigns/{$campaign->id}/wiki/{$wiki->id}/edit")
        ->assertNoJavascriptErrors()
        ->assertSee('Modifica Scheda');
});
