<?php
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

$title = formalLeaderboardTitle(1, 500000, 4000000);
if ($title['title'] !== 'Supreme Sovereign' || $title['band'] !== 'Legendary' || $title['prestige'] < 95) {
    fwrite(STDERR, "formalLeaderboardTitle failed: " . json_encode($title) . "\n");
    exit(1);
}

echo "formal logic checks passed\n";
