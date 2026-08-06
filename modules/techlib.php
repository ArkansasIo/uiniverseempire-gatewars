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

function tl_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function tl_num($value): string {
    return number_format((float)$value);
}

function tl_theme_class(string $key): string {
    $map = [
        'research_campus' => 'tech-accent-cyan',
        'data_vault' => 'tech-accent-blue',
        'simulation_core' => 'tech-accent-amber',
        'quantum_archive' => 'tech-accent-violet',
        'ai_directorate' => 'tech-accent-gold',
    ];

    return $map[$key] ?? 'tech-accent-cyan';
}

function tl_catalog(): array {
    return [
        [
            'key' => 'research_campus',
            'name' => 'Research Campus',
            'base' => ['nq' => 120000, 'metal' => 9000, 'crystal' => 13000, 'deut' => 3500, 'energy' => 1200],
            'scale' => 1.57,
            'effect' => 'Research speed +3.0% per level.'
        ],
        [
            'key' => 'data_vault',
            'name' => 'Data Vault',
            'base' => ['nq' => 145000, 'metal' => 7000, 'crystal' => 16000, 'deut' => 2600, 'energy' => 1000],
            'scale' => 1.58,
            'effect' => 'Technology cost reduction +1.5% per level.'
        ],
        [
            'key' => 'simulation_core',
            'name' => 'Simulation Core',
            'base' => ['nq' => 165000, 'metal' => 10500, 'crystal' => 12000, 'deut' => 5200, 'energy' => 1400],
            'scale' => 1.59,
            'effect' => 'Research speed +1.5% and battle modeling quality +2.5% per level.'
        ],
        [
            'key' => 'quantum_archive',
            'name' => 'Quantum Archive',
            'base' => ['nq' => 220000, 'metal' => 12000, 'crystal' => 19000, 'deut' => 6400, 'energy' => 1800],
            'scale' => 1.61,
            'effect' => 'Technology cost reduction +1.0% and archive quality +2.0% per level.'
        ],
        [
            'key' => 'ai_directorate',
            'name' => 'AI Research Directorate',
            'base' => ['nq' => 310000, 'metal' => 18000, 'crystal' => 24000, 'deut' => 9000, 'energy' => 2500],
            'scale' => 1.63,
            'effect' => 'Research speed +2.0% and technology cost reduction +0.5% per level.'
        ],
    ];
}

$catalog = tl_catalog();
$catalogByKey = [];
foreach ($catalog as $row) {
    $catalogByKey[$row['key']] = $row;
}

$s->query("CREATE TABLE IF NOT EXISTS player_resources (
    uid INT NOT NULL PRIMARY KEY,
    metal BIGINT NOT NULL DEFAULT 80000,
    crystal BIGINT NOT NULL DEFAULT 60000,
    deuterium BIGINT NOT NULL DEFAULT 45000,
    food BIGINT NOT NULL DEFAULT 55000,
    water BIGINT NOT NULL DEFAULT 55000,
    population BIGINT NOT NULL DEFAULT 120000,
    energy BIGINT NOT NULL DEFAULT 50000,
    last_tick_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
$s->query("ALTER TABLE player_resources ADD COLUMN IF NOT EXISTS energy BIGINT NOT NULL DEFAULT 50000");
$s->query("INSERT IGNORE INTO player_resources (uid) VALUES (" . $uid . ")");

$s->query("CREATE TABLE IF NOT EXISTS research_infrastructure (
    uid INT NOT NULL PRIMARY KEY,
    research_campus INT NOT NULL DEFAULT 0,
    data_vault INT NOT NULL DEFAULT 0,
    simulation_core INT NOT NULL DEFAULT 0,
    quantum_archive INT NOT NULL DEFAULT 0,
    ai_directorate INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
$s->query("INSERT IGNORE INTO research_infrastructure (uid) VALUES (" . $uid . ")");

if (isset($_GET['id']) && $_GET['id'] === 'upgrade') {
    $key = isset($_GET['atype']) ? strtolower((string)$_GET['atype']) : '';
    if (!isset($catalogByKey[$key])) {
        $status = 'Unknown Tech Library building.';
    } else {
        $lvlQ = $s->query("SELECT " . $key . " AS lvl FROM research_infrastructure WHERE uid=" . $uid . " LIMIT 1");
        $cur = ($lvlQ && $lvlQ->num_rows > 0) ? (int)($lvlQ->fetch_object()->lvl ?? 0) : 0;

        $def = $catalogByKey[$key];
        $costNq = formalCostValue((int)$def['base']['nq'], $cur, (float)$def['scale'], 0.12);
        $costM = formalCostValue((int)$def['base']['metal'], $cur, (float)$def['scale'], 0.12);
        $costC = formalCostValue((int)$def['base']['crystal'], $cur, (float)$def['scale'], 0.12);
        $costD = formalCostValue((int)$def['base']['deut'], $cur, (float)$def['scale'], 0.12);
        $costE = formalCostValue((int)$def['base']['energy'], $cur, (float)$def['scale'], 0.12);

        $bankQ = $s->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
        $bank = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];
        $resQ = $s->query("SELECT metal,crystal,deuterium,energy FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
        $res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'energy' => 0];

        if ((int)$bank->onHand < $costNq || (int)$res->metal < $costM || (int)$res->crystal < $costC || (int)$res->deuterium < $costD || (int)$res->energy < $costE) {
            $status = 'Insufficient resources for ' . $def['name'] . ' level ' . ($cur + 1) . '.';
        } else {
            $s->query("UPDATE bank SET onHand=onHand-" . $costNq . " WHERE uid=" . $uid . " LIMIT 1");
            $s->query("UPDATE player_resources SET metal=metal-" . $costM . ", crystal=crystal-" . $costC . ", deuterium=deuterium-" . $costD . ", energy=energy-" . $costE . " WHERE uid=" . $uid . " LIMIT 1");
            $s->query("UPDATE research_infrastructure SET " . $key . "=" . $key . "+1 WHERE uid=" . $uid . " LIMIT 1");
            $status = $def['name'] . ' upgraded to level ' . ($cur + 1) . '.';
        }
    }
}

$infraQ = $s->query("SELECT research_campus, data_vault, simulation_core, quantum_archive, ai_directorate FROM research_infrastructure WHERE uid=" . $uid . " LIMIT 1");
$infra = $infraQ ? $infraQ->fetch_object() : (object)[
    'research_campus' => 0,
    'data_vault' => 0,
    'simulation_core' => 0,
    'quantum_archive' => 0,
    'ai_directorate' => 0,
];

$levels = [
    'research_campus' => (int)$infra->research_campus,
    'data_vault' => (int)$infra->data_vault,
    'simulation_core' => (int)$infra->simulation_core,
    'quantum_archive' => (int)$infra->quantum_archive,
    'ai_directorate' => (int)$infra->ai_directorate,
];

$costDiscount = min(45, formalResearchBonus(1.5, $levels['data_vault']) + formalResearchBonus(1.0, $levels['quantum_archive']) + formalResearchBonus(0.5, $levels['ai_directorate']));
$researchSpeed = 1 + formalResearchBonus(0.03, $levels['research_campus']) + formalResearchBonus(0.015, $levels['simulation_core']) + formalResearchBonus(0.02, $levels['ai_directorate']);
$modelQuality = 1 + formalResearchBonus(0.025, $levels['simulation_core']) + formalResearchBonus(0.02, $levels['quantum_archive']);

$resQ = $s->query("SELECT metal,crystal,deuterium,energy FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
$res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'energy' => 0];
$bankQ = $s->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
$bank = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];
?>
<div class="tech-shell">
    <div class="tech-hero">
        <div class="feature-hero">
            <img src="images/ui/research.svg" alt="Tech library" />
            <div>
                <h3>Tech Library Command Deck</h3>
                <p>Upgrade research infrastructure like a fleet command center, with every major payoff visible at a glance.</p>
            </div>
        </div>
        <div class="tech-hero-badge">Research Flow <?= tl_num($researchSpeed); ?>x</div>
    </div>

    <?php if ($status !== '') { ?>
    <div class="tech-alert"><strong><?= tl_h($status); ?></strong></div>
    <?php } ?>

    <div class="tech-overview-grid">
        <div class="tech-card tech-card-accent">
            <h4>Research Reserves</h4>
            <div class="tech-stat-grid">
                <div class="tech-stat-pill"><span>Naquadah</span><strong><?= tl_num((int)$bank->onHand); ?></strong></div>
                <div class="tech-stat-pill"><span>Metal</span><strong><?= tl_num((int)$res->metal); ?></strong></div>
                <div class="tech-stat-pill"><span>Crystal</span><strong><?= tl_num((int)$res->crystal); ?></strong></div>
                <div class="tech-stat-pill"><span>Energy</span><strong><?= tl_num((int)$res->energy); ?></strong></div>
            </div>
        </div>

        <div class="tech-card">
            <h4>Library Effects</h4>
            <ul class="tech-list">
                <li><span>Research Speed</span><strong><?= tl_num($researchSpeed); ?>x</strong></li>
                <li><span>Cost Reduction</span><strong><?= tl_num($costDiscount); ?>%</strong></li>
                <li><span>Battle Model Quality</span><strong><?= tl_num($modelQuality); ?>x</strong></li>
            </ul>
            <p><a href="javascript:void(0)" onclick="sendData('stargatetech','get','mainDisplay'); return false">Open Empire Tech</a></p>
            <p><a href="javascript:void(0)" onclick="sendData('pages','get','research','techlib'); return false">Open Research Tech Tree</a></p>
            <p><small>Each tier compounds the payoff of the next, so building toward the archive line creates a snowball effect for your empire.</small></p>
        </div>

        <div class="tech-card tech-card-wide">
            <h4>Infrastructure Matrix</h4>
            <div class="tech-grid">
                <?php foreach ($catalog as $row) {
                    $cur = (int)($levels[$row['key']] ?? 0);
                    $needNq = formalCostValue((int)$row['base']['nq'], $cur, (float)$row['scale'], 0.12);
                    $needM = formalCostValue((int)$row['base']['metal'], $cur, (float)$row['scale'], 0.12);
                    $needC = formalCostValue((int)$row['base']['crystal'], $cur, (float)$row['scale'], 0.12);
                    $needD = formalCostValue((int)$row['base']['deut'], $cur, (float)$row['scale'], 0.12);
                    $needE = formalCostValue((int)$row['base']['energy'], $cur, (float)$row['scale'], 0.12);
                ?>
                <article class="tech-item <?= tl_theme_class($row['key']); ?>">
                    <div class="tech-item-head">
                        <h5><?= tl_h($row['name']); ?></h5>
                        <span class="tech-badge">Lv <?= tl_num($cur); ?></span>
                    </div>
                    <p><?= tl_h($row['effect']); ?></p>
                    <div class="tech-costs">
                        <span>Next cost</span>
                        <strong><?= tl_num($needNq); ?>/<?= tl_num($needM); ?>/<?= tl_num($needC); ?>/<?= tl_num($needD); ?>/<?= tl_num($needE); ?></strong>
                    </div>
                    <a class="tech-action" href="javascript:void(0)" onclick="sendData('techlib','get','upgrade','<?= tl_h($row['key']); ?>'); return false">Upgrade</a>
                </article>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<?php

echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>
