<?php
include("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn || !$_GET['time']){ header("Location: ../index.php"); exit; }
$s->updatePower($_SESSION['userid']);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page <= 0) {
	$page = 1;
}

$allyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($allyId <= 0) {
	$base = $s->baseVars();
	$allyId = isset($base->allyid) ? (int)$base->allyid : 0;
}

$rankings = [];
$allyinfo = (object)['allyname' => 'No Alliance'];
if ($allyId > 0) {
	$rankings = $s->allyRankings($page, $allyId);
	$allyinfo = $s->getallyinfo($allyId);
}
?>
<table width="100%" border="0">
  <tr>
    <td>Name</td>
    <td>Rank</td>
    <td>Title</td>
    <td>Prestige</td>
    <td>Army Size </td>
    <td>Race</td>
    <td>Treasury</td>
  </tr>
<?php
for($x = 0; $x < count($rankings); $x++)
{
	if(isset($rankings[$x]['rank']) && $rankings[$x]['rank'] != 0){?>
    <tr>
  	  <td><a href='javascript:void(0)' onclick="sendData('user','get','<?= $rankings[$x]['uid']; ?>')"><?= htmlspecialchars($rankings[$x]['name'], ENT_QUOTES, 'UTF-8'); ?></a>[<?= htmlspecialchars($allyinfo->allyname, ENT_QUOTES, 'UTF-8'); ?>]</a></td>
    	<td><?= htmlspecialchars($rankings[$x]['rank'], ENT_QUOTES, 'UTF-8'); ?></td>
    	<td><?= htmlspecialchars(formalTitleDisplay($rankings[$x]['title'] ?? 'Rookie Commander', $rankings[$x]['titleBand'] ?? 'Novice', (int)($rankings[$x]['prestige'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></td>
    	<td><?= (int)($rankings[$x]['prestige'] ?? 0); ?></td>
    	<td><?= htmlspecialchars($rankings[$x]['army'], ENT_QUOTES, 'UTF-8'); ?></td>
    	<td><?= htmlspecialchars($rankings[$x]['race'], ENT_QUOTES, 'UTF-8'); ?></td>
    	<td><?= htmlspecialchars($rankings[$x]['cash'], ENT_QUOTES, 'UTF-8'); ?></td>
  		</tr>
	
<?php
}
}
?>
</table>
<?php
echo "Query Count: ".$s->queryCount."<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>