<?php

function formalCostValue($base, $level = 0, $multiplier = 1.15, $growth = 0.8) {
    $level = max(0, (int)$level);
    $base = (float)$base;
    $multiplier = (float)$multiplier;
    $growth = (float)$growth;
    return (int)round($base * pow($multiplier, $level) * (1 + ($growth * $level)));
}

function formalTimeValue($baseTurns, $level = 0, $factor = 1.1) {
    $level = max(0, (int)$level);
    $baseTurns = max(1, (int)$baseTurns);
    $factor = (float)$factor;
    return max(1, (int)ceil($baseTurns * pow($factor, $level)));
}

function formalPowerValue($basePower, $level = 0, $factor = 1.08) {
    $level = max(0, (int)$level);
    $basePower = (float)$basePower;
    $factor = (float)$factor;
    return (int)round($basePower * pow($factor, $level));
}

function formalReadinessIndex($armySize, $up, $colonies, $treasury, $turns) {
    $armySize = max(0, (int)$armySize);
    $up = max(0, (int)$up);
    $colonies = max(0, (int)$colonies);
    $treasury = max(0, (int)$treasury);
    $turns = max(1, (int)$turns);

    $armyScore = ($armySize / 100000) * 35;
    $upScore = ($up / 1000) * 25;
    $colonyScore = ($colonies / $turns) * 20;
    $treasuryScore = ($treasury / 1000000) * 20;

    return (int)round(min(100, $armyScore + $upScore + $colonyScore + $treasuryScore));
}

function formalBossHp($bossLevel, $threatLevel) {
    $bossLevel = max(1, (int)$bossLevel);
    $threatLevel = max(0, (int)$threatLevel);
    return (int)(300000 + ($bossLevel * 120000) + ($threatLevel * 2500));
}

function formalResourcePressure($income, $treasury) {
    $income = max(0, (int)$income);
    $treasury = max(0, (int)$treasury);
    return $income > 0 ? (int)round($treasury / $income) : 0;
}

function formalUpgradeState($currentLevel, $maxLevel, $progressRatio) {
    $currentLevel = max(0, (int)$currentLevel);
    $maxLevel = max(1, (int)$maxLevel);
    $progressRatio = max(0, min(1, (float)$progressRatio));
    return [
        'level' => $currentLevel,
        'max_level' => $maxLevel,
        'percent' => (int)round($progressRatio * 100),
        'can_progress' => $currentLevel < $maxLevel,
    ];
}

function formalCombatOutcome($attackPower, $defensePower) {
    $attackPower = max(0, (int)$attackPower);
    $defensePower = max(0, (int)$defensePower);
    $attackScore = max(1, $attackPower);
    $defenseScore = max(1, $defensePower);
    $ratio = $attackScore / max(1, $defenseScore);
    $winChance = min(98, max(5, (int)round(50 + (($ratio - 1) * 20))));
    return [
        'win_chance' => $winChance,
        'advantage' => $ratio > 1 ? 'attack' : ($ratio < 1 ? 'defense' : 'balanced'),
    ];
}

function formalPlanetYield($habitability, $size, $waterBonus = 0) {
    $habitability = max(0, (int)$habitability);
    $size = max(1, (int)$size);
    $waterBonus = max(0, (int)$waterBonus);
    return (int)round((($habitability / 10) + ($size * 2) + $waterBonus) * 1.35);
}

function formalResearchBonus($baseBonus, $researchLevel) {
    $baseBonus = (float)$baseBonus;
    $researchLevel = max(0, (int)$researchLevel);
    return (float)round($baseBonus * (1 + ($researchLevel * 0.06)), 2);
}
