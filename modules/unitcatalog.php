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
if (!$s->loggedIn) { header("Location: ../index.php"); exit; }

$db    = $s->db_link;
$atype = $_GET['atype'] ?? 'military';
if (!in_array($atype, ['military','civilian','government'])) { $atype = 'military'; }
$status = '';

function uc_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function uc_catalog(): array {
    $catalog = [];
    $id = 1;

    // Military Units (30)
    $mil_classes = ['Infantry', 'Armor', 'Aerospace'];
    $mil_subclasses = ['Assault', 'Recon', 'Support', 'Siege', 'Interceptor', 'Bomber', 'Logistics', 'Command', 'Special Ops', 'Garrison'];
    foreach ($mil_classes as $class) {
        foreach ($mil_subclasses as $subclass) {
            $tier = ($id % 5) + 1;
            $base_cost = 100 * $tier;
            $catalog[] = [
                'unit_id' => $id, 'category' => 'military', 'unit_code' => 'MIL-' . str_pad($id, 3, '0', STR_PAD_LEFT),
                'unit_name' => "$class $subclass Unit", 'class' => $class, 'subclass' => $subclass, 'tier' => $tier,
                'attack_power' => 100 + ($tier * 20), 'defense_power' => 80 + ($tier * 15), 'covert_power' => 0, 'income_gen' => 0,
                'metal_cost' => (int)($base_cost * 1.5),
                'crystal_cost' => (int)($base_cost * 1.2),
                'deut_cost' => (int)($base_cost * 0.8),
                'food_cost' => (int)($base_cost * 0.2),
                'water_cost' => (int)($base_cost * 0.2),
                'pop_cost' => (int)(5 + $tier),
                'crew_cost' => (int)(10 + $tier * 2),
                'description' => "A standard military unit for $subclass roles within the $class corps."
            ];
            $id++;
        }
    }

    // Civilian Units (30)
    $civ_classes = ['Engineering', 'Medical', 'Logistics'];
    $civ_subclasses = ['Construction', 'Mining', 'Terraforming', 'Research', 'Field Medic', 'Surgeon', 'Supply Chain', 'Transport', 'Quartermaster', 'Exploration'];
    foreach ($civ_classes as $class) {
        foreach ($civ_subclasses as $subclass) {
            $tier = ($id % 5) + 1;
            $base_cost = 80 * $tier;
            $catalog[] = [
                'unit_id' => $id, 'category' => 'civilian', 'unit_code' => 'CIV-' . str_pad($id-30, 3, '0', STR_PAD_LEFT),
                'unit_name' => "$class $subclass Team", 'class' => $class, 'subclass' => $subclass, 'tier' => $tier,
                'attack_power' => 0, 'defense_power' => 20 + ($tier * 5), 'covert_power' => 0, 'income_gen' => 50 + ($tier * 10),
                'metal_cost' => (int)($base_cost * 0.8),
                'crystal_cost' => (int)($base_cost * 1.0),
                'deut_cost' => (int)($base_cost * 0.5),
                'food_cost' => (int)($base_cost * 0.5),
                'water_cost' => (int)($base_cost * 0.5),
                'pop_cost' => (int)(2 + $tier),
                'crew_cost' => (int)(5 + $tier),
                'description' => "A civilian support unit specializing in $subclass tasks for the $class branch."
            ];
            $id++;
        }
    }

    // Government Units (30)
    $gov_classes = ['Administration', 'Diplomacy', 'Intelligence'];
    $gov_subclasses = ['Bureaucrat', 'Adjudicator', 'Policy Advisor', 'Tax Collector', 'Ambassador', 'Envoy', 'Negotiator', 'Analyst', 'Agent', 'Propagandist'];
    foreach ($gov_classes as $class) {
        foreach ($gov_subclasses as $subclass) {
            $tier = ($id % 5) + 1;
            $base_cost = 120 * $tier;
            $catalog[] = [
                'unit_id' => $id, 'category' => 'government', 'unit_code' => 'GOV-' . str_pad($id-60, 3, '0', STR_PAD_LEFT),
                'unit_name' => "$class $subclass", 'class' => $class, 'subclass' => $subclass, 'tier' => $tier,
                'attack_power' => 0, 'defense_power' => 10 + ($tier * 5), 'covert_power' => 30 + ($tier * 10), 'income_gen' => 100 + ($tier * 20),
                'metal_cost' => (int)($base_cost * 0.5),
                'crystal_cost' => (int)($base_cost * 1.5),
                'deut_cost' => (int)($base_cost * 1.0),
                'food_cost' => (int)($base_cost * 0.8),
                'water_cost' => (int)($base_cost * 0.8),
                'pop_cost' => (int)(1 + $tier),
                'crew_cost' => (int)(2 + $tier),
                'description' => "A government official for $subclass duties within the $class department."
            ];
            $id++;
        }
    }
    return $catalog;
}

function uc_seed_catalog(mysqli $db, int $uid): void {
    $countRes = $db->query("SELECT COUNT(*) as c FROM unit_catalog");
    $count = $countRes ? (int)$countRes->fetch_object()->c : 0;

    if ($count < 90) {
        $catalog = uc_catalog();
        $stmt = $db->prepare("INSERT IGNORE INTO unit_catalog (unit_id, category, unit_code, unit_name, class, subclass, tier, attack_power, defense_power, covert_power, income_gen, metal_cost, crystal_cost, deut_cost, food_cost, water_cost, pop_cost, crew_cost, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_player = $db->prepare("INSERT IGNORE INTO player_unit_owned (uid, unit_id, quantity) VALUES (?, ?, 0)");

        foreach ($catalog as $u) {
            $stmt->bind_param("isssssiiiiiiiiiiis", $u['unit_id'], $u['category'], $u['unit_code'], $u['unit_name'], $u['class'], $u['subclass'], $u['tier'], $u['attack_power'], $u['defense_power'], $u['covert_power'], $u['income_gen'], $u['metal_cost'], $u['crystal_cost'], $u['deut_cost'], $u['food_cost'], $u['water_cost'], $u['pop_cost'], $u['crew_cost'], $u['description']);
            $stmt->execute();

            $stmt_player->bind_param("ii", $uid, $u['unit_id']);
            $stmt_player->execute();
        }
    }
}

uc_seed_catalog($db);

// Tier color map
function tierColor(int $tier): string {
    $colors = [1=>'#888',2=>'#7a9',3=>'#6af',4=>'#4cf',5=>'#fc0'];
    return $colors[$tier] ?? '#aaa';
}

function catIcon(string $cat): string {
    return ['military'=>'&#9876;','civilian'=>'&#9955;','government'=>'&#9817;'][$cat] ?? '&#9679;';
}

if (!empty($_POST) && isset($_GET['id']) && $_GET['id'] === 'recruit_unit') {
    $uid = (int)$_SESSION['userid'];
    $unitId = isset($_POST['unit_id']) ? (int)$_POST['unit_id'] : 0;
    $amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;

    if ($amount > 0 && $unitId > 0) {
        $unitQ = $db->prepare("SELECT * FROM unit_catalog WHERE unit_id = ?");
        $unitQ->bind_param("i", $unitId);
        $unitQ->execute();
        $unit = $unitQ->get_result()->fetch_object();

        $resQ = $db->prepare("SELECT * FROM player_resources WHERE uid = ?");
        $resQ->bind_param("i", $uid);
        $resQ->execute();
        $res = $resQ->get_result()->fetch_object();

        $needMetal = $unit->metal_cost * $amount;
        $needCrystal = $unit->crystal_cost * $amount;
        $needDeut = $unit->deut_cost * $amount;
        $needFood = $unit->food_cost * $amount;
        $needWater = $unit->water_cost * $amount;
        $needPop = $unit->pop_cost * $amount;

        if ($res->metal < $needMetal || $res->crystal < $needCrystal || $res->deuterium < $needDeut || $res->food < $needFood || $res->water < $needWater || $res->population < $needPop) {
            $status = "Insufficient resources to recruit " . uc_h($unit->unit_name) . ".";
        } else {
            $db->query("UPDATE player_resources SET metal=metal-{$needMetal}, crystal=crystal-{$needCrystal}, deuterium=deuterium-{$needDeut}, food=food-{$needFood}, water=water-{$needWater}, population=population-{$needPop} WHERE uid={$uid}");
            $db->query("UPDATE player_unit_owned SET quantity=quantity+{$amount} WHERE uid={$uid} AND unit_id={$unitId}");
            $status = "Successfully recruited " . number_format($amount) . "x " . uc_h($unit->unit_name) . ".";
        }
    } else {
        $status = "Invalid recruitment request.";
    }
}
?>
<style>
.uc-shell { font-family: inherit; }
.uc-tabs  { display:flex; gap:4px; margin-bottom:14px; border-bottom:2px solid #444; padding-bottom:6px; }
.uc-tab   { padding:6px 20px; background:#222; color:#aaa; border-radius:4px 4px 0 0; text-decoration:none; font-size:.9em; cursor:pointer; }
.uc-tab.active, .uc-tab:hover { background:#336; color:#adf; }
.uc-grid  { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:10px; }
.uc-card  { background:#111; border:1px solid #333; border-radius:4px; padding:12px; cursor:pointer; transition:border-color .15s; }
.uc-card:hover { border-color:#55a; background:#16162a; }
.uc-code  { font-size:.75em; color:#666; font-family:monospace; }
.uc-name  { font-size:1.05em; font-weight:bold; margin:4px 0 2px; }
.uc-rank  { font-size:.82em; color:#aaa; }
.uc-class { font-size:.78em; color:#888; margin-top:4px; }
.uc-tier-badge { display:inline-block; padding:1px 7px; border-radius:10px; font-size:.75em; font-weight:bold; margin-left:6px; color:#000; }
.uc-stats { display:flex; gap:8px; margin-top:8px; font-size:.8em; flex-wrap:wrap; }
.uc-stat  { background:#1a1a2e; border:1px solid #334; border-radius:3px; padding:2px 7px; }
.uc-costs { display:flex; gap:6px; margin-top:6px; font-size:.75em; color:#89a; flex-wrap:wrap; }
.uc-cost  { background:#222; border-radius:3px; padding:1px 5px; }
/* Detail modal */
#uc-modal { display:none; position:fixed; top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.8);z-index:9999;overflow:auto; }
.uc-modal-box { background:#0d0d1a; color:#ccc; border:1px solid #446; border-radius:6px; max-width:680px; margin:40px auto; padding:28px; position:relative; }
.uc-modal-box h2 { margin-top:0; color:#adf; }
.uc-modal-close { position:absolute;top:12px;right:16px;background:none;border:none;color:#888;font-size:20px;cursor:pointer; }
.uc-detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin:14px 0; font-size:.88em; }
.uc-detail-row  { background:#111; border:1px solid #2a2a3e; border-radius:3px; padding:6px 10px; }
.uc-detail-row label { color:#668; display:block; font-size:.8em; margin-bottom:2px; }
.uc-ability { background:#1a1a2e; border:1px solid #44c; border-radius:4px; padding:10px 14px; margin:12px 0; font-size:.88em; }
.uc-lore { background:#0a0a14; border-left:3px solid #446; padding:12px 16px; font-size:.85em; line-height:1.6; color:#aaa; font-style:italic; margin-top:12px; }
.uc-recruit-form { margin-top:20px; padding-top:16px; border-top:1px solid #334; }
.uc-recruit-form h4 { margin:0 0 10px; color:#afc; }
.uc-recruit-form input[type=number] { width:100px; }
.uc-recruit-form input[type=submit] { margin-left:8px; }
.uc-nav { margin-bottom:12px; display:flex; align-items:center; justify-content:space-between; }
</style>

<div class="uc-shell">

<div class="uc-nav">
  <strong>&#9741; Unit Catalog</strong>
  <span style="font-size:.85em;color:#668">90 units across 3 categories &mdash; Click any card to view full details</span>
</div>

<?php if ($status !== '') { ?>
    <div class="card full" style="margin-bottom:12px;"><strong><?= uc_h($status); ?></strong></div>
<?php } ?>

<div class="uc-tabs">
  <?php foreach(['military'=>'&#9876; Military','civilian'=>'&#9955; Civilian','government'=>'&#9817; Government'] as $cat=>$label): ?>
  <a class="uc-tab<?= $atype===$cat?' active':'' ?>" href="javascript:void(0)"
     onclick="sendData('unitcatalog','get','0','<?= $cat ?>')"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<?php
// Fetch all units in category
$stmt = $db->prepare("SELECT * FROM unit_catalog WHERE category=? ORDER BY tier ASC, unit_id ASC");
$stmt->bind_param("s", $atype);
$stmt->execute();
$units = $stmt->get_result();

echo '<div class="uc-grid">';
$currentTier = 0;
$rows = [];
while ($u = $units->fetch_object()) { $rows[] = $u; }

foreach ($rows as $u):
    if ($u->tier !== $currentTier) {
        if ($currentTier > 0) { echo '</div><div class="uc-grid">'; }
        $tc = tierColor($u->tier);
        echo '<div style="grid-column:1/-1;margin-top:10px;border-bottom:1px solid #333;padding-bottom:4px;">';
        echo '<span style="color:'.$tc.';font-weight:bold;">&#9670; Tier '.$u->tier.'</span> ';
        echo '</div>';
        $currentTier = $u->tier;
    }
    $tc = tierColor($u->tier);
?>
<div class="uc-card" onclick="showUnit(<?= $u->unit_id ?>)">
  <div class="uc-code"><?= htmlspecialchars($u->unit_code) ?>
    <span class="uc-tier-badge" style="background:<?= $tc ?>">T<?= $u->tier ?></span>
  </div>
  <div class="uc-name"><?= htmlspecialchars($u->unit_name) ?></div>
  <div class="uc-class">Class: <?= htmlspecialchars($u->class) ?> / <?= htmlspecialchars($u->subclass) ?></div>
  <div class="uc-stats">
    <?php if ($u->attack_power  > 0): ?><span class="uc-stat" title="Attack">&#9876; <?= number_format($u->attack_power) ?></span><?php endif; ?>
    <?php if ($u->defense_power > 0): ?><span class="uc-stat" title="Defense">&#128737; <?= number_format($u->defense_power) ?></span><?php endif; ?>
    <?php if ($u->covert_power  > 0): ?><span class="uc-stat" title="Covert">&#128373; <?= number_format($u->covert_power) ?></span><?php endif; ?>
    <?php if ($u->income_gen    > 0): ?><span class="uc-stat" title="Income">&#9670; <?= number_format($u->income_gen) ?></span><?php endif; ?>
  </div>
  <div class="uc-costs">
    <?php if ($u->metal_cost > 0): ?><span class="uc-cost" title="Metal">M:<?= number_format($u->metal_cost) ?></span><?php endif; ?>
    <?php if ($u->crystal_cost > 0): ?><span class="uc-cost" title="Crystal">C:<?= number_format($u->crystal_cost) ?></span><?php endif; ?>
    <?php if ($u->deut_cost > 0): ?><span class="uc-cost" title="Deuterium">D:<?= number_format($u->deut_cost) ?></span><?php endif; ?>
    <?php if ($u->food_cost > 0): ?><span class="uc-cost" title="Food">F:<?= number_format($u->food_cost) ?></span><?php endif; ?>
    <?php if ($u->water_cost > 0): ?><span class="uc-cost" title="Water">W:<?= number_format($u->water_cost) ?></span><?php endif; ?>
    <?php if ($u->pop_cost > 0): ?><span class="uc-cost" title="Population">P:<?= number_format($u->pop_cost) ?></span><?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
</div>

</div>

<!-- Detail modal -->
<div id="uc-modal">
  <div class="uc-modal-box">
    <button class="uc-modal-close" onclick="document.getElementById('uc-modal').style.display='none'">&#x2715;</button>
    <div id="uc-modal-body"></div>
  </div>
</div>

<script>
var ucUnits = <?php
$allQ = $db->prepare("SELECT * FROM unit_catalog ORDER BY unit_id ASC");
$allRows = [];
if ($allQ) {
    $allQ->execute();
    $r = $allQ->get_result();
    if ($r) {
        $allRows = $r->fetch_all(MYSQLI_ASSOC);
    }
}
echo json_encode(array_column($allRows, null, 'unit_id'));
?>;

function showUnit(id) {
    var u = ucUnits[id];
    if (!u) return;
    var tc = tierColors[u.tier] || '#aaa';
    var html = '<h2>' + esc(u.unit_name) + '</h2>';
    html += '<div style="font-size:.85em;color:#668;margin-bottom:12px">' + esc(u.class) + ' / ' + esc(u.subclass) + '</div>';
    html += '<div class="uc-detail-grid">';
    html += drow('Unit Code',      u.unit_code);
    html += drow('Category',       u.category.charAt(0).toUpperCase()+u.category.slice(1));
    html += drow('Tier',           '<span style="color:'+tc+';font-weight:bold">Tier ' + u.tier + '</span>');
    html += drow('Attack Power',   u.attack_power > 0  ? num(u.attack_power)  : '—');
    html += drow('Defense Power',  u.defense_power > 0 ? num(u.defense_power) : '—');
    html += drow('Covert Power',   u.covert_power > 0  ? num(u.covert_power)  : '—');
    html += drow('Income/Turn',    u.income_gen > 0    ? num(u.income_gen) + ' Naq' : '—');
    html += drow('Metal Cost',     u.metal_cost > 0 ? num(u.metal_cost) : '—');
    html += drow('Crystal Cost',   u.crystal_cost > 0 ? num(u.crystal_cost) : '—');
    html += drow('Deuterium Cost', u.deut_cost > 0 ? num(u.deut_cost) : '—');
    html += drow('Food Cost',      u.food_cost > 0 ? num(u.food_cost) : '—');
    html += drow('Water Cost',     u.water_cost > 0 ? num(u.water_cost) : '—');
    html += drow('Population Cost',u.pop_cost > 0 ? num(u.pop_cost) : '—');
    html += '</div>';
    html += '<div class="uc-lore">' + esc(u.description) + '</div>';

    html += '<div class="uc-recruit-form">';
    html += '<h4>Recruit Unit</h4>';
    html += '<form action="javascript:void(0)" onsubmit="sendData(\'unitcatalog\',\'post\',\'recruit_unit\'); return false;">';
    html += '<input type="hidden" name="unit_id" value="' + u.unit_id + '">';
    html += '<label>Amount: <input type="number" name="amount" min="1" max="10000" value="1"></label>';
    html += '<input type="submit" value="Recruit">';
    html += '</form>';
    html += '</div>';

    document.getElementById('uc-modal-body').innerHTML = html;
    document.getElementById('uc-modal').style.display = 'block';
}
var tierColors={1:'#888',2:'#7a9',3:'#6af',4:'#4cf',5:'#fc0'};
function drow(l,v){return '<div class="uc-detail-row"><label>'+l+'</label>'+v+'</div>';}
function esc(s){var d=document.createElement('div');d.appendChild(document.createTextNode(String(s||'')));return d.innerHTML;}
function num(n){return Number(n).toLocaleString();}
document.getElementById('uc-modal').addEventListener('click',function(e){if(e.target===this)this.style.display='none';});
</script>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>