<?php
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