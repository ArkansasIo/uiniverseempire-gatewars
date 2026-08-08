# Universe Civilization: Empire at Wars

Universe Civilization: Empire at Wars is an open-source PHP/MySQL browser strategy game with a universe civilization : empire at war  scifi pushtogithub

-inspired command experience, deep empire management, research and technology trees, and a custom theme system.

## Project Guide

This repository now includes a full folder-level documentation layout for the game, the backend, and the UI layers.

- Start here: [docs/README.md](docs/README.md)
- Full layout map: [docs/PROJECT_STRUCTURE.md](docs/PROJECT_STRUCTURE.md)
- Backend setup: [BACKEND_SETUP.md](BACKEND_SETUP.md)
- Hostinger deployment: [docs/HOSTINGER_DEPLOYMENT.md](docs/HOSTINGER_DEPLOYMENT.md)
- Patch history: [PATCHLOG.md](PATCHLOG.md)
- Legacy readme retained: [README](README)

## Current Experience

- Universe Civilization: Empire at Wars branding across the public landing page and main game shell
- Theme picker with White, OG, Blue, and Stargate themes
- Research and technology tree pages with richer navigation and styling
- Compatibility bootstrap files for local development and legacy config imports

## Quick Start

1. Initialize database:

```bash
mysql -u root -p < database/sql/01_create_database.sql
mysql -u sgw -psgwpass sgw < game.sql
./scripts/backend/db_migrate.sh
```

2. Validate backend:

```bash
./scripts/backend/healthcheck.sh
```

3. Run locally:

```bash
/usr/bin/php -S 0.0.0.0:8080
```

4. Open:

- http://127.0.0.1:8080/index.php

## Local Test Account

For local development and testing, the default demo account is:

- Username: copilotpilot
- Password: SGWLogin123!

Use these credentials when logging in to the local game instance.

## Notes

The local server is intended to run from the repository root with the PHP built-in server. For a full experience, make sure the database is initialized and the backend healthcheck passes before launching the game.
