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
// Unit tests for the pure functions inside scripts/backend/game_tick.php.
// The script cannot be included directly (it runs the tick + exits on missing
// mysqli), so we extract the actual function source text and eval it with a
// fake mysqli handle to exercise the real implementation.

$src = file_get_contents(__DIR__ . '/../scripts/backend/game_tick.php');
if ($src === false) {
    fwrite(STDERR, "cron_pure_functions_test failed: cannot read game_tick.php\n");
    exit(1);
}

$posBonus = strpos($src, 'function stargateBonus');
$posCalc = strpos($src, 'function calcRates');
$posSchema = strpos($src, '// Schema safety');
if ($posBonus === false || $posCalc === false || $posSchema === false || $posBonus >= $posCalc || $posCalc >= $posSchema) {
    fwrite(STDERR, "cron_pure_functions_test failed: marker functions not found\n");
    exit(1);
}
// mysqli is not loaded in the CLI runtime, so the extracted type hint cannot
// match any object. Strip it before eval so the real body is exercised.
$stargateSrc = substr($src, $posBonus, $posCalc - $posBonus);
$stargateSrc = preg_replace('/function stargateBonus\(mysqli \$db/', 'function stargateBonus($db', $stargateSrc, 1);
$calcSrc = substr($src, $posCalc, $posSchema - $posCalc);
eval($stargateSrc);
eval($calcSrc);
if (!function_exists('stargateBonus') || !function_exists('calcRates')) {
    fwrite(STDERR, "cron_pure_functions_test failed: extracted functions unavailable\n");
    exit(1);
}

class FakeCronResult
{
    public $num_rows;
    private $rows;
    private $i = 0;

    public function __construct(int $numRows, ?array $rows)
    {
        $this->num_rows = $numRows;
        $this->rows = $rows ?? [];
    }

    public function fetch_assoc(): ?array
    {
        if ($this->i >= count($this->rows)) {
            return null;
        }
        return $this->rows[$this->i++];
    }

    public function free(): void
    {
    }
}

class FakeCronDb
{
    private $mode;

    public function __construct(string $mode)
    {
        $this->mode = $mode;
    }

    public function query(string $sql)
    {
        if (strpos($sql, 'SHOW TABLES LIKE') === 0) {
            return new FakeCronResult($this->mode === 'no-table' ? 0 : 1, null);
        }
        if (strpos($sql, 'SELECT tech_key') === 0) {
            if ($this->mode === 'no-table') {
                return false;
            }
            return new FakeCronResult(2, [
                ['tech_key' => 'lantian_knowledge_matrix', 'level' => '5'],
                ['tech_key' => 'zero_point_theory', 'level' => '10'],
            ]);
        }
        return false;
    }
}

// --- stargateBonus(): no tech table -> identity bonuses.
$bonus = stargateBonus(new FakeCronDb('no-table'), 1);
if ($bonus !== ['production' => 1.0, 'energy' => 1.0, 'deuterium' => 1.0, 'population' => 1.0]) {
    fwrite(STDERR, "cron_pure_functions_test failed: stargateBonus no-table = " . json_encode($bonus) . "\n");
    exit(1);
}

// --- stargateBonus(): known levels apply documented coefficients.
$bonus = stargateBonus(new FakeCronDb('tech'), 1);
if (abs($bonus['production'] - (1.0 + 5 * 0.008)) > 1e-9) {
    fwrite(STDERR, "cron_pure_functions_test failed: stargateBonus production = {$bonus['production']}\n");
    exit(1);
}
if (abs($bonus['energy'] - (1.0 + 10 * 0.020)) > 1e-9) {
    fwrite(STDERR, "cron_pure_functions_test failed: stargateBonus energy = {$bonus['energy']}\n");
    exit(1);
}
if ($bonus['deuterium'] !== 1.0 || $bonus['population'] !== 1.0) {
    fwrite(STDERR, "cron_pure_functions_test failed: stargateBonus unaffected keys changed\n");
    exit(1);
}

// --- calcRates(): baseline profile (level-1 structures, no tech/planets).
$baseline = [
    'income' => 220,
    'up' => 10,
    'tech_income' => 0,
    'tech_unit_prod' => 0,
    'planet_count' => 1,
];
$lv1 = ['metal_mine' => 1, 'crystal_lab' => 1, 'deuterium_refinery' => 1, 'hydroponics' => 1, 'water_plant' => 1, 'habitat_dome' => 1, 'energy_reactor' => 1];
$sg1 = ['production' => 1.0, 'energy' => 1.0, 'deuterium' => 1.0, 'population' => 1.0];
$rates = calcRates($baseline, $lv1, $sg1);
$expected = ['metal' => 390, 'crystal' => 282, 'deuterium' => 212, 'food' => 276, 'water' => 293, 'population' => 36, 'energy' => 235];
if ($rates !== $expected) {
    fwrite(STDERR, "cron_pure_functions_test failed: calcRates baseline = " . json_encode($rates) . ", expected " . json_encode($expected) . "\n");
    exit(1);
}

// --- calcRates(): production bonus scales metal output.
$sgBoosted = ['production' => 1.5, 'energy' => 1.0, 'deuterium' => 1.0, 'population' => 1.0];
$rates = calcRates($baseline, $lv1, $sgBoosted);
if ($rates['metal'] !== 585) {
    fwrite(STDERR, "cron_pure_functions_test failed: calcRates boosted metal = {$rates['metal']}, expected 585\n");
    exit(1);
}

// --- calcRates(): floors protect degenerate inputs.
$floor = calcRates(['income' => 0, 'up' => 0, 'tech_income' => -5, 'tech_unit_prod' => 0, 'planet_count' => 0], $lv1, $sg1);
if ($floor['metal'] <= 0 || $floor['population'] < 25) {
    fwrite(STDERR, "cron_pure_functions_test failed: calcRates floor = " . json_encode($floor) . "\n");
    exit(1);
}

echo "cron pure functions checks passed\n";
