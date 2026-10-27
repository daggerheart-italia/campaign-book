# Daggerheart Italia – Campaign Book

Applicazione web collaborativa per gestire il **libro di campagna** di [Daggerheart](https://www.daggerheart.com/): una wiki condivisa, mappe interattive con segnaposto e note di sessione, pensata per Game Master e giocatori di una stessa campagna. L'interfaccia è in italiano, con tema scuro e stile "glass".

## Funzionalità

### Campagne e ruoli
- Creazione di campagne e dashboard con l'elenco delle proprie campagne.
- Due ruoli: **GM** e **Giocatore**. Solo il GM modifica la campagna (membri, mappe, cestino, ecc.).
- Ingresso tramite **codici invito** distinti per GM e giocatori, anche con link diretto (`/join/{codice}`).
- Gestione dei membri: cambio ruolo e rimozione.

### Wiki
- Pagine in **Markdown** organizzate per categoria (Personaggi & PNG, Luoghi & Città, Fazioni & Gilde, Oggetti & Reliquie, Lore & Storia, Mostri & Minacce, Altro).
- **Cronologia delle revisioni** con ripristino di una versione precedente e compattazione delle revisioni.
- Eliminazione "morbida" con ripristino dal cestino.
- Creazione di una pagina direttamente da un segnaposto della mappa.
- **Esportazione** della campagna in Markdown.

### Mappe interattive
- Più mappe per campagna, caricate come immagine, con una mappa predefinita.
- Riquadro a dimensione fissa ("Adatta"); zoom 50–300% con scroll in entrambe le direzioni.
- Trascinamento con il tasto sinistro per spostare la mappa.
- **Nuovo segnaposto** con tasto destro o doppio clic sulla mappa.
- Segnaposto con titolo, descrizione, colore (modificabile dal popup) e collegamento opzionale a una pagina wiki.
- Trascinamento dei segnaposto per riposizionarli, con possibilità di **bloccarli** (il blocco è applicato anche lato server).
- Le posizioni sono salvate in percentuale, quindi restano corrette a qualsiasi zoom o dimensione.
- Elenco dei segnaposto e interruttore per mostrare/nascondere le etichette.

### Note di sessione
- Note in Markdown per sessione, con possibilità di fissarle in alto.
- Eliminazione con ripristino dal cestino.

### Cestino
- Pagine wiki, segnaposto e note eliminati finiscono nel cestino e possono essere ripristinati o svuotati definitivamente dal GM.

## Stack tecnico
- **PHP 8.4** e **Laravel 13**
- **Blade**, **Alpine.js** e **Tailwind CSS 4**, compilati con **Vite**
- Test con **PHPUnit**; formattazione con **Laravel Pint**
- Sviluppo con **Laravel Sail** (Docker); SQLite come database predefinito in `.env.example`

## Sviluppo locale

```bash
cp .env.example .env
vendor/bin/sail up -d
vendor/bin/sail composer install
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate
vendor/bin/sail npm install
vendor/bin/sail npm run dev
```

Se `vendor/` non esiste ancora, installa prima le dipendenze con un container Composer temporaneo (vedi la documentazione di Sail).

### Test e qualità

```bash
vendor/bin/sail artisan test --compact
vendor/bin/sail bin pint --dirty --format agent
vendor/bin/sail npm run build
```

## Struttura

| Percorso | Contenuto |
| --- | --- |
| `app/Models` | `Campaign`, `CampaignMap`, `MapPin`, `WikiPage`, `WikiPageRevision`, `SessionNote`, `User` |
| `app/Http/Controllers` | Campagne, mappe, segnaposto, wiki, note, autenticazione |
| `app/Policies` | Autorizzazioni GM / Giocatore |
| `resources/views/campaigns/partials` | Schede della campagna: wiki, mappa, note, membri, cestino |
| `routes/web.php` | Tutte le rotte (autenticate tranne landing, login e registrazione) |
| `docs/implementation-plan.md` | Piano di implementazione originale |

## Deploy

In produzione l'app gira su un normale stack PHP (Apache/Nginx) senza Docker. Lo script [`scripts/deploy.sh`](scripts/deploy.sh) aggiorna il codice, installa le dipendenze, esegue le migrazioni e rigenera le cache.
