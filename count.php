<?php
// count.php — bb_fix iframe "onload counter" echo stub
$bbFrameCount = (int)($_GET['count'] ?? 0);
?>
<BODY onload='parent.bb_done_loading();'>
<div id="divFrameCount"><?= $bbFrameCount; ?></div>
