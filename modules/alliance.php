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

$message = '';
if ($atype === 'create') {
    $name  = (string)($_REQUEST['subject'] ?? '');
    $desc  = (string)($_REQUEST['message'] ?? '');
    $forum = (string)($_REQUEST['url'] ?? '');
    $closed= (int)(($_REQUEST['allow'] ?? '') === '1' ? 1 : 0);
    $message = $s->create_allliance($myUID, $name, $desc, $forum, $closed) ? 'Alliance created successfully.' : 'Failed to create alliance (name may already exist).';
} elseif ($atype === 'join') {
    $message = $s->joinAlliance((int)($_GET['id'] ?? 0));
} elseif ($atype === 'leave') {
    $message = $s->leaveAlliance();
} elseif ($atype === 'deposit') {
    $message = $s->allianceBank('deposit', (float)($_REQUEST['amount'] ?? 0));
} elseif ($atype === 'withdraw') {
    $message = $s->allianceBank('withdraw', (float)($_REQUEST['amount'] ?? 0));
}
?>
<h3>Alliance Command</h3>
<?php if ($message !== ''): ?>
<p style="font-weight:bold;"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>
<?php
$myAlly = $s->getMyAlliance();
if ($myAlly !== null):
    $members = $s->getAllianceMembers((int)$myAlly->allyid);
    $bankRow = $db->query("SELECT SUM(amount) AS deposits FROM alliance_bank_log WHERE allyid=" . (int)$myAlly->allyid)->fetch_object();
?>
<div class="card">
  <h4><?= htmlspecialchars($myAlly->allyname) ?></h4>
  <p><?= htmlspecialchars($myAlly->desc) ?></p>
  <table border="0" cellpadding="4">
    <tr><td><strong>Status</strong></td><td><?= (int)$myAlly->isclosed === 1 ? 'Closed' : 'Open' ?></td></tr>
    <tr><td><strong>Founder</strong></td><td>UID <?= (int)$myAlly->founder ?></td></tr>
    <tr><td><strong>Bank Balance</strong></td><td><?= number_format((float)$myAlly->allybank) ?> Naquadah</td></tr>
    <tr><td><strong>Members</strong></td><td><?= count($members) ?></td></tr>
  </table>
</div>

<div class="card">
  <h4>Alliance Bank</h4>
  <form action="javascript:void(0)" onSubmit="sendData('alliance','post','mainDisplay',depositBtn.value);">
    <input type="hidden" name="allyid" value="<?= (int)$myAlly->allyid ?>">
    <label>Amount:<br><input type="number" name="amount" value="" min="1" style="width:120px"></label>
    <button type="submit" name="depositBtn" value="deposit">Deposit</button>
    <button type="submit" name="depositBtn" value="withdraw">Withdraw</button>
  </form>
</div>

<div class="card">
  <h4>Member Roster</h4>
  <table border="0" cellpadding="4" width="100%">
    <tr><th align="left">Commander</th><th align="left">Rank</th><th align="left">Power</th></tr>
<?php foreach ($members as $m): ?>
    <tr>
      <td><?= htmlspecialchars($m->uname) ?></td>
      <td><?= (int)$m->arank === 2 ? 'Leader' : 'Member' ?></td>
      <td><?= number_format((int)$m->overall) ?></td>
    </tr>
<?php endforeach; ?>
  </table>
</div>

<div class="card">
  <h4>Leave Alliance</h4>
  <form action="javascript:void(0)" onSubmit="sendData('alliance','post','mainDisplay','leave');">
    <button type="submit" name="leaveBtn" value="leave">Abandon Alliance</button>
  </form>
</div>

<?php else: ?>
<div class="card">
  <h4>Create a New Alliance</h4>
  <form action="javascript:void(0)" onSubmit="sendData('alliance','post','mainDisplay','create');">
    <table border="0" cellpadding="4">
      <tr><td>Alliance Name:</td><td><input type="text" name="subject" style="width:200px"></td></tr>
      <tr><td valign="top">Description:</td><td><textarea name="message" rows="4" cols="50"></textarea></td></tr>
      <tr><td>Alliance URL:</td><td><input type="text" name="url" value="http://" style="width:200px"></td></tr>
      <tr><td>Closed to new members?</td><td><input type="checkbox" name="allow" value="1"></td></tr>
    </table>
    <button type="submit" name="createBtn" value="create">Create Alliance</button>
  </form>
</div>

<div class="card">
  <h4>Alliances of the Universe</h4>
<?php
$list = $s->getAllianceList();
if (count($list) === 0) {
    echo '<p>No alliances have been founded yet.</p>';
} else {
    echo '<table border="0" cellpadding="4" width="100%"><tr><th align="left">Name</th><th align="left">Members</th><th align="left">Bank</th><th align="left">Status</th><th></th></tr>';
    foreach ($list as $a) {
        echo '<tr><td>' . htmlspecialchars($a->allyname) . '</td><td>' . (int)$a->members . '</td><td>' . number_format((float)$a->allybank) . '</td><td>' . ((int)$a->isclosed === 1 ? 'Closed' : 'Open') . '</td>';
        if ((int)$a->isclosed === 0) {
            echo '<td><a href="javascript:void(0)" onclick="sendData(\'alliance\',\'post\',' . (int)$a->allyid . ',\'join\'); return false">Join</a></td>';
        } else {
            echo '<td>&nbsp;</td>';
        }
        echo '</tr>';
    }
    echo '</table>';
}
?>
</div>
<?php endif; ?>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>
