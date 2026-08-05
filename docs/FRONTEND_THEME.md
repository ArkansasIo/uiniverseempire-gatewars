# Frontend & Theme System

> Page shell, theme variants, CSS layout system, and client-side JavaScript
> modules.

## 1. Page Shell

The in-game UI is a constant shell with a swap-in content region:

- `templates/header.tpl` — `<head>`, brand, top menu scaffolding, `bb_init()`,
  and the first `sendData('pages','get','empire','overview')`.
- `templates/index.tpl` — the main shell: top stat bar, left navigation suites,
  `#mainDisplay` content region, theme picker, `autoLoad()` bootstrap.
- `templates/footer.tpl` — closes the shell, prints `Debug::out()`, page timing.

The public site (`index.php`) uses `templates/index.tpl` (legacy) for the logged-out
landing, plus `indexpages/login.php` / `register.php` / `updates.php` forms that
are loaded into `#mainDisplay` via `mainUpdate()`.

## 2. Layout System (`main.css`)

| Class | Purpose |
|-------|---------|
| `.app-shell` | Fixed viewport shell: header, sidebar, main content |
| `.top-header` | Top stat bar (rank, turns, Naquadah, bank, resources, time) |
| `.stat-pill` | Individual top-bar stat chips |
| `.top-sub-header` | Secondary header row (search + quick links) |
| `.left-menu` | Left navigation suites (details/summary accordion) |
| `.menu-section-title` | Left-nav section header (pill in v1.5) |
| `.content-panel` | Main content column next to the left menu |
| `.page-hub-shell` | Page hub: `.page-hub-head`, `.page-hub-copy`, `.page-hub-badge` |
| `.page-subnav-title` / `.page-subnav` | Sub-page title + pill tab row (`.active` = current) |
| `.page-hub` | Newer content layouts (grid cards for rich sub-pages) |
| `.mini-table`, `.market-tbl`, `.msg-list` | Data grids (styled globally since v1.5) |
| `.main-display` | The `#mainDisplay` region that AJAX fragments fill |
| `.public-header` | Public landing header with brand + GitHub button |
| `.public-github` | GitHub "Game Source" button (top-left on public header) |
| `.auth-card` | Login/register card |
| `.auth-details` | Bulleted detail list under the login form |
| `.race-card` | Race selection cards on register |
| `.combat-log`, `.battle-report` | Battle report formatting |
| `.btn`, `.btn-primary` | Action buttons |
| `.modal` | Dialog overlays (used by spy/send message dialogs) |

The shell uses CSS grid (`grid-template-columns: 260px 1fr`) for the sidebar +
content split, with a responsive fallback below ~900px that stacks the nav above
content.

## 3. Theme System

### 3.1 Theme variants

| Theme | body class | palette |
|-------|-----------|---------|
| White | `theme-white` | light, clean (default) |
| OG | `theme-og` | original dark blue/gold SGW palette |
| Blue | `theme-blue` | deep-space blue |
| Stargate | `theme-stargate` | stargate ring blues / teal |

### 3.2 How theming works

1. `ThemeSupport` (base/Theme.class.php) normalizes the requested theme and
   exposes the body class + brand helpers.
2. The body element carries the class, e.g. `<body class="theme-stargate">`.
3. CSS uses `body.theme-white .top-header { ... }` selectors to swap variables
   and gradients per theme; shared structural rules live on the unprefixed classes.
4. The theme picker (a `<select>` in the shell) calls `setTheme(name)` in
   `js/main.js`, which persists to `localStorage['sgwTheme']` and re-applies the
   body class (no page reload).
5. On load, the shell reads `localStorage['sgwTheme']` first, then server-side
   default.

### 3.3 Adding a theme

1. Add a `theme-<name>` body class in `ThemeSupport::normalizeTheme()`.
2. Add a `body.theme-<name>` variable block + any overrides in `main.css`.
3. Add the option to the theme `<select>`.
4. Re-run `tests/theme_support_test.php`.

### 3.4 v1.5 UI polish layer

Since v1.5.0 a modern component layer is appended at the **end** of `main.css`
(behind the `/* v1.5 UI polish */` banner) so it consistently wins over legacy
and per-theme rules. It is CSS-only — no markup or JS changes.

- **Accent variables.** Each `body.theme-*` block defines `--accent`,
  `--accent-strong`, `--accent-soft`, `--accent-faint`. New rules reference
  these instead of hard-coded colors, so a component restyle stays theme-aware.
- **Typography.** `.app-shell`/`.public-shell` use `Segoe UI` + system sans
  (cursive legacy font removed); `.terminal-shell` keeps monospace; heading
  underlines removed; `:focus-visible` accent outlines added.
- **Buttons/forms.** Unified radius/padding/transitions for `combat-btn`,
  `calc-btn`, `tech-action`, `public-btn`, `auth-submit`, `gov-option`,
  `gov-preset`, `theme-option`, `gov-system-actions a`, and `page-subnav a`.
  Form controls get solid borders, rounded corners, and an accent focus ring;
  `input[type=button]`/`input[type=submit]` render as accent-solid buttons.
- **Page/sub-page headers.** `.page-hub-head` and `.content-header` get an
  accent top border + radius; `.page-hub-badge` is a pill; `.page-subnav-title`
  gets an accent dot marker; sub-nav links become pill tabs with an
  `.active` = accent-strong state.
- **Tables.** `table.mini-table`, `table.market-tbl`, `table.msg-list` are
  collapsed with uppercase accent-tinted headers, zebra striping, and hover;
  `#mainDisplay table` (legacy plain tables) gets light padding-only polish.
- **Left navigation.** `.left-menu` is sticky/rounded with `summary` chevron
  markers and hover slide; `.menu-section-title` renders as an accent pill.
- **Cards/spacing.** Cards get 8px radius; `.content-panel` breathing room.

Keep new UI CSS inside this appended layer (or later) and always reference the
accent variables so all four themes stay consistent.

## 4. Icons

All icons are inline SVG files under `images/ui/` (14 files, e.g. logo mark,
race emblems, resource icons, nav icons). Referenced via `<img src="images/ui/*.svg">`.
Use existing SVG icons rather than adding icon-font dependencies.

## 5. Client JavaScript Modules

### `js/main.js` — router + shell behaviors
- `sendData(page, type, id, atype, subject, message)`: XHR to
  `modules/<page>.php`, POSTs serialized form state when `type=='post'`.
- `mainUpdate(page, title)`: loads `indexpages/<page>.php` into `#mainDisplay`.
- `stylizeDiv()`: injects returned fragment and re-evaluates inline scripts.
- `setTheme(name)`, menu accordion behavior, dialog helpers.

### `js/auto.js` — top-bar stats
- `autoLoad()`: GET `stats.php`, parses the 14-slot payload
  `[inHand, inBank, isRank, turns, time, messages, next, metal, crystal,
  deuterium, food, water, population, energy]` and updates the stat pills.
- Polls every **15 seconds**.

### `js/train.js` — training forms
- `trainUnits()`/`untrainUnits()`: posts troop-type conversion forms via
  `sendData('military', ...)`.

### `js/search.js` — player lookup
- `userLookup(term)`: autocomplete against `userlist.php` for attack/spy/msg
  target fields.

### `js/images.js` — legacy swap
- `MM_swapImgRestore()`, `MM_preloadImages()`, `MM_swapImage()` legacy helpers.

### `js/bbfix.js` — forum/bbcode
- `bb_init()`: enables BBCode toolbar on message forms.

### `count.php` / `bb_fix` iframe
- `count.php` is an iframe "onload counter" used by the BBCode fix page; keep it
  side-effect-free beyond incrementing a visit counter.

## 6. OGame-Style Research / Tech System

Since v1.6 the `research` page (`modules/pages.php`) has an OGame-style research
tree with persistent per-player levels and real upgrade costs:

- **Two branches** — `research` and `technology` — each with 20 programs across
  10 domains (Quantum, Void, Psionic, Nano, Graviton, Xeno, Bioforge, Temporal,
  Stellar, Aegis). Programs have tiers 1–6 and a max level of 25.
- **Persistent levels** live in the `player_tech_levels` table
  (`uid`, `tech_key`, `level`); `ogameTechEnsureTables()` creates it on demand
  and seeds zero-level rows.
- **Costs** escalate with `formalCostValue(base, level, scale, 0.12)` for the
  five resources (Naquadah from `bank.onHand`; Metal/Crystal/Deuterium/Energy
  from `player_resources`) plus `formalTimeValue(base_turns, level, 1.08)`
  research turns. An infrastructure cost reduction (`data_vault`,
  `quantum_archive`, `ai_directorate`) discounts the resource cost up to 45%.
- **Prerequisites** gate programs; each `prereq` entry is a
  `{key, level, name}` tuple and unmet gates show the shortfall in the UI.
- **Upgrade action** is routed via `cmd=ogame_research&key=<key>` embedded in
  the sub-page link; `ogameResearchAction()` validates level cap, prereqs and
  resources, then levels up with an atomic upsert.
- **Pure logic** (catalog, cost, prereq, tree grouping) lives in
  `modules/ogame_research_logic.php` — no DB or session — so it is directly
  unit-testable (`tests/ogame_research_test.php`). The DB/render wrappers stay
  in `modules/pages.php`.

### Views (`research/<sub>`)

| Sub | Content |
|-----|---------|
| `tree` | Research branch tree board (`.wows-tree compact`), stat cards, Research Reserves |
| `techlib` | Technology branch tree board, Technology Reserves card |
| `talents` | Full catalog table (Branch/Domain/Program/Focus/Tier/Level/Effect/Next Cost/Prereq) with Research buttons |

The `.wows-tree`, `.wows-node*`, `.wows-domain` board CSS (main.css) drives the
node state visuals: unlocked (researched), available (prereqs met), locked.

## 7. Theme & Copy Tests

- `tests/theme_support_test.php` — theme normalization + CSS class mapping.
- `tests/theme_and_copy_test.php` — required copy tokens and icon presence.
- `tests/formel_logic_test.php` — module handler paren/logic sanity.
- `tests/ogame_research_test.php` — OGame catalog integrity, cost escalation,
  discount clamping, prereq gating, tree grouping.

Run with:

```bash
php tests/theme_support_test.php
php tests/theme_and_copy_test.php
```
