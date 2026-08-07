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
include_once("config.php");
$s = new Game();
if (isset($_GET['logout']) && $_GET['logout']) { User::logOut();} 
if (isset($_POST['submit']) && ($_POST['submit'] == "Login" || $_POST['submit'] == "Initialize Command Link"))
{
        $s = new User($_POST['user'], $_POST['pass']);
        if ($s->loggedIn) {
                if (!empty($_POST['remember'])) {
                        setcookie('sgw_remember_user', (string)$_POST['user'], time() + 2592000, '/');
                        setcookie('sgw_remember_pass', (string)$_POST['pass'], time() + 2592000, '/');
                } else {
                        setcookie('sgw_remember_user', '', time() - 3600, '/');
                        setcookie('sgw_remember_pass', '', time() - 3600, '/');
                }
        }
}

$maintenanceEnabled = $s->getAppSetting('maintenance.enabled', '0') === '1';
$announcementActive = $s->getAppSetting('announcement.active', '0') === '1';
$announcementTitle = $s->getAppSetting('announcement.title', '');
$announcementBody = $s->getAppSetting('announcement.body', '');

$subs['{ANNOUNCEMENT_BANNER}'] = '';
if ($announcementActive && ($announcementTitle !== '' || $announcementBody !== '')) {
    $bannerTitle = $announcementTitle !== '' ? $announcementTitle : 'Announcement';
    $subs['{ANNOUNCEMENT_BANNER}'] = '<div class="announcement-banner">'
        . '<strong>' . htmlspecialchars($bannerTitle, ENT_QUOTES, 'UTF-8') . '</strong>'
        . ($announcementBody !== '' ? ' ' . htmlspecialchars($announcementBody, ENT_QUOTES, 'UTF-8') : '')
        . '</div>';
}

$subs['{ADMIN_MENU}'] = '';
if ($s->loggedIn && method_exists($s, 'isAdmin') && $s->isAdmin()) {
	$subs['{ADMIN_MENU}'] = '<div class="menu-section-title"><img src="images/ui/core-command.svg" alt="Staff" /><span>Staff Console</span></div>
<details open>
  <summary><span class="menu-summary"><img src="images/ui/core-command.svg" alt="Admin" /><span>Admin Control Panel</span></span></summary>
  <a href="admin/index.php" target="_blank">Admin Control Panel</a>
  <a href="admin/index.php?view=players" target="_blank">Manage Players</a>
  <a href="admin/index.php?view=messages" target="_blank">Broadcast Message</a>
  <a href="admin/index.php?view=logs" target="_blank">Action Logs</a>
  <a href="admin/index.php?view=market" target="_blank">Market Moderation</a>
  <a href="admin/index.php?view=adminlog" target="_blank">Staff Log</a>
  <a href="admin/index.php?view=settings" target="_blank">Settings</a>
</details>';
}

$isStaff = $s->loggedIn && method_exists($s, 'isAdmin') && $s->isAdmin();
if ($maintenanceEnabled && !$isStaff) {
    header('HTTP/1.1 503 Service Unavailable');
    $maintenanceMessage = $s->getAppSetting('maintenance.message', 'The Stargate network is currently undergoing maintenance. Please check back soon.');
    $maintenanceMessage = htmlspecialchars($maintenanceMessage, ENT_QUOTES, 'UTF-8');
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Under Maintenance - Universe Civilization: Empire at Wars</title>
    <style>
        body { margin:0; font-family: Arial, Helvetica, sans-serif; background:#0a0f1a; color:#c9d6e8; }
        .maint-wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .maint-card { max-width:560px; margin:24px; padding:36px; background:#111a2b; border:1px solid #24334d; border-radius:8px; text-align:center; }
        .maint-card h1 { margin:0 0 12px; color:#ffd76e; font-size:26px; }
        .maint-card p { line-height:1.6; }
        .maint-card a { color:#7ab4ff; }
    </style>
</head>
<body>
<div class="maint-wrap">
  <div class="maint-card">
    <h1>Under Maintenance</h1>
    <p><?= $maintenanceMessage; ?></p>
    <p><a href="index.php">Reload</a></p>
  </div>
</div>
</body>
</html>
<?php
    exit;
}

if(!$s->loggedIn || (isset($_GET['logout']) && $_GET['logout']))
{

?>
<!DOCTYPE html>
<html>
<head>
		<script type="text/javascript" src="js/main.js"></script>
		<script type="text/javascript" src="js/auto.js"></script>
		<script type="text/javascript" src="js/train.js"></script>
		<script type="text/javascript" src="js/images.js"></script>
		<script type="text/javascript" src="js/bbfix.js"></script>
    <title>Universe Civilization: Empire at Wars</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<LINK REL=STYLESHEET TYPE='text/css' HREF='main.css' />
</head>

<body class="theme-blue" onLoad="mainUpdate('login','Login'); MM_preloadImages('images/galaxy1-2.jpg','images/galaxy2-2.jpg','images/galaxy3-2.jpg'); autoclear(); bb_init('divBody', false); if (typeof setTheme === 'function') { setTheme(getStoredTheme()); }">

<div id="divBody">

<div class="public-shell">
  <header class="public-top">
    <a class="public-github" href="https://github.com/ArkansasIo/universe-civilization-enmpire-stargate" target="_blank" rel="noopener" title="View the game source on GitHub">
      <svg viewBox="0 0 16 16" width="16" height="16" fill="currentColor" aria-hidden="true"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27s1.36.09 2 .27c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8Z"/></svg>
      <span>Game Source</span>
    </a>
    <div class="public-brand">
      <h1>Universe Civilization: Empire at Wars</h1>
      <p>Strategic command and empire operations across the Stargate network</p>
      <p class="public-tagline">A persistent, turn-based war game. Raise a fleet, develop technologies, and conquer worlds through the Stargate frontier — every decision shapes your empire and the balance of power in the galaxy.</p>
    </div>
    <div class="public-actions">
      <a class="public-btn" href="javascript:void(0)" onClick="mainUpdate('login','Login'); return false" onMouseOver="rollUpDate('Login'); return false" onMouseOut="autoclear(); return false">Pilot Login</a>
      <a class="public-btn secondary" href="javascript:void(0)" onClick="mainUpdate('register','Register To Play'); return false" onMouseOver="rollUpDate('Register To Play'); return false" onMouseOut="autoclear(); return false">Create Account</a>
    </div>
  </header>

  <section class="public-hero">
    <div class="public-hero-left">
      <div class="public-hero-card public-hero-card-main">
        <a href="javascript:void(0)" onClick="mainUpdate('login','Login'); return false" onMouseOver="rollUpDate('Login'); return false" onMouseOut="autoclear(); return false">
          <img src="images/galaxy1.jpg" name="Image12" border="0" id="Image12" onMouseOver="MM_swapImage('Image12','','images/galaxy1-2.jpg',1)" onMouseOut="MM_swapImgRestore()" />
        </a>
        <div class="hero-overlay">
          <span class="hero-pill">Stargate Command</span>
          <h2>Command the Final Frontier</h2>
          <p>Build fleets, seize systems, and wage turn-based war across the Stargate frontier.</p>
        </div>
      </div>
    </div>
    <div class="public-hero-right">
      <div class="public-hero-card">
        <a href="javascript:void(0)" onClick="mainUpdate('register','Register To Play'); return false" onMouseOver="rollUpDate('Register To Play'); return false" onMouseOut="autoclear(); return false">
          <img src="images/galaxy2.jpg" name="Image11" border="0" id="Image11" onMouseOver="MM_swapImage('Image11','','images/galaxy2-2.jpg',1)" onMouseOut="MM_swapImgRestore()" />
        </a>
        <div class="hero-caption">Forge a warfleet</div>
      </div>
      <div class="public-hero-card">
        <a href="javascript:void(0)" onClick="mainUpdate('updates','Updates'); return false" onMouseOver="rollUpDate('Updates'); return false" onMouseOut="autoclear(); return false">
          <img src="images/galaxy3.JPG" name="Image13" border="0" id="Image13" onMouseOver="MM_swapImage('Image13','','images/galaxy3-2.jpg',1)" onMouseOut="MM_swapImgRestore()" />
        </a>
        <div class="hero-caption">Watch the frontier evolve</div>
      </div>
    </div>
  </section>

  <section class="public-content-grid">
    <aside class="public-panel public-news">
      <h3>Command Feed</h3>
      <div id="up2date"></div>
      <h4>Status</h4>
      <div id="rollover">Login</div>
    </aside>
    <main class="public-panel public-main">
	  <?php
	if (isset($_POST['submit']) && $_POST['submit']=="Register")
{
	$number = $_POST['number'];
	if(md5($number) != $_SESSION['image_value'])
	{
	echo 'Validation string not valid! Please try again!<br>';
	}
	else
	{
	$s->addUser($_POST['user'], $_POST['pass'], $_POST['email'], $_POST['rid'], $_POST['hpname'], $_SERVER["REMOTE_ADDR"], 1);
	}
}
?>
      <div id="mainDisplay"></div>
      <div class="public-footnote">
        <span>Graphics and source code done by Stephen and open code ai</span>
      </div>
    </main>
  </section>
</div>

</div>
</body>
</html>

<?php
}
else {

showPage();

}

?>
