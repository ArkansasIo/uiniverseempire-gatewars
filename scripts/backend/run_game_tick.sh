#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

mkdir -p "$PROJECT_ROOT/exports"

if command -v php >/dev/null 2>&1; then
  php "$PROJECT_ROOT/scripts/backend/game_tick.php" >> "$PROJECT_ROOT/exports/game_tick.log" 2>&1
else
  echo "php not found in PATH" >&2
  exit 127
fi
