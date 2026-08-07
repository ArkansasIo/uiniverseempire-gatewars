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
// Base::Admin.class.php
// Staff operations layer for the admin control panel.
//
// Access levels are read from users.alevel (a bitmask, see User::isAdmin()):
//   1 = player, 2 = moderator (reserved), 4 = administrator, 8 = super admin.
// The class degrades gracefully when the database is unavailable so the
// panel can still render with empty/safe values during local testing.

class Admin extends Game
{
	/**
	 * Staff access level bit that grants administrator rights.
	 */
	public const ADMIN_LEVEL = 4;

	/**
	 * Constructor for Admin
	 * @param string $userName Name of user
	 * @param string $password Password of user
	 */
	public function __construct(string $userName = "", string $password = "DoodleCakes and Rofl Sundae4278vsid")
	{
		parent::__construct($userName, $password);
		$this->ensureAdminTables();
	}

	/**
	 * Guards a page so only logged-in staff can continue.
	 * Non-logged users are redirected to the main game login; logged-in
	 * non-staff users receive a 403 page.
	 */
	public function requireAdmin(): void
	{
		if (!$this->loggedIn) {
			header("Location: ../index.php");
			exit;
		}
		if (!$this->isAdmin()) {
			header("HTTP/1.1 403 Forbidden");
			echo "<h1>403 - Access denied</h1><p>Your account does not have staff privileges.</p>";
			exit;
		}
	}

	/**
	 * Creates the admin_log table and ensures the users.banned column and
	 * core settings rows exist. Safe to call on every request.
	 */
	public function ensureAdminTables(): void
	{
		if (!$this->connected() || !$this->db_link) {
			return;
		}
		$this->query("CREATE TABLE IF NOT EXISTS `admin_log` (
			`logID` int(11) NOT NULL AUTO_INCREMENT,
			`uid` int(11) NOT NULL DEFAULT 0,
			`username` varchar(64) NOT NULL DEFAULT '',
			`action` varchar(64) NOT NULL,
			`target_uid` int(11) NOT NULL DEFAULT 0,
			`details` text NOT NULL,
			`ip_address` varchar(45) NOT NULL DEFAULT '',
			`time` varchar(32) NOT NULL,
			PRIMARY KEY (`logID`),
			KEY `idx_admin_log_uid` (`uid`),
			KEY `idx_admin_log_action` (`action`),
			KEY `idx_admin_log_time` (`time`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1");

		$this->query("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `banned` tinyint(1) NOT NULL DEFAULT 0");
	}

	/**
	 * Records a staff action in admin_log.
	 *
	 * @param string $action    Short machine-friendly action slug.
	 * @param mixed  $details   Text or array details to store.
	 * @param int    $targetUid Optional affected player id.
	 */
	public function log(string $action, $details = "", int $targetUid = 0): void
	{
		if (!$this->connected() || !$this->db_link) {
			return;
		}
		$username = (string)($this->userName ?: '');
		$detailsText = is_array($details) || is_object($details)
			? json_encode($details, JSON_UNESCAPED_SLASHES)
			: (string)$details;
		$ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '';
		$stmt = $this->db_link->prepare(
			"INSERT INTO `admin_log` (`uid`, `username`, `action`, `target_uid`, `details`, `ip_address`, `time`)
			 VALUES (?, ?, ?, ?, ?, ?, ?)"
		);
		if (!$stmt) {
			return;
		}
		$now = time();
		$stmt->bind_param("ississs", $this->userid, $username, $action, $targetUid, $detailsText, $ip, $now);
		$stmt->execute();
		$stmt->close();
	}

	/**
	 * Human label for an access level.
	 */
	public static function roleLabel(int $level): string
	{
		if ($level >= 8) {
			return 'Super Admin';
		}
		if ($level >= 4) {
			return 'Admin';
		}
		if ($level >= 2) {
			return 'Moderator';
		}
		return 'Player';
	}

	/**
	 * Selectable access level options for the player editor.
	 */
	public static function accessOptions(): array
	{
		return [
			1   => 'Player (1)',
			2   => 'Moderator (2)',
			4   => 'Admin (4)',
			8   => 'Super Admin (8)',
			255 => 'Full Access (255)',
		];
	}

	/**
	 * Dashboard aggregate stats used on the panel home page.
	 */
	public function dashboardStats(): array
	{
		$stats = [
			'totalPlayers'     => 0,
			'activeToday'      => 0,
			'totalOnHand'      => 0,
			'totalInBank'      => 0,
			'totalNaq'         => 0,
			'totalUnits'       => 0,
			'totalUntrained'   => 0,
			'messageCount'     => 0,
			'actionCount'      => 0,
			'activeListings'   => 0,
			'adminCount'       => 0,
			'bannedCount'      => 0,
		];
		if (!$this->connected() || !$this->db_link) {
			return $stats;
		}

		$q = $this->query("SELECT COUNT(*) AS n FROM `users`");
		$stats['totalPlayers'] = $q ? (int)($q->fetch_object()->n ?? 0) : 0;

		$dayStart = strtotime('today');
		$q = $this->query("SELECT COUNT(*) AS n FROM `users` WHERE `lastLogin` >= " . (int)$dayStart);
		$stats['activeToday'] = $q ? (int)($q->fetch_object()->n ?? 0) : 0;

		$q = $this->query("SELECT IFNULL(SUM(`onHand`),0) AS a, IFNULL(SUM(`inBank`),0) AS b FROM `bank`");
		if ($q && $row = $q->fetch_object()) {
			$stats['totalOnHand'] = (int)($row->a ?? 0);
			$stats['totalInBank'] = (int)($row->b ?? 0);
		}
		$stats['totalNaq'] = $stats['totalOnHand'] + $stats['totalInBank'];

		$q = $this->query("SELECT
			IFNULL(SUM(`untrained`),0) AS untrained,
			IFNULL(SUM(`attack`+`superAttack`+`attackMercs`+`defense`+`superDefense`+`defenseMercs`+`covert`+`superCovert`+`anticovert`+`superAnticovert`+`miners`+`lifers`),0) AS trained
			FROM `units`");
		if ($q && $row = $q->fetch_object()) {
			$stats['totalUntrained'] = (int)($row->untrained ?? 0);
			$stats['totalUnits'] = $stats['totalUntrained'] + (int)($row->trained ?? 0);
		}

		$q = $this->query("SELECT COUNT(*) AS n FROM `messages`");
		$stats['messageCount'] = $q ? (int)($q->fetch_object()->n ?? 0) : 0;

		$q = $this->query("SELECT COUNT(*) AS n FROM `actionlog`");
		$stats['actionCount'] = $q ? (int)($q->fetch_object()->n ?? 0) : 0;

		$q = $this->query("SELECT COUNT(*) AS n FROM `market_listings` WHERE `active`=1");
		$stats['activeListings'] = $q ? (int)($q->fetch_object()->n ?? 0) : 0;

		$q = $this->query("SELECT COUNT(*) AS n FROM `users` WHERE `alevel` >= " . self::ADMIN_LEVEL);
		$stats['adminCount'] = $q ? (int)($q->fetch_object()->n ?? 0) : 0;

		$q = $this->query("SELECT COUNT(*) AS n FROM `users` WHERE `banned`=1");
		$stats['bannedCount'] = $q ? (int)($q->fetch_object()->n ?? 0) : 0;

		return $stats;
	}

	/**
	 * Recently registered accounts for the dashboard feed.
	 */
	public function recentRegistrations(int $limit = 8): array
	{
		$rows = [];
		if (!$this->connected() || !$this->db_link) {
			return $rows;
		}
		$limit = max(1, min(100, $limit));
		$q = $this->query("SELECT `uid`, `uname`, `email`, `alevel`, `lastLogin` FROM `users` ORDER BY `uid` DESC LIMIT " . (int)$limit);
		if ($q) {
			while ($row = $q->fetch_object()) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Most recent staff actions from admin_log.
	 */
	public function recentAdminLog(int $limit = 10): array
	{
		$rows = [];
		if (!$this->connected() || !$this->db_link) {
			return $rows;
		}
		$limit = max(1, min(100, $limit));
		$q = $this->query("SELECT * FROM `admin_log` ORDER BY `logID` DESC LIMIT " . (int)$limit);
		if ($q) {
			while ($row = $q->fetch_object()) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Paginated player listing with optional name/email search.
	 */
	public function players(string $search = "", int $page = 1, int $perPage = 25): array
	{
		$page = max(1, (int)$page);
		$perPage = max(5, min(100, (int)$perPage));
		$offset = ($page - 1) * $perPage;

		$rows = [];
		$total = 0;
		if (!$this->connected() || !$this->db_link) {
			return ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
		}

		$where = "";
		$like = '%' . $search . '%';
		if ($search !== '') {
			$where = " WHERE u.`uname` LIKE ? OR u.`email` LIKE ?";
		}

		$countQuery = "SELECT COUNT(*) AS n FROM `users` u" . $where;
		if ($search !== '') {
			$stmt = $this->db_link->prepare($countQuery);
			if (!$stmt) {
				return ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
			}
			$stmt->bind_param("ss", $like, $like);
			$stmt->execute();
			$total = (int)($stmt->get_result()->fetch_object()->n ?? 0);
		} else {
			$q = $this->query($countQuery);
			$total = $q ? (int)($q->fetch_object()->n ?? 0) : 0;
		}

		$query = "SELECT u.`uid`, u.`uname`, u.`email`, u.`alevel`, u.`banned`, u.`lastLogin`, u.`ip`, u.`allyid`,
		                 IFNULL(b.`onHand`,0) AS onHand, IFNULL(b.`inBank`,0) AS inBank,
		                 IFNULL(ud.`actionTurns`,0) AS actionTurns,
		                 IFNULL(un.`untrained`,0) AS untrained
		          FROM `users` u
		          LEFT JOIN `bank` b ON b.`uid` = u.`uid`
		          LEFT JOIN `userdata` ud ON ud.`uid` = u.`uid`
		          LEFT JOIN `units` un ON un.`uid` = u.`uid`"
		          . $where . "
		          ORDER BY u.`uid` ASC
		          LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;

		if ($search !== '') {
			$stmt = $this->db_link->prepare($query);
			if (!$stmt) {
				return ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
			}
			$stmt->bind_param("ss", $like, $like);
			$stmt->execute();
			$result = $stmt->get_result();
		} else {
			$result = $this->query($query);
		}
		if ($result) {
			while ($row = $result->fetch_object()) {
				$rows[] = $row;
			}
		}

		return ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
	}

	/**
	 * Full profile for one player including economy/military state.
	 */
	public function getPlayer(int $uid): ?object
	{
		if (!$this->connected() || !$this->db_link || $uid <= 0) {
			return null;
		}
		$query = "SELECT u.`uid`, u.`uname`, u.`email`, u.`alevel`, u.`banned`, u.`lastLogin`, u.`ip`, u.`allyid`,
		                 IFNULL(ud.`actionTurns`,0) AS actionTurns, ud.`rid`, ud.`cid`,
		                 IFNULL(b.`onHand`,0) AS onHand, IFNULL(b.`inBank`,0) AS inBank,
		                 IFNULL(un.`untrained`,0) AS untrained,
		                 IFNULL(r.`overall`,0) AS rank,
		                 IFNULL(pr.`metal`,0) AS metal, IFNULL(pr.`crystal`,0) AS crystal,
		                 IFNULL(pr.`deuterium`,0) AS deuterium, IFNULL(pr.`food`,0) AS food,
		                 IFNULL(pr.`water`,0) AS water, IFNULL(pr.`population`,0) AS population,
		                 IFNULL(pr.`energy`,0) AS energy,
		                 IFNULL(rce.`r_name`,'Unknown') AS race
		          FROM `users` u
		          LEFT JOIN `userdata` ud ON ud.`uid` = u.`uid`
		          LEFT JOIN `bank` b ON b.`uid` = u.`uid`
		          LEFT JOIN `units` un ON un.`uid` = u.`uid`
		          LEFT JOIN `rank` r ON r.`uid` = u.`uid`
		          LEFT JOIN `player_resources` pr ON pr.`uid` = u.`uid`
		          LEFT JOIN `race` rce ON rce.`rid` = ud.`rid`
		          WHERE u.`uid` = ?
		          LIMIT 1";
		$stmt = $this->db_link->prepare($query);
		if (!$stmt) {
			return null;
		}
		$stmt->bind_param("i", $uid);
		$stmt->execute();
		$q = $stmt->get_result();
		return $q && $q->num_rows ? $q->fetch_object() : null;
	}

	/**
	 * Updates core account fields for a player.
	 *
	 * @return array<string,string> list of error messages (empty on success).
	 */
	public function updatePlayerAccount(int $uid, string $email, int $alevel, int $rid, int $cid): array
	{
		$errors = [];
		if (!$this->connected() || !$this->db_link) {
			return ['Database connection is unavailable.'];
		}
		if ($uid <= 0) {
			return ['Invalid player id.'];
		}
		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$errors[] = 'A valid email address is required.';
		}
		if ($alevel < 0) {
			$errors[] = 'Access level cannot be negative.';
		}
		if (count($errors)) {
			return $errors;
		}

		$stmt = $this->db_link->prepare("UPDATE `users` SET `email`=?, `alevel`=? WHERE `uid`=? LIMIT 1");
		if (!$stmt) {
			return ['Database error while updating account.'];
		}
		$stmt->bind_param("sii", $email, $alevel, $uid);
		$stmt->execute();

		$stmt = $this->db_link->prepare("UPDATE `userdata` SET `rid`=?, `cid`=? WHERE `uid`=? LIMIT 1");
		if (!$stmt) {
			return ['Database error while updating account.'];
		}
		$stmt->bind_param("iii", $rid, $cid, $uid);
		$stmt->execute();

		$this->log('account.update', [
			'email'  => $email,
			'alevel' => $alevel,
			'rid'    => $rid,
			'cid'    => $cid,
		], $uid);
		return $errors;
	}

	/**
	 * Sets a player's access level directly.
	 */
	public function setAccessLevel(int $uid, int $level): bool
	{
		if (!$this->connected() || !$this->db_link || $uid <= 0) {
			return false;
		}
		$level = max(0, (int)$level);
		$stmt = $this->db_link->prepare("UPDATE `users` SET `alevel`=? WHERE `uid`=? LIMIT 1");
		if (!$stmt) {
			return false;
		}
		$stmt->bind_param("ii", $level, $uid);
		$ok = $stmt->execute();
		if ($ok) {
			$this->log('access.set', ['alevel' => $level], $uid);
		}
		return (bool)$ok;
	}

	/**
	 * Bans or unbans a player.
	 */
	public function setBanned(int $uid, bool $banned): bool
	{
		if (!$this->connected() || !$this->db_link || $uid <= 0) {
			return false;
		}
		if ($uid === (int)$this->userid) {
			return false;
		}
		$flag = $banned ? 1 : 0;
		$stmt = $this->db_link->prepare("UPDATE `users` SET `banned`=? WHERE `uid`=? LIMIT 1");
		if (!$stmt) {
			return false;
		}
		$stmt->bind_param("ii", $flag, $uid);
		$ok = $stmt->execute();
		if ($ok) {
			$this->log($banned ? 'player.ban' : 'player.unban', [], $uid);
		}
		return (bool)$ok;
	}

	/**
	 * Grants Naquadah to a player's on-hand balance.
	 */
	public function grantNaq(int $uid, int $amount): bool
	{
		if (!$this->connected() || !$this->db_link || $uid <= 0 || $amount < 0) {
			return false;
		}
		$this->query("INSERT IGNORE INTO `bank` (`uid`, `onHand`, `inBank`) VALUES (" . (int)$uid . ", 0, 0)");
		$stmt = $this->db_link->prepare("UPDATE `bank` SET `onHand`=`onHand` + ? WHERE `uid`=? LIMIT 1");
		if (!$stmt) {
			return false;
		}
		$stmt->bind_param("ii", $amount, $uid);
		$ok = $stmt->execute();
		if ($ok) {
			$this->log('grant.naquadah', ['amount' => $amount], $uid);
		}
		return (bool)$ok;
	}

	/**
	 * Grants action turns to a player.
	 */
	public function grantTurns(int $uid, int $amount): bool
	{
		if (!$this->connected() || !$this->db_link || $uid <= 0 || $amount < 0) {
			return false;
		}
		$stmt = $this->db_link->prepare("UPDATE `userdata` SET `actionTurns`=`actionTurns` + ? WHERE `uid`=? LIMIT 1");
		if (!$stmt) {
			return false;
		}
		$stmt->bind_param("ii", $amount, $uid);
		$ok = $stmt->execute();
		if ($ok) {
			$this->log('grant.turns', ['amount' => $amount], $uid);
		}
		return (bool)$ok;
	}

	/**
	 * Grants untrained units to a player.
	 */
	public function grantUntrained(int $uid, int $amount): bool
	{
		if (!$this->connected() || !$this->db_link || $uid <= 0 || $amount < 0) {
			return false;
		}
		$stmt = $this->db_link->prepare("UPDATE `units` SET `untrained`=`untrained` + ? WHERE `uid`=? LIMIT 1");
		if (!$stmt) {
			return false;
		}
		$stmt->bind_param("ii", $amount, $uid);
		$ok = $stmt->execute();
		if ($ok) {
			$this->log('grant.untrained', ['amount' => $amount], $uid);
		}
		return (bool)$ok;
	}

	/**
	 * Sets strategic resource levels for a player.
	 */
	public function setPlayerResources(int $uid, array $res): bool
	{
		if (!$this->connected() || !$this->db_link || $uid <= 0) {
			return false;
		}
		$allowed = ['metal', 'crystal', 'deuterium', 'food', 'water', 'population', 'energy'];
		$sets = [];
		$values = [];
		$types = '';
		foreach ($allowed as $key) {
			if (array_key_exists($key, $res)) {
				$sets[] = "`" . $key . "`=?";
				$values[] = max(0, (int)$res[$key]);
				$types .= 'i';
			}
		}
		if (count($sets) === 0) {
			return false;
		}
		$this->query("INSERT IGNORE INTO `player_resources` (`uid`) VALUES (" . (int)$uid . ")");
		$values[] = $uid;
		$types .= 'i';

		$query = "UPDATE `player_resources` SET " . implode(", ", $sets) . " WHERE `uid`=? LIMIT 1";
		$stmt = $this->db_link->prepare($query);
		if (!$stmt) {
			return false;
		}
		$stmt->bind_param($types, ...$values);
		$ok = $stmt->execute();
		if ($ok) {
			$this->log('resources.set', $res, $uid);
		}
		return (bool)$ok;
	}

	/**
	 * Resets a player's password using the game's salted hash scheme.
	 */
	public function resetPassword(int $uid, string $newPassword): bool
	{
		if (!$this->connected() || !$this->db_link || $uid <= 0) {
			return false;
		}
		$newPassword = trim($newPassword);
		if (strlen($newPassword) < 6) {
			return false;
		}
		$hash = $this->salt($newPassword);
		$stmt = $this->db_link->prepare("UPDATE `users` SET `password`=? WHERE `uid`=? LIMIT 1");
		if (!$stmt) {
			return false;
		}
		$stmt->bind_param("si", $hash, $uid);
		$ok = $stmt->execute();
		if ($ok) {
			$this->log('password.reset', [], $uid);
		}
		return (bool)$ok;
	}

	/**
	 * Sends a system message to every player account.
	 */
	public function broadcastMessage(string $subject, string $body): bool
	{
		if (!$this->connected() || !$this->db_link) {
			return false;
		}
		$subject = trim($subject);
		$body = trim($body);
		if ($subject === '' || $body === '') {
			return false;
		}
		$fromUid = (int)$this->userid;
		$now = time();
		$stmt = $this->db_link->prepare(
			"			 INSERT INTO `messages` (`fromUID`, `toUID`, `subject`, `message`, `timeSent`, `isRead`, `isDeleted`, `replyToMid`)
			 SELECT ?, `uid`, ?, ?, ?, 0, 0, 0 FROM `users`"
		);
		if (!$stmt) {
			return false;
		}
		$stmt->bind_param("issi", $fromUid, $subject, $body, $now);
		$ok = $stmt->execute();
		if ($ok) {
			$this->log('broadcast.send', ['subject' => $subject], 0);
		}
		return (bool)$ok;
	}

	/**
	 * Active market listings for moderation.
	 */
	public function marketListings(bool $activeOnly = true, int $limit = 100): array
	{
		$rows = [];
		if (!$this->connected() || !$this->db_link) {
			return $rows;
		}
		$where = $activeOnly ? " WHERE ml.`active`=1" : "";
		$limit = max(1, min(500, (int)$limit));
		$q = $this->query("SELECT ml.`lid`, ml.`uid`, u.`uname`, ml.`resource`, ml.`amount`, ml.`price_per`, ml.`created`, ml.`active`,
		                          (ml.`amount` * ml.`price_per`) AS total_cost
		                   FROM `market_listings` ml
		                   INNER JOIN `users` u ON u.`uid` = ml.`uid`"
		                   . $where . "
		                   ORDER BY ml.`lid` DESC LIMIT " . $limit);
		if ($q) {
			while ($row = $q->fetch_object()) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Admin moderation cancel for a market listing (refunds the seller).
	 */
	public function cancelListing(int $lid): bool
	{
		if (!$this->connected() || !$this->db_link || $lid <= 0) {
			return false;
		}
		$stmt = $this->db_link->prepare("SELECT * FROM `market_listings` WHERE `lid`=? AND `active`=1 LIMIT 1");
		if (!$stmt) {
			return false;
		}
		$stmt->bind_param("i", $lid);
		$stmt->execute();
		$listing = $stmt->get_result()->fetch_object();
		if (!$listing) {
			return false;
		}

		$resource = (string)$listing->resource;
		$amount = (int)$listing->amount;
		$uid = (int)$listing->uid;
		if ($resource === 'naquadah') {
			$stmt = $this->db_link->prepare("UPDATE `bank` SET `onHand`=`onHand` + ? WHERE `uid`=? LIMIT 1");
			if ($stmt) {
				$stmt->bind_param("ii", $amount, $uid);
				$stmt->execute();
			}
		} else {
			$safe = in_array($resource, ['metal', 'crystal', 'deuterium'], true) ? $resource : null;
			if ($safe) {
				$stmt = $this->db_link->prepare("INSERT INTO `player_resources` (`uid`, `" . $safe . "`) VALUES (?, ?)
					ON DUPLICATE KEY UPDATE `" . $safe . "`=`" . $safe . "` + ?");
				if ($stmt) {
					$stmt->bind_param("iii", $uid, $amount, $amount);
					$stmt->execute();
				}
			}
		}

		$stmt = $this->db_link->prepare("UPDATE `market_listings` SET `active`=0 WHERE `lid`=? LIMIT 1");
		if (!$stmt) {
			return false;
		}
		$stmt->bind_param("i", $lid);
		$ok = $stmt->execute();
		if ($ok) {
			$this->log('market.cancel', ['lid' => $lid, 'resource' => $resource, 'amount' => $amount], $uid);
		}
		return (bool)$ok;
	}

	/**
	 * Browsable action log (combat/system events).
	 */
	public function actionLogBrowse(string $type = "", int $limit = 100): array
	{
		$rows = [];
		if (!$this->connected() || !$this->db_link) {
			return $rows;
		}
		$where = "";
		if ($type !== '' && in_array($type, ['attack', 'raid', 'spy', 'sab'], true)) {
			$where = " WHERE al.`type`='" . $type . "'";
		}
		$limit = max(1, min(500, (int)$limit));
		$q = $this->query("SELECT al.*, u.`uname` AS attacker
		                   FROM `actionlog` al
		                   LEFT JOIN `users` u ON u.`uid` = al.`uid`"
		                   . $where . "
		                   ORDER BY al.`actID` DESC LIMIT " . $limit);
		if ($q) {
			while ($row = $q->fetch_object()) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Application settings merged with runtime server info.
	 */
	public function settings(): array
	{
		$settings = [];
		if ($this->connected() && $this->db_link) {
			$q = $this->query("SELECT `setting_key`, `setting_value` FROM `app_settings` ORDER BY `setting_key` ASC");
			if ($q) {
				while ($row = $q->fetch_object()) {
					$settings[(string)$row->setting_key] = (string)$row->setting_value;
				}
			}
		}
		return $settings;
	}

	/**
	 * Reads a single setting with a fallback default.
	 */
	public function getSetting(string $key, string $default = ''): string
	{
		$all = $this->settings();
		return isset($all[$key]) ? $all[$key] : $default;
	}

	/**
	 * Writes a single setting into app_settings.
	 */
	public function setSetting(string $key, string $value): bool
	{
		if (!$this->connected() || !$this->db_link || $key === '') {
			return false;
		}
		$stmt = $this->db_link->prepare(
			"INSERT INTO `app_settings` (`setting_key`, `setting_value`) VALUES (?, ?)
			 ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`)"
		);
		if (!$stmt) {
			return false;
		}
		$stmt->bind_param("ss", $key, $value);
		$ok = $stmt->execute();
		if ($ok) {
			$this->log('settings.update', ['key' => $key], 0);
		}
		return (bool)$ok;
	}

	/**
	 * Server/runtime information for the settings screen.
	 */
	public function serverInfo(): array
	{
		return [
			'game_version' => defined('SGW_VERSION') ? (string)SGW_VERSION : '',
			'php_version'  => PHP_VERSION,
			'php_sapi'     => PHP_SAPI,
			'db_connected' => $this->connected() ? 'Yes' : 'No',
			'db_server'    => (string)$this->db_server,
			'db_name'      => (string)$this->db_name,
			'server_time'  => date('Y-m-d H:i:s'),
			'unix_time'    => time(),
		];
	}

	/**
	 * Runs one full game turn tick for every player (or a single player).
	 * Delegates to the GameTick engine so the panel and the cron share one path.
	 *
	 * @return array{ok:bool,message:string,processed:int,intents:string[]}
	 */
	public function runGameTick(array $options = []): array
	{
		$this->loadTickEngine();
		$tick = new GameTick();
		$result = $tick->run($options);
		if (!empty($result['error'])) {
			return ['ok' => false, 'message' => (string)$result['error'], 'processed' => 0, 'intents' => []];
		}
		$intents = [
			'income_total' => (int)($result['income_total'] ?? 0),
			'upkeep_total' => (int)($result['upkeep_total'] ?? 0),
			'turns_granted' => (int)($result['turns_granted'] ?? 0),
			'untrained_granted' => (int)($result['untrained_granted'] ?? 0),
			'rank_recalc' => (int)($result['rank_recalc'] ?? 0),
		];
		$this->log('tick.run', $intents, 0);
		return ['ok' => true, 'message' => 'Game tick completed.', 'processed' => (int)($result['processed'] ?? 0), 'intents' => $intents];
	}

	/**
	 * Status of the turn-tick engine (last run + configured knobs).
	 */
	public function tickStatus(): array
	{
		$this->loadTickEngine();
		$tick = new GameTick();
		return $tick->tickStatus();
	}

	/**
	 * Wipes every piece of game data for a player and re-grants the
	 * fresh-start package (matches User::addUser), keeping the account row.
	 *
	 * @return array<string> list of error messages (empty on success).
	 */
	public function resetPlayer(int $uid): array
	{
		if (!$this->connected() || !$this->db_link) {
			return ['Database connection is unavailable.'];
		}
		if ($uid <= 0) {
			return ['Invalid player id.'];
		}
		$wipe = ['bank', 'units', 'power', 'rank', 'planets', 'technology', 'player_resources'];
		foreach ($wipe as $table) {
			$stmt = $this->db_link->prepare("DELETE FROM `" . $table . "` WHERE `uid`=?");
			if (!$stmt) {
				continue;
			}
			$stmt->bind_param("i", $uid);
			$stmt->execute();
		}

		$fresh = [
			['bank (uid, inbank, onHand) VALUES (?, 0, 350000)', 'i'],
			['player_resources (uid, metal, crystal, deuterium, food, water, population, energy, last_tick_at) VALUES (?, 11200, 19000, 16000, 80000, 70000, 150000, 15000, 0)', 'i'],
			['units (uid, attack, superAttack, attackMercs, defense, superDefense, defenseMercs, untrained, miners, lifers, covert, superCovert, anticovert, superAnticovert) VALUES (?, 0, 0, 0, 0, 0, 0, 250, 0, 0, 0, 0, 0, 0)', 'i'],
			['technology (uid, income, unitProd, uppl, cov_lvl, anti_lvl, covert, anticovert, attack, defense, auEffect, auRes, auSteal, acuEffect, acuRes, duSteal, cuEffect, cuRes, duEffect, duRes, ascend, galaxy, pDef, puCap, pmCap) VALUES (?, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)', 'i'],
			['power (uid, overall, mil_atk, mil_def, mil_cov, mil_anti, mil_total) VALUES (?, 0, 0, 0, 0, 0, 0)', 'i'],
			['rank (uid, overall, mil_atk, mil_def, mil_cov, mil_anti, mil_total) VALUES (?, 0, 0, 0, 0, 0, 0)', 'i'],
			['planets (uid, text, plnt_name, income_bonus, up_bonus, isHome, pid, plnt_size) VALUES (?, \'\', \'Home Planet\', 0, 0, 1, 0, 0)', 'i'],
		];
		foreach ($fresh as $def) {
			$stmt = $this->db_link->prepare("INSERT INTO `" . $def[0]);
			if (!$stmt) {
				return ['Database error while resetting player.'];
			}
			$stmt->bind_param($def[1], $uid);
			$stmt->execute();
		}

		$stmt = $this->db_link->prepare("UPDATE `userdata` SET `actionTurns`=?, `progress`=0, `cid`=0 WHERE `uid`=? LIMIT 1");
		if (!$stmt) {
			return ['Database error while resetting player.'];
		}
		$stmt->bind_param("ii", 250, $uid);
		$ok = $stmt->execute();
		if (!$ok) {
			return ['Database error while resetting player.'];
		}
		$this->log('player.reset', [], $uid);
		return [];
	}

	/**
	 * Deletes a player account and all associated game data.
	 *
	 * @return array<string> list of error messages (empty on success).
	 */
	public function deletePlayer(int $uid): array
	{
		if (!$this->connected() || !$this->db_link) {
			return ['Database connection is unavailable.'];
		}
		if ($uid <= 0) {
			return ['Invalid player id.'];
		}
		if ($uid === (int)$this->userid) {
			return ['You cannot delete your own account.'];
		}
		$stmt = $this->db_link->prepare("SELECT `uid` FROM `users` WHERE `uid`=? LIMIT 1");
		if ($stmt) {
			$stmt->bind_param("i", $uid);
			$stmt->execute();
			if (!$stmt->get_result()->fetch_object()) {
				return ['Player does not exist.'];
			}
		}
		$wipe = ['bank', 'userdata', 'units', 'power', 'rank', 'planets', 'technology', 'player_resources', 'user_prefs'];
		foreach ($wipe as $table) {
			$stmt = $this->db_link->prepare("DELETE FROM `" . $table . "` WHERE `uid`=?");
			if (!$stmt) {
				continue;
			}
			$stmt->bind_param("i", $uid);
			$stmt->execute();
		}
		$stmt = $this->db_link->prepare("DELETE FROM `messages` WHERE `fromUID`=? OR `toUID`=?");
		if ($stmt) {
			$stmt->bind_param("ii", $uid, $uid);
			$stmt->execute();
		}
		$stmt = $this->db_link->prepare("DELETE FROM `users` WHERE `uid`=? LIMIT 1");
		if (!$stmt) {
			return ['Database error while deleting player.'];
		}
		$stmt->bind_param("i", $uid);
		$ok = $stmt->execute();
		if (!$ok) {
			return ['Database error while deleting player.'];
		}
		$this->log('player.delete', [], $uid);
		return [];
	}

	/**
	 * Publishes a global in-game announcement banner.
	 */
	public function publishAnnouncement(string $title, string $body): bool
	{
		if (!$this->connected() || !$this->db_link) {
			return false;
		}
		$title = trim($title);
		$body = trim($body);
		if ($title === '' && $body === '') {
			return false;
		}
		$ok = $this->setSetting('announcement.title', $title);
		$ok = $this->setSetting('announcement.body', $body) && $ok;
		$ok = $this->setSetting('announcement.active', '1') && $ok;
		return (bool)$ok;
	}

	/**
	 * Hides the active announcement banner.
	 */
	public function clearAnnouncement(): bool
	{
		if (!$this->connected() || !$this->db_link) {
			return false;
		}
		return $this->setSetting('announcement.active', '0');
	}

	/**
	 * Current announcement banner state.
	 */
	public function announcementStatus(): array
	{
		return [
			'active' => $this->getSetting('announcement.active', '0') === '1',
			'title' => $this->getSetting('announcement.title', ''),
			'body' => $this->getSetting('announcement.body', ''),
		];
	}

	/**
	 * Current maintenance-mode state.
	 */
	public function maintenanceStatus(): array
	{
		return [
			'enabled' => $this->getSetting('maintenance.enabled', '0') === '1',
			'message' => $this->getSetting('maintenance.message', ''),
		];
	}

	/**
	 * Enables or disables maintenance mode. Staff accounts are unaffected.
	 */
	public function setMaintenance(bool $enabled, string $message): bool
	{
		if (!$this->connected() || !$this->db_link) {
			return false;
		}
		$ok = $this->setSetting('maintenance.enabled', $enabled ? '1' : '0');
		if (trim($message) !== '') {
			$ok = $this->setSetting('maintenance.message', trim($message)) && $ok;
		}
		return (bool)$ok;
	}

	/**
	 * Applies a grant to many players at once.
	 *
	 * @param string   $kind   One of: naq, turns, untrained.
	 * @param int[]    $uids   Player ids to receive the grant.
	 * @param int      $amount Amount per player.
	 * @return array{ok:int,failed:int,kind:string,amount:int}
	 */
	public function massGrant(string $kind, array $uids, int $amount): array
	{
		$okCount = 0;
		$failed = 0;
		foreach ($uids as $uid) {
			$uid = (int)$uid;
			if ($uid <= 0) {
				$failed++;
				continue;
			}
			if ($kind === 'naq') {
				$res = $this->grantNaq($uid, $amount);
			} elseif ($kind === 'turns') {
				$res = $this->grantTurns($uid, $amount);
			} elseif ($kind === 'untrained') {
				$res = $this->grantUntrained($uid, $amount);
			} else {
				$res = false;
			}
			if ($res) {
				$okCount++;
			} else {
				$failed++;
			}
		}
		$this->log('mass.grant', ['kind' => $kind, 'amount' => $amount, 'ok' => $okCount, 'failed' => $failed], 0);
		return ['ok' => $okCount, 'failed' => $failed, 'kind' => $kind, 'amount' => $amount];
	}

	/**
	 * Returns every active player uid (all access levels).
	 */
	public function allPlayerUids(): array
	{
		$uids = [];
		if (!$this->connected() || !$this->db_link) {
			return $uids;
		}
		$q = $this->query("SELECT `uid` FROM `users` ORDER BY `uid` ASC");
		if ($q) {
			while ($row = $q->fetch_object()) {
				$uids[] = (int)$row->uid;
			}
		}
		return $uids;
	}

	/**
	 * Staff accounts (moderator access level 2 or higher).
	 */
	public function staffAccounts(): array
	{
		$rows = [];
		if (!$this->connected() || !$this->db_link) {
			return $rows;
		}
		$q = $this->query("SELECT `uid`, `uname`, `email`, `alevel`, `banned`, `lastLogin`
		                   FROM `users` WHERE `alevel` >= 2
		                   ORDER BY `alevel` DESC, `uname` ASC");
		if ($q) {
			while ($row = $q->fetch_object()) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Accounts currently flagged as banned.
	 */
	public function bannedPlayers(): array
	{
		$rows = [];
		if (!$this->connected() || !$this->db_link) {
			return $rows;
		}
		$q = $this->query("SELECT u.`uid`, u.`uname`, u.`email`, u.`alevel`, u.`lastLogin`
		                   FROM `users` u WHERE u.`banned` = 1
		                   ORDER BY u.`uid` ASC");
		if ($q) {
			while ($row = $q->fetch_object()) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Universe-wide economy totals and the richest empires.
	 */
	public function economyOverview(): array
	{
		$overview = [
			'totalOnHand'    => 0,
			'totalInBank'    => 0,
			'totalNaq'       => 0,
			'totalUntrained' => 0,
			'playerCount'    => 0,
			'topPlayers'     => [],
		];
		if (!$this->connected() || !$this->db_link) {
			return $overview;
		}
		$q = $this->query("SELECT IFNULL(SUM(b.`onHand`),0) AS onHand, IFNULL(SUM(b.`inBank`),0) AS inBank,
		                          COUNT(DISTINCT u.`uid`) AS players
		                   FROM `users` u
		                   LEFT JOIN `bank` b ON b.`uid` = u.`uid`");
		if ($q && $row = $q->fetch_object()) {
			$overview['totalOnHand'] = (int)$row->onHand;
			$overview['totalInBank'] = (int)$row->inBank;
			$overview['totalNaq']    = (int)$row->onHand + (int)$row->inBank;
			$overview['playerCount'] = (int)$row->players;
		}
		$q = $this->query("SELECT IFNULL(SUM(`untrained`),0) AS untrained FROM `units`");
		if ($q && $row = $q->fetch_object()) {
			$overview['totalUntrained'] = (int)$row->untrained;
		}
		$q = $this->query("SELECT u.`uid`, u.`uname`, IFNULL(b.`onHand`,0) AS onHand, IFNULL(b.`inBank`,0) AS inBank,
		                          (IFNULL(b.`onHand`,0) + IFNULL(b.`inBank`,0)) AS total
		                   FROM `users` u
		                   LEFT JOIN `bank` b ON b.`uid` = u.`uid`
		                   ORDER BY total DESC LIMIT 10");
		if ($q) {
			while ($row = $q->fetch_object()) {
				$overview['topPlayers'][] = $row;
			}
		}
		return $overview;
	}

	/**
	 * Recent daily economy snapshots (app_daily_economy_metrics).
	 */
	public function dailyEconomyMetrics(int $limit = 30): array
	{
		$rows = [];
		if (!$this->connected() || !$this->db_link) {
			return $rows;
		}
		$limit = max(1, min(365, (int)$limit));
		$q = $this->query("SELECT * FROM `app_daily_economy_metrics`
		                   ORDER BY `metric_date` DESC LIMIT " . (int)$limit);
		if ($q) {
			while ($row = $q->fetch_object()) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Writes today's universe totals into app_daily_economy_metrics.
	 * Upserts on metric_date so it is safe to run more than once per day.
	 */
	public function recordEconomySnapshot(): bool
	{
		if (!$this->connected() || !$this->db_link) {
			return false;
		}
		$query = "INSERT INTO `app_daily_economy_metrics`
			(`metric_date`, `total_players`, `total_onhand`, `total_inbank`, `total_untrained`, `total_attack`, `total_defense`)
			SELECT CURDATE(),
			       COUNT(DISTINCT u.`uid`),
			       IFNULL(SUM(b.`onHand`),0),
			       IFNULL(SUM(b.`inBank`),0),
			       IFNULL(SUM(un.`untrained`),0),
			       IFNULL(SUM(un.`attack` + un.`superAttack` + un.`attackMercs`),0),
			       IFNULL(SUM(un.`defense` + un.`superDefense` + un.`defenseMercs`),0)
			FROM `users` u
			LEFT JOIN `bank` b ON b.`uid` = u.`uid`
			LEFT JOIN `units` un ON un.`uid` = u.`uid`
			ON DUPLICATE KEY UPDATE
				total_players   = VALUES(total_players),
				total_onhand    = VALUES(total_onhand),
				total_inbank    = VALUES(total_inbank),
				total_untrained = VALUES(total_untrained),
				total_attack    = VALUES(total_attack),
				total_defense   = VALUES(total_defense)";
		$ok = (bool)$this->query($query);
		if ($ok) {
			$this->log('economy.snapshot', ['date' => date('Y-m-d')], 0);
		}
		return $ok;
	}

	/**
	 * Planet census: totals, home worlds, colonies, distribution by race
	 * and the largest planets in the universe.
	 */
	public function planetCensus(): array
	{
		$census = ['total' => 0, 'home' => 0, 'colonies' => 0, 'byRace' => [], 'largest' => []];
		if (!$this->connected() || !$this->db_link) {
			return $census;
		}
		$q = $this->query("SELECT COUNT(*) AS n, IFNULL(SUM(`isHome`),0) AS home FROM `planets`");
		if ($q && $row = $q->fetch_object()) {
			$census['total'] = (int)$row->n;
			$census['home']  = (int)$row->home;
			$census['colonies'] = $census['total'] - $census['home'];
		}
		$q = $this->query("SELECT r.`r_name`, COUNT(p.`pid`) AS planets
		                   FROM `planets` p
		                   LEFT JOIN `userdata` ud ON ud.`uid` = p.`uid`
		                   LEFT JOIN `race` r ON r.`rid` = ud.`rid`
		                   GROUP BY r.`rid`, r.`r_name`
		                   ORDER BY planets DESC");
		if ($q) {
			while ($row = $q->fetch_object()) {
				$census['byRace'][] = $row;
			}
		}
		$q = $this->query("SELECT p.`pid`, p.`plnt_name`, p.`plnt_size`, p.`isHome`, u.`uid`, u.`uname`, r.`r_name`
		                   FROM `planets` p
		                   LEFT JOIN `users` u ON u.`uid` = p.`uid`
		                   LEFT JOIN `userdata` ud ON ud.`uid` = p.`uid`
		                   LEFT JOIN `race` r ON r.`rid` = ud.`rid`
		                   ORDER BY p.`plnt_size` DESC LIMIT 10");
		if ($q) {
			while ($row = $q->fetch_object()) {
				$census['largest'][] = $row;
			}
		}
		return $census;
	}

	/**
	 * Universe military totals and the strongest fleets.
	 */
	public function militaryOverview(): array
	{
		$overview = [
			'total' => 0, 'total_atk' => 0, 'total_def' => 0, 'total_cov' => 0, 'total_anti' => 0,
			'topArmies' => [],
		];
		if (!$this->connected() || !$this->db_link) {
			return $overview;
		}
		$q = $this->query("SELECT IFNULL(SUM(`mil_atk`),0) AS a, IFNULL(SUM(`mil_def`),0) AS d,
		                          IFNULL(SUM(`mil_cov`),0) AS c, IFNULL(SUM(`mil_anti`),0) AS an,
		                          IFNULL(SUM(`mil_total`),0) AS t FROM `power`");
		if ($q && $row = $q->fetch_object()) {
			$overview['total_atk']  = (int)$row->a;
			$overview['total_def']  = (int)$row->d;
			$overview['total_cov']  = (int)$row->c;
			$overview['total_anti'] = (int)$row->an;
			$overview['total']      = (int)$row->t;
		}
		$q = $this->query("SELECT u.`uid`, u.`uname`, p.`mil_atk`, p.`mil_def`, p.`mil_cov`, p.`mil_anti`, p.`mil_total`
		                   FROM `power` p
		                   INNER JOIN `users` u ON u.`uid` = p.`uid`
		                   ORDER BY p.`mil_total` DESC LIMIT 10");
		if ($q) {
			while ($row = $q->fetch_object()) {
				$overview['topArmies'][] = $row;
			}
		}
		return $overview;
	}

	/**
	 * Full race catalog with per-race player counts.
	 */
	public function raceCatalog(): array
	{
		$rows = [];
		if (!$this->connected() || !$this->db_link) {
			return $rows;
		}
		$q = $this->query("SELECT r.`rid`, r.`r_name`, r.`income_bonus`, r.`up_bonus`, r.`r_group`, COUNT(ud.`uid`) AS players
		                   FROM `race` r
		                   LEFT JOIN `userdata` ud ON ud.`rid` = r.`rid`
		                   GROUP BY r.`rid`, r.`r_name`, r.`income_bonus`, r.`up_bonus`, r.`r_group`
		                   ORDER BY r.`rid` ASC");
		if ($q) {
			while ($row = $q->fetch_object()) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Unit catalog browser with optional category filter.
	 */
	public function unitCatalog(string $category = ''): array
	{
		$rows = [];
		if (!$this->connected() || !$this->db_link) {
			return $rows;
		}
		$where = "";
		$params = [];
		$types = '';
		if ($category !== '' && in_array($category, ['military', 'civilian', 'government'], true)) {
			$where = " WHERE `category`=?";
			$params[] = $category;
			$types = 's';
		}
		$query = "SELECT * FROM `unit_catalog`" . $where . "
		          ORDER BY `category` ASC, `tier` ASC, `unit_id` ASC LIMIT 500";
		$stmt = $this->db_link->prepare($query);
		if (!$stmt) {
			return $rows;
		}
		if ($params) {
			$stmt->bind_param($types, ...$params);
		}
		$stmt->execute();
		$result = $stmt->get_result();
		if ($result) {
			while ($row = $result->fetch_object()) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Armory weapon catalog joined with race names.
	 */
	public function weaponCatalog(): array
	{
		$rows = [];
		if (!$this->connected() || !$this->db_link) {
			return $rows;
		}
		$q = $this->query("SELECT a.`wid`, a.`rid`, r.`r_name`, a.`isDefense`, a.`cash_cost`, a.`unit_cost`,
		                          a.`weaponName`, a.`weaponPower`, a.`requireTrained`
		                   FROM `armory` a
		                   LEFT JOIN `race` r ON r.`rid` = a.`rid`
		                   ORDER BY a.`rid` ASC, a.`isDefense` ASC, a.`wid` ASC LIMIT 500");
		if ($q) {
			while ($row = $q->fetch_object()) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Background job list from app_server_jobs.
	 */
	public function serverJobs(int $limit = 50): array
	{
		$rows = [];
		if (!$this->connected() || !$this->db_link) {
			return $rows;
		}
		$limit = max(1, min(500, (int)$limit));
		$q = $this->query("SELECT * FROM `app_server_jobs` ORDER BY `id` DESC LIMIT " . (int)$limit);
		if ($q) {
			while ($row = $q->fetch_object()) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Audit trail browse from app_audit_log with optional module filter.
	 */
	public function auditLog(int $limit = 100, string $module = ''): array
	{
		$rows = [];
		if (!$this->connected() || !$this->db_link) {
			return $rows;
		}
		$limit = max(1, min(500, (int)$limit));
		$where = "";
		$params = [];
		$types = '';
		if ($module !== '') {
			$where = " WHERE `module_name`=?";
			$params[] = $module;
			$types = 's';
		}
		$query = "SELECT * FROM `app_audit_log`" . $where . " ORDER BY `id` DESC LIMIT " . (int)$limit;
		$stmt = $this->db_link->prepare($query);
		if (!$stmt) {
			return $rows;
		}
		if ($params) {
			$stmt->bind_param($types, ...$params);
		}
		$stmt->execute();
		$result = $stmt->get_result();
		if ($result) {
			while ($row = $result->fetch_object()) {
				$rows[] = $row;
			}
		}
		return $rows;
	}

	/**
	 * Distinct audit module names for the audit-log filter.
	 */
	public function auditModules(): array
	{
		$modules = [];
		if (!$this->connected() || !$this->db_link) {
			return $modules;
		}
		$q = $this->query("SELECT DISTINCT `module_name` FROM `app_audit_log` ORDER BY `module_name` ASC LIMIT 200");
		if ($q) {
			while ($row = $q->fetch_object()) {
				$modules[] = (string)$row->module_name;
			}
		}
		return $modules;
	}

	/**
	 * Extended runtime + database information for the server screen.
	 */
	public function serverInfoExtended(): array
	{
		$info = $this->serverInfo();
		$info['php_ini_file'] = php_ini_loaded_file() ?: 'n/a';
		$info['memory_limit'] = (string)ini_get('memory_limit');
		$info['max_execution_time'] = (string)ini_get('max_execution_time') . 's';
		$info['upload_max_filesize'] = (string)ini_get('upload_max_filesize');
		$extensions = [];
		foreach (['mysqli', 'pdo', 'mysqlnd', 'json', 'curl', 'gd', 'mbstring', 'openssl', 'zip'] as $ext) {
			$extensions[] = $ext . (extension_loaded($ext) ? '' : ' (missing)');
		}
		$info['key_extensions'] = implode(', ', $extensions);
		$info['db_size'] = 'n/a';
		$info['table_count'] = 0;
		$info['view_count'] = 0;
		if ($this->connected() && $this->db_link) {
			$dbName = (string)$this->db_name;
			$stmt = $this->db_link->prepare(
				"SELECT IFNULL(SUM(`data_length` + `index_length`),0) AS sz,
				        IFNULL(SUM(`table_type`='BASE TABLE'),0) AS tbls,
				        IFNULL(SUM(`table_type`='VIEW'),0) AS views
				 FROM `information_schema`.`TABLES` WHERE `table_schema`=?"
			);
			if ($stmt) {
				$stmt->bind_param("s", $dbName);
				$stmt->execute();
				if ($row = $stmt->get_result()->fetch_object()) {
					$info['db_size'] = number_format((float)$row->sz / 1048576, 2) . ' MB';
					$info['table_count'] = (int)$row->tbls;
					$info['view_count'] = (int)$row->views;
				}
			}
		}
		return $info;
	}

	/**
	 * Ensures the GameTick engine class is loadable.
	 */
	private function loadTickEngine(): void
	{
		if (!class_exists('GameTick', false)) {
			include_once(SCRIPT_PATH . 'GameTick.class.php');
		}
	}

	/**
	 * Normalizes an input string for display.
	 */
	public static function clean(?string $value): string
	{
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}
?>
