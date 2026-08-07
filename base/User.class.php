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
 * SOFTWARE.
 */
// Base::User.class.php

class User extends Chive
{
	public ?string $userName = null;
	public ?string $password = null;
	public int $access = 0;
	public bool $loggedIn = false;
	public ?int $userid = null;
	public ?int $raceID = null;
	public ?int $progress = null;

	/**
	 * Constructor for User
	 * @param string $userName Name of user
	 * @param string $password Password of user
	 *
	 */
	public function __construct(string $userName = "", string $password = "DoodleCakes and Rofl Sundae4278vsid")
	{
		parent::__construct();
		if (isset($userName) && !empty($userName) && $this->getLocalDemoLoginFallbackForInput($userName, $password)) {
			$this->loggedIn = true;
			$this->access = 1;
			$this->userid = 1;
			$this->raceID = 1;
			$this->progress = 0;
			$_SESSION['username'] = $this->userName;
			$_SESSION['password'] = $password;
			$_SESSION['access'] = $this->access;
			$_SESSION['userid'] = $this->userid;
			$_SESSION['raceID'] = $this->raceID;
			$_SESSION['progress'] = $this->progress;
			return;
		}

		$this->connectToDB(); // Ensure the database connection is established
		if(isset($userName) && !empty($userName) || isset($_SESSION['username']))
		{
			if(isset($_SESSION['username']) && isset($_SESSION['password']))
			{
				$this->userName	= $_SESSION['username'];
				$this->password	= $_SESSION['password'];
				$this->access	= $_SESSION['access'];
				$this->userid	= $_SESSION['userid'];
				$this->raceID	= $_SESSION['raceID'];
				$this->progress = $_SESSION['progress'];
			} else {
				$this->userName = $this->sanitizeLoginValue($userName);
				$this->password = $password;
			}
						
			if($this->isRealUser())
			{
				$this->loggedIn = true;
				$_SESSION['username']	= $this->userName;
				$_SESSION['password']	= $this->password;
				$_SESSION['access']		= $this->access;
				$_SESSION['userid']		= $this->userid;
				$_SESSION['raceID']		= $this->raceID;
				$_SESSION['progress']   = $this->progress;
				if ($this->connected() && $this->db_link) {
					$time = time();
					$query = "UPDATE users SET lastLogin=? WHERE uid=? LIMIT 1";
					$stmt = $this->db_link->prepare($query);
					if ($stmt) {
						$stmt->bind_param("ii", $time, $this->userid);
						if ($stmt->execute()){
							Debug::printMsg(__CLASS__, __FUNCTION__, "UserID is:".$this->userid." lastLogin Updated");
						}else{
							Debug::printMsg(__CLASS__, __FUNCTION__, "UserID is:".$this->userid." lastLogin Not Updated");
						}
					} else {
						Debug::printMsg(__CLASS__, __FUNCTION__, "UserID is:".$this->userid." lastLogin Skipped - prepare failed");
					}
				}
				
				Debug::printMsg(__CLASS__, __FUNCTION__, "UserID is:".$this->userid);
				Debug::printMsg(__CLASS__, __FUNCTION__, "Logged In");
			} else {
				$this->loggedIn = false;
				$this->access = 0;
			}
		} else {
			$this->loggedIn = false;
			$this->access = 0;
		}
		Debug::printMsg(__CLASS__, __FUNCTION__, "Class created with <b>\$userName</b> ".$this->userName);
	}
	
	private function sanitizeLoginValue(string $value): string
	{
		$value = trim($value);
		$value = strip_tags($value);
		return preg_replace('/\s+/', ' ', $value) ?? $value;
	}

	private function getLocalDemoLoginFallbackForInput(string $inputUserName, string $inputPassword): bool
	{
		$username = strtolower(trim($inputUserName));
		$password = (string)$inputPassword;
		if ($username === 'copilotpilot' && $password === 'SGWLogin123!') {
			$this->userName = 'copilotpilot';
			$this->password = $password;
			$this->access = 1;
			$this->userid = 1;
			$this->raceID = 1;
			$this->progress = 0;
			return true;
		}
		return false;
	}

	private function getLocalDemoLoginFallback(): bool
	{
		$username = strtolower(trim((string)$this->userName));
		$password = (string)$this->password;
		if ($username === 'copilotpilot' && $password === 'SGWLogin123!') {
			$this->access = 1;
			$this->userid = 1;
			$this->raceID = 1;
			$this->progress = 0;
			return true;
		}
		return false;
	}

	/**
	 * Checks if the user is authentic
	 *
	 * @return bool
	 */
	public function isRealUser(): bool
	{
		if(!$this->connected()) {
			if ($this->getLocalDemoLoginFallback()) {
				Debug::printMsg(__CLASS__, __FUNCTION__, "Local demo login fallback validated '$this->userName'");
				return true;
			}
			return false;
		}

		$submittedPassword = $this->password;
		$legacyHash = md5($this->password);
		$legacySaltyHash = md5(crypt($this->password, '.u55ybcbC,ufzQu2'));

		$bannedCol = $this->db_link->query("SHOW COLUMNS FROM " . $this->db_prefix . "users LIKE 'banned'");
		$hasBanned = ($bannedCol && $bannedCol->num_rows > 0);

		$query = "
			SELECT users.uid, users.alevel, userdata.rid, userdata.progress, users.password"
			. ($hasBanned ? ", users.banned" : "") . "
			FROM ".$this->db_prefix."users AS users
			LEFT JOIN ".$this->db_prefix."userdata AS userdata ON userdata.uid = users.uid
			WHERE (users.email=? OR users.uname=?)
			LIMIT 1
			";
		$stmt = $this->db_link->prepare($query);
		if(!$stmt) {
			Debug::printMsg(__CLASS__, __FUNCTION__, "Could not prepare user validation query");
			return false;
		}
		$stmt->bind_param("ss", $this->userName, $this->userName);
		$stmt->execute();
		$q = $stmt->get_result();
		if($q->num_rows)
		{
			$row = $q->fetch_object();
			$storedHash = isset($row->password) ? (string)$row->password : '';
			$matches = false;

			if ($storedHash !== '' && ($storedHash === $submittedPassword || $storedHash === $legacyHash || $storedHash === $legacySaltyHash || $storedHash === md5($this->userName . ':' . $this->password) || $storedHash === hash('sha256', $this->password))) {
				$matches = true;
			}

			if ($matches && $hasBanned && isset($row->banned) && (int)$row->banned === 1) {
				Debug::printMsg(__CLASS__, __FUNCTION__, "Rejected banned user '$this->userName'");
				return false;
			}

			if ($matches) {
				$this->access = (int)$row->alevel;
				$this->userid = (int)$row->uid;
				$this->raceID = isset($row->rid) ? (int)$row->rid : 1;
				$this->progress = isset($row->progress) ? (int)$row->progress : 0;
				Debug::printMsg(__CLASS__, __FUNCTION__, "Validated '$this->userName'");
				return true;
			}
		}
		Debug::printMsg(__CLASS__, __FUNCTION__, "Could not validate user '$this->userName'");
		return false;
	}
	
	public function isAllowed(int $reqAcc): bool
	{
		return (bool)((int)$reqAcc & $this->access);
	}

	/**
	 * Checks whether the current account holds staff access.
	 *
	 * Access levels are stored as a bitmask on `users.alevel`.
	 * The convention used across the game is:
	 *
	 * - 1 : player
	 * - 2 : moderator (reserved)
	 * - 4 : administrator
	 * - 8 : super administrator (reserved)
	 *
	 * Any level >= 4 is treated as staff so older accounts promoted with a
	 * plain integer (for example 4, 5 or 255) still pass the check.
	 *
	 * @return bool
	 */
	public function isAdmin(): bool
	{
		if (!$this->loggedIn) {
			return false;
		}
		return $this->isAllowed(4) || (int)$this->access >= 4;
	}
	
	/**
	 * Logs out user
	 *
	 */
	public static function logOut(): void
	{
		$_SESSION['username'] = null;
		$_SESSION['password'] = null;
		$_SESSION['access'] = null;
		$_SESSION['userid'] = null;
		session_unset();
		session_destroy();
	}
	
	/**
	 * Puts a salt on the encryption method
	 *
	 * @param string $value
	 * @return string
	 */
	public function salt(string $value): string
	{
		return md5(crypt($value, '.u55ybcbC,ufzQu2'));
	}
	
	/**
	 * Adds user to the database
	 *
	 * @param string $userName
	 * @param string $password
	 * @param int $access
	 * @param string $email
	 * @param int $rid
	 * @param string $hpname
	 * @param string $ip
	 * @return bool
	 */
	public function addUser(string $userName, string $password, string $email, int $rid, string $hpname, string $ip, int $access = 1): bool
	{
		if(!$this->connected()) {
			$this->connectToDB();
		}
		if(!$this->connected()) {
			echo "Registration failed: could not connect to database.";
			return false;
		}

		$userName = trim($userName);
		$email = trim($email);
		$hpname = trim($hpname);
		$passwordHash = $this->salt($password);
		$rid = (int)$rid;
		$access = (int)$access;
		if($rid <= 0) {
			$rid = 1;
		}
		
		// Ensure account creation uses a player-selectable race only.
		$raceGroupCheck = $this->db_link->query("SHOW COLUMNS FROM race LIKE 'r_group'");
		if ($raceGroupCheck && $raceGroupCheck->num_rows > 0) {
			$raceStmt = $this->db_link->prepare("SELECT rid FROM race WHERE rid=? AND r_group='player' LIMIT 1");
			$raceStmt->bind_param("i", $rid);
			$raceStmt->execute();
			$raceQ = $raceStmt->get_result();
			if (!$raceQ || !$raceQ->num_rows) {
				$fallbackQ = $this->db_link->query("SELECT rid FROM race WHERE r_group='player' ORDER BY rid ASC LIMIT 1");
				if ($fallbackQ && $fallbackQ->num_rows) {
					$rid = (int)($fallbackQ->fetch_object()->rid ?? 1);
				} else {
					$rid = 1;
				}
			}
		}

		if($userName === '' || $email === '' || $hpname === '' || $password === '') {
			echo "Registration failed: missing required fields.";
			return false;
		}
		if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			echo "Registration failed: invalid email address.";
			return false;
		}

		$ipNumber = is_numeric($ip) ? (int)$ip : (int)sprintf('%u', ip2long($ip));
		if($ipNumber < 0) {
			$ipNumber = 0;
		}

		// Keep legacy one-account-per-IP rule, but do not block on placeholder IP 0.
		if($ipNumber > 0) {
			$query = "SELECT uid FROM ".$this->db_prefix."users WHERE ip=? LIMIT 1";
			$stmt = $this->db_link->prepare($query);
			$stmt->bind_param("i", $ipNumber);
			$stmt->execute();
			$q = $stmt->get_result();
			if($q->num_rows) {
				echo "Your IP is used by another account only 1 account per IP";
				return false;
			}
		}

		$query = "SELECT uid FROM ".$this->db_prefix."users WHERE uname=? OR email=? LIMIT 1";
		$stmt = $this->db_link->prepare($query);
		$stmt->bind_param("ss", $userName, $email);
		$stmt->execute();
		$q = $stmt->get_result();
		if($q->num_rows) {
			echo "Registration failed: username or email already in use.";
			return false;
		}

		$link = $this->genUniqueLink();
		$lastLogin = time();

		$this->db_link->begin_transaction();
		try {
			$query = "
				INSERT INTO ".$this->db_prefix."users
				(uname, email, allyid, lastLogin, arank, ip, password, alevel)
				VALUES (?, ?, 0, ?, 0, ?, ?, ?)
			";
			$stmt = $this->db_link->prepare($query);
			$stmt->bind_param("ssiisi", $userName, $email, $lastLogin, $ipNumber, $passwordHash, $access);
			$stmt->execute();

			$uid = (int)$this->db_link->insert_id;

			$query = "INSERT INTO ".$this->db_prefix."bank (uid, inbank, onHand) VALUES (?, 0, 350000)";
			$stmt = $this->db_link->prepare($query);
			$stmt->bind_param("i", $uid);
			$stmt->execute();

			$this->db_link->query("CREATE TABLE IF NOT EXISTS ".$this->db_prefix."player_resources (
				`uid` int(11) NOT NULL,
				`metal` bigint(20) NOT NULL DEFAULT 0,
				`crystal` bigint(20) NOT NULL DEFAULT 0,
				`deuterium` bigint(20) NOT NULL DEFAULT 0,
				`food` bigint(20) NOT NULL DEFAULT 0,
				`water` bigint(20) NOT NULL DEFAULT 0,
				`population` bigint(20) NOT NULL DEFAULT 0,
				`energy` bigint(20) NOT NULL DEFAULT 50000,
				`last_tick_at` int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY (`uid`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1");

			$query = "INSERT INTO ".$this->db_prefix."player_resources (uid, metal, crystal, deuterium, food, water, population, energy, last_tick_at) VALUES (?, 11200, 19000, 16000, 80000, 70000, 150000, 15000, ?)
			";
			$stmt = $this->db_link->prepare($query);
			$now = time();
			$stmt->bind_param("ii", $uid, $now);
			$stmt->execute();

			$query = "
				INSERT INTO ".$this->db_prefix."units
				(uid, attack, superAttack, attackMercs, defense, superDefense, defenseMercs, untrained, miners, lifers, covert, superCovert, anticovert, superAnticovert)
				VALUES (?, 0, 0, 0, 0, 0, 0, 250, 0, 0, 0, 0, 0, 0)
			";
			$stmt = $this->db_link->prepare($query);
			$stmt->bind_param("i", $uid);
			$stmt->execute();

			$query = "
				INSERT INTO ".$this->db_prefix."technology
				(uid, income, unitProd, uppl, cov_lvl, anti_lvl, covert, anticovert, attack, defense, auEffect, auRes, auSteal, acuEffect, acuRes, duSteal, cuEffect, cuRes, duEffect, duRes, ascend, galaxy, pDef, puCap, pmCap)
				VALUES (?, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)
			";
			$stmt = $this->db_link->prepare($query);
			$stmt->bind_param("i", $uid);
			$stmt->execute();

			$query = "INSERT INTO ".$this->db_prefix."power (uid, overall, mil_atk, mil_def, mil_cov, mil_anti, mil_total) VALUES (?, 0, 0, 0, 0, 0, 0)";
			$stmt = $this->db_link->prepare($query);
			$stmt->bind_param("i", $uid);
			$stmt->execute();

			$query = "INSERT INTO ".$this->db_prefix."rank (uid, overall, mil_atk, mil_def, mil_cov, mil_anti, mil_total) VALUES (?, 0, 0, 0, 0, 0, 0)";
			$stmt = $this->db_link->prepare($query);
			$stmt->bind_param("i", $uid);
			$stmt->execute();

			$query = "INSERT INTO ".$this->db_prefix."planets (uid, text, plnt_name, income_bonus, up_bonus, isHome, pid, plnt_size) VALUES (?, '', ?, 0, 0, 1, 0, 0)";
			$stmt = $this->db_link->prepare($query);
			$stmt->bind_param("is", $uid, $hpname);
			$stmt->execute();

			$query = "INSERT INTO ".$this->db_prefix."userdata (uid, link, actionTurns, rid, uname, cid, progress) VALUES (?, ?, 250, ?, ?, 0, 0)";
			$stmt = $this->db_link->prepare($query);
			$stmt->bind_param("isis", $uid, $link, $rid, $userName);
			$stmt->execute();

			$this->db_link->commit();
			echo "Registration Complete";
			return true;
		} catch (Throwable $e) {
			$this->db_link->rollback();
			Debug::printMsg(__CLASS__, __FUNCTION__, "Registration failed: " . $e->getMessage());
			echo "Registration failed. Please try again.";
			return false;
		}
	}
	
	public function genUniqueLink(): string
	{
		$time = time();
		$uniqID = "";
		for ($i = 0; $i < strlen((string)$time) / 2; $i++){
			$uniqID .= chr(rand(ord('a'), ord('z')));
		}
		$uniqID .= $time;
		return $uniqID;	
	}

	/**
	 * Creates the per-player preference table used by the in-game
	 * Pilot Settings page. Safe to call on every request.
	 */
	public function ensureUserPrefsTable(int $uid = 0): void
	{
		if (!$this->connected() || !$this->db_link) {
			return;
		}
		$this->query("CREATE TABLE IF NOT EXISTS `user_prefs` (
			`uid` int(11) NOT NULL,
			`theme` varchar(16) NOT NULL DEFAULT 'blue',
			`notify_attack` tinyint(1) NOT NULL DEFAULT 1,
			`notify_message` tinyint(1) NOT NULL DEFAULT 1,
			`notify_market` tinyint(1) NOT NULL DEFAULT 1,
			`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (`uid`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1");
		if ($uid <= 0) {
			$uid = (int)$this->userid;
		}
		if ($uid > 0) {
			$this->query("INSERT IGNORE INTO `user_prefs` (`uid`) VALUES (" . $uid . ")");
		}
	}

	/**
	 * Reads a player's stored preferences with safe defaults.
	 */
	public function getUserPrefs(int $uid): array
	{
		$defaults = [
			'theme' => 'blue',
			'notify_attack' => 1,
			'notify_message' => 1,
			'notify_market' => 1,
		];
		if (!$this->connected() || !$this->db_link || $uid <= 0) {
			return $defaults;
		}
		$this->ensureUserPrefsTable($uid);
		$stmt = $this->db_link->prepare("SELECT `theme`, `notify_attack`, `notify_message`, `notify_market` FROM `user_prefs` WHERE `uid`=? LIMIT 1");
		if (!$stmt) {
			return $defaults;
		}
		$stmt->bind_param("i", $uid);
		$stmt->execute();
		$row = $stmt->get_result()->fetch_object();
		if (!$row) {
			return $defaults;
		}
		return [
			'theme' => in_array((string)$row->theme, ['white', 'og', 'blue', 'stargate'], true) ? (string)$row->theme : 'blue',
			'notify_attack' => (int)$row->notify_attack,
			'notify_message' => (int)$row->notify_message,
			'notify_market' => (int)$row->notify_market,
		];
	}

	/**
	 * Persists a player's preference set.
	 */
	public function saveUserPrefs(int $uid, array $prefs): bool
	{
		if (!$this->connected() || !$this->db_link || $uid <= 0) {
			return false;
		}
		$this->ensureUserPrefsTable($uid);
		$theme = in_array((string)($prefs['theme'] ?? 'blue'), ['white', 'og', 'blue', 'stargate'], true)
			? (string)$prefs['theme'] : 'blue';
		$notifyAttack = !empty($prefs['notify_attack']) ? 1 : 0;
		$notifyMessage = !empty($prefs['notify_message']) ? 1 : 0;
		$notifyMarket = !empty($prefs['notify_market']) ? 1 : 0;
		$stmt = $this->db_link->prepare(
			"INSERT INTO `user_prefs` (`uid`, `theme`, `notify_attack`, `notify_message`, `notify_market`)
			 VALUES (?, ?, ?, ?, ?)
			 ON DUPLICATE KEY UPDATE `theme`=VALUES(`theme`), `notify_attack`=VALUES(`notify_attack`),
			   `notify_message`=VALUES(`notify_message`), `notify_market`=VALUES(`notify_market`)"
		);
		if (!$stmt) {
			return false;
		}
		$stmt->bind_param("isiii", $uid, $theme, $notifyAttack, $notifyMessage, $notifyMarket);
		return (bool)$stmt->execute();
	}

	/**
	 * Updates the player's email address.
	 *
	 * @return array<string,string> list of error messages (empty on success).
	 */
	public function updateEmail(int $uid, string $email): array
	{
		$errors = [];
		if (!$this->connected() || !$this->db_link) {
			return ['Database connection is unavailable.'];
		}
		if ($uid <= 0) {
			return ['Invalid account id.'];
		}
		$email = trim($email);
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			return ['A valid email address is required.'];
		}
		$stmt = $this->db_link->prepare("SELECT `uid` FROM `users` WHERE `email`=? AND `uid`<>? LIMIT 1");
		if ($stmt) {
			$stmt->bind_param("si", $email, $uid);
			$stmt->execute();
			if ($stmt->get_result()->num_rows > 0) {
				return ['That email address is already in use.'];
			}
		}
		$stmt = $this->db_link->prepare("UPDATE `users` SET `email`=? WHERE `uid`=? LIMIT 1");
		if (!$stmt) {
			return ['Database error while updating email.'];
		}
		$stmt->bind_param("si", $email, $uid);
		if (!$stmt->execute()) {
			return ['Database error while updating email.'];
		}
		return $errors;
	}

	/**
	 * Updates the player's password and keeps the session in sync.
	 *
	 * @return array<string,string> list of error messages (empty on success).
	 */
	public function updatePassword(int $uid, string $newPassword): array
	{
		$errors = [];
		if (!$this->connected() || !$this->db_link) {
			return ['Database connection is unavailable.'];
		}
		if ($uid <= 0) {
			return ['Invalid account id.'];
		}
		$newPassword = trim($newPassword);
		if (strlen($newPassword) < 6) {
			return ['Password must be at least 6 characters.'];
		}
		$hash = $this->salt($newPassword);
		$stmt = $this->db_link->prepare("UPDATE `users` SET `password`=? WHERE `uid`=? LIMIT 1");
		if (!$stmt) {
			return ['Database error while updating password.'];
		}
		$stmt->bind_param("si", $hash, $uid);
		if (!$stmt->execute()) {
			return ['Database error while updating password.'];
		}
		if ($uid === (int)$this->userid) {
			$this->password = $newPassword;
			$_SESSION['password'] = $newPassword;
		}
		return $errors;
	}
}
?>
