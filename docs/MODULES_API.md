# Modules & API Reference

> Every `modules/*.php` endpoint, the `sendData()` contract, and the
> `pages.php` sub-page router.

## 1. `sendData()` Contract (client → server)

Defined in `js/main.js` and mirrored in `js/images.js`:

```js
sendData(page, type, id, atype, subject, message)
```

- `page`   → file `modules/<page>.php`
- `type`   → `get` (query) or `post` (serialize the active form)
- `id`     → target player id (attacker/spy/support target) — `0` when n/a
- `atype`  → action slug handled by the module
- `subject` / `message` → used by `sendmessage`/`c_ally` forms

Request URL shape: `modules/<page>.php?id=<id>&atype=<atype>&time=<ts>&<extra>`,
with `time` mandatory for the login guard. **Every** module entry must:

```php
include_once("../config.php");
if (!$s->loggedIn || !$_GET['time']) { print('Invalid request'); die(); }
$u = new Game();
```

## 2. Request Parameters (`pages.php` router)

| Param | Meaning | Example |
|-------|---------|---------|
| `id` | main suite slug | `id=empire`, `id=universe` |
| `atype` | sub-page slug within the suite | `atype=worldboss` |
| `p` | pagination offset | `p=2` |
| `pp` | per-page count | `pp=25` |
| `cmd` | action command inside a sub-page | `cmd=queue`, `cmd=settle` |
| `tcclass`, `tclegion` | troop catalog filters | `tcclass=Elite`, `tclegion=Asgard` |
| `tp`, `tpid`, `tqty` | training queue params | `tqty=1000` |

Unknown `id` falls back to `empire`; unknown `atype` falls back to the suite's
default (`$subDefaults`).

## 3. Main Suites (10) and Sub-Pages (71)

From `modules/pages.php` `$mainTitles` / `$subLabels`:

| Suite | Title | Sub-pages |
|-------|-------|-----------|
| `empire` | Empire Command | home, overview, planets, command, progress, logistics, doctrine |
| `military` | Military Directorate | personnel, troops, armory, training, fleet, navy, defensegrid |
| `operations` | Operations Center | attack, raid, spy, logs, commandqueue, diplomacyops, rts |
| `economy` | Economic Network | banking, market, technology, production, resources, buildings, logistics, treasury, store, battlepass, seasonpass |
| `diplomacy` | Diplomacy Office | alliance, relations, messages, commander, governance, treaties, councils |
| `intel` | Intelligence Bureau | rankings, reports, threats, map, signals, dossiers |
| `community` | Community & Updates | forums, updates, contact, faq, events, academy |
| `help` | Guides & Help Desk | newplayer, mechanics, glossary, support, troubleshooting, hotkeys |
| `universe` | Universe Observatory | galaxies, planets, objects, expedition, bases, travel, lanes, anomalies, seeds, events, worldboss, story |
| `research` | Research Directorate | tree, techlib, infrastructure, classes, talents, stargate, projects, labs, blueprints |

## 4. Module Inventory (`modules/*.php`)

### Gameplay endpoints

| Module | Action slugs / purpose |
|--------|------------------------|
| `action.php` | Generic action dispatch (attacks, spy, sabotage) |
| `actionLogs.php` | View `actionlog` reports (defense tabs) |
| `armory.php` | Buy/sell attack & defense weapons; sell-link handler fixed at line 101 |
| `armoryold.php` | Legacy armory view (superseded by `armory.php`) |
| `bank.php` | Naquadah deposit/withdraw (`Game::bank()`) |
| `base.php` | Empire base/overview + news feed |
| `c_ally.php` | Alliance management (create/join/roster) |
| `commandergov.php` | Commander governance systems + options (18 systems) |
| `fleetdock.php` | Shipyard, fleet, missions (shipyard, fleet, fleet_missions tables) |
| `hyperspace.php` | Jumpgate/hyperspace transit UI + routes |
| `market.php` | Resource listings (`market_listings`) |
| `megaforge.php` | Mega Forge assets, starships, units, buildings |
| `messages.php` | Inbox/sent/compose |
| `ogamebuildings.php` | OGame building upgrades + `ogame_building_levels` |
| `personnel.php` | Unit roster overview |
| `progress.php` / `progressinfo.php` | Progression dashboard |
| `rank.php` | Rank/ladder view |
| `recruit.php` | Recruit untrained units with cash |
| `resourcehq.php` | Resource HQ (strategic resources) |
| `sendmessage.php` | Compose/forward messages |
| `spy.php` | Covert action + results |
| `stations.php` | Station command + `space_installations` |
| `stargatetech.php` | Empire tech + `stargate_tech_levels` |
| `techlib.php` | Technology tree + `research_infrastructure` |
| `technology.php` | Technology view/buy |
| `terminal.php` | Command terminal / logs |
| `train.php` | Train units |
| `2train.php` | Multi-train view |
| `untrain.php` | Untrain units |
| `user.php` | Player profile page |
| `unitcatalog.php` | Unit catalog listing |
| `logs.php` | Operational logs |
| `faq.php` | FAQ content |
| `ally_mlist.php` | Alliance member list |

### Router / helpers

| Module | Purpose |
|--------|---------|
| `pages.php` | The 410 KB sub-page router + catalog/state systems (see §3) |
| `formal_logic.php` | Formal logic helpers (conditions, validations) used by other modules |
| `entity_name_helpers.php` | Entity/name normalization helpers |

## 5. `mainUpdate()` (public pages)

```js
mainUpdate(page, title)   // loads indexpages/<page>.php into #mainDisplay
```

- `indexpages/login.php`, `indexpages/register.php`, `indexpages/updates.php`
- `index.php` also supports `GET ?page=` handling for direct links.

## 6. Add a New Module (quick reference)

1. Create `modules/<name>.php` with the standard entry guard (§1).
2. Route on `$_GET['atype']` (and `$_POST` when `type == 'post'`).
3. Output an HTML fragment (no full `<html>` doc) or plain text for actions.
4. Wire the trigger: menu link or `<input onclick="sendData('<name>','get',<id>,'<atype>')">`.
5. Add CSS to `main.css` only if the fragment needs new classes (theme-scoped if needed).
6. Run `php -l modules/<name>.php` and the test suite.

## 7. Handler Reference (per-module `atype` routing)

Exact dispatch for the modules with explicit `atype` routing (verified against
`rg "atype" modules/`):

| Module | `atype` values | Behavior |
|--------|----------------|----------|
| `action.php` | `attack`, `raid`, `spy` | Delegates to `attack_raid()` / `spy()` with the target `id` and `turns`; returns the resulting report or message |
| `armory.php` | `(default)` → view | weapon buy form |
| | `repair` | repair/restore weapon strength |
| | `sellweps` | sell selected weapons (fixed handler at line 101 uses `sellweps`) |
| `armoryold.php` | `repair` | legacy armory repair view |
| `bank.php` | `view`, `deposit`, `withdrawl` (from `$_GET['atype']`) | `Game::bank()` |
| `c_ally.php` | `Send` | alliance action (accept/send) vs. default view |
| `commandergov.php` | lowercase slug | governance systems + options views |
| `fleetdock.php` | default `overview` + slugs | shipyard/fleet/mission views |
| `hyperspace.php` | slugs + numeric route id (`(int)$_GET['atype']`) | jumpgate/hyperspace views & routes |
| `logs.php` | `attack`, `raid`, `spy`, `sab` (default `attack`) | `Game::actionLog($atype)` |
| `market.php` | `` / `post` / `buy` / `cancel` | listing create, buy, cancel |
| `messages.php` | `inbox`, `sent`, `compose`, `reply`, `read`, `delete`, `deleteAll`, `send` (POST) | inbox CRUD |
| `megaforge.php` | slug | Mega Forge views |
| `ogamebuildings.php` | lowercase slug | OGame building upgrade views |
| `pages.php` | suite `id` + `atype` slug | sub-page router (see §3) |
| `resourcehq.php` | slug | Resource HQ views |
| `spy.php` | `spy` (form value) | spy form post |
| `stargatetech.php` | lowercase slug | empire-tech views |
| `stations.php` | lowercase slug | station command views |
| `techlib.php` | lowercase slug | technology-tree / infrastructure views |
| `technology.php` | from `$_GET['atype']` | tech view/buy |
| `terminal.php` | default `all` | terminal log filter |
| `unitcatalog.php` | `military`, `civilian`, `government` (default `military`) | catalog category filter |
| `user.php` | `set_commander`, `clear_commander`, `support` | commander/support actions on a profile |
| `train.php` / `2train.php` | (form-driven) | `trainUnits()` |
| `untrain.php` | (form-driven) | `untrainUnits()` |
| `actionLogs.php` | `atk` (+ `id` = actID) | `Game::getActID()` report detail |

### 7.1 Handler entry shape (canonical example)

```php
include_once("../config.php");
if (!$s->loggedIn || !$_GET['time']) { print('Invalid request'); die(); }
$u = new Game();
$atype = $_GET['atype'] ?? '';
switch ($atype) {
    case 'sellweps':
        // id = qty, wid = weapon id (POST field)
        $u->sellWeapons((int)($_POST['wid'] ?? 0), (int)($_GET['id'] ?? 0));
        break;
    default:
        // render armory catalog + inventory fragment
}
```
