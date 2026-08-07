#!/usr/bin/env bash
# MIT License
#
# Copyright (c) 2026 Universe Civilization : Empire at wars
#
# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:
#
# The above copyright notice and this permission notice shall be included in all
# copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# SOFTWARE.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

MYSQL_ADMIN_USER="${MYSQL_ADMIN_USER:-root}"
MYSQL_ADMIN_PASS="${MYSQL_ADMIN_PASS:-}"

MYSQL_ADMIN_ARGS=("-u" "$MYSQL_ADMIN_USER")
if [[ -n "$MYSQL_ADMIN_PASS" ]]; then
  MYSQL_ADMIN_ARGS+=("-p$MYSQL_ADMIN_PASS")
fi

echo "[db_init] Creating database and grants..."
mysql "${MYSQL_ADMIN_ARGS[@]}" < database/sql/01_create_database.sql

echo "[db_init] Importing core schema..."
mysql -u "${SGW_DB_USER:-sgw}" -p"${SGW_DB_PASS:-sgwpass}" "${SGW_DB_NAME:-sgw}" < game.sql

echo "[db_init] Applying backend tables..."
mysql -u "${SGW_DB_USER:-sgw}" -p"${SGW_DB_PASS:-sgwpass}" "${SGW_DB_NAME:-sgw}" < database/sql/03_backend_tables.sql

echo "[db_init] Applying reporting views..."
mysql -u "${SGW_DB_USER:-sgw}" -p"${SGW_DB_PASS:-sgwpass}" "${SGW_DB_NAME:-sgw}" < database/sql/04_reporting_views.sql

echo "[db_init] Seeding backend defaults..."
mysql -u "${SGW_DB_USER:-sgw}" -p"${SGW_DB_PASS:-sgwpass}" "${SGW_DB_NAME:-sgw}" < database/sql/05_seed_backend_defaults.sql

echo "[db_init] Done."
