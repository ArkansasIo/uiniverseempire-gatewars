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
| `.stat-pills` | Individual top-bar stat chips |
| `.side-nav` | Left navigation suites (details/summary accordion) |
| `.page-hub` | Newer content layouts (grid cards for rich sub-pages) |
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

## 6. Theme & Copy Tests

- `tests/theme_support_test.php` — theme normalization + CSS class mapping.
- `tests/theme_and_copy_test.php` — required copy tokens and icon presence.
- `tests/formel_logic_test.php` — module handler paren/logic sanity.

Run with:

```bash
php tests/theme_support_test.php
php tests/theme_and_copy_test.php
```
