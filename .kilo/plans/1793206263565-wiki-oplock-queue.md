# Piano: lock di modifica Wiki, coda FIFO e sincronizzazione delle viste

## Obiettivo
Prevenire conflitti di sovrascrittura quando più utenti modificano la stessa pagina Wiki contemporaneamente (oplock + coda FIFO), gestire gli stale lock, e **aggiungere aggiornamenti live in visualizzazione** per wiki, note di sessione e segnaposto di mappa.

## Ambito
- **Wiki**: lock + coda su `wiki.edit` (pagina dedicata) + aggiornamenti live in `wiki.show`. La verifica lato server copre tutte le mutazioni del contenuto, inclusi update e ripristino di revisione.
- **Note di sessione**: aggiornamenti live in visualizzazione (dashboard/campagna). **Nessun oplock completo** per il modal inline nella dashboard (troppo invasivo per il flusso attuale) - limitarsi a polling mirato + notifica che esiste una versione più recente.
- **Mappe/pin**: aggiornamenti live dei dati pin (posizione, titolo/descrizione/colore, collegamento wiki, stato `locked`) in visualizzazione mappa. Non applicare oplock al drag (meccanismo diverso, già gestito da `locked`).
- Il lock è consultivo per l'interfaccia, ma vincolante lato server per ogni mutazione Wiki coperta.
- Implementazione in fasi: prima lock/coda e difesa server Wiki; poi sync Wiki; infine sync note e pin. Tutte le fasi fanno parte dell'ambito, ma il rilascio delle successive è subordinato alla verifica della fase precedente.

## Coordinamento e struttura dati
Usare `Illuminate\Support\Facades\Cache` con un backend **condiviso tra tutte le istanze** e che supporti i lock atomici di Laravel. Verificare driver e configurazione effettivi prima dell'implementazione; se il backend non soddisfa questi requisiti, fermare il rilascio e configurarne uno idoneo, senza fallback a lock non atomici o cache locale in produzione.

Ogni operazione di lettura-modifica-scrittura sullo stato (acquisizione, heartbeat, join/leave, scadenza, offerta, accettazione, takeover, salvataggio Wiki e force-release) deve essere serializzata con un lock atomico di coordinamento per Wiki. Anche la verifica del proprietario e il salvataggio Wiki devono usare lo stesso coordinamento, così force-release/takeover non possono intercalarsi tra controllo e scrittura finché la lease di coordinamento resta valida.

**Garanzia e compromesso accettato:** il coordinamento usa una lease cache con durata superiore al limite massimo della richiesta/operazione DB e una transazione Wiki breve; in condizioni normali impedisce operazioni concorrenti sulla stessa pagina senza polling DB per gli heartbeat. Se un processo resta sospeso oltre la lease e poi riprende, il lock cache da solo non garantisce di fermarne la scrittura: questo rischio residuo è accettato in favore della semplicità/prestazioni. Non descrivere quindi il sistema come garanzia assoluta contro qualsiasi sospensione/arresto del processo. Configurare timeout massimi DB/request inferiori alla lease, ridurre la sezione critica e fallire esplicitamente se il coordinamento cache non è disponibile o non è acquisito. Il lock atomico di coordinamento è distinto dal lock editoriale.

Chiavi logiche:

| Chiave | Tipo | Contenuto | Note |
| --- | --- | --- | --- |
| `edit:wiki:{wiki_id}:state` | Un singolo array serializzato | `{owner: null|{user_id,name,tab_id,lock_token,acquired_at,last_heartbeat_at,expires_at}, queue: [{user_id,name,tab_id,requested_at}], offer: null|{user_id,name,tab_id,offered_at,offer_expires_at}}` | Tenere lock, FIFO e offerta in un unico valore logico, salvato con una singola scrittura cache sotto coordinamento, per evitare aggiornamenti parziali fra chiavi diverse. La coda contiene una sola voce per utente; la scheda che entra per prima è quella destinataria. Join successivi dello stesso utente sono idempotenti e non cambiano posizione/scheda; per cambiare scheda deve uscire e rientrare. La scadenza logica del lock editoriale non deve cancellare automaticamente la chiave contenente la coda. |
| `edit:wiki:{wiki_id}:coord` | Lock atomico temporaneo con lease | Mutua esclusione sulle operazioni che leggono/modificano lo stato e sulle mutazioni Wiki protette | Lease `coordination_ttl` configurata sopra il massimo timeout della richiesta/operazione DB, con margine; il timeout DB/request deve essere più breve. Il backend deve essere condiviso e supportare lock atomici. Una sospensione oltre la lease resta il rischio residuo accettato. |

`tab_id` è un UUID per scheda in `sessionStorage`, non il session ID Laravel. Rilevare schede duplicate via `BroadcastChannel` e rigenerare l'identificativo duplicato; se la funzionalità non è disponibile, applicare una strategia documentata di fallback e non trattare due schede con lo stesso `tab_id` come proprietari distinti. `lock_token` è un identificativo casuale di proprietà e invalida le richieste normali dopo takeover/rilascio, ma non è un fencing token monotono capace di neutralizzare un processo ripreso dopo la scadenza della lease.

## Parametri temporali
| Parametro | Valore consigliato | Motivazione |
| --- | --- | --- |
| `heartbeat_interval` | 20-25s | Frequente abbastanza senza spam |
| `lock_ttl` | 90s dall'ultimo heartbeat | 3-4x intervallo, dà margine a throttling/sospensione |
| `offer_ttl` | 45s dall'offerta di turno | Tempo per accettare il turno prima di passare al successivo |
| `coordination_ttl` | Maggiore del timeout massimo request/DB, con margine operativo | Mantiene il coordinamento durante transazione e commit. Definire anche timeout hard inferiori per request e DB; non basarsi su lease arbitrariamente breve. |

Regola unica: un lock editoriale è scaduto quando `expires_at <= now()`, impostato a `last_heartbeat_at + lock_ttl`. Non applicare una seconda soglia `stale_after`: `last_heartbeat_at` serve per diagnostica e calcolo della scadenza, mentre `expires_at` è il criterio operativo comune a tutte le rotte. Un'offerta di turno scade analogamente quando `offer_expires_at <= now()`.

## Rotte da aggiungere
Tutte sotto il gruppo autenticato. Ogni rotta verifica che la pagina Wiki appartenga alla campagna indicata; lock, coda e mutazioni richiedono `update`, gli endpoint di stato/sync richiedono `view`, e force-release richiede il permesso GM previsto dalla policy. I parametri `tab_id`, `lock_token`, `ids[]` e `map_id` sono validati lato server; ogni query resta scoped alla campagna autorizzata.

| Metodo | URI | Route name (proposto) | Scopo |
| --- | --- | --- | --- |
| `POST` | `/campaigns/{campaign}/wiki/{wiki}/edit/lock` | `wiki.edit.lock` | Acquisizione o coda. |
| `POST` | `/campaigns/{campaign}/wiki/{wiki}/edit/heartbeat` | `wiki.edit.heartbeat` | Prova di vita. |
| `POST` | `/campaigns/{campaign}/wiki/{wiki}/edit/release` | `wiki.edit.release` | Rilascio esplicito (salvataggio, annulla, `pagehide`/`sendBeacon`). Offre il turno al primo in coda. |
| `GET`  | `/campaigns/{campaign}/wiki/{wiki}/edit/state`  | `wiki.edit.state`  | Lettura non mutante di lock+coda per UI/polling; restituisce solo nomi/posizioni necessarie, mai `tab_id` o `lock_token`. |
| `POST` | `/campaigns/{campaign}/wiki/{wiki}/edit/queue/join` | `wiki.edit.queue.join` | Entra esplicitamente in coda. |
| `POST` | `/campaigns/{campaign}/wiki/{wiki}/edit/queue/leave`| `wiki.edit.queue.leave`| Esce dalla coda. |
| `POST` | `/campaigns/{campaign}/wiki/{wiki}/edit/queue/accept` | `wiki.edit.queue.accept` | Accetta un'offerta di turno ancora valida e acquisisce il lock editoriale. |
| `POST` | `/campaigns/{campaign}/wiki/{wiki}/edit/queue/advance` | `wiki.edit.queue.advance` | Elabora scadenza lock/offerta e avanza la coda; chiamata dal polling quando la pagina edit è visibile. |
| `POST` | `/campaigns/{campaign}/wiki/{wiki}/edit/force-release` | `wiki.edit.forceRelease` | Solo GM, con conferma + log. |
| `GET`  | `/campaigns/{campaign}/wiki/{wiki}/sync` | `wiki.sync` | Polling per aggiornamenti in visualizzazione (`wiki.show`). Restituisce stato/versione della pagina. |
| `GET`  | `/campaigns/{campaign}/notes/sync` | `notes.sync` | Sync incrementale paginato delle note. |
| `GET`  | `/campaigns/{campaign}/pins/sync` | `pins.sync` | Sync incrementale paginato dei pin; può filtrare per `map_id`. |

## Flussi

### Acquisizione (`lock`)
1. La pagina crea/legge un UUID `tab_id` da `sessionStorage` e lo invia con ogni richiesta di lock; l'identità autenticata resta l'utente Laravel.
2. Sotto il lock atomico di coordinamento, carica lock + coda dalla cache.
3. **Cleanup scaduto on-demand**: ogni endpoint che muta lo stato (lock, heartbeat, release, queue join/leave/accept/advance, takeover e force-release) controlla sotto coordinamento se lock editoriale o offerta sono scaduti; se sì, rimuove lo stato scaduto e offre il turno al successivo prima di completare la richiesta. `GET edit/state` resta read-only e non esegue cleanup.
4. Se lock esiste e non è scaduto:
   - Se utente e `tab_id` corrispondono -> rinnova il lock e restituisce `acquired` con il `lock_token` corrente.
   - Se è lo stesso utente ma una **tab diversa** -> mostra "Prendi controllo in questa scheda" con conferma esplicita. Il takeover genera un nuovo `lock_token`, rimuove un'eventuale posizione in coda dello stesso utente e rende non valido quello della scheda precedente.
   - Se altro utente -> restituisce `occupied` con dati proprietario; suggerisce `joinQueue`.
5. Se non c'è un proprietario/offerta valida e la coda è vuota, assegna il lock e restituisce `acquired`. Se esiste una coda, l'utente deve rispettare l'ordine FIFO: non può saltare chi è già in attesa. La coda contiene una sola posizione per utente, associata alla prima scheda che si è accodata; le altre schede dello stesso utente non ricevono posizioni aggiuntive.
6. Tutte le risposte che mutano lo stato sono idempotenti per utente/scheda ove applicabile. Una coda/offerta non concede permesso di salvare prima che il server abbia assegnato un lock attivo.

### Heartbeat
1. Sotto il coordinamento atomico, verifica utente, `tab_id` e `lock_token`.
2. Se lock scaduto -> restituisci `410 Gone` con stato `stale` e avvia il passaggio FIFO; il frontend ferma il timer e conserva il contenuto del form.
3. Se lock passato ad altro o token superato -> restituisci `423 Locked`.
4. Altrimenti aggiorna `last_heartbeat_at` e `expires_at = last_heartbeat_at + lock_ttl`.

### Rilascio (`release`) e promozione
1. Sotto il coordinamento atomico, verifica proprietà, `tab_id` e `lock_token`; force-release segue la propria autorizzazione e conferma esplicita.
2. Rimuovi il lock attivo e crea un'offerta al primo elemento FIFO, senza concedergli ancora il permesso di modifica. Conserva quell'elemento in testa alla coda e registra `offer_expires_at`.
3. `POST queue/advance` controlla e avanza le scadenze sotto coordinamento; il frontend lo chiama ogni 5-10 secondi mentre la pagina edit è visibile, poi legge `GET edit/state` per mostrare l'offerta al destinatario. Lo stato pubblico non espone `tab_id` o token. Solo `queue/accept`, verificata identità e scheda destinataria, rimuove l'elemento dalla coda e crea un lock attivo con nuovo `lock_token`.
4. Se l'utente rifiuta/esce o non accetta entro `offer_ttl`, scade l'offerta e si propone il turno al successivo; `queue/leave` rimuove anche l'offerta appartenente a quell'utente. Non lasciare la pagina bloccata da un destinatario offline. Un'offerta pendente impedisce ad altri di saltare la fila.
5. Le modifiche a lock e coda avvengono nella stessa sezione atomica. Eventuali notifiche/log sono successivi e non determinano l'esito del lock.

### Mutazioni Wiki (difesa lato server)
1. Proteggere `WikiController@update` e `restoreRevision` (ripristina contenuto e crea revisione); creazione di una nuova pagina non richiede lock editoriale. Verificare tutte le altre route che cambiano titolo, categoria, parent o contenuto e includerle nella stessa regola oppure dichiarare esplicitamente l'eccezione.
2. Per ogni mutazione, sotto lo stesso coordinamento atomico, verificare lock non scaduto, utente, `tab_id` e `lock_token`. L'HTML form include i token correnti; nessun client può auto-assegnarsi un lock inviando un token arbitrario.
3. Se proprietà/token/scadenza non corrispondono, rispondere `409 Conflict` con messaggio e link/stato per tornare alla coda; il contenuto del form va preservato. Non sovrascrivere silenziosamente né restituire successo.
4. Dopo verifica, eseguire aggiornamento e creazione della revisione nella stessa transazione DB, mantenendo il coordinamento fino al commit. In caso di errore/rollback, non rilasciare il lock editoriale; restituire un errore esplicito. Solo dopo commit riuscito rilasciare e avviare l'offerta FIFO.
5. Force-release e takeover usano lo stesso coordinamento: nessuno dei due può invalidare un lock tra verifica e commit. Un lock-token superato non può salvare anche se apparteneva allo stesso utente.
6. Il force-release è GM-only, richiede conferma esplicita e scrive un log strutturato con campagna, pagina, GM, proprietario precedente e timestamp; il log non sostituisce la verifica delle autorizzazioni.

### Ripristino revisione con lock
La UI di ripristino non invia una mutazione diretta senza lock. Selezionare "Ripristina" apre la pagina editor con il lock acquisito e i campi precompilati dalla revisione richiesta (titolo, categoria, parent e contenuto); la revisione deve appartenere alla Wiki e alla campagna correnti. Il salvataggio passa dal normale update protetto, crea una nuova revisione con riepilogo di ripristino e rilascia il lock dopo il commit. Se si mantiene una route `restoreRevision` diretta, deve richiedere i token del lock e la stessa transazione/coordinamento; non deve esistere alcun percorso UI/API che aggiri il lock.

## Aggiornamenti live in visualizzazione (wiki/note/mappa)

L'obiettivo è **aggiornare i dati mostrati quando vengono notificate modifiche**, senza sincronizzazione in tempo reale del testo mentre si legge passivamente. Si basa su polling mirato con confronto `updated_at`/hash, con notifica non invasiva all'utente.

### Principi
- **Non intrusivo**: mostrare un banner *"È stata aggiornata. [Aggiorna]"* o *"Nuova versione disponibile"*; **mai** sostituire automaticamente il contenuto letto dall'utente senza conferma (soprattutto su pagine aperte da tempo).
- **Leggero**: polling ogni 20-30s solo su viste aperte e rilevanti (wiki.show, tab note, mappa attiva). Non pollare su tutte le pagine.
- **Consapevole**: in `wiki.show` mostrare anche stato lock *"In modifica da..."* se presente (derivato da `edit.state` leggero o cache).
- **Diff sicuro**: per wiki, preferire ricaricamento parziale o redirect "aggiorna" che ricarica la pagina con dati freschi. Le revisioni restano punto di riferimento.

### Wiki (`wiki.show`)
| Aspetto | Approccio |
| --- | --- |
| **Tracciamento** | Salvare `wiki.updated_at` e `wiki.updated_by` al caricamento in Alpine (`lastKnownUpdatedAt`, `lastKnownUpdatedById`). |
| **Endpoint** | `GET /campaigns/{campaign}/wiki/{wiki}/sync` -> `{ updated_at, updated_by: {id,name}, has_new_revision: bool, lock_state?: {active: bool, owner?: {user_id,name}} }`. Non esporre `tab_id`, `lock_token` o identificativi di sessione ad altri utenti. |
| **Polling** | Ogni 25s finché pagina aperta. Se la versione cambia, mostra il banner anche quando l'autore coincide con l'utente corrente ma la modifica proviene da un'altra scheda. Usare l'autore solo per il testo informativo. |
| **Azione** | Pulsante "Aggiorna pagina" fa `location.reload()` (semplice e sicuro). Link "Vedi ultima revisione" punta a `wiki.revisions`. Non sovrascrive il DOM automaticamente. |
| **Lock awareness** | Usare `lock_state` dal sync oppure chiamata leggera a `edit/state`. Mostrare pill *"In modifica da [Nome]"* se attivo. |

### Note di sessione (dashboard)
Le note sono gestite in `resources/views/campaigns/partials/notes.blade.php` (lista + modal edit inline).

| Aspetto | Approccio |
| --- | --- |
| **Tracciamento** | Per ogni nota renderizzata salvare `note.sync_version` in Alpine; inizializzare anche il cursor/generazione di campagna corrispondente alla snapshot server-rendered. |
| **Endpoint** | `GET /campaigns/{campaign}/notes/sync?cursor=...` -> pagina incrementale di `{ changes:[{id,updated_at,updated_by:{id,name}|null,title,session_date,is_pinned,deleted}], next_cursor, has_more, reset_required }`, con page size limitata (massimo 100). Senza cursor restituisce la prima pagina di snapshot completa; successive richieste seguono `next_cursor`. Cursor opaco e validato; se si mantiene il filtro `ids[]`, limitarne cardinalità e scope campagna. Includere tombstone per soft-delete, non contenuto. |
| **Polling** | Ogni 25-30s quando tab "Note" è attivo. Non sovrapporre richieste di polling; consumare tutte le pagine dello snapshot prima di avanzare il cursor. Se la versione cambia, indipendentemente dall'identità dell'autore, evidenziare la card (bordi/alone) + banner *"Questa nota è stata aggiornata [da Nome]. [Aggiorna lista]"*. |
| **Azione** | "Aggiorna lista" ricarica la sezione note o fa refresh parziale (Alpine) rifacendo fetch dei dati. Se la nota è aperta nel modal di modifica dell'utente corrente, mostrare avviso *"Questa nota è stata modificata. Ricarica per vedere la versione più recente."* senza chiudere forzatamente il modal. |
| **Nota** | Non applicare oplock al modal inline per ora; focus su notifica + aggiornamento consapevole. Mantieni il contenuto nel form se aperto. |

### Mappa / Segnaposto
Nella vista mappa (`resources/views/campaigns/partials/map.blade.php`) i pin sono già caricati come JS object.

| Aspetto | Approccio |
| --- | --- |
| **Tracciamento** | Mantenere `pin.sync_version` per ogni pin in `this.pins` e il cursor/generazione iniziale della snapshot. Quando cambia `map_id`, caricare una nuova snapshot per la mappa selezionata prima di riprendere il delta. |
| **Endpoint** | Preferire l'estensione di `pins.index` già presente, se il contratto è compatibile; altrimenti introdurre `pins.sync` senza duplicare query/serializzazione. Accettare un cursor incrementale e rispondere con pagine (massimo 100) di `{ changes:[{id,x,y,title,description,color,wiki_page_id,locked,updated_at,updated_by:{id,name}|null,campaign_map_id,deleted}], next_cursor, has_more, reset_required }`. Senza cursor avvia una snapshot completa paginata. Validare `map_id` e limitarlo alla campagna. Includere tombstone per soft-delete. |
| **Polling** | Ogni 25-30s mentre mappa visibile (tab mappa attivo). Non sovrapporre richieste; consumare tutte le pagine prima di avanzare il cursor e applicare i delta per ID. |
| **Gestione aggiornamenti** | - **Posizione**: se pin spostato da altro utente e **non** bloccato/localmente in drag -> aggiornare posizione con transizione leggera (`transition: transform 150ms` o CSS). Se `this.draggingPin?.id === pin.id` oppure `isDragging`, **non** sovrascrivere posizione finché drag non finisce. - **Altri campi** (titolo/descrizione/colore/wiki link/locked): aggiornare se diversi, mostrando piccolo badge *"Aggiornato da [Nome]"* sul popup/pin per 3-5 secondi. - **Aggiunta/rimozione**: gestire inserimento/rimozione pin se cambiati rispetto allo stato locale. - **Locked**: se `locked` cambia, aggiornare UI (cursor/style) immediatamente. |
| **Blocco esistente** | `locked` resta come oggi (protezione deliberata). Non introdurre claim temporaneo. |

### Implementazione tecnica generale
| Elemento | Specifica |
| --- | --- |
| **Versioning** | Sync note/pin incrementale con cursor opaco basato su versione monotona per campagna e paginazione stabile; non affidarsi solo a timestamp con precisione potenzialmente insufficiente. Aggiungere un contatore versionale alla campagna e `sync_version` alle note/pin; le righe preesistenti sono incluse nella snapshot iniziale anche se non hanno ancora una versione successiva al cursor. Ogni mutazione assegna una nuova versione all'entità nella stessa transazione; soft-deleted restano interrogabili come tombstone (`withTrashed`). Si tratta di una scrittura DB mirata inclusa nelle mutazioni già esistenti, non di polling DB: gli heartbeat e il lock editoriale restano in cache. Il cursor contiene generazione, versione snapshot e posizione stabile (versione/id); applicare tutte le pagine dello snapshot prima di avanzare la versione locale. Incrementare la generazione su hard-delete/purge: cursor precedente riceve `reset_required` e la UI rifà una snapshot completa paginata. |
| **Autore note/pin** | Aggiungere colonne nullable `updated_by` con foreign key coerente al progetto per note e pin, esponendo la relazione utente. Popolare l'autore in ogni percorso di modifica, inclusi update, toggle pin, drag, creazione, soft-delete/ripristino e altri flussi che cambiano dati mostrati; verificare tutti i punti di scrittura. Record preesistenti senza autore restituiscono `null` e UI con testo generico. |
| **Endpoint leggeri** | Ritornare solo campi necessari per confronto + rendering minimo. Evitare relazioni pesanti e contenuti completi non necessari. |
| **Autorizzazione** | Stesse policy `view` sulla campagna per tutti gli endpoint sync; le policy `update` restano obbligatorie per lock e mutazioni. Binding e query devono verificare che pagina, nota, mappa e pin appartengano alla campagna indicata, anche per ID forniti dal client. |
| **Alpine.js** | Un solo intervallo per widget/pagina rilevante. Avviare il polling solo con tab/pannello visibile e pagina visibile (`visibilitychange`); sospenderlo in background e riprenderlo al ritorno. Pulire timer e listener alla distruzione del componente. |
| **Rate limiting** | Applicare un limite server-side ragionevole agli endpoint di polling; il solo intervallo client non protegge da richieste manuali o client difettosi. |
| **UX** | Banner/freccia "nuovo" non bloccanti, cliccabili per aggiornare. Mai forzare reload automatico. Mantenere contenuto nel DOM/modal durante notifiche. |
| **Sicurezza** | Sync restituisce solo dati autorizzati; validare dimensione/numero degli `ids[]`, applicare scope campagna a ogni query e non esporre token di lock o dati non necessari. |

### Rotte aggiuntive dettagliate
| Rotta | Dettagli |
| --- | --- |
| `wiki.sync` | Autorizza `view` campagna e verifica l'appartenenza Wiki-campagna. Restituisce dati essenziali, versione e `updated_by`; lock-state può esporre solo nome/ID utente proprietario, mai `tab_id` o token. |
| `notes.sync` | Autorizza `view` campagna. Restituisce modifiche successive al cursor in pagine limitate, incluse tombstone; ogni query è scoped alla campagna. `reset_required` richiede snapshot paginata. |
| `pins.sync` / `pins.index` | Autorizza `view` campagna. Riutilizza/estende `pins.index` quando possibile; filtra per mappa validata appartenente alla campagna e restituisce delta/tombstone paginati, non una lista illimitata. |

### Ordine implementazione
1. Verificare driver cache effettivo in ogni ambiente e supporto a lock atomici condivisi; documentare la configurazione richiesta e fallire esplicitamente se il coordinamento non è disponibile.
2. Implementare `WikiEditLockService` (o servizio equivalente) per lock atomico, lock editoriale, FIFO per utente, offerte/accettazione/scadenza, takeover, token, heartbeat, release, avanzamento esplicito e stato. Non duplicare la logica di coordinamento nei controller.
3. Aggiungere rotte/controller lock e test automatici per servizio e autorizzazioni; verificare scope della route Wiki-campagna.
4. Proteggere `update` e `restoreRevision`: verifica token e proprietà nel coordinamento, aggiornamento + revisione transazionali, rilascio solo dopo commit. Verificare ogni altra mutazione Wiki.
5. Aggiornare `wiki.edit` con UUID per scheda, acquisizione/takeover, heartbeat, accettazione coda, contenuto preservato in caso di lock perso e messaggi accessibili. `pagehide`/`sendBeacon` è solo best-effort, non parte della correttezza.
6. Implementare e testare `wiki.sync`; aggiornare `wiki.show` con polling, banner e stato lock non sensibile.
7. Aggiungere migrazioni per `updated_by`, contatore e generazione di sync campagna e `sync_version` per note/pin; aggiornare ogni percorso di scrittura in transazione e coprire i casi con autore nullo.
8. Implementare sync note/pin (riusando `pins.index` se possibile) con cursor opaco, snapshot paginata, tombstone e `reset_required`; aggiungere test endpoint, pagination e scope.
9. Aggiornare le viste note e mappa; non sostituire contenuto modal aperto né coordinate del pin durante drag locale.
10. Eseguire test automatici mirati per lock/concorrenza/autorizzazioni/versioni e test manuali per UI, sospensione scheda, drag e modal aperto.

## Validazione / Test da fare
- [ ] Lock atomico: due acquisizioni concorrenti non creano due proprietari; join/leave/promozione non perdono o duplicano voci; `GET edit/state` è read-only e `queue/advance` muta solo sotto coordinamento
- [ ] Tab: stesso utente nella stessa scheda rinnova; takeover in scheda diversa invalida il vecchio token
- [ ] Offerta FIFO: destinatario vede l'offerta, accetta entro la finestra; rifiuto/uscita/scadenza passa al successivo senza saltare la fila
- [ ] Scadenza: dopo `lock_ttl` senza heartbeat, lock scaduto e offerta al primo in coda
- [ ] Difesa `update` e restore: 409 per proprietario/token/scadenza non validi; restore aperto nell'editor con lock; revisioni di altra Wiki/campagna rifiutate; nessuna revisione parziale in caso di rollback
- [ ] Wiki show: banner "aggiornata da altri" + reload consapevole
- [ ] Coda per utente: schede multiple dello stesso utente occupano una sola posizione; solo la scheda destinataria accetta l'offerta
- [ ] Sync note/pin: cursor incrementale e paginato non perde cambi concorrenti; soft-delete genera tombstone, hard purge richiede reset; cambio mappa forza nuova snapshot; ID/mappe fuori campagna non restituiscono dati; autore storico nullo gestito
- [ ] Note dashboard: card evidenziata; se il modal è aperto il form resta intatto e avvisa della versione nuova
- [ ] Mappa: aggiunta/rimozione e aggiornamento pin; aggiornamento remoto non interrompe drag locale; `locked` rispettato
- [ ] Nessun auto-reload forzato
- [ ] Polling sospeso quando vista/pagina è nascosta e riavviato al ritorno
- [ ] Test multi-istanza/configurazione: tutte le istanze condividono cache e lock atomici; indisponibilità del backend non consente salvataggi non coordinati
- [ ] Timeout operativi: max request/DB inferiore a `coordination_ttl`; documentare che una sospensione del processo oltre la lease può superare la garanzia del cache lock


## Gestione casi particolari
| Caso | Trattamento |
| --- | --- |
| **Utente si dimentica e si allontana** | PC in sospensione/tab throttled interrompe heartbeat; alla scadenza `expires_at` il lock è considerato libero e il prossimo turno viene offerto on-demand. |
| **Browser crash / kill tab** | Nessun `sendBeacon` è garantito. La scadenza del lock/offerta e il cleanup on-demand sono sufficienti per la correttezza. |
| **Connessione persa** | Heartbeat fallisce; alla riconnessione la UI interroga lo stato. Se token perso/scaduto, conserva il form, ferma il timer e propone coda/riacquisizione. |
| **Salvataggio riuscito** | Update e revisione sono committati insieme; successivamente il lock viene rilasciato e il turno offerto al successivo. Un errore DB non rilascia il lock. |
| **GM forza rilascio** | Permesso con conferma esplicita, coordinato atomicamente e registrato in log strutturato. Token precedente è subito invalido. |
| **Stesso utente due tab** | Ogni tab ha `tab_id` distinto; takeover confermato invalida il token della tab precedente e non la mette in coda. |
| **Destinatario coda offline** | L'offerta ha scadenza; alla scadenza o all'uscita si offre il turno al successivo. Nessuna tab offline può salvare senza accettazione e token attivo. |
| **Backend cache non disponibile** | Rispondere con errore esplicito e non consentire mutazioni Wiki non coordinate; non degradare a lock best-effort. |

## Note implementative
- **Cache driver**: verificare il driver configurato realmente in ogni ambiente; non assumere che file/database/Redis siano intercambiabili per atomic lock o condivisione multi-istanza. Redis non è obbligatorio se il backend scelto soddisfa i requisiti, ma il requisito non è negoziabile.
- **Identità scheda**: UUID casuale in `sessionStorage`, validato lato server. Non usare il session ID Laravel come identità di tab e non esporre `tab_id`/`lock_token` negli endpoint di lettura.
- **Atomicità**: usare le API di lock atomico del driver scelto per l'intero ciclo read-modify-write. Non usare sequenze Cache::get/put non protette. Gestire timeout/lease adeguati e indisponibilità come errore, senza salvare fuori dal coordinamento.
- **Token di proprietà**: token casuale nuovo per ogni assegnazione o takeover. Ogni mutazione verifica il token corrente sotto coordinamento; token vecchi non sono riutilizzabili dopo scadenza, takeover o force-release. La durata del lock atomico di coordinamento deve superare il limite massimo della transazione Wiki e la fase di rilascio, oppure essere rinnovata in modo sicuro: non deve scadere mentre la mutazione è ancora in corso.
- **Dati note/pin**: aggiungere autore ultimo aggiornamento e versione sync; aggiornare autore/versione in ogni mutazione atomica, senza sovrascrivere l'autore con null sui flussi che non lo inviano.
- **Niente auto-redirect**: toast con scelta, mai forzare navigazione al momento della promozione.
- **Contenuto non perso**: in caso di stale/lock perso durante edit, mantenere valori del form nel DOM.

## Ordine esecuzione (sintesi)
Seguire l'ordine implementativo definito sopra: non eseguire in parallelo i passaggi che dipendono da lock, token e versionamento già verificati.
