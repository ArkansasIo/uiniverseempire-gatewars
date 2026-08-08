# Cron Jobs

## Unified Game Tick

A single run of `game_tick.php` advances every time-based game system through
the `GameTick` engine (`base/GameTick.class.php`).

- **fast cadence**: +6 action turns every 10-second tick and n% (default 100%)
  of the formal per-minute OGame production rate every minute. Because each
  player's elapsed time is tracked in `fast_tick_state`, this also advances on
  in-game page loads for logged-in players, so the fast cadence stays live even
  between cron runs.
- legacy turn economy: naquadah income, unit upkeep, action-turn refill,
  untrained unit production
- strategic resource economy (30-minute cadence): metal/crystal/deuterium/
  food/water/population/energy production
- hyperspace transit arrivals and returns
- fleet mission arrivals, completions and expedition rewards
- trade route transfers
- military troop training queues
- RTS operations turn queues
- colony power-grid catch-up and node upgrades
- market listing expiry sweep
- inactive account purge

Recommended scheduling: run the full engine every 5 minutes, and add a light
`--systems=fast` job every 10 seconds for the fast turn cadence:

```cron
* * * * * /usr/bin/php /path/to/project/scripts/backend/game_tick.php --systems=fast
*/5 * * * * /usr/bin/php /path/to/project/scripts/backend/game_tick.php >> exports/game_tick.log 2>&1
```

> Because the on-page-load hook already advances the fast cadence for logged-in
> players, the 10-second job is optional; without it, fast progress accrues
> while the game is being played and backfills with `FAST_MAX_CATCHUP` on the
> next load.

Command:

```bash
php /home/codespace/Stargate-Wars/scripts/backend/game_tick.php
```

Dry-run test (no writes):

```bash
php /home/codespace/Stargate-Wars/scripts/backend/game_tick.php --dry-run
```

Single player test:

```bash
php /home/codespace/Stargate-Wars/scripts/backend/game_tick.php --uid=1
```

Select specific systems (`turn,res,hyper,fleet,trade,mil,ops,grid,market,purge,fast`):

```bash
php /home/codespace/Stargate-Wars/scripts/backend/game_tick.php --systems=turn,res
```

Legacy turn-only tick (same engine, restricted to the turn economy):

```bash
php /home/codespace/Stargate-Wars/scripts/backend/turn_tick.php
```

Example crontab:

```cron
*/5 * * * * /usr/bin/php /path/to/your/project/scripts/backend/game_tick.php >> /path/to/your/project/exports/game_tick.log 2>&1
```

Or use the wrapper script on Unix-like systems:

```cron
*/5 * * * * /bin/bash /path/to/your/project/scripts/backend/run_game_tick.sh
```

On Windows, use PowerShell:

```powershell
# Run every 5 minutes from Task Scheduler or a scheduled task
powershell -ExecutionPolicy Bypass -File "C:\path\to\your\project\scripts\backend\run_game_tick.ps1"
```
