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

function formalLeaderboardTitle($rank, $armySize, $treasury) {
    $rank = max(1, (int)$rank);
    $armySize = max(0, (int)$armySize);
    $treasury = max(0, (int)$treasury);

    $prestige = min(100, (int)round((1000 / max(1, $rank)) + ($armySize / 40000) + ($treasury / 5000000) * 30));
    $band = 'Novice';
    if ($prestige >= 90) {
        $band = 'Legendary';
    } elseif ($prestige >= 70) {
        $band = 'Elite';
    } elseif ($prestige >= 45) {
        $band = 'Veteran';
    } elseif ($prestige >= 20) {
        $band = 'Rising';
    }

    $titles = [
        'Novice' => 'Rookie Commander',
        'Rising' => 'Frontier Marshal',
        'Veteran' => 'Sovereign Captain',
        'Elite' => 'Imperial Warden',
        'Legendary' => 'Supreme Sovereign',
    ];

    return [
        'title' => $titles[$band],
        'band' => $band,
        'prestige' => $prestige,
    ];
}

function formalPowerNodeOutput($basePower, $level = 0, $integrity = 100, $boost = 0, $nodeType = 'generator') {
    $basePower = max(0, (float)$basePower);
    $level = max(0, (int)$level);
    $integrity = max(0, min(100, (int)$integrity));
    $boost = max(0, (int)$boost);
    $nodeType = strtolower((string)$nodeType);

    $integrityFactor = $integrity / 100.0;
    $levelFactor = 1 + ($level * 0.06);
    $typeFactor = $nodeType === 'storage' ? 0.55 : ($nodeType === 'relay' ? 0.8 : 1.0);
    $scaled = $basePower * $levelFactor * $typeFactor * $integrityFactor;
    return (int)round($scaled * (1 + ($boost / 100.0)));
}

function formalPowerNodeLoad($baseLoad, $integrity = 100, $loadMode = 'balanced') {
    $baseLoad = max(0, (float)$baseLoad);
    $integrity = max(0, min(100, (int)$integrity));
    $loadMode = strtolower((string)$loadMode);

    $integrityFactor = $integrity / 100.0;
    $modeFactor = $loadMode === 'surge' ? 1.08 : ($loadMode === 'eco' ? 0.8 : 1.0);
    return (int)round($baseLoad * $modeFactor * (1.04 - ($integrityFactor * 0.2)));
}

function formalPowerGridDelta($netMw, $ticks = 1, $efficiency = 8.0) {
    $netMw = (float)$netMw;
    $ticks = max(0, (int)$ticks);
    $efficiency = max(0.1, (float)$efficiency);
    return (int)round($netMw * $ticks * $efficiency);
}

function formalPowerGridState($stability, $risk, $storedEnergy, $storageCapacity, $ticks = 1, $netDelta = 0) {
    $stability = max(0, min(100, (int)$stability));
    $risk = max(0, min(100, (int)$risk));
    $storedEnergy = max(0, (int)$storedEnergy);
    $storageCapacity = max(10000, (int)$storageCapacity);
    $ticks = max(0, (int)$ticks);
    $netDelta = (int)$netDelta;

    $nextStored = max(0, min($storageCapacity, $storedEnergy + $netDelta));
    $nextStability = $netDelta >= 0 ? min(100, $stability + $ticks) : max(0, $stability - ($ticks * 2));
    $nextRisk = $netDelta >= 0 ? max(0, $risk - $ticks) : min(100, $risk + ($ticks * 2));

    if ($nextStored < (int)round($storageCapacity * 0.1)) {
        $nextRisk = min(100, $nextRisk + 5);
    }
    if ($nextStored > (int)round($storageCapacity * 0.6)) {
        $nextRisk = max(0, $nextRisk - 3);
    }

    return [
        'stored_energy' => $nextStored,
        'stability_index' => $nextStability,
        'blackout_risk' => $nextRisk,
    ];
}
