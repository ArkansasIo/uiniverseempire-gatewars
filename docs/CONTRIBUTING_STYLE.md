# Contributor & Style Guide

> How to write and ship source for Universe Civilization: Empire at Wars.
> Follows the existing conventions in `docs/DEVELOPMENT_WORKFLOW.md`.

## 1. Golden Rules

1. **Keep gameplay changes isolated to the relevant module.** Infrastructure and
   gameplay changes never mix in one commit.
2. **The project is legacy-safe PHP.** Write code the way the codebase already
   writes code: procedural modules, `include`-based bootstrap, `mysql`-era
   idioms where the surrounding file uses them, modern bits only where a file
   already uses them (e.g. `mysqli` prepared statements in `game_tick.php`).
3. **Never break the AJAX fragment contract.** Modules return HTML fragments or
   plain text — never a full HTML document.
4. **Never commit credentials or secrets.** `config.local.php` and `config.php`
   hold placeholder DB creds; keep them placeholders.

## 2. Mandatory Entry Pattern (modules)

Every `modules/*.php` starts with the standard guard:

```php
include_once("../config.php");
if (!$s->loggedIn || !$_GET['time']) { print('Invalid request'); die(); }
$u = new Game();
```

Then route on `$_GET['atype']` (and `$_POST` fields when the caller used
`type='post'`). Echo a fragment; `stylizeDiv()` in `js/main.js` will inject it.

## 3. `sendData()` Argument Order

Never swap the positional args when calling from HTML/JS:

```js
sendData(page, type, id, atype, subject, message)
// e.g.
sendData('armory','get',<qty>.value,'sellweps',<wid>); return false;
```

`id` is the target player id, `atype` the action slug. See
`docs/MODULES_API.md` §1.

## 4. Database Access

- Use the `Chive::query()` helper or prepared statements with
  `SafeDbStatement`-style binding where available (`game_tick.php` uses real
  `mysqli` prepared statements).
- Escape anything that touches SQL with `clean_sql()` — never inline user input
  into a query string.
- **Idempotent DDL everywhere.** New tables/columns are shipped as
  `CREATE TABLE IF NOT EXISTS ...` / `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`
  in the owning module, *and* added to the `database/sql` bundle so clean and
  live installs converge (see `docs/DATABASE.md` §4.2).
- Prefer `player_resources` + 30-minute tick cadence for new economy features;
  do not create parallel turn timers.

## 5. HTML / CSS

- Fragments are table-based where the module is legacy, and `.page-hub` grid
  cards for new rich pages. Match the file you are editing.
- New CSS classes go into `main.css` under the shared structural rules first;
  add `body.theme-<name> .class` overrides only when a theme actually differs.
- Theme-safe naming: lowercase, kebab-case (`stat-pills`, `auth-card`,
  `world-boss-panel`). Never inline styles for layout.
- Icons: use existing `images/ui/*.svg`; do not add icon-font dependencies.

## 6. PHP Style

- No strict types, no namespaces, no composer — the project does not use them.
- `php -l` must pass before commit:
  ```bash
  find . -type f -name '*.php' -print0 | xargs -0 -n1 php -l
  ```
- Keep handlers parent-balanced; `tests/formal_logic_test.php` sanity-checks
  event-handler paren balance across modules.
- Do not add comments unless the surrounding file uses them for the same thing;
  the codebase is intentionally terse.

## 7. Constants & Balance

Balance constants live in `base/Game.class.php`
(`TURN_TICK_MINUTES`, `TURNS_PER_MINUTE`, `UNIT_PROD_BASE_LEVEL`, `PLAYER_RACES`).
When tuning, change the constant there first, then any duplicated literals in
`game_tick.php` and `pages.php` economy views.

## 8. Tests

CLI regression tests live in `tests/`; run the full set after any change:

```bash
php tests/entity_name_test.php
php tests/formal_logic_test.php
php tests/race_catalog_test.php
php tests/research_tree_test.php
php tests/runtime_db_fallback_test.php
php tests/theme_and_copy_test.php
php tests/theme_support_test.php
```

Backend readiness: `./scripts/backend/healthcheck.sh`.

> Local constraint: CLI PHP here has no `mysqli` and there is no MySQL server,
> so live DB ticks cannot run locally — use `--dry-run` and static/unit checks.

## 9. Commit & Push

Git is not on PATH on this machine — always use the full path:

```powershell
$git = 'C:\Program Files\Git\cmd\git.exe'
& $git add <target-files>
& $git commit -m "<clear summary>"
& $git push origin main        # remote: github.com/ArkansasIo/universe-civilization-enmpire-stargate.git
```

Flow: stage only intended files → inspect `git status`/`git diff` → commit with
a concise summary → push. Never amend a failed commit; make a new one. (The
existing docs reference `arkansas master` — that alias is stale; the configured
remote is `origin` on `main`.)

## 10. Documentation Rule

When adding new systems, add or update the nearest folder README and update
`docs/PROJECT_STRUCTURE.md` if responsibilities changed. Design docs live in
`docs/` (see `docs/README.md` index).
