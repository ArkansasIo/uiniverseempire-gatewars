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
// Unit tests for the pure functions of the unified tick engine
// (base/GameTick.class.php): GameTick::resourceRates() and
// GameTick::stargateCoefficients(). These mirror the former inline
// calcRates()/stargateBonus() helpers of scripts/backend/game_tick.php.

require_once __DIR__ . '/../config.php';

// --- stargateCoefficients(): no tech levels -> identity bonuses.
$bonus = GameTick::stargateCoefficients([]);
if ($bonus !== ['production' => 1.0, 'energy' => 1.0, 'deuterium' => 1.0, 'population' => 1.0]) {
    fwrite(STDERR, "cron_pure_functions_test failed: stargateCoefficients empty = " . json_encode($bonus) . "\n");
    exit(1);
}

// --- stargateCoefficients(): known levels apply documented coefficients.
$bonus = GameTick::stargateCoefficients(['lantian_knowledge_matrix' => 5, 'zero_point_theory' => 10]);
if (abs($bonus['production'] - (1.0 + 5 * 0.008)) > 1e-9) {
    fwrite(STDERR, "cron_pure_functions_test failed: stargateCoefficients production = {$bonus['production']}\n");
    exit(1);
}
if (abs($bonus['energy'] - (1.0 + 10 * 0.020)) > 1e-9) {
    fwrite(STDERR, "cron_pure_functions_test failed: stargateCoefficients energy = {$bonus['energy']}\n");
    exit(1);
}
if ($bonus['deuterium'] !== 1.0 || $bonus['population'] !== 1.0) {
    fwrite(STDERR, "cron_pure_functions_test failed: stargateCoefficients unaffected keys changed\n");
    exit(1);
}

// --- resourceRates(): baseline profile (level-1 structures, no tech/planets).
$baseline = [
    'income' => 220,
    'up' => 10,
    'tech_income' => 0,
    'tech_unit_prod' => 0,
    'planet_count' => 1,
];
$lv1 = ['metal_mine' => 1, 'crystal_lab' => 1, 'deuterium_refinery' => 1, 'hydroponics' => 1, 'water_plant' => 1, 'habitat_dome' => 1, 'energy_reactor' => 1];
$sg1 = ['production' => 1.0, 'energy' => 1.0, 'deuterium' => 1.0, 'population' => 1.0];
$rates = GameTick::resourceRates($baseline, $lv1, $sg1);
$expected = ['metal' => 390, 'crystal' => 282, 'deuterium' => 212, 'food' => 276, 'water' => 293, 'population' => 36, 'energy' => 235];
if ($rates !== $expected) {
    fwrite(STDERR, "cron_pure_functions_test failed: resourceRates baseline = " . json_encode($rates) . ", expected " . json_encode($expected) . "\n");
    exit(1);
}

// --- resourceRates(): production bonus scales metal output.
$sgBoosted = ['production' => 1.5, 'energy' => 1.0, 'deuterium' => 1.0, 'population' => 1.0];
$rates = GameTick::resourceRates($baseline, $lv1, $sgBoosted);
if ($rates['metal'] !== 585) {
    fwrite(STDERR, "cron_pure_functions_test failed: resourceRates boosted metal = {$rates['metal']}, expected 585\n");
    exit(1);
}

// --- resourceRates(): floors protect degenerate inputs.
$floor = GameTick::resourceRates(['income' => 0, 'up' => 0, 'tech_income' => -5, 'tech_unit_prod' => 0, 'planet_count' => 0], $lv1, $sg1);
if ($floor['metal'] <= 0 || $floor['population'] < 25) {
    fwrite(STDERR, "cron_pure_functions_test failed: resourceRates floor = " . json_encode($floor) . "\n");
    exit(1);
}

// --- computeTradeTransfer(): bounded by rate / remaining / on-hand.
if (GameTick::computeTradeTransfer(50, 100, 30) !== 30) {
    fwrite(STDERR, "cron_pure_functions_test failed: computeTradeTransfer on-hand bound\n");
    exit(1);
}
if (GameTick::computeTradeTransfer(50, 20, 100) !== 20) {
    fwrite(STDERR, "cron_pure_functions_test failed: computeTradeTransfer remaining bound\n");
    exit(1);
}

echo "cron pure functions checks passed\n";
