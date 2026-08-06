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
require __DIR__ . '/../modules/formal_logic.php';

$cost = formalCostValue(1000, 2, 1.2, 0.8);
if ($cost !== 3744) {
    fwrite(STDERR, "formalCostValue failed: {$cost}\n");
    exit(1);
}

$readiness = formalReadinessIndex(180000, 260, 8, 900000, 12);
if ($readiness !== 100) {
    fwrite(STDERR, "formalReadinessIndex failed: {$readiness}\n");
    exit(1);
}

$hp = formalBossHp(4, 12);
if ($hp !== 300000 + (4 * 120000) + (12 * 2500)) {
    fwrite(STDERR, "formalBossHp failed: {$hp}\n");
    exit(1);
}

$generation = formalPowerNodeOutput(100, 2, 80, 10, 'generator');
if ($generation !== 99) {
    fwrite(STDERR, "formalPowerNodeOutput failed: {$generation}\n");
    exit(1);
}

$load = formalPowerNodeLoad(40, 70, 'balanced');
if ($load !== 36) {
    fwrite(STDERR, "formalPowerNodeLoad failed: {$load}\n");
    exit(1);
}

$delta = formalPowerGridDelta(15, 2, 8.0);
if ($delta !== 240) {
    fwrite(STDERR, "formalPowerGridDelta failed: {$delta}\n");
    exit(1);
}

$state = formalPowerGridState(40, 10, 5000, 10000, 15, 2);
if ($state['stability_index'] !== 55 || $state['blackout_risk'] !== 0 || $state['stored_energy'] !== 5002) {
    fwrite(STDERR, "formalPowerGridState failed: " . json_encode($state) . "\n");
    exit(1);
}

$endfield = formalArknitEndfieldPower(3, 100, 40, 20);
if ($endfield['generation'] !== 124 || $endfield['stability'] !== 47 || $endfield['risk'] !== 16) {
    fwrite(STDERR, "formalArknitEndfieldPower failed: " . json_encode($endfield) . "\n");
    exit(1);
}

$title = formalLeaderboardTitle(1, 500000, 4000000);
if ($title['title'] !== 'Supreme Sovereign' || $title['band'] !== 'Legendary' || $title['prestige'] < 95) {
    fwrite(STDERR, "formalLeaderboardTitle failed: " . json_encode($title) . "\n");
    exit(1);
}

$titleDisplay = formalTitleDisplay('Imperial Warden', 'Elite', 87);
if ($titleDisplay !== 'Imperial Warden (Elite) - 87 prestige') {
    fwrite(STDERR, "formalTitleDisplay failed: {$titleDisplay}\n");
    exit(1);
}

$arcBoss = formalArcBossProfile(3, 40, 7);
if ($arcBoss['phase'] !== 'Phase 2' || $arcBoss['reward'] !== 180000 + (3 * 55000) + (40 * 2200) || $arcBoss['name'] === '') {
    fwrite(STDERR, "formalArcBossProfile failed: " . json_encode($arcBoss) . "\n");
    exit(1);
}

$raidProfile = formalGalaxyRaidProfile(2, 5, 12);
if ($raidProfile['reward'] !== 54000 + (2 * 7000) + (5 * 1500) + (12 * 900) || $raidProfile['target'] !== 'G2-S5') {
    fwrite(STDERR, "formalGalaxyRaidProfile failed: " . json_encode($raidProfile) . "\n");
    exit(1);
}

echo "formal logic checks passed\n";
