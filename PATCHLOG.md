# Patch Log

> Chronological patch history for **Universe Civilization: Empire at Wars**.
> Newest first. In-game players see the public-facing version in the Update
> Log (`indexpages/updates.php`); the FAQ lives in `modules/faq.php`.

## 1.5.0-d (Unreleased) — Fast Cadence

A pace layer on top of the 30-minute economy: quick action turns and
per-minute production, live in the browser.

- **Fast cadence** (`game_tick.fast_*` settings, defaults enabled): +6 action
  turns per 10-second tick, and once per minute a grant of n% (default 100%) of
  the formal per-minute OGame production rate for metal, crystal, deuterium,
  food, water, population and energy.
- New `GameTick` statics `computeElapsedIntervals()`,
  `computeFastTurnGrant()`, `computeFastResourceGrant()` with unit-test
  coverage; per-player progress tracked in the new `fast_tick_state` table.
- **On-page-load hook**: logged-in players advance their fast cadence on every
  in-game page load, so progress accrues live without waiting for cron;
  catches up at most `FAST_MAX_CATCHUP` intervals per call.
- `game_tick.php --systems=fast` supports a 10-second cron job;
  `cron_jobs.md` documents both schedules.

## 1.5.0-c (Unreleased) — Unified Game Tick

Backend stability and a single engine for every time-based system.

- **Unified tick engine** (`base/GameTick.class.php`): one `run()` advances turn
  economy, strategic resources, hyperspace transits, fleet missions, trade
  routes, military training queues, RTS operations, colony power grid, market
  sweep and inactive-account purge. Configurable systems, `--dry-run`,
  `--uid=` and `--no-rank` flags.
- `scripts/backend/game_tick.php` and `scripts/backend/turn_tick.php` now
  delegate to the unified engine; `cron_jobs.md` documents the 5-minute cadence.
- **Fixed** trade routes being applied twice per tick (routes between two
  players are processed once globally instead of once per player).
- **Fixed** `GameTick` method visibility clash with `Game::processTradeRoutes()`.
- **Fixed** `config.php` bootstrap: restored session init, `SGW_VERSION 1.5.0`,
  and the `GameTick`/`Admin` base-class includes (local MariaDB wiring).
- Extracted pure statics `GameTick::resourceRates()`,
  `GameTick::stargateCoefficients()`, `GameTick::militaryRecruitCosts()` and
  `GameTick::computeTradeTransfer()` with unit-test coverage.

## 1.5.0-b — 2026-08-07

- SGW theme updates across modules; copyright renamed to Universe Civilization:
  Empire at Wars; `modules/pages.php` suite expansion.
- Artillery systems: 180-piece offense/defense catalog, quests, military and
  planet view modules; sub-page suites refactored into standalone modules.
- Colony grid systems: field buildings, power grid and AI factory.
- Modular master/sub CSS.
- Alliance, sabotage, ascension, spy, trade-route and colony systems.

## 1.5.0-a — 2026-08-06

- Admin control panel system with staff operations layer.
- In-game settings, staff login, turn-tick engine and admin tools.
- Missing detail pass across page suites and sub-pages.
- Guarded legacy `Bursted()` stub call in `process.php`.
- Bounded DB connect timeout so unreachable hosts fail fast.
- Runtime fatal/warning fixes; removed dev probe files.
- Remember-me login, MIT headers, legacy `mysql_*` call and warning fixes.

## 1.5.0 — 2026-08-05

- OGame-style research/tech system with extracted pure logic module.
- Hardened XMLHttpRequest layer and userlist server program.
- v1.5 CSS component layer polish across all four themes (White, OG, Blue,
  Stargate).
- v1.5.0 documentation set and game-logic unit tests.
- GitHub source button on the front page; armory sell-link fix.
- Player banking and resource initialization.
- Initial import.
