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
if (!$s->loggedIn) { header("Location: ../index.php"); exit; }
$s->updatePower($_SESSION['userid']);

$db = $s->db_link;
$myUID = (int)$_SESSION['userid'];

$atype = $_REQUEST['atype'] ?? '';
$result = '';
$actID = null;
if ($atype === 'sabotage') {
    $turns = max(1, (int)($_REQUEST['turns'] ?? 1));
    $touid = (int)($_GET['id'] ?? 0);
    if ($touid <= 0) { $result = 'Invalid target.'; }
    elseif ($touid === $myUID) { $result = 'You cannot sabotage yourself.'; }
    else { $actID = $s->sabotage($touid, $turns); }
} elseif ($atype === 'counterspy') {
    $turns = max(1, (int)($_REQUEST['turns'] ?? 1));
    $touid = (int)($_GET['id'] ?? 0);
    if ($touid <= 0) { $result = 'Invalid target.'; }
    elseif ($touid === $myUID) { $result = 'You cannot sweep yourself.'; }
    else { $actID = $s->counterSpy($touid, $turns); }
}

$stmt = $db->prepare("SELECT power.mil_cov, power.mil_anti, userdata.actionTurns
    FROM power INNER JOIN userdata ON power.uid=userdata.uid
    WHERE power.uid=? LIMIT 1");
$stmt->bind_param("i", $myUID);
$stmt->execute();
$me = $stmt->get_result()->fetch_object();
?>
<h3>Covert Strike &amp; Counter-Intelligence</h3>
<?php if ($result !== ''): ?>
<p style="font-weight:bold;"><?= htmlspecialchars($result) ?></p>
<?php endif; ?>
<?php if ($actID !== null): ?>
<p><a href="javascript:void(0)" onclick="sendData('actionLogs','get',<?= (int)$actID ?>); return false">View mission report</a></p>
<?php endif; ?>

<div class="card">
  <h4>Your Covert Assets</h4>
  <table border="0" cellpadding="4">
    <tr><td>Covert Power</td><td><?= number_format((int)$me->mil_cov) ?></td></tr>
    <tr><td>Anti-Covert Power</td><td><?= number_format((int)$me->mil_anti) ?></td></tr>
    <tr><td>Action Turns</td><td><?= number_format((int)$me->actionTurns) ?></td></tr>
  </table>
</div>

<div class="card">
  <h4>Sabotage Mission</h4>
  <p>Destroy enemy weapons and covert agents. Success requires your covert + anti-covert power to outclass the target's combined covert defenses.</p>
  <form action="javascript:void(0)" onSubmit="sendData('sabotage','post',sabTarget.value,'sabotage');">
    <label>Target UID:<br><input type="number" name="sabTarget" value="" min="1" style="width:120px"></label><br>
    <label>Turns to use:<br><input type="number" name="turns" value="1" min="1" max="<?= (int)$me->actionTurns ?>" style="width:80px"></label><br><br>
    <button type="submit" name="sabBtn" value="sabotage">Launch Sabotage</button>
  </form>
</div>

<div class="card">
  <h4>Counter-Intelligence Sweep</h4>
  <p>Hunt down enemy covert agents operating in your territory. Success scales with your anti-covert power vs their covert power.</p>
  <form action="javascript:void(0)" onSubmit="sendData('sabotage','post',csTarget.value,'counterspy');">
    <label>Target UID:<br><input type="number" name="csTarget" value="" min="1" style="width:120px"></label><br>
    <label>Turns to use:<br><input type="number" name="turns" value="1" min="1" max="<?= (int)$me->actionTurns ?>" style="width:80px"></label><br><br>
    <button type="submit" name="csBtn" value="counterspy">Run Sweep</button>
  </form>
</div>

<?php
$stmt = $db->prepare(
    "SELECT al.actID, al.to_uid, u.uname AS target, al.time, al.success
     FROM actionlog al INNER JOIN users u ON u.uid=al.to_uid
     WHERE al.uid=? AND al.type IN ('sab','cs')
     ORDER BY al.actID DESC LIMIT 20");
$stmt->bind_param("i", $myUID);
$stmt->execute();
$log = $stmt->get_result();
?>
<div class="card">
  <h4>Recent Covert Operations</h4>
<?php if ($log->num_rows === 0): ?>
  <p>No covert operations yet.</p>
<?php else: ?>
  <table border="0" cellpadding="4">
    <tr><th>Target</th><th>Time</th><th>Result</th></tr>
<?php while ($row = $log->fetch_object()): ?>
    <tr>
      <td><?= htmlspecialchars($row->target) ?></td>
      <td><?= htmlspecialchars($row->time) ?></td>
      <td><?= (int)$row->success === 1 ? '<span style="color:#6f6">Success</span>' : '<span style="color:#f66">Failed</span>' ?></td>
    </tr>
<?php endwhile; ?>
  </table>
<?php endif; ?>
</div>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>
