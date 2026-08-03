<?php
include("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn || !$_GET['time']){ header("Location: ../index.php"); exit; }
$s->updatePower($_SESSION['userid']);

$page = $_GET['page'] ?? '1';
$rankings = $s->Rankings($page);

?>
<table width="100%" border="0" cellspacing="0" cellpadding="4">
  <tr style="background:#142233;color:#fff;">
    <td><strong>Name</strong></td>
    <td><strong>Rank</strong></td>
    <td><strong>Title</strong></td>
    <td><strong>Prestige</strong></td>
    <td><strong>Army Size</strong></td>
    <td><strong>Race</strong></td>
    <td><strong>Treasury</strong></td>
    <td><strong>Action</strong></td>
  </tr>
<?php
for($x = 0; $x < count($rankings); $x++)
{
  if($rankings[$x]['rank'] != 0){
  $allyinfo = $s->getallyinfo($rankings[$x]['allyid']); ?>
    <tr style="border-bottom:1px solid #23364d;">
	
  	  <td><a href='javascript:void(0)' onclick="sendData('user','get','<?= htmlspecialchars($rankings[$x]['uid'], ENT_QUOTES, 'UTF-8'); ?>')"><?= htmlspecialchars($rankings[$x]['name'], ENT_QUOTES, 'UTF-8'); ?></a><?php if ($rankings[$x]['allyid'] != 0){ ?> [<a href="javascript:void(0)" onclick="sendData('ally_mlist','get','<?= htmlspecialchars($rankings[$x]['allyid'], ENT_QUOTES, 'UTF-8'); ?>','attack'); return false;"><?= htmlspecialchars($allyinfo->allyname, ENT_QUOTES, 'UTF-8');?></a>]<?php } ?></td>
    	<td><?= htmlspecialchars($rankings[$x]['rank'], ENT_QUOTES, 'UTF-8'); ?></td>
    	<td><?= htmlspecialchars(formalTitleDisplay($rankings[$x]['title'] ?? 'Rookie Commander', $rankings[$x]['titleBand'] ?? 'Novice', (int)($rankings[$x]['prestige'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></td>
    	<td><?= (int)($rankings[$x]['prestige'] ?? 0); ?></td>
    	<td><?= htmlspecialchars($rankings[$x]['army'], ENT_QUOTES, 'UTF-8'); ?></td>
    	<td><?= htmlspecialchars($rankings[$x]['race'], ENT_QUOTES, 'UTF-8'); ?></td>
    	<td><?= htmlspecialchars($rankings[$x]['cash'], ENT_QUOTES, 'UTF-8'); ?></td>
		<?php if ($rankings[$x]['uid'] != $_SESSION['userid']){ ?>
		<td><a href="javascript:void(0)" onclick="sendData('action','get','<?= htmlspecialchars($rankings[$x]['uid'], ENT_QUOTES, 'UTF-8'); ?>','attack'); return false;">Attack</a></td><?php } else { ?>
       <td></td><?php } ?>
  		</tr>
	
<?php
  }
}
?>
</table>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>