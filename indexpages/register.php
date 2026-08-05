<?php
include_once("../config.php");
$s = new Game();

if (!$s->loggedIn)
{
?>
<form method="post" action="/index.php">
<div class="auth-card" id="register-form">
  <h3>Register New Pilot</h3>
  <p>Create your realm and begin expansion across the galaxy.</p>

  <label for="username">Username</label>
  <input type="text" name="user" id="username" required />

  <label for="hpname">Home Planet Name</label>
  <input type="text" name="hpname" id="hpname" required />

  <label for="password">Password</label>
  <input type="password" name="pass" id="password" required />

  <label for="email">E-mail Address</label>
  <input type="email" name="email" id="email" required />

  <label for="race">Race</label>
  <select name="rid" id="race" required>
    <?php
		$list = $s->getRaces();
		foreach ($list as $race) {
			$raceId = (int)($race['id'] ?? 0);
			$raceName = htmlspecialchars((string)($race['name'] ?? 'Unknown'), ENT_QUOTES, 'UTF-8');
			if ($raceId > 0) {
				echo "<option value=\"{$raceId}\">{$raceName}</option>\r\n";
			}
		}
	?>
  </select>
  <p class="auth-hint">Choose your empire lineage. This sets your starting unit doctrine.</p>

  <p class="auth-hint">Type the validation text shown in the image.</p>
  <input name="number" type="text" id="number" required />
  <p style="text-align:center; margin-top:8px;"><img src="image.php?mt=<?= microtime();?>" alt="Validation code"></p>

  <input type="submit" class="auth-submit" name="submit" value="Register" />
</div>
</form>
<?php
}
else
{
	echo "You are Logged in, You cannot register another account as it's against the rules.";
}
?>
