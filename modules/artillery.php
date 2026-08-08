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
 *
 * Artillery Command - offense/defense artillery with full taxonomy:
 *   major class (offense/defense) -> class -> subclass
 *   type -> subtype
 *   stats -> sub-stats
 *   attributes -> sub-attributes
 * Acquisition: buy (naquadah + untrained units + resources),
 *              convert (trained/untrained units), sell (scrap).
 */
include_once("../config.php");
include_once(__DIR__ . '/artillery_lib.php');

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn || !isset($_GET['time'])) { header("Location: index.php"); }
$uid = (int)($_SESSION['userid'] ?? 0);

function art_seedTables(Game $s, int $uid): void
{
    $s->query("CREATE TABLE IF NOT EXISTS `artillery_catalog` (
        `artillery_id` INT(11) NOT NULL AUTO_INCREMENT,
        `artillery_code` VARCHAR(16) NOT NULL DEFAULT '',
        `artillery_name` VARCHAR(120) NOT NULL DEFAULT '',
        `artillery_title` VARCHAR(160) NOT NULL DEFAULT '',
        `major_class` VARCHAR(16) NOT NULL DEFAULT 'offense',
        `class_name` VARCHAR(40) NOT NULL DEFAULT '',
        `subclass_name` VARCHAR(60) NOT NULL DEFAULT '',
        `type_name` VARCHAR(40) NOT NULL DEFAULT '',
        `subtype_name` VARCHAR(60) NOT NULL DEFAULT '',
        `tier` INT(11) NOT NULL DEFAULT 1,
        `power_rating` INT(11) NOT NULL DEFAULT 0,
        `attack_stat` INT(11) NOT NULL DEFAULT 0,
        `attack_sub` INT(11) NOT NULL DEFAULT 0,
        `defense_stat` INT(11) NOT NULL DEFAULT 0,
        `defense_sub` INT(11) NOT NULL DEFAULT 0,
        `shield_stat` INT(11) NOT NULL DEFAULT 0,
        `shield_sub` INT(11) NOT NULL DEFAULT 0,
        `accuracy_stat` INT(11) NOT NULL DEFAULT 0,
        `accuracy_sub` INT(11) NOT NULL DEFAULT 0,
        `range_stat` INT(11) NOT NULL DEFAULT 0,
        `range_sub` INT(11) NOT NULL DEFAULT 0,
        `reload_stat` INT(11) NOT NULL DEFAULT 0,
        `reload_sub` INT(11) NOT NULL DEFAULT 0,
        `mobility_stat` INT(11) NOT NULL DEFAULT 0,
        `mobility_sub` INT(11) NOT NULL DEFAULT 0,
        `naq_cost` BIGINT(20) NOT NULL DEFAULT 0,
        `unit_cost` INT(11) NOT NULL DEFAULT 0,
        `metal_cost` BIGINT(20) NOT NULL DEFAULT 0,
        `crystal_cost` BIGINT(20) NOT NULL DEFAULT 0,
        `deut_cost` BIGINT(20) NOT NULL DEFAULT 0,
        `food_cost` BIGINT(20) NOT NULL DEFAULT 0,
        `water_cost` BIGINT(20) NOT NULL DEFAULT 0,
        `pop_cost` BIGINT(20) NOT NULL DEFAULT 0,
        `attack_convert` INT(11) NOT NULL DEFAULT 0,
        `defense_convert` INT(11) NOT NULL DEFAULT 0,
        `build_time` INT(11) NOT NULL DEFAULT 0,
        `attributes` TEXT NOT NULL,
        `legacy_key` VARCHAR(32) NOT NULL DEFAULT '',
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`artillery_id`),
        UNIQUE KEY `idx_art_code` (`artillery_code`),
        KEY `idx_art_class` (`major_class`, `class_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1");

    $s->query("CREATE TABLE IF NOT EXISTS `player_artillery` (
        `uid` INT(11) NOT NULL,
        `artillery_id` INT(11) NOT NULL,
        `battery` VARCHAR(16) NOT NULL DEFAULT 'reserve',
        `quantity` INT(11) NOT NULL DEFAULT 0,
        `total_power` BIGINT(20) NOT NULL DEFAULT 0,
        `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`uid`, `artillery_id`, `battery`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1");

    $check = $s->query("SHOW TABLES LIKE 'artillery_catalog'");
    if (!$check || $check->num_rows === 0) {
        return;
    }
    $countQ = $s->query("SELECT COUNT(*) AS c FROM artillery_catalog");
    $countObj = $countQ ? $countQ->fetch_object() : (object)['c' => 0];
    if ((int)$countObj->c > 0) {
        return;
    }
    foreach (art_buildCatalog() as $art) {
        $db = $s->db_link;
        $code = art_safeToken((string)$art['artillery_code']);
        $name = art_safeToken((string)$art['artillery_name']);
        $title = art_safeToken((string)$art['artillery_title']);
        $major = art_safeToken((string)$art['major_class']);
        $cls = art_safeToken((string)$art['class_name']);
        $sub = art_safeToken((string)$art['subclass_name']);
        $type = art_safeToken((string)$art['type_name']);
        $subtype = art_safeToken((string)$art['subtype_name']);
        $attrs = $db->real_escape_string((string)$art['attributes']);
        $s->query("INSERT INTO artillery_catalog (
            artillery_id, artillery_code, artillery_name, artillery_title, major_class,
            class_name, subclass_name, type_name, subtype_name, tier, power_rating,
            attack_stat, attack_sub, defense_stat, defense_sub, shield_stat, shield_sub,
            accuracy_stat, accuracy_sub, range_stat, range_sub, reload_stat, reload_sub,
            mobility_stat, mobility_sub, naq_cost, unit_cost, metal_cost, crystal_cost,
            deut_cost, food_cost, water_cost, pop_cost, attack_convert, defense_convert,
            build_time, attributes, legacy_key
        ) VALUES (
            " . (int)$art['artillery_id'] . ", '$code', '$name', '$title', '$major',
            '$cls', '$sub', '$type', '$subtype', " . (int)$art['tier'] . ", " . (int)$art['power_rating'] . ",
            " . (int)$art['attack_stat'] . ", " . (int)$art['attack_sub'] . ", " . (int)$art['defense_stat'] . ", " . (int)$art['defense_sub'] . ",
            " . (int)$art['shield_stat'] . ", " . (int)$art['shield_sub'] . ", " . (int)$art['accuracy_stat'] . ", " . (int)$art['accuracy_sub'] . ",
            " . (int)$art['range_stat'] . ", " . (int)$art['range_sub'] . ", " . (int)$art['reload_stat'] . ", " . (int)$art['reload_sub'] . ",
            " . (int)$art['mobility_stat'] . ", " . (int)$art['mobility_sub'] . ", " . (int)$art['naq_cost'] . ", " . (int)$art['unit_cost'] . ",
            " . (int)$art['metal_cost'] . ", " . (int)$art['crystal_cost'] . ", " . (int)$art['deut_cost'] . ",
            " . (int)$art['food_cost'] . ", " . (int)$art['water_cost'] . ", " . (int)$art['pop_cost'] . ",
            " . (int)$art['attack_convert'] . ", " . (int)$art['defense_convert'] . ", " . (int)$art['build_time'] . ",
            '$attrs', '" . art_safeToken((string)$art['legacy_key']) . "'
        )");
    }
    $s->query("INSERT IGNORE INTO player_artillery (uid, artillery_id) VALUES (" . (int)$uid . ", 1)");
    $s->query("DELETE FROM player_artillery WHERE uid=" . (int)$uid . " AND quantity <= 0");
}

function art_fetchCatalog(Game $s): array
{
    $out = [];
    $q = $s->query("SELECT * FROM artillery_catalog ORDER BY major_class ASC, class_name ASC, subclass_name ASC, type_name ASC, subtype_name ASC LIMIT 500");
    if ($q) {
        while ($row = $q->fetch_object()) {
            $out[] = $row;
        }
    }
    return $out;
}

function art_owned(Game $s, int $uid): array
{
    $out = [];
    $q = $s->query("SELECT pa.*, c.artillery_code, c.artillery_name, c.artillery_title, c.major_class,
        c.class_name, c.subclass_name, c.type_name, c.subtype_name, c.power_rating
        FROM player_artillery pa
        JOIN artillery_catalog c ON c.artillery_id = pa.artillery_id
        WHERE pa.uid = $uid AND pa.quantity > 0
        ORDER BY c.major_class ASC, c.class_name ASC, c.subclass_name ASC");
    if ($q) {
        while ($row = $q->fetch_object()) {
            $out[] = $row;
        }
    }
    return $out;
}

function art_powerTotals(Game $s, int $uid): array
{
    $totals = ['offense' => 0, 'defense' => 0, 'reserve' => 0, 'count' => 0];
    $q = $s->query("SELECT battery, SUM(quantity) AS qty, SUM(total_power) AS tp FROM player_artillery WHERE uid = $uid GROUP BY battery");
    if ($q) {
        while ($row = $q->fetch_object()) {
            $key = isset($totals[$row->battery]) ? $row->battery : 'reserve';
            $totals[$key] = (int)$row->tp;
            $totals['count'] += (int)$row->qty;
        }
    }
    return $totals;
}

function art_fetchPiece(Game $s, int $artId): ?object
{
    $q = $s->query("SELECT * FROM artillery_catalog WHERE artillery_id = " . (int)$artId . " LIMIT 1");
    return $q ? $q->fetch_object() : null;
}

function art_addOwned(Game $s, int $uid, int $artId, string $battery, int $qty, int $power): void
{
    $battery = in_array($battery, ['offense', 'defense', 'reserve'], true) ? $battery : 'reserve';
    $q = $s->query("SELECT quantity FROM player_artillery WHERE uid = $uid AND artillery_id = $artId AND battery = '$battery' LIMIT 1");
    if ($q && $q->num_rows === 1) {
        $s->query("UPDATE player_artillery SET quantity = quantity + $qty, total_power = total_power + $power WHERE uid = $uid AND artillery_id = $artId AND battery = '$battery' LIMIT 1");
    } else {
        $s->query("INSERT INTO player_artillery (uid, artillery_id, battery, quantity, total_power) VALUES ($uid, $artId, '$battery', $qty, $power)");
    }
}

function art_buy(Game $s, int $uid, int $artId, int $qty, string $battery): string
{
    if ($qty <= 0) { return 'Quantity must be positive.'; }
    $c = art_fetchPiece($s, $artId);
    if (!$c) { return 'Artillery piece not found.'; }
    if ($battery !== 'offense' && $battery !== 'defense') {
        $battery = $c->major_class;
    }
    if (($battery === 'offense' && $c->major_class !== 'offense') ||
        ($battery === 'defense' && $c->major_class !== 'defense')) {
        $battery = $c->major_class;
    }
    $needNaq = (int)$c->naq_cost * $qty;
    $needUnit = (int)$c->unit_cost * $qty;
    $needMetal = (int)$c->metal_cost * $qty;
    $needCrystal = (int)$c->crystal_cost * $qty;
    $needDeut = (int)$c->deut_cost * $qty;
    $needFood = (int)$c->food_cost * $qty;
    $needWater = (int)$c->water_cost * $qty;
    $needPop = (int)$c->pop_cost * $qty;

    $bankQ = $s->query("SELECT onHand FROM bank WHERE uid = $uid LIMIT 1");
    $bank = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];
    $unitsQ = $s->query("SELECT untrained FROM units WHERE uid = $uid LIMIT 1");
    $units = $unitsQ ? $unitsQ->fetch_object() : (object)['untrained' => 0];
    $resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid = $uid LIMIT 1");
    $res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'food' => 0, 'water' => 0, 'population' => 0];

    if ((int)$bank->onHand < $needNaq) { return 'Not enough Naquadah.'; }
    if ((int)$units->untrained < $needUnit) { return 'Not enough untrained units.'; }
    if ((int)($res->metal ?? 0) < $needMetal || (int)($res->crystal ?? 0) < $needCrystal ||
        (int)($res->deuterium ?? 0) < $needDeut || (int)($res->food ?? 0) < $needFood ||
        (int)($res->water ?? 0) < $needWater || (int)($res->population ?? 0) < $needPop) {
        return 'Not enough resources (metal/crystal/deuterium/food/water/population).';
    }

    $s->query("UPDATE bank SET onHand = onHand - $needNaq WHERE uid = $uid LIMIT 1");
    $s->query("UPDATE units SET untrained = untrained - $needUnit WHERE uid = $uid LIMIT 1");
    $s->query("UPDATE player_resources SET metal = metal - $needMetal, crystal = crystal - $needCrystal,
        deuterium = deuterium - $needDeut, food = food - $needFood, water = water - $needWater,
        population = population - $needPop WHERE uid = $uid LIMIT 1");

    $totalPower = (int)$c->power_rating * $qty;
    art_addOwned($s, $uid, $artId, $battery, $qty, $totalPower);
    $s->updatePower($uid);
    return 'Acquired ' . $qty . ' x ' . $c->artillery_name . ' (deployed to ' . $battery . ' battery).';
}

function art_convert(Game $s, int $uid, int $artId, int $qty, string $source): string
{
    if ($qty <= 0) { return 'Quantity must be positive.'; }
    $c = art_fetchPiece($s, $artId);
    if (!$c) { return 'Artillery piece not found.'; }
    $needPer = 0;
    $col = '';
    if ($source === 'attack') { $needPer = (int)$c->attack_convert; $col = 'attack'; }
    elseif ($source === 'defense') { $needPer = (int)$c->defense_convert; $col = 'defense'; }
    elseif ($source === 'untrained') { $needPer = (int)$c->unit_cost; $col = 'untrained'; }
    else { return 'Invalid conversion source.'; }
    if ($needPer <= 0) { return 'This piece cannot be converted from that unit source.'; }
    $needTotal = $needPer * $qty;

    $uQ = $s->query("SELECT $col FROM units WHERE uid = $uid LIMIT 1");
    $u = $uQ ? $uQ->fetch_object() : (object)[$col => 0];
    if ((int)($u->$col ?? 0) < $needTotal) {
        return 'Not enough ' . htmlspecialchars($source, ENT_QUOTES, 'UTF-8') . ' units (need ' . number_format($needTotal) . ').';
    }

    $s->query("UPDATE units SET $col = $col - $needTotal WHERE uid = $uid LIMIT 1");
    $battery = $c->major_class;
    $totalPower = (int)$c->power_rating * $qty;
    art_addOwned($s, $uid, $artId, $battery, $qty, $totalPower);
    $s->updatePower($uid);
    return 'Converted ' . number_format($needTotal) . ' ' . htmlspecialchars($source, ENT_QUOTES, 'UTF-8') . ' units into ' . $qty . ' x ' . $c->artillery_name . '.';
}

function art_sell(Game $s, int $uid, int $artId, int $qty, string $battery): string
{
    if ($qty <= 0) { return 'Quantity must be positive.'; }
    $battery = in_array($battery, ['offense', 'defense', 'reserve'], true) ? $battery : 'reserve';
    $ownQ = $s->query("SELECT * FROM player_artillery WHERE uid = $uid AND artillery_id = $artId AND battery = '$battery' LIMIT 1");
    $own = $ownQ ? $ownQ->fetch_object() : null;
    if (!$own || (int)$own->quantity < $qty) { return 'You do not own that many in ' . $battery . ' battery.'; }
    $c = art_fetchPiece($s, $artId);
    if (!$c) { return 'Artillery piece not found.'; }

    $refund = (int)round((int)$c->naq_cost * $qty * 0.8);
    $metal = (int)round((int)$c->metal_cost * $qty * 0.5);
    $crystal = (int)round((int)$c->crystal_cost * $qty * 0.5);
    $deut = (int)round((int)$c->deut_cost * $qty * 0.5);

    $s->query("UPDATE bank SET onHand = onHand + $refund WHERE uid = $uid LIMIT 1");
    $s->query("UPDATE player_resources SET metal = metal + $metal, crystal = crystal + $crystal, deuterium = deuterium + $deut WHERE uid = $uid LIMIT 1");

    $newQty = (int)$own->quantity - $qty;
    $newPower = max(0, (int)$own->total_power - ((int)$c->power_rating * $qty));
    if ($newQty <= 0) {
        $s->query("DELETE FROM player_artillery WHERE uid = $uid AND artillery_id = $artId AND battery = '$battery' LIMIT 1");
    } else {
        $s->query("UPDATE player_artillery SET quantity = $newQty, total_power = $newPower WHERE uid = $uid AND artillery_id = $artId AND battery = '$battery' LIMIT 1");
    }
    $s->updatePower($uid);
    return 'Sold ' . $qty . ' x ' . $c->artillery_name . ' for ' . number_format($refund) . ' Naquadah (50% of resources refunded).';
}

function art_deploy(Game $s, int $uid, int $artId, int $qty, string $target): string
{
    if ($qty <= 0) { return 'Quantity must be positive.'; }
    $c = art_fetchPiece($s, $artId);
    if (!$c) { return 'Artillery piece not found.'; }
    $major = $c->major_class;
    $powPer = (int)$c->power_rating;
    $target = in_array($target, ['offense', 'defense', 'reserve'], true) ? $target : 'reserve';

    if ($target === 'reserve') {
        $srcQ = $s->query("SELECT * FROM player_artillery WHERE uid = $uid AND artillery_id = $artId AND battery = '$major' LIMIT 1");
        $src = $srcQ ? $srcQ->fetch_object() : null;
        if (!$src || (int)$src->quantity < $qty) {
            return 'Not enough artillery in the ' . $major . ' battery.';
        }
        $s->query("UPDATE player_artillery SET quantity = quantity - $qty, total_power = total_power - " . ($powPer * $qty) . " WHERE uid = $uid AND artillery_id = $artId AND battery = '$major' LIMIT 1");
        art_addOwned($s, $uid, $artId, 'reserve', $qty, $powPer * $qty);
    } else {
        if ($target !== $major) {
            return 'A ' . $major . ' piece cannot be deployed to the ' . $target . ' battery.';
        }
        $srcQ = $s->query("SELECT * FROM player_artillery WHERE uid = $uid AND artillery_id = $artId AND battery = 'reserve' LIMIT 1");
        $src = $srcQ ? $srcQ->fetch_object() : null;
        if (!$src || (int)$src->quantity < $qty) {
            return 'Not enough artillery in reserve.';
        }
        $s->query("UPDATE player_artillery SET quantity = quantity - $qty, total_power = total_power - " . ($powPer * $qty) . " WHERE uid = $uid AND artillery_id = $artId AND battery = 'reserve' LIMIT 1");
        art_addOwned($s, $uid, $artId, $target, $qty, $powPer * $qty);
    }
    $s->query("DELETE FROM player_artillery WHERE uid = $uid AND quantity <= 0");
    $s->updatePower($uid);
    return 'Deployment complete: ' . $qty . ' x ' . $c->artillery_name . ' -> ' . $target . ' battery.';
}

art_seedTables($s, $uid);

$atype = $_REQUEST['atype'] ?? "";
$subject = (int)($_REQUEST['subject'] ?? 0);
$message = (string)($_REQUEST['message'] ?? "");
$qty = (int)($_REQUEST['id'] ?? 0);

if ($atype === 'buy') {
    echo art_buy($s, $uid, $subject, $qty, $message);
    $pagegen->stop();
    print('page generation time: ' . $pagegen->gen());
    exit;
}
if ($atype === 'convert') {
    echo art_convert($s, $uid, $subject, $qty, $message);
    $pagegen->stop();
    print('page generation time: ' . $pagegen->gen());
    exit;
}
if ($atype === 'sell') {
    echo art_sell($s, $uid, $subject, $qty, $message);
    $pagegen->stop();
    print('page generation time: ' . $pagegen->gen());
    exit;
}
if ($atype === 'deploy') {
    echo art_deploy($s, $uid, $subject, $qty, $message);
    $pagegen->stop();
    print('page generation time: ' . $pagegen->gen());
    exit;
}

if (!$s->loggedIn) { header("Location: index.php"); }

$catalog = art_fetchCatalog($s);
$owned = art_owned($s, $uid);
$totals = art_powerTotals($s, $uid);
?>
<table width="100%" border="0">
  <tr>
    <td colspan="2" align="center"><h2>Artillery Command</h2></td>
  </tr>
  <tr>
    <td width="33%" align="center">Offense Battery Power: <?php echo number_format($totals['offense']); ?></td>
    <td width="33%" align="center">Defense Battery Power: <?php echo number_format($totals['defense']); ?></td>
    <td width="34%" align="center">Reserve / Total: <?php echo number_format($totals['reserve']); ?> / <?php echo number_format($totals['count']); ?> pieces</td>
  </tr>
  <tr>
    <td colspan="3" align="center"><em>180-piece catalog (90 offense / 90 defense) across 9 classes x 2 sub-classes x 5 types x 2 sub-types each.</em></td>
  </tr>
  <tr><td colspan="3">&nbsp;</td></tr>
</table>

<table width="100%" border="1">
  <tr>
    <td colspan="9" align="center"><strong>Current Artillery Inventory</strong></td>
  </tr>
  <tr>
    <td>Code</td><td>Name</td><td>Class / Sub-class</td><td>Type / Sub-type</td>
    <td>Battery</td><td>Quantity</td><td>Power</td><td>Deploy</td><td>Sell</td>
  </tr>
  <?php if (count($owned) === 0) { ?>
  <tr><td colspan="9" align="center"><em>No artillery in your arsenal yet. Purchase from the catalog below.</em></td></tr>
  <?php } ?>
  <?php foreach ($owned as $o) {
      $ownId = (int)$o->artillery_id;
      $ownBat = (string)$o->battery;
      $deployTo = ($ownBat === 'reserve') ? $o->major_class : 'reserve';
  ?>
  <tr>
    <td><?php echo htmlspecialchars($o->artillery_code, ENT_QUOTES, 'UTF-8'); ?></td>
    <td><?php echo htmlspecialchars($o->artillery_name, ENT_QUOTES, 'UTF-8'); ?></td>
    <td><?php echo htmlspecialchars($o->class_name, ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars($o->subclass_name, ENT_QUOTES, 'UTF-8'); ?></td>
    <td><?php echo htmlspecialchars($o->type_name, ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars($o->subtype_name, ENT_QUOTES, 'UTF-8'); ?></td>
    <td><?php echo htmlspecialchars($ownBat, ENT_QUOTES, 'UTF-8'); ?></td>
    <td><?php echo number_format((int)$o->quantity); ?></td>
    <td><?php echo number_format((int)$o->total_power); ?></td>
    <td>
      <input name="art_dep_<?php echo $ownId . '_' . $ownBat; ?>" id="art_dep_<?php echo $ownId . '_' . $ownBat; ?>" type="text" value="0" size="5" />
      <a href="javascript:void(0)" onclick="sendData('artillery','get',document.getElementById('art_dep_<?php echo $ownId . '_' . $ownBat; ?>').value,'deploy','<?php echo $ownId; ?>','<?php echo htmlspecialchars($deployTo, ENT_QUOTES); ?>'); return false;">to <?php echo htmlspecialchars($deployTo, ENT_QUOTES); ?></a>
    </td>
    <td>
      <input name="art_sell_<?php echo $ownId . '_' . $ownBat; ?>" id="art_sell_<?php echo $ownId . '_' . $ownBat; ?>" type="text" value="0" size="5" />
      <a href="javascript:void(0)" onclick="sendData('artillery','post',document.getElementById('art_sell_<?php echo $ownId . '_' . $ownBat; ?>').value,'sell','<?php echo $ownId; ?>','<?php echo htmlspecialchars($ownBat, ENT_QUOTES); ?>'); return false;">sell</a>
    </td>
  </tr>
  <?php } ?>
</table>

<br />
<table width="100%" border="0">
  <tr>
    <td align="center"><strong>ARTILLERY CATALOG</strong> - purchase with Naquadah + untrained units + resources, or convert units.</td>
  </tr>
</table>

<?php
function art_renderAttributeList(string $json): string
{
    $items = json_decode($json, true);
    if (!is_array($items)) { return ''; }
    $parts = [];
    foreach ($items as $it) {
        $nm = htmlspecialchars((string)($it['name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $val = (int)($it['value'] ?? 0);
        $sub = htmlspecialchars((string)($it['sub'] ?? ''), ENT_QUOTES, 'UTF-8');
        $sv = (int)($it['sub_value'] ?? 0);
        $parts[] = $nm . ' ' . $val . ($sub !== '' ? ' [' . $sub . ' ' . $sv . ']' : '');
    }
    return implode(', ', $parts);
}

$currentMajor = '';
$currentClass = '';
$currentSub = '';
foreach ($catalog as $art) {
    if ($currentMajor !== $art->major_class) {
        if ($currentMajor !== '') {
            echo "</table><br />\n";
        }
        $currentMajor = $art->major_class;
        $currentClass = '';
        $currentSub = '';
        echo '<table width="100%" border="1">';
        echo '<tr><td colspan="11" align="center" style="background-color:#223"><strong>'
            . htmlspecialchars(ucfirst($currentMajor), ENT_QUOTES, 'UTF-8')
            . ' Artillery (' . ($currentMajor === 'offense' ? '90' : '90') . ' pieces)</strong></td></tr>';
        echo '<tr><td>Class / Sub</td><td>Type / Sub</td><td>Tier</td><td>Power</td>'
            . '<td>Stats (atk/sub, def/sub, shield/sub)</td><td>Attributes</td><td>Costs</td><td>Convert</td><td>Buy</td></tr>';
    }
    if ($currentClass !== $art->class_name || $currentSub !== $art->subclass_name) {
        $currentClass = $art->class_name;
        $currentSub = $art->subclass_name;
        echo '<tr><td colspan="11" align="left" style="background-color:#334"><strong>'
            . htmlspecialchars($currentClass, ENT_QUOTES, 'UTF-8') . ' - '
            . htmlspecialchars($currentSub, ENT_QUOTES, 'UTF-8') . '</strong></td></tr>';
    }
    $aid = (int)$art->artillery_id;
    $convSrc = ($art->major_class === 'offense') ? 'attack' : 'defense';
    $convPer = ($art->major_class === 'offense') ? (int)$art->attack_convert : (int)$art->defense_convert;
    $battery = $art->major_class;
    echo '<tr>';
    echo '<td>' . htmlspecialchars($art->type_name, ENT_QUOTES, 'UTF-8') . ' / ' . htmlspecialchars($art->subtype_name, ENT_QUOTES, 'UTF-8') . '</td>';
    echo '<td>' . (int)$art->tier . '</td>';
    echo '<td>' . number_format((int)$art->power_rating) . '</td>';
    echo '<td>' . number_format((int)$art->attack_stat) . '/' . number_format((int)$art->attack_sub)
        . ' ' . number_format((int)$art->defense_stat) . '/' . number_format((int)$art->defense_sub)
        . ' ' . number_format((int)$art->shield_stat) . '/' . number_format((int)$art->shield_sub)
        . ' | ACC ' . number_format((int)$art->accuracy_stat) . '/' . number_format((int)$art->accuracy_sub)
        . ' RNG ' . number_format((int)$art->range_stat) . '/' . number_format((int)$art->range_sub)
        . ' RLD ' . number_format((int)$art->reload_stat) . '/' . number_format((int)$art->reload_sub)
        . ' MOB ' . number_format((int)$art->mobility_stat) . '/' . number_format((int)$art->mobility_sub) . '</td>';
    echo '<td><small>' . art_renderAttributeList((string)$art->attributes) . '</small></td>';
    echo '<td><small>N' . number_format((int)$art->naq_cost) . ' U' . (int)$art->unit_cost
        . ' M' . number_format((int)$art->metal_cost) . ' C' . number_format((int)$art->crystal_cost)
        . ' D' . number_format((int)$art->deut_cost) . '</small></td>';
    echo '<td><small>' . ($convPer > 0 ? $convPer . ' ' . $convSrc . ' units' : 'n/a') . '</small><br />';
    echo '<input name="art_cv_' . $aid . '" id="art_cv_' . $aid . '" type="text" value="0" size="4" />';
    echo ' <a href="javascript:void(0)" onclick="sendData(\'artillery\',\'post\',document.getElementById(\'art_cv_' . $aid . '\').value,\'convert\',\'' . $aid . '\',\'' . $convSrc . '\'); return false;">convert</a></td>';
    echo '<td><input name="art_by_' . $aid . '" id="art_by_' . $aid . '" type="text" value="0" size="4" />';
    echo ' <a href="javascript:void(0)" onclick="sendData(\'artillery\',\'post\',document.getElementById(\'art_by_' . $aid . '\').value,\'buy\',\'' . $aid . '\',\'' . $battery . '\'); return false;">buy</a></td>';
    echo '</tr>';
}
if ($currentMajor !== '') {
    echo '</table><br />';
}
?>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>
