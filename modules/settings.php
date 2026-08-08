<?php
/*
 * MIT License
 *
 * Copyright (c) 2026 Stephen, Universe Civilization : Empire at wars
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
include("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn || !isset($_GET['time'])) {
    header("Location: ../index.php"); exit;
}

$u = new User();
$uid = (int)$_SESSION['userid'];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user_csrf'])) {
    $_SESSION['user_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['user_csrf'];

$messages = [];
$errors = [];
$savedTheme = null;

$post = (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST')
    ? $_POST : [];
if (!empty($post) && isset($post['user_csrf'])) {
    if (!hash_equals($csrf, (string)$post['user_csrf'])) {
        $errors[] = 'Session token expired. Please try again.';
    } else {
        $emailChanged = isset($post['email']);
        $passwordChanged = isset($post['newpass']) && trim((string)$post['newpass']) !== '';
        $prefsChanged = isset($post['theme']);

        if ($emailChanged) {
            $emailResult = $u->updateEmail($uid, (string)$post['email']);
            $errors = array_merge($errors, $emailResult);
            if (empty($emailResult)) {
                $messages[] = 'Email address updated.';
            }
        }
        if ($passwordChanged) {
            if ((string)$post['newpass'] !== (string)($post['confirmpass'] ?? '')) {
                $errors[] = 'Password confirmation does not match.';
            } else {
                $passResult = $u->updatePassword($uid, (string)$post['newpass']);
                $errors = array_merge($errors, $passResult);
                if (empty($passResult)) {
                    $messages[] = 'Password updated.';
                }
            }
        }
        if ($prefsChanged) {
            $theme = in_array((string)$post['theme'], ['white', 'og', 'blue', 'stargate'], true)
                ? (string)$post['theme'] : 'blue';
            $saved = $u->saveUserPrefs($uid, [
                'theme' => $theme,
                'notify_attack' => isset($post['notify_attack']) ? 1 : 0,
                'notify_message' => isset($post['notify_message']) ? 1 : 0,
                'notify_market' => isset($post['notify_market']) ? 1 : 0,
            ]);
            if ($saved) {
                $messages[] = 'Preferences saved.';
                $savedTheme = $theme;
            } else {
                $errors[] = 'Could not save preferences.';
            }
        }
        if (!$emailChanged && !$passwordChanged && !$prefsChanged) {
            $errors[] = 'Nothing to save.';
        }
    }
}

$prefs = $u->getUserPrefs($uid);
$currentEmail = '';
if ($u->connected() && $u->db_link) {
    $stmt = $u->db_link->prepare("SELECT `email` FROM `users` WHERE `uid`=? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $emailRow = $stmt->get_result()->fetch_object();
        if ($emailRow) {
            $currentEmail = (string)$emailRow->email;
        }
    }
}
$activeTheme = in_array($savedTheme, ['white', 'og', 'blue', 'stargate'], true) ? $savedTheme : $prefs['theme'];
?>
<?php if (!empty($messages)) { ?>
<div style="margin-bottom:12px; padding:8px 12px; border:1px solid #2a6b3a; background:#16301f; color:#9fdcae; border-radius:4px;">
    <?php foreach ($messages as $m) { ?><div><?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
</div>
<?php } ?>
<?php if (!empty($errors)) { ?>
<div style="margin-bottom:12px; padding:8px 12px; border:1px solid #7a2a2a; background:#301616; color:#e6a6a6; border-radius:4px;">
    <?php foreach ($errors as $e) { ?><div><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
</div>
<?php } ?>
<div class="content-header" style="margin-bottom:14px;">
    <div class="content-title">Pilot Settings</div>
</div>
<div style="display:flex; flex-wrap:wrap; gap:24px;">
    <form method="post" action="javascript:void(0);"
          onsubmit="setQueryString(); sendData('settings','post','mainDisplay'); return false;"
          style="flex:1 1 340px; max-width:560px;">
        <input type="hidden" name="user_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>" />
        <table border="0" cellpadding="3" cellspacing="0" style="width:100%;">
            <tr>
                <td colspan="2" class="header-text" style="padding-bottom:6px;">Account</td>
            </tr>
            <tr>
                <td style="width:40%;">Email address</td>
                <td><input type="text" name="email" value="<?= htmlspecialchars($currentEmail, ENT_QUOTES, 'UTF-8'); ?>" maxlength="100" style="width:100%;" /></td>
            </tr>
            <tr>
                <td>New password</td>
                <td><input type="password" name="newpass" value="" maxlength="64" style="width:100%;" /></td>
            </tr>
            <tr>
                <td>Confirm password</td>
                <td><input type="password" name="confirmpass" value="" maxlength="64" style="width:100%;" /></td>
            </tr>
            <tr>
                <td colspan="2" class="header-text" style="padding-top:12px; padding-bottom:6px;">Game theme</td>
            </tr>
            <tr>
                <td>Theme</td>
                <td>
                    <select name="theme" style="width:100%;">
                        <?php foreach (['blue' => 'Blue', 'og' => 'Original', 'white' => 'White', 'stargate' => 'Stargate'] as $key => $label) { ?>
                        <option value="<?= $key; ?>"<?= $activeTheme === $key ? ' selected' : ''; ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="header-text" style="padding-top:12px; padding-bottom:6px;">Notifications</td>
            </tr>
            <tr>
                <td>Notify on attack</td>
                <td><input type="checkbox" name="notify_attack" value="1"<?= $prefs['notify_attack'] ? ' checked' : ''; ?> /></td>
            </tr>
            <tr>
                <td>Notify on new message</td>
                <td><input type="checkbox" name="notify_message" value="1"<?= $prefs['notify_message'] ? ' checked' : ''; ?> /></td>
            </tr>
            <tr>
                <td>Notify on market activity</td>
                <td><input type="checkbox" name="notify_market" value="1"<?= $prefs['notify_market'] ? ' checked' : ''; ?> /></td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top:14px;">
                    <button type="submit" class="button">Save Settings</button>
                </td>
            </tr>
        </table>
    </form>
</div>
<?php if ($savedTheme !== null) { ?>
<script type="text/javascript">
try {
    localStorage.setItem('sgwTheme', '<?= $savedTheme; ?>');
} catch (e) {}
setTheme('<?= $savedTheme; ?>');
</script>
<?php } ?>
