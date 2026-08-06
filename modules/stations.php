<?php
/*
 * MIT License
 *
 * Copyright (c) 2026 Stargate Wars contributors
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
include_once(__DIR__ . '/entity_name_helpers.php');
include_once(__DIR__ . '/formal_logic.php');

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn || !isset($_GET['time'])) {
    header("Location: index.php?");
    exit;
}

$uid = (int)$_SESSION['userid'];
$status = '';

function sb_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function sb_num($value): string {
    return number_format((float)$value);
}

$s->query("CREATE TABLE IF NOT EXISTS player_resources (
    uid INT NOT NULL PRIMARY KEY,
    metal BIGINT NOT NULL DEFAULT 80000,
    crystal BIGINT NOT NULL DEFAULT 60000,
    deuterium BIGINT NOT NULL DEFAULT 45000,
    food BIGINT NOT NULL DEFAULT 55000,
    water BIGINT NOT NULL DEFAULT 55000,
    population BIGINT NOT NULL DEFAULT 120000,
    last_tick_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$s->query("CREATE TABLE IF NOT EXISTS space_installations (
    uid INT NOT NULL PRIMARY KEY,
    space_station_level INT NOT NULL DEFAULT 0,
    starbase_level INT NOT NULL DEFAULT 0,
    moon_base_level INT NOT NULL DEFAULT 0,
    defense_grid INT NOT NULL DEFAULT 0,
    dock_matrix INT NOT NULL DEFAULT 0,
    scan_array INT NOT NULL DEFAULT 0,
    starbase_name VARCHAR(64) NOT NULL DEFAULT 'Starbase',
    moon_base_name VARCHAR(64) NOT NULL DEFAULT 'Moon Base',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
$s->query("ALTER TABLE space_installations ADD COLUMN IF NOT EXISTS starbase_name VARCHAR(64) NOT NULL DEFAULT 'Starbase'");
$s->query("ALTER TABLE space_installations ADD COLUMN IF NOT EXISTS moon_base_name VARCHAR(64) NOT NULL DEFAULT 'Moon Base'");

$s->query("INSERT IGNORE INTO player_resources (uid) VALUES (" . $uid . ")");
$s->query("INSERT IGNORE INTO space_installations (uid) VALUES (" . $uid . ")");

if (isset($_GET['id']) && $_GET['id'] === 'rename') {
    $entity = isset($_GET['entity']) ? preg_replace('/[^a-z]/', '', strtolower((string)$_GET['entity'])) : '';
    $nameInput = trim((string)(isset($_GET['new_name']) ? $_GET['new_name'] : ''));
    if ($entity === 'starbase') {
        $safeName = buildDisplayName($nameInput, 'Starbase');
        $s->query("UPDATE space_installations SET starbase_name='" . dbSafeEntityName($safeName) . "' WHERE uid=" . $uid . " LIMIT 1");
        $status = 'Starbase renamed to ' . sb_h($safeName) . '.';
    } elseif ($entity === 'moonbase') {
        $safeName = buildDisplayName($nameInput, 'Moon Base');
        $s->query("UPDATE space_installations SET moon_base_name='" . dbSafeEntityName($safeName) . "' WHERE uid=" . $uid . " LIMIT 1");
        $status = 'Moon Base renamed to ' . sb_h($safeName) . '.';
    }
}

$installQ = $s->query("SELECT space_station_level,starbase_level,moon_base_level,defense_grid,dock_matrix,scan_array,starbase_name,moon_base_name FROM space_installations WHERE uid=" . $uid . " LIMIT 1");
$install = $installQ ? $installQ->fetch_object() : (object)[
    'space_station_level' => 0,
    'starbase_level' => 0,
    'moon_base_level' => 0,
    'defense_grid' => 0,
    'dock_matrix' => 0,
    'scan_array' => 0,
    'starbase_name' => 'Starbase',
    'moon_base_name' => 'Moon Base',
];

$resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
$res = $resQ ? $resQ->fetch_object() : (object)[
    'metal' => 0,
    'crystal' => 0,
    'deuterium' => 0,
    'food' => 0,
    'water' => 0,
    'population' => 0,
];

if (isset($_GET['id']) && $_GET['id'] === 'upgrade') {
    $kind = isset($_GET['atype']) ? strtolower((string)$_GET['atype']) : '';
    $defs = [
        'spacestation' => [
            'field' => 'space_station_level',
            'name' => 'Space Station',
            'base' => ['metal' => 18000, 'crystal' => 9000, 'deuterium' => 4500, 'food' => 3000, 'water' => 3000, 'population' => 220],
            'scale' => 1.55,
        ],
        'starbase' => [
            'field' => 'starbase_level',
            'name' => 'Starbase',
            'base' => ['metal' => 26000, 'crystal' => 15000, 'deuterium' => 9000, 'food' => 4200, 'water' => 4200, 'population' => 320],
            'scale' => 1.62,
        ],
        'moonbase' => [
            'field' => 'moon_base_level',
            'name' => 'Moon Base',
            'base' => ['metal' => 22000, 'crystal' => 13000, 'deuterium' => 8200, 'food' => 3600, 'water' => 3600, 'population' => 280],
            'scale' => 1.58,
        ],
    ];

    if (!isset($defs[$kind])) {
        $status = 'Unknown installation type.';
    } else {
        $def = $defs[$kind];
        $field = $def['field'];
        $curr = (int)($install->$field ?? 0);

        if ($kind === 'starbase' && (int)$install->space_station_level < 2) {
            $status = 'Starbase requires Space Station level 2+';
        } elseif ($kind === 'moonbase' && (int)$install->starbase_level < 1) {
            $status = 'Moon Base requires Starbase level 1+';
        } else {
            $cost = [];
            foreach ($def['base'] as $k => $v) {
                $cost[$k] = formalCostValue((int)$v, $curr, (float)$def['scale'], 0.12);
            }

            if ((int)$res->metal < $cost['metal'] || (int)$res->crystal < $cost['crystal'] || (int)$res->deuterium < $cost['deuterium'] || (int)$res->food < $cost['food'] || (int)$res->water < $cost['water'] || (int)$res->population < $cost['population']) {
                $status = 'Insufficient resources for ' . $def['name'] . ' upgrade.';
            } else {
                $s->query("UPDATE player_resources SET
                    metal=metal-" . (int)$cost['metal'] . ",
                    crystal=crystal-" . (int)$cost['crystal'] . ",
                    deuterium=deuterium-" . (int)$cost['deuterium'] . ",
                    food=food-" . (int)$cost['food'] . ",
                    water=water-" . (int)$cost['water'] . ",
                    population=population-" . (int)$cost['population'] . "
                    WHERE uid=" . $uid . " LIMIT 1");

                $s->query("UPDATE space_installations SET " . $field . "=" . $field . "+1 WHERE uid=" . $uid . " LIMIT 1");

                $installQ = $s->query("SELECT space_station_level,starbase_level,moon_base_level,defense_grid,dock_matrix,scan_array FROM space_installations WHERE uid=" . $uid . " LIMIT 1");
                $install = $installQ ? $installQ->fetch_object() : $install;

                $defenseGrid = ((int)$install->starbase_level * 8) + ((int)$install->moon_base_level * 5);
                $dockMatrix = ((int)$install->space_station_level * 3) + ((int)$install->starbase_level * 4);
                $scanArray = ((int)$install->moon_base_level * 7) + ((int)$install->space_station_level * 2);

                $s->query("UPDATE space_installations SET defense_grid=" . $defenseGrid . ", dock_matrix=" . $dockMatrix . ", scan_array=" . $scanArray . " WHERE uid=" . $uid . " LIMIT 1");
                $install->defense_grid = $defenseGrid;
                $install->dock_matrix = $dockMatrix;
                $install->scan_array = $scanArray;

                $resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
                $res = $resQ ? $resQ->fetch_object() : $res;

                $status = $def['name'] . ' upgraded to level ' . ($curr + 1) . '.';
            }
        }
    }
}

$spaceStationLv = (int)($install->space_station_level ?? 0);
$starbaseLv = (int)($install->starbase_level ?? 0);
$moonBaseLv = (int)($install->moon_base_level ?? 0);
$fleetCapacity = formalPowerValue(20, $spaceStationLv, 1.08) + formalPowerValue(15, $starbaseLv, 1.07) + formalPowerValue(10, $moonBaseLv, 1.06);
$missionSafety = min(35, formalPowerValue(2, $starbaseLv, 1.05) + formalPowerValue(3, $moonBaseLv, 1.05));

?>
<div class="page-hub">
    <div class="page-hub-head">
        <h3>Orbital Base Command</h3>
        <p>Create and upgrade Space Stations, Starbases, and Moon Bases for fleet and expansion control.</p>
    </div>

    <?php if ($status !== '') { ?>
    <div class="card full"><strong><?= sb_h($status); ?></strong></div>
    <?php } ?>

    <div class="page-grid">
        <div class="card full">
            <div class="feature-hero"><img src="images/ui/operations-console.svg" alt="Orbital bases" /><div><h4>Orbital Base Command</h4><p>Scale stations, starbases, and moon bases into a layered defense and fleet control network.</p></div></div>
        </div>

        <div class="card">
            <h4>Installation Levels</h4>
            <p><strong>Space Station:</strong> <?= sb_num($spaceStationLv); ?></p>
            <p><strong>Starbase:</strong> <?= sb_num($starbaseLv); ?> <span>(<?= sb_h(buildDisplayName((string)($install->starbase_name ?? ''), 'Starbase')); ?>)</span></p>
            <p><strong>Moon Base:</strong> <?= sb_num($moonBaseLv); ?> <span>(<?= sb_h(buildDisplayName((string)($install->moon_base_name ?? ''), 'Moon Base')); ?>)</span></p>
            <p><strong>Defense Grid:</strong> <?= sb_num((int)$install->defense_grid); ?></p>
            <p><strong>Dock Matrix:</strong> <?= sb_num((int)$install->dock_matrix); ?></p>
            <p><strong>Scan Array:</strong> <?= sb_num((int)$install->scan_array); ?></p>
        </div>

        <div class="card">
            <h4>Strategic Effects</h4>
            <p><strong>Fleet Capacity Bonus:</strong> <?= sb_num($fleetCapacity); ?></p>
            <p><strong>Expedition Safety Bonus:</strong> <?= sb_num($missionSafety); ?>%</p>
            <p><a href="javascript:void(0)" onclick="sendData('fleetdock','get','mainDisplay'); return false">Open Fleet Dock</a></p>
            <p><a href="javascript:void(0)" onclick="sendData('pages','get','universe','expedition'); return false">Open Expedition Planner</a></p>
        </div>

        <div class="card full">
            <h4>Upgrade Controls</h4>
            <p><a href="javascript:void(0)" onclick="sendData('stations','get','upgrade','spacestation'); return false">Upgrade Space Station</a> (unlocks advanced orbital logistics)</p>
            <p><a href="javascript:void(0)" onclick="sendData('stations','get','upgrade','starbase'); return false">Upgrade Starbase</a> (requires Space Station level 2+)</p>
            <p><a href="javascript:void(0)" onclick="sendData('stations','get','upgrade','moonbase'); return false">Upgrade Moon Base</a> (requires Starbase level 1+)</p>
        </div>

        <div class="card full">
            <h4>Rename Orbital Installations</h4>
            <form method="get" action="modules/stations.php">
                <input type="hidden" name="id" value="rename">
                <input type="hidden" name="time" value="<?= time(); ?>">
                <p><label>Target <select name="entity"><option value="starbase">Starbase</option><option value="moonbase">Moon Base</option></select></label>
                <label>New Name <input type="text" name="new_name" maxlength="64" value="" /></label>
                <button type="submit">Rename</button></p>
            </form>
        </div>

        <div class="card full">
            <h4>Resource Stockpile</h4>
            <p><strong>Metal:</strong> <?= sb_num((int)$res->metal); ?> | <strong>Crystal:</strong> <?= sb_num((int)$res->crystal); ?> | <strong>Deuterium:</strong> <?= sb_num((int)$res->deuterium); ?></p>
            <p><strong>Food:</strong> <?= sb_num((int)$res->food); ?> | <strong>Water:</strong> <?= sb_num((int)$res->water); ?> | <strong>Population:</strong> <?= sb_num((int)$res->population); ?></p>
        </div>

        <div class="card full">
            <h4>Base Doctrine</h4>
            <ul>
                <li>Space Station improves orbital infrastructure and ship handling.</li>
                <li>Starbase increases defensive projection and fleet staging depth.</li>
                <li>Moon Base increases scanning reach and mission survivability windows.</li>
            </ul>
        </div>
    </div>
</div>
<?php
echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>