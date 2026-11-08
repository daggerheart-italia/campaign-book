# Changelog

## [0.1.18] - 2026-11-08

- style(ui): improve mobile ergonomics for campaign maps (5d9377e)
- chore(changelog): update entries for version 0.1.17 (9cfd0ff)

## [0.1.17] - 2026-11-07

- style(ui): show note save label on mobile and keep wiki header row (5289dec)
- fix(a11y): remove automatic mobile tooltips from icon controls (5ad6e22)
- feat(maps): add reusable map selector component and GM management menu (5edbb3d)
- style(ui): update icons and layout alignment across views (326aa7e)
- refactor(campaigns): convert pin modal to native dialog element (44dd97c)
- feat(campaigns): show icon labels on mobile in edit view actions (ef3a56c)
- style(campaigns): adjust notes form footer layout and shorten pin label (93febfe)
- refactor(ui): improve map selector layout and icon-label visibility (c33f4b8)
- feat(mobile): show icon labels on mobile and simplify nav hamburger (2627379)
- feat(ui): add native tooltip to desktop-hidden icon labels (2d3524b)
- feat(icon-label): add mobile and desktop visibility toggles (92a314d)
- refactor(ui): improve pin roster responsiveness and accessibility (d5374c3)
- feat(mobile): enhance touch targets and responsive UI for premium mobile UX (ddcc338)
- feat(map): unify pin modal sub-states and wire parent page select component (a8470e1)
- refactor(ui): track create and details modal state separately in map view (a4d5487)
- Refactor code structure for improved readability and maintainability (968f10c)
- feat(icons): replace inline SVGs with Lucide Blade icon components (7166249)
- feat(docs): add icon guidelines and usage standards (9ae9ed3)
- feat(mobile): add incremental todo list for mobile polish and unified pin modal (8b86f61)
- refactor(ui): unify map pin modal state and improve responsive layouts (693f6a4)
- feat(mobile): implement mobile-first visual refinements and unified pin modal (430179e)
- chore(changelog): add entries for version 0.1.16 (db71f8c)

## [0.1.16] - 2026-11-06

- feat(config): make route throttle limits configurable (7fc20b0)
- fix(ui): correct keydown handler name and restore query on close (5b0823f)
- chore(changelog): add entries for version 0.1.15 (96c121f)

## [0.1.15] - 2026-11-04

- refactor(campaign): improve markdown excerpt stripping (4332849)
- chore(changelog): update entries for version 0.1.14 (e21d455)

## [0.1.14] - 2026-11-04

- feat(markdown-editor): add image insertion from public URLs (bd16253)
- chore(changelog): add entries for version 0.1.13 (ca95a2d)

## [0.1.13] - 2026-11-04

- fix(maps): center map and constrain width in print view (108d0da)
- chore(changelog): update entries for version 0.1.12 (48e2e0a)

## [0.1.12] - 2026-11-04

- test(browser): add smoke test covering main authenticated routes (0cae9f0)
- feat(auth): add password reset and recovery flow (7643cd1)
- refactor(campaigns): optimize sync tracking, excerpts, and map defaults (560287b)
- chore(changelog): update entries for version 0.1.11 (b96c003)

## [0.1.11] - 2026-11-04

- feat(maps): add numbered pin markers and legend indices (833816e)
- chore(changelog): add entries for version 0.1.10 (f426a79)

## [0.1.10] - 2026-11-04

- style(maps): refine print layout, legend columns, and page breaks (b2f9abf)
- chore(changelog): add entries for version 0.1.9 (660cfce)

## [0.1.9] - 2026-11-04

- feat(maps): add print-friendly view with pin legend (2317c95)
- chore(changelog): add entries for version 0.1.8 (1d279e4)

## [0.1.8] - 2026-11-03

- feat(wiki): format updated timestamps with relative time labels (44a9d28)
- chore(changelog): update entries for version 0.1.7 (0f11862)

## [0.1.7] - 2026-11-03

- fix(campaigns): redirect unauthenticated join attempts to login instead of registration (ca49d7e)
- feat(auth): allow unauthenticated users to follow campaign invite links (193b2d9)
- fix(wiki): guard edit lock initialization and clean up timers on destroy (8389172)
- chore(changelog): update entries for version 0.1.6 (2c9d831)

## [0.1.6] - 2026-11-03

- feat(sync): add campaign map synchronization support (9bdf860)
- feat(ui): add searchable parent page select component (f15b25b)
- feat(wiki): add noscript fallback and guard children_ids access (105574e)
- refactor(wiki): derive page hierarchy client-side from parent_id (35ba5b2)
- test(browser): update wiki sync test selectors and button labels (64463a3)
- fix: wiki notes sync and loading (8c5e602)
- refactor(sync): unify campaign sync endpoints and introduce shared sync manager (6883050)
- test(browser): add Playwright browser tests and supporting infrastructure (e008c47)
- build(deps): add Pest testing framework and Playwright dependency (8ca00fc)
- fix(wiki): allow revision restore with update permission and fix sync closure (1669526)
- feat(sync): add wiki page sync support to campaign sync system (3feaf41)
- feat(editor): add local autosave for notes and wiki editor (39a6335)
- chore(changelog): update entries for version 0.1.5 (a2456e4)

## [0.1.5] - 2026-11-01

- feat(campaigns): hide member emails from non-game-masters (e1e2b18)
- fix(wiki): restrict revision history to game masters (99d03f3)
- feat(sharing): suggest alternative public slugs on validation failure (6745f33)
- chore(changelog): update entries for version 0.1.4 (1aedbef)

## [0.1.4] - 2026-11-01

- feat(notes): add client-side search filtering to session notes (b522d66)
- refactor(public): convert campaign page to tabbed wiki, map, and notes views (5e40568)
- feat(sharing): add public read-only campaign sharing via slug (21cdf21)
- chore(changelog): update entries for version 0.1.3 (dc39c59)

## [0.1.3] - 2026-11-01

- fix(campaigns): hide invite links and codes from non-GM players (6f31ed6)
- chore(changelog): add entries for version 0.1.2 (96c6d65)

## [0.1.2] - 2026-10-30

- feat(pwa): add PWA support with manifest, service worker, and icons (3cbf045)
- style(ui): remove w-max constraint from campaign header quick actions container (d2ebd80)
- chore(changelog): add changelog entries for version 0.1.1 (43d59b7)

## [0.1.1] - 2026-10-29

- refactor(ui): reorganize campaign header actions and edit button placement (4ac4f6b)
- chore(changelog): update changelog for version 0.1.0 (c279373)

## [0.1.0] - 2026-10-29

- feat(sync): add versioned collaborative sync and wiki edit locks (6c17464)
- plan(wiki): implement atomic edit locks and live updates for collaborative editing (1673793)
- chore(changelog): update changelog for version 0.0.9 (2b9a703)

## [0.0.9] - 2026-10-28

- feat(maps): add pinch-to-zoom and ctrl+wheel zoom on campaign map (38afb02)
- perf(js): lazy-load markdown editor and split tiptap chunks (c023231)
- style(ui): improve typography and replace inline display styles with x-cloak (4ca63f3)
- feat(campaigns): add edit and update actions for game masters (fba2fdd)
- chore(changelog): update changelog for version 0.0.8 (7264198)

## [0.0.8] - 2026-10-28

- refactor(ui): replace inline wiki child expansion with modal navigation (d4e7a42)
- chore(changelog): update changelog for version 0.0.7 (92b0e0c)

## [0.0.7] - 2026-10-28

- feat(wiki): add parent and child page navigation to wiki detail view (f2eb6d5)
- feat(wiki): show flat filtered cards when search or category filter is active (956e58a)
- style(wiki): adjust stacked card border and background opacity (882264a)
- chore(changelog): add entries for version 0.0.6 (1fc7f9d)

## [0.0.6] - 2026-10-28

- feat(wiki): add parent-child page hierarchy with nested tree view (49aacb0)
- chore(changelog): update changelog for version 0.0.5 (163ed74)

## [0.0.5] - 2026-10-28

- refactor(nav): collapse create-campaign button to icon and add tooltips (dbe7ffa)
- style(css): adjust prose-dagger list indentation and markers (c9fb276)
- chore(changelog): update changelog for version 0.0.4 (256e0f3)

## [0.0.4] - 2026-10-28

- fix(maps): remove pins when deleting a campaign map (db84227)
- feat(wiki): add rich markdown editor with inter-page link resolution (42fd584)
- chore(changelog): add changelog entries for version 0.0.3 (ad78354)

## [0.0.3] - 2026-10-28

- feat(map): allow editing pin details and scope wiki page links to campaign (1815506)
- chore(changelog): update changelog for version 0.0.2 (c14f5eb)

## [0.0.2] - 2026-10-28

- feat(ui): display application version badge in navigation (9f705c8)

## [0.0.1] - 2026-10-28

- chore(deploy): relocate deploy script to scripts/ and harden deployment (75e8916)
- feat(map): add pin color selection to map pins (b2bfee1)
- chore(githooks): add git-flow hooks and helper modules (eab616e)
- feat(map): add lock state to prevent moving map pins (bfb114b)
- feat: add Daggerheart campaign book application (70fd422)


