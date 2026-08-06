<?php
/*
 * MIT License
 *
 * Copyright (c) 2026 Stargate Wars contributors
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
// Basic backend healthcheck: DB connection, core tables, backend tables, reporting views.

require_once dirname(__DIR__, 2) . '/config.php';

if (!extension_loaded('mysqli') || !class_exists('mysqli')) {
    fwrite(STDERR, "Healthcheck error: PHP mysqli extension is not available for this runtime." . PHP_EOL);
    fwrite(STDERR, "Install/enable mysqli for CLI or run with a PHP binary that includes mysqli." . PHP_EOL);
    exit(3);
}

if (!isset($conf) || !is_array($conf)) {
    fwrite(STDERR, "Healthcheck error: missing or invalid database configuration." . PHP_EOL);
    exit(3);
}

$requiredKeys = ['db_server', 'db_username', 'db_password', 'db_name'];
foreach ($requiredKeys as $key) {
    if (!array_key_exists($key, $conf)) {
        fwrite(STDERR, "Healthcheck error: missing config key '" . $key . "'." . PHP_EOL);
        exit(3);
    }
}

$mysqli = mysqli_init();
if (!$mysqli) {
    fwrite(STDERR, "Healthcheck error: unable to initialize mysqli." . PHP_EOL);
    exit(3);
}
mysqli_options($mysqli, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
if (!@mysqli_real_connect($mysqli, $conf['db_server'], $conf['db_username'], $conf['db_password'], $conf['db_name'])) {
    fwrite(STDERR, "DB connection failed: " . mysqli_connect_error() . PHP_EOL);
    exit(1);
}

$checks = [
    'tables' => ['users', 'userdata', 'bank', 'units', 'technology', 'power', 'app_settings', 'app_migrations', 'app_audit_log', 'app_server_jobs'],
    'views' => ['vw_player_core', 'vw_player_economy', 'vw_player_military'],
];

$allGood = true;

foreach ($checks['tables'] as $table) {
    $res = $mysqli->query("SHOW TABLES LIKE '" . $mysqli->real_escape_string($table) . "'");
    $exists = ($res && $res->num_rows > 0);
    echo sprintf("[table] %-25s %s\n", $table, $exists ? 'OK' : 'MISSING');
    if (!$exists) {
        $allGood = false;
    }
    if ($res) {
        $res->free();
    }
}

foreach ($checks['views'] as $view) {
    $res = $mysqli->query("SHOW FULL TABLES WHERE Table_type='VIEW' AND Tables_in_" . $conf['db_name'] . "='" . $mysqli->real_escape_string($view) . "'");
    $exists = ($res && $res->num_rows > 0);
    echo sprintf("[view ] %-25s %s\n", $view, $exists ? 'OK' : 'MISSING');
    if (!$exists) {
        $allGood = false;
    }
    if ($res) {
        $res->free();
    }
}

$mysqli->close();

if (!$allGood) {
    exit(2);
}

echo "Backend healthcheck passed." . PHP_EOL;
