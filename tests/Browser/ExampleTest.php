<?php

use App\Models\Campaign;
use App\Models\User;

it('permette il login contemporaneo di due utenti su browser separati', function () {
    $alice = User::factory()->create(['name' => 'Alice']);
    $bob = User::factory()->create(['name' => 'Bob']);

    $browserA = visit('/login');
    $browserB = visit('/login');

    $browserA->type('email', $alice->email)
        ->type('password', 'password')
        ->press('Entra nella Campagna')
        ->assertPathIs('/campaigns');

    $browserB->type('email', $bob->email)
        ->type('password', 'password')
        ->press('Entra nella Campagna')
        ->assertPathIs('/campaigns');

    // Le sessioni sono indipendenti: ciascun browser resta autenticato
    $browserA->navigate('/campaigns')->assertPathIs('/campaigns');
    $browserB->navigate('/campaigns')->assertPathIs('/campaigns');
});

it('una nota creata da un utente appare nellaltro browser tramite sync delta', function () {
    $userA = User::factory()->create(['name' => 'Alice']);
    $userB = User::factory()->create(['name' => 'Bob']);

    $campaign = Campaign::create([
        'owner_id' => $userA->id,
        'name' => 'Campagna Sync Multi-Browser',
        'system' => 'Daggerheart',
    ]);
    $campaign->users()->attach($userA->id, ['role' => 'gm']);
    $campaign->users()->attach($userB->id, ['role' => 'gm']);

    // Browser A: login, apre la campagna sul tab Note
    $browserA = visit('/login')
        ->type('email', $userA->email)
        ->type('password', 'password')
        ->press('Entra nella Campagna')
        ->assertPathIs('/campaigns')
        ->navigate("/campaigns/{$campaign->id}?tab=notes")
        ->assertSee($campaign->name)
        ->assertNoJavascriptErrors()
        ->waitForText('Nuova Nota di Sessione');

    // Browser B: login, apre la campagna sul tab Note
    $browserB = visit('/login')
        ->type('email', $userB->email)
        ->type('password', 'password')
        ->press('Entra nella Campagna')
        ->assertPathIs('/campaigns')
        ->navigate("/campaigns/{$campaign->id}?tab=notes")
        ->assertSee($campaign->name)
        ->assertNoJavascriptErrors()
        ->waitForText('Nuova Nota di Sessione');

    // Utente A crea la nota dalla UI (registra l'evento di sync)
    $form = 'form[action$="/notes"]';
    $browserA->click('Nuova Nota di Sessione')
        ->type("{$form} input[name=title]", 'Nota Sincronizzata Multi-Browser')
        ->click('Markdown')
        ->type("{$form} textarea[name=content]", 'Questa nota deve apparire sul browser B tramite polling.')
        ->press('Salva Nota');

    // Browser B riceve la nota tramite sync delta polling (assertSee fa retry)
    $browserB->assertSee('Nota Sincronizzata Multi-Browser');
});

it('una pagina wiki creata da un utente appare nellaltro browser tramite sync delta nella vista non filtrata', function () {
    $userA = User::factory()->create(['name' => 'Alice']);
    $userB = User::factory()->create(['name' => 'Bob']);

    $campaign = Campaign::create([
        'owner_id' => $userA->id,
        'name' => 'Campagna Wiki Sync Non Filtrata',
        'system' => 'Daggerheart',
    ]);
    $campaign->users()->attach($userA->id, ['role' => 'gm']);
    $campaign->users()->attach($userB->id, ['role' => 'gm']);

    $browserA = visit('/login')
        ->type('email', $userA->email)
        ->type('password', 'password')
        ->press('Entra nella Campagna')
        ->assertPathIs('/campaigns')
        ->navigate("/campaigns/{$campaign->id}?tab=wiki")
        ->assertSee($campaign->name)
        ->assertNoJavascriptErrors()
        ->waitForText('Nuova Pagina');

    $browserB = visit('/login')
        ->type('email', $userB->email)
        ->type('password', 'password')
        ->press('Entra nella Campagna')
        ->assertPathIs('/campaigns')
        ->navigate("/campaigns/{$campaign->id}?tab=wiki")
        ->assertSee($campaign->name)
        ->assertNoJavascriptErrors()
        ->waitForText('Nuova Pagina');

    $browserA->click('Nuova Pagina')
        ->assertPathBeginsWith("/campaigns/{$campaign->id}/wiki/create")
        ->type('title', 'Pagina Sincronizzata Vista Non Filtrata')
        ->type('content', 'Contenuto della pagina sincronizzata nella vista non filtrata')
        ->press('Salva Pagina');

    $browserB->waitForText('Pagina Sincronizzata Vista Non Filtrata')->assertSee('Contenuto della pagina sincronizzata nella vista non filtrata');
});
