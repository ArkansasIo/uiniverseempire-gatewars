# Game Mechanics

> Core gameplay rules: turns, resources, training, combat, tech, and economy
> systems. Constants and formulas are listed with their source location.

## 1. Turns and the Turn Cycle

- **Turn cadence:** a turn advances every **30 minutes** (`TURN_TICK_MINUTES = 30` in `base/Game.class.php`).
- **Turn cap:** action turns are tracked in `userdata.actionTurns`; the display
  splits turns into "in hand" vs. "next turn in HH:MM".
- **Next-turn countdown:** `Game::nextTurn()` (base/Game.class.php:44) computes the
  minutes until the next 00/30 minute boundary. Server time is refreshed by the
  `stats.php` poll.
- **Manual tick:** `30min.php` forces `turnUpdate()` on the requesting player.
- **Cron tick:** `scripts/backend/game_tick.php` runs the strategic resource
  economy every 5 minutes, advancing stored resource ticks on a 30-minute cadence.

## 2. Resources

### 2.1 Strategic resources (`player_resources`)

Each player has seven strategic resources, produced by `resource_structures`
and advanced by the cron economy tick:

| Resource | Structure | Notes |
|----------|-----------|-------|
| Metal | Metal Mine | primary construction currency |
| Crystal | Crystal Lab | ship/tech currency |
| Deuterium | Deuterium Refinery | fuel for fleets/hyperspace |
| Food | Hydroponics | population upkeep |
| Water | Water Plant | planet/settlement upkeep |
| Population | Habitat Dome | grows via housing, consumed by jobs |
| Energy | Energy Reactor | production capacity; negative energy throttles output |

The economy tick computes elapsed slots (`floor(now - last_tick_at / 1800)`) and
adds `rate × slots` to each resource, then advances `last_tick_at` by `slots × 1800`
so partial intervals roll forward cleanly.

### 2.2 Naquadah and the Bank

- Naquadah lives in two buckets: `bank.onHand` (spendable) and `bank.inbank`
  (storage). `Game::bank()` moves funds between them.
- Income is paid by `turnUpdate()` on the 30-minute cadence and is a function of:
  - race `income_bonus` (Ancient/Nox/Tau'ri/Asgard/Tok'ra),
  - `technology.income` level,
  - number of miners,
  - `planets.income_bonus` per owned planet,
  - untrained unit population.

### 2.3 OGame-style buildings

`ogamebuildings.php` + `ogame_building_levels` track Mine/Crystal/Refinery/
Hydroponics/Water/Reactor levels with per-level build cost curves; higher levels
increase the per-tick production rate used by `game_tick.php`.

## 3. Units and Training

- Unit types: Attack, Super Attack, Attack Mercs, Defense, Super Defense,
  Defense Mercs, Untrained, Miners, Lifers, Covert, Super Covert, Anti-covert,
  Super Anti-covert (`units` table).
- **Training** (`Game::trainUnits()`): untrained units convert into a chosen
  troop type at a per-turn rate governed by `technology.unitProd` and
  `UNIT_PROD_BASE_LEVEL = 6`.
- **Untraining** (`Game::untrainUnits()`): converts a troop type back to
  untrained at a reduced rate (cash + time cost).
- **Unit costs** are per-race (`unitcost` table); display names are per-race
  (`unitnames` table).
- **Recruiting:** `modules/military.php` recruits untrained units with cash.
- **Mercenaries** (attack/defense mercs) can be bought instantly for cash and
  fight alongside regular units without training time.

## 4. Weapons and Armory

- The **armory** catalog (`armory` table) defines weapons per race with
  `cash_cost`, `unit_cost`, `weaponName`, `weaponPower`, and `requireTrained`;
  `isDefense` splits attack vs. defense weapons.
- Players buy weapons with cash + trained units (`Game::buyWeapons()`); owned
  weapons are stored in `weapons` (uid, wid, strength, quantity).
- Weapon power feeds `updatePower()` → the `power` snapshot used for rank and
  combat ratios. See the fixed sell-link handler in `modules/armory.php` for the
  current buy/sell form pattern.

## 5. Combat

### 5.1 Attack / Raid (`Game::attack_raid()`)

1. Attacker power vs. defender power (attack units + attack weapons vs. defense
   units + defense weapons), each modified by `technology` attack/defense levels
   and commander bonuses.
2. Loss ratio is a function of the power ratio, capped so defenders always take
   at least a minimum.
3. Loot = attacker income + steal potential (`auSteal` / `duSteal` / `cuSteal`
   tech), capped by what the defender holds and by a raid cap.
4. Both players get an `actionlog` report; mission is written to the log with
   `getActID()`.

### 5.2 Spy (covert) and Sabotage

- **Covert action** power = covert/super covert units × covert tech level vs.
  target anti-covert power.
- `Game::spy()` returns target intel (units, resources, tech) proportional to the
  covert-action ratio; failures risk detection.
- `Game::sabotage()` destroys a target's weapons or resources on a successful
  covert-action check; losses scale with `auRes`/`duRes`/`cuRes` resistance tech.

## 6. Technology

`technology` row per player; each tech has an effect and a cost curve:

| Tech | Effect |
|------|--------|
| income | Naquadah per turn |
| unitProd | units trained per turn |
| uppl | unit power per level (unit production power) |
| covert / anti-covert | spy action power / defense |
| cov_lvl / anti_lvl | levels of those techs |
| attack / defense | combat multipliers |
| auEffect / auRes / auSteal | attack-unit effectiveness/resistance/steal |
| acuEffect / acuRes | attack-covert-unit effectiveness/resistance |
| duEffect / duRes / duSteal | defense-unit effectiveness/resistance/steal |
| cuEffect / cuRes | covert-unit effectiveness/resistance |
| ascend | ascension level |
| galaxy | galaxy/universal bonus |
| pDef / puCap / pmCap | planet defense / unit caps |

Tech is bought via `modules/techlib.php` and the `buytech()` method; research
infrastructure (`research_infrastructure`) boosts buy speed.

## 7. Fleets and Hyperspace

- **Ships** are built in the `shipyard`; owned ships live in `fleet`; missions
  are queued in `fleet_missions` and executed by `fleetdock.php`.
- **Hyperspace** (`hyperspace.php` + `hyperspace_systems`/`hyperspace_routes`):
  - Jump gates / stargates / hyperspace cores have levels affecting range,
    capacity, and fuel cost.
  - Fleet transits (`hyperspace_transits`) move through states
    `enroute → arrived → completed`; `game_tick.php` advances them each run.
  - Sending fleet to another player costs deuterium fuel proportional to
    distance × fleet weight.
- **Fleet power** is the sum of ship power; used in raid/escort scenarios.

## 8. Universe and Colonization

- The universe suite (`modules/universe.php`, `modules/pages.php`) provides
  star systems, colony fields (`universe_colony_fields`), and story campaigns.
- `Game::getUserPlanets()` lists owned planets; new colonies are settled on
  free fields and add income/up bonuses.
- **World boss / events:** `universe_world_boss` with damage phases, plus world
  plagues and water sources; events are logged in `universe_event_log` and
  processed by `pages.php`.

## 9. Alliance and Diplomacy

- `Game::create_allliance()`, `allyRankings()`, `getallyinfo()` manage alliances
  and their rosters.
- Diplomacy suite covers treaties, embassies, and officer assignment
  (`setCommander`/`clearCommander`); commanders grant a stat bonus to the squad
  they lead.
- `sendSupport()` lets a player send units to an ally (transit via hyperspace).

## 10. Economy Systems

- **Market** (`modules/market.php`, `market_listings`): list metal/crystal/
  deuterium for Naquadah or vice versa; matching is manual (buyers accept offers).
- **Bank** as in §2.2.
- **Battle / season pass** (`economy_pass_progress` + `economy_pass_claims`):
  players earn pass XP from actions and claim milestone rewards.
- **Store** (`economy_store_catalog`/`economy_store_purchases`): one-time and
  repeatable purchases.
- **Governance** (`commandergov.php`, `governance_system_levels`): governor
  buildings that add global % bonuses (military, economy, research).
- **Economy passes** (`economy_pass_progress`): tracked by the cron for daily
  leaderboards.

## 11. RTS Turn System (Operations)

- `operations_rts_state` / `operations_turn_queue` (via `pages.php`) give the
  Operations suite a mini-RTS: building and troop build orders queue and resolve
  one per RTS turn, throttled by `operations_turn_queue`.

## 12. Balance Constants (source of truth)

| Constant | Value | Location |
|----------|-------|----------|
| `TURN_TICK_MINUTES` | 30 | `base/Game.class.php` |
| `TURNS_PER_MINUTE` | 6 | `base/Game.class.php` |
| `UNIT_PROD_BASE_LEVEL` | 6 | `base/Game.class.php` |
| `PLAYER_RACES` | 1 Ancient, 2 Nox, 3 Tau'ri, 4 Asgard, 5 Tok'ra | `base/Game.class.php` |
| 30-min resource tick | `last_tick_at` + 1800 | `game_tick.php`, `player_resources` |

> Tuning changes should update the constants in `base/Game.class.php` first, then
> any duplicated literals in the cron script and `pages.php` economy views.

## 13. Formula Reference (exact, from source)

> Every formula below is the literal expression used by the code. See
> `docs/FUNCTION_REFERENCE.md` for the surrounding logic.

### 13.1 Income (Naquadah per turn)

```
income = ( miners × (80 + tech.income) )
       + ( lifers × (80 + tech.income) )
       + Σ( planets.income_bonus )
       + race.income_bonus × ( (miners × (80+tech.income)) + (lifers × (80+tech.income)) )
```
(`baseVars()` and `turnUpdate()`)

### 13.2 Unit production (UP, untrained per turn)

```
up = ( tech.unitProd × (3 + tech.uppl) )
   + Σ( planets.up_bonus )
   + race.up_bonus × ( tech.unitProd × (3 + tech.uppl) )
```
(`baseVars()`, `turnUpdate()`)

### 13.3 Power (updatePower)

```
cSpys   = 5×covert + 10×superCovert
aSpys   = 5×anticovert + 10×superAnticovert
c_pBonus = Σ( planets.cov_bonus )

covert     = round( ( ( ( sqrt(2^cov_lvl) × cSpys × (1+cov_lvl) × (1+race.cov_bonus) ) + cSpys ) × 10 + c_pBonus )
                     × (1 + tech.covert/10) )
anticovert = round( ( ( ( sqrt(2^(anti_lvl+2)) × aSpys × (1+anti_lvl) × (1+race.cov_bonus) ) + aSpys ) × 10 + c_pBonus )
                     × (1 + tech.anticovert/10) )
```

Weapon contribution (per weapon, `power = min(strength, weaponPower)`):
- `requireTrained=0`: add `power × quanity` directly.
- `requireTrained=1`: consume units in order Super (×10) → normal (×5) → mercs (×5), capped by available units per track.

```
attack_power  = Σ weapon contributions
              + (tech.attack/10) × attack_power
              + Σ( planets.atk_bonus )
              + race.atk_bonus × attack_power
defense_power = same pattern with tech.defense, planets.def_bonus, race.def_bonus
```

### 13.4 Combat resolution (attack_raid)

```
atk_roll = round(| (mt_rand(75,100)/100) × attacker_power |)
def_roll = round(| (mt_rand(75,100)/100) × defender_power |)

resStolen = 0.75 + (atk.auSteal/100 − target.duSteal/100)

attack loot  = |round( target.onHand × resStolen × (mt_rand(50,60)/100) )|   # Naquadah
raid loot    = |round( target.untrained × ((mt_rand(15,25)/100 + mt_rand(15,25)/100)/2) × resStolen )|  # UU
```

Damage modifiers (from tech):
```
atkrDmg = 1 + ((target.duEffect − atk.auRes) / 50)
defrDmg = 1 + ((atk.auEffect − target.duRes) / 50)
covDmg  = 1 + ((atk.acuEffect − target.cuRes) / 50)
antiDmg = 1 + ((atk.cuEffect − target.acuRes) / 50)
```

Casualties:
```
dead = round( count × percs(opponent_roll, own_roll) × dmgFactor )
attackers:  superAttack × defrDmg ;  attack × defrDmg ;  attackMercs × defrDmg
defenders:  superDefense × atkrDmg ; defense × atkrDmg ; defenseMercs × atkrDmg
covert:     (superCovert, covert) × antiDmg
anticovert: (superAnticovert, anticovert) × covDmg
```

Weapon decay: `strength' = strength − strength × percs(opponent_roll, own_roll)` per weapon.

`percs()` loss table — see FUNCTION_REFERENCE §3.23 (bands 0%…25% depending on
power ratio; heavier underdog → heavier weapon damage).

### 13.5 Covert missions (spy)

Accuracy by power ratio `from = viewer(mil_cov+mil_anti)`, `to = target(mil_cov+mil_anti)`:

| ratio `from/to` | intel accuracy | result |
|-----------------|----------------|--------|
| ≥ 5 | 100 % | success |
| > 4 | 80 % | success |
| > 3 | 60 % | success |
| > 2 | 40 % | success |
| > 1 | 20 % | success |
| > 0.25 | 10 % | success |
| ≤ 0.25 | 0 % | failed |

Hidden fields = `20 × (1 − accuracy)` out of a 33-field intel array, replaced
with `??????`.

### 13.6 Bank capacity and fees

```
cap  = ( Σ planets (by uid join) × (72 × income_formula) ) × (ascend + 1)
left = cap − inBank          # clamp for deposits
deposit  → inBank += amount × 0.95   (5% fee),  onHand -= amount
withdraw → onHand += amount,          inBank -= amount   (no fee)
```

### 13.7 Technology costs (buytech)

| Tech | Per-level cost | Max level |
|------|----------------|-----------|
| unitProd | `(ascend+1)×5,000,000 × (unitProd + x)` | `(ascend+1)×500` |
| uppl | `(ascend+1)×50,000,000 × (uppl+1+x)` | `(ascend+1)×10` |
| income | `(ascend+1)×10,000,000 × (income+1+x)` | `(ascend+1)×10` |
| cov_lvl / anti_lvl | start 15,000; double per current level | 100,000 |
| all other techs | `level().y × (ttl + x)` | `level().x` |
| ascend | not implemented | — |

`ttl = 1 + attack + duSteal + auEffect + auRes + defense + duSteal + duEffect +
duRes + covert + cuEffect + cuRes + anticovert + acuEffect + acuRes + pDef`.

### 13.8 Strategic resource tick (game_tick.php, per 30-minute slot)

Base context: `incomeBase = max(220, income)`, `upBase = max(10, up)`,
`planetCount = max(1, count(planets))`.

```
metal     = round( (incomeBase×0.40 + planets×180 + up×8 + techProd×20) × (1 + metal_mine×0.12) ) × prodMul
crystal   = round( (incomeBase×0.28 + planets×140 + up×5 + techIncome×16) × (1 + crystal_lab×0.12) ) × prodMul
deuterium = round( (incomeBase×0.18 + planets×120 + up×3 + techIncome×12) × (1 + refinery×0.12) ) × prodMul × deutMul
food      = round( (incomeBase×0.14 + planets×220 + techIncome×9)  × (1 + hydroponics×0.10) ) × prodMul
water     = round( (incomeBase×0.12 + planets×240 + techIncome×8)  × (1 + water_plant×0.10) ) × prodMul
population= max(25, round( (planets×30 + up×0.35) × (1 + habitat_dome×0.08) ) × popMul )
energy    = round( (incomeBase×0.22 + planets×160 + techProd×14 + techIncome×10) × (1 + reactor×0.13) ) × energyMul
```

Upkeep per slot (applied each tick, `ticks` = elapsed 30-min slots):
```
foodUse   = round( population × 0.008 × ticks )
waterUse  = round( population × 0.007 × ticks )
energyUse = round( population × 0.005 × ticks )
if food == 0 OR water == 0 OR energy == 0 → population -= max(150, round(population×0.02))
```

Stargate tech multipliers (additive per level):
```
prodMul  += lantian_knowledge_matrix×0.008 + time_dilation_calculus×0.007 + transit_manifest_ai×0.005
energyMul+= zero_point_theory×0.020 + zpm_focusing×0.018 + reactor_overdrive×0.015 + grid_redundancy×0.010
deutMul  += wormhole_topology×0.010 + destiny_navigation×0.008 + phase_inverters×0.007
popMul   += ascension_interface×0.005 + fortress_polarization×0.004
```

### 13.9 Support transfer

```
brokerFee = floor( amount × 0.01 )     # 1%
received  = amount − brokerFee
```
Allowed types: `naq` (bank.onHand), `turns` (userdata.actionTurns), `units` (units.untrained).
