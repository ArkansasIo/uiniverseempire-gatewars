<?php
/*
 * MIT License
 *
 * Copyright (c) 2026 Universe Civilization : Empire at wars
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
 *  SOFTWARE.
 */
$rememberUser = isset($_COOKIE['sgw_remember_user']) ? (string)$_COOKIE['sgw_remember_user'] : '';
$rememberPass = isset($_COOKIE['sgw_remember_pass']) ? (string)$_COOKIE['sgw_remember_pass'] : '';
$isRemembered = $rememberUser !== '';
?>

<form method="post" action="/index.php" class="auth-card" id="login-form">
  <div class="auth-eyebrow"><span class="auth-signal"></span>Secure uplink // Sector 09</div>
  <h3>Pilot Access</h3>
  <p class="auth-intro">Authenticate to enter your empire command network and resume command of your world.</p>

  <ul class="auth-details">
    <li>Manage your home planet's economy, defenses, and military forces</li>
    <li>Research technologies, trade on the market, and log every engagement</li>
    <li>Attack rivals, raid resources, and climb the ranks of the galaxy</li>
  </ul>

  <label for="email">Email or Username</label>
  <input type="text" name="user" id="email" required value="<?php echo htmlspecialchars($rememberUser, ENT_QUOTES, 'UTF-8'); ?>" />

  <label for="password">Password</label>
  <input type="password" name="pass" id="password" required value="<?php echo htmlspecialchars($rememberPass, ENT_QUOTES, 'UTF-8'); ?>" />

  <label class="auth-remember">
    <input type="checkbox" name="remember" id="remember" value="1"<?php echo $isRemembered ? ' checked' : ''; ?> />
    <span>Remember me on this device</span>
  </label>

  <input type="submit" class="auth-submit" name="submit" value="Initialize Command Link" />

  <p class="auth-hint">New commander? Select <strong>Create Account</strong> from the title navigation.</p>
  <div class="auth-status"><span class="auth-signal"></span>Gate network standing by</div>
</form>
