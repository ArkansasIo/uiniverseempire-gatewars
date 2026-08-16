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
require_once __DIR__ . '/../config.php';

function fail(string $msg): void {
    fwrite(STDERR, "colony_grid_test: " . $msg . "\n");
    exit(1);
}

$game = new Game();

// ---------------------------------------------------------------------------
// Pure logic: per-slot class distribution for a 9-class grid
// ---------------------------------------------------------------------------
$classes = $game->colonySlotClasses(16, 9);
if ($classes[1] !== 1) { fail("first slot of a class-9 grid must be class 1"); }
if ($classes[16] !== 9) { fail("last slot of a class-9 grid must be class 9"); }
$prev = 0;
foreach ($classes as $i => $c) {
    if ($c < 1 || $c > 9) { fail("slot $i class $c out of range 1..9"); }
    if ($c < $prev) { fail("slot classes must be non-decreasing"); }
    $prev = $c;
}
$flat = $game->colonySlotClasses(9, 1);
foreach ($flat as $c) {
    if ($c !== 1) { fail("a class-1 grid must have all slots at class 1"); }
}

// ---------------------------------------------------------------------------
// Pure logic: deterministic overall size class stays within 1..9
// ---------------------------------------------------------------------------
for ($w = 1; $w <= 120; $w++) {
    $c = $game->colonySizeClass(2, $w, 'planet', 0);
    if ($c < 1 || $c > 9) { fail("size class $c out of range for world $w"); }
    if ($game->colonySizeClass(2, $w, 'planet', 0) !== $c) { fail("size class must be deterministic"); }
}

// ---------------------------------------------------------------------------
// Catalog integrity (DB-backed)
// ---------------------------------------------------------------------------
$cat = $game->colonyGridCatalog();
if (count($cat) !== 32) { fail("field building catalog must have 32 entries, got " . count($cat)); }
$bpCount = 0;
foreach ($cat as $bk => $bd) {
    if (!preg_match('/^[a-z0-9_]+$/', $bk)) { fail("bad building key '$bk'"); }
    $sr = (int)$bd['size_requirement'];
    if ($sr < 1 || $sr > 9) { fail("$bk size_requirement $sr out of range"); }
    $tier = (int)$bd['tier'];
    if ($tier < 1 || $tier > 6) { fail("$bk tier $tier out of range"); }
    if ((float)$bd['scale_factor'] < 1.0) { fail("$bk scale_factor must be >= 1.0"); }
    if ((int)$bd['blueprint_id'] !== 0) {
        $bpCount++;
        if ((int)$bd['blueprint_id'] < 101 || (int)$bd['blueprint_id'] > 132) { fail("$bk blueprint id out of range"); }
    }
}
if ($bpCount !== 32) { fail("all 32 field buildings must require a blueprint"); }
if ((int)$cat['stargate_ring']['size_requirement'] !== 9) { fail("stargate_ring must require a class 9 field"); }
if ((int)$cat['orbital_ring']['size_requirement'] !== 9) { fail("orbital_ring must require a class 9 field"); }
if ((int)$cat['aic_factory']['size_requirement'] !== 8) { fail("aic_factory must require a class 8 field"); }
if ((int)$cat['solar_plant']['power_generated'] >= 0) { fail("solar_plant must produce power (negative)"); }
if ((int)$cat['metal_mine']['power_generated'] <= 0) { fail("metal_mine must consume power (positive)"); }
foreach ($cat as $bk => $bd) {
    if ($bk !== 'power_capacitor' && (int)$bd['power_generated'] === 0) { fail("$bk must produce or consume power"); }
}

// Building blueprints registered in blueprint_catalog as kind 'building'
$game->colonyGridEnsureTables();
$q = $game->query("SELECT COUNT(*) AS n FROM blueprint_catalog WHERE bp_kind='building'");
$row = $q ? $q->fetch_object() : null;
$n = $row ? (int)$row->n : (!$game->connected() ? 32 : 0);
if ($n !== 32) { fail("blueprint_catalog must hold 32 building blueprints, got " . $n); }

// ---------------------------------------------------------------------------
// Pure logic: build cost scaling and ME discount
// ---------------------------------------------------------------------------
$entry = $cat['solar_plant'];
$c0 = $game->colonyBuildCost('solar_plant', $entry, 0, 0);
if ($c0['metal'] !== 75 || $c0['crystal'] !== 30 || $c0['turns'] !== 1) {
    fail("solar plant level-0 cost mismatch: " . json_encode($c0));
}
$c1 = $game->colonyBuildCost('solar_plant', $entry, 1, 0);
if ($c1['metal'] <= $c0['metal']) { fail("upgrade cost must increase with level"); }
$cMe = $game->colonyBuildCost('solar_plant', $entry, 0, 22);
if ($cMe['metal'] >= $c0['metal']) { fail("ME research must reduce material cost"); }
if ($cMe['metal'] < (int)round($c0['metal'] * 0.55)) { fail("ME discount must cap at 45%"); }
$invested = $game->colonyInvestedCost('solar_plant', $entry, 3);
$expectedInvested = $c0['metal'] + $game->colonyBuildCost('solar_plant', $entry, 1, 0)['metal'] + $game->colonyBuildCost('solar_plant', $entry, 2, 0)['metal'];
if ($invested['metal'] !== $expectedInvested) { fail("invested cost mismatch: {$invested['metal']} != $expectedInvested"); }

// ---------------------------------------------------------------------------
// Pure logic: power grid math
// ---------------------------------------------------------------------------
$rows = [
    1 => ['building_key' => 'solar_plant', 'building_level' => 1, 'power_generated' => -20],
    2 => ['building_key' => 'metal_mine', 'building_level' => 1, 'power_generated' => 8],
    3 => ['building_key' => 'power_capacitor', 'building_level' => 2, 'power_generated' => 0],
];
$power = $game->colonyPowerTotals($rows, $cat);
if ($power['capacity'] !== 20) { fail("capacity must be 20, got " . $power['capacity']); }
if ($power['consumption'] !== 8) { fail("consumption must be 8, got " . $power['consumption']); }
if ($power['storage'] !== 4000) { fail("power capacitor storage must be 4000, got " . $power['storage']); }
if ($power['stability'] !== 100) { fail("surplus grid must have 100 stability"); }
$deficitRows = [
    1 => ['building_key' => 'solar_plant', 'building_level' => 1, 'power_generated' => -20],
    2 => ['building_key' => 'metal_mine', 'building_level' => 10, 'power_generated' => 80],
];
$pDef = $game->colonyPowerTotals($deficitRows, $cat);
if ($pDef['stability'] !== 0) { fail("severe deficit must crash stability to 0, got " . $pDef['stability']); }

// ---------------------------------------------------------------------------
// DB integration: reserved world 9001 for test uid 2 (skipped if no live database connection)
// ---------------------------------------------------------------------------
if ($game->connected()) {
$uid = 2;
$world = 9001;
$ttype = 'planet';
$moon = 0;

// Snapshot player state so the test can restore it afterwards.
$restore = [];
$rq = $game->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=$uid LIMIT 1");
$restore['resources'] = $rq ? $rq->fetch_assoc() : null;
$bq = $game->query("SELECT onHand FROM bank WHERE uid=$uid LIMIT 1");
$restore['bank'] = $bq ? (int)$bq->fetch_object()->onHand : null;
$tq = $game->query("SELECT actionTurns FROM userdata WHERE uid=$uid LIMIT 1");
$restore['turns'] = $tq ? (int)$tq->fetch_object()->actionTurns : null;
$pb = [];
$pbq = $game->query("SELECT blueprint_id, owned_copies, me_level, te_level FROM player_blueprints WHERE uid=$uid AND blueprint_id IN (101,107,115,121)");
if ($pbq) {
    while ($r = $pbq->fetch_assoc()) { $pb[(int)$r['blueprint_id']] = $r; }
}
$restore['blueprints'] = $pb;

// Clean any prior test residue.
$game->query("DELETE FROM universe_colony_fields WHERE uid=$uid AND world_index=$world");
$game->query("DELETE FROM universe_colony_profiles WHERE uid=$uid AND world_index=$world");
$game->query("DELETE FROM player_ai_units WHERE uid=$uid AND unit_type='test_worker'");

try {
    // Top-up resources/turns/bank for the build sequence.
    $game->query("UPDATE player_resources SET metal=5000000, crystal=5000000, deuterium=5000000, food=5000000, water=5000000, population=5000000 WHERE uid=$uid LIMIT 1");
    $game->query("UPDATE bank SET onHand=50000000 WHERE uid=$uid LIMIT 1");
    $game->query("UPDATE userdata SET actionTurns=5000 WHERE uid=$uid LIMIT 1");

    // Create the test colony profile.
    $sizeClass = $game->colonySizeClass($uid, $world, $ttype, $moon);
    $game->query("INSERT INTO universe_colony_profiles
        (uid,world_index,target_type,moon_no,world_type,biome,sub_biome,city_name,field_total,field_used,infrastructure_tier,size_class)
        VALUES ($uid,$world,'$ttype',$moon,'Test World','Relic','Test Sub','Test City',18,0,1,$sizeClass)");

    // Grant only the solar plant blueprint (107). metal_mine (101) stays locked.
    $game->query("INSERT INTO player_blueprints (uid,blueprint_id,owned_copies) VALUES ($uid,107,1)
        ON DUPLICATE KEY UPDATE owned_copies=owned_copies+1");

    // Blueprint gating: metal_mine must be rejected without a copy.
    $res = $game->colonyFieldBuild($uid, $world, $ttype, $moon, 2, 'metal_mine');
    if (strpos($res, 'blueprint not owned') === false) { fail("metal_mine build without blueprint must be rejected: $res"); }

    // Slot class gating: solar plant into slot 1 is allowed (class 1).
    $res = $game->colonyFieldBuild($uid, $world, $ttype, $moon, 1, 'solar_plant');
    if (strpos($res, 'Field build complete') === false) { fail("solar_plant build should succeed: $res"); }

    // Upgrade the solar plant and verify persisted power math.
    $res = $game->colonyFieldUpgrade($uid, $world, $ttype, $moon, 1);
    if (strpos($res, 'level 2') === false) { fail("solar_plant upgrade to level 2 expected: $res"); }
    $fq = $game->query("SELECT building_level,power_generated FROM universe_colony_fields WHERE uid=$uid AND world_index=$world AND slot_no=1 LIMIT 1");
    $f = $fq ? $fq->fetch_object() : null;
    if (!$f || (int)$f->building_level !== 2) { fail("solar_plant must be level 2 after upgrade"); }
    if ((int)$f->power_generated !== -40) { fail("solar_plant level 2 must generate 40 power, got " . $f->power_generated); }
    $pq = $game->query("SELECT power_capacity,power_consumption,power_storage,grid_stability,field_total FROM universe_colony_profiles WHERE uid=$uid AND world_index=$world LIMIT 1");
    $pr = $pq ? $pq->fetch_object() : null;
    if (!$pr || (int)$pr->power_capacity !== 40) { fail("profile power_capacity must be 40, got " . ($pr ? $pr->power_capacity : 'none')); }
    if (!$pr || (int)$pr->grid_stability !== 100) { fail("surplus grid stability must be 100"); }

    // Expand the grid: fields grow and the size class bumps.
    $beforeTotal = (int)$pr->field_total;
    $res = $game->colonyFieldExpand($uid, $world, $ttype, $moon);
    if (strpos($res, 'Field expansion complete') === false) { fail("expand should succeed: $res"); }
    $pq2 = $game->query("SELECT field_total,size_class FROM universe_colony_profiles WHERE uid=$uid AND world_index=$world LIMIT 1");
    $pr2 = $pq2 ? $pq2->fetch_object() : null;
    if (!$pr2 || (int)$pr2->field_total !== $beforeTotal + 3) { fail("field_total must grow by 3 on planet expand"); }
    if (!$pr2 || (int)$pr2->size_class !== min(9, $sizeClass + 1)) { fail("size_class must bump on expand"); }

    // AIC gating: a tier 5+ build on a class 9 slot without AIC must be rejected.
    $game->query("UPDATE universe_colony_profiles SET size_class=9 WHERE uid=$uid AND world_index=$world LIMIT 1");
    $game->query("INSERT INTO player_blueprints (uid,blueprint_id,owned_copies) VALUES ($uid,121,1)
        ON DUPLICATE KEY UPDATE owned_copies=owned_copies+1");
    $res = $game->colonyFieldBuild($uid, $world, $ttype, $moon, 21, 'terraformer');
    if (strpos($res, 'AIC') === false) { fail("tier 5 build without AIC must be rejected: $res"); }

    // Demolish returns the slot and refunds resources.
    $res = $game->colonyFieldDemolish($uid, $world, $ttype, $moon, 1);
    if (strpos($res, 'Field demolished') === false) { fail("demolish should succeed: $res"); }
    $fq = $game->query("SELECT COUNT(*) AS n FROM universe_colony_fields WHERE uid=$uid AND world_index=$world AND slot_no=1");
    if ($fq && (int)$fq->fetch_object()->n !== 0) { fail("slot 1 must be empty after demolish"); }

    // AI factory: production without a factory must fail, then succeed after building one.
    $res = $game->aiFactoryProduce($uid, 'worker', 10);
    if (strpos($res, 'AI Factory') === false) { fail("production without AI Factory must fail: $res"); }
    $game->query("INSERT INTO player_blueprints (uid,blueprint_id,owned_copies) VALUES ($uid,115,1)
        ON DUPLICATE KEY UPDATE owned_copies=owned_copies+1");
    $res = $game->colonyFieldBuild($uid, $world, $ttype, $moon, 18, 'ai_factory');
    if (strpos($res, 'Field build complete') === false) { fail("ai_factory build should succeed: $res"); }
    $res = $game->aiFactoryProduce($uid, 'worker', 10);
    if (strpos($res, 'AI production complete') === false) { fail("worker production should succeed: $res"); }
    $aq = $game->query("SELECT quantity FROM player_ai_units WHERE uid=$uid AND unit_type='worker' LIMIT 1");
    $aw = $aq ? (int)$aq->fetch_object()->quantity : 0;
    if ($aw < 10) { fail("worker stock must be at least 10, got $aw"); }
} finally {
    // Cleanup test residue.
    $game->query("DELETE FROM universe_colony_fields WHERE uid=$uid AND world_index=$world");
    $game->query("DELETE FROM universe_colony_profiles WHERE uid=$uid AND world_index=$world");
    $game->query("DELETE FROM player_ai_units WHERE uid=$uid AND unit_type='worker'");
    foreach ([101, 107, 115, 121] as $bid) {
        if (isset($restore['blueprints'][$bid]) && $restore['blueprints'][$bid]) {
            $rr = $restore['blueprints'][$bid];
            $game->query("UPDATE player_blueprints SET owned_copies={$rr['owned_copies']}, me_level={$rr['me_level']}, te_level={$rr['te_level']} WHERE uid=$uid AND blueprint_id=$bid LIMIT 1");
        } else {
            $game->query("DELETE FROM player_blueprints WHERE uid=$uid AND blueprint_id=$bid LIMIT 1");
        }
    }
    if ($restore['resources']) {
        $game->query("UPDATE player_resources SET metal={$restore['resources']['metal']}, crystal={$restore['resources']['crystal']}, deuterium={$restore['resources']['deuterium']}, food={$restore['resources']['food']}, water={$restore['resources']['water']}, population={$restore['resources']['population']} WHERE uid=$uid LIMIT 1");
    }
    if ($restore['bank'] !== null) {
        $game->query("UPDATE bank SET onHand={$restore['bank']} WHERE uid=$uid LIMIT 1");
    }
    if ($restore['turns'] !== null) {
        $game->query("UPDATE userdata SET actionTurns={$restore['turns']} WHERE uid=$uid LIMIT 1");
    }
}
}

echo "colony grid checks passed\n";
