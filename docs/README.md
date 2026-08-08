# Documentation Index

**Version 1.5.0** — Universe Civilization: Empire at Wars

This folder organizes technical documents for the Universe Civilization: Empire at Wars codebase.

## Design Documents

- [ARCHITECTURE.md](ARCHITECTURE.md): system overview, runtime layers, request flow, component map.
- [DATABASE.md](DATABASE.md): schema, entity-relationship view, table reference, migration strategy.
- [UML_DIAGRAMS.md](UML_DIAGRAMS.md): Mermaid class + sequence diagrams for backend and frontend.
- [GAME_MECHANICS.md](GAME_MECHANICS.md): turns, resources, units, combat, tech, fleets, economy rules.
- [FRONTEND_THEME.md](FRONTEND_THEME.md): page shell, layout classes, 4-theme system, client JS modules.
- [CRONJOBS.md](CRONJOBS.md): `game_tick.php`, wrappers, legacy tickers, ops scripts, testing notes.
- [MODULES_API.md](MODULES_API.md): `sendData()` contract, `pages.php` suites/sub-pages, module inventory.
- [FUNCTION_REFERENCE.md](FUNCTION_REFERENCE.md): deep-dive signatures + logic for `Chive`/`User`/`Game`, cron processor, and client JS functions.

## Contributor Guides

- [CONTRIBUTING_STYLE.md](CONTRIBUTING_STYLE.md): how to write source — entry patterns, conventions, tests, git flow.

## Core Documents

- [PROJECT_STRUCTURE.md](PROJECT_STRUCTURE.md): repository layout and folder responsibilities.
- [DEVELOPMENT_WORKFLOW.md](DEVELOPMENT_WORKFLOW.md): coding, validation, and release flow.

## Operational Docs Outside docs/

- [../BACKEND_SETUP.md](../BACKEND_SETUP.md): backend environment setup and scripts.
- [../database/README.md](../database/README.md): database import and SQL bundle notes.
- [../scripts/backend/cron_jobs.md](../scripts/backend/cron_jobs.md): scheduled tasks.

## Release Documents

- [../PATCHLOG.md](../PATCHLOG.md): chronological patch history for every build.
- [../indexpages/updates.php](../indexpages/updates.php): public in-game Update Log.
- [../modules/faq.php](../modules/faq.php): in-game FAQ module.
