#!/usr/bin/env bash
# MIT License
#
# Copyright (c) 2026 Stargate Wars contributors
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

DB_NAME="${SGW_DB_NAME:-sgw}"
DB_USER="${SGW_DB_USER:-sgw}"
DB_PASS="${SGW_DB_PASS:-sgwpass}"

for sql_file in database/sql/03_backend_tables.sql database/sql/04_reporting_views.sql database/sql/05_seed_backend_defaults.sql database/sql/08_admin_panel.sql database/sql/09_user_prefs.sql; do
  echo "[db_migrate] Applying $sql_file"
  mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$sql_file"
done

echo "[db_migrate] Migration batch complete."
