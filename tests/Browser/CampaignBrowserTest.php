<?php

use App\Models\Campaign;
use App\Models\CampaignMap;
use App\Models\MapPin;
use App\Models\User;
use App\Models\WikiPage;

it('non mostra tooltip automatici su mobile e conserva i nomi accessibili', function () {
    $gm = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $gm->id]);
    $campaign->users()->attach($gm->id, ['role' => 'gm']);

    $browser = visit('/login')
        ->resize(375, 700)
        ->type('email', $gm->email)
        ->type('password', 'password')
        ->press('Entra nella Campagna')
        ->navigate("/campaigns/{$campaign->id}?tab=map")
        ->click('button[aria-label="Carica mappa"]')
        ->assertVisible('button[aria-label="Chiudi caricamento mappa"]');

    $result = $browser->script('() => {
        const button = document.querySelector("button[aria-label=\\"Chiudi caricamento mappa\\"]");
        button.focus();
        return { label: button.getAttribute("aria-label"), tooltip: getComputedStyle(button, "::after").content };
    }');
    expect($result)->toMatchArray(['label' => 'Chiudi caricamento mappa', 'tooltip' => 'none']);

    $browser->click('button[aria-label="Chiudi caricamento mappa"]');
    $result = $browser->script('() => {
        const button = [...document.querySelectorAll("button")].find(el => el.getAttribute("@click") === "zoomOut");
        button.focus();
        const label = button.querySelector(".icon-label-mobile");
        return { label: label.textContent.trim(), tooltip: getComputedStyle(label, "::after").content };
    }');
    expect($result)->toMatchArray(['label' => 'Riduci zoom', 'tooltip' => 'none']);
    $browser->assertNoJavascriptErrors();
});

it('permette di scegliere la mappa e di gestirla dal menu gm', function () {
    $gm = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $gm->id, 'public_slug' => 'mappe-browser']);
    $campaign->users()->attach($gm->id, ['role' => 'gm']);
    $default = CampaignMap::factory()->create([
        'campaign_id' => $campaign->id,
        'title' => 'Mappa predefinita',
        'is_default' => true,
    ]);
    $other = CampaignMap::factory()->create([
        'campaign_id' => $campaign->id,
        'title' => 'Mappa secondaria',
    ]);
    MapPin::factory()->create([
        'campaign_id' => $campaign->id,
        'campaign_map_id' => $other->id,
        'created_by' => $gm->id,
        'title' => 'Luogo secondario',
    ]);

    $browser = visit('/login')
        ->resize(320, 700)
        ->type('email', $gm->email)
        ->type('password', 'password')
        ->press('Entra nella Campagna')
        ->navigate("/campaigns/{$campaign->id}?tab=map")
        ->assertVisible('select[aria-label="Mappa visualizzata"]');

    expect($browser->script('() => document.querySelector("select[aria-label=\\"Mappa visualizzata\\"]").value'))
        ->toBe((string) $default->id);
    foreach ([320, 768, 1280] as $width) {
        $browser->resize($width, 700);
        $layout = $browser->script('() => {
            const select = document.querySelector("select[aria-label=\\"Mappa visualizzata\\"]").getBoundingClientRect();
            const upload = document.querySelector("button[aria-label=\\"Carica mappa\\"]").getBoundingClientRect();
            const menu = document.querySelector("summary[aria-label=\\"Gestisci mappa\\"]").getBoundingClientRect();
            return { sameRow: select.top === upload.top && upload.top === menu.top, fits: select.left >= 0 && menu.right <= innerWidth };
        }');
        expect($layout)->toMatchArray(['sameRow' => true, 'fits' => true]);
    }
    $browser->resize(320, 700);
    $browser->click('summary[aria-label="Gestisci mappa"]')
        ->assertMissing('details[open] form[action$="/default"]')
        ->click('summary[aria-label="Gestisci mappa"]');
    $browser->select('select[aria-label="Mappa visualizzata"]', (string) $other->id)
        ->assertSee('Luogo secondario')
        ->click('summary[aria-label="Gestisci mappa"]')
        ->assertVisible('details[open]')
        ->assertSee('Imposta come predefinita');

    expect($browser->script('() => document.querySelector("select[aria-label=\\"Mappa visualizzata\\"]").selectedOptions[0].textContent'))
        ->toBe('Mappa secondaria · 1 pin');
    $actions = $browser->script('() => [...document.querySelectorAll("details[open] form")].map(form => form.action)');
    expect($actions)->toBe([
        url("/campaigns/{$campaign->id}/maps/{$other->id}/default"),
        url("/campaigns/{$campaign->id}/maps/{$other->id}"),
    ]);
    $browser->click('summary[aria-label="Gestisci mappa"]')
        ->click('button[aria-label="Carica mappa"]')
        ->assertSee('Carica Mappa di Gioco')
        ->click('button[aria-label="Chiudi caricamento mappa"]')
        ->navigate('/share/mappe-browser?tab=map')
        ->assertVisible('select[aria-label="Mappa visualizzata"]')
        ->select('select[aria-label="Mappa visualizzata"]', (string) $other->id)
        ->assertSee('Luogo secondario')
        ->assertMissing('summary[aria-label="Gestisci mappa"]')
        ->assertNoJavascriptErrors();
});

it('non mostra le azioni gm ai giocatori', function () {
    $gm = User::factory()->create();
    $player = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $gm->id]);
    $campaign->users()->attach($gm->id, ['role' => 'gm']);
    $campaign->users()->attach($player->id, ['role' => 'player']);
    CampaignMap::factory()->create(['campaign_id' => $campaign->id, 'title' => 'Mappa del gruppo']);

    visit('/login')
        ->type('email', $player->email)
        ->type('password', 'password')
        ->press('Entra nella Campagna')
        ->navigate("/campaigns/{$campaign->id}?tab=map")
        ->assertVisible('select[aria-label="Mappa visualizzata"]')
        ->assertMissing('summary[aria-label="Gestisci mappa"]')
        ->assertVisible('button[aria-label="Carica mappa"]')
        ->assertNoJavascriptErrors();
});

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

it('mantiene la modale del segnaposto scorrevole e ripristina la pagina su mobile', function () {
    $user = User::factory()->create();
    $campaign = Campaign::factory()->create(['owner_id' => $user->id]);
    $campaign->users()->attach($user->id, ['role' => 'gm']);
    MapPin::factory()->create([
        'campaign_id' => $campaign->id,
        'created_by' => $user->id,
        'title' => 'Luogo mobile',
    ]);
    WikiPage::factory()->create([
        'campaign_id' => $campaign->id,
        'created_by' => $user->id,
        'updated_by' => $user->id,
        'title' => 'Scheda mobile',
    ]);

    $browser = visit('/login')
        ->resize(375, 500)
        ->type('email', $user->email)
        ->type('password', 'password')
        ->press('Entra nella Campagna')
        ->navigate("/campaigns/{$campaign->id}?tab=map")
        ->assertSee('Luogo mobile');

    $browser->script('() => [...document.querySelectorAll("button")].find(button => button.getAttribute("@click") === "openPinPopover(pin)").scrollIntoView()');
    $originalScroll = $browser->script('() => window.scrollY');
    $browser->click('button[\\@click="openPinPopover(pin)"]')
        ->assertVisible('dialog[open]')
        ->click('Modifica nome e descrizione')
        ->assertVisible('#pin-dialog-name');

    $state = $browser->script('() => {
        const dialog = document.querySelector("dialog[open]");
        const content = dialog.querySelector(".overflow-y-auto");
        content.scrollTop = content.scrollHeight;
        const save = dialog.querySelector("[form=pin-dialog-form]").getBoundingClientRect();
        return {
            inBody: dialog.parentElement === document.body,
            bodyPosition: document.body.style.position,
            scrollable: content.scrollHeight > content.clientHeight && content.scrollTop > 0,
            saveVisible: save.top >= 0 && save.bottom <= innerHeight,
        };
    }');
    expect($state)->toMatchArray([
        'inBody' => true,
        'bodyPosition' => 'fixed',
        'scrollable' => true,
        'saveVisible' => true,
    ]);

    $browser->type('#pin_form_wiki_page_id_search', 'Scheda mobile')
        ->assertVisible('#pin_form_wiki_page_id_option_0')
        ->keys('#pin_form_wiki_page_id_search', 'Escape')
        ->assertVisible('dialog[open]');
    expect($browser->attribute('#pin_form_wiki_page_id_search', 'aria-expanded'))->toBe('false');

    $browser->click('button[aria-label="Chiudi segnaposto"]');
    expect($browser->script('() => ({ scrollY: window.scrollY, position: document.body.style.position })'))
        ->toMatchArray(['scrollY' => $originalScroll, 'position' => '']);

    $browser->click('button[\\@click="openPinPopover(pin)"]')
        ->assertVisible('dialog[open]')
        ->keys('button[aria-label="Chiudi segnaposto"]', 'Escape')
        ->assertMissing('dialog[open]');
    $browser->assertNoJavascriptErrors();
});
