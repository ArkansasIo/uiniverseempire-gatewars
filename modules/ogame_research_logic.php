<?php
// Base::ogame_research_logic.php
// Pure OGame-style research/tech logic. No session or DB access here so the
// catalog and cost/prerequisite rules are directly unit-testable.
require_once __DIR__ . '/formal_logic.php';

function ogameTechCatalog(): array {
    $costBase = [
        1 => ['nq' => 45000, 'metal' => 12000, 'crystal' => 8000, 'deut' => 4000, 'energy' => 2500],
        2 => ['nq' => 90000, 'metal' => 22000, 'crystal' => 16000, 'deut' => 7500, 'energy' => 4000],
        3 => ['nq' => 160000, 'metal' => 38000, 'crystal' => 28000, 'deut' => 13000, 'energy' => 6000],
        4 => ['nq' => 280000, 'metal' => 65000, 'crystal' => 48000, 'deut' => 22000, 'energy' => 9000],
        5 => ['nq' => 460000, 'metal' => 105000, 'crystal' => 78000, 'deut' => 35000, 'energy' => 13000],
        6 => ['nq' => 720000, 'metal' => 165000, 'crystal' => 120000, 'deut' => 55000, 'energy' => 18000],
    ];
    $scaleBase = [1 => 1.55, 2 => 1.58, 3 => 1.60, 4 => 1.63, 5 => 1.66, 6 => 1.70];
    $turnBase = [1 => 30, 2 => 55, 3 => 95, 4 => 160, 5 => 260, 6 => 400];

    $defs = [
        ['key' => 'quantum_field', 'name' => 'Quantum Field Theory', 'branch' => 'research', 'domain' => 'Quantum', 'tier' => 1, 'focus' => 'Warfare', 'description' => 'Lattice coherence studies that sharpen offensive mathematics.', 'effect' => 'Attack power +1.5% per level.', 'prereq' => []],
        ['key' => 'quantum_entanglement', 'name' => 'Entanglement Relay', 'branch' => 'research', 'domain' => 'Quantum', 'tier' => 4, 'focus' => 'Espionage', 'description' => 'Paired-state relays for faster intel decoding across the network.', 'effect' => 'Espionage power +2.5% per level.', 'prereq' => [['key' => 'quantum_field', 'level' => 8, 'name' => 'Quantum Field Theory']]],
        ['key' => 'void_stabilization', 'name' => 'Void Stabilization', 'branch' => 'research', 'domain' => 'Void', 'tier' => 1, 'focus' => 'Logistics', 'description' => 'Stabilize the void between gate points for safer fleet transit.', 'effect' => 'Fleet logistics efficiency +1.2% per level.', 'prereq' => []],
        ['key' => 'void_harvest', 'name' => 'Void Harvest Theory', 'branch' => 'research', 'domain' => 'Void', 'tier' => 4, 'focus' => 'Economy', 'description' => 'Extract theoretical energy from the void itself.', 'effect' => 'Income +1.8% per level.', 'prereq' => [['key' => 'void_stabilization', 'level' => 8, 'name' => 'Void Stabilization']]],
        ['key' => 'psionic_link', 'name' => 'Psionic Link', 'branch' => 'research', 'domain' => 'Psionic', 'tier' => 1, 'focus' => 'Espionage', 'description' => 'Mental-channel integration for covert networks.', 'effect' => 'Covert power +1.8% per level.', 'prereq' => []],
        ['key' => 'psionic_ascension', 'name' => 'Psionic Ascension', 'branch' => 'research', 'domain' => 'Psionic', 'tier' => 4, 'focus' => 'Warfare', 'description' => 'Peak mental discipline that sharpens command reflexes.', 'effect' => 'Attack +2.2% and defense +1.2% per level.', 'prereq' => [['key' => 'psionic_link', 'level' => 8, 'name' => 'Psionic Link']]],
        ['key' => 'nano_fabrication', 'name' => 'Nano Fabrication', 'branch' => 'research', 'domain' => 'Nano', 'tier' => 1, 'focus' => 'Economy', 'description' => 'Molecular-scale manufacturing blueprints for every supply line.', 'effect' => 'Unit production +1.4% per level.', 'prereq' => []],
        ['key' => 'nano_repair_matrix', 'name' => 'Repair Matrix', 'branch' => 'research', 'domain' => 'Nano', 'tier' => 4, 'focus' => 'Defense', 'description' => 'Self-healing hull lattices for defensive structures.', 'effect' => 'Defense power +2.0% per level.', 'prereq' => [['key' => 'nano_fabrication', 'level' => 8, 'name' => 'Nano Fabrication']]],
        ['key' => 'graviton_anchor', 'name' => 'Graviton Anchor', 'branch' => 'research', 'domain' => 'Graviton', 'tier' => 2, 'focus' => 'Expansion', 'description' => 'Pin a stable gravity well to anchor orbital expansion.', 'effect' => 'Planet income bonus +1.0% per level.', 'prereq' => []],
        ['key' => 'graviton_lance', 'name' => 'Graviton Lance', 'branch' => 'research', 'domain' => 'Graviton', 'tier' => 5, 'focus' => 'Warfare', 'description' => 'Focused gravity waves deployed as a primary weapon.', 'effect' => 'Attack power +3.0% per level.', 'prereq' => [['key' => 'graviton_anchor', 'level' => 7, 'name' => 'Graviton Anchor'], ['key' => 'quantum_field', 'level' => 5, 'name' => 'Quantum Field Theory']]],
        ['key' => 'xeno_linguistics', 'name' => 'Xeno Linguistics', 'branch' => 'research', 'domain' => 'Xeno', 'tier' => 2, 'focus' => 'Espionage', 'description' => 'Decode alien scripts and intercepted trade signals.', 'effect' => 'Espionage power +1.6% per level.', 'prereq' => []],
        ['key' => 'xeno_biochemistry', 'name' => 'Xeno Biochemistry', 'branch' => 'research', 'domain' => 'Xeno', 'tier' => 5, 'focus' => 'Economy', 'description' => 'Alien enzymes for high-yield biosynthesis chains.', 'effect' => 'Food production +2.4% per level.', 'prereq' => [['key' => 'xeno_linguistics', 'level' => 7, 'name' => 'Xeno Linguistics']]],
        ['key' => 'bioforge_cell', 'name' => 'Bioforge Cell', 'branch' => 'research', 'domain' => 'Bioforge', 'tier' => 2, 'focus' => 'Economy', 'description' => 'Cultivate engineered cells to accelerate resource growth.', 'effect' => 'Population growth +1.6% per level.', 'prereq' => []],
        ['key' => 'bioforge_regeneration', 'name' => 'Bioforge Regeneration', 'branch' => 'research', 'domain' => 'Bioforge', 'tier' => 5, 'focus' => 'Defense', 'description' => 'Living armor that regenerates between engagements.', 'effect' => 'Defense power +2.8% per level.', 'prereq' => [['key' => 'bioforge_cell', 'level' => 7, 'name' => 'Bioforge Cell']]],
        ['key' => 'temporal_math', 'name' => 'Temporal Math', 'branch' => 'research', 'domain' => 'Temporal', 'tier' => 2, 'focus' => 'Logistics', 'description' => 'Chronometric models that accelerate work cycles.', 'effect' => 'Research time -2.0% per level.', 'prereq' => []],
        ['key' => 'temporal_accelerator', 'name' => 'Temporal Accelerator', 'branch' => 'research', 'domain' => 'Temporal', 'tier' => 5, 'focus' => 'Logistics', 'description' => 'Compress local time to finish deep research projects.', 'effect' => 'Production efficiency +2.6% per level.', 'prereq' => [['key' => 'temporal_math', 'level' => 7, 'name' => 'Temporal Math']]],
        ['key' => 'stellar_mapping', 'name' => 'Stellar Mapping', 'branch' => 'research', 'domain' => 'Stellar', 'tier' => 3, 'focus' => 'Expansion', 'description' => 'Chart stellar nurseries for safe colonization routes.', 'effect' => 'Colonization success +1.8% per level.', 'prereq' => []],
        ['key' => 'stellar_forge', 'name' => 'Stellar Forge', 'branch' => 'research', 'domain' => 'Stellar', 'tier' => 6, 'focus' => 'Economy', 'description' => 'Ignite a controlled star-forge for mass resource output.', 'effect' => 'Metal production +3.2% per level.', 'prereq' => [['key' => 'stellar_mapping', 'level' => 6, 'name' => 'Stellar Mapping'], ['key' => 'void_harvest', 'level' => 4, 'name' => 'Void Harvest Theory']]],
        ['key' => 'aegis_shield', 'name' => 'Aegis Shield', 'branch' => 'research', 'domain' => 'Aegis', 'tier' => 3, 'focus' => 'Defense', 'description' => 'Planar shield technology for base protection.', 'effect' => 'Shield strength +2.0% per level.', 'prereq' => []],
        ['key' => 'aegis_barrier', 'name' => 'Aegis Barrier', 'branch' => 'research', 'domain' => 'Aegis', 'tier' => 6, 'focus' => 'Defense', 'description' => 'Project a planet-wide Aegis barrier.', 'effect' => 'Defense power +3.4% per level.', 'prereq' => [['key' => 'aegis_shield', 'level' => 6, 'name' => 'Aegis Shield'], ['key' => 'nano_repair_matrix', 'level' => 4, 'name' => 'Repair Matrix']]],
        ['key' => 'tech_quantum_compute', 'name' => 'Quantum Computing', 'branch' => 'technology', 'domain' => 'Quantum', 'tier' => 1, 'focus' => 'Logistics', 'description' => 'Quantum cores for command and analysis infrastructure.', 'effect' => 'Research speed +1.5% per level.', 'prereq' => []],
        ['key' => 'tech_quantum_shell', 'name' => 'Quantum Shell', 'branch' => 'technology', 'domain' => 'Quantum', 'tier' => 4, 'focus' => 'Defense', 'description' => 'Shell reactor cores in folded space.', 'effect' => 'Energy capacity +2.0% per level.', 'prereq' => [['key' => 'tech_quantum_compute', 'level' => 8, 'name' => 'Quantum Computing']]],
        ['key' => 'tech_void_drive', 'name' => 'Void Drive', 'branch' => 'technology', 'domain' => 'Void', 'tier' => 1, 'focus' => 'Expansion', 'description' => 'Drives that skim the void for extra thrust.', 'effect' => 'Fleet speed +2.0% per level.', 'prereq' => []],
        ['key' => 'tech_void_bomb', 'name' => 'Void Bomb', 'branch' => 'technology', 'domain' => 'Void', 'tier' => 4, 'focus' => 'Warfare', 'description' => 'Implosion warheads that collapse defensive fields.', 'effect' => 'Attack power +2.6% per level.', 'prereq' => [['key' => 'tech_void_drive', 'level' => 8, 'name' => 'Void Drive']]],
        ['key' => 'tech_psionic_interdictor', 'name' => 'Psionic Interdictor', 'branch' => 'technology', 'domain' => 'Psionic', 'tier' => 1, 'focus' => 'Espionage', 'description' => 'Disrupt enemy mental links and covert coordination.', 'effect' => 'Anti-covert power +1.8% per level.', 'prereq' => []],
        ['key' => 'tech_psionic_titan', 'name' => 'Psionic Titan', 'branch' => 'technology', 'domain' => 'Psionic', 'tier' => 4, 'focus' => 'Warfare', 'description' => 'Psi-amplified heavy combat frame.', 'effect' => 'Attack +2.4% and defense +1.2% per level.', 'prereq' => [['key' => 'tech_psionic_interdictor', 'level' => 8, 'name' => 'Psionic Interdictor']]],
        ['key' => 'tech_nano_alloy', 'name' => 'Nano Alloy', 'branch' => 'technology', 'domain' => 'Nano', 'tier' => 1, 'focus' => 'Defense', 'description' => 'Reinforced alloys for hulls, armor, and towers.', 'effect' => 'Armor strength +1.6% per level.', 'prereq' => []],
        ['key' => 'tech_nano_swarm', 'name' => 'Nano Swarm', 'branch' => 'technology', 'domain' => 'Nano', 'tier' => 4, 'focus' => 'Logistics', 'description' => 'Swarm fabricators that print small craft at scale.', 'effect' => 'Unit production +2.2% per level.', 'prereq' => [['key' => 'tech_nano_alloy', 'level' => 8, 'name' => 'Nano Alloy']]],
        ['key' => 'tech_graviton_lens', 'name' => 'Graviton Lens', 'branch' => 'technology', 'domain' => 'Graviton', 'tier' => 2, 'focus' => 'Logistics', 'description' => 'Gravity lenses for long-range sensors and power routing.', 'effect' => 'Sensor range +1.4% per level.', 'prereq' => []],
        ['key' => 'tech_graviton_turret', 'name' => 'Graviton Turret', 'branch' => 'technology', 'domain' => 'Graviton', 'tier' => 5, 'focus' => 'Defense', 'description' => 'Heavy turrets that pinch space around targets.', 'effect' => 'Defense power +3.0% per level.', 'prereq' => [['key' => 'tech_graviton_lens', 'level' => 7, 'name' => 'Graviton Lens'], ['key' => 'tech_void_bomb', 'level' => 4, 'name' => 'Void Bomb']]],
        ['key' => 'tech_xeno_sensors', 'name' => 'Xeno Sensors', 'branch' => 'technology', 'domain' => 'Xeno', 'tier' => 2, 'focus' => 'Espionage', 'description' => 'Alien-adapted sensor arrays for threat isolation.', 'effect' => 'Espionage power +1.6% per level.', 'prereq' => []],
        ['key' => 'tech_xeno_cloak', 'name' => 'Xeno Cloak', 'branch' => 'technology', 'domain' => 'Xeno', 'tier' => 5, 'focus' => 'Espionage', 'description' => 'Phase-cloaking for covert hulls and recon frames.', 'effect' => 'Covert power +2.8% per level.', 'prereq' => [['key' => 'tech_xeno_sensors', 'level' => 7, 'name' => 'Xeno Sensors']]],
        ['key' => 'tech_bioforge_plating', 'name' => 'Bioforge Plating', 'branch' => 'technology', 'domain' => 'Bioforge', 'tier' => 2, 'focus' => 'Defense', 'description' => 'Living plating that hardens on impact.', 'effect' => 'Armor strength +2.0% per level.', 'prereq' => []],
        ['key' => 'tech_bioforge_wraith', 'name' => 'Bioforge Wraith', 'branch' => 'technology', 'domain' => 'Bioforge', 'tier' => 5, 'focus' => 'Warfare', 'description' => 'Regenerative hunter-killer frame.', 'effect' => 'Attack power +2.8% per level.', 'prereq' => [['key' => 'tech_bioforge_plating', 'level' => 7, 'name' => 'Bioforge Plating']]],
        ['key' => 'tech_temporal_grid', 'name' => 'Temporal Grid', 'branch' => 'technology', 'domain' => 'Temporal', 'tier' => 2, 'focus' => 'Logistics', 'description' => 'Grid-synced timing for production lines.', 'effect' => 'Production speed +1.6% per level.', 'prereq' => []],
        ['key' => 'tech_temporal_warp', 'name' => 'Temporal Warp', 'branch' => 'technology', 'domain' => 'Temporal', 'tier' => 5, 'focus' => 'Logistics', 'description' => 'Warp the local frame for rapid fleet transit.', 'effect' => 'Fleet speed +3.0% per level.', 'prereq' => [['key' => 'tech_temporal_grid', 'level' => 7, 'name' => 'Temporal Grid']]],
        ['key' => 'tech_stellar_reactor', 'name' => 'Stellar Reactor', 'branch' => 'technology', 'domain' => 'Stellar', 'tier' => 3, 'focus' => 'Economy', 'description' => 'Harness a miniature star for grid power.', 'effect' => 'Energy output +2.4% per level.', 'prereq' => []],
        ['key' => 'tech_stellar_dreadnought', 'name' => 'Stellar Dreadnought', 'branch' => 'technology', 'domain' => 'Stellar', 'tier' => 6, 'focus' => 'Warfare', 'description' => 'Flagship hull built around a stellar core.', 'effect' => 'Attack +3.2% and defense +1.6% per level.', 'prereq' => [['key' => 'tech_stellar_reactor', 'level' => 6, 'name' => 'Stellar Reactor'], ['key' => 'tech_graviton_turret', 'level' => 4, 'name' => 'Graviton Turret']]],
        ['key' => 'tech_aegis_emitter', 'name' => 'Aegis Emitter', 'branch' => 'technology', 'domain' => 'Aegis', 'tier' => 3, 'focus' => 'Defense', 'description' => 'Emitters that project hard-light barriers.', 'effect' => 'Shield strength +2.2% per level.', 'prereq' => []],
        ['key' => 'tech_aegis_dome', 'name' => 'Aegis Dome', 'branch' => 'technology', 'domain' => 'Aegis', 'tier' => 6, 'focus' => 'Defense', 'description' => 'Bubble an entire world in an Aegis field.', 'effect' => 'Defense power +3.6% per level.', 'prereq' => [['key' => 'tech_aegis_emitter', 'level' => 6, 'name' => 'Aegis Emitter'], ['key' => 'tech_nano_swarm', 'level' => 4, 'name' => 'Nano Swarm']]],
    ];

    $catalog = [];
    foreach ($defs as $def) {
        $tier = (int)$def['tier'];
        $def['base'] = $costBase[$tier];
        $def['scale'] = $scaleBase[$tier];
        $def['base_turns'] = $turnBase[$tier];
        $def['max_level'] = 25;
        $catalog[] = $def;
    }
    return $catalog;
}

function ogameTechNextCosts(array $def, int $level, float $discountPct = 0): array {
    $level = max(0, (int)$level);
    $discountPct = max(0, min(100, (float)$discountPct));
    $factor = 1 - ($discountPct / 100);
    $costs = [
        'nq' => (int)round(formalCostValue((int)$def['base']['nq'], $level, (float)$def['scale'], 0.12) * $factor),
        'metal' => (int)round(formalCostValue((int)$def['base']['metal'], $level, (float)$def['scale'], 0.12) * $factor),
        'crystal' => (int)round(formalCostValue((int)$def['base']['crystal'], $level, (float)$def['scale'], 0.12) * $factor),
        'deut' => (int)round(formalCostValue((int)$def['base']['deut'], $level, (float)$def['scale'], 0.12) * $factor),
        'energy' => (int)round(formalCostValue((int)$def['base']['energy'], $level, (float)$def['scale'], 0.12) * $factor),
    ];
    $costs['turns'] = formalTimeValue((int)$def['base_turns'], $level, 1.08);
    return $costs;
}

function ogameTechPrereqMet(array $levels, array $def): bool {
    foreach (($def['prereq'] ?? []) as $need) {
        if ((int)($levels[$need['key']] ?? 0) < (int)$need['level']) {
            return false;
        }
    }
    return true;
}

function ogameTechPrereqText(array $levels, array $def): string {
    $parts = [];
    foreach (($def['prereq'] ?? []) as $need) {
        $cur = (int)($levels[$need['key']] ?? 0);
        $parts[] = $need['name'] . ' L' . (int)$need['level'] . ' (have L' . $cur . ')';
    }
    return $parts ? implode(', ', $parts) : 'None';
}

function ogameTreeBranches(array $catalog, string $branch, array $levels, float $discountPct): array {
    $grouped = [];
    foreach ($catalog as $def) {
        if (($def['branch'] ?? '') !== $branch) {
            continue;
        }
        $domain = $def['domain'];
        if (!isset($grouped[$domain])) {
            $grouped[$domain] = ['domain' => $domain, 'nodes' => []];
        }
        $key = $def['key'];
        $level = (int)($levels[$key] ?? 0);
        $grouped[$domain]['nodes'][] = [
            'key' => $key,
            'name' => $def['name'],
            'tier' => (int)$def['tier'],
            'focus' => $def['focus'],
            'effect' => $def['effect'],
            'description' => $def['description'],
            'level' => $level,
            'max_level' => (int)$def['max_level'],
            'cost' => ogameTechNextCosts($def, $level, $discountPct),
            'prereqMet' => ogameTechPrereqMet($levels, $def),
            'prereqText' => ogameTechPrereqText($levels, $def),
        ];
    }
    foreach ($grouped as &$g) {
        usort($g['nodes'], function ($a, $b) {
            return $a['tier'] <=> $b['tier'];
        });
    }
    unset($g);
    return array_values($grouped);
}
