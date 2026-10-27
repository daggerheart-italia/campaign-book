# Daggerheart Lore‑Sharing App – Updated Implementation Plan (Laravel, Alpine.js, No Yjs)

## Goal Description
Create a web‑application for the tabletop RPG *Daggerheart* that provides a collaborative campaign‑book/wiki, a map, and notes. The app will be built with **Laravel 11** (using **Alpine.js** for front‑end interactivity) and **MySQL** for persistence. The UI follows a **dark‑city** theme with glass‑morphism, micro‑animations, and the Google Font *Outfit*.

## User‑Approved Decisions
- **Feature Scope**: Campaign book / wiki only (no video chat, no AI lore generation in the first version).
- **Tech Stack**: Laravel backend, Alpine.js for interactivity, Leaflet.js for map. (Yjs deferred)
- **Hosting**: Development with Laravel Sail (Docker) but production will be a plain PHP/Apache (or Nginx) deployment without Docker Compose.
- **Visual Theme**: *dark‑city* – deep grays, neon blue accents, glass‑like cards.
- **Permissions**: GM / Player roles; GMs can invite players to a campaign book.
- **Export**: Markdown export of wiki pages (downloadable).
- **Future**: Offline sync, AI lore generation, video chat – deferred.

## Proposed Architecture
### Backend (Laravel)
- **Models & Migrations**
  - `User` (default) – pivot `campaign_user` with `role` (`gm`, `player`).
  - `Campaign` – `id`, `title`, `slug`, `owner_id`, `theme`.
  - `WikiPage` – `id`, `campaign_id`, `title`, `slug`, `content_md`.
  - `MapPin` – `id`, `campaign_id`, `title`, `lat`, `lng`, `linked_page_id`.
  - `SessionNote` – `id`, `campaign_id`, `author_id`, `content_md`.
- **Controllers** – RESTful API under `/api` protected by Sanctum token auth.
- **Policies** – Enforce GM vs. Player permissions (only GM can manage invitations and campaign settings).
- **Export Route** – `GET /api/campaign/{id}/export` returns a zip of markdown files.
- **Mail** – Invitation emails with secure links.
- **Testing** – PHPUnit tests for models, policies, and API endpoints.

### Front‑end (Blade + Alpine.js)
- **Layout** – `resources/views/layouts/app.blade.php` with dark‑city CSS variables, Outfit font, and glass‑morphism utility classes.
- **Pages**
  1. **Dashboard** – List campaigns, create new campaign.
  2. **Campaign Workspace** – Tabbed view: *Wiki*, *Map*, *Notes*.
  3. **Wiki Editor** – Markdown textarea + live preview (`marked.js`).
  4. **Map** – Leaflet.js map with draggable pins linked to wiki pages.
  5. **Notes** – Simple textarea for notes (Yjs integration planned for later).
- **Interactivity** – Alpine.js components for tabs, modals, form handling, and UI state.
- **Micro‑animations** – CSS transitions for hover, fade‑in, pin drop.
- **Responsive Design** – Mobile‑first grid, collapsible sidebars.

### Development Environment
- **Laravel Sail** for local Docker development (`./vendor/bin/sail up -d`).

### Production Deployment (plain PHP/Apache)
- Provide a **deploy.sh** script that:
  1. Pulls latest code on the VPS.
  2. Installs Composer dependencies (`composer install --optimize-autoloader --no-dev`).
  3. Runs migrations (`php artisan migrate --force`).
  4. Clears caches (`php artisan config:cache`, `php artisan route:cache`).
- Server must have PHP 8.3+, Composer, MySQL.

## Verification Plan
### Automated Tests
- **PHPUnit** unit/feature tests for:
  * Campaign creation, role assignment, invitation flow.
  * Wiki CRUD, markdown storage.
  * Export endpoint correctness.
- **Playwright** (or Dusk) UI tests covering:
  * Dashboard navigation, wiki editor live preview, map pin linking.

### Manual QA
- Visual QA of dark‑city theme (contrast ≥ 4.5:1, accessible focus states).
- Performance audit (page load < 2 s on modest VPS).
- Accessibility audit using the **a11y‑debugging** skill.
