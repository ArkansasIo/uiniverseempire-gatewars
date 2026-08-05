# Cron Jobs & Backend Operations

> Scheduled processing and operational scripts. Canonical reference lives at
> `scripts/backend/cron_jobs.md`; this page documents the design.

## 1. Game Tick — `game_tick.php`

Runs every **5 minutes** and processes:

- resource economy ticks on a **30-minute cadence** (`player_resources`:
  metal, crystal, deuterium, food, water, population, energy),
- food/water/energy upkeep and population penalties,
- hyperspace transit arrivals and returns (`enroute → arrived → completed`),
- expedition rewards.

### Usage

```bash
php scripts/backend/game_tick.php            # live run
php scripts/backend/game_tick.php --dry-run  # show what WOULD happen, no writes
php scripts/backend/game_tick.php --uid=1    # single player only
```

`--dry-run` is the safe smoke test. A live run requires the `mysqli` PHP driver
and reachable MySQL; without them the script exits with
`Missing PHP MySQL driver` (see §4).

### Crontab

```cron
*/5 * * * * /usr/bin/php /path/to/project/scripts/backend/game_tick.php >> /path/to/project/exports/game_tick.log 2>&1
```

Or use the wrappers:

- Unix: `scripts/backend/run_game_tick.sh`
- Windows: `scripts/backend/run_game_tick.ps1` (run from Task Scheduler)

## 2. Client-Side / Legacy Tickers

| Trigger | Endpoint | Cadence | Work |
|---------|----------|---------|------|
| `30min.php` | manual | on demand | runs `turnUpdate()` for the caller (Naquadah income, unit production, action turns) |
| `stats.php` | `js/auto.js` `autoLoad()` | 15 s | returns the 14-slot top-bar payload; no mutation |
| `count.php` | bb_fix iframe | on load | visit counter only, side-effect-free |

The legacy `turnUpdate()` (30-minute cadence) and the newer cron resource
economy (`game_tick.php`) are separate timers; both converge on a 30-minute
"turn". Do not merge them without reconciling `userdata.actionTurns` and
`player_resources.last_tick_at`.

## 3. Operational Scripts (`scripts/backend/`)

| Script | Purpose |
|--------|---------|
| `db_init.sh` | Create database + import baseline schema (`game.sql`) |
| `db_migrate.sh` | Apply `database/sql/03…07` bundle, record keys in `app_migrations` |
| `db_backup.sh` | Dump database to `exports/` |
| `healthcheck.sh` | Runtime + DB readiness checks |
| `export_reports.sh` | Generate SQL/CSV reporting exports (`tools/backend/export_reports.php`) |
| `game_tick.php` | Economy/turn tick processor (see §1) |
| `run_game_tick.sh` / `.ps1` | Cron wrappers |

## 4. Local Testing Notes

The current dev machine has **no MySQL server** and the CLI PHP build lacks the
`mysqli` extension (only `mysqlnd`/PDO), so:

- live `game_tick.php` runs exit with `Missing PHP MySQL driver`;
- unit-level/static checks (PHP lint, handler-paren checks, catalog tests,
  theme tests) are the available verification locally;
- to run live ticks, install MySQL, enable `mysqli` for the CLI SAPI, and set
  real credentials in `config.local.php` / `config.php`.

## 5. Daily Metrics Job

`app_server_jobs` + `app_daily_economy_metrics` (from `database/sql/03`)
support a once-daily aggregation of economy metrics, queried through the
reporting views in `database/sql/04_reporting_views.sql` and exported by
`export_reports.sh`.
