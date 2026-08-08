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

// Artillery taxonomy library (pure functions, no database or session access).

function art_safeToken(string $v): string
{
    return substr(preg_replace('/[^A-Za-z0-9 _\-:.]/', '', trim($v)) ?? '', 0, 160);
}

function art_types(): array
{
    return [
        'Kinetic' => ['Slug', 'Sabot'],
        'Energy'  => ['Beam', 'Pulse'],
        'Plasma'  => ['Bolt', 'Torrent'],
        'Missile' => ['Warhead', 'Cluster'],
        'EMP'     => ['Surge', 'Jammer'],
    ];
}

function art_offenseClasses(): array
{
    return [
        'Strike'     => ['Light Strike', 'Heavy Strike'],
        'Siege'      => ['Field Siege', 'Long Siege'],
        'Rail'       => ['Coil Rail', 'Mass Rail'],
        'Assault'    => ['Direct Assault', 'Shock Assault'],
        'Orbital'    => ['Low-Orbit', 'High-Orbit'],
        'Bombard'    => ['Drop Bombard', 'Plasma Bombard'],
        'Torpedo'    => ['Fleet Torpedo', 'Fortress Torpedo'],
        'Artillery'  => ['Field Artillery', 'Rocket Artillery'],
        'Lance'      => ['Ion Lance', 'Void Lance'],
    ];
}

function art_defenseClasses(): array
{
    return [
        'Flak'           => ['Light Flak', 'Heavy Flak'],
        'Laser'          => ['Point Laser', 'Array Laser'],
        'Plasma'         => ['Turret Plasma', 'Tower Plasma'],
        'Kinetic'        => ['Slug Turret', 'Gauss Turret'],
        'Shield'         => ['Bubble Shield', 'Hardened Shield'],
        'Mine'           => ['Magnetic Mine', 'Nova Mine'],
        'Interceptor'    => ['Missile Interceptor', 'Beam Interceptor'],
        'Point-Defense'  => ['CIWS Battery', 'ABM Rack'],
        'Fortress'       => ['Bunker Array', 'Citadel Core'],
    ];
}

function art_buildAttributes(string $major, int $tier, int $ci): array
{
    $attrs = [
        ['name' => 'anti_fleet', 'value' => (int)(30 + $tier * 6 + $ci * 2), 'sub' => 'armor_shear', 'sub_value' => (int)(8 + $tier * 2)],
        ['name' => 'anti_structure', 'value' => (int)(20 + $tier * 5 + $ci), 'sub' => 'wall_breach', 'sub_value' => (int)(6 + $tier * 2)],
        ['name' => 'anti_orbital', 'value' => ($major === 'defense') ? (int)(28 + $tier * 6 + $ci * 2) : (int)(12 + $tier * 3)],
        ['name' => 'energy_drain', 'value' => (int)(6 + $tier * 2 + ($ci % 3))],
        ['name' => 'stealth', 'value' => (int)(5 + (($ci + $tier) % 5))],
        ['name' => 'shield_pierce', 'value' => (int)(12 + $tier * 3 + ($ci % 2) * 4), 'sub' => 'harmonic_break', 'sub_value' => (int)(4 + $tier)],
        ['name' => 'splash', 'value' => (int)(8 + (($ci + $tier) % 6))],
        ['name' => 'tracking', 'value' => (int)(40 + $tier * 4 + ($ci % 3) * 3), 'sub' => 'lead_compute', 'sub_value' => (int)(10 + $tier * 2)],
        ['name' => 'self_repair', 'value' => (int)(3 + (($ci * 2 + $tier) % 4))],
        ['name' => 'overcharge', 'value' => (int)(15 + $tier * 5), 'sub' => 'thermal_limit', 'sub_value' => (int)(5 + $tier)],
    ];
    if ($major === 'offense') {
        $attrs[] = ['name' => 'breach_sequence', 'value' => (int)(10 + $tier * 3), 'sub' => 'code_density', 'sub_value' => (int)(3 + ($ci % 3))];
    }
    return $attrs;
}

function art_buildCatalog(): array
{
    $catalog = [];
    $id = 1;
    $typeKeys = array_keys(art_types());
    $typeSubs = art_types();
    foreach (['offense' => art_offenseClasses(), 'defense' => art_defenseClasses()] as $major => $classes) {
        $ci = 0;
        foreach ($classes as $class => $subs) {
            $si = 0;
            foreach ($subs as $sub) {
                foreach ($typeKeys as $ti => $type) {
                    $subtype = $typeSubs[$type][($ci + $si + $ti) % 2];
                    $tier = $ci + 1 + $si;
                    $power = (int)(45 + ($tier * 42) + ($ci * 7));
                    $code = strtoupper(substr($major, 0, 3)) . '-' . str_pad((string)$id, 3, '0', STR_PAD_LEFT);
                    $name = $sub . ' ' . $type;
                    $catalog[] = [
                        'artillery_id' => $id,
                        'artillery_code' => $code,
                        'artillery_name' => $name,
                        'artillery_title' => $type . ' ' . $sub . ' (' . $major . ')',
                        'major_class' => $major,
                        'class_name' => $class,
                        'subclass_name' => $sub,
                        'type_name' => $type,
                        'subtype_name' => $subtype,
                        'tier' => $tier,
                        'power_rating' => $power,
                        'attack_stat' => (int)round($power * (($major === 'offense') ? 1.30 : 0.55)),
                        'attack_sub' => (int)round($power * (($major === 'offense') ? 0.42 : 0.18)),
                        'defense_stat' => (int)round($power * (($major === 'defense') ? 1.35 : 0.45)),
                        'defense_sub' => (int)round($power * (($major === 'defense') ? 0.48 : 0.16)),
                        'shield_stat' => (int)round(120 + ($tier * 110) + ($ci * 14)),
                        'shield_sub' => (int)round(30 + ($tier * 28) + ($ci * 4)),
                        'accuracy_stat' => (int)(62 + ($tier * 5) + (($ci + $si) % 3) * 3),
                        'accuracy_sub' => (int)(38 + ($tier * 4) + (($ci + $si) % 2) * 2),
                        'range_stat' => (int)(900 + ($tier * 260) + ($ci * 40)),
                        'range_sub' => (int)(40 + ($tier * 6) + ($ci * 1)),
                        'reload_stat' => (int)(9 + (($ci + $si) % 4)),
                        'reload_sub' => (int)(3 + (($ci + $si) % 3)),
                        'mobility_stat' => (int)(30 + ($tier * 4) + ($ci * 2)),
                        'mobility_sub' => (int)(10 + ($tier * 2)),
                        'naq_cost' => (int)(26000 + ($tier * 34000) + ($ci * 4200) + ($si * 1800)),
                        'unit_cost' => (int)(12 + ($tier * 9) + ($ci * 2)),
                        'metal_cost' => (int)(700 + ($tier * 900) + ($ci * 120)),
                        'crystal_cost' => (int)(500 + ($tier * 680) + ($ci * 90)),
                        'deut_cost' => (int)(180 + ($tier * 300) + ($ci * 40)),
                        'food_cost' => (int)(90 + ($tier * 60)),
                        'water_cost' => (int)(70 + ($tier * 50)),
                        'pop_cost' => (int)(4 + ($tier * 3)),
                        'attack_convert' => ($major === 'offense') ? (int)(8 + ($tier * 5) + $ci) : 0,
                        'defense_convert' => ($major === 'defense') ? (int)(8 + ($tier * 5) + $ci) : 0,
                        'build_time' => (int)(40 + ($tier * 22) + ($ci * 3)),
                        'attributes' => json_encode(art_buildAttributes($major, $tier, $ci)),
                        'legacy_key' => '',
                    ];
                    $id++;
                }
                $si++;
            }
            $ci++;
        }
    }
    return $catalog;
}
