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
if (!$s->loggedIn || !$_GET['time']){ header("Location: index.php?"); }
if (!$_POST) { $s->updatePower($_SESSION['userid']); }
$weapons = $s->getWeapons($_SESSION['userid']);
if (!isset($weapons['atk']) || !is_array($weapons['atk'])) { $weapons['atk'] = []; }
if (!isset($weapons['def']) || !is_array($weapons['def'])) { $weapons['def'] = []; }
$atype = $_REQUEST['atype'] ?? "";
if($atype == "repair")
{
	$id = $_REQUEST['id'];
	$query = "UPDATE `weapons` SET `strength`=(SELECT weaponPower FROM armory WHERE wid =$id) WHERE uid=".$_SESSION['userid']." AND wid=$id";
	$s->query($query);
	echo "Weapon Repaired";
}

$submit = $_POST['submit'] ?? "";
if($_SERVER['REQUEST_METHOD'] === 'POST')
{
	$posted = array();
	for ($x = 0; $x < count($weapons['atk']); $x++)
	{
    $field = $weapons['atk'][$x]['fieldname'];
    $posted[$field] = $_POST[$field] ?? 0;
	}
	for ($x = 0; $x < count($weapons['def']); $x++)
	{
    $field = $weapons['def'][$x]['fieldname'];
    $posted[$field] = $_POST[$field] ?? 0;
	}
  if (!empty($posted)) {
    $s->buyWeapons($posted);
  }
	$s->updatePower($_SESSION['userid']);
}
$inv = $s->getWeaponInventory($_SESSION['userid']);
if (!isset($inv['atk']) || !is_array($inv['atk'])) { $inv['atk'] = []; }
if (!isset($inv['def']) || !is_array($inv['def'])) { $inv['def'] = []; }
?>
<table width="100%" border="0">
  <tr>
    <td colspan="2"><table width="100%" border="0">
      <tr>
        <td colspan="5" align="center" valign="middle">Current Weapon Inventory </td>
        </tr>
      <tr>
        <td width="22%" align="left" valign="middle">Attack Weapons</td>
        <td width="27%" align="center" valign="middle">Quanity</td>
        <td width="16%" align="center" valign="middle">Strength</td>
        <td width="13%" align="center" valign="middle">Repair</td>
        <td width="22%" align="center" valign="middle">Scrap /Sell </td>
      </tr>
	  <?php for ($x = 0; $x < count($inv['atk']); $x++){?>
	  <tr>
        <td align="left" valign="middle"><?= $inv['atk'][$x]['name'];?></td>
        <td align="center" valign="middle"><?= $inv['atk'][$x]['quanity'];?></td>
		<td align="center" valign="middle"><?= $inv['atk'][$x]['strength']."/".$inv['atk'][$x]['power'];?></td>
        <td align="center" valign="middle"><a href="javascript:void(0)" onclick="sendData('armory','get','<?= $inv['atk'][$x]['wid'];?>','repair'); return false;"><?= $inv['atk'][$x]['perpoint'];?></a></td>
        <td align="center" valign="middle"><input name="<?= $inv['atk'][$x]['fieldname'];?>" type="text" value="<?= $inv['atk'][$x]['quanity'];?>" size="10" /> for <?= $inv['atk'][$x]['sell'];?> each</td>
      </tr>
	  <?php } ?>
	  <tr><td>&nbsp;</td></tr>
      <tr>
        <td align="left" valign="middle">Defense Weapons</td>
        <td align="center" valign="middle">Quanity</td>
        <td align="center" valign="middle">Strength</td>
        <td align="center" valign="middle">Repair</td>
        <td align="center" valign="middle">Scrap /Sell </td>
      </tr>
      <?php for ($x = 0; $x < count($inv['def']); $x++){?>
	  <tr>
        <td align="left" valign="middle"><?= $inv['def'][$x]['name'];?></td>
        <td align="center" valign="middle"><?= $inv['def'][$x]['quanity'];?></td>
		<td align="center" valign="middle"><?= $inv['def'][$x]['strength']."/".$inv['def'][$x]['power']; ?></td>
        <td align="center" valign="middle"><a href="javascript:void(0)" onclick="sendData('armory','get','<?= $inv['def'][$x]['wid'];?>','repair'); return false;"><?= $inv['def'][$x]['perpoint'];?></a></td>
        <td align="center" valign="middle"><input name="<?= $inv['def'][$x]['fieldname'];?>" type="text" value="<?= $inv['def'][$x]['quanity'];?>" size="10" /> for <?= $inv['def'][$x]['sell'];?> each</td>
      </tr>
    <?php } ?>
	  <tr><td>&nbsp;</td></tr>
    </table></td>
  </tr>
  <tr>
    <td width="37%" align="left" valign="top"><?php include_once('mil_rank.php'); echo "<br>"; include_once('personnel.php'); ?>
    <br /></td>
    <td width="63%" align="right" valign="top"><form action="javascript:void(0)"><table width="90%" border="0">
      <tr>
        <td colspan="4" align="center" valign="top"> Weapons </td>
        </tr>
      <tr>
        <td width="26%" align="left" valign="top">Attack Weapons </td>
        <td width="18%" align="right">Power</td>
        <td width="40%" align="right">Cost</td>
        <td width="16%" align="right">Quanity</td>
      </tr>
          <?php
	  for ($x = 0; $x < count($weapons['atk']); $x++)
	  {
	  	if($weapons['atk'][$x]['unitcost'] ==0 && !$weapons['atk'][$x]['cashcost'] ==0)
		{
	    ?>
	  <tr>
        <td width="26%" align="left" valign="top"><?= $weapons['atk'][$x]['name'];?></td>
        <td width="18%" align="right" valign="top"><?= $weapons['atk'][$x]['power'];?></td>
        <td width="40%" align="right" valign="top"><?= $weapons['atk'][$x]['cashcost'];?> naquadrea</td>
        <td width="16%" align="right" valign="bottom"><input name="<?= $weapons['atk'][$x]['fieldname'];?>" type="text" value="0" size="10" /></td>
      </tr>
        <?php
	  	}elseif ($weapons['atk'][$x]['cashcost'] ==0 && !$weapons['atk'][$x]['unitcost'] ==0 ){
	    ?>
	  <tr>
        <td width="26%" align="left" valign="top"><?= $weapons['atk'][$x]['name'];?></td>
        <td width="18%" align="right" valign="top"><?= $weapons['atk'][$x]['power'];?></td>
        <td width="40%" align="right" valign="top"><?= $weapons['atk'][$x]['unitcost'];?> untrained units</td>
        <td width="16%" align="right" valign="bottom"><input name="<?= $weapons['atk'][$x]['fieldname'];?>" type="text" value="0" size="10" /></td>
      </tr>
	  	<?php
		}else if(!$weapons['atk'][$x]['cashcost'] ==0 && (!$weapons['atk'][$x]['unitcost'] ==0)){		
		?>
	  <tr>
        <td width="26%" align="left" valign="top"><?= $weapons['atk'][$x]['name'];?></td>
        <td width="18%" align="right" valign="top"><?= $weapons['atk'][$x]['power'];?></td>
        <td width="40%" align="right" valign="top"><?= $weapons['atk'][$x]['cashcost'];?> naquadrea and<br /> 
          <?= $weapons['atk'][$x]['unitcost'];?> untrained units</td>
        <td width="16%" align="right" valign="bottom"><input name="<?= $weapons['atk'][$x]['fieldname'];?>" type="text" value="0" size="10" /></td>
      </tr>
	  <?php
	  }
	 }
	  ?><tr><td>&nbsp;</td></tr>
	  <tr>
        <td width="26%" align="left" valign="top">Defense Weapons </td>
        <td width="18%" align="right">Power</td>
        <td width="40%" align="right">Cost</td>
        <td width="16%" align="right" valign="bottom">Quanity</td>
      </tr>
          <?php
	  for ($x = 0; $x < count($weapons['def']); $x++)
	  {
	  	if($weapons['def'][$x]['unitcost'] ==0  && !$weapons['def'][$x]['cashcost'] ==0 )
		{
	    ?>
	  <tr>
        <td width="26%" align="left" valign="top"><?= $weapons['def'][$x]['name'];?></td>
        <td width="18%" align="right" valign="top"><?= $weapons['def'][$x]['power'];?></td>
        <td width="40%" align="right" valign="top"><?= $weapons['def'][$x]['cashcost'];?> naquadrea</td>
        <td width="16%" align="right" valign="bottom"><input name="<?= $weapons['def'][$x]['fieldname'];?>" type="text" value="0" size="10" /></td>
      </tr>
        <?php
	  	}elseif ($weapons['def'][$x]['cashcost'] ==0 && !$weapons['def'][$x]['unitcost'] ==0  ){
	    ?>
	  <tr>
        <td width="26%" align="left" valign="top"><?= $weapons['def'][$x]['name'];?></td>
        <td width="18%" align="right" valign="top"><?= $weapons['def'][$x]['power'];?></td>
        <td width="40%" align="right" valign="top"><?= $weapons['def'][$x]['unitcost'];?> untrained units</td>
        <td width="16%" align="right" valign="bottom"><input name="<?= $weapons['def'][$x]['fieldname'];?>" type="text" value="0" size="10" /></td>
      </tr>
	  	<?php
		}else if(!$weapons['def'][$x]['cashcost'] ==0 && (!$weapons['def'][$x]['unitcost'] ==0)){		
		?>
	  <tr>
        <td width="26%" align="left" valign="top"><?= $weapons['def'][$x]['name'];?></td>
        <td width="18%" align="right" valign="top"><?= $weapons['def'][$x]['power'];?></td>
        <td width="40%" align="right" valign="top"><?= $weapons['def'][$x]['cashcost'];?> naquadrea and <br />
          <?= $weapons['def'][$x]['unitcost'];?> untrained units</td>
        <td width="16%" align="right" valign="bottom"><input name="<?= $weapons['def'][$x]['fieldname'];?>" type="text" value="0" size="10" /></td>
      </tr>
	  <?php
	  }
	 }
	  ?>
	  <tr><td>&nbsp;</td></tr>
      <tr>
        <td colspan="4" align="right" valign="bottom"><input type="submit" name="buyweaps" value="Submit" onclick="this.value='Buying Weapons...'; this.disabled=true; sendData('armory','post','<?= (int)($_SESSION['userid'] ?? 0);?>')"/></td>
        </tr>
    </table

></table>
<?php
echo "Query Count: ".$s->queryCount."<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>