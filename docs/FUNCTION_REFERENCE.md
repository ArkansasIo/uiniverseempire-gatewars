# Function Reference (Deep Dive)

> Exhaustive signature + logic reference for the domain classes (`Chive`,
> `User`, `Game`), the client JS modules, and the cron processor. Line numbers
> are from the current `base/*.class.php` files.

---

## 1. `Chive` — base/base/Chive.class.php

Root class: DB connection, query helper, and graceful-degradation stubs.

### 1.1 Properties

| Property | Type | Purpose |
|----------|------|---------|
| `name` | `?string` | Class/instance name |
| `db_prefix` | `?string` | Table prefix from `$conf['db_prefix']` |
| `db_server` / `db_name` / `db_username` / `db_password` | `?string` | Connection config from `$conf` |
| `db_link` | `mixed` | real `mysqli` OR `SafeDbConnection` stub |
| `queryCount` | `int` | Total attempted queries |

### 1.2 `__construct(string $name = "")`
Copies `$conf['db_server'|'db_name'|'db_username'|'db_password'|'db_prefix']` into
properties. Reads the global `$conf` (set by `config.php`). Does **not** connect.

### 1.3 `connectToDB(): void` (line 195)
1. If `class_exists('mysqli')` is false → set `$db_link = new SafeDbConnection(...)`, return.
2. `mysqli_report(MYSQLI_REPORT_OFF)`.
3. `@new mysqli(server, user, pass, db)`; if server is `localhost` and connect fails, retry over TCP `127.0.0.1`.
4. On success (`!connect_error`) → keep real connection.
5. On failure → replace `$db_link` with `SafeDbConnection(error)`.

**Sub-logic:** the localhost→127.0.0.1 retry exists for container/dev environments where socket resolution fails. All DB code must go through `connected()` to detect the stub.

### 1.4 `connected(): bool` (line 236)
- `null` → `false`.
- `instanceof SafeDbConnection` → `false`.
- else → `!$this->db_link->connect_error`.

### 1.5 `clean_sql(string $string, int $quotes = 1): string` (line 256)
- Reconnects if not connected.
- If still not connected → `addslashes` fallback (quoted when `$quotes`).
- If `$string` is numeric → returned **unquoted** (trusted).
- Otherwise → `'` + `real_escape_string()` + `'`.

**Sub-logic:** numeric strings skip quoting so integer columns work in raw SQL;
everything else gets escaped+quoted.

### 1.6 `query(string $query)` (line 279)
- Reconnects if not connected.
- No connection → increments `queryCount`, returns `false`.
- Wraps `$this->db_link->query($query)` in try/catch (`Throwable`).
- Logs success/error via `Debug::printMsg`, increments `queryCount`, returns result or `false`.

### 1.7 Safe-db stubs (lines 3–118)
Compatibility layer used **only** when `mysqli` is missing/unreachable:

| Class | Behaviors |
|-------|-----------|
| `SafeDbResult` | `num_rows=0`; `fetch_object/assoc/array/row` → `null`; `free()` no-op |
| `SafeDbStatement` | `bind_param` → `true`; `execute()` → `false`; `get_result()` → empty `SafeDbResult`; unknown method calls → `false` |
| `SafeDbConnection` | `prepare()` → stub statement; `query()` → `false`; `real_escape_string()` → `addslashes`; `begin_transaction/commit/rollback` → `false`; stores the connection error message |

**Why this matters:** `autoLoad()`, `baseVars()`, `getRaces()`, `viewTech()`, etc.
all branch on `$this->connected()` first and return safe defaults when false, so
the shell renders even without a database.

### 1.8 `page_gen` class (lines 309–354)
- `start()` captures microtime; `stop()` captures end; `gen()` returns rounded diff (`round_to` default 4).
- Used by `footer.tpl` to print page-generation time.

---

## 2. `User extends Chive` — base/User.class.php

Authentication, session bootstrap, registration.

### 2.1 Properties

| Property | Type | Default |
|----------|------|---------|
| `userName` | `?string` | `null` |
| `password` | `?string` | `null` |
| `access` | `int` | `0` |
| `loggedIn` | `bool` | `false` |
| `userid` | `?int` | `null` |
| `raceID` | `?int` | `null` |
| `progress` | `?int` | `null` |

### 2.2 `__construct(string $userName = "", string $password = "DoodleCakes and Rofl Sundae4278vsid")` (line 20)

Decision tree (in order):

1. **Demo-fallback fast path:** if `$userName` is non-empty and
   `getLocalDemoLoginFallbackForInput()` matches → set `loggedIn=true, access=1,
   userid=1, raceID=1, progress=0`, write all into `$_SESSION`, **return early**
   (skips DB entirely).
2. `connectToDB()`.
3. **Session path:** if `$_SESSION['username']` and `$_SESSION['password']` exist
   → restore all session fields.
4. **Login-form path:** else sanitize `$userName`, keep `$password`.
5. `isRealUser()`:
   - success → `loggedIn=true`, repopulate session, update `users.lastLogin=time()`, log via Debug.
   - failure → `loggedIn=false, access=0`.

**Sub-logic — default password param:** when `$password` is omitted, the sentinel
value is used. A real password must always be supplied by the login form, so a
blank/omitted password never authenticates.

### 2.3 `sanitizeLoginValue(string $value): string` (line 92)
`trim()` → `strip_tags()` → collapse whitespace runs to single spaces.

### 2.4 Demo login fallbacks
- `getLocalDemoLoginFallbackForInput(inputUser, inputPassword): bool` (line 99): matches `copilotpilot` / `SGWLogin123!` (case-insensitive username).
- `getLocalDemoLoginFallback(): bool` (line 115): same check against instance props.
- **Both set** `access=1, userid=1, raceID=1, progress=0` on success.

### 2.5 `isRealUser(): bool` (line 134)

1. **Not connected** → return the demo fallback result (only demo login succeeds without DB).
2. Compute candidate hashes of the submitted password:
   - plain `$password`
   - `md5($password)` (legacy)
   - `md5(crypt($password, '.u55ybcbC,ufzQu2'))` (legacy salted)
   - `md5(userName . ':' . password)`
   - `hash('sha256', $password)`
3. Query `users` + `userdata` by `email=? OR uname=?`.
4. If stored hash equals **any** candidate → set `access=alevel`, `userid=uid`,
   `raceID=rid`, `progress`; return true.

**Sub-logic:** multiple hash formats are accepted for backward compatibility with
old accounts; new accounts store `salt()` output.

### 2.6 `isAllowed(int $reqAcc): bool` (line 186)
`(int)$reqAcc & $this->access` — bitmask permission check.

### 2.7 `logOut(): void` (static, line 195)
Nulls session vars, `session_unset()`, `session_destroy()`.

### 2.8 `salt(string $value): string` (line 211)
`md5(crypt($value, '.u55ybcbC,ufzQu2'))` — the same legacy salted hash used for
password storage and for `fieldtocrypt()` tech-form tamper protection.

### 2.9 `addUser(string $userName, string $password, string $email, int $rid, string $hpname, string $ip, int $access = 1): bool` (line 228)

Validation sequence:
1. Connect; abort with message if impossible.
2. Trim inputs; `$passwordHash = salt($password)`; coerce `$rid`/`$access`.
3. Race guard: only `r_group='player'` races are allowed; fall back to first player race (then `1`).
4. Required fields non-empty + `filter_var($email, FILTER_VALIDATE_EMAIL)`.
5. IP: `ip2long`→unsigned int; `ip=0` placeholders are allowed, otherwise **one account per IP** check.
6. Unique check: username or email must not already exist.

Insert transaction (all-or-nothing `begin_transaction`/`commit`/`rollback`):
- `users` (uname, email, allyid=0, lastLogin, arank=0, ip, password=hash, alevel)
- `bank` (onHand=250000, inbank=0)
- `player_resources` (metal 1200 / crystal 900 / deuterium 600 / food 80000 / water 70000 / population 150000 / energy 15000, last_tick_at=now)
- `units` (untrained=250, all else 0)
- `technology` (unitProd=1, everything else 0)
- `power` and `rank` (all 0)
- `planets` (isHome=1, name=`$hpname`)
- `userdata` (actionTurns=250, rid, uname, link=`genUniqueLink()`)

Prints "Registration Complete" and returns true; any exception rolls back and prints failure.

### 2.10 `genUniqueLink(): string` (line 392)
Random lowercase letters (length = half the digits of `time()`), followed by the
raw `time()`. Used as the per-account `userdata.link`.

---

## 3. `Game extends User` — base/Game.class.php

The gameplay engine. Sections mirror file order.

### 3.0 Constants and state

| Constant | Value | Use |
|----------|-------|-----|
| `TURN_TICK_MINUTES` | 30 | cadence of a turn |
| `TURNS_PER_MINUTE` | 6 | turns granted per minute (→ 180 per tick) |
| `UNIT_PROD_BASE_LEVEL` | 6 | baseline unit-production factor |
| `PLAYER_RACES` | 1 Ancient, 2 Nox, 3 Tau'ri, 4 Asgard, 5 Tok'ra | selectable races |

Public state: `gameTime`, `isRank`, `actionTurns`, `inHand`, `inBank`,
`nextTurn`, `numMessages`, `uid`, `rid`, `fields` (the technology field-name list
used by `fieldtocrypt`).

Constructor: calls `parent::__construct` (User), then when connected runs the
**ensure*() table-guard family** (see 3.2).

### 3.1 `nextTurn(): int` (line 44)

Countdown in minutes to the next turn boundary (00:00 or 00:30).

Loop `$x = 1 .. 2` (60/30):
- if `minute` is in `[($x-1)*30, $x*30]` (inclusive), `nextTurn = ($x*30) - minute`.

**Sub-logic / known edge:** because both bands use inclusive `<=`, minute values
in `30..45` match the `$x=2` band first and report `60-minute` (time until the
next 00 boundary) instead of `30-minute`. Minutes ≥ 45 report `60-minute`
correctly; minutes < 30 report `30-minute` correctly. Design intent is
"minutes until next 00/30 boundary".

### 3.2 The `ensure*` table-guard family (constructor)

All use `CREATE TABLE IF NOT EXISTS` + `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`
+ `INSERT IGNORE` — idempotent provisioning on every request.

| Method | Tables | Notes |
|--------|--------|-------|
| `ensureRaceCatalog()` (line 93) | `race` | Seeds 5 player races + 9 NPC races (Goa'uld, Replicator, Wraith, Ori, Genii, Jaffa, Unas, Reetou, Vanir) with `ON DUPLICATE KEY UPDATE` |
| `ensureMessagesTable()` (line 131) | `messages` | inbox schema + `idx_toUID`/`idx_fromUID` |
| `ensureUnitMetadataTables()` (line 149) | `unitcost`, `unitnames` | One row per race; auto-generates names ("<Race> Infantry", "… Strike Guard", "… Raiders", "… Defenders", "… Shield Guard", "… Sentinels", "… Scouts", "… Shadow Ops", "… Wardens", "… Watchers"); base costs attack=100, superAttack=250, defense=100, superDefense=250, covert=150, superCovert=300, anticovert=150, superAnticovert=300 |
| `ensureActionLogTable()` (line 230) | `actionlog` | combat/covert report rows + `phrase`, `thereDead`, `myDead`, `attackPower`, `defensePower`, `turnsUsed` columns |
| `ensureUnitCatalogTable()` (line 260) | `unit_catalog` | New-style catalog (`unit_code` unique, category/class/tier/rank/powers); seeds MIL-001, CIV-001, GOV-001 if empty |
| `ensurePlayerStateTables()` (line 300) | `bank`, `rank`, `userdata`, `player_resources` | Creates all four + inserts defaults for the session user when logged in |

**New-player defaults (written for the logged-in uid):**
- `bank`: onHand **250,000**, inbank 0
- `rank`: overall 0
- `userdata`: actionTurns **250**, rid 1, cid 0, progress 0, alevel 1
- `player_resources`: metal 1200 / crystal 900 / deuterium 600 / food 80000 / water 70000 / population 150000 / energy 15000, `last_tick_at=UNIX_TIMESTAMP()`

### 3.3 `autoLoad(): string` (line 380)

Returns the **14-slot JS array string** consumed by `stats.php`/`js/auto.js`:

```
new Array("inHand","inBank","isRank","turns","serverTime","messages","nextTurn minutes","metal","crystal","deuterium","food","water","population","energy")
```

Logic:
1. `gameTime = date("F jS H:i:s")`.
2. Not logged in → array of zeros with a `250` turns stub.
3. Ensure `bank`/`rank`/`userdata`/`player_resources` exist + seed for session uid.
4. Prepared query joins `bank`+`userdata`+`rank` and (if the `messages` table exists) LEFT JOINs an inbox count; returns `onHand`, `inBank`, `overall`, `actionTurns`, `messageCount`.
5. Reads the 7 strategic resources from `player_resources` (checks the `energy` column exists first for older installs).
6. Number-formats everything; slot 7 = `$this->nextTurn()." minutes"`.
7. Sets `$_SESSION['money'] = $bankOnHand` (used by `trainUnits` cost checks).

### 3.4 `messageCount(): string` (line 523)
`SELECT count(message) FROM messages WHERE toUID=?` → `num_rows` formatted. Note:
counts **rows**, not message values.

### 3.5 `baseVars(): object` (line 535)

Returns the empire-dashboard object; no DB → safe zero object.

SQL joins `users/userdata/race/units/planets/planetsize/users(cid)/technology`
and computes two live aggregates:

- **income** = `(miners*(80+technology.income)) + (lifers*(80+technology.income)) + SUM(planets.income_bonus) + (race.income_bonus * (miners*(80+technology.income) + lifers*(80+technology.income)))`
- **up** (unit production) = `(technology.unitProd*(3+technology.uppl)) + SUM(planets.up_bonus) + (race.up_bonus * (technology.unitProd*(3+technology.uppl)))`

Also returns `ttlPlanetsOwned`, commander (`cid`/`cname`), planet size text, home planet name.

### 3.6 `getRanks(): object` (line 587)
Joins `rank` and `power`; returns attack/defense/covert/anti/military rank numbers
and raw power values, plus `mil` = sum of the four rank columns. No DB → zeros.

### 3.7 `getPersonnel(int $uid): ?object` (line 654)
Full unit counts (`attackCount`…`superAnticovertCount`, `uuCount`, `minerCount`,
`liferCount`), per-race unit names, per-race unit costs, and `ttlarmysize` (sum
of all 13 unit columns). No row → a default zero object with default names/costs.

### 3.8 `getOfficers(int $uid): array` (line 735)
Officers = players whose `userdata.cid = $uid`. For each: uid, name, rank,
race, army size, mercs, plus `formalLeaderboardTitle()` title/band/prestige.

### 3.9 `Rankings(int $pnum=1): array` (line 773) and `allyRankings(int $allyid, int $pnum=1): array` (line 839)

Leaderboard pagination (25/page). `$page[0]=($pnum-1)*25`, `$page[1]=$pnum*25-1`.

**Covert gating (sub-logic):**
- Compute viewer's `covact = SUM(mil_cov + mil_anti)` from `power`.
- If `covact < 0.20 × target_covact` → army shown as `??????`.
- If `covact < 0.25 × target_covact` → cash shown as `??????`.
- Each row enriched with `formalLeaderboardTitle()`.

`allyRankings` adds `users.allyid = ?` to the WHERE.

### 3.10 `getallyinfo(int $allyid): object` (line 911)
`SELECT * FROM alliances WHERE allyid=?`.

### 3.11 `getUserInfo(int $uid): object` (line 925)
Profile for attack/defense pages. Returns `userName, rank, covPro, treasury,
cmdrName, cmdrID, race, onHand, armySize` + leaderboard title fields. Applies the
same 0.20/0.25 covert-gating to army/cash as `Rankings()`. Unknown user → safe
default object.

### 3.12 `getUserPlanets(int $uid): array` (line 1004)
`SELECT plnt_name, income_bonus, up_bonus, plnt_size (+ size_text via planetsize)
WHERE uid=? ORDER BY isHome DESC, pid ASC LIMIT 100`. Renders bonus as
`"+N income / +M UP"`.

### 3.13 `getActionTurnsByUid(int $uid): int` (line 1031)
Reads `userdata.actionTurns` (0 default).

### 3.14 Commander chain

- `setCommander(int $commanderUid): string` (line 1042): validations — uid>0, not self, target exists — then `UPDATE userdata SET cid=?`. Returns a user-facing message.
- `clearCommander(): string` (line 1073): `UPDATE userdata SET cid=0`.

### 3.15 `sendSupport(int $toUid, string $supportType, int $amount): string` (line 1086)

Transfers resources to another player inside a transaction.

- **Broker fee:** `1%` of the amount (`floor($amount * 0.01)`); recipient gets `amount - fee`.
- Validates: target exists, amount > 0, not self.
- `supportType` switch:
  - `naq` → requires `bank.onHand >= amount`; debit sender, credit recipient.
  - `turns` → requires `userdata.actionTurns >= amount`; debit/credit.
  - `units` → requires `units.untrained >= amount`; debit/credit.
  - unknown → rollback.
- Every branch is checked inside `begin_transaction` with rollback on insufficient funds / exceptions. Returns a detailed outcome message.

### 3.16 `getWeapons(): array` (line 1197)
Armory catalog filtered by the **viewer's** race (`armory.rid = session raceID`),
ordered by power. Returned as `weapons['def'][...]` / `weapons['atk'][...]`
arrays with `name, power, cashcost, unitcost, wid, fieldname`
(`def0…`, `atk0…`).

### 3.17 `getWeaponInventory(int $uid): array` (line 1237)
Owned weapons joined against `armory` (per the owner's race). For each item:
`wid, name, quanity, power, strength, fieldname`, plus:
- `sell` = `cash_cost × (strength/weaponPower) × 0.80`
- `perpoint` = `round((cash_cost × 0.5) / weaponPower) × quanity`

### 3.18 `updatePower(int $uid): void` (line 1284) — the combat power engine

Recomputes `power.mil_atk/mil_def/mil_cov/mil_anti`. Steps:

1. Fetch `rid` from `userdata`.
2. Load the player's weapons (`weapons`×`armory`, race-matched, power DESC), planet bonuses, and the `combo` row (`units`, `technology`, `race`).
3. **Covert power:**
   - `cSpys = 5*covert + 10*superCovert`
   - `aSpys = 5*anticovert + 10*superAnticovert`
   - `c_pBonus = Σ planets.cov_bonus`
   - `covert = round((((sqrt(2^cov_lvl) * cSpys * (1+cov_lvl) * (1+race.cov_bonus)) + cSpys) * 10 + c_pBonus) * (1 + tech.covert/10))`
   - `anticovert = round((((sqrt(2^(anti_lvl+2)) * aSpys * (1+anti_lvl) * (1+race.cov_bonus)) + aSpys) * 10 + c_pBonus) * (1 + tech.anticovert/10))`
4. **Weapon assignment loop:** for every weapon, `weapon_power = min(strength, weaponPower)`.
   - `requireTrained=0` weapons add `weapon_power × quanity` directly (unlimited).
   - `requireTrained=1` weapons are capped by the units available, consumed in order Super Attack (×10) → Attack (×5) → Attack Mercs (×5); defense mirrors with Super Defense (×10) → Defense (×5) → Defense Mercs (×5). A track counter (`sAused`, `aused`, …) keeps cumulative usage per weapon.
5. **Tech multiplier:** `attackpower += (tech.attack/10) * attackpower`; same for defense.
6. **Planet + race bonuses:** add `Σ planets.atk_bonus/def_bonus`; then `attackpower += race.atk_bonus * attackpower` (and defense).
7. `UPDATE power SET mil_atk, mil_def, mil_cov, mil_anti`.
8. Sets `$this->fields` = the 24 tech field names used by the crypt functions.

> Note: `updatePower` is invoked before/after combat so rank and ratios stay fresh.

### 3.19 `buyWeapons(array $data): void` (line 1554)
- Sums `cashcost` and `unitcost` across all atk+def fields from the form.
- Guard: `cashavail >= cashcost && unitsavail >= unitcost && cashcost > 0`.
- For each weapon bought: if an inventory row exists → increment `quanity`; else
  `INSERT` with `strength = armory.weaponPower`.
- Deducts cash from `bank.onHand` and units from `units.untrained`.
- Echoes `"Purchase Successful"` or specific shortage message.

### 3.20 `trainUnits(int $atk, int $uberAtk, int $def, int $uberDef, int $miners, int $cov, int $uberCov, int $anti, int $uberAnti): void` (line 1669)

Converts untrained units into trained types for cash.

1. `autoLoad()` to refresh `$_SESSION['money']`; `getPersonnel()` for costs/avail.
2. `cashcost` = sum of qty×unitCost per type; miners cost **1,500 each**.
3. Negative inputs zeroed.
4. `unitcost = atk+def+miners+cov+anti` (super variants are upgraded from the base trained count, not from untrained).
5. Guards: enough cash + enough untrained + `cashcost>0` + enough base-trained units for each super conversion (`attackCount >= uberAtk`, etc.).
6. **Miners→lifers split:** `lifers = 0.1*miners`, `miner = miners - lifers`; the loop corrects rounding drift so `lifers+miner == miners`.
7. Two UPDATE passes:
   - add trained types (+ super variants) and miners/lifers;
   - subtract `unitcost` from `untrained` and subtract super amounts from base trained (`attack -= uberAtk`, …).
8. Deduct cash from `bank.onHand`. Echoes outcome message.

### 3.21 `untrainUnits(int $atk, int $def, int $cov, int $anti, int $min): void` (line 1796)
- Requires qty ≤ current trained counts (base attack/defense/covert/anticovert/miners).
- Adds all five quantities back into `untrained`; decrements each type column.
- No cash cost; echo `"Resignation of Units Successful"` or shortage message.

### 3.22 `attack_raid(string $type, int $uid, int $turns=0): ?int` (line 1834)

The full combat resolution. `$type` ∈ `attack` (Naquadah) | `raid` (untrained units).

**Setup:**
- Guards: `$turns>0`, `$uid>0`, `$uid != session userid`.
- One massive SELECT loads attacker rows + defender subselects (units, power, tech factors `auEffect/auRes/auSteal`, `acuEffect/acuRes`, `duEffect/duRes`, `cuEffect/cuRes`, defender `duSteal/protect`).
- Load attacker attack-weapons and defender defense-weapons (race-matched).
- **Power roll:** `atk = round(|(mt_rand(75,100)/100) × atkrPower|)`; `def` likewise vs `atkdPower`.
- **Weapon damage factor:** `aw_power = percs(def, atk)` (attackers' weapons take that % loss); `df_power = percs(atk, def)`.
- Builds human-readable equipment tables (`atkSent`, `defSent`) with per-type weapon assignment (super → attack → mercs, capped by unit counts).

**Outcome:**
- If `atk > def`:
  - `raid`: loot `resources = |round(uu_target × ((mt_rand(15,25)/100 + mt_rand(15,25)/100)/2)) × resStolen|` **untrained units** moved from target to attacker.
  - `attack`: loot `|round(target.onHand × resStolen × (mt_rand(50,60)/100))|` **Naquadah** moved.
  - `resStolen = 0.75 + ((atk.auSteal/100) - (target.duSteal/100))`.
  - `succes = 1`.
- Else: no loot, `succes = 0`.

**Casualties (damage modifiers):**
- `atkrDmg = 1 + ((target.duEffect/50) - (atk.auRes/50))`
- `defrDmg = 1 + ((atk.auEffect/50) - (target.duRes/50))`
- `covDmg  = 1 + ((atk.acuEffect/50) - (target.cuRes/50))`
- `antiDmg = 1 + ((atk.cuEffect/50) - (target.acuRes/50))`
- Dead per type = `round(typeCount × percs(opposite_power, own_power) × dmgFactor)`, e.g. `uberAtkDead = round(superAttack × percs(def,atk) × defrDmg)`; anti/covert dead use `covDmg`/`antiDmg`.

**Persistence:**
- Weapon strengths decayed: `strength -= strength × own_weapon_damage_factor` per weapon (attacker and defender).
- Unit columns decremented by dead counts.
- `actionlog` row inserted (all forces + casualties + weapon tables + phrase + stolen).
- `userdata.actionTurns -= $turns`.
- Both players' `updatePower()` refreshed.
- Returns the `actID` of the report.

### 3.23 `percs(float $val1, float $val2): float` (line 2199)

Weapon-loss percentage lookup table (ratio `val1/val2`, randomized bands):

| Condition | power |
|-----------|-------|
| either zero | 0 |
| `val1 <= 0.01*val2` | 0.0001 |
| `val1 <= 0.10*val2` | 0.01 |
| `val1 <= 0.25*val2` | 2–4 % |
| `val1 <= 0.50*val2` | 5–7 % |
| `val1 <= 0.75*val2` | 8–10 % |
| `val1 <= 1.50*val2` | 11–13 % |
| `val1 <= 2*val2` | 14–16 % |
| `val1 <= 3*val2` | 17–19 % |
| `val1 <= 4*val2` | 20–22 % |
| `val1 > 4*val2` | 23–25 % |

### 3.24 `getActID(int $actID): bool` (line 2228)

Battle-report renderer. Authorization: `act.to_uid` or `act.uid` must be the
session user, else prints "Not Authorized…" and returns true.
- `attack`/`raid` → prints attacker/defender equipment, powers, casualties, weapon status, phrase.
- `spy` → renders the comma-separated `atkWeaponStatus` array as a personnel/info/weapons report (see `spy()` for the array layout).

### 3.25 `actionLog(string $type="attack"): void` (line 2423)
Two queries: actions **by me** (to_uid) and **on me** (from uid), ordered by
`actID DESC`, for the given `type`. Renders a table with time, enemy, result,
turns, losses, damage, and a "Details" link into `actionLogs.php`. Supported
types: `attack`, `raid`, `spy`.

### 3.26 `turnUpdate(): bool` (line 2567) — legacy 30-minute turn tick

1. One query per player returns `income`, `up`, and `onHand` (same formulas as `baseVars`).
2. Per player (in a loop):
   - `bank.onHand += income`
   - `userdata.actionTurns += TURN_TICK_MINUTES × TURNS_PER_MINUTE` (**180** per tick)
   - `units.untrained += up`
3. **Rank recompute (sub-logic):** iterates 7 ordered queries and writes positional ranks:
   - `atk` ← order by `power.mil_atk DESC`
   - `def` ← `mil_def DESC`
   - `cov` ← `mil_cov DESC`
   - `anti` ← `mil_anti DESC`
   - `up` ← by unit-production formula DESC
   - `inc` ← by income formula DESC
   - `overall` ← by `avg(rank.mil_cov+mil_def+mil_atk+mil_anti+up+income)` DESC
4. `UPDATE rank SET mil_atk,mil_def,mil_cov,mil_anti,up,income,mil_total,overall`.

> This echoes verbose "… Rank is N" lines — designed for cron output. `30min.php`
> invokes it manually.

### 3.27 `delOld(): void` (line 2693)
Deletes users whose `lastLogin == date('F jS', time() - 30 days)` (string compare)
along with their `bank, planets, power, rank, technology, units, userdata, weapons`
rows. Note the string-equality quirk: it purges everyone whose stored lastLogin
string equals the value from 30 days ago.

### 3.28 `viewTech(): object` (line 2746)
Reads the player's `technology` row + `ttl` (total tech levels + 1). Safe zero
object when no DB/stmt/row.

### 3.29 Tech-form tamper protection

- `fieldtocrypt(): array` (line 2846): returns the 24 `$this->fields` names, each passed through `salt()`.
- `crypttofield(string $crypt): ?string` (line 2858): inverse lookup — find which field name salts to `$crypt`, return the plain name or `null`.
- Forms submit the salted token instead of the raw column name so players can't rewrite arbitrary columns.

### 3.30 `buytech(string $crypt, int $quanity=1): void` (line 2877)

1. Resolve `$type = crypttofield($crypt)`; abort on bad token.
2. Load current tech + `bank.onHand/inBank`; `money = onHand + inBank`.
3. `level(ascend)` gives the base scalars `y`/`x`; `cost base = y × ttl`.
4. **Per-type cost formulas:**
   - `unitProd`: per level `((ascend+1)×5,000,000) × (unitProd+x)`; max `(ascend+1)×500`
   - `uppl`: `((ascend+1)×50,000,000) × (uppl+1+x)`; max `(ascend+1)×10`
   - `income`: `((ascend+1)×10,000,000) × (income+1+x)`; max `(ascend+1)×10`
   - `cov_lvl`/`anti_lvl`: start 15,000, double per current level; max 100,000
   - `ascend`: not implemented ("Ascension is not Ready Yet")
   - default (all other techs): `Σ (y × (ttl+x))`; max `x`
5. Validations: `quanity>0`, resulting level ≤ max, cash available.
6. Applies the level bump, then pays from `onHand` first, topping up from `inBank` (note: the onHand-top-up branch uses an `onHand=0 AND inBank=...` update).

### 3.31 `level(int $type): array` (line 2979)

Ascension tier metadata:

| tier | str | y (cost scalar) | x (level max) |
|------|-----|-----------------|---------------|
| 0 | Non Ascended | 500,000 | 15 |
| 1 | Prior | 1,000,000 | 20 |
| 2 | Prophet | 5,000,000 | 25 |
| 3 | Messiah | 10,000,000 | 30 |
| 4 | Incarnate | 50,000,000 | 35 |
| 5 | Living God | 100,000,000 | 40 |
| 6 | Fully Ascended | 500,000,000 | 45 |

### 3.32 Messaging
- `sendMessage(int $toUID, string $message, string $subject="None"): bool` (line 3028): INSERT into `messages` with `timeSent=date("F jS H:i:s")`.
- `viewMessages(): mysqli_result` (line 3075): inbox join with sender name.
- `deleteMessage($mid): bool` (line 3085): `$mid=="all"` clears inbox; numeric `$mid` deletes that message for the recipient only.

### 3.33 `create_allliance(int $UID, string $name, string $desc, string $forumadd, int $isclosed): bool` (line 3038)
Validates name, checks uniqueness, inserts into `alliances` (founder=`$UID`), then
`UPDATE users SET allyid=?, arank='2' WHERE uid=$UID`. Duplicate name → error.

### 3.34 `bank(string $type="view", float $ammount=0): ?object` (line 3103)

- `view`: returns `onHand`, `inBank`, **capacity** `cap` and `left = cap - inBank`, where
  `cap = (Σ planet_uids × (72 × income-formula) ) × (ascend+1)`. Left ≤ 0 when full.
- `deposit`: clamps amount to `left`, then
  `inbank += amount×0.95` (5% bank fee), `onHand -= amount`.
- `withdrawl`: clamps to `inBank`; `inbank -= amount`, `onHand += amount`.

### 3.35 `spy(int $uid, int $turns=0): ?int` (line 3186)

Covert-intel mission.

1. Guard `$turns>0`; refresh both players' power.
2. Ratio = viewer `fromCovert=(mil_cov+mil_anti)` vs target `toCovert`.
3. **Success / accuracy bands (switch on `fromCovert`):**
   - `>= 5×toCovert` → perc 1.0
   - `> 4×` → 0.8 · `> 3×` → 0.6 · `> 2×` → 0.4 · `> 1×` → 0.2 · `> 0.25×` → 0.1
   - `<= 0.25×` → **failed** (suc=0, perc=0)
4. Build the 33-field intel array (unit names/counts, totals, powers, levels, turns, up, income).
5. **Obfuscation (sub-logic):** `xyz = 20×(1-perc)` random indices (excluding name/count label positions and duplicate indices) are replaced with `??????`; lower ratio → more hidden fields.
6. Failed op → `actionlog` spy row with success=0, no intel.
7. Success → `actionlog` spy row with `atkWeaponStatus = implode(',', arrayFinal)`; returns the `actID`.

### 3.36 `sabotage(int $uid, int $turns = 0): void` (line 3387)
Currently **partially implemented**: validates turns, compares `fromCov > toCov`,
prints a flavor message and `print_r(getWeaponInventory(...))` (debug dump) — no
permanent destruction applied yet. Destructive behavior is the intended next step.

---

## 4. ThemeSupport — base/Theme.class.php

| Function | Behavior |
|----------|----------|
| `normalizeTheme($themeName, $fallback='og')` | Validates against known themes (`white`, `og`, `blue`, `stargate`); returns fallback for unknown |
| `themeClass($themeName)` | Body class `theme-<name>` |
| `themeOptions()` | `<option>` list for the theme picker |
| `brandTitle($value=null)` / `brandSubtitle($value=null)` | Getter/setter brand copy (config `{TITLE}`/`{SUBTITLE}`) |

## 5. Debug — base/Debug.class.php
Static `printMsg($class, $function, $message)` prints when the debug flag is on
(`config`), used pervasively for audit logging of DB/action activity.

## 6. Template engine — base/functions.php
- `showPage()`: renders `header.tpl + <self>.tpl + footer.tpl` via `template()`.
- `addSub($subName, $sub)`: registers `{subName}` → value in `$GLOBALS['subs']`.
- `template($filepath, $subs)`: `str_replace` each `{token}` then `eval("?>" . $text)` to execute embedded PHP, returning the captured output.

---

## 7. Cron economy tick — scripts/backend/game_tick.php

### 7.1 Entry
- CLI only; requires `mysqli` (exits 2 with driver guidance otherwise).
- Flags: `--uid=N` filter, `--dry-run` (no writes, prints what would happen).

### 7.2 Helpers
- `q(mysqli, string $sql)`: fire-and-forget query, logs errors to STDERR.
- `one(mysqli, string $sql): ?array`: first row assoc or null.
- `stargateBonus(mysqli, int $uid): array` (line 57): reads `stargate_tech_levels`
  and returns per-category multipliers:
  - `production += lantian_knowledge_matrix×0.008 + time_dilation_calculus×0.007 + transit_manifest_ai×0.005`
  - `energy += zero_point_theory×0.020 + zpm_focusing×0.018 + reactor_overdrive×0.015 + grid_redundancy×0.010`
  - `deuterium += wormhole_topology×0.010 + destiny_navigation×0.008 + phase_inverters×0.007`
  - `population += ascension_interface×0.005 + fortress_polarization×0.004`
- `calcRates(ctx, levels, sgBonus)` (line 99): per-30-min production rates:
  - `metal = ((incomeBase×0.40 + planets×180 + up×8 + techProd×20) × (1 + metal_mine×0.12)) × prodMul`
  - `crystal = ((incomeBase×0.28 + planets×140 + up×5 + techIncome×16) × (1 + crystal_lab×0.12)) × prodMul`
  - `deuterium = ((incomeBase×0.18 + planets×120 + up×3 + techIncome×12) × (1 + refinery×0.12)) × prodMul × deutMul`
  - `food = ((incomeBase×0.14 + planets×220 + techIncome×9) × (1 + hydroponics×0.10)) × prodMul`
  - `water = ((incomeBase×0.12 + planets×240 + techIncome×8) × (1 + water_plant×0.10)) × prodMul`
  - `population = max(25, (planets×30 + up×0.35) × (1 + habitat_dome×0.08)) × popMul`
  - `energy = ((incomeBase×0.22 + planets×160 + techProd×14 + techIncome×10) × (1 + reactor×0.13)) × energyMul`

### 7.3 Per-player loop
1. `INSERT IGNORE` rows for `player_resources`, `resource_structures`, `hyperspace_systems`.
2. Compute income/up/tech context + planet count (all nullable-safe, defaults 220/10/0/0/1).
3. `ticks = floor((now - last_tick_at) / 1800)` — whole 30-minute intervals only.
4. If `ticks>0`: apply `stock += rate×ticks`, then **upkeep**:
   - `foodUse = population×0.008×ticks`, `waterUse = population×0.007×ticks`, `energyUse = population×0.005×ticks`.
   - if any of food/water/energy hits 0 → `population -= max(150, 2%)`.
   - `UPDATE player_resources SET ..., last_tick_at=NOW()` (skipped in dry-run).
5. **Transits:**
   - `status='enroute' AND eta_at <= NOW()` → become `arrived`; expeditions pay `metal 2500–12000 + core×240`, `crystal 1800–9000 + stargate×180`, `deuterium 1200–7600 + jump×140`.
   - `status='arrived' AND return_at <= NOW()` → become `completed`.
6. Prints summary counts: users processed, resource updates, transits arrived/completed.

---

## 8. Client JS function inventory

### `js/main.js`
| Function | Logic |
|----------|-------|
| `getStoredTheme()` / `setTheme(name)` / `initThemePicker()` | localStorage `sgwTheme` + body class `theme-*` |
| `autocomplete(sender, ev)` | key-up autocomplete for target inputs |
| `toggle_visible(elName)` | show/hide element |
| `sendData(page,type,id,atype,subject,message)` (line 104) | builds `modules/<page>.php?id=&atype=&time=` (+ form POST when `type=='post'`), issues XHR, routes response to `stylizeDiv` |
| `mainUpdate(page,text)` (line 130) | loads `indexpages/<page>.php` into `#mainDisplay` |
| `handleResponse()` / `initReq` / `httpRequest` | XHR plumbing (onreadystatechange) |
| `stylizeDiv(bdyTxt,div)` (line 191) | inject HTML fragment + re-run inline scripts |
| `setQueryString()` | encodes current form into query string |
| `MM_swapImgRestore/preloadImages/findObj/swapImage` | legacy image rollover |
| `disableFormElements(formD)` / `disableFormElementsAfterSubmit(name)` | prevent double submits |

### `js/auto.js`
| Function | Logic |
|----------|-------|
| `autoLoad()` | GET `stats.php`, parse 14-slot array, update stat pills |
| `autoHandle()` | applies parsed values to the top bar (bank, turns, rank, time, messages, resources) |
| `autoReq(reqType,url,bool)` / `autoRequest(...)` | XHR wrapper |
| 15 s timer | re-polls stats |

### `js/train.js`
`trainthis(page,type,id)` + response plumbing; `setQueryStringTrain(page)` for the
train/untrain forms.

### `js/search.js`
`Suggest(...)` object with `query()/sendQuery()/process()/htmlFormat()/
keyupHandler/keypressHandler/highlight()/autosuggest()/typeAhead()/selectRange()/
trim()` — the player-lookup autocomplete used on attack/spy/message targets
(server: `userlist.php`).

### `js/bbfix.js`
`bb_init(div_name, debug_val)` + state helpers for the BBCode toolbar fix.

### `js/images.js`
Macromedia `MM_*` image-swap helpers (kept for legacy markup).

---

## 9. Shared helper library — modules/formal_logic.php
Included by `Game`; provides the domain predicates used across modules:

- `formalLeaderboardTitle(int $rank, int $armySize, int $onHand): array` — returns `['title','band','prestige']` used by rankings, officers, and player profiles.
- Other `formal*` validators/coercers (race, entity names — see `tests/formal_logic_test.php`).
