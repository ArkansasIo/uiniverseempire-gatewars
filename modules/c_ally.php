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
include("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();
$s = new Game();
$s->updatePower($_SESSION['userid']); 

$id = 0;
$name = '';
$atype = $_REQUEST['atype'] ?? '';

if (!empty($_GET['id']) && $atype != "Send") {
    $query = "SELECT `uname` FROM `users` WHERE uid = ? LIMIT 1";
  $stmt = $s->db_link->prepare($query);
  $lookupId = (int)$_GET['id'];
  $stmt->bind_param("i", $lookupId);
    $stmt->execute();
    $q = $stmt->get_result();
    $data = $q->fetch_object();
  if ($data) {
    $name = $data->uname;
    $id = $lookupId;
  }
}

if ($atype == "Send") {
    if ($s->create_allliance($_GET['id'], $_REQUEST['subject'], $_REQUEST['message'], $_REQUEST['url'], $_REQUEST['allow'])) {
        echo ",Thank you";
    } else {
        echo ",If problem persists, contact Admin";
    }
}
?>
<form action="javascript:void(0)" onSubmit="submit.value='Sending Message'; submit.disabled=true; sendData('c_ally','post',userID.value,'Send');"><center>
<input type="hidden" id="userID" name="userID" value="<?= $id; ?>">
<table width="100%" border="0">
  <tr>
    <td align="left" valign="top">Alliance Name:</td>
    <td colspan="3" align="left" valign="top"><input type="text" name="subject"></td>
  </tr>
  <tr>
    <td align="left" valign="top">Alliance Description:</td>
    <td colspan="3" align="left" valign="top">
      <p>:
        <textarea name="message" cols="100" rows="20" wrap="virtual"></textarea>
        <br>
      </p>
    </td>
  </tr>
  <tr>
    <td><div align="center">Alliance URL: http:// </div></td>
    <td><input name="url" type="text" id="url" value="http://"></td>
  </tr>
  <tr>
    <td><div align="center">Don't Allow New Members? </div></td>
    <td><input name="allow" type="checkbox" id="allow" value="1"></td>
  </tr>
</table>
<input type="submit" name="submit" id="submit" value="Create Alliance">
</center>
</form>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>