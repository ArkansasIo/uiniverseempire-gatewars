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
include_once("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn) { header("Location: ../index.php"); exit; }
$s->updatePower($_SESSION['userid']);

$db = $s->db_link;
$myUID = (int)$_SESSION['userid'];

$message = '';
if (($_REQUEST['atype'] ?? '') === 'ascend') {
    $message = $s->ascend();
}

$query = "SELECT t.ascend, t.income, t.unitProd, b.onHand, b.inbank, ud.actionTurns
          FROM technology t INNER JOIN bank b ON b.uid=t.uid INNER JOIN userdata ud ON ud.uid=t.uid WHERE t.uid=? LIMIT 1";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $myUID);
$stmt->execute();
$tech = $stmt->get_result()->fetch_object();

$levels = [0, 1, 2, 3, 4, 5, 6];
$tierNames = [];
foreach ($levels as $lv) { $tierNames[$lv] = $s->level($lv)['str']; }
$current = (int)$tech->ascend;
$cost = $s->ascensionCost($current + 1);
$money = (float)$tech->onHand + (float)$tech->inbank;
$turnsOk = (int)$tech->actionTurns >= 100;
$techOk = (int)$tech->income >= 50 && (int)$tech->unitProd >= 50;
$moneyOk = $current >= 6 || $money >= $cost;
?>
<h3>Ascension Program</h3>
<?php if ($message !== ''): ?>
<p style="font-weight:bold;"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<div class="card">
  <h4>Current State</h4>
  <table border="0" cellpadding="4">
    <tr><td>Current Tier</td><td><strong><?= htmlspecialchars($tierNames[$current]) ?></strong></td></tr>
    <tr><td>Next Tier</td><td><?= $current >= 6 ? '-' : htmlspecialchars($tierNames[$current + 1]) ?></td></tr>
    <tr><td>Next Ascension Cost</td><td><?= $current >= 6 ? '-' : number_format($cost) ?> Naquadah</td></tr>
    <tr><td>Naquadah Available</td><td><?= number_format($money) ?></td></tr>
    <tr><td>Action Turns</td><td><?= number_format((int)$tech->actionTurns) ?></td></tr>
    <tr><td>Income Level</td><td><?= (int)$tech->income ?></td></tr>
    <tr><td>Unit Production Level</td><td><?= (int)$tech->unitProd ?></td></tr>
  </table>
</div>

<div class="card">
  <h4>Ascension Ladder</h4>
  <table border="0" cellpadding="4" width="100%">
    <tr><th align="left">Tier</th><th align="left">Name</th><th align="left">Multiplier</th><th align="left">Max Level</th><th align="left">Status</th></tr>
<?php foreach ($levels as $lv):
    $tier = $s->level($lv);
    $status = $lv === $current ? '<span style="color:#6f6">Current</span>' : ($lv < $current ? '<span style="color:#999">Passed</span>' : 'Locked');
?>
    <tr>
      <td><?= $lv ?></td>
      <td><?= htmlspecialchars($tier['str']) ?></td>
      <td><?= number_format($tier['y']) ?>x</td>
      <td><?= $tier['x'] ?></td>
      <td><?= $status ?></td>
    </tr>
<?php endforeach; ?>
  </table>
</div>

<div class="card">
  <h4>Ascend</h4>
<?php if ($current >= 6): ?>
  <p>You have reached the highest tier. Fully Ascended commanders have no further ladder to climb.</p>
<?php else: ?>
  <p>Requirements: income &amp; unit production level 50+, 100 action turns, and <?= number_format($cost) ?> Naquadah.</p>
  <table border="0" cellpadding="4">
    <tr><td>Requirements Met</td><td><?= $techOk && $turnsOk ? '<span style="color:#6f6">Yes</span>' : '<span style="color:#f66">No</span>' ?></td></tr>
    <tr><td>Funds Sufficient</td><td><?= $moneyOk ? '<span style="color:#6f6">Yes</span>' : '<span style="color:#f66">No</span>' ?></td></tr>
  </table>
  <form action="javascript:void(0)" onSubmit="sendData('ascension','post','mainDisplay','ascend');">
    <button type="submit" name="ascendBtn" value="ascend" <?= ($techOk && $turnsOk && $moneyOk) ? '' : 'disabled' ?>>Ascend to <?= htmlspecialchars($tierNames[$current + 1]) ?></button>
  </form>
<?php endif; ?>
</div>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>
