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
// Structural checks for the in-game Settings module, the game shell wiring
// (announcement banner + maintenance gate) and the dedicated staff login.

$root = dirname(__DIR__);

// --- modules/settings.php: CSRF + expected form fields.
$settings = file_get_contents($root . '/modules/settings.php');
if ($settings === false) {
    fwrite(STDERR, "settings/shell test failed: modules/settings.php missing\n");
    exit(1);
}
foreach (['user_csrf', 'name="email"', 'name="newpass"', 'name="confirmpass"', 'name="theme"', 'notify_attack', 'notify_message', 'notify_market'] as $needle) {
    if (strpos($settings, $needle) === false) {
        fwrite(STDERR, "settings/shell test failed: settings module missing '$needle'\n");
        exit(1);
    }
}

// --- templates/index.tpl: announcement placeholder + settings nav link.
$indexTpl = file_get_contents($root . '/templates/index.tpl');
if ($indexTpl === false) {
    fwrite(STDERR, "settings/shell test failed: templates/index.tpl missing\n");
    exit(1);
}
if (strpos($indexTpl, '{ANNOUNCEMENT_BANNER}') === false) {
    fwrite(STDERR, "settings/shell test failed: index.tpl missing announcement placeholder\n");
    exit(1);
}
if (strpos($indexTpl, "sendData('settings','get','mainDisplay')") === false) {
    fwrite(STDERR, "settings/shell test failed: index.tpl missing settings nav link\n");
    exit(1);
}

// --- index.php: banner substitution + maintenance gate present.
$indexPhp = file_get_contents($root . '/index.php');
if ($indexPhp === false) {
    fwrite(STDERR, "settings/shell test failed: index.php missing\n");
    exit(1);
}
foreach (["\$subs['{ANNOUNCEMENT_BANNER}']", 'maintenance.enabled', 'maintenance.message', 'announcement.active', 'announcement.title', 'announcement.body', 'HTTP/1.1 503 Service Unavailable'] as $needle) {
    if (strpos($indexPhp, $needle) === false) {
        fwrite(STDERR, "settings/shell test failed: index.php missing '$needle'\n");
        exit(1);
    }
}

// --- main.css: banner styling present.
$mainCss = file_get_contents($root . '/main.css');
if ($mainCss === false || strpos($mainCss, '.announcement-banner') === false) {
    fwrite(STDERR, "settings/shell test failed: main.css missing .announcement-banner\n");
    exit(1);
}

// --- admin/login.php: standalone staff login with CSRF + access gate.
$loginPhp = file_get_contents($root . '/admin/login.php');
if ($loginPhp === false) {
    fwrite(STDERR, "settings/shell test failed: admin/login.php missing\n");
    exit(1);
}
foreach (['admin_login_csrf', 'name="csrf"', 'name="user"', 'name="pass"', 'isAdmin()'] as $needle) {
    if (strpos($loginPhp, $needle) === false) {
        fwrite(STDERR, "settings/shell test failed: admin/login.php missing '$needle'\n");
        exit(1);
    }
}

// --- admin/index.php: new views + actions wired.
$adminIndex = file_get_contents($root . '/admin/index.php');
if ($adminIndex === false) {
    fwrite(STDERR, "settings/shell test failed: admin/index.php missing\n");
    exit(1);
}
foreach (['player_reset', 'player_delete', 'announcement_publish', 'announcement_clear', 'maintenance_set', 'mass_grant', 'tick_run', "'tick'", "'mass'", "'announcements'", "'maintenance'"] as $needle) {
    if (strpos($adminIndex, $needle) === false) {
        fwrite(STDERR, "settings/shell test failed: admin/index.php missing '$needle'\n");
        exit(1);
    }
}

// --- base/Admin.class.php: new operations present.
$adminClass = file_get_contents($root . '/base/Admin.class.php');
if ($adminClass === false) {
    fwrite(STDERR, "settings/shell test failed: base/Admin.class.php missing\n");
    exit(1);
}
foreach (['function runGameTick', 'function tickStatus', 'function resetPlayer', 'function deletePlayer', 'function publishAnnouncement', 'function clearAnnouncement', 'function announcementStatus', 'function maintenanceStatus', 'function setMaintenance', 'function massGrant', 'function allPlayerUids'] as $needle) {
    if (strpos($adminClass, $needle) === false) {
        fwrite(STDERR, "settings/shell test failed: Admin.class.php missing '$needle'\n");
        exit(1);
    }
}

// --- Migration 09 exists and is referenced by db_migrate.sh.
if (!is_file($root . '/database/sql/09_user_prefs.sql')) {
    fwrite(STDERR, "settings/shell test failed: database/sql/09_user_prefs.sql missing\n");
    exit(1);
}
$migrate = file_get_contents($root . '/scripts/backend/db_migrate.sh');
if ($migrate === false || strpos($migrate, '09_user_prefs.sql') === false) {
    fwrite(STDERR, "settings/shell test failed: db_migrate.sh missing 09_user_prefs.sql\n");
    exit(1);
}

// --- Fresh-start package: addUser and resetPlayer share the same values.
$userClass = file_get_contents($root . '/base/User.class.php');
if ($userClass === false) {
    fwrite(STDERR, "settings/shell test failed: base/User.class.php missing\n");
    exit(1);
}
foreach (['VALUES (?, 0, 350000)', 'VALUES (?, 11200, 19000, 16000'] as $needle) {
    if (strpos($userClass, $needle) === false) {
        fwrite(STDERR, "settings/shell test failed: addUser missing fresh-start '$needle'\n");
        exit(1);
    }
}
if (strpos($adminClass, 'VALUES (?, 0, 350000)') === false || strpos($adminClass, '11200, 19000, 16000') === false) {
    fwrite(STDERR, "settings/shell test failed: resetPlayer missing fresh-start package\n");
    exit(1);
}

// --- Version info: SGW_VERSION constant + visible in game shell and admin panel.
$configPhp = file_get_contents($root . '/config.php');
if ($configPhp === false || strpos($configPhp, 'SGW_VERSION') === false || strpos($configPhp, '"1.5.0"') === false) {
    fwrite(STDERR, "settings/shell test failed: config.php missing SGW_VERSION 1.5.0\n");
    exit(1);
}
if (strpos($indexTpl, 'Version') === false) {
    fwrite(STDERR, "settings/shell test failed: index.tpl missing version footer\n");
    exit(1);
}
if (strpos($adminIndex, 'version') === false) {
    fwrite(STDERR, "settings/shell test failed: admin/index.php missing version footer\n");
    exit(1);
}

// --- Version surfaces in serverInfo() for the admin settings screen.
require_once $root . '/config.php';
$adminInfo = new Admin('copilotpilot', 'SGWLogin123!');
$info = $adminInfo->serverInfo();
if (($info['game_version'] ?? '') !== '1.5.0') {
    fwrite(STDERR, "settings/shell test failed: serverInfo game_version\n");
    exit(1);
}

echo "settings/shell checks passed\n";
