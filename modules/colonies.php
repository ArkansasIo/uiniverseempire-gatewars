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
if (($_REQUEST['atype'] ?? '') === 'focus') {
    $message = $s->setColonyFocus((int)($_REQUEST['pid'] ?? 0), (string)($_REQUEST['focus'] ?? 'balanced'));
}

$planets = $s->getColonyState($myUID);
?>
<h3>Colony Management</h3>
<?php if ($message !== ''): ?>
<p style="font-weight:bold;"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<div class="card">
  <h4>Your Planets</h4>
<?php if (count($planets) === 0): ?>
  <p>You have not settled any planets yet.</p>
<?php else: ?>
  <table border="0" cellpadding="4" width="100%">
    <tr><th align="left">Colony</th><th align="left">Size</th><th align="left">Income Bonus</th><th align="left">UP Bonus</th><th align="left">Focus</th></tr>
<?php foreach ($planets as $p): ?>
    <tr>
      <td><?= htmlspecialchars($p->plnt_name) ?><?= (int)$p->isHome === 1 ? ' <em>(home)</em>' : '' ?></td>
      <td><?= (int)$p->plnt_size ?></td>
      <td><?= number_format((int)$p->income_bonus) ?></td>
      <td><?= number_format((int)$p->up_bonus) ?></td>
      <td>
        <form action="javascript:void(0)" onSubmit="sendData('colonies','post','mainDisplay','focus');" style="display:inline;">
          <input type="hidden" name="pid" value="<?= (int)$p->pid ?>">
          <select name="focus" onchange="this.form.submit();">
<?php foreach (['income' => 'Income', 'up' => 'Unit Production', 'military' => 'Military', 'balanced' => 'Balanced'] as $val => $label): ?>
            <option value="<?= $val ?>" <?= $p->focus === $val ? 'selected' : '' ?>><?= $label ?></option>
<?php endforeach; ?>
          </select>
        </form>
      </td>
    </tr>
<?php endforeach; ?>
  </table>
<?php endif; ?>
</div>

<div class="card">
  <h4>Focus Effects</h4>
  <table border="0" cellpadding="4" width="100%">
    <tr><th align="left">Focus</th><th align="left">Effect</th></tr>
    <tr><td>Income</td><td>Multiplies colony income_bonus contribution.</td></tr>
    <tr><td>Unit Production</td><td>Multiplies colony up_bonus contribution.</td></tr>
    <tr><td>Military</td><td>Prioritizes garrison strength on this colony.</td></tr>
    <tr><td>Balanced</td><td>Equal weighting for all colony outputs.</td></tr>
  </table>
</div>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>
