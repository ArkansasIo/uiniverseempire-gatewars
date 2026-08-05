# Architecture Overview

> **Universe Civilization: Empire at Wars** — architecture, runtime flow, and component map.

## 1. Technology Stack

> **Current release: Version 1.5.0**

| Layer | Technology | Notes |
|-------|-----------|-------|
| Language | PHP 8.x | Procedural entry scripts + one domain-object hierarchy |
| Database | MySQL (InnoDB) | Accessed through `mysqli` with prepared statements and raw queries |
| Frontend | Vanilla JavaScript + XHR | No framework; AJAX module loading |
| Markup | HTML tables + modern CSS grid shell | Mixed legacy/table layout with new `.page-hub` / `.public-*` layouts |
| Styling | `main.css` (single stylesheet) | 4 theme variants selected by body class + localStorage |
| Deployment | Hostinger / LAMP, PHP built-in server for dev | See `docs/HOSTINGER_DEPLOYMENT.md` |

There is **no framework and no composer/autoloader**. Every script includes `config.php`,
which defines paths, loads the base classes, and starts the session.

## 2. Runtime Layers

```
+-----------------------------------------------------------------------+
| BROWSER                                                               |
|  main.js / auto.js / train.js / search.js / images.js / bbfix.js      |
|  main.css, index.tpl (shell), pages.php (router content)              |
+------------------------------+----------------------------------------+
                               | HTTP GET/POST (XHR + page loads)
+------------------------------v----------------------------------------+
| ENTRY POINTS (root)                                                   |
|  index.php          public landing + login/register + logged-in shell |
|  stats.php          autoLoad() JSON payload (top bar, 15s poll)       |
|  userlist.php       player search autocomplete                        |
|  image.php          registration captcha image                        |
|  count.php / contact.php / passwd.php / process.php  legacy stubs     |
+------------------------------+----------------------------------------+
                               | include config.php
+------------------------------v----------------------------------------+
| ROUTING LAYER                                                         |
|  sendData('MODULE','type','id','atype') -> modules/MODULE.php         |
|  mainUpdate('PAGE')          -> indexpages/PAGE.php (public forms)    |
|  pages router                -> modules/pages.php?id=<main>&atype=<sub>|
+------------------------------+----------------------------------------+
                               |
+------------------------------v----------------------------------------+
| DOMAIN LAYER  (base/)                                                 |
|  Chive      DB connection, query(), clean_sql(), page_gen timing      |
|    +-> User  auth, sessions, registration, isRealUser(), salt()       |
|          +-> Game  gameplay (turns, combat, tech, spy, bank, market)  |
|  ThemeSupport  theme normalization + brand helpers                    |
|  Debug        conditional debug printing                              |
|  SafeDb*      no-DB fallback stubs (graceful degradation)             |
+------------------------------+----------------------------------------+
                               |
+------------------------------v----------------------------------------+
| DATA LAYER   MySQL: game.sql core + runtime-created tables            |
|              scripts/backend/game_tick.php (cron economy tick)        |
|              scripts/backend/*.sh|ps1 (db init/migrate/backup)        |
+-----------------------------------------------------------------------+
```

## 3. Class Hierarchy

```
Chive (base/Chive.class.php)
  |  props: db_link, db_prefix, db_server/name/user/pass, queryCount
  |  methods: __construct, connectToDB(), connected(), clean_sql(), query()
  |  sibling: page_gen (page generation timer)
  +--> User (base/User.class.php)
        props: userName, password, access, loggedIn, userid, raceID, progress
        methods: __construct, isRealUser(), isAllowed(), logOut(), salt(),
                 addUser(), genUniqueLink()
        +--> Game (base/Game.class.php)
              props: gameTime, isRank, actionTurns, inHand, inBank, nextTurn,
                     numMessages, uid, rid, fields
              methods: nextTurn(), getRaces(), autoLoad(), messageCount(),
                       baseVars(), getRanks(), getPersonnel(), getOfficers(),
                       Rankings(), allyRankings(), getallyinfo(), getUserInfo(),
                       getUserPlanets(), getActionTurnsByUid(), setCommander(),
                       clearCommander(), sendSupport(), getWeapons(),
                       getWeaponInventory(), updatePower(), buyWeapons(),
                       trainUnits(), untrainUnits(), attack_raid(), percs(),
                       getActID(), actionLog(), turnUpdate(), delOld(),
                       viewTech(), fieldtocrypt(), crypttofield(), buytech(),
                       level(), sendMessage(), create_allliance(),
                       viewMessages(), deleteMessage(), bank(), spy(), sabotage()

ThemeSupport (base/Theme.class.php)  static helpers only
Debug (base/Debug.class.php)          static debug printer
SafeDbConnection / SafeDbStatement / SafeDbResult  (fallback stubs)
```

See `docs/UML_DIAGRAMS.md` for class and sequence diagrams.

For per-function signatures, parameters, return types, and step-by-step logic of
every `Chive`/`User`/`Game` method, the cron processor, and the client JS modules,
see `docs/FUNCTION_REFERENCE.md`.

## 4. Request Flow

### 4.1 Public landing page (`index.php`)

1. `index.php` instantiates `new Game()`.
2. If a `POST` login form was submitted, it constructs `new User($_POST['user'], $_POST['pass'])`.
3. If `!$s->loggedIn`, renders the public landing page:
   - Header with brand, GitHub source button, **Pilot Login** / **Create Account** buttons.
   - Hero gallery (`images/galaxy*.jpg`) and content grid.
   - `#mainDisplay` area that the browser fills by calling
     `mainUpdate('login','Login')` → `indexpages/login.php`.
4. If logged in, calls `showPage()`, which renders
   `templates/header.tpl + templates/index.tpl + templates/footer.tpl`.

### 4.2 In-game shell (`templates/index.tpl`)

- On `<body onload=...>` the shell issues `sendData('pages','get','empire','overview')`
  to populate `#mainDisplay` with the empire overview sub-page.
- `autoLoad()` (`js/auto.js`) polls `stats.php` every **15 seconds** to refresh the top bar
  (turns, bank, rank, messages, server time, and the seven strategic resources).
- The left menu uses nested `<details>/<summary>` blocks; each link calls
  `sendData(...)` which loads a `modules/*.php` result into `#mainDisplay`.

### 4.3 AJAX module routing

```js
function sendData(page, type, id, atype, subject, message) {
    // url = "modules/" + page + ".php?id=" + id + "&atype=" + atype + "&time=..."
    // POST body serializes document.forms[1] when type == "post"
}
```

- `modules/<page>.php` must `include_once("../config.php")`, guard with
  `if (!$s->loggedIn || !$_GET['time'])` and then output **HTML fragment** or action text.
- The fragment is injected into `#mainDisplay` by `stylizeDiv()` in `js/main.js`,
  which also re-executes any inline `<script>` blocks.

### 4.4 `pages.php` sub-page router

`modules/pages.php?id=<main>&atype=<sub>` is a 410 KB hub that dispatches on two slugs:

- `$main` — one of: `empire, military, operations, economy, diplomacy, intel,
  community, help, universe, research`.
- `$sub` — a sub-page key defined in `$subLabels[$main]` (e.g. `empire/home`,
  `universe/worldboss`, `research/blueprints`).

Unknown `$main` falls back to `empire`; unknown `$sub` falls back to the main's
default (`$subDefaults`). The hub also owns the large catalog/state systems:
unit catalog, story campaign, world boss, expeditions, star systems, blueprints,
battle pass, treasury, governance, OGame buildings, and more.

## 5. Component Map

| Component | Files | Responsibility |
|-----------|-------|----------------|
| Config / bootstrap | `config.php`, `config.local.php`, `db_config.php` | env-driven settings, class loading |
| Base classes | `base/Chive.class.php`, `User.class.php`, `Game.class.php`, `Theme.class.php`, `Debug.class.php`, `functions.php` | DB, auth, gameplay, theme helpers |
| Module pages | `modules/*.php` (~40 files) | AJAX-rendered gameplay screens |
| Sub-page hub | `modules/pages.php` | large router for rich sub-pages |
| Shell templates | `templates/header.tpl`, `index.tpl`, `footer.tpl`, `accCheck.tpl`, `debug.tpl` | page shell + in-game layout |
| Public pages | `indexpages/login.php`, `register.php`, `updates.php` | login/registration forms |
| Client JS | `js/main.js`, `auto.js`, `train.js`, `search.js`, `images.js`, `bbfix.js` | XHR routing, polling, autocomplete |
| Styles | `main.css` (3,000+ lines) | themes, shell, auth, page hubs |
| Cron | `scripts/backend/game_tick.php`, `30min.php`, `1milquery.php` | economy tick, turn/rank update |
| Ops scripts | `scripts/backend/*.sh|ps1`, `tools/backend/*` | db init/migrate/backup/health/export |
| SQL | `game.sql`, `database/sql/*` | schema + seeds + views |
| Tests | `tests/*.php` | CLI regression checks |
| Docs | `docs/*`, `README.md`, `BACKEND_SETUP.md` | this documentation set |

## 6. Key Design Decisions

1. **Graceful degradation / no-DB fallback.** `Chive::connectToDB()` swaps in
   `SafeDbConnection` stubs when `mysqli` is missing or the DB is unreachable.
   `User` and `Game` then fall back to a local demo login
   (`copilotpilot` / `SGWLogin123!`) and static payloads so the shell and theme
   can be developed without a database.
2. **Schema is self-healing.** The constructors and `pages.php` issue
   `CREATE TABLE IF NOT EXISTS` + `ADD COLUMN IF NOT EXISTS` on every request,
   so new features can be introduced without a formal migration step.
3. **Turn economy vs. resource economy are separate timers.** The legacy
   `turnUpdate()` (30-minute cadence, `30min.php`) pays Naquadah, action turns,
   and untrained units. The newer `game_tick.php` (5-minute cron cadence)
   advances the strategic resource economy (`player_resources`) in 30-minute ticks.
4. **AJAX fragments, not SPA.** Every gameplay screen is a server-rendered HTML
   fragment swapped into `#mainDisplay`. The shell stays constant while the
   content region changes.
5. **Theme is CSS + body class.** `theme-white`, `theme-og`, `theme-blue`,
   `theme-stargate`; persisted in `localStorage['sgwTheme']` and applied by
   `setTheme()` on `js/main.js`.
