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

$bankAction = $_GET['id'] ?? '';
$bankAmount = (float)($_GET['atype'] ?? 0);
if ($bankAction === "deposit" || $bankAction === "withdrawl") {
    $s->bank($bankAction, $bankAmount);
}
$data = $s->bank();
if (!$data) {
  echo "Bank data unavailable.";
  exit;
}
?>
<form action="javascript:void(0)">
Your Bank Account:<br /><br />
<table width="100%" border="0">
  <tr>
    <td width="23%">Naquadah on Hand </td>
    <td width="23%">Naquadah in Bank </td>
    <td width="27%"> Bank Account Capacity </td>
    <td width="27%">Space Left </td>
  </tr>
  <tr>
    <td><?= number_format($data->onHand); ?></td>
    <td><?= number_format($data->inBank); ?></td>
    <td><?= number_format($data->cap); ?></td>
    <td><?php if ($data->left < 0) { echo "0"; } else { echo number_format($data->left); } ?></td>
  </tr>
  <tr>
    <td>Put Naquadah into Account:</td>
    <td align="left">amount: <input id="deposit" name="deposit" type='text' value="<?= number_format($data->onHand, 0, '', ''); ?>" size="10" /></td>
    <td colspan="2" align="left" valign="top"><input type="submit" name="giveThis" value="Deposit" onClick="this.disabled=true; this.value='Depositing'; sendData('bank','get','deposit',deposit.value);" /></td>
  </tr>
  <tr>
    <td>Take Naquadah out of Account:</td>
    <td align="left">amount:
    <input id="withdrawl" name="withdrawl" type='text' value="0" size="10" /></td>
    <td colspan="2" align="left" valign="top"><input type="submit" name="takeThis" value="Withdraw" onClick="this.disabled=true; this.value='Withdrawing'; sendData('bank','get','withdrawl',withdrawl.value);" /></td>
  </tr>
</table>
</form>
<br /><br />
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>