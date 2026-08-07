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
require_once __DIR__ . '/../config.php';

// --- roleLabel(): access-level labels.
$expectations = [
    0   => 'Player',
    1   => 'Player',
    2   => 'Moderator',
    3   => 'Moderator',
    4   => 'Admin',
    5   => 'Admin',
    7   => 'Admin',
    8   => 'Super Admin',
    255 => 'Super Admin',
];
foreach ($expectations as $level => $label) {
    $actual = Admin::roleLabel($level);
    if ($actual !== $label) {
        fwrite(STDERR, "admin_panel_test failed: roleLabel({$level}) = {$actual}, expected {$label}\n");
        exit(1);
    }
}

// --- accessOptions(): selectable levels present.
$options = Admin::accessOptions();
foreach ([1, 2, 4, 8, 255] as $level) {
    if (!isset($options[$level])) {
        fwrite(STDERR, "admin_panel_test failed: accessOptions() missing level {$level}\n");
        exit(1);
    }
}

// --- clean(): HTML escaping helper.
if (Admin::clean('<script>alert(1)</script>') !== '&lt;script&gt;alert(1)&lt;/script&gt;') {
    fwrite(STDERR, "admin_panel_test failed: clean() did not escape HTML\n");
    exit(1);
}

// --- Admin instance via the demo login (no database): graceful no-DB operation.
$admin = new Admin('copilotpilot', 'SGWLogin123!');
if (!$admin->loggedIn || $admin->userid !== 1) {
    fwrite(STDERR, "admin_panel_test failed: admin demo login did not initialize\n");
    exit(1);
}

// --- isAdmin(): bitmask + threshold semantics.
$admin->loggedIn = true;
$admin->access = 1;
if ($admin->isAdmin()) {
    fwrite(STDERR, "admin_panel_test failed: access 1 must not be admin\n");
    exit(1);
}
$admin->access = 4;
if (!$admin->isAdmin()) {
    fwrite(STDERR, "admin_panel_test failed: access 4 must be admin\n");
    exit(1);
}
$admin->access = 255;
if (!$admin->isAdmin()) {
    fwrite(STDERR, "admin_panel_test failed: access 255 must be admin\n");
    exit(1);
}
$admin->loggedIn = false;
$admin->access = 255;
if ($admin->isAdmin()) {
    fwrite(STDERR, "admin_panel_test failed: logged-out account must not be admin\n");
    exit(1);
}

// --- No-DB aggregators must return safe empty structures.
if (!is_array($admin->dashboardStats()) || !is_array($admin->players('search', 1, 25))) {
    fwrite(STDERR, "admin_panel_test failed: no-DB aggregators must return arrays\n");
    exit(1);
}

// --- Panel asset wiring checks.
$indexTpl = file_get_contents(__DIR__ . '/../templates/index.tpl');
if ($indexTpl === false || strpos($indexTpl, '{ADMIN_MENU}') === false) {
    fwrite(STDERR, "admin_panel_test failed: templates/index.tpl must expose {ADMIN_MENU}\n");
    exit(1);
}

$config = file_get_contents(__DIR__ . '/../config.php');
if ($config === false || strpos($config, 'Admin.class.php') === false) {
    fwrite(STDERR, "admin_panel_test failed: config.php must load Admin.class.php\n");
    exit(1);
}

$panel = file_get_contents(__DIR__ . '/../admin/index.php');
if ($panel === false || strpos($panel, 'Admin Control Panel') === false) {
    fwrite(STDERR, "admin_panel_test failed: admin/index.php must exist and brand the panel\n");
    exit(1);
}

echo "admin panel checks passed\n";
