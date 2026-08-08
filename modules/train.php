<?php
/*
 * MIT License
 *
 * Copyright (c) 2026 Stephen, Universe Civilization : Empire at wars
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

if (!$s->loggedIn || !$_GET['time']) {
    header("Location: ../index.php"); exit;
}

if (!$_REQUEST) {
    $s->updatePower($_SESSION['userid']);
}

$trainAction = $_REQUEST['id'] ?? '';
if ($trainAction == "untrn") {
    $s->untrainUnits((int)($_REQUEST['resatk'] ?? 0), (int)($_REQUEST['resdef'] ?? 0), (int)($_REQUEST['rescov'] ?? 0), (int)($_REQUEST['resanti'] ?? 0), (int)($_REQUEST['resmin'] ?? 0));
    $s->updatePower($_SESSION['userid']);
}
if ($trainAction == "trn") {
    $s->trainUnits((int)($_POST['atk'] ?? 0), (int)($_POST['uberAtk'] ?? 0), (int)($_POST['def'] ?? 0), (int)($_POST['uberDef'] ?? 0),
                   (int)($_POST['miners'] ?? 0), (int)($_POST['cov'] ?? 0), (int)($_POST['uberCov'] ?? 0), (int)($_POST['anti'] ?? 0),
                   (int)($_POST['uberAnti'] ?? 0));
    $s->updatePower($_SESSION['userid']);
}
?>
<table width="100%" border="0">
  <tr>
    <td width="47%" align="center" valign="top"><?php include_once('personnel.php'); ?></td>
    <td width="53%" align="center" valign="top">
      <table width="100%" border="0">
        <tr>
          <td align="center" valign="top"><a href='javascript:void(0)' onclick="trainthis('2train'); return false">Train</a> | <a href='javascript:void(0)' onclick="trainthis('untrain'); return false">Untrain</a></td>
        </tr>
        <tr>
          <td><div id="display">&nbsp;</div></td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>