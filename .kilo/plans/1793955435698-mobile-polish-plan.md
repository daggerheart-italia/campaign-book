# Mobile Polish Plan

Scope: mobile-first visual refinement (approach A) across the Campaign Book UI, plus a unified pin modal with searchable wiki-page dropdown.

## Context

The Campaign Book (Laravel 13, Blade + Alpine.js 3.17 + Tailwind 4) is a premium dark/glass app for Daggerheart RPG. On small screens several controls and rows feel cramped or inconsistent:

- Campaign tab bar (`show.blade.php:96`) has 5-6 `whitespace-nowrap` buttons; the "Cestino" button is pushed by `ml-auto` and can scroll out of view. Button radii are inconsistent (`rounded-xl px-5` vs `rounded-lg px-4`).
- Map panel (`partials/map.blade.php`): control bar wraps awkwardly; pin roster is a 1/2/3-col grid with tight cards; the help text row is long.
- Markdown editor toolbar (`components/markdown-editor.blade.php`) has text labels (`[[ Pagina ]]`, `• Elenco`, `1. Elenco`) that overlap below 360px.
- Wiki show page (`wiki/show.blade.php:54`) action buttons (Revisioni/Modifica/Elimina) compress into a messy row.
- Pin modals (`partials/map.blade.php`) use plain `<select>` elements listing all wiki pages for the linked wiki page — unwieldy on mobile and desktop when the list is long. Worse, the same wiki linking logic is duplicated across three places: the "Create Pin" form, the "Pin Details" edit form, and the inline "collega a una scheda esistente" dropdown with its own "Collega" button.

## Approach A (polish, no restructure)

1. Normalize the campaign tab bar: consistent `rounded-lg`, tighter horizontal padding on `<sm`, wrap-friendly, and keep the trash button pinned right without breaking layout.
2. Map controls: stackable row, smaller gaps, and make the pin roster a 1-col grid on `<sm` with larger touch targets.
3. Markdown editor toolbar: icon-only on `<sm` with tooltip, keep text on `>=sm`.
4. Wiki show actions: wrap into a single scrollable row with consistent `shrink-0` buttons.
5. Pin modals: replace the plain `<select>` for "Collega a Scheda Wiki" with the existing searchable `parentPageSelect` component (same one used for parent pages), on both desktop and mobile.

## New: unified pin modal (approach B)

Merge the two separate modals (`createModalOpen` and `detailsModalOpen`) into a single modal with three sub-states, so the create and edit forms look visually identical:

| Sub-state | Trigger | Content |
|---|---|---|
| **Crea** | click on empty map area | Form with Nome, Descrizione, Colore (round swatches, same as Edit), Collega Wiki (`parentPageSelect`). No "nessuna pagina collegata" warning. Button "Salva Segnaposto" -> `saveNewPin`. |
| **Visualizza** | click on existing pin | Title, description, "Leggi Scheda Wiki" button *or* "Crea Pagina Wiki" button + `parentPageSelect`, color swatches, blocca, elimina, "Modifica" button. |
| **Modifica** | "Modifica" button from Visualizza | Form identical to Crea (same fields, same color swatches, same `parentPageSelect`). Button "Salva" -> `savePinDetails`. |

This removes the inline "collega a una scheda esistente" dropdown with its "Collega" button entirely, and unifies the color picker (round swatches from the details modal replace the `<select>` in the create form).

## New: searchable wiki-page dropdown in pin modals

- Reuse `parentPageSelect` Alpine component (`resources/js/parent-page-select.js` + `resources/views/components/parent-page-select.blade.php`).
- Pass options as `category — title` labels (same shape used for parent pages).
- Wire the hidden `wiki_page_id` input to the component's `selected` value via `x-modelable="value"` (verified against Alpine 3.17.5 `x-modelable.js` source: it entangles the component's `selected` getter/setter with the element's `x-model` get/set).
- Apply in all three sub-states where wiki linking appears (Crea, Modifica, Visualizza).

## New: icon-only action buttons on wiki show page

Apply the same icon-only pattern already used for the markdown editor toolbar to the wiki show action bar (`wiki/show.blade.php:54-74`):

- **Desktop (`>=sm`):** keep current behavior — icon + text label for each button (Revisioni, Modifica, Elimina).
- **Mobile (`<sm`):** render icon-only buttons with `title`/`aria-label` tooltips. The "Revisioni" badge count is hidden on mobile (the tooltip still announces it).
- Wrap the group in a horizontally scrollable container (`overflow-x-auto scrollbar-none`) so it never pushes the article title.
- Keep the existing `<form>` for the delete action intact — only the button glyph changes to icon-only.

## Files to change

- `resources/views/campaigns/show.blade.php` — tab bar.
- `resources/views/campaigns/partials/map.blade.php` — control bar, roster, unified pin modal (three sub-states), Alpine data model (`modalMode`, `pinForm`, `selectedPin`).
- `resources/views/components/markdown-editor.blade.php` — toolbar responsive classes.
- `resources/views/wiki/show.blade.php` — action buttons (icon-only on `<sm`).
- `resources/css/app.css` — optional utility tweaks (touch targets, scrollbars).
- `resources/js/parent-page-select.js` / `resources/views/components/parent-page-select.blade.php` — minor styling to fit inside a modal (full width, max-h).

## Validation

- `vendor/bin/sail npm run build` compiles without errors.
- `vendor/bin/sail bin pint --dirty --format agent` on touched PHP files (none expected).
- **PHPUnit suite must stay green:** `vendor/bin/sail artisan test` (alias `vendor/bin/sail composer run test`). Browser tests are run separately with Pest; `tests/Browser/AllRoutesSmokeTest.php` visits `/campaigns/{id}/pins` and `/campaigns/{id}/maps/{map}/print`.
- Browser tests: `./scripts/browser-procs.sh --kill && ./vendor/bin/sail pest tests/Browser/` — run on desktop (default) and mobile devices (`Device::IPHONE_SE`, `Device::IPHONE_15`, `Device::MOBILE`) with `screenshot()` captures into `tests/Browser/Screenshots/` for visual regression review.
- Manual check at 320px, 375px, 768px, 1024px on: campaign tabs, map tab, wiki edit modal, wiki show page, pin create/details modals.

## Incremental Todo List

### Polish (approach A)
- [x] Normalize campaign tab bar (show.blade.php)
- [x] Map controls stackable + roster 1-col on <sm
- [x] Markdown editor toolbar icon-only on `<sm`
- [x] Wiki show actions icon-only + scrollable

### Unified pin modal (approach B)
- [x] Parameterize parent-page-select (name/modelable/label)
- [x] Add `modalMode`/`modalOpen` state + `openPinModal` helpers
- [x] Update `handleMapInsert` to open create modal via helper
- [x] Update `openPinPopover` to open view modal via helper
- [x] Update `applyPinChange`/`deletePin` to use `modalOpen`/`modalMode`
- [x] 1. Replace create modal markup with unified `x-if` sub-states (Crea)
- [x] 2. Replace details modal markup with unified view/edit sub-states (Visualizza/Modifica)
- [x] 3. Wire `parentPageSelect` in create mode (`pinForm.wiki_page_id`)
- [x] 4. Wire `parentPageSelect` in edit mode (`pinForm.wiki_page_id`)
- [x] 5. Wire `parentPageSelect` in view mode + `linkWikiId` watcher -> `linkPinToWiki`
- [x] 6. Add `color` to `savePinDetails` PUT body
- [x] 7. Run build + pint + artisan test

- No restructuring into bottom sheets, no new JS dependencies, no backend changes.