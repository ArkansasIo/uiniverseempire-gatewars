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
// Staff login for the admin control panel.
// Validates a normal game account and requires alevel >= 4 (see User::isAdmin()).
// On success the shared game session already carries userid/access, so the
// panel entry point grants access without a second login.

include_once("../config.php");

if (!class_exists('Admin', false)) {
    require_once(__DIR__ . '/../base/Admin.class.php');
}

if (empty($_SESSION['admin_login_csrf'])) {
    $_SESSION['admin_login_csrf'] = bin2hex(random_bytes(16));
}
$loginCsrf = (string)$_SESSION['admin_login_csrf'];

$admin = new Admin();
if ($admin->loggedIn && $admin->isAdmin()) {
    header("Location: index.php");
    exit;
}

$error = '';
$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
if ($isPost) {
    $submittedCsrf = (string)($_POST['csrf'] ?? '');
    if (!hash_equals($loginCsrf, $submittedCsrf)) {
        $error = 'Session token expired. Please try again.';
    } else {
        $username = (string)($_POST['user'] ?? '');
        $password = (string)($_POST['pass'] ?? '');
        if ($username === '' || $password === '') {
            $error = 'Enter your username and password.';
        } else {
            $login = new User($username, $password);
            if ($login->loggedIn && $login->isAdmin()) {
                header("Location: index.php");
                exit;
            }
            $error = 'Login failed: invalid credentials or insufficient privileges.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Staff Login - Admin Control Panel</title>
    <style>
        body { margin:0; font-family: Arial, Helvetica, sans-serif; background:#0a0f1a; color:#c9d6e8; }
        .login-wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .login-card { width:340px; max-width:92vw; padding:32px; background:#111a2b; border:1px solid #24334d; border-radius:8px; }
        .login-card h1 { margin:0 0 4px; font-size:20px; color:#ffd76e; }
        .login-card .sub { margin:0 0 20px; font-size:12px; color:#8ba0bd; }
        .login-card label { display:block; margin:12px 0 4px; font-size:12px; color:#a9c0dd; }
        .login-card input[type="text"], .login-card input[type="password"] {
            width:100%; box-sizing:border-box; padding:9px 10px; border:1px solid #2c3f5e;
            border-radius:4px; background:#0c1424; color:#e8f0fb; font-size:14px;
        }
        .login-card button { margin-top:20px; width:100%; padding:10px; border:0; border-radius:4px;
            background:#2f6fb2; color:#fff; font-size:14px; cursor:pointer; }
        .login-card button:hover { background:#3a82cc; }
        .login-error { margin-bottom:14px; padding:9px 12px; background:#301616; border:1px solid #7a2a2a;
            color:#e6a6a6; border-radius:4px; font-size:13px; }
        .login-card .back { display:block; margin-top:16px; text-align:center; font-size:12px; color:#7ab4ff; text-decoration:none; }
    </style>
</head>
<body>
<div class="login-wrap">
  <form class="login-card" method="post" action="login.php" autocomplete="off">
    <h1>Staff Login</h1>
    <p class="sub">Admin Control Panel &mdash; authorized personnel only</p>
    <?php if ($error !== '') { ?>
    <div class="login-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php } ?>
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($loginCsrf, ENT_QUOTES, 'UTF-8'); ?>" />
    <label for="login-user">Username</label>
    <input type="text" id="login-user" name="user" value="<?= htmlspecialchars((string)($_POST['user'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="64" />
    <label for="login-pass">Password</label>
    <input type="password" id="login-pass" name="pass" value="" />
    <button type="submit">Sign In</button>
    <a class="back" href="../index.php">&larr; Back to game</a>
  </form>
</div>
</body>
</html>
