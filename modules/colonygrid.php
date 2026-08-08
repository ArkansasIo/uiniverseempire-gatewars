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
if (!$s->loggedIn) { header("Location: ../index.php"); exit; }
$s->updatePower($_SESSION['userid']);

$db = $s->db_link;
$myUID = (int)$_SESSION['userid'];

$atype = (string)($_GET['atype'] ?? '');
$cmd = strtok($atype, '&');
$cmd = ($cmd === false) ? '' : $cmd;

$world = max(0, (int)($_GET['world'] ?? 0));
$target = ((string)($_GET['target'] ?? '') === 'moon') ? 'moon' : 'planet';
$moon = ($target === 'moon') ? max(1, (int)($_GET['moon'] ?? 0)) : 0;
$slot = max(0, (int)($_GET['slot'] ?? 0));
$key = preg_replace('/[^a-z0-9_]/', '', strtolower((string)($_GET['key'] ?? '')));
$unit = preg_replace('/[^a-z0-9_]/', '', strtolower((string)($_GET['unit'] ?? '')));
$qty = max(1, (int)($_GET['qty'] ?? 0));

$message = '';
$error = '';
if ($cmd === 'expand' && $world > 0) {
    $message = $s->colonyFieldExpand($myUID, $world, $target, $moon);
} elseif ($cmd === 'build' && $world > 0 && $slot > 0 && $key !== '') {
    $message = $s->colonyFieldBuild($myUID, $world, $target, $moon, $slot, $key);
} elseif ($cmd === 'upgrade' && $world > 0 && $slot > 0) {
    $message = $s->colonyFieldUpgrade($myUID, $world, $target, $moon, $slot);
} elseif ($cmd === 'demolish' && $world > 0 && $slot > 0) {
    $message = $s->colonyFieldDemolish($myUID, $world, $target, $moon, $slot);
} elseif ($cmd === 'produce' && $unit !== '' && $qty > 0) {
    $message = $s->aiFactoryProduce($myUID, $unit, $qty);
}

$profiles = [];
$pq = $s->query("SELECT world_index,target_type,moon_no,world_type,city_name,field_total,field_used,size_class,infrastructure_tier,
    power_capacity,power_consumption,power_storage,grid_stability
    FROM universe_colony_profiles WHERE uid=" . $myUID . " ORDER BY world_index ASC, target_type ASC, moon_no ASC");
if ($pq) {
    while ($r = $pq->fetch_assoc()) { $profiles[] = $r; }
}

$selWorld = $world;
$selTarget = $target;
$selMoon = $moon;
$found = false;
if ($selWorld > 0) {
    foreach ($profiles as $pr) {
        if ((int)$pr['world_index'] === $selWorld && $pr['target_type'] === $selTarget && (int)$pr['moon_no'] === $selMoon) { $found = true; break; }
    }
}
if (!$found && count($profiles) > 0) {
    $first = $profiles[0];
    $selWorld = (int)$first['world_index'];
    $selTarget = $first['target_type'];
    $selMoon = (int)$first['moon_no'];
    $found = true;
}

$state = null;
$ai = $s->aiFactoryStatus($myUID);
if ($found) {
    $state = $s->colonyGridState($myUID, $selWorld, $selTarget, $selMoon);
}

$ownedBp = [];
$obq = $s->query("SELECT blueprint_id, owned_copies, me_level FROM player_blueprints WHERE uid=" . $myUID);
if ($obq) {
    while ($r = $obq->fetch_assoc()) {
        $ownedBp[(int)$r['blueprint_id']] = ['owned_copies' => (int)$r['owned_copies'], 'me_level' => (int)$r['me_level']];
    }
}

function cgEsc(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES);
}
?>
<h3>Colony Grid</h3>
<?php if ($message !== ''): ?>
<p style="font-weight:bold;"><?= cgEsc($message) ?></p>
<?php endif; ?>

<?php if (count($profiles) === 0): ?>
<div class="card">
  <h4>No Colonies Found</h4>
  <p>Your empire has not settled any world grids yet. Open the Universe &gt; Planets &amp; Moons section to seed your first colony worlds, then return here.</p>
  <p><a href="javascript:void(0)" onclick="sendData('pages','get','universe','planets&target=1'); return false">Open Universe &gt; Planets &amp; Moons</a></p>
</div>
<?php else: ?>

<div class="card">
  <h4>Select Colony Grid</h4>
  <select id="cgWorld">
<?php foreach ($profiles as $pr): $lab = ((string)$pr['target_type'] === 'moon') ? ('Moon #' . $pr['moon_no']) : 'Planet'; ?>
    <option value="<?= (int)$pr['world_index'] ?>:<?= $pr['target_type'] ?>:<?= (int)$pr['moon_no'] ?>" <?= ((int)$pr['world_index'] === $selWorld && $pr['target_type'] === $selTarget && (int)$pr['moon_no'] === $selMoon) ? 'selected' : '' ?>>
      World <?= (int)$pr['world_index'] ?> - <?= $lab ?> (<?= cgEsc((string)$pr['world_type']) ?>) - Class <?= (int)$pr['size_class'] ?> Grid
    </option>
<?php endforeach; ?>
  </select>
  <a href="javascript:void(0)" onclick="var v=document.getElementById('cgWorld').value;var p=v.split(':');sendData('colonygrid','get','mainDisplay','grid&world='+p[0]+'&target='+p[1]+'&moon='+p[2]); return false">Load Colony Grid</a>
  <?php if ($found): ?>
  <a href="javascript:void(0)" onclick="sendData('colonygrid','get','mainDisplay','expand&world=<?= $selWorld ?>&target=<?= $selTarget ?>&moon=<?= $selMoon ?>'); return false">Expand Grid (+fields)</a>
  <?php endif; ?>
</div>

<?php if ($state): ?>
<?php
    $power = $state['power'];
    $profile = $state['profile'] ?: [];
    $slots = $state['slot_classes'];
    $rows = $state['rows'];
    $cat = $state['catalog'];
    $emptySlots = [];
    for ($i = 1; $i <= $state['field_total']; $i++) {
        if (!isset($rows[$i])) { $emptySlots[] = $i; }
    }
?>
<div class="card full">
  <h4>Field Grid - World <?= (int)$selWorld ?> <?= $selTarget === 'moon' ? ('Moon #' . $selMoon) : 'Planet' ?> (Class <?= (int)$state['size_class'] ?>)</h4>
  <p><?= cgEsc((string)($profile['city_name'] ?? '')) ?> | <?= cgEsc((string)($profile['biome'] ?? '')) ?> | Fields <?= (int)$state['field_used'] ?> / <?= (int)$state['field_total'] ?> | Infrastructure T<?= (int)($profile['infrastructure_tier'] ?? 1) ?></p>
  <table class="mini-table" border="0" width="100%">
    <tr><th align="left">Slot</th><th align="left">Class</th><th align="left">Building</th><th align="left">Level</th><th align="left">Power</th><th align="left">Population</th><th align="left">Actions</th></tr>
<?php for ($i = 1; $i <= $state['field_total']; $i++): $cls = (int)$slots[$i]; ?>
<?php if (isset($rows[$i])): $rw = $rows[$i]; ?>
    <tr>
      <td>#<?= $i ?></td>
      <td>C<?= $cls ?></td>
      <td><?= cgEsc((string)$rw['building_name']) ?></td>
      <td>Lv<?= (int)$rw['building_level'] ?></td>
      <td><?= number_format((int)$rw['power_generated']) ?></td>
      <td><?= number_format((int)$rw['population_use']) ?></td>
      <td><a href="javascript:void(0)" onclick="sendData('colonygrid','get','mainDisplay','upgrade&world=<?= $selWorld ?>&target=<?= $selTarget ?>&moon=<?= $selMoon ?>&slot=<?= $i ?>'); return false">Upgrade</a> | <a href="javascript:void(0)" onclick="sendData('colonygrid','get','mainDisplay','demolish&world=<?= $selWorld ?>&target=<?= $selTarget ?>&moon=<?= $selMoon ?>&slot=<?= $i ?>'); return false">Demolish</a></td>
    </tr>
<?php else: ?>
    <tr>
      <td>#<?= $i ?></td>
      <td>C<?= $cls ?></td>
      <td colspan="4"><em>Empty field</em></td>
      <td><em>Available</em></td>
    </tr>
<?php endif; ?>
<?php endfor; ?>
  </table>
</div>

<div class="card full">
  <h4>Power Grid</h4>
  <table class="mini-table" border="0" width="100%">
    <tr><th align="left">Capacity</th><th align="left">Consumption</th><th align="left">Surplus</th><th align="left">Storage</th><th align="left">Stability</th></tr>
    <tr>
      <td><?= number_format((int)$power['capacity']) ?></td>
      <td><?= number_format((int)$power['consumption']) ?></td>
      <td><?= number_format((int)$power['surplus']) ?></td>
      <td><?= number_format((int)$power['storage']) ?></td>
      <td><?= (int)$power['stability'] ?>%</td>
    </tr>
  </table>
  <p><small>Producers add to capacity; consumers draw from it. Power banks add storage buffer. Stability drops when demand exceeds generation.</small></p>
</div>

<div class="card full">
  <h4>Field Building Catalog</h4>
  <?php if (count($emptySlots) === 0): ?>
  <p>No empty slots. Expand the grid first.</p>
  <?php else: ?>
  <p>Place into slot: <select id="cgBuildSlot">
<?php foreach ($emptySlots as $es): ?>
    <option value="<?= $es ?>">Slot #<?= $es ?> (Class <?= (int)$slots[$es] ?>)</option>
<?php endforeach; ?>
  </select></p>
  <table class="mini-table" border="0" width="100%">
    <tr><th align="left">Building</th><th align="left">Category</th><th align="left">Tier</th><th align="left">Req Class</th><th align="left">Cost</th><th align="left">Power</th><th align="left">Blueprint</th><th align="left">Action</th></tr>
<?php foreach ($cat as $bk => $bd): $bpId = (int)$bd['blueprint_id']; $meLvl = $bpId > 0 ? (int)($ownedBp[$bpId]['me_level'] ?? 0) : 0; $cost = $s->colonyBuildCost($bk, $bd, 0, $meLvl); $locked = ($bpId > 0 && (int)($ownedBp[$bpId]['owned_copies'] ?? 0) < 1); $aicLock = ((int)$bd['tier'] >= 5 && $s->colonyBuildingLevel($myUID, $selWorld, $selTarget, $selMoon, 'aic_factory') < 1); ?>
    <tr>
      <td><?= cgEsc((string)$bd['building_name']) ?></td>
      <td><?= cgEsc((string)$bd['category']) ?></td>
      <td>T<?= (int)$bd['tier'] ?></td>
      <td>C<?= (int)$bd['size_requirement'] ?></td>
      <td><?= number_format((int)$cost['metal']) ?>M / <?= number_format((int)$cost['crystal']) ?>C / <?= number_format((int)$cost['deuterium']) ?>D / <?= number_format((int)$cost['naq']) ?>N / <?= (int)$cost['turns'] ?> turns</td>
      <td><?= (int)$bd['power_generated'] ?> / lvl</td>
      <td>
<?php if ($bpId > 0): ?>
        <?= $locked ? 'Locked (BP #' . $bpId . ')' : 'Unlocked' ?>
<?php else: ?>
        Always buildable
<?php endif; ?>
      </td>
      <td>
<?php if ($locked): ?>
        <a href="javascript:void(0)" onclick="sendData('pages','get','research','blueprints&cmd=bp_acquire&bp=<?= $bpId ?>'); return false">Acquire Blueprint</a>
<?php elseif ($aicLock): ?>
        <em>Requires AIC</em>
<?php else: ?>
        <a href="javascript:void(0)" onclick="var s=document.getElementById('cgBuildSlot').value;if(s){sendData('colonygrid','get','mainDisplay','build&world=<?= $selWorld ?>&target=<?= $selTarget ?>&moon=<?= $selMoon ?>&slot='+s+'&key=<?= $bk ?>');} return false;">Build</a>
<?php endif; ?>
      </td>
    </tr>
<?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<div class="card full">
  <h4>Alliance Industrial Complex (AIC)</h4>
  <p>AIC level: <strong><?= $s->colonyBuildingLevel($myUID, $selWorld, $selTarget, $selMoon, 'aic_factory') ?></strong></p>
  <p><small>The AIC massively accelerates construction and unlocks tier 5+ field buildings. Robotics factories add a 10% construction speed bonus per level.</small></p>
</div>

<div class="card full">
  <h4>AI Factory</h4>
  <p>AI Factory level: <strong><?= (int)$ai['level'] ?></strong> (account-wide)</p>
  <table class="mini-table" border="0" width="100%">
    <tr><th align="left">Unit</th><th align="left">Stock</th><th align="left">Cost / unit</th><th align="left">Action</th></tr>
<?php foreach ($ai['unit_catalog'] as $ut => $ud): $stock = (int)($ai['units'][$ut] ?? 0); $cap = (int)$ai['level'] * (int)$ud['per_level']; ?>
    <tr>
      <td><?= cgEsc((string)$ud['name']) ?></td>
      <td><?= number_format($stock) ?></td>
      <td><?= (int)$ud['metal'] ?>M / <?= (int)$ud['crystal'] ?>C / <?= (int)$ud['deuterium'] ?>D / <?= (int)$ud['turns'] ?> turns</td>
      <td>
        <input id="cgQty_<?= $ut ?>" type="number" min="1" max="<?= max(1, $cap) ?>" value="1" style="width:70px">
        <a href="javascript:void(0)" onclick="var q=parseInt(document.getElementById('cgQty_<?= $ut ?>').value,10)||1;sendData('colonygrid','get','mainDisplay','produce&world=<?= $selWorld ?>&target=<?= $selTarget ?>&moon=<?= $selMoon ?>&unit=<?= $ut ?>&qty='+q); return false">Produce</a>
      </td>
    </tr>
<?php endforeach; ?>
  </table>
</div>
<?php endif; ?>
<?php endif; ?>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>
