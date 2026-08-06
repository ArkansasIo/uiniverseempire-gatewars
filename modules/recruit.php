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
include_once("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
$db = $s->db_link;

$db->query("CREATE TABLE IF NOT EXISTS `recruit_ips` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `recruitID` int(11) NOT NULL DEFAULT 0,
    `uid` int(11) NOT NULL,
    `ip` varchar(45) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uid_ip` (`uid`,`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1");

$recruitLink = $s->clean_sql($_GET['id'] ?? '', 0);
$msg = $_GET['strErr'] ?? '';
$recruiterName = '';
$error = false;

if ($recruitLink !== '') {
    $stmt = $db->prepare("SELECT u.uid, u.uname FROM users u
        INNER JOIN userdata ud ON ud.uid=u.uid
        WHERE ud.link=? LIMIT 1");
    $stmt->bind_param("s", $recruitLink);
    $stmt->execute();
    $recruiter = $stmt->get_result()->fetch_object();

    if (!$recruiter) {
        $error = true;
        $msg = 'That recruit link does not exist.';
    } else {
        $recruiterUID  = (int)$recruiter->uid;
        $recruiterName = $recruiter->uname;
        $clientIP      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $stmt = $db->prepare("SELECT id FROM recruit_ips WHERE uid=? AND ip=? LIMIT 1");
        $stmt->bind_param("is", $recruiterUID, $clientIP);
        $stmt->execute();
        $already = $stmt->get_result()->fetch_object();

        if ($already) {
            $error = true;
            $msg = 'Your IP has already used this recruit link.';
        } else {
            $db->begin_transaction();
            try {
                $stmt = $db->prepare("UPDATE units SET untrained=untrained+4 WHERE uid=? LIMIT 1");
                $stmt->bind_param("i", $recruiterUID);
                $stmt->execute();

                $stmt = $db->prepare("INSERT IGNORE INTO recruit_ips (recruitID, uid, ip) VALUES (0, ?, ?)");
                $stmt->bind_param("is", $recruiterUID, $clientIP);
                $stmt->execute();

                $db->commit();
            } catch (Throwable $e) {
                $db->rollback();
                $error = true;
                $msg = 'Could not process recruit link. Please try again.';
            }
        }
    }
}
?>
<html>
<head><title>Recruit - <?= htmlspecialchars($subs['{TITLE}'] ?? 'Universe Civilization: Empire at Wars') ?></title></head>
<body>
<center>
<table width="60%" cellspacing="1" cellpadding="8">
<tr><td>
  <?php if ($msg): ?>
    <p><strong><?= htmlspecialchars($msg) ?></strong></p>
  <?php endif; ?>

  <?php if ($error || $recruitLink === ''): ?>
    <strong>Welcome to <?= htmlspecialchars($subs['{TITLE}'] ?? 'Universe Civilization: Empire at Wars') ?>!</strong>
    <p>We are sorry, but your IP has already used this enlistment link, or the link is invalid.<br>
    Why not join and get others to click your own link?<br>
    <a href="../index.php">Join Now</a></p>
  <?php else: ?>
    <strong>Welcome to <?= htmlspecialchars($subs['{TITLE}'] ?? 'Universe Civilization: Empire at Wars') ?>!</strong>
    <p>By clicking this enlistment link you have recruited 4 troops into the armies of
    <strong><?= htmlspecialchars($recruiterName) ?></strong>.<br>
    Join up and build your own empire!<br>
    <a href="../index.php">Join Now</a></p>
  <?php endif; ?>
</td></tr>
</table>
</center>
</body>
</html>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>
