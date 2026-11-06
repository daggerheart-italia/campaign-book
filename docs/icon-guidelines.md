# Linea guida per le icone

## Standard adottato

Usare **Lucide** come set predefinito per le icone funzionali. Il linguaggio outline, la griglia 24 × 24 e il tratto leggero sono coerenti con la UI scura e minimale dell'app.

Le icone sono fornite da `mallardduck/blade-lucide-icons` e renderizzate lato server attraverso Blade Icons. Non aggiungere Lucide al bundle JavaScript o caricare icone da CDN. Il pacchetto Composer contiene il set completo; le view renderizzano soltanto gli SVG effettivamente richiesti. La configurazione disabilita la registrazione di un componente Blade per ogni icona, mentre il wrapper centrale mantiene un'interfaccia stabile.

## Regole d'uso

1. Usare il set Lucide per tutte le icone funzionali; evitare nuovi SVG disegnati a mano quando esiste un'icona equivalente.
2. Usare `<x-app-icon name="search" />` per un'icona singola. Il wrapper centralizza il prefisso `lucide-`, la dimensione predefinita e gli attributi decorativi.
3. Per azioni con etichetta, usare `<x-icon-label icon="search" label="Cerca" />`. Mostra icona ed etichetta da `sm` in su; sotto `sm` mantiene il testo accessibile e lo presenta come tooltip CSS su focus o hover.
4. Per controlli costituiti soltanto da un'icona, fornire sempre un nome accessibile (`aria-label` sul link o sul pulsante). Su mobile il nome è usato anche come tooltip CSS; il tooltip non sostituisce il nome accessibile.
5. Mantenere la geometria del set: griglia 24 × 24, stile outline e tratto uniforme. Non modificare singole icone per simulare un altro set.
6. Usare `16 × 16 px` come dimensione standard per toolbar e azioni; riservare `12–14 px` ai metadati compatti e `20–24 px` agli elementi di maggiore rilievo. Verificare sempre la leggibilità alla dimensione effettiva.
7. Lasciare che le icone ereditino `currentColor`; applicare il colore al componente o al contenitore con utility semantiche. Non codificare colori direttamente nei tracciati SVG.
8. Marcare come decorative le icone ridondanti rispetto a un'etichetta (`aria-hidden="true"` e non focalizzabili); è il comportamento predefinito di `<x-app-icon>`.
9. Riservare SVG personalizzati a marchi, illustrazioni o simboli specifici del prodotto che non abbiano un equivalente adeguato nel set.

Il comportamento di `<x-icon-label>` può essere personalizzato con due proprietà booleane indipendenti:

- `:show-on-mobile="true"` mostra l'etichetta anche sotto `sm`.
- `:hide-on-desktop="true"` nasconde visivamente l'etichetta da `sm` in su, mantenendola disponibile agli screen reader.

Le proprietà possono essere combinate: ad esempio, `<x-icon-label icon="search" label="Cerca" :show-on-mobile="true" :hide-on-desktop="true" />` mostra il testo solo su mobile. Senza proprietà aggiuntive rimane il comportamento predefinito.

## Criteri per valutare alternative

Se si confrontano altri set, valutarli con gli stessi criteri:

- coerenza con il tratto outline e con la palette attuale;
- copertura dei casi d'uso dell'app (navigazione, editor, mappa, stati e azioni);
- resa alle dimensioni ridotte e su sfondo scuro;
- licenza e modalità di distribuzione;
- integrazione server-rendered con Blade e impatto sul bundle;
- possibilità di mantenere un unico stile senza mescolare famiglie o pesi.

Heroicons è una valida alternativa se si vuole restare molto vicini alla forma di alcuni SVG già presenti; Tabler offre una copertura ampia; Phosphor è più adatto se si desidera una maggiore varietà di pesi. In ogni caso, scegliere un solo set e un solo stile per l'interfaccia.
