# Linea guida per le icone

## Stato attuale

Le icone dell'interfaccia sono SVG inline scritti direttamente nelle view Blade. Non risulta adottata una libreria di icone: `package.json` non dichiara un set e il layout applicativo non carica un font o un servizio esterno per le icone.

Lo stile prevalente è quello delle icone outline:

- `viewBox="0 0 24 24"`;
- `fill="none"` e `stroke="currentColor"`;
- tratto da `2`, con estremità e giunzioni arrotondate;
- dimensione più comune di `16 × 16 px`; sono presenti anche icone da `12`, `14`, `20` e `24 px`;
- colore ereditato dal contesto, spesso con le utility `text-*` di Tailwind.

La palette segue in genere il significato dell'azione: ambra per l'accento principale, slate per elementi neutri, rose per azioni o stati distruttivi, emerald per conferme. Compaiono anche colori contestuali come indigo. Le icone sono spesso affiancate da testo; quelle isolate usano talvolta `title`.

Questi sono tratti ricorrenti, non uno standard già applicato ovunque: gli SVG sono duplicati o mantenuti a mano, le dimensioni variano e l'accessibilità delle icone isolate non è uniforme. Lo stile ricorda la famiglia Heroicons Outline, ma il markup esistente non dimostra che provenga da quel set.

Riferimenti rappresentativi: [markdown-editor.blade.php](../resources/views/components/markdown-editor.blade.php), [nav.blade.php](../resources/views/partials/nav.blade.php), [app.blade.php](../resources/views/layouts/app.blade.php) e [app.css](../resources/css/app.css).

## Standard adottato

### Set adottato

Usare **Lucide** come set predefinito per le icone funzionali. Il suo linguaggio outline, la griglia 24 × 24 e il tratto leggero sono compatibili con le icone già presenti e con la UI scura e minimale. La scelta definisce la convenzione di design; non implica che la libreria sia già installata.

Prima di introdurre il set, verificare che le icone necessarie siano disponibili, che la licenza sia compatibile con il progetto e che l'integrazione funzioni con Blade e Vite senza richieste a CDN o JavaScript aggiuntivo a runtime.

### Regole d'uso

1. Usare il set scelto per tutte le icone funzionali; evitare nuovi SVG disegnati a mano quando esiste un'icona equivalente.
2. Esporre le icone tramite un punto di rendering Blade centralizzato, così nome, dimensione e attributi di accessibilità non dipendono da SVG copiati nelle view.
3. Mantenere la geometria del set: griglia 24 × 24, stile outline e tratto uniforme. Non modificare singole icone per simulare un altro set.
4. Usare `16 × 16 px` come dimensione standard per toolbar e azioni; riservare `12–14 px` ai metadati compatti e `20–24 px` agli elementi di maggiore rilievo. Verificare sempre la leggibilità alla dimensione effettiva.
5. Lasciare che le icone ereditino `currentColor`; applicare il colore al componente o al contenitore con utility semantiche. Non codificare colori direttamente nei tracciati SVG.
6. Su tablet e desktop mostrare icona ed etichetta insieme. Su mobile (`< sm`) mostrare l'icona e rendere disponibile l'etichetta tramite tooltip.
7. I controlli composti solo da icona devono avere sempre un nome accessibile (`aria-label` sul controllo). Il tooltip non sostituisce il nome accessibile e non deve essere l'unica fonte d'informazione per azioni non ovvie.
8. Marcare come decorative le icone ridondanti rispetto a un'etichetta visibile (`aria-hidden="true"` e non focalizzabili).
9. Riservare SVG personalizzati a marchi, illustrazioni o simboli specifici del prodotto che non abbiano un equivalente adeguato nel set.

## Criteri per valutare alternative

Se si confrontano altri set, valutarli con gli stessi criteri:

- coerenza con il tratto outline e con la palette attuale;
- copertura dei casi d'uso dell'app (navigazione, editor, mappa, stati e azioni);
- resa alle dimensioni ridotte e su sfondo scuro;
- licenza e modalità di distribuzione;
- integrazione server-rendered con Blade e impatto sul bundle;
- possibilità di mantenere un unico stile senza mescolare famiglie o pesi.

Heroicons è una valida alternativa se si vuole restare molto vicini alla forma di alcuni SVG già presenti; Tabler offre una copertura ampia; Phosphor è più adatto se si desidera una maggiore varietà di pesi. In ogni caso, scegliere un solo set e un solo stile per l'interfaccia.
