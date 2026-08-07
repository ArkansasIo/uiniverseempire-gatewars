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
	 * Normalizes an input string for display.
	 */
	public static function clean(?string $value): string
	{
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
}
?>
