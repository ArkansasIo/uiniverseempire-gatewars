# Cron Jobs

## Game Tick

Run every 5 minutes to process:
- resource economy ticks (30-minute cadence)
- food/water/energy upkeep and population penalties
- hyperspace transit arrivals and returns
- expedition rewards

Command:

```bash
php /home/codespace/Stargate-Wars/scripts/backend/game_tick.php
```

Dry-run test:

```bash
php /home/codespace/Stargate-Wars/scripts/backend/game_tick.php --dry-run
```

Single player test:

```bash
php /home/codespace/Stargate-Wars/scripts/backend/game_tick.php --uid=1
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
