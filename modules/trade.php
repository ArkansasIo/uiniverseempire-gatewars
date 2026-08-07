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
if (($_REQUEST['atype'] ?? '') === 'create') {
    $message = $s->createTradeRoute((int)($_REQUEST['toUid'] ?? 0), (float)($_REQUEST['amount'] ?? 0), (int)($_REQUEST['turns'] ?? 1));
}

$bankRow = $db->query("SELECT onHand FROM bank WHERE uid=$myUID LIMIT 1")->fetch_object();
$routes = $s->listTradeRoutes($myUID);
?>
<h3>Trade Routes</h3>
<?php if ($message !== ''): ?>
<p style="font-weight:bold;"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<div class="card">
  <h4>Your Balance</h4>
  <p>Naquadah on hand: <strong><?= number_format((float)$bankRow->onHand) ?></strong></p>
</div>

<div class="card">
  <h4>Establish a Trade Route</h4>
  <p>Move Naquadah to another commander in scheduled installments. The full amount is deducted over the route's lifetime as your bank allows.</p>
  <form action="javascript:void(0)" onSubmit="sendData('trade','post','mainDisplay','create');">
    <label>Recipient UID:<br><input type="number" name="toUid" value="" min="1" style="width:120px"></label><br>
    <label>Total Naquadah:<br><input type="number" name="amount" value="" min="1" style="width:150px"></label><br>
    <label>Turns to deliver over:<br><input type="number" name="turns" value="10" min="1" style="width:80px"></label><br><br>
    <button type="submit" name="tradeBtn" value="create">Open Route</button>
  </form>
</div>

<div class="card">
  <h4>Active Trade Routes</h4>
<?php if (count($routes) === 0): ?>
  <p>No active trade routes.</p>
<?php else: ?>
  <table border="0" cellpadding="4" width="100%">
    <tr><th align="left">Direction</th><th align="left">Partner</th><th align="left">Remaining</th><th align="left">Per Turn</th><th align="left">Turns Left</th></tr>
<?php foreach ($routes as $r): ?>
    <tr>
      <td><?= (int)$r->from_uid === $myUID ? 'Outgoing' : 'Incoming' ?></td>
      <td><?= htmlspecialchars((int)$r->from_uid === $myUID ? $r->to_name : $r->from_name) ?> (UID <?= (int)$r->from_uid === $myUID ? (int)$r->to_uid : (int)$r->from_uid ?>)</td>
      <td><?= number_format((float)$r->amount) ?></td>
      <td><?= number_format((float)$r->rate) ?></td>
      <td><?= (int)$r->turns ?></td>
    </tr>
<?php endforeach; ?>
  </table>
<?php endif; ?>
</div>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>
