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
require __DIR__ . '/../modules/ogame_research_logic.php';

function fail(string $msg): void {
    fwrite(STDERR, $msg . "\n");
    exit(1);
}

$catalog = ogameTechCatalog();

if (count($catalog) !== 40) {
    fail("Catalog must have 40 programs, got " . count($catalog));
}

$research = 0;
$technology = 0;
$keys = [];
foreach ($catalog as $def) {
    $keys[$def['key']] = true;
    if ($def['branch'] === 'research') {
        $research++;
    } elseif ($def['branch'] === 'technology') {
        $technology++;
    } else {
        fail("Unexpected branch '" . $def['branch'] . "' for " . $def['key']);
    }
    if ($def['tier'] < 1 || $def['tier'] > 6) {
        fail($def['key'] . " has out-of-range tier " . $def['tier']);
    }
    foreach (['nq', 'metal', 'crystal', 'deut', 'energy'] as $k) {
        if ($def['base'][$k] <= 0) {
            fail($def['key'] . " base cost $k is not positive");
        }
    }
    if ($def['scale'] <= 1) {
        fail($def['key'] . " scale must be > 1");
    }
    if ($def['base_turns'] <= 0) {
        fail($def['key'] . " base_turns must be positive");
    }
    if ($def['max_level'] !== 25) {
        fail($def['key'] . " max_level must be 25");
    }
    foreach ($def['prereq'] as $need) {
        if (!isset($keys[$need['key']])) {
            fail($def['key'] . " prereq '" . $need['key'] . "' does not exist in catalog");
        }
        if ($need['level'] < 1) {
            fail($def['key'] . " prereq level must be >= 1");
        }
    }
}
if ($research !== 20 || $technology !== 20) {
    fail("Expected 20 research + 20 technology, got $research + $technology");
}

$domains = [];
foreach ($catalog as $def) {
    $domains[$def['domain']][] = $def;
}
if (count($domains) !== 10) {
    fail("Expected 10 domains, got " . count($domains));
}
foreach ($domains as $domain => $items) {
    $researchInDomain = 0;
    $technologyInDomain = 0;
    foreach ($items as $item) {
        if ($item['branch'] === 'research') {
            $researchInDomain++;
        } else {
            $technologyInDomain++;
        }
    }
    if (count($items) !== 4) {
        fail("Domain '$domain' should have 4 programs, got " . count($items));
    }
    if ($researchInDomain !== 2 || $technologyInDomain !== 2) {
        fail("Domain '$domain' should have 2 research + 2 technology programs, got $researchInDomain + $technologyInDomain");
    }
}

$qField = null;
foreach ($catalog as $def) {
    if ($def['key'] === 'quantum_field') {
        $qField = $def;
    }
}
if (!$qField) {
    fail("quantum_field missing");
}

$c0 = ogameTechNextCosts($qField, 0);
$c1 = ogameTechNextCosts($qField, 1);
$c10 = ogameTechNextCosts($qField, 10);
if ($c1['nq'] <= $c0['nq']) {
    fail("Cost must escalate with level");
}
if ($c10['nq'] <= $c1['nq']) {
    fail("Cost must keep escalating with level");
}
if ($c0['turns'] <= 0) {
    fail("Turns must be positive");
}

$cDisc = ogameTechNextCosts($qField, 2, 50.0);
if ($cDisc['nq'] !== (int)round(ogameTechNextCosts($qField, 2)['nq'] * 0.5)) {
    fail("50% discount did not halve the cost");
}
$cClampHi = ogameTechNextCosts($qField, 2, 200.0);
$cClampLo = ogameTechNextCosts($qField, 2, -5.0);
if ($cClampHi['nq'] !== 0) {
    fail("Discount above 100 must clamp to 100% (zero cost)");
}
if ($cClampLo['nq'] !== ogameTechNextCosts($qField, 2, 0)['nq']) {
    fail("Negative discount must clamp to 0%");
}

$locked = null;
$unlocked = null;
foreach ($catalog as $def) {
    if ($def['key'] === 'quantum_entanglement') {
        $locked = $def;
    }
    if ($def['key'] === 'void_stabilization') {
        $unlocked = $def;
    }
}
if (ogameTechPrereqMet([], $unlocked) !== true) {
    fail("Program with no prereqs must always be met");
}
if (ogameTechPrereqMet([], $locked) !== false) {
    fail("Program with unmet prereqs must report not met");
}
if (ogameTechPrereqMet(['quantum_field' => 8], $locked) !== true) {
    fail("Program with satisfied prereq must report met");
}
if (ogameTechPrereqMet(['quantum_field' => 7], $locked) !== false) {
    fail("Program with level 7 prereq must still be locked at level 8 requirement");
}
if (strpos(ogameTechPrereqText([], $locked), 'Quantum Field Theory L8') === false) {
    fail("Prereq text must include the required name and level");
}
if (ogameTechPrereqText([], $unlocked) !== 'None') {
    fail("No-prereq program must report 'None'");
}

$levels = ['quantum_field' => 8, 'void_stabilization' => 3];
$branches = ogameTreeBranches($catalog, 'research', $levels, 0.0);
$domainCount = count($branches);
if ($domainCount !== 10) {
    fail("Research tree should have 10 domain branches, got $domainCount");
}
$branchKeys = [];
foreach ($branches as $g) {
    foreach ($g['nodes'] as $node) {
        $branchKeys[$node['key']] = true;
        if ($node['tier'] < 1) {
            fail("Node " . $node['key'] . " missing tier");
        }
        if ($node['level'] !== (int)($levels[$node['key']] ?? 0)) {
            fail("Node level inconsistency for " . $node['key']);
        }
    }
}
foreach ($catalog as $def) {
    if ($def['branch'] === 'research' && !isset($branchKeys[$def['key']])) {
        fail("Research program " . $def['key'] . " missing from tree");
    }
}

echo "ogame research logic checks passed\n";
