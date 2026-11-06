<?php

use App\Models\Campaign;
use App\Models\MapPin;
use App\Models\User;
use App\Models\WikiPage;

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

    $browserA->click('Nuova Pagina Wiki')
        ->assertPathBeginsWith("/campaigns/{$campaign->id}/wiki/create")
        ->assertNoJavascriptErrors()
        ->type('title', 'Pagina Sincronizzata Vista Non Filtrata')
        ->click('Markdown')
        ->type('content', 'Contenuto della pagina sincronizzata nella vista non filtrata')
        ->press('Salva Scheda Wiki');

    $browserB->waitForText('Pagina Sincronizzata Vista Non Filtrata', 15)->assertSee('Contenuto della pagina sincronizzata nella vista non filtrata');
});

it('crea modifica e collega un segnaposto dalla modale mappa', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $user->id]);
    $campaign->users()->attach($user->id, ['role' => 'gm']);
    $wiki = WikiPage::factory()->create([
        'campaign_id' => $campaign->id,
        'title' => 'Torre segreta',
        'category' => 'Luoghi',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $browser = visit('/login')
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('Entra nella Campagna')
        ->navigate("/campaigns/{$campaign->id}?tab=map")
        ->assertSee('Mappa Predefinita')
        ->assertNoJavascriptErrors();

    $browser->script('() => { const image = document.querySelector("[x-ref=mapImage]"); const rect = image.getBoundingClientRect(); image.dispatchEvent(new MouseEvent("dblclick", { bubbles: true, clientX: rect.left + rect.width / 2, clientY: rect.top + rect.height / 2 })); }');
    $browser->assertSee('Nuovo Punto sulla Mappa')
        ->type('input[x-model="pinForm.title"]', 'Pin della torre')
        ->click('button[aria-label="Oro / Ambra"]')
        ->type('#pin_form_wiki_page_id_search', 'Torre segreta')
        ->click('#pin_form_wiki_page_id_option_0')
        ->press('Salva segnaposto')
        ->assertSee('Pin della torre');

    $pin = MapPin::where('title', 'Pin della torre')->firstOrFail();
    expect($pin->color)->toBe('#f59e0b')
        ->and($pin->wiki_page_id)->toBe($wiki->id);

    $browser->click('button[\\@click="openPinPopover(pin)"]')
        ->assertSee('Leggi scheda Wiki')
        ->click('Modifica nome e descrizione')
        ->type('input[x-model="pinForm.title"]', 'Pin modificato')
        ->click('button[aria-label="Smeraldo"]')
        ->click('button[aria-label="Rimuovi selezione"]')
        ->press('Salva segnaposto')
        ->assertSee('Nessuna scheda wiki collegata');

    $pin->refresh();
    expect($pin->title)->toBe('Pin modificato')
        ->and($pin->color)->toBe('#10b981')
        ->and($pin->wiki_page_id)->toBeNull();

    $browser->type('#pin_link_wiki_page_id_search', 'Torre segreta')
        ->click('#pin_link_wiki_page_id_option_0')
        ->assertSee('Leggi scheda Wiki')
        ->assertNoJavascriptErrors();

    expect($pin->fresh()->wiki_page_id)->toBe($wiki->id);
});
