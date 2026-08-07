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
// Admin Control Panel - single entry point.
// Guards every view behind an authenticated staff account (users.alevel >= 4).

include_once("../config.php");

// The panel works even if the local config.php predates the Admin class
// wiring; make sure the operations layer is always available.
if (!class_exists('Admin', false)) {
    require_once(__DIR__ . '/../base/Admin.class.php');
}

$admin = new Admin();

if (!$admin->loggedIn) {
    header("Location: ../index.php");
    exit;
}

function adminCsrfToken(): string
{
    if (empty($_SESSION['admin_csrf'])) {
        $_SESSION['admin_csrf'] = bin2hex(random_bytes(16));
    }
    return (string)$_SESSION['admin_csrf'];
}

function adminVerifyCsrf(): bool
{
    if (!isset($_POST['csrf']) || !is_string($_POST['csrf'])) {
        return false;
    }
    return hash_equals((string)($_SESSION['admin_csrf'] ?? ''), $_POST['csrf']);
}

function adminFlash(string $type, string $message): void
{
    $_SESSION['admin_flash'][] = ['type' => $type, 'message' => $message];
}

function adminRenderFlash(): string
{
    $out = '';
    $flashes = $_SESSION['admin_flash'] ?? [];
    unset($_SESSION['admin_flash']);
    foreach ($flashes as $flash) {
        $cls = ($flash['type'] ?? 'ok') === 'err' ? 'admin-err' : 'admin-ok';
        $out .= '<div class="' . $cls . '">' . Admin::clean((string)($flash['message'] ?? '')) . '</div>';
    }
    return $out;
}

// ---- POST handling (mutations) -------------------------------------------------
$redirect = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $admin->isAdmin()) {
    if (!adminVerifyCsrf()) {
        adminFlash('err', 'Invalid security token. Please try again.');
    } else {
        $action = (string)($_POST['action'] ?? '');
        switch ($action) {
            case 'update_account':
                $uid = (int)($_POST['uid'] ?? 0);
                $email = (string)($_POST['email'] ?? '');
                $alevel = (int)($_POST['alevel'] ?? 1);
                $rid = (int)($_POST['rid'] ?? 1);
                $cid = (int)($_POST['cid'] ?? 0);
                $errors = $admin->updatePlayerAccount($uid, $email, $alevel, $rid, $cid);
                if (count($errors)) {
                    adminFlash('err', implode(' ', $errors));
                } else {
                    adminFlash('ok', 'Account updated.');
                }
                $redirect = 'index.php?view=player&uid=' . $uid;
                break;

            case 'grant_naq':
                if ($admin->grantNaq((int)($_POST['uid'] ?? 0), (int)($_POST['amount'] ?? 0))) {
                    adminFlash('ok', 'Naquadah granted.');
                } else {
                    adminFlash('err', 'Could not grant Naquadah.');
                }
                $redirect = 'index.php?view=player&uid=' . (int)($_POST['uid'] ?? 0);
                break;

            case 'grant_turns':
                if ($admin->grantTurns((int)($_POST['uid'] ?? 0), (int)($_POST['amount'] ?? 0))) {
                    adminFlash('ok', 'Action turns granted.');
                } else {
                    adminFlash('err', 'Could not grant turns.');
                }
                $redirect = 'index.php?view=player&uid=' . (int)($_POST['uid'] ?? 0);
                break;

            case 'grant_units':
                if ($admin->grantUntrained((int)($_POST['uid'] ?? 0), (int)($_POST['amount'] ?? 0))) {
                    adminFlash('ok', 'Untrained units granted.');
                } else {
                    adminFlash('err', 'Could not grant units.');
                }
                $redirect = 'index.php?view=player&uid=' . (int)($_POST['uid'] ?? 0);
                break;

            case 'set_resources':
                $uid = (int)($_POST['uid'] ?? 0);
                $res = [
                    'metal' => (int)($_POST['metal'] ?? 0),
                    'crystal' => (int)($_POST['crystal'] ?? 0),
                    'deuterium' => (int)($_POST['deuterium'] ?? 0),
                    'food' => (int)($_POST['food'] ?? 0),
                    'water' => (int)($_POST['water'] ?? 0),
                    'population' => (int)($_POST['population'] ?? 0),
                    'energy' => (int)($_POST['energy'] ?? 0),
                ];
                if ($admin->setPlayerResources($uid, $res)) {
                    adminFlash('ok', 'Resources updated.');
                } else {
                    adminFlash('err', 'Could not update resources.');
                }
                $redirect = 'index.php?view=player&uid=' . $uid;
                break;

            case 'set_access':
                if ($admin->setAccessLevel((int)($_POST['uid'] ?? 0), (int)($_POST['alevel'] ?? 1))) {
                    adminFlash('ok', 'Access level updated.');
                } else {
                    adminFlash('err', 'Could not update access level.');
                }
                $redirect = 'index.php?view=player&uid=' . (int)($_POST['uid'] ?? 0);
                break;

            case 'ban':
                if ($admin->setBanned((int)($_POST['uid'] ?? 0), true)) {
                    adminFlash('ok', 'Player banned.');
                } else {
                    adminFlash('err', 'Could not ban player (you cannot ban yourself).');
                }
                $redirect = 'index.php?view=player&uid=' . (int)($_POST['uid'] ?? 0);
                break;

            case 'unban':
                if ($admin->setBanned((int)($_POST['uid'] ?? 0), false)) {
                    adminFlash('ok', 'Player unbanned.');
                } else {
                    adminFlash('err', 'Could not unban player.');
                }
                $redirect = 'index.php?view=player&uid=' . (int)($_POST['uid'] ?? 0);
                break;

            case 'reset_password':
                if ($admin->resetPassword((int)($_POST['uid'] ?? 0), (string)($_POST['newpass'] ?? ''))) {
                    adminFlash('ok', 'Password reset.');
                } else {
                    adminFlash('err', 'Password reset failed (min 6 characters).');
                }
                $redirect = 'index.php?view=player&uid=' . (int)($_POST['uid'] ?? 0);
                break;

            case 'broadcast':
                if ($admin->broadcastMessage((string)($_POST['subject'] ?? ''), (string)($_POST['body'] ?? ''))) {
                    adminFlash('ok', 'Broadcast sent to all players.');
                } else {
                    adminFlash('err', 'Broadcast failed: subject and body are required.');
                }
                $redirect = 'index.php?view=messages';
                break;

            case 'cancel_listing':
                if ($admin->cancelListing((int)($_POST['lid'] ?? 0))) {
                    adminFlash('ok', 'Listing cancelled and seller refunded.');
                } else {
                    adminFlash('err', 'Could not cancel listing.');
                }
                $redirect = 'index.php?view=market';
                break;

            case 'save_settings':
                $key = (string)($_POST['setting_key'] ?? '');
                $value = (string)($_POST['setting_value'] ?? '');
                if ($admin->setSetting($key, $value)) {
                    adminFlash('ok', 'Setting saved.');
                } else {
                    adminFlash('err', 'Could not save setting.');
                }
                $redirect = 'index.php?view=settings';
                break;

            case 'player_reset':
                $uid = (int)($_POST['uid'] ?? 0);
                $errors = $admin->resetPlayer($uid);
                if (count($errors)) {
                    adminFlash('err', implode(' ', $errors));
                } else {
                    adminFlash('ok', 'Player reset to a fresh state.');
                }
                $redirect = 'index.php?view=player&uid=' . $uid;
                break;

            case 'player_delete':
                $uid = (int)($_POST['uid'] ?? 0);
                $errors = $admin->deletePlayer($uid);
                if (count($errors)) {
                    adminFlash('err', implode(' ', $errors));
                    $redirect = 'index.php?view=player&uid=' . $uid;
                } else {
                    adminFlash('ok', 'Player account deleted.');
                    $redirect = 'index.php?view=players';
                }
                break;

            case 'announcement_publish':
                if ($admin->publishAnnouncement((string)($_POST['title'] ?? ''), (string)($_POST['body'] ?? ''))) {
                    adminFlash('ok', 'Announcement published.');
                } else {
                    adminFlash('err', 'Announcement requires a title or a body.');
                }
                $redirect = 'index.php?view=announcements';
                break;

            case 'announcement_clear':
                if ($admin->clearAnnouncement()) {
                    adminFlash('ok', 'Announcement hidden.');
                } else {
                    adminFlash('err', 'Could not hide announcement.');
                }
                $redirect = 'index.php?view=announcements';
                break;

            case 'maintenance_set':
                $enabled = isset($_POST['enabled']) && (string)$_POST['enabled'] === '1';
                if ($admin->setMaintenance($enabled, (string)($_POST['message'] ?? ''))) {
                    adminFlash('ok', 'Maintenance mode ' . ($enabled ? 'enabled.' : 'disabled.'));
                } else {
                    adminFlash('err', 'Could not update maintenance mode.');
                }
                $redirect = 'index.php?view=maintenance';
                break;

            case 'mass_grant':
                $kind = (string)($_POST['kind'] ?? 'naq');
                $amount = (int)($_POST['amount'] ?? 0);
                $uids = [];
                if (isset($_POST['all_players']) && (string)$_POST['all_players'] === '1') {
                    $uids = $admin->allPlayerUids();
                } else {
                    foreach (preg_split('/[\s,]+/', (string)($_POST['uids'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $part) {
                        $uids[] = (int)$part;
                    }
                }
                $uids = array_values(array_unique(array_filter($uids, static fn ($v) => $v > 0)));
                if (count($uids) === 0) {
                    adminFlash('err', 'No valid player ids provided.');
                } else {
                    $result = $admin->massGrant($kind, $uids, $amount);
                    adminFlash('ok', 'Grant applied to ' . $result['ok'] . ' player(s), ' . $result['failed'] . ' failed.');
                }
                $redirect = 'index.php?view=mass';
                break;

            case 'tick_run':
                $result = $admin->runGameTick(['dry_run' => isset($_POST['dry_run']) && (string)$_POST['dry_run'] === '1']);
                if ($result['ok']) {
                    $intents = $result['intents'];
                    adminFlash('ok', $result['message'] . ' Processed: ' . $result['processed']
                        . ' | Income: ' . number_format($intents['income_total'])
                        . ' | Upkeep: ' . number_format($intents['upkeep_total'])
                        . ' | Turns: ' . number_format($intents['turns_granted'])
                        . ' | Untrained: ' . number_format($intents['untrained_granted']));
                } else {
                    adminFlash('err', $result['message']);
                }
                $redirect = 'index.php?view=tick';
                break;

            default:
                adminFlash('err', 'Unknown action.');
                $redirect = 'index.php';
                break;
        }
    }
}

if ($redirect !== null) {
    header("Location: " . $redirect);
    exit;
}

// ---- Non-admin staff guard -------------------------------------------------------
if (!$admin->isAdmin()) {
    ?><!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Access Denied - Admin Control Panel</title>
        <link rel="stylesheet" href="../main.css">
    </head>
    <body>
    <div style="max-width:520px;margin:80px auto;padding:24px;background:#222;border:1px solid #555;color:#eee;font-family:sans-serif;">
        <h1>403 - Access Denied</h1>
        <p>Your account does not have staff privileges. Log in with an account whose access level is Admin (4) or higher.</p>
        <p><a href="../index.php">Back to the game</a></p>
    </div>
    </body>
    </html><?php
    exit;
}

// ---- View routing ----------------------------------------------------------------
$view = (string)($_GET['view'] ?? 'dashboard');
$views = ['dashboard', 'players', 'player', 'messages', 'logs', 'market', 'adminlog', 'settings', 'tick', 'announcements', 'maintenance', 'mass'];
if (!in_array($view, $views, true)) {
    $view = 'dashboard';
}

$csrf = adminCsrfToken();

// ---- Content renderers ------------------------------------------------------------
function adminShellStart(string $view, string $csrf, string $title = 'Admin Control Panel'): void
{
    $navItems = [
        'dashboard'     => ['Dashboard', 'index.php'],
        'players'       => ['Players', 'index.php?view=players'],
        'messages'      => ['Broadcast', 'index.php?view=messages'],
        'tick'          => ['Game Tick', 'index.php?view=tick'],
        'mass'          => ['Mass Grants', 'index.php?view=mass'],
        'announcements' => ['Announcements', 'index.php?view=announcements'],
        'maintenance'   => ['Maintenance', 'index.php?view=maintenance'],
        'logs'          => ['Action Logs', 'index.php?view=logs'],
        'market'        => ['Market', 'index.php?view=market'],
        'adminlog'      => ['Staff Log', 'index.php?view=adminlog'],
        'settings'      => ['Settings', 'index.php?view=settings'],
    ];
    $nav = '';
    foreach ($navItems as $key => $item) {
        $active = $key === $view ? ' class="active"' : '';
        $nav .= '<a href="' . Admin::clean($item[1]) . '"' . $active . '>' . Admin::clean($item[0]) . '</a>';
    }
    echo '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>' . Admin::clean($title) . '</title>
<style>
* { box-sizing: border-box; }
body.admin-panel { margin:0; background:#0d1220; color:#dbe4f0; font-family:"Segoe UI",Roboto,Arial,sans-serif; }
a { color:#7db6ff; text-decoration:none; }
a:hover { text-decoration:underline; }
.admin-topbar { display:flex; justify-content:space-between; align-items:center; padding:10px 18px; background:#151d31; border-bottom:1px solid #2a3550; }
.admin-brand { font-size:18px; font-weight:700; letter-spacing:.4px; }
.admin-brand span { color:#6f8bb5; font-weight:400; font-size:12px; margin-left:8px; }
.admin-actions { font-size:13px; }
.admin-actions a { margin-left:14px; }
.admin-layout { display:flex; min-height:calc(100vh - 108px); }
.admin-nav { width:190px; flex:0 0 190px; padding:16px 10px; background:#111827; border-right:1px solid #2a3550; }
.admin-nav a { display:block; padding:9px 12px; margin:2px 0; border-radius:6px; color:#aebcd6; font-size:14px; }
.admin-nav a.active, .admin-nav a:hover { background:#1e2c47; color:#fff; }
.admin-content { flex:1 1 auto; padding:20px 26px; overflow-x:auto; }
.admin-content h2 { margin-top:0; color:#fff; }
.admin-card { background:#141c2e; border:1px solid #2a3550; border-radius:8px; padding:16px; margin-bottom:16px; }
.admin-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; }
.admin-stat { background:#151f36; border:1px solid #2a3550; border-radius:8px; padding:14px; }
.admin-stat b { display:block; font-size:22px; color:#fff; }
.admin-stat span { font-size:12px; color:#8aa0c4; text-transform:uppercase; letter-spacing:.5px; }
table.admin-tbl { width:100%; border-collapse:collapse; font-size:13px; }
table.admin-tbl th, table.admin-tbl td { border:1px solid #2a3550; padding:6px 9px; text-align:left; }
table.admin-tbl th { background:#1a2340; color:#c3d0ea; }
table.admin-tbl tr:nth-child(even) td { background:#121a2c; }
.admin-ok { background:#10351c; border:1px solid #1f5c33; color:#8df3b0; padding:9px 12px; border-radius:6px; margin-bottom:12px; }
.admin-err { background:#3a1420; border:1px solid #6b2438; color:#ffb3c1; padding:9px 12px; border-radius:6px; margin-bottom:12px; }
.admin-form label { display:inline-block; min-width:170px; color:#aebcd6; font-size:13px; }
.admin-form input[type=text], .admin-form input[type=number], .admin-form input[type=email], .admin-form input[type=password], .admin-form select, .admin-form textarea {
  background:#0e1526; color:#e6eefc; border:1px solid #33415f; border-radius:5px; padding:7px 9px; margin:4px 0; font-size:13px; width:260px; max-width:100%;
}
.admin-form textarea { width:460px; max-width:100%; height:90px; }
.admin-form .row { margin-bottom:10px; }
.admin-btn { display:inline-block; background:#2b4b8f; color:#fff !important; border:none; border-radius:6px; padding:8px 16px; font-size:13px; cursor:pointer; margin-top:6px; }
.admin-btn:hover { background:#3563b8; text-decoration:none; }
.admin-btn.danger { background:#8f2b2b; }
.admin-btn.danger:hover { background:#b83a3a; }
.admin-btn.warn { background:#8f7a2b; }
.admin-btn.warn:hover { background:#b89b3a; }
.admin-pager { margin-top:12px; font-size:13px; color:#8aa0c4; }
.admin-pager a { margin:0 4px; }
.admin-footer { padding:12px 18px; font-size:12px; color:#6f8bb5; border-top:1px solid #2a3550; background:#111827; }
.admin-meta { color:#7f93b8; font-size:12px; }
</style>
</head>
<body class="admin-panel">
<header class="admin-topbar">
  <div class="admin-brand">Admin Control Panel<span>Universe Civilization: Empire at Wars</span></div>
  <div class="admin-actions">
    <span>Logged in as <strong>' . Admin::clean((string)($GLOBALS['admin_user'] ?? '')) . '</strong></span>
    <a href="../index.php">Back to Game</a>
    <a href="../index.php?logout=true">Logout</a>
  </div>
</header>
<div class="admin-layout">
  <nav class="admin-nav">' . $nav . '</nav>
  <main class="admin-content">'
  . adminRenderFlash();
}

function adminShellEnd(): void
{
    echo '</main>
</div>
<footer class="admin-footer">Admin Control Panel &middot; actions are logged to the staff log &middot; version '
    . (defined('SGW_VERSION') ? htmlspecialchars(SGW_VERSION, ENT_QUOTES, 'UTF-8') : '')
    . '</footer>
</body>
</html>';
}

$GLOBALS['admin_user'] = (string)$admin->userName;

adminShellStart($view, $csrf);
?>
<input type="hidden" id="adminCsrf" value="<?= Admin::clean($csrf) ?>">

<?php
switch ($view) {
    case 'dashboard':
        $stats = $admin->dashboardStats();
        ?>
        <h2>Dashboard</h2>
        <div class="admin-grid">
            <div class="admin-stat"><b><?= number_format($stats['totalPlayers']) ?></b><span>Total Players</span></div>
            <div class="admin-stat"><b><?= number_format($stats['activeToday']) ?></b><span>Active Today</span></div>
            <div class="admin-stat"><b><?= number_format($stats['totalNaq']) ?></b><span>Naquadah In Circulation</span></div>
            <div class="admin-stat"><b><?= number_format($stats['totalUnits']) ?></b><span>Total Units</span></div>
            <div class="admin-stat"><b><?= number_format($stats['totalUntrained']) ?></b><span>Untrained Units</span></div>
            <div class="admin-stat"><b><?= number_format($stats['messageCount']) ?></b><span>Messages</span></div>
            <div class="admin-stat"><b><?= number_format($stats['actionCount']) ?></b><span>Action Log Entries</span></div>
            <div class="admin-stat"><b><?= number_format($stats['activeListings']) ?></b><span>Active Market Listings</span></div>
            <div class="admin-stat"><b><?= number_format($stats['adminCount']) ?></b><span>Staff Accounts</span></div>
            <div class="admin-stat"><b><?= number_format($stats['bannedCount']) ?></b><span>Banned Players</span></div>
        </div>

        <div class="admin-card">
            <h3>Recent Registrations</h3>
            <?php $recent = $admin->recentRegistrations(); ?>
            <?php if (count($recent) === 0): ?>
                <p class="admin-meta">No accounts found.</p>
            <?php else: ?>
            <table class="admin-tbl">
                <tr><th>ID</th><th>Username</th><th>Email</th><th>Access</th><th>Last Login</th></tr>
                <?php foreach ($recent as $u): ?>
                <tr>
                    <td><a href="index.php?view=player&uid=<?= (int)$u->uid ?>"><?= (int)$u->uid ?></a></td>
                    <td><?= Admin::clean($u->uname) ?></td>
                    <td><?= Admin::clean($u->email) ?></td>
                    <td><?= Admin::clean(Admin::roleLabel((int)$u->alevel)) ?></td>
                    <td><?= Admin::clean(date('Y-m-d H:i', (int)$u->lastLogin)) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>

        <div class="admin-card">
            <h3>Recent Staff Actions</h3>
            <?php $adminLog = $admin->recentAdminLog(); ?>
            <?php if (count($adminLog) === 0): ?>
                <p class="admin-meta">No staff actions recorded yet.</p>
            <?php else: ?>
            <table class="admin-tbl">
                <tr><th>Time</th><th>Staff</th><th>Action</th><th>Target</th><th>Details</th></tr>
                <?php foreach ($adminLog as $entry): ?>
                <tr>
                    <td><?= Admin::clean(date('Y-m-d H:i', (int)$entry->time)) ?></td>
                    <td><?= Admin::clean($entry->username) ?></td>
                    <td><?= Admin::clean($entry->action) ?></td>
                    <td><?= (int)$entry->target_uid > 0 ? '<a href="index.php?view=player&uid=' . (int)$entry->target_uid . '">' . (int)$entry->target_uid . '</a>' : '&mdash;' ?></td>
                    <td><?= Admin::clean($entry->details) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
        <?php
        break;

    case 'players':
        $search = (string)($_GET['search'] ?? '');
        $page = max(1, (int)($_GET['p'] ?? 1));
        $data = $admin->players($search, $page, 25);
        $totalPages = max(1, (int)ceil($data['total'] / 25));
        ?>
        <h2>Players</h2>
        <div class="admin-card">
            <form method="get" action="index.php" class="admin-form">
                <input type="hidden" name="view" value="players">
                <div class="row">
                    <label>Search name / email:</label>
                    <input type="text" name="search" value="<?= Admin::clean($search) ?>" placeholder="Pilot name or email">
                    <button class="admin-btn" type="submit">Search</button>
                    <?php if ($search !== ''): ?><a class="admin-btn warn" href="index.php?view=players">Clear</a><?php endif; ?>
                </div>
            </form>
        </div>
        <div class="admin-card">
            <p class="admin-meta"><?= number_format($data['total']) ?> player(s) found.</p>
            <?php if (count($data['rows']) === 0): ?>
                <p class="admin-meta">No players match.</p>
            <?php else: ?>
            <table class="admin-tbl">
                <tr>
                    <th>ID</th><th>Username</th><th>Email</th><th>Access</th><th>Banned</th>
                    <th>On Hand</th><th>In Bank</th><th>Turns</th><th>Untrained</th><th>Last Login</th>
                </tr>
                <?php foreach ($data['rows'] as $p): ?>
                <tr>
                    <td><a href="index.php?view=player&uid=<?= (int)$p->uid ?>"><?= (int)$p->uid ?></a></td>
                    <td><?= Admin::clean($p->uname) ?></td>
                    <td><?= Admin::clean($p->email) ?></td>
                    <td><?= Admin::clean(Admin::roleLabel((int)$p->alevel)) ?></td>
                    <td><?= (int)$p->banned === 1 ? 'Yes' : 'No' ?></td>
                    <td><?= number_format((int)$p->onHand) ?></td>
                    <td><?= number_format((int)$p->inBank) ?></td>
                    <td><?= number_format((int)$p->actionTurns) ?></td>
                    <td><?= number_format((int)$p->untrained) ?></td>
                    <td><?= (int)$p->lastLogin > 0 ? Admin::clean(date('Y-m-d H:i', (int)$p->lastLogin)) : 'never' ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
            <?php if ($totalPages > 1): ?>
            <div class="admin-pager">
                Page:
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $page): ?><strong><?= $i ?></strong><?php else: ?>
                    <a href="index.php?view=players&p=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
        break;

    case 'player':
        $uid = (int)($_GET['uid'] ?? 0);
        $p = $admin->getPlayer($uid);
        if (!$p) {
            echo '<h2>Player Not Found</h2><p class="admin-meta">No account exists for uid ' . $uid . '.</p>';
            break;
        }
        $races = $admin->getRaces();
        $accessOptions = Admin::accessOptions();
        $isSelf = $uid === (int)$admin->userid;
        ?>
        <h2>Player: <?= Admin::clean($p->uname) ?> <span class="admin-meta">(uid <?= (int)$p->uid ?>)</span></h2>

        <?php if ((int)$p->banned === 1): ?>
            <div class="admin-err">This account is currently banned.</div>
        <?php endif; ?>

        <div class="admin-grid">
            <div class="admin-stat"><b><?= number_format((int)$p->onHand) ?></b><span>Naquadah On Hand</span></div>
            <div class="admin-stat"><b><?= number_format((int)$p->inBank) ?></b><span>In Bank</span></div>
            <div class="admin-stat"><b><?= number_format((int)$p->actionTurns) ?></b><span>Action Turns</span></div>
            <div class="admin-stat"><b><?= number_format((int)$p->untrained) ?></b><span>Untrained Units</span></div>
            <div class="admin-stat"><b><?= number_format((int)$p->rank) ?></b><span>Rank</span></div>
            <div class="admin-stat"><b><?= Admin::clean($p->race) ?></b><span>Race</span></div>
            <div class="admin-stat"><b><?= Admin::clean(Admin::roleLabel((int)$p->alevel)) ?></b><span>Access Level</span></div>
            <div class="admin-stat"><b><?= (int)$p->banned === 1 ? 'Yes' : 'No' ?></b><span>Banned</span></div>
        </div>

        <div class="admin-card">
            <h3>Account Details</h3>
            <table class="admin-tbl">
                <tr><th>Email</th><td><?= Admin::clean($p->email) ?></td></tr>
                <tr><th>Last Login</th><td><?= (int)$p->lastLogin > 0 ? Admin::clean(date('Y-m-d H:i', (int)$p->lastLogin)) : 'never' ?></td></tr>
                <tr><th>IP</th><td><?= (int)$p->ip > 0 ? Admin::clean(long2ip((int)$p->ip)) : 'n/a' ?></td></tr>
                <tr><th>Alliance</th><td><?= (int)$p->allyid ?></td></tr>
                <tr><th>Commander</th><td><?= (int)$p->cid > 0 ? (int)$p->cid : 'none' ?></td></tr>
            </table>
        </div>

        <div class="admin-card">
            <h3>Edit Account</h3>
            <form method="post" action="index.php" class="admin-form">
                <input type="hidden" name="csrf" value="<?= Admin::clean($csrf) ?>">
                <input type="hidden" name="action" value="update_account">
                <input type="hidden" name="uid" value="<?= (int)$p->uid ?>">
                <div class="row"><label>Email:</label><input type="email" name="email" value="<?= Admin::clean($p->email) ?>"></div>
                <div class="row">
                    <label>Access level:</label>
                    <select name="alevel">
                        <?php foreach ($accessOptions as $level => $label): ?>
                            <option value="<?= $level ?>" <?= (int)$p->alevel === $level ? 'selected' : '' ?>><?= Admin::clean($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row">
                    <label>Race:</label>
                    <select name="rid">
                        <?php foreach ($races as $r): ?>
                            <option value="<?= (int)$r['id'] ?>" <?= (int)$p->rid === (int)$r['id'] ? 'selected' : '' ?>><?= Admin::clean($r['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row"><label>Commander (uid):</label><input type="number" name="cid" min="0" value="<?= (int)$p->cid ?>"></div>
                <button class="admin-btn" type="submit">Save Account</button>
            </form>
        </div>

        <div class="admin-card">
            <h3>Grants</h3>
            <form method="post" action="index.php" class="admin-form" style="display:inline-block;vertical-align:top;">
                <input type="hidden" name="csrf" value="<?= Admin::clean($csrf) ?>">
                <input type="hidden" name="action" value="grant_naq">
                <input type="hidden" name="uid" value="<?= (int)$p->uid ?>">
                <label>Grant Naquadah:</label><input type="number" name="amount" min="0" value="0">
                <button class="admin-btn" type="submit">Grant</button>
            </form>
            <form method="post" action="index.php" class="admin-form" style="display:inline-block;vertical-align:top;">
                <input type="hidden" name="csrf" value="<?= Admin::clean($csrf) ?>">
                <input type="hidden" name="action" value="grant_turns">
                <input type="hidden" name="uid" value="<?= (int)$p->uid ?>">
                <label>Grant Turns:</label><input type="number" name="amount" min="0" value="0">
                <button class="admin-btn" type="submit">Grant</button>
            </form>
            <form method="post" action="index.php" class="admin-form" style="display:inline-block;vertical-align:top;">
                <input type="hidden" name="csrf" value="<?= Admin::clean($csrf) ?>">
                <input type="hidden" name="action" value="grant_units">
                <input type="hidden" name="uid" value="<?= (int)$p->uid ?>">
                <label>Grant Untrained:</label><input type="number" name="amount" min="0" value="0">
                <button class="admin-btn" type="submit">Grant</button>
            </form>
        </div>

        <div class="admin-card">
            <h3>Strategic Resources</h3>
            <form method="post" action="index.php" class="admin-form">
                <input type="hidden" name="csrf" value="<?= Admin::clean($csrf) ?>">
                <input type="hidden" name="action" value="set_resources">
                <input type="hidden" name="uid" value="<?= (int)$p->uid ?>">
                <div class="row"><label>Metal:</label><input type="number" name="metal" min="0" value="<?= (int)$p->metal ?>"></div>
                <div class="row"><label>Crystal:</label><input type="number" name="crystal" min="0" value="<?= (int)$p->crystal ?>"></div>
                <div class="row"><label>Deuterium:</label><input type="number" name="deuterium" min="0" value="<?= (int)$p->deuterium ?>"></div>
                <div class="row"><label>Food:</label><input type="number" name="food" min="0" value="<?= (int)$p->food ?>"></div>
                <div class="row"><label>Water:</label><input type="number" name="water" min="0" value="<?= (int)$p->water ?>"></div>
                <div class="row"><label>Population:</label><input type="number" name="population" min="0" value="<?= (int)$p->population ?>"></div>
                <div class="row"><label>Energy:</label><input type="number" name="energy" min="0" value="<?= (int)$p->energy ?>"></div>
                <button class="admin-btn" type="submit">Set Resources</button>
            </form>
        </div>

        <div class="admin-card">
            <h3>Danger Zone</h3>
            <form method="post" action="index.php" class="admin-form" style="display:inline-block;vertical-align:top;">
                <input type="hidden" name="csrf" value="<?= Admin::clean($csrf) ?>">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="uid" value="<?= (int)$p->uid ?>">
                <label>New password (min 6):</label><input type="password" name="newpass" value="">
                <button class="admin-btn warn" type="submit">Reset Password</button>
            </form>
            <?php if (!$isSelf): ?>
            <form method="post" action="index.php" style="display:inline-block;vertical-align:top;">
                <input type="hidden" name="csrf" value="<?= Admin::clean($csrf) ?>">
                <input type="hidden" name="action" value="<?= (int)$p->banned === 1 ? 'unban' : 'ban' ?>">
                <input type="hidden" name="uid" value="<?= (int)$p->uid ?>">
                <?php if ((int)$p->banned === 1): ?>
                    <button class="admin-btn" type="submit">Unban Player</button>
                <?php else: ?>
                    <button class="admin-btn danger" type="submit" onclick="return confirm('Ban this player? They will be unable to log in.');">Ban Player</button>
                <?php endif; ?>
            </form>
            <form method="post" action="index.php" style="display:inline-block;vertical-align:top;">
                <input type="hidden" name="csrf" value="<?= Admin::clean($csrf) ?>">
                <input type="hidden" name="action" value="player_reset">
                <input type="hidden" name="uid" value="<?= (int)$p->uid ?>">
                <button class="admin-btn warn" type="submit" onclick="return confirm('Reset this player to a fresh state? This wipes all their resources, units, planets, technology and power. The account remains.');">Reset Player</button>
            </form>
            <form method="post" action="index.php" style="display:inline-block;vertical-align:top;">
                <input type="hidden" name="csrf" value="<?= Admin::clean($csrf) ?>">
                <input type="hidden" name="action" value="player_delete">
                <input type="hidden" name="uid" value="<?= (int)$p->uid ?>">
                <button class="admin-btn danger" type="submit" onclick="return confirm('Permanently delete this player and all their data? This cannot be undone.');">Delete Player</button>
            </form>
            <?php else: ?>
            <span class="admin-meta">You cannot ban, reset or delete yourself.</span>
            <?php endif; ?>
        </div>
        <?php
        break;

    case 'tick':
        $tick = $admin->tickStatus();
        $lastRun = (int)($tick['last_run'] ?? 0);
        ?>
        <h2>Game Tick</h2>
        <div class="admin-card">
            <h3>Last Run</h3>
            <table class="admin-tbl">
                <tr><th>Last Run</th><td><?= $lastRun > 0 ? Admin::clean(date('Y-m-d H:i', $lastRun)) : 'never' ?></td></tr>
                <tr><th>Status</th><td><?= Admin::clean((string)($tick['last_status'] ?? 'never')) ?></td></tr>
                <tr><th>Duration</th><td><?= number_format((float)($tick['last_duration'] ?? 0.0), 2) ?>s</td></tr>
                <tr><th>Processed</th><td><?= number_format((int)($tick['last_processed'] ?? 0)) ?></td></tr>
                <tr><th>Naquadah Income (total)</th><td><?= number_format((int)($tick['last_income'] ?? 0)) ?></td></tr>
                <tr><th>Unit Upkeep (total)</th><td><?= number_format((int)($tick['last_upkeep'] ?? 0)) ?></td></tr>
                <tr><th>Turns Granted</th><td><?= number_format((int)($tick['last_turns'] ?? 0)) ?></td></tr>
                <tr><th>Untrained Granted</th><td><?= number_format((int)($tick['last_units'] ?? 0)) ?></td></tr>
            </table>
        </div>
        <div class="admin-card">
            <h3>Run Tick Now</h3>
            <p class="admin-meta">Runs the full turn-tick engine: income, unit upkeep, action-turn refill, untrained growth and rank recalculation. The cron runner is <code>scripts/backend/turn_tick.php</code>.</p>
            <form method="post" action="index.php" class="admin-form" style="display:inline-block;vertical-align:top;">
                <input type="hidden" name="csrf" value="<?= Admin::clean($csrf) ?>">
                <input type="hidden" name="action" value="tick_run">
                <button class="admin-btn" type="submit" onclick="return confirm('Run the game tick for all players now?');">Run Tick</button>
            </form>
            <form method="post" action="index.php" class="admin-form" style="display:inline-block;vertical-align:top;">
                <input type="hidden" name="csrf" value="<?= Admin::clean($csrf) ?>">
                <input type="hidden" name="action" value="tick_run">
                <input type="hidden" name="dry_run" value="1">
                <button class="admin-btn warn" type="submit">Dry Run (no changes)</button>
            </form>
        </div>
        <?php
        break;

    case 'mass':
        ?>
        <h2>Mass Grants</h2>
        <div class="admin-card">
            <p class="admin-meta">Apply a grant to many players at once. Provide a comma or whitespace separated list of player ids, or grant to every account.</p>
            <form method="post" action="index.php" class="admin-form">
                <input type="hidden" name="csrf" value="<?= Admin::clean($csrf) ?>">
                <input type="hidden" name="action" value="mass_grant">
                <div class="row">
                    <label>Grant type:</label>
                    <select name="kind">
                        <option value="naq">Naquadah</option>
                        <option value="turns">Action Turns</option>
                        <option value="untrained">Untrained Units</option>
                    </select>
                </div>
                <div class="row"><label>Amount per player:</label><input type="number" name="amount" min="0" value="0"></div>
                <div class="row"><label>Player ids:</label><textarea name="uids" placeholder="e.g. 1, 2, 3"></textarea></div>
                <div class="row"><label>All players:</label><input type="checkbox" name="all_players" value="1"></div>
                <button class="admin-btn" type="submit">Apply Grant</button>
            </form>
        </div>
        <?php
        break;

    case 'announcements':
        $ann = $admin->announcementStatus();
        ?>
        <h2>Announcements</h2>
        <?php if ($ann['active']): ?><div class="admin-ok">An announcement is currently visible to players.</div><?php endif; ?>
        <div class="admin-card">
            <h3>Current Announcement</h3>
            <?php if ($ann['title'] === '' && $ann['body'] === ''): ?>
                <p class="admin-meta">No announcement content set.</p>
            <?php else: ?>
            <table class="admin-tbl">
                <tr><th>Title</th><td><?= Admin::clean($ann['title']) ?></td></tr>
                <tr><th>Body</th><td><?= Admin::clean($ann['body']) ?></td></tr>
            </table>
            <?php endif; ?>
        </div>
        <div class="admin-card">
            <h3>Publish Announcement</h3>
            <form method="post" action="index.php" class="admin-form">
                <input type="hidden" name="csrf" value="<?= Admin::clean($csrf) ?>">
                <input type="hidden" name="action" value="announcement_publish">
                <div class="row"><label>Title:</label><input type="text" name="title" value="<?= Admin::clean($ann['title']) ?>" maxlength="128"></div>
                <div class="row"><label>Body:</label><textarea name="body" placeholder="Message shown to every player"></textarea></div>
                <button class="admin-btn" type="submit">Publish</button>
            </form>
        </div>
        <?php if ($ann['active']): ?>
        <div class="admin-card">
            <form method="post" action="index.php" class="admin-form">
                <input type="hidden" name="csrf" value="<?= Admin::clean($csrf) ?>">
                <input type="hidden" name="action" value="announcement_clear">
                <button class="admin-btn warn" type="submit">Hide Announcement</button>
            </form>
        </div>
        <?php endif; ?>
        <?php
        break;

    case 'maintenance':
        $maint = $admin->maintenanceStatus();
        ?>
        <h2>Maintenance Mode</h2>
        <?php if ($maint['enabled']): ?><div class="admin-err">Maintenance mode is ON. Regular players cannot access the game; staff accounts are unaffected.</div><?php endif; ?>
        <div class="admin-card">
            <form method="post" action="index.php" class="admin-form">
                <input type="hidden" name="csrf" value="<?= Admin::clean($csrf) ?>">
                <input type="hidden" name="action" value="maintenance_set">
                <div class="row"><label>Enable maintenance:</label><input type="checkbox" name="enabled" value="1"<?= $maint['enabled'] ? ' checked' : '' ?>></div>
                <div class="row"><label>Message:</label><input type="text" name="message" value="<?= Admin::clean($maint['message']) ?>" placeholder="Shown to players while maintenance is active"></div>
                <button class="admin-btn" type="submit">Save</button>
            </form>
        </div>
        <?php
        break;

    case 'messages':
        $recentMessages = [];
        if ($admin->connected() && $admin->db_link) {
            $q = $admin->query("SELECT m.`mid`, m.`fromUID`, m.`toUID`, m.`subject`, m.`message`, m.`timeSent`, u.`uname`
                                FROM `messages` m
                                LEFT JOIN `users` u ON u.`uid` = m.`toUID`
                                ORDER BY m.`mid` DESC LIMIT 50");
            if ($q) {
                while ($row = $q->fetch_object()) {
                    $recentMessages[] = $row;
                }
            }
        }
        ?>
        <h2>Broadcast</h2>
        <div class="admin-card">
            <h3>Send Message to All Players</h3>
            <form method="post" action="index.php" class="admin-form">
                <input type="hidden" name="csrf" value="<?= Admin::clean($csrf) ?>">
                <input type="hidden" name="action" value="broadcast">
                <div class="row"><label>Subject:</label><input type="text" name="subject" placeholder="Subject"></div>
                <div class="row"><label>Message:</label><textarea name="body" placeholder="Message body"></textarea></div>
                <button class="admin-btn" type="submit">Broadcast</button>
            </form>
        </div>
        <div class="admin-card">
            <h3>Recent Messages</h3>
            <?php if (count($recentMessages) === 0): ?>
                <p class="admin-meta">No messages found.</p>
            <?php else: ?>
            <table class="admin-tbl">
                <tr><th>ID</th><th>From</th><th>To</th><th>Subject</th><th>Body</th><th>Time</th></tr>
                <?php foreach ($recentMessages as $m): ?>
                <tr>
                    <td><?= (int)$m->mid ?></td>
                    <td><?= (int)$m->fromUID ?></td>
                    <td><?= Admin::clean($m->uname) ?></td>
                    <td><?= Admin::clean($m->subject) ?></td>
                    <td><?= Admin::clean(substr($m->message, 0, 80)) ?></td>
                    <td><?= Admin::clean(date('Y-m-d H:i', (int)$m->timeSent)) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
        <?php
        break;

    case 'logs':
        $type = (string)($_GET['type'] ?? '');
        $rows = $admin->actionLogBrowse($type, 150);
        ?>
        <h2>Action Logs</h2>
        <div class="admin-card">
            <form method="get" action="index.php" class="admin-form">
                <input type="hidden" name="view" value="logs">
                <div class="row">
                    <label>Filter type:</label>
                    <select name="type" onchange="this.form.submit()">
                        <option value="">All</option>
                        <?php foreach (['attack', 'raid', 'spy', 'sab'] as $t): ?>
                            <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= Admin::clean(ucfirst($t)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
        <div class="admin-card">
            <?php if (count($rows) === 0): ?>
                <p class="admin-meta">No action log entries found.</p>
            <?php else: ?>
            <table class="admin-tbl">
                <tr><th>ID</th><th>Time</th><th>Attacker</th><th>Target</th><th>Type</th><th>Success</th><th>Stolen</th><th>Deaths (them)</th><th>Deaths (us)</th></tr>
                <?php foreach ($rows as $a): ?>
                <tr>
                    <td><?= (int)$a->actID ?></td>
                    <td><?= Admin::clean(date('Y-m-d H:i', (int)$a->time)) ?></td>
                    <td><?= (int)$a->uid === 0 ? 'system' : Admin::clean($a->attacker) ?></td>
                    <td><?= (int)$a->to_uid ?></td>
                    <td><?= Admin::clean($a->type) ?></td>
                    <td><?= (int)$a->success === 1 ? 'Yes' : 'No' ?></td>
                    <td><?= number_format((int)$a->stolen) ?></td>
                    <td><?= number_format((int)$a->thereDead) ?></td>
                    <td><?= number_format((int)$a->myDead) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
        <?php
        break;

    case 'market':
        $showAll = (string)($_GET['all'] ?? '') === '1';
        $listings = $admin->marketListings(!$showAll, 150);
        ?>
        <h2>Market</h2>
        <div class="admin-card">
            <p class="admin-meta"><?= $showAll ? 'Showing all listings.' : 'Showing active listings only.' ?>
            <a href="index.php?view=market<?= $showAll ? '' : '&all=1' ?>"><?= $showAll ? 'Show active only' : 'Show all (incl. sold)' ?></a></p>
            <?php if (count($listings) === 0): ?>
                <p class="admin-meta">No listings found.</p>
            <?php else: ?>
            <table class="admin-tbl">
                <tr><th>ID</th><th>Seller</th><th>Resource</th><th>Amount</th><th>Price / Unit</th><th>Total</th><th>Status</th><th>Action</th></tr>
                <?php foreach ($listings as $l): ?>
                <tr>
                    <td><?= (int)$l->lid ?></td>
                    <td><a href="index.php?view=player&uid=<?= (int)$l->uid ?>"><?= Admin::clean($l->uname) ?></a></td>
                    <td><?= Admin::clean($l->resource) ?></td>
                    <td><?= number_format((int)$l->amount) ?></td>
                    <td><?= number_format((float)$l->price_per, 2) ?></td>
                    <td><?= number_format((float)$l->total_cost, 0) ?></td>
                    <td><?= (int)$l->active === 1 ? 'Active' : 'Closed' ?></td>
                    <td>
                        <?php if ((int)$l->active === 1): ?>
                        <form method="post" action="index.php" style="display:inline;">
                            <input type="hidden" name="csrf" value="<?= Admin::clean($csrf) ?>">
                            <input type="hidden" name="action" value="cancel_listing">
                            <input type="hidden" name="lid" value="<?= (int)$l->lid ?>">
                            <button class="admin-btn danger" type="submit" onclick="return confirm('Cancel this listing and refund the seller?');">Cancel</button>
                        </form>
                        <?php else: ?>&mdash;<?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
        <?php
        break;

    case 'adminlog':
        $entries = $admin->recentAdminLog(200);
        ?>
        <h2>Staff Log</h2>
        <div class="admin-card">
            <?php if (count($entries) === 0): ?>
                <p class="admin-meta">No staff actions recorded yet.</p>
            <?php else: ?>
            <table class="admin-tbl">
                <tr><th>ID</th><th>Time</th><th>Staff</th><th>Action</th><th>Target</th><th>IP</th><th>Details</th></tr>
                <?php foreach ($entries as $entry): ?>
                <tr>
                    <td><?= (int)$entry->logID ?></td>
                    <td><?= Admin::clean(date('Y-m-d H:i', (int)$entry->time)) ?></td>
                    <td><?= Admin::clean($entry->username) ?></td>
                    <td><?= Admin::clean($entry->action) ?></td>
                    <td><?= (int)$entry->target_uid > 0 ? '<a href="index.php?view=player&uid=' . (int)$entry->target_uid . '">' . (int)$entry->target_uid . '</a>' : '&mdash;' ?></td>
                    <td><?= Admin::clean($entry->ip_address) ?></td>
                    <td><?= Admin::clean($entry->details) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
        <?php
        break;

    case 'settings':
        $settings = $admin->settings();
        $info = $admin->serverInfo();
        $knownKeys = [
            'site.maintenance_mode'      => 'Maintenance Mode (on/off)',
            'site.turn_interval_minutes' => 'Turn Interval (minutes)',
            'economy.default_reserve_ratio' => 'Default Reserve Ratio',
            'operations.max_attack_turns_per_action' => 'Max Attack Turns / Action',
            'operations.max_covert_turns_per_action' => 'Max Covert Turns / Action',
            'admin.announcement'         => 'Global Announcement',
        ];
        ?>
        <h2>Settings</h2>
        <div class="admin-card">
            <h3>Server Information</h3>
            <table class="admin-tbl">
                <?php foreach ($info as $key => $value): ?>
                <tr><th style="width:220px;"><?= Admin::clean(ucfirst(str_replace('_', ' ', $key))) ?></th><td><?= Admin::clean($value) ?></td></tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="admin-card">
            <h3>Application Settings</h3>
            <?php foreach ($knownKeys as $key => $label): ?>
            <form method="post" action="index.php" class="admin-form" style="margin-bottom:10px;">
                <input type="hidden" name="csrf" value="<?= Admin::clean($csrf) ?>">
                <input type="hidden" name="action" value="save_settings">
                <input type="hidden" name="setting_key" value="<?= Admin::clean($key) ?>">
                <div class="row">
                    <label><?= Admin::clean($label) ?>:</label>
                    <input type="text" name="setting_value" value="<?= Admin::clean($settings[$key] ?? '') ?>">
                    <button class="admin-btn" type="submit">Save</button>
                </div>
            </form>
            <?php endforeach; ?>
        </div>
        <?php
        break;
}

adminShellEnd();
?>
