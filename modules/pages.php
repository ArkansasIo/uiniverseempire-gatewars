<?php
/*
 * MIT License
 *
 * Copyright (c) 2026 Stephen, Universe Civilization : Empire at wars
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
include_once("../config.php");
include_once(__DIR__ . '/entity_name_helpers.php');
include_once(__DIR__ . '/formal_logic.php');
include_once(__DIR__ . '/ogame_research_logic.php');

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn || !isset($_GET['time'])) {
    header("Location: ../index.php");
    exit;
}

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function fnum($value): string {
    return number_format((float)$value);
}

function universeRand(int &$seed, int $min, int $max): int {
    $seed = (int)(($seed * 1103515245 + 12345) & 0x7fffffff);
    $span = ($max - $min) + 1;
    return $min + ($seed % $span);
}

function universePick(int &$seed, array $list): string {
    if (count($list) === 0) {
        return '';
    }
    $idx = universeRand($seed, 0, count($list) - 1);
    return (string)$list[$idx];
}

function universeTaxonomy(): array {
    $planetBiomeCatalog = [
        'Terran' => [
            ['name' => 'Verdant Spine', 'description' => 'A dense temperate belt rich in flora, water, and long-term colonist comfort.', 'resourceBias' => 'food', 'resourceValue' => 12, 'hazard' => 'humid rot', 'habitatBand' => 'high', 'suitability' => 'agriculture', 'strategy' => 'Great for food production and stable habitation.'],
            ['name' => 'Cinder Orchard', 'description' => 'Volcanic ash soils produce resilient crop lattices and fast mineral recovery.', 'resourceBias' => 'metal', 'resourceValue' => 8, 'hazard' => 'ash storms', 'habitatBand' => 'moderate', 'suitability' => 'industry', 'strategy' => 'Useful for rapid industrial bootstraps.'],
            ['name' => 'Silver Fen', 'description' => 'Shallow marshland and peat flats hold abundant water and medicinal algae.', 'resourceBias' => 'water', 'resourceValue' => 14, 'hazard' => 'mire blight', 'habitatBand' => 'high', 'suitability' => 'water', 'strategy' => 'Ideal for water-intensive habitats and research.'],
            ['name' => 'Ironroot Meadow', 'description' => 'Hardy root systems anchor deep mineral veins beneath rolling plains.', 'resourceBias' => 'metal', 'resourceValue' => 10, 'hazard' => 'thistle storms', 'habitatBand' => 'moderate', 'suitability' => 'mining', 'strategy' => 'Excellent for mining hubs and defensive garrisons.'],
            ['name' => 'Aurora Reedbeds', 'description' => 'Wide reed marshes glow under polar light and are excellent for bioengineering.', 'resourceBias' => 'crystal', 'resourceValue' => 7, 'hazard' => 'electro fog', 'habitatBand' => 'high', 'suitability' => 'research', 'strategy' => 'Strong for laboratories and high-output synthesis.'],
        ],
        'Oceanic' => [
            ['name' => 'Tidal Veldt', 'description' => 'Saltgrass seas form broad shallows that are ideal for aquaculture and low-pressure living.', 'resourceBias' => 'food', 'resourceValue' => 11, 'hazard' => 'tidal surges', 'habitatBand' => 'high', 'suitability' => 'agriculture', 'strategy' => 'Perfect for sustained food and population growth.'],
            ['name' => 'Coral Canopy', 'description' => 'A reef-grown skyline produces dense mineral and biotech yields beneath the waves.', 'resourceBias' => 'crystal', 'resourceValue' => 9, 'hazard' => 'reef currents', 'habitatBand' => 'moderate', 'suitability' => 'biotech', 'strategy' => 'Good for advanced eco-industrial districts.'],
            ['name' => 'Stormglass Shelf', 'description' => 'Glassy shallows with constant wave agitation expose massive deuterium-rich trenches.', 'resourceBias' => 'deuterium', 'resourceValue' => 13, 'hazard' => 'tempest swells', 'habitatBand' => 'moderate', 'suitability' => 'fuel', 'strategy' => 'Excellent for fuel and fleet logistics.'],
            ['name' => 'Brine Lagoon', 'description' => 'Warm saline pools offer exceptional water recovery and algae bloom potential.', 'resourceBias' => 'water', 'resourceValue' => 12, 'hazard' => 'saline fog', 'habitatBand' => 'high', 'suitability' => 'water', 'strategy' => 'Strong for water- and food-heavy colonies.'],
            ['name' => 'Sunken Archipelago', 'description' => 'A ring of drowned islands hides deep caverns and fresh resource pockets.', 'resourceBias' => 'metal', 'resourceValue' => 8, 'hazard' => 'subsea collapse', 'habitatBand' => 'moderate', 'suitability' => 'expansion', 'strategy' => 'Best for layered infrastructure and frontier cities.'],
        ],
        'Arid' => [
            ['name' => 'Ember Dune', 'description' => 'Long rolling dunes hold heat and mineral dust that serves strong industrial output.', 'resourceBias' => 'metal', 'resourceValue' => 10, 'hazard' => 'sandstorms', 'habitatBand' => 'moderate', 'suitability' => 'industry', 'strategy' => 'Useful for forging and heavy construction.'],
            ['name' => 'Glass Canyon', 'description' => 'Sharp ridgelines refract light into crystal-rich seams and broad vistas.', 'resourceBias' => 'crystal', 'resourceValue' => 10, 'hazard' => 'shard winds', 'habitatBand' => 'low', 'suitability' => 'research', 'strategy' => 'Favors crystal production and observatories.'],
            ['name' => 'Mirrorkeep Basin', 'description' => 'Broad salt basins reflect heat and support resilient solar networks.', 'resourceBias' => 'energy', 'resourceValue' => 9, 'hazard' => 'mirror glare', 'habitatBand' => 'moderate', 'suitability' => 'energy', 'strategy' => 'Good for energy-intensive installations.'],
            ['name' => 'Saffron Steppe', 'description' => 'Wind-scoured plains with hardy flora support both habitation and supply stockpiles.', 'resourceBias' => 'food', 'resourceValue' => 7, 'hazard' => 'dust devils', 'habitatBand' => 'moderate', 'suitability' => 'agriculture', 'strategy' => 'Balanced for mixed economy colonies.'],
            ['name' => 'Dust Choir', 'description' => 'Low hills and resonant plateaus produce strong geology and hidden caverns.', 'resourceBias' => 'deuterium', 'resourceValue' => 8, 'hazard' => 'seismic tremors', 'habitatBand' => 'low', 'suitability' => 'mining', 'strategy' => 'Great for deep tunnel networks and defense work.'],
        ],
        'Volcanic' => [
            ['name' => 'Obsidian Rift', 'description' => 'A fractured abyss of black glass and venting heat with massive industrial value.', 'resourceBias' => 'metal', 'resourceValue' => 11, 'hazard' => 'lava bursts', 'habitatBand' => 'low', 'suitability' => 'industry', 'strategy' => 'Excellent for forge-heavy expansion.'],
            ['name' => 'Sulfur Spine', 'description' => 'Eroded ridges expose sulfur-rich slopes and high-energy geothermal pockets.', 'resourceBias' => 'energy', 'resourceValue' => 10, 'hazard' => 'acid vapors', 'habitatBand' => 'low', 'suitability' => 'energy', 'strategy' => 'Strong for power generation and shield grid support.'],
            ['name' => 'Magma Splay', 'description' => 'Wide lava fields couple strong metal output with harsh but defensible terrain.', 'resourceBias' => 'metal', 'resourceValue' => 9, 'hazard' => 'fire plumes', 'habitatBand' => 'low', 'suitability' => 'defense', 'strategy' => 'Best where fortress economies matter.'],
            ['name' => 'Ember Flats', 'description' => 'Stable ash plains offer good harvest and close-to-surface ore extraction.', 'resourceBias' => 'food', 'resourceValue' => 7, 'hazard' => 'cinder rain', 'habitatBand' => 'moderate', 'suitability' => 'agriculture', 'strategy' => 'Balanced for mixed industry and food.'],
            ['name' => 'Cinder Basin', 'description' => 'A broad lowland ringed by vents and mineral seams, perfect for heavy production.', 'resourceBias' => 'crystal', 'resourceValue' => 8, 'hazard' => 'thermal geysers', 'habitatBand' => 'moderate', 'suitability' => 'production', 'strategy' => 'Valuable for production complexes and shipyards.'],
        ],
        'Ice' => [
            ['name' => 'Frostglass Ridge', 'description' => 'Clear ice ridges refract light into bright corridors and mineral veins.', 'resourceBias' => 'crystal', 'resourceValue' => 9, 'hazard' => 'ice fractures', 'habitatBand' => 'moderate', 'suitability' => 'research', 'strategy' => 'Great for crystal and science infrastructure.'],
            ['name' => 'Rime Delta', 'description' => 'Slow-moving icewater flats offer abundant water and a cold-adapted biosphere.', 'resourceBias' => 'water', 'resourceValue' => 12, 'hazard' => 'whiteouts', 'habitatBand' => 'high', 'suitability' => 'water', 'strategy' => 'Very good for water and life support.'],
            ['name' => 'Blue Abyss', 'description' => 'Deep glacial trenches hide rich deuterium veins and dark, frozen seas.', 'resourceBias' => 'deuterium', 'resourceValue' => 13, 'hazard' => 'crevasse collapse', 'habitatBand' => 'low', 'suitability' => 'fuel', 'strategy' => 'Excellent for fuel and fortified outposts.'],
            ['name' => 'Aurora Shelf', 'description' => 'Polar shelves with shimmering aurora have excellent habitability for adapted populations.', 'resourceBias' => 'food', 'resourceValue' => 8, 'hazard' => 'aurora storms', 'habitatBand' => 'high', 'suitability' => 'habitation', 'strategy' => 'Ideal for large, comfortable colonies.'],
            ['name' => 'Cryo Moor', 'description' => 'Mossy frozen lowlands preserve ancient ice and provide strong biotech output.', 'resourceBias' => 'crystal', 'resourceValue' => 7, 'hazard' => 'ice quakes', 'habitatBand' => 'moderate', 'suitability' => 'biotech', 'strategy' => 'Great for biotech and small-scale industry.'],
        ],
        'Gas Dwarf' => [
            ['name' => 'Cloudfall Belt', 'description' => 'A ring of storm bands and upper cloud forests ideal for atmospheric harvest.', 'resourceBias' => 'energy', 'resourceValue' => 9, 'hazard' => 'lightning reefs', 'habitatBand' => 'moderate', 'suitability' => 'energy', 'strategy' => 'Good for solar and atmospheric conversion.'],
            ['name' => 'Ionic Veil', 'description' => 'A bright plasma halo rich in charged compounds and research value.', 'resourceBias' => 'crystal', 'resourceValue' => 10, 'hazard' => 'static blooms', 'habitatBand' => 'low', 'suitability' => 'research', 'strategy' => 'Very strong for advanced laboratories.'],
            ['name' => 'Helium Shoal', 'description' => 'Drifting gas shelves yield major deuterium and hydrogen output.', 'resourceBias' => 'deuterium', 'resourceValue' => 12, 'hazard' => 'pressure fronts', 'habitatBand' => 'moderate', 'suitability' => 'fuel', 'strategy' => 'Excellent for fleet logistics and fuel economies.'],
            ['name' => 'Stormwake Layer', 'description' => 'A high-altitude weather layer with enormous power conversion potential.', 'resourceBias' => 'energy', 'resourceValue' => 11, 'hazard' => 'thunder bands', 'habitatBand' => 'low', 'suitability' => 'power', 'strategy' => 'Strong for power production and station support.'],
            ['name' => 'Halo Mist', 'description' => 'A luminous haze of frozen vapor and rare compounds makes ultra-light habitats possible.', 'resourceBias' => 'crystal', 'resourceValue' => 8, 'hazard' => 'ion fog', 'habitatBand' => 'moderate', 'suitability' => 'habitation', 'strategy' => 'Useful for prestige colonies and orbital support.'],
        ],
        'Toxic' => [
            ['name' => 'Caustic Mire', 'description' => 'A toxic wetland where black water and fungal growth support chemical industry.', 'resourceBias' => 'food', 'resourceValue' => 6, 'hazard' => 'corrosive mists', 'habitatBand' => 'low', 'suitability' => 'industry', 'strategy' => 'Works best with heavy filtration and industrial planning.'],
            ['name' => 'Violet Fumarole', 'description' => 'Sulfuric vents and mineral lodes create high-value chemical production zones.', 'resourceBias' => 'metal', 'resourceValue' => 9, 'hazard' => 'acid rain', 'habitatBand' => 'low', 'suitability' => 'chemistry', 'strategy' => 'Excellent for grimy industrial ecology.'],
            ['name' => 'Nox Bloom', 'description' => 'Bioluminescent flora produces rare compounds and strong biotech research potential.', 'resourceBias' => 'crystal', 'resourceValue' => 8, 'hazard' => 'spore clouds', 'habitatBand' => 'low', 'suitability' => 'biotech', 'strategy' => 'Good for biotech and pharma chains.'],
            ['name' => 'Acid Fjord', 'description' => 'Deep chemical ravines hide rich deuterium and rare mineral pockets.', 'resourceBias' => 'deuterium', 'resourceValue' => 10, 'hazard' => 'caustic tides', 'habitatBand' => 'low', 'suitability' => 'fuel', 'strategy' => 'Very strong for fuel-heavy operations.'],
            ['name' => 'Toxin Tundra', 'description' => 'An ice-scarred permafrost of toxic salts and dormant microbes.', 'resourceBias' => 'water', 'resourceValue' => 7, 'hazard' => 'radioactive sleet', 'habitatBand' => 'low', 'suitability' => 'defense', 'strategy' => 'Best when you need a harsh but defensible front.'],
        ],
        'Crystalline' => [
            ['name' => 'Prism Valley', 'description' => 'A glittering basin where crystal growth forms broad, resonant fields.', 'resourceBias' => 'crystal', 'resourceValue' => 12, 'hazard' => 'shard blooms', 'habitatBand' => 'moderate', 'suitability' => 'research', 'strategy' => 'Excellent for high-tech and research economies.'],
            ['name' => 'Quartz Crown', 'description' => 'High ridges produce luminous crystal spires and strong energy resonance.', 'resourceBias' => 'energy', 'resourceValue' => 9, 'hazard' => 'resonance pulses', 'habitatBand' => 'moderate', 'suitability' => 'energy', 'strategy' => 'Useful for power and communications.'],
            ['name' => 'Lattice Hollow', 'description' => 'Subsurface crystal networks create a maze of high-value mining chambers.', 'resourceBias' => 'metal', 'resourceValue' => 9, 'hazard' => 'crystal fractures', 'habitatBand' => 'low', 'suitability' => 'mining', 'strategy' => 'Great for mining and industrial cities.'],
            ['name' => 'Diamond Fen', 'description' => 'A bright saline wetland where crystal and water both accumulate.', 'resourceBias' => 'water', 'resourceValue' => 8, 'hazard' => 'lattice fog', 'habitatBand' => 'moderate', 'suitability' => 'water', 'strategy' => 'Balanced for mixed colonies and labs.'],
            ['name' => 'Halo Spire', 'description' => 'Tall crystal spires sustain strong observatories and refined extraction.', 'resourceBias' => 'crystal', 'resourceValue' => 11, 'hazard' => 'prism flares', 'habitatBand' => 'moderate', 'suitability' => 'observatory', 'strategy' => 'Perfect for science-forward settlements.'],
        ],
        'Relic' => [
            ['name' => 'Ruin Garden', 'description' => 'Ancient terraforming fields still produce strange crops and hidden vaults.', 'resourceBias' => 'food', 'resourceValue' => 8, 'hazard' => 'ghost radiation', 'habitatBand' => 'moderate', 'suitability' => 'archaeology', 'strategy' => 'Excellent for mixed settlements and special projects.'],
            ['name' => 'Archive Steppe', 'description' => 'A broad, dust-strewn plain littered with derelict megastructures and data caches.', 'resourceBias' => 'crystal', 'resourceValue' => 9, 'hazard' => 'memory storms', 'habitatBand' => 'moderate', 'suitability' => 'research', 'strategy' => 'Great for relic scavenging and research.'],
            ['name' => 'Null Basin', 'description' => 'A cracked depression where ancient power cells still pulse beneath the surface.', 'resourceBias' => 'energy', 'resourceValue' => 10, 'hazard' => 'null surges', 'habitatBand' => 'low', 'suitability' => 'power', 'strategy' => 'Best for strategic energy and anomaly work.'],
            ['name' => 'Echo Wastes', 'description' => 'Ruined terrain full of acoustic anomalies and buried infrastructure.', 'resourceBias' => 'metal', 'resourceValue' => 8, 'hazard' => 'echo shrapnel', 'habitatBand' => 'low', 'suitability' => 'mining', 'strategy' => 'Strong for salvage and defensive strongholds.'],
            ['name' => 'Monolith Flats', 'description' => 'Raised plateaus around ancient monoliths provide a mystic, defensible frontier.', 'resourceBias' => 'deuterium', 'resourceValue' => 7, 'hazard' => 'resonance flares', 'habitatBand' => 'moderate', 'suitability' => 'defense', 'strategy' => 'Excellent for frontier bastions and anomaly teams.'],
        ],
    ];

    $planetBiomes = [];
    $planetSubBiomes = [];
    $biomeDetails = [];
    foreach ($planetBiomeCatalog as $worldType => $biomeRows) {
        foreach ($biomeRows as $biomeRow) {
            $biomeName = (string)$biomeRow['name'];
            $planetBiomes[$worldType][] = $biomeName;
            $biomeDetails[$biomeName] = [
                'description' => (string)$biomeRow['description'],
                'resourceBias' => (string)$biomeRow['resourceBias'],
                'resourceValue' => (int)$biomeRow['resourceValue'],
                'hazard' => (string)$biomeRow['hazard'],
                'habitatBand' => (string)$biomeRow['habitatBand'],
                'suitability' => (string)$biomeRow['suitability'],
                'strategy' => (string)$biomeRow['strategy'],
            ];
            $planetSubBiomes[$biomeName] = [
                'Canopy Veil',
                'Rootwork March',
                'Sunlit Knoll',
            ];
        }
    }

    $moonBiomeCatalog = [
        'Rocky' => [
            ['name' => 'Cinderglass Basin', 'description' => 'A basalt bowl fused with glassy fallout and strong ore veins.', 'resourceBias' => 'metal', 'resourceValue' => 10, 'hazard' => 'microquakes', 'habitatBand' => 'low', 'suitability' => 'mining', 'strategy' => 'Excellent for mining and shield bunkers.'],
            ['name' => 'Ironbloom Ridge', 'description' => 'High ridges rich in metallic blooms and sheltered crater basins.', 'resourceBias' => 'metal', 'resourceValue' => 9, 'hazard' => 'dust jets', 'habitatBand' => 'low', 'suitability' => 'industry', 'strategy' => 'Great for industrial moon infrastructure.'],
            ['name' => 'Vesper Crater', 'description' => 'A broad lowland crater with deep regolith and strong scan lines.', 'resourceBias' => 'crystal', 'resourceValue' => 8, 'hazard' => 'resonance tremors', 'habitatBand' => 'low', 'suitability' => 'sensor', 'strategy' => 'Excellent for listening posts and sensor arrays.'],
            ['name' => 'Hollow Scar', 'description' => 'A massive impact scar with hidden caverns and explosive salvage potential.', 'resourceBias' => 'deuterium', 'resourceValue' => 8, 'hazard' => 'impact vents', 'habitatBand' => 'low', 'suitability' => 'salvage', 'strategy' => 'Good for salvage and emergency depots.'],
            ['name' => 'Rimefract Shelf', 'description' => 'A cold, fractured shelf of basalt and pale ice near the shadow line.', 'resourceBias' => 'water', 'resourceValue' => 7, 'hazard' => 'shadow frost', 'habitatBand' => 'low', 'suitability' => 'habitation', 'strategy' => 'Useful for small moon colonies.'],
        ],
        'Icy' => [
            ['name' => 'Frostglass Sea', 'description' => 'Frozen seas with shining ice ridges and high deuterium viability.', 'resourceBias' => 'deuterium', 'resourceValue' => 11, 'hazard' => 'ice shear', 'habitatBand' => 'moderate', 'suitability' => 'fuel', 'strategy' => 'Excellent for fuel extraction and station support.'],
            ['name' => 'Blue Ice Caverns', 'description' => 'Subsurface caverns preserve ancient ice and support deep drilling.', 'resourceBias' => 'water', 'resourceValue' => 10, 'hazard' => 'cavern collapse', 'habitatBand' => 'moderate', 'suitability' => 'water', 'strategy' => 'Great for water recovery and cryo facilities.'],
            ['name' => 'Aurora Shelf', 'description' => 'Glowing polar shelves that offer low-pressure habitation and science output.', 'resourceBias' => 'crystal', 'resourceValue' => 8, 'hazard' => 'aurora flares', 'habitatBand' => 'moderate', 'suitability' => 'research', 'strategy' => 'Excellent for science and observation.'],
            ['name' => 'Hollow Basin', 'description' => 'A cold crater basin where pressure pockets preserve trapped gases.', 'resourceBias' => 'energy', 'resourceValue' => 8, 'hazard' => 'gas venting', 'habitatBand' => 'moderate', 'suitability' => 'power', 'strategy' => 'Useful for ion and power systems.'],
            ['name' => 'Cryo Moor', 'description' => 'Low ridges of frozen moss and mineral salts create a resilient frontier.', 'resourceBias' => 'food', 'resourceValue' => 7, 'hazard' => 'cold snaps', 'habitatBand' => 'moderate', 'suitability' => 'habitation', 'strategy' => 'Good for long-term outposts.'],
        ],
        'Metallic' => [
            ['name' => 'Ferrite Dunes', 'description' => 'Dusty metal plains that are rich in ore and shield substrate.', 'resourceBias' => 'metal', 'resourceValue' => 11, 'hazard' => 'magnet storms', 'habitatBand' => 'low', 'suitability' => 'industry', 'strategy' => 'Perfect for orbital foundries.'],
            ['name' => 'Alloy Spine', 'description' => 'Pale ridgelines of composite metal that support heavy fabrication.', 'resourceBias' => 'metal', 'resourceValue' => 10, 'hazard' => 'metal fatigue', 'habitatBand' => 'low', 'suitability' => 'production', 'strategy' => 'Great for shipyard-style moon complexes.'],
            ['name' => 'Magnet Flats', 'description' => 'Broad plains with magnetic anomalies that boost energy conversion.', 'resourceBias' => 'energy', 'resourceValue' => 9, 'hazard' => 'magnetic surges', 'habitatBand' => 'low', 'suitability' => 'power', 'strategy' => 'Strong for energy-intensive facilities.'],
            ['name' => 'Forge Scar', 'description' => 'A deep impact gouge with metal-rich walls and industrial room.', 'resourceBias' => 'crystal', 'resourceValue' => 8, 'hazard' => 'sparks', 'habitatBand' => 'low', 'suitability' => 'mining', 'strategy' => 'Useful for dense industrial planning.'],
            ['name' => 'Iron Veil', 'description' => 'A mantle of polished ore that favors strong fortress design.', 'resourceBias' => 'metal', 'resourceValue' => 9, 'hazard' => 'shrapnel dust', 'habitatBand' => 'low', 'suitability' => 'defense', 'strategy' => 'Excellent for defense and bunkered infrastructure.'],
        ],
        'Ruined' => [
            ['name' => 'Derelict Spires', 'description' => 'Collapsed towers and buried machinery still yield salvage and anomaly data.', 'resourceBias' => 'crystal', 'resourceValue' => 9, 'hazard' => 'ghost debris', 'habitatBand' => 'low', 'suitability' => 'salvage', 'strategy' => 'Great for archaeology and salvage teams.'],
            ['name' => 'Null Dockyards', 'description' => 'Ancient shipyard scars create a rich salvage field and battle history.', 'resourceBias' => 'metal', 'resourceValue' => 8, 'hazard' => 'resonance bursts', 'habitatBand' => 'low', 'suitability' => 'archaeology', 'strategy' => 'Strong for relic and expedition operations.'],
            ['name' => 'Broken Archive', 'description' => 'Data vault ruins contain latent power and hidden manufacturing logic.', 'resourceBias' => 'energy', 'resourceValue' => 8, 'hazard' => 'archive storms', 'habitatBand' => 'low', 'suitability' => 'research', 'strategy' => 'Very good for research and anomaly hunts.'],
            ['name' => 'Silent Vault', 'description' => 'A hallowed ruin of ancient habitats and buried stasis chambers.', 'resourceBias' => 'water', 'resourceValue' => 7, 'hazard' => 'null cold', 'habitatBand' => 'moderate', 'suitability' => 'habitation', 'strategy' => 'Useful for long-term moon outposts.'],
            ['name' => 'Debris Halo', 'description' => 'A broad ring of shredded wreckage and metallic detritus around the moon.', 'resourceBias' => 'metal', 'resourceValue' => 8, 'hazard' => 'debris showers', 'habitatBand' => 'low', 'suitability' => 'defense', 'strategy' => 'Excellent for defense and debris recycling.'],
        ],
    ];

    $moonBiomes = [];
    $moonSubBiomes = [];
    $moonBiomeDetails = [];
    foreach ($moonBiomeCatalog as $moonClass => $biomeRows) {
        foreach ($biomeRows as $biomeRow) {
            $biomeName = (string)$biomeRow['name'];
            $moonBiomes[$moonClass][] = $biomeName;
            $moonBiomeDetails[$biomeName] = [
                'description' => (string)$biomeRow['description'],
                'resourceBias' => (string)$biomeRow['resourceBias'],
                'resourceValue' => (int)$biomeRow['resourceValue'],
                'hazard' => (string)$biomeRow['hazard'],
                'habitatBand' => (string)$biomeRow['habitatBand'],
                'suitability' => (string)$biomeRow['suitability'],
                'strategy' => (string)$biomeRow['strategy'],
            ];
            $moonSubBiomes[$biomeName] = [
                'Shale Veil',
                'Ridge Hollow',
                'Echo Trench',
            ];
        }
    }

    $subBiomeCatalog = [];
    foreach ($planetSubBiomes as $biomeName => $subBiomeChoices) {
        $subBiomeCatalog[$biomeName] = $subBiomeChoices;
    }
    foreach ($moonSubBiomes as $biomeName => $subBiomeChoices) {
        $subBiomeCatalog[$biomeName] = $subBiomeChoices;
    }

    return [
        'worldTypes' => ['Terran', 'Oceanic', 'Arid', 'Volcanic', 'Ice', 'Gas Dwarf', 'Toxic', 'Crystalline', 'Relic'],
        'biomes' => $planetBiomes,
        'subBiomes' => $subBiomeCatalog,
        'biomeDetails' => $biomeDetails,
        'moonBiomes' => $moonBiomes,
        'moonSubBiomes' => $moonSubBiomes,
        'moonBiomeDetails' => $moonBiomeDetails,
    ];
}

function universePlagueCatalog(): array {
    return [
        ['name' => 'Blight Bloom', 'effect_type' => 'habitability', 'effect_value' => -8, 'severity' => 2, 'symptom' => 'Fungal spores choke the air and lower habitability.'],
        ['name' => 'Silt Fever', 'effect_type' => 'resource', 'effect_value' => -18, 'severity' => 3, 'symptom' => 'Toxin runoff corrodes shelters and harvest rigs.'],
        ['name' => 'Solar Rot', 'effect_type' => 'habitability', 'effect_value' => -10, 'severity' => 4, 'symptom' => 'A luminous contagion warps exposed biospheres.'],
        ['name' => 'Void Murrain', 'effect_type' => 'resource', 'effect_value' => -24, 'severity' => 5, 'symptom' => 'A cosmic spore storm drains support infrastructure.'],
    ];
}

function universePlagueProfile(int &$seed, string $targetType, string $biomeName): array {
    $catalog = universePlagueCatalog();
    $entry = $catalog[universeRand($seed, 0, count($catalog) - 1)];
    $severity = universeRand($seed, 1, 5);
    $effectValue = (int)$entry['effect_value'] - ($severity * 2);
    if ($targetType === 'moon') {
        $effectValue -= 3;
    }
    if ($targetType === 'biome') {
        $effectValue -= 4;
    }
    $effectValue = max(-40, $effectValue);

    return [
        'plague_name' => (string)$entry['name'] . ' ' . $severity,
        'severity' => $severity,
        'effect_type' => (string)$entry['effect_type'],
        'effect_value' => $effectValue,
        'symptom' => (string)$entry['symptom'],
        'target_type' => $targetType,
        'biome_name' => $biomeName,
    ];
}

function ensureUniversePlagueTables(Game $s): void {
    $s->query("CREATE TABLE IF NOT EXISTS universe_world_plagues (
        uid INT NOT NULL,
        world_index INT NOT NULL DEFAULT 0,
        target_type VARCHAR(10) NOT NULL DEFAULT 'planet',
        moon_no INT NOT NULL DEFAULT 0,
        biome_name VARCHAR(80) NOT NULL DEFAULT '',
        plague_name VARCHAR(80) NOT NULL,
        severity INT NOT NULL DEFAULT 1,
        effect_type VARCHAR(24) NOT NULL DEFAULT 'habitability',
        effect_value INT NOT NULL DEFAULT 0,
        symptom VARCHAR(160) NOT NULL DEFAULT '',
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(uid, world_index, target_type, moon_no, plague_name),
        KEY idx_uid_world (uid, world_index, target_type)
    )");
}

function universeCreatePlague(Game $s, int $uid, array $world, string $targetType, int $moonNo, string $biomeName): string {
    $targetType = in_array($targetType, ['planet', 'moon', 'biome'], true) ? $targetType : 'planet';
    $worldIndex = max(1, (int)($world['idx'] ?? 0));
    $moonNo = max(0, (int)$moonNo);
    if ($targetType === 'moon' && $moonNo <= 0) {
        return 'Plague creation failed: a moon index is required.';
    }

    $checkQ = $s->query("SELECT plague_name FROM universe_world_plagues WHERE uid=" . (int)$uid . " AND world_index=" . $worldIndex . " AND target_type='" . $targetType . "' AND moon_no=" . $moonNo . " LIMIT 1");
    if ($checkQ && $checkQ->num_rows > 0) {
        return 'Plague creation failed: this target already carries an active plague.';
    }

    $seed = ((int)$uid * 131) + ($worldIndex * 17) + ($moonNo * 29) + strlen((string)$biomeName);
    $plague = universePlagueProfile($seed, $targetType, (string)$biomeName);
    $targetLabel = ($targetType === 'moon') ? 'moon #' . $moonNo : (($targetType === 'biome') ? 'biome' : 'planet');
    $insert = "INSERT INTO universe_world_plagues (uid, world_index, target_type, moon_no, biome_name, plague_name, severity, effect_type, effect_value, symptom)
        VALUES (" . (int)$uid . ", " . $worldIndex . ", '" . $targetType . "', " . $moonNo . ", '" . pageSafeToken((string)$biomeName) . "', '" . pageSafeToken((string)$plague['plague_name']) . "', " . (int)$plague['severity'] . ", '" . pageSafeToken((string)$plague['effect_type']) . "', " . (int)$plague['effect_value'] . ", '" . pageSafeToken((string)$plague['symptom']) . "')";
    if (!$s->query($insert)) {
        return 'Plague creation failed: could not persist the plague event.';
    }

    $effectText = ((int)$plague['effect_value'] < 0 ? 'decrease' : 'increase') . ' of ' . (string)$plague['effect_type'];
    return 'Plague created on ' . $targetLabel . ': ' . (string)$plague['plague_name'] . ' (severity ' . (int)$plague['severity'] . ') — ' . (string)$plague['symptom'] . ' Effect: ' . abs((int)$plague['effect_value']) . ' ' . $effectText . '.';
}

function universePlagueRowsForWorld(Game $s, int $uid, int $worldIndex): array {
    $worldIndex = max(1, (int)$worldIndex);
    $q = $s->query("SELECT world_index, target_type, moon_no, biome_name, plague_name, severity, effect_type, effect_value, symptom, status, UNIX_TIMESTAMP(created_at) AS created_ts
        FROM universe_world_plagues
        WHERE uid=" . (int)$uid . " AND world_index=" . $worldIndex . "
        ORDER BY target_type ASC, moon_no ASC, created_at ASC");
    $rows = [];
    if ($q) {
        while ($row = $q->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function universePlagueSummaryText(array $rows): string {
    if (count($rows) === 0) {
        return 'None';
    }
    $labels = [];
    foreach ($rows as $row) {
        $labels[] = (string)($row['plague_name'] ?? 'Plague') . ' (' . ((int)($row['severity'] ?? 1)) . ')';
    }
    return implode(', ', array_slice($labels, 0, 2));
}

function universeWaterCatalog(): array {
    return [
        ['name' => 'Aqua Spring', 'effect_type' => 'water', 'effect_value' => 3200, 'potency' => 2, 'description' => 'A clean spring rises from a fractured aquifer.'],
        ['name' => 'Glacier Lens', 'effect_type' => 'water', 'effect_value' => 4600, 'potency' => 3, 'description' => 'A frozen reservoir feeds deep channels beneath the surface.'],
        ['name' => 'Tidal Well', 'effect_type' => 'water', 'effect_value' => 5400, 'potency' => 4, 'description' => 'A briny seam shifts with the world tides and moon pull.'],
        ['name' => 'Orbital Dew Basin', 'effect_type' => 'water', 'effect_value' => 7000, 'potency' => 5, 'description' => 'A cold trap gathers condensate from the upper atmosphere.'],
    ];
}

function universeWaterProfile(int &$seed, string $targetType, string $biomeName): array {
    $catalog = universeWaterCatalog();
    $entry = $catalog[universeRand($seed, 0, count($catalog) - 1)];
    $potency = universeRand($seed, 1, 5);
    $effectValue = (int)$entry['effect_value'] + ($potency * 650);
    if ($targetType === 'moon') {
        $effectValue += 700;
    }
    if ($targetType === 'biome') {
        $effectValue += 900;
    }

    return [
        'water_name' => (string)$entry['name'] . ' ' . $potency,
        'effect_type' => (string)$entry['effect_type'],
        'effect_value' => $effectValue,
        'potency' => $potency,
        'description' => (string)$entry['description'],
        'target_type' => $targetType,
        'biome_name' => $biomeName,
    ];
}

function ensureUniverseWaterTables(Game $s): void {
    $s->query("CREATE TABLE IF NOT EXISTS universe_world_water_sources (
        uid INT NOT NULL,
        world_index INT NOT NULL DEFAULT 0,
        target_type VARCHAR(10) NOT NULL DEFAULT 'planet',
        moon_no INT NOT NULL DEFAULT 0,
        biome_name VARCHAR(80) NOT NULL DEFAULT '',
        water_name VARCHAR(80) NOT NULL,
        effect_type VARCHAR(24) NOT NULL DEFAULT 'water',
        effect_value INT NOT NULL DEFAULT 0,
        potency INT NOT NULL DEFAULT 1,
        description VARCHAR(160) NOT NULL DEFAULT '',
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(uid, world_index, target_type, moon_no, water_name),
        KEY idx_uid_world_water (uid, world_index, target_type)
    )");
}

function universeCreateWater(Game $s, int $uid, array $world, string $targetType, int $moonNo, string $biomeName): string {
    $targetType = in_array($targetType, ['planet', 'moon', 'biome'], true) ? $targetType : 'planet';
    $worldIndex = max(1, (int)($world['idx'] ?? 0));
    $moonNo = max(0, (int)$moonNo);
    if ($targetType === 'moon' && $moonNo <= 0) {
        return 'Water creation failed: a moon index is required.';
    }

    $checkQ = $s->query("SELECT water_name FROM universe_world_water_sources WHERE uid=" . (int)$uid . " AND world_index=" . $worldIndex . " AND target_type='" . $targetType . "' AND moon_no=" . $moonNo . " LIMIT 1");
    if ($checkQ && $checkQ->num_rows > 0) {
        return 'Water creation failed: this target already has a water source.';
    }

    $seed = ((int)$uid * 173) + ($worldIndex * 29) + ($moonNo * 37) + strlen((string)$biomeName);
    $water = universeWaterProfile($seed, $targetType, (string)$biomeName);
    $targetLabel = ($targetType === 'moon') ? 'moon #' . $moonNo : (($targetType === 'biome') ? 'biome' : 'planet');
    $insert = "INSERT INTO universe_world_water_sources (uid, world_index, target_type, moon_no, biome_name, water_name, effect_type, effect_value, potency, description)
        VALUES (" . (int)$uid . ", " . $worldIndex . ", '" . $targetType . "', " . $moonNo . ", '" . pageSafeToken((string)$biomeName) . "', '" . pageSafeToken((string)$water['water_name']) . "', '" . pageSafeToken((string)$water['effect_type']) . "', " . (int)$water['effect_value'] . ", " . (int)$water['potency'] . ", '" . pageSafeToken((string)$water['description']) . "')";
    if (!$s->query($insert)) {
        return 'Water creation failed: could not persist the water source.';
    }

    $boostText = 'Water output +' . fnum((int)$water['effect_value']) . ' ' . (string)$water['effect_type'];
    return 'Water source created on ' . $targetLabel . ': ' . (string)$water['water_name'] . ' (potency ' . (int)$water['potency'] . ') — ' . (string)$water['description'] . ' ' . $boostText . '.';
}

function universeWaterRowsForWorld(Game $s, int $uid, int $worldIndex): array {
    $worldIndex = max(1, (int)$worldIndex);
    $q = $s->query("SELECT world_index, target_type, moon_no, biome_name, water_name, effect_type, effect_value, potency, description, status, UNIX_TIMESTAMP(created_at) AS created_ts
        FROM universe_world_water_sources
        WHERE uid=" . (int)$uid . " AND world_index=" . $worldIndex . "
        ORDER BY target_type ASC, moon_no ASC, created_at ASC");
    $rows = [];
    if ($q) {
        while ($row = $q->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function universeWaterSummaryText(array $rows): string {
    if (count($rows) === 0) {
        return 'None';
    }
    $labels = [];
    foreach ($rows as $row) {
        $labels[] = (string)($row['water_name'] ?? 'Water Source') . ' (' . ((int)($row['potency'] ?? 1)) . ')';
    }
    return implode(', ', array_slice($labels, 0, 2));
}

function universeBiomeProfile(int &$seed, string $type): array {
    $taxonomy = universeTaxonomy();
    $biomes = $taxonomy['biomes'];
    $subBiomes = $taxonomy['subBiomes'];
    $biome = universePick($seed, $biomes[$type] ?? ['Unknown']);
    $subBiome = universePick($seed, $subBiomes[$biome] ?? ['Frontier Zone']);
    return ['biome' => $biome, 'subBiome' => $subBiome];
}

function universeMoonProfile(int &$seed, string $moonClass, int $moonCount): array {
    $taxonomy = universeTaxonomy();
    $moonBiomes = $taxonomy['moonBiomes'];
    $moonSubBiomes = $taxonomy['moonSubBiomes'];
    if ($moonCount <= 0) {
        return ['moonBiome' => 'None', 'moonSubBiome' => 'None'];
    }
    $moonBiome = universePick($seed, $moonBiomes[$moonClass] ?? ['Unknown Lunar Zone']);
    $moonSubBiome = universePick($seed, $moonSubBiomes[$moonBiome] ?? ['Uncharted Crater']);
    return ['moonBiome' => $moonBiome, 'moonSubBiome' => $moonSubBiome];
}

function universeNpcRaces(): array {
    return [
        ['key' => 'goauld', 'name' => "Goa'uld", 'alignment' => 'hostile', 'description' => 'Parasitic host-symbionts who enslave worlds through fear and military might.', 'focus' => 'warfare', 'power' => 380],
        ['key' => 'replicator', 'name' => 'Replicator', 'alignment' => 'hostile', 'description' => 'Self-replicating machines that consume worlds into block swarms.', 'focus' => 'industry', 'power' => 460],
        ['key' => 'wraith', 'name' => 'Wraith', 'alignment' => 'hostile', 'description' => 'Feeding predators who cull entire populations across the dark reaches.', 'focus' => 'warfare', 'power' => 410],
        ['key' => 'ori', 'name' => 'Ori', 'alignment' => 'hostile', 'description' => 'Ascended zealots who demand worship and burn the unenlightened.', 'focus' => 'research', 'power' => 500],
        ['key' => 'genii', 'name' => 'Genii', 'alignment' => 'neutral', 'description' => 'Secretive industrial factions hoarding nuclear arsenals and old technology.', 'focus' => 'industry', 'power' => 260],
        ['key' => 'jaffa', 'name' => 'Jaffa', 'alignment' => 'neutral', 'description' => 'Warrior-clans freed from their gods, now carving out independent dominions.', 'focus' => 'defense', 'power' => 300],
        ['key' => 'unas', 'name' => 'Unas', 'alignment' => 'neutral', 'description' => 'Ancient burly survivors of the first host-symbiont wars.', 'focus' => 'defense', 'power' => 210],
        ['key' => 'reetou', 'name' => 'Reetou', 'alignment' => 'hostile', 'description' => 'Invisible phased assassins who flicker in and out of normal reality.', 'focus' => 'espionage', 'power' => 240],
        ['key' => 'vanir', 'name' => 'Vanir', 'alignment' => 'neutral', 'description' => 'Cryo-preserved Asgard offshoots experimenting on worlds for survival.', 'focus' => 'research', 'power' => 330],
        ['key' => 'nox', 'name' => 'Nox', 'alignment' => 'friendly', 'description' => 'Gentle pacifists who hide their worlds behind illusions and ancient power.', 'focus' => 'research', 'power' => 190],
    ];
}

function universeNpcRaceForWorld(int &$seed): array {
    $races = universeNpcRaces();
    $race = $races[universeRand($seed, 0, count($races) - 1)];
    $powerScale = universeRand($seed, 8000, 26000);
    return [
        'npcRace' => $race['key'],
        'npcName' => $race['name'],
        'npcAlignment' => $race['alignment'],
        'npcDescription' => $race['description'],
        'npcFocus' => $race['focus'],
        'npcPower' => (int)($race['power'] * ($powerScale / 1000)),
    ];
}

function buildUniverseSnapshot(int $uid, array $ownedPlanets): array {
    $seed = (($uid + 11) * 7919) & 0x7fffffff;
    $taxonomy = universeTaxonomy();
    $worldTypes = $taxonomy['worldTypes'];
    $biomes = $taxonomy['biomes'];

    $galaxies = [];
    $worlds = [];
    $objects = [];
    $ownedIdx = 0;
    $moonTotal = 0;
    $colonizable = 0;
    $npcWorlds = 0;

    for ($g = 1; $g <= 4; $g++) {
        $galName = 'G' . $g;
        $totalHabitability = 0;
        $galWorldCount = 0;
        $galMoonCount = 0;

        for ($sector = 1; $sector <= 6; $sector++) {
            for ($orbit = 1; $orbit <= 6; $orbit++) {
                $type = universePick($seed, $worldTypes);
                $biomeProfile = universeBiomeProfile($seed, $type);
                $biome = (string)$biomeProfile['biome'];
                $subBiome = (string)$biomeProfile['subBiome'];

                $habitability = universeRand($seed, 18, 98);
                $metal = universeRand($seed, 220, 1200);
                $crystal = universeRand($seed, 120, 980);
                $deut = universeRand($seed, 60, 760);

                $moonCount = ($type === 'Gas Dwarf' || $type === 'Relic') ? universeRand($seed, 1, 3) : universeRand($seed, 0, 2);
                $moonClass = $moonCount > 0 ? universePick($seed, ['Rocky', 'Icy', 'Metallic', 'Ruined']) : '-';
                $moonProfile = universeMoonProfile($seed, $moonClass, $moonCount);
                $slots = max(2, (int)floor($habitability / 12));

                $owner = 'Unclaimed';
                $planetLabel = $galName . '-' . $sector . ':' . $orbit;
                $npc = null;
                if ($ownedIdx < count($ownedPlanets)) {
                    $owner = 'Player Colony';
                    $planetLabel = (string)$ownedPlanets[$ownedIdx]['name'];
                    $ownedIdx++;
                } else {
                    $npcRoll = universeRand($seed, 1, 100);
                    if ($npcRoll <= 40) {
                        $npc = universeNpcRaceForWorld($seed);
                        $owner = $npc['npcName'] . ' Territory';
                    }
                }

                $worlds[] = [
                    'coord' => $galName . ' [' . $sector . ':' . $orbit . ']',
                    'name' => $planetLabel,
                    'type' => $type,
                    'biome' => $biome,
                    'subBiome' => $subBiome,
                    'habitability' => $habitability,
                    'slots' => $slots,
                    'metal' => $metal,
                    'crystal' => $crystal,
                    'deut' => $deut,
                    'moons' => $moonCount,
                    'moonClass' => $moonClass,
                    'moonBiome' => (string)$moonProfile['moonBiome'],
                    'moonSubBiome' => (string)$moonProfile['moonSubBiome'],
                    'owner' => $owner,
                    'npcRace' => $npc['npcRace'] ?? '',
                    'npcName' => $npc['npcName'] ?? '',
                    'npcAlignment' => $npc['npcAlignment'] ?? '',
                    'npcDescription' => $npc['npcDescription'] ?? '',
                    'npcFocus' => $npc['npcFocus'] ?? '',
                    'npcPower' => $npc['npcPower'] ?? 0,
                ];

                $totalHabitability += $habitability;
                $galMoonCount += $moonCount;
                $galWorldCount++;
                if ($habitability >= 48 && $owner === 'Unclaimed') {
                    $colonizable++;
                }
                if ($npc !== null) {
                    $npcWorlds++;
                }
            }
        }

        $galaxies[] = [
            'name' => $galName,
            'sectors' => 6,
            'worlds' => $galWorldCount,
            'avgHab' => $galWorldCount > 0 ? (int)round($totalHabitability / $galWorldCount) : 0,
            'moons' => $galMoonCount,
        ];
        $moonTotal += $galMoonCount;

        $objects[] = [
            'galaxy' => $galName,
            'asteroidBelts' => universeRand($seed, 8, 24),
            'debrisFields' => universeRand($seed, 4, 16),
            'nebulae' => universeRand($seed, 2, 9),
            'cometStreams' => universeRand($seed, 1, 7),
            'wormholes' => universeRand($seed, 0, 3),
            'ancientRuins' => universeRand($seed, 1, 5),
        ];
    }

    return [
        'seed' => (($uid + 11) * 7919) & 0x7fffffff,
        'galaxies' => $galaxies,
        'worlds' => $worlds,
        'objects' => $objects,
        'summary' => [
            'totalGalaxies' => count($galaxies),
            'totalWorlds' => count($worlds),
            'totalMoons' => $moonTotal,
            'colonizableWorlds' => $colonizable,
            'npcWorlds' => $npcWorlds,
            'ownedColonies' => count($ownedPlanets),
        ],
    ];
}

function universeConfig(): array {
    $galaxies = 134;
    $systemsPerGalaxy = 499;
    $positionsPerSystem = 15;
    $maxWorlds = $galaxies * $systemsPerGalaxy * $positionsPerSystem;

    return [
        'maxWorlds' => $maxWorlds,
        'maxColonies' => 1000000,
        'maxMoons' => 1000000,
        'galaxies' => $galaxies,
        'systemsPerGalaxy' => $systemsPerGalaxy,
        'positionsPerSystem' => $positionsPerSystem,
        'sectorsPerGalaxy' => $systemsPerGalaxy,
        'orbitsPerSector' => $positionsPerSystem,
    ];
}

function universeWorldByIndex(int $uid, array $ownedPlanets, int $index, array $cfg): array {
    $seed = (($uid + 17) * 6151 + ($index * 97)) & 0x7fffffff;
    $taxonomy = universeTaxonomy();
    $worldTypes = $taxonomy['worldTypes'];
    $biomes = $taxonomy['biomes'];

    $type = universePick($seed, $worldTypes);
    $biomeProfile = universeBiomeProfile($seed, $type);
    $biome = (string)$biomeProfile['biome'];
    $subBiome = (string)$biomeProfile['subBiome'];
    $habitability = universeRand($seed, 18, 98);
    $metal = universeRand($seed, 220, 1200);
    $crystal = universeRand($seed, 120, 980);
    $deut = universeRand($seed, 60, 760);
    $moonCount = ($type === 'Gas Dwarf' || $type === 'Relic') ? universeRand($seed, 1, 3) : universeRand($seed, 0, 2);
    $moonClass = $moonCount > 0 ? universePick($seed, ['Rocky', 'Icy', 'Metallic', 'Ruined']) : '-';
    $moonProfile = universeMoonProfile($seed, $moonClass, $moonCount);
    $slots = max(2, (int)floor($habitability / 12));

    $systemsPerGalaxy = (int)($cfg['systemsPerGalaxy'] ?? $cfg['sectorsPerGalaxy']);
    $positionsPerSystem = (int)($cfg['positionsPerSystem'] ?? $cfg['orbitsPerSector']);
    $worldsPerGalaxy = (int)($systemsPerGalaxy * $positionsPerSystem);
    $galaxyIndex = (int)floor(($index - 1) / $worldsPerGalaxy) + 1;
    $withinGalaxy = (($index - 1) % $worldsPerGalaxy) + 1;
    $system = (int)floor(($withinGalaxy - 1) / $positionsPerSystem) + 1;
    $position = (($withinGalaxy - 1) % $positionsPerSystem) + 1;

    $owner = 'Unclaimed';
    $planetLabel = 'G' . $galaxyIndex . '-' . $system . ':' . $position;
    $npc = null;
    if ($index <= count($ownedPlanets)) {
        $owner = 'Player Colony';
        $planetLabel = (string)($ownedPlanets[$index - 1]['name'] ?? $planetLabel);
    } else {
        $npcRoll = universeRand($seed, 1, 100);
        if ($npcRoll <= 40) {
            $npc = universeNpcRaceForWorld($seed);
            $owner = $npc['npcName'] . ' Territory';
        }
    }

    return [
        'idx' => $index,
        'coord' => 'G' . $galaxyIndex . ' [' . $system . ':' . $position . ']',
        'system' => $system,
        'position' => $position,
        'name' => $planetLabel,
        'type' => $type,
        'biome' => $biome,
        'subBiome' => $subBiome,
        'habitability' => $habitability,
        'slots' => $slots,
        'metal' => $metal,
        'crystal' => $crystal,
        'deut' => $deut,
        'moons' => $moonCount,
        'moonClass' => $moonClass,
        'moonBiome' => (string)$moonProfile['moonBiome'],
        'moonSubBiome' => (string)$moonProfile['moonSubBiome'],
        'owner' => $owner,
        'npcRace' => $npc['npcRace'] ?? '',
        'npcName' => $npc['npcName'] ?? '',
        'npcAlignment' => $npc['npcAlignment'] ?? '',
        'npcDescription' => $npc['npcDescription'] ?? '',
        'npcFocus' => $npc['npcFocus'] ?? '',
        'npcPower' => $npc['npcPower'] ?? 0,
    ];
}

function universeWorldSlice(int $uid, array $ownedPlanets, array $cfg, int $page, int $perPage): array {
    $total = (int)$cfg['maxWorlds'];
    $page = max(1, $page);
    $perPage = max(10, min(200, $perPage));
    $maxPage = max(1, (int)ceil($total / $perPage));
    if ($page > $maxPage) {
        $page = $maxPage;
    }
    $start = (($page - 1) * $perPage) + 1;
    $end = min($total, $start + $perPage - 1);

    $rows = [];
    for ($i = $start; $i <= $end; $i++) {
        $rows[] = universeWorldByIndex($uid, $ownedPlanets, $i, $cfg);
    }

    return [
        'rows' => $rows,
        'page' => $page,
        'perPage' => $perPage,
        'maxPage' => $maxPage,
        'start' => $start,
        'end' => $end,
        'total' => $total,
    ];
}

function universeColonizeCosts(array $world): array {
    $habitability = (int)($world['habitability'] ?? 0);
    $slots = (int)($world['slots'] ?? 2);
    $moons = (int)($world['moons'] ?? 0);

    $naqCost = 120000 + ((100 - max(0, min(100, $habitability))) * 1500) + ($moons * 25000);
    $deutCost = 12000 + ($slots * 1300);
    $foodCost = 9000 + ($slots * 900);
    $waterCost = 9000 + ($slots * 900);
    $popCost = 5000 + ($slots * 450);
    $turnCost = 25;

    return [
        'naq' => $naqCost,
        'deut' => $deutCost,
        'food' => $foodCost,
        'water' => $waterCost,
        'pop' => $popCost,
        'turns' => $turnCost,
    ];
}

function universeColonizeWorld(Game $s, int $uid, array $cfg, array $ownedPlanets, int $targetWorld): string {
    if ($targetWorld < 1 || $targetWorld > (int)$cfg['maxWorlds']) {
        return 'Colonization failed: invalid world target.';
    }

    if (count($ownedPlanets) >= (int)$cfg['maxColonies']) {
        return 'Colonization failed: colony cap reached.';
    }

    if ($targetWorld <= count($ownedPlanets)) {
        return 'Colonization failed: target already owned.';
    }

    $world = universeWorldByIndex($uid, $ownedPlanets, $targetWorld, $cfg);
    if ((string)($world['owner'] ?? '') !== 'Unclaimed') {
        return 'Colonization failed: target world is no longer available.';
    }
    if ((int)($world['habitability'] ?? 0) < 46) {
        return 'Colonization failed: habitability too low. Requires 46%+.';
    }

    $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . (int)$uid . " LIMIT 1");
    $turns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
    $costs = universeColonizeCosts($world);
    if ($turns < (int)$costs['turns']) {
        return 'Colonization failed: not enough action turns.';
    }

    $bank = $s->bank();
    $onHand = (int)($bank->onHand ?? 0);
    if ($onHand < (int)$costs['naq']) {
        return 'Colonization failed: insufficient Naquadah on hand.';
    }

    $s->query("INSERT IGNORE INTO player_resources (uid) VALUES (" . (int)$uid . ")");

    $resQ = $s->query("SELECT deuterium,food,water,population FROM player_resources WHERE uid=" . (int)$uid . " LIMIT 1");
    $resObj = $resQ ? $resQ->fetch_object() : null;
    $curDeut = (int)($resObj->deuterium ?? 0);
    $curFood = (int)($resObj->food ?? 0);
    $curWater = (int)($resObj->water ?? 0);
    $curPop = (int)($resObj->population ?? 0);

    if ($curDeut < (int)$costs['deut'] || $curFood < (int)$costs['food'] || $curWater < (int)$costs['water'] || $curPop < (int)$costs['pop']) {
        return 'Colonization failed: insufficient strategic resources (D/F/W/Population).';
    }

    $pidQ = $s->query("SELECT IFNULL(MAX(pid), 0) + 1 AS nextPid FROM planets WHERE uid=" . (int)$uid);
    $nextPid = $pidQ ? (int)($pidQ->fetch_object()->nextPid ?? 1) : 1;
    if ($nextPid < 1) {
        $nextPid = 1;
    }

    $rawName = 'Colony ' . (int)$targetWorld;
    $safeName = preg_replace('/[^A-Za-z0-9 _-]/', '', $rawName);
    if (!$safeName) {
        $safeName = 'Colony ' . (int)$targetWorld;
    }
    $safeText = preg_replace('/[^A-Za-z0-9 _:\[\]-]/', '', (string)($world['coord'] . ' ' . $world['type']));
    $incomeBonus = max(20, (int)floor((int)$world['metal'] / 20));
    $upBonus = max(8, (int)floor((int)$world['habitability'] / 6));
    $sizeCode = max(0, min(9, (int)$world['slots'] - 2));

    $insert = "INSERT INTO planets (uid, text, plnt_name, income_bonus, up_bonus, isHome, pid, plnt_size) VALUES ("
        . (int)$uid . ", '" . $safeText . "', '" . $safeName . "', " . $incomeBonus . ", " . $upBonus . ", 0, " . $nextPid . ", " . $sizeCode . ")";
    if (!$s->query($insert)) {
        return 'Colonization failed: could not create colony record.';
    }

    $s->query("UPDATE bank SET onHand = GREATEST(0, onHand - " . (int)$costs['naq'] . ") WHERE uid=" . (int)$uid . " LIMIT 1");
    $s->query("UPDATE player_resources SET deuterium = GREATEST(0, deuterium - " . (int)$costs['deut'] . "), food = GREATEST(0, food - " . (int)$costs['food'] . "), water = GREATEST(0, water - " . (int)$costs['water'] . "), population = GREATEST(0, population - " . (int)$costs['pop'] . ") WHERE uid=" . (int)$uid . " LIMIT 1");
    $s->query("UPDATE userdata SET actionTurns = GREATEST(0, actionTurns - " . (int)$costs['turns'] . ") WHERE uid=" . (int)$uid . " LIMIT 1");
    $s->updatePower($uid);

    return 'Colonization successful: ' . $safeName . ' established at ' . (string)$world['coord'] . ' (Cost: ' . fnum($costs['naq']) . ' Naquadah, ' . fnum($costs['turns']) . ' turns).';
}

function universeSeedWorldCities(Game $s, int $uid, array $worlds): void {
    $planetRows = [];
    $moonRows = [];
    foreach ($worlds as $world) {
        $worldIndex = (int)($world['idx'] ?? 0);
        if ($worldIndex < 1) {
            continue;
        }
        if (trim((string)($world['biome'] ?? '')) === '') {
            continue;
        }
        $habitability = (int)($world['habitability'] ?? 0);
        $slots = (int)($world['slots'] ?? 2);
        $planetFieldTotal = max(16, ($slots * 8) + (int)floor($habitability / 5));
        $planetCityName = 'Colony City ' . $worldIndex;
        $planetRows[] = "(" . $uid . "," . $worldIndex . ",'planet',0,"
            . "'" . pageSafeToken((string)$world['type']) . "',"
            . "'" . pageSafeToken((string)$world['biome']) . "',"
            . "'" . pageSafeToken((string)$world['subBiome']) . "',"
            . "'" . pageSafeToken($planetCityName) . "',"
            . $planetFieldTotal . ")";
        $moonCount = (int)($world['moons'] ?? 0);
        for ($mn = 1; $mn <= $moonCount; $mn++) {
            $moonFieldTotal = max(6, 4 + ($mn * 2) + (int)floor($habitability / 18));
            $moonCity = 'Moon Outpost ' . $worldIndex . '-' . $mn;
            $moonRows[] = "(" . $uid . "," . $worldIndex . ",'moon'," . $mn . ","
                . "'" . pageSafeToken((string)$world['moonClass'] . ' Moon') . "',"
                . "'" . pageSafeToken((string)$world['moonBiome']) . "',"
                . "'" . pageSafeToken((string)$world['moonSubBiome']) . "',"
                . "'" . pageSafeToken($moonCity) . "',"
                . $moonFieldTotal . ")";
        }
    }
    if (count($planetRows) > 0) {
        $s->query("INSERT IGNORE INTO universe_colony_profiles
            (uid, world_index, target_type, moon_no, world_type, biome, sub_biome, city_name, field_total)
            VALUES " . implode(", ", $planetRows));
    }
    if (count($moonRows) > 0) {
        $s->query("INSERT IGNORE INTO universe_colony_profiles
            (uid, world_index, target_type, moon_no, world_type, biome, sub_biome, city_name, field_total)
            VALUES " . implode(", ", $moonRows));
    }
}

function researchPick(int &$seed, array $list): string {
    if (count($list) === 0) {
        return '';
    }
    $idx = universeRand($seed, 0, count($list) - 1);
    return (string)$list[$idx];
}

function buildResearchDirectorate(int $uid, $techView, $personnel): array {
    $seed = (($uid + 41) * 104729) & 0x7fffffff;

    $domains = ['Quantum', 'Void', 'Psionic', 'Nano', 'Graviton', 'Xeno', 'Bioforge', 'Temporal', 'Stellar', 'Aegis'];
    $focuses = ['Warfare', 'Economy', 'Espionage', 'Logistics', 'Expansion', 'Defense'];
    $typePool = ['Offensive', 'Defensive', 'Support', 'Industrial', 'Recon', 'Colonial'];
    $subTypePool = ['Kinetic', 'Energy', 'Psionic', 'Stealth', 'Command', 'Terraform', 'Recovery', 'Anomaly'];

    $classRoles = ['Architect', 'Sentinel', 'Reaver', 'Oracle', 'Warden', 'Harbinger', 'Cipher', 'Ranger', 'Artificer'];
    $subclassRoles = ['Prime', 'Vanguard', 'Seeker', 'Bastion', 'Catalyst', 'Executor', 'Scholar', 'Pathfinder', 'Anchor'];

    $classes = [];
    $classId = 1;
    foreach ($domains as $domain) {
        foreach ($classRoles as $idx => $role) {
            $type = $typePool[$idx % count($typePool)];
            $subType = $subTypePool[($idx + universeRand($seed, 0, 7)) % count($subTypePool)];
            $classes[] = [
                'id' => $classId,
                'className' => $domain . ' ' . $role,
                'subClass' => $domain . ' ' . $subclassRoles[$idx],
                'type' => $type,
                'subType' => $subType,
            ];
            $classId++;
        }
    }

    $researchTree = [];
    $techTree = [];
    foreach ($domains as $domain) {
        $researchNodes = [];
        $techNodes = [];
        for ($tier = 1; $tier <= 6; $tier++) {
            $researchNodes[] = [
                'name' => $domain . ' Research Tier ' . $tier,
                'focus' => researchPick($seed, $focuses),
                'cost' => (50000 * $tier) + universeRand($seed, 2500, 15000),
                'power' => universeRand($seed, 4, 18) * $tier,
            ];
            $techNodes[] = [
                'name' => $domain . ' Technology Tier ' . $tier,
                'focus' => researchPick($seed, $focuses),
                'cost' => (65000 * $tier) + universeRand($seed, 3500, 18000),
                'power' => universeRand($seed, 5, 22) * $tier,
            ];
        }
        $researchTree[] = ['domain' => $domain, 'nodes' => $researchNodes];
        $techTree[] = ['domain' => $domain, 'nodes' => $techNodes];
    }

    $talentPrefixes = ['Adaptive', 'Warped', 'Hyper', 'Focused', 'Deep', 'Prime', 'Echo', 'Null', 'Stellar', 'Iron', 'Arc', 'Silent'];
    $talentCore = ['Matrix', 'Lattice', 'Protocol', 'Vector', 'Engine', 'Manifold', 'Beacon', 'Circuit', 'Doctrine', 'Kernel'];
    $talentSuffix = ['Surge', 'Lock', 'Burst', 'Thread', 'Field', 'Link', 'Sight', 'Ward', 'Pulse', 'Drive'];

    $talents = [];
    for ($i = 1; $i <= 240; $i++) {
        $isResearch = $i <= 120;
        $tier = 1 + (int)floor(($i - 1) / 30);
        $domain = $domains[($i - 1) % count($domains)];
        $focus = $focuses[($i + 2) % count($focuses)];
        $prefix = $talentPrefixes[($i + universeRand($seed, 0, 11)) % count($talentPrefixes)];
        $core = $talentCore[($i + universeRand($seed, 0, 9)) % count($talentCore)];
        $suffix = $talentSuffix[($i + universeRand($seed, 0, 9)) % count($talentSuffix)];

        $talents[] = [
            'id' => $i,
            'branch' => $isResearch ? 'Research' : 'Technology',
            'domain' => $domain,
            'focus' => $focus,
            'tier' => $tier,
            'name' => $prefix . ' ' . $core . ' ' . $suffix,
            'effect' => ($isResearch ? 'Lab Output +' : 'Tech Throughput +') . universeRand($seed, 2, 12) . '%',
        ];
    }

    $ttl = (int)($techView->ttl ?? 0);
    $asc = (int)($techView->ascend ?? 0);
    $commandLevel = max(1, 1 + (int)floor(($ttl + ($asc * 25)) / 10));
    $xpToNext = ($commandLevel * 1200) + ($asc * 500);

    $stats = [
        'Research Mastery' => 60 + ($commandLevel * 4),
        'Tech Integration' => 55 + ($commandLevel * 3),
        'Doctrine Control' => 50 + ($commandLevel * 3),
        'Fleet Engineering' => 45 + ($commandLevel * 4),
        'Expedition Theory' => 48 + ($commandLevel * 3),
    ];

    $subStats = [
        'Lab Efficiency' => 35 + ((int)($techView->income ?? 0) * 2),
        'Prototype Speed' => 30 + ((int)($techView->uppl ?? 0) * 2),
        'Resource Fidelity' => 28 + ((int)($techView->duRes ?? 0)),
        'Signal Intelligence' => 32 + ((int)($techView->cuEffect ?? 0)),
        'Containment Stability' => 26 + ((int)($techView->pDef ?? 0)),
        'Field Logistics' => 34 + (int)floor((int)($personnel->uuCount ?? 0) / 10000),
    ];

    return [
        'counts' => [
            'classes' => count($classes),
            'subclasses' => count($classes),
            'types' => count($typePool),
            'subtypes' => count($subTypePool),
            'talents' => count($talents),
        ],
        'level' => [
            'commandLevel' => $commandLevel,
            'researchLevel' => max(1, 1 + (int)floor($ttl / 8)),
            'technologyLevel' => max(1, 1 + (int)floor(($ttl + $asc) / 9)),
            'ascension' => $asc,
            'xpToNext' => $xpToNext,
        ],
        'stats' => $stats,
        'subStats' => $subStats,
        'researchTree' => $researchTree,
        'techTree' => $techTree,
        'classes' => $classes,
        'talents' => $talents,
        'types' => $typePool,
        'subTypes' => $subTypePool,
    ];
}

function resourceEnsureAndTick(Game $s, int $uid, $baseData, array $planets, $techView): array {
    $s->query("INSERT IGNORE INTO player_resources (uid) VALUES (" . (int)$uid . ")");
    $s->query("CREATE TABLE IF NOT EXISTS resource_structures (
        uid INT NOT NULL PRIMARY KEY,
        metal_mine INT NOT NULL DEFAULT 1,
        crystal_lab INT NOT NULL DEFAULT 1,
        deuterium_refinery INT NOT NULL DEFAULT 1,
        hydroponics INT NOT NULL DEFAULT 1,
        water_plant INT NOT NULL DEFAULT 1,
        habitat_dome INT NOT NULL DEFAULT 1,
        energy_reactor INT NOT NULL DEFAULT 1,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $s->query("INSERT IGNORE INTO resource_structures (uid) VALUES (" . (int)$uid . ")");

    $strQ = $s->query("SELECT metal_mine,crystal_lab,deuterium_refinery,hydroponics,water_plant,habitat_dome,energy_reactor FROM resource_structures WHERE uid=" . (int)$uid . " LIMIT 1");
    $structures = $strQ ? $strQ->fetch_object() : (object)[
        'metal_mine' => 1,
        'crystal_lab' => 1,
        'deuterium_refinery' => 1,
        'hydroponics' => 1,
        'water_plant' => 1,
        'habitat_dome' => 1,
        'energy_reactor' => 1,
    ];
    $resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population,energy,last_tick_at FROM player_resources WHERE uid=" . (int)$uid . " LIMIT 1");
    $res = $resQ ? $resQ->fetch_object() : (object)[
        'metal' => 80000,
        'crystal' => 60000,
        'deuterium' => 45000,
        'food' => 55000,
        'water' => 55000,
        'population' => 120000,
        'energy' => 50000,
        'last_tick_at' => date('Y-m-d H:i:s'),
    ];

    $planetCount = max(1, count($planets));
    $incomeBase = max(220, (int)($baseData->income ?? 220));
    $upBase = max(10, (int)($baseData->up ?? 10));
    $techIncome = max(0, (int)($techView->income ?? 0));
    $techProd = max(0, (int)($techView->unitProd ?? 0));

    $rates = [
        'metal' => (int)round((($incomeBase * 0.40) + ($planetCount * 180) + ($upBase * 8) + ($techProd * 20)) * (1 + ((int)$structures->metal_mine * 0.12))),
        'crystal' => (int)round((($incomeBase * 0.28) + ($planetCount * 140) + ($upBase * 5) + ($techIncome * 16)) * (1 + ((int)$structures->crystal_lab * 0.12))),
        'deuterium' => (int)round((($incomeBase * 0.18) + ($planetCount * 120) + ($upBase * 3) + ($techIncome * 12)) * (1 + ((int)$structures->deuterium_refinery * 0.12))),
        'food' => (int)round((($incomeBase * 0.14) + ($planetCount * 220) + ($techIncome * 9)) * (1 + ((int)$structures->hydroponics * 0.10))),
        'water' => (int)round((($incomeBase * 0.12) + ($planetCount * 240) + ($techIncome * 8)) * (1 + ((int)$structures->water_plant * 0.10))),
        'population' => max(25, (int)round((($planetCount * 30) + ($upBase * 0.35)) * (1 + ((int)$structures->habitat_dome * 0.08)))),
        'energy' => (int)round((($incomeBase * 0.22) + ($planetCount * 160) + ($techProd * 14) + ($techIncome * 10)) * (1 + ((int)$structures->energy_reactor * 0.13))),
    ];

    $lastTickTs = strtotime((string)$res->last_tick_at);
    if ($lastTickTs === false) {
        $lastTickTs = time();
    }
    $nowTs = time();
    $tickSeconds = 60;
    $ticks = (int)floor(max(0, $nowTs - $lastTickTs) / $tickSeconds);

    if ($ticks > 0) {
        $metal = max(0, (int)$res->metal + ($rates['metal'] * $ticks));
        $crystal = max(0, (int)$res->crystal + ($rates['crystal'] * $ticks));
        $deuterium = max(0, (int)$res->deuterium + ($rates['deuterium'] * $ticks));
        $food = max(0, (int)$res->food + ($rates['food'] * $ticks));
        $water = max(0, (int)$res->water + ($rates['water'] * $ticks));
        $population = max(0, (int)$res->population + ($rates['population'] * $ticks));
        $energy = max(0, (int)$res->energy + ($rates['energy'] * $ticks));

        $foodUse = (int)round($population * 0.008 * $ticks);
        $waterUse = (int)round($population * 0.007 * $ticks);
        $energyUse = (int)round($population * 0.005 * $ticks);

        $food = max(0, $food - $foodUse);
        $water = max(0, $water - $waterUse);
        $energy = max(0, $energy - $energyUse);

        if ($food === 0 || $water === 0 || $energy === 0) {
            $popDrop = (int)round($population * 0.02);
            $population = max(0, $population - max(150, $popDrop));
        }

        $s->query("UPDATE player_resources SET
            metal=" . (int)$metal . ",
            crystal=" . (int)$crystal . ",
            deuterium=" . (int)$deuterium . ",
            food=" . (int)$food . ",
            water=" . (int)$water . ",
            population=" . (int)$population . ",
            energy=" . (int)$energy . ",
            last_tick_at=NOW()
            WHERE uid=" . (int)$uid . " LIMIT 1");

        $res = (object)[
            'metal' => $metal,
            'crystal' => $crystal,
            'deuterium' => $deuterium,
            'food' => $food,
            'water' => $water,
            'population' => $population,
            'energy' => $energy,
        ];
    }

    return [
        'current' => [
            'metal' => (int)($res->metal ?? 0),
            'crystal' => (int)($res->crystal ?? 0),
            'deuterium' => (int)($res->deuterium ?? 0),
            'food' => (int)($res->food ?? 0),
            'water' => (int)($res->water ?? 0),
            'population' => (int)($res->population ?? 0),
            'energy' => (int)($res->energy ?? 0),
        ],
        'rates' => $rates,
        'structures' => [
            'metal_mine' => (int)$structures->metal_mine,
            'crystal_lab' => (int)$structures->crystal_lab,
            'deuterium_refinery' => (int)$structures->deuterium_refinery,
            'hydroponics' => (int)$structures->hydroponics,
            'water_plant' => (int)$structures->water_plant,
            'habitat_dome' => (int)$structures->habitat_dome,
            'energy_reactor' => (int)$structures->energy_reactor,
        ],
        'ticksApplied' => $ticks,
    ];
}

function renderTreeBoard(array $branches, int $level, string $boardId, string $nodePrefix): void {
    echo '<div class="wows-tree" id="' . h($boardId) . '">';
    echo '<div class="wows-tier-head">';
    echo '<span>Domain</span>';
    for ($tier = 1; $tier <= 6; $tier++) {
        echo '<span>T' . $tier . '</span>';
    }
    echo '</div>';

    foreach ($branches as $branch) {
        echo '<div class="wows-tree-row">';
        echo '<div class="wows-domain">' . h($branch['domain']) . '</div>';
        echo '<div class="wows-node-lane">';

        foreach ($branch['nodes'] as $idx => $node) {
            $tier = $idx + 1;
            $state = 'locked';
            if ($level > $tier) {
                $state = 'unlocked';
            } elseif ($level === $tier) {
                $state = 'available';
            }

            $stateBadge = $state === 'unlocked' ? 'Active' : ($state === 'available' ? 'Ready' : 'Queued');

            echo '<div class="wows-node ' . h($state) . '">';
            echo '<div class="wows-node-badge">' . h($stateBadge) . '</div>';
            echo '<div class="wows-node-title">' . h($nodePrefix . ' ' . $tier) . '</div>';
            echo '<div class="wows-node-name">' . h($node['name']) . '</div>';
            echo '<div class="wows-node-meta">' . h($node['focus']) . '</div>';
            echo '<div class="wows-node-meta">Cost ' . fnum($node['cost']) . ' | Power ' . fnum($node['power']) . '</div>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }

    echo '</div>';
}

function ogameTechEnsureTables(Game $s, int $uid, array $catalog): array {
    $s->query("CREATE TABLE IF NOT EXISTS player_tech_levels (
        uid INT NOT NULL,
        tech_key VARCHAR(48) NOT NULL,
        level INT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (uid, tech_key)
    )");
    $levels = [];
    foreach ($catalog as $def) {
        $key = $def['key'];
        $s->query("INSERT IGNORE INTO player_tech_levels (uid, tech_key, level) VALUES (" . (int)$uid . ", '" . $key . "', 0)");
        $levels[$key] = 0;
    }
    $q = $s->query("SELECT tech_key, level FROM player_tech_levels WHERE uid=" . (int)$uid);
    if ($q) {
        while ($r = $q->fetch_assoc()) {
            $levels[(string)$r['tech_key']] = (int)$r['level'];
        }
    }
    return $levels;
}

function ogameResearchAction(Game $s, int $uid, string $key, array $catalogByKey, array $levels, array $resCurrent, $bankObj, float $discountPct = 0): string {
    if (!isset($catalogByKey[$key])) {
        return 'Research failed: unknown research program.';
    }
    $def = $catalogByKey[$key];
    $cur = (int)($levels[$key] ?? 0);
    if ($cur >= (int)$def['max_level']) {
        return $def['name'] . ' is already at maximum level ' . $cur . '.';
    }
    if (!ogameTechPrereqMet($levels, $def)) {
        return 'Research failed: ' . $def['name'] . ' prerequisites not met (' . ogameTechPrereqText($levels, $def) . ').';
    }
    $costs = ogameTechNextCosts($def, $cur, $discountPct);
    $have = [
        'nq' => (int)($bankObj->onHand ?? 0),
        'metal' => (int)($resCurrent['metal'] ?? 0),
        'crystal' => (int)($resCurrent['crystal'] ?? 0),
        'deut' => (int)($resCurrent['deuterium'] ?? 0),
        'energy' => (int)($resCurrent['energy'] ?? 0),
    ];
    $labels = ['nq' => 'Naquadah', 'metal' => 'Metal', 'crystal' => 'Crystal', 'deut' => 'Deuterium', 'energy' => 'Energy'];
    foreach (['nq', 'metal', 'crystal', 'deut', 'energy'] as $k) {
        if ($have[$k] < $costs[$k]) {
            return 'Research failed: insufficient ' . $labels[$k] . ' for ' . $def['name'] . ' level ' . ($cur + 1) . '.';
        }
    }
    $s->query("UPDATE bank SET onHand = GREATEST(0, onHand - " . $costs['nq'] . ") WHERE uid=" . (int)$uid . " LIMIT 1");
    $s->query("UPDATE player_resources SET metal = GREATEST(0, metal - " . $costs['metal'] . "), crystal = GREATEST(0, crystal - " . $costs['crystal'] . "), deuterium = GREATEST(0, deuterium - " . $costs['deut'] . "), energy = GREATEST(0, energy - " . $costs['energy'] . ") WHERE uid=" . (int)$uid . " LIMIT 1");
    $s->query("INSERT INTO player_tech_levels (uid, tech_key, level) VALUES (" . (int)$uid . ", '" . $key . "', 1) ON DUPLICATE KEY UPDATE level = level + 1");
    return $def['name'] . ' advanced to level ' . ($cur + 1) . '.';
}

function renderOgameTreeBoard(array $branches, array $resCurrent, int $bankOnHand, string $linkSub): void {
    echo '<div class="wows-tree compact" id="ogameTreeBoard">';
    echo '<div class="wows-tier-head"><span>Domain</span><span>Program A</span><span>Program B</span></div>';

    foreach ($branches as $branch) {
        echo '<div class="wows-tree-row">';
        echo '<div class="wows-domain">' . h($branch['domain']) . '</div>';
        echo '<div class="wows-node-lane">';

        foreach ($branch['nodes'] as $node) {
            $state = $node['level'] > 0 ? 'unlocked' : ($node['prereqMet'] ? 'available' : 'locked');
            echo '<div class="wows-node ' . h($state) . '">';
            echo '<div class="wows-node-badge">T' . h($node['tier']) . ' · Lv ' . fnum($node['level']) . '</div>';
            echo '<div class="wows-node-title">' . h($node['name']) . '</div>';
            echo '<div class="wows-node-name">' . h($node['focus']) . '</div>';
            echo '<div class="wows-node-meta">' . h($node['effect']) . '</div>';
            echo '<div class="wows-node-meta">Next: ' . fnum($node['cost']['nq']) . ' Nq / ' . fnum($node['cost']['metal']) . ' M / ' . fnum($node['cost']['crystal']) . ' C / ' . fnum($node['cost']['deut']) . ' D / ' . fnum($node['cost']['energy']) . ' E / ' . fnum($node['cost']['turns']) . ' turns</div>';
            if (!$node['prereqMet']) {
                echo '<div class="wows-node-meta">Prereq: ' . h($node['prereqText']) . '</div>';
            }
            echo '<div class="wows-node-action">';
            if ($node['level'] >= $node['max_level']) {
                echo '<button class="public-btn secondary" disabled>Maxed</button>';
            } elseif (!$node['prereqMet']) {
                echo '<button class="public-btn secondary" disabled>Locked</button>';
            } else {
                echo '<button class="public-btn" onclick="sendData(\'pages\',\'get\',\'research\',\'' . h($linkSub) . '&cmd=ogame_research&key=' . h($node['key']) . '\'); return false">Research L' . fnum($node['level'] + 1) . '</button>';
            }
            echo '</div>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }

    echo '</div>';
}

function ogameReserveLine(array $resCurrent, int $bankOnHand): string {
    return '<strong>Naquadah:</strong> ' . fnum($bankOnHand)
        . ' | <strong>Metal:</strong> ' . fnum((int)($resCurrent['metal'] ?? 0))
        . ' | <strong>Crystal:</strong> ' . fnum((int)($resCurrent['crystal'] ?? 0))
        . ' | <strong>Deuterium:</strong> ' . fnum((int)($resCurrent['deuterium'] ?? 0))
        . ' | <strong>Energy:</strong> ' . fnum((int)($resCurrent['energy'] ?? 0));
}

function renderInfoBlock(array $detail): void {
    echo '<div class="card full"><h4>Operational Brief</h4><p>' . h($detail['brief']) . '</p></div>';

    echo '<div class="card"><h4>Functions</h4><ul>';
    foreach (($detail['functions'] ?? []) as $item) {
        echo '<li>' . h($item) . '</li>';
    }
    echo '</ul></div>';

    echo '<div class="card"><h4>Features</h4><ul>';
    foreach (($detail['features'] ?? []) as $item) {
        echo '<li>' . h($item) . '</li>';
    }
    echo '</ul></div>';

    echo '<div class="card full"><h4>Logic & Rules</h4><ol>';
    foreach (($detail['logic'] ?? []) as $item) {
        echo '<li>' . h($item) . '</li>';
    }
    echo '</ol></div>';
}

function renderMechanicsMatrix(string $main, string $sub): void {
    $core = [
        'Turn cycle runs every 30 minutes and updates key production resources.',
        'Primary strategic resources are Metal, Crystal, Deuterium, Food, Water, Population, plus Naquadah and turn currencies.',
        'Untrained units are generated by unit production and converted into military roles through training.',
    ];

    $race = [
        'Tauri focus: stronger attack posture.',
        'Goa\'uld focus: stronger income posture.',
        'Asgard focus: stronger defense posture.',
        'Replicator focus: stronger covert posture.',
    ];

    $byPage = [
        'operations:attack' => [
            'Attacks consume action turns and compare attacker offensive power against defender defensive power.',
            'Victory can transfer Naquadah from defender to attacker based on combat outcome.',
            'Anti-covert detachments may engage enemy covert forces during combat phases.',
        ],
        'operations:raid' => [
            'Raids focus on stealing untrained units instead of Naquadah.',
            'Raid missions are high tempo operations with elevated retaliation risk when overused.',
            'Repeated short-cycle raids should be monitored to avoid overextension and diplomatic blowback.',
        ],
        'operations:spy' => [
            'Spy missions consume covert turns, not regular attack turns.',
            'Failure can cost covert agents; success reveals enemy military and economy indicators.',
            'Covert vs anti-covert balance heavily influences reconnaissance reliability.',
        ],
        'operations:logs' => [
            'Logs are a tactical feedback loop for force composition and target quality.',
            'Reviewing losses by mission type helps adjust training and equipment priorities.',
            'Short debrief loops improve campaign consistency over long wars.',
        ],
        'military:fleet' => [
            'Mothership fleets support planet-oriented operations and offensive projection.',
            'Fleet strength can support attacks but does not act as home-planet defense in the same way.',
            'Fleet repair and bay investment should be planned with campaign timing.',
        ],
        'empire:planets' => [
            'Planet conquest commonly uses full mission expenditure and favors strong fleet posture.',
            'Planet acquisition is generally cadence-limited and should be timed around war objectives.',
            'Planet bonuses should be mapped to economy and military specialization plans.',
        ],
        'economy:banking' => [
            'Naquadah is the central purchase currency for units, equipment, and upgrades.',
            'Maintaining split reserves (on-hand and banked) improves shock resistance.',
            'Economic discipline sets the tempo for sustained military operations.',
        ],
        'economy:market' => [
            'Market turns and broker systems convert resources into strategic flexibility.',
            'Trade timing can materially change effective growth rates.',
            'Overtrading for one dimension can leave military or covert gaps.',
        ],
        'diplomacy:relations' => [
            'Relation stance affects war risk, coalition behavior, and target pressure.',
            'Stable stance policy across alliance members improves deterrence.',
            'Repeated actions against the same realm can trigger escalating political response.',
        ],
        'diplomacy:commander' => [
            'Commander chains shape protection structure and economic support flow.',
            'Support transfers should follow command objectives and risk posture.',
            'Leadership churn can reduce alliance execution quality.',
        ],
        'diplomacy:governance' => [
            'Governance systems tune command doctrine, policy cadence, and strategic automation layers.',
            'Enable systems that match current doctrine while avoiding over-specialization in one branch.',
            'Commander settings should align with war, economy, and expansion priorities each cycle.',
        ],
        'help:mechanics' => [
            'Covert sabotage doctrine often accepts lower losses on success and higher losses on failure.',
            'Mothership progression includes high-cost entry, bay expansion, and weapon specialization.',
            'Effective macro play balances production growth, intel quality, and turn efficiency.',
        ],
        'universe:galaxies' => [
            'Universe is divided into OGame-style galaxies, systems, and orbital positions for expansion routing.',
            'Each world has a biome profile, habitability score, and distinct resource distribution.',
            'OGame-style growth favors staggered colonies across multiple galaxy lanes to reduce bottlenecks.',
        ],
        'universe:planets' => [
            'Moon presence improves surveillance coverage and tactical deployment windows.',
            'Planet biomes influence long-run mining bias and defensive architecture planning.',
            'Colonization slots should be prioritized for high-habitability worlds with stable debris income nearby.',
        ],
        'universe:objects' => [
            'Debris fields support recycler-style recovery loops for metal and crystal reconstruction.',
            'Nebula and wormhole zones increase expedition variance and scouting risk.',
            'Ancient ruins provide high-variance anomaly opportunities for advanced empires.',
        ],
        'universe:expedition' => [
            'Expeditions are fleet-timed probes with outcome variance tied to mission scale and support posture.',
            'Colonization cadence should match economy reserve and defensive readiness.',
            'Rapid multi-wave expansion increases reach but can weaken local defense if staged too aggressively.',
        ],
    ];

    $key = $main . ':' . $sub;
    $context = $byPage[$key] ?? [
        'This page participates in the 30-minute turn economy and shared resource model.',
        'Actions here should be sequenced with current action-turn, covert-turn, and Naquadah budget.',
        'Use this panel with logs and rankings to continually adjust doctrine.',
    ];

    echo '<div class="card full"><h4>Deep Mechanics Matrix</h4><ul>';
    foreach ($core as $line) {
        echo '<li>' . h($line) . '</li>';
    }
    echo '</ul></div>';

    echo '<div class="card"><h4>Race Meta Effects</h4><ul>';
    foreach ($race as $line) {
        echo '<li>' . h($line) . '</li>';
    }
    echo '</ul></div>';

    echo '<div class="card"><h4>Page-Specific Rules</h4><ul>';
    foreach ($context as $line) {
        echo '<li>' . h($line) . '</li>';
    }
    echo '</ul></div>';
}

function renderInteractiveCalculators(string $main, string $sub, $baseData, $personnel, $bank): void {
    if (($main === 'operations' && ($sub === 'attack' || $sub === 'raid')) || ($main === 'help' && $sub === 'mechanics')) {
        echo '<div class="card full">';
        echo '<h4>Battle Outcome Estimator</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Attack Power<input id="calcAtkPower" type="number" min="0" value="' . h((int)($personnel->attackCount ?? 0)) . '"></label>';
        echo '<label>Defense Power<input id="calcDefPower" type="number" min="0" value="' . h((int)($personnel->defenseCount ?? 0)) . '"></label>';
        echo '<label>Attack Tech %<input id="calcAtkTech" type="number" min="0" value="12"></label>';
        echo '<label>Defense Tech %<input id="calcDefTech" type="number" min="0" value="12"></label>';
        echo '<label>Fleet Strength<input id="calcFleet" type="number" min="0" value="0"></label>';
        echo '<label>Shield/Planet Defense<input id="calcShield" type="number" min="0" value="0"></label>';
        echo '<label>Turns Committed<input id="calcTurns" type="number" min="1" max="15" value="10"></label>';
        echo '<label>Target Naquadah Pool<input id="calcNaqPool" type="number" min="0" value="1000000"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var atk=Math.max(0,parseFloat(document.getElementById(\'calcAtkPower\').value)||0);var def=Math.max(0,parseFloat(document.getElementById(\'calcDefPower\').value)||0);var atkTech=Math.max(0,parseFloat(document.getElementById(\'calcAtkTech\').value)||0);var defTech=Math.max(0,parseFloat(document.getElementById(\'calcDefTech\').value)||0);var fleet=Math.max(0,parseFloat(document.getElementById(\'calcFleet\').value)||0);var shield=Math.max(0,parseFloat(document.getElementById(\'calcShield\').value)||0);var turns=Math.min(15,Math.max(1,parseFloat(document.getElementById(\'calcTurns\').value)||1));var naqPool=Math.max(0,parseFloat(document.getElementById(\'calcNaqPool\').value)||0);var atkScore=(atk*(1+atkTech/100))+fleet*0.35;var defScore=(def*(1+defTech/100))+shield*0.25;var ratio=atkScore/Math.max(defScore,1);var winChance=Math.max(5,Math.min(95,50+((ratio-1)*35)));var lootPct=Math.max(0.01,Math.min(0.25,0.03+Math.max(0,ratio-1)*0.1));var estLoot=naqPool*lootPct*(turns/15);document.getElementById(\'calcBattleOut\').innerHTML=\'Attack Score: \'+Math.round(atkScore).toLocaleString()+\' | Defense Score: \'+Math.round(defScore).toLocaleString()+\'<br>Win Chance: \'+winChance.toFixed(1)+\'% | Est. Naquadah Gain: \'+Math.round(estLoot).toLocaleString()+\'\';})();">Estimate Battle</button>';
        echo '<div id="calcBattleOut" class="calc-output">Adjust values and run estimate.</div>';
        echo '</div>';
    }

    if (($main === 'operations' && $sub === 'spy') || ($main === 'help' && $sub === 'mechanics')) {
        echo '<div class="card full">';
        echo '<h4>Covert Mission Estimator</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Spies Sent<input id="calcSpySent" type="number" min="1" value="10000"></label>';
        echo '<label>Your Covert Tech %<input id="calcCovertTech" type="number" min="0" value="10"></label>';
        echo '<label>Enemy Anti-Covert Units<input id="calcEnemyAnti" type="number" min="0" value="8000"></label>';
        echo '<label>Enemy Anti-Covert Tech %<input id="calcEnemyAntiTech" type="number" min="0" value="10"></label>';
        echo '<label>Covert Turns Used<input id="calcCt" type="number" min="1" max="15" value="5"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var spies=Math.max(1,parseFloat(document.getElementById(\'calcSpySent\').value)||1);var cTech=Math.max(0,parseFloat(document.getElementById(\'calcCovertTech\').value)||0);var enemy=Math.max(0,parseFloat(document.getElementById(\'calcEnemyAnti\').value)||0);var eTech=Math.max(0,parseFloat(document.getElementById(\'calcEnemyAntiTech\').value)||0);var ct=Math.min(15,Math.max(1,parseFloat(document.getElementById(\'calcCt\').value)||1));var covertPower=spies*(1+cTech/100)*Math.sqrt(ct/5);var antiPower=enemy*(1+eTech/100);var success=Math.max(2,Math.min(98,50+((covertPower-antiPower)/Math.max(antiPower,1))*40));var successLoss=spies*0.05;var failLoss=spies*0.50;var expectedLoss=(success/100)*successLoss+((100-success)/100)*failLoss;document.getElementById(\'calcCovertOut\').innerHTML=\'Success Chance: \'+success.toFixed(1)+\'%<br>Expected Spy Loss: \'+Math.round(expectedLoss).toLocaleString()+\' (Success ~5%, Failure ~50%)\';})();">Estimate Covert Mission</button>';
        echo '<div id="calcCovertOut" class="calc-output">Model includes SGW-style high failure penalties for covert actions.</div>';
        echo '</div>';
    }

    if (($main === 'economy' && ($sub === 'banking' || $sub === 'market' || $sub === 'production')) || ($main === 'help' && $sub === 'mechanics') || ($main === 'empire' && ($sub === 'home' || $sub === 'overview'))) {
        echo '<div class="card full">';
        echo '<h4>Turn Economy Planner</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Current On-Hand Naquadah<input id="calcCurrNaq" type="number" min="0" value="' . h((int)($bank->onHand ?? 0)) . '"></label>';
        echo '<label>Income Per Turn<input id="calcIncomeTurn" type="number" min="0" value="' . h((int)($baseData->income ?? 0)) . '"></label>';
        echo '<label>Current Untrained Units<input id="calcCurrUu" type="number" min="0" value="' . h((int)($personnel->uuCount ?? 0)) . '"></label>';
        echo '<label>Unit Production / Turn<input id="calcUpTurn" type="number" min="0" value="' . h((int)($baseData->up ?? 0)) . '"></label>';
        echo '<label>Planning Horizon (turns)<input id="calcHorizon" type="number" min="1" max="200" value="24"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var naq=Math.max(0,parseFloat(document.getElementById(\'calcCurrNaq\').value)||0);var income=Math.max(0,parseFloat(document.getElementById(\'calcIncomeTurn\').value)||0);var uu=Math.max(0,parseFloat(document.getElementById(\'calcCurrUu\').value)||0);var up=Math.max(0,parseFloat(document.getElementById(\'calcUpTurn\').value)||0);var horizon=Math.min(200,Math.max(1,parseFloat(document.getElementById(\'calcHorizon\').value)||1));var projNaq=naq+(income*horizon);var projUu=uu+(up*horizon);var attackBudget=Math.floor(projNaq*0.35);var techBudget=Math.floor(projNaq*0.25);var reserveBudget=Math.floor(projNaq*0.20);document.getElementById(\'calcEcoOut\').innerHTML=\'Projected Naquadah: \'+Math.round(projNaq).toLocaleString()+\' | Projected UU: \'+Math.round(projUu).toLocaleString()+\'<br>Suggested Split -> Military: \'+attackBudget.toLocaleString()+\', Technology: \'+techBudget.toLocaleString()+\', Reserve: \'+reserveBudget.toLocaleString()+\'\';})();">Project Economy</button>';
        echo '<div id="calcEcoOut" class="calc-output">Use this to simulate turn-based growth and budget splits.</div>';
        echo '</div>';
    }
}

function renderFeatureWorkbenches(string $main, string $sub, $baseData, $personnel, $bank, $userStats, array $planets): void {
    if (($main === 'operations' && $sub === 'raid') || ($main === 'help' && $sub === 'mechanics')) {
        echo '<div class="card full">';
        echo '<h4>Raid Yield Planner</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Enemy Untrained Units<input id="raidEnemyUu" type="number" min="0" value="120000"></label>';
        echo '<label>Raid Power<input id="raidPower" type="number" min="0" value="' . h((int)($personnel->attackCount ?? 0)) . '"></label>';
        echo '<label>Enemy Defense Power<input id="raidEnemyDef" type="number" min="0" value="90000"></label>';
        echo '<label>Turns Committed<input id="raidTurns" type="number" min="1" max="15" value="8"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var enemyUU=Math.max(0,parseFloat(document.getElementById(\'raidEnemyUu\').value)||0);var rp=Math.max(0,parseFloat(document.getElementById(\'raidPower\').value)||0);var ed=Math.max(1,parseFloat(document.getElementById(\'raidEnemyDef\').value)||1);var turns=Math.min(15,Math.max(1,parseFloat(document.getElementById(\'raidTurns\').value)||1));var ratio=rp/ed;var success=Math.max(5,Math.min(95,45+((ratio-1)*40)));var stealPct=Math.max(0.01,Math.min(0.18,0.02+Math.max(0,ratio-1)*0.06));var estSteal=enemyUU*stealPct*(turns/15);var retaliation=Math.max(5,Math.min(95,55-((ratio-1)*20)+(turns*1.2)));document.getElementById(\'raidOut\').innerHTML=\'Raid Success Chance: \'+success.toFixed(1)+\'%<br>Estimated UU Captured: \'+Math.round(estSteal).toLocaleString()+\'<br>Retaliation Pressure Index: \'+retaliation.toFixed(1)+\'%\';})();">Estimate Raid</button>';
        echo '<div id="raidOut" class="calc-output">Use this planner to balance raid yield versus retaliation pressure.</div>';
        echo '</div>';
    }

    if (($main === 'operations' && $sub === 'spy') || ($main === 'intel' && $sub === 'reports') || ($main === 'help' && $sub === 'mechanics')) {
        echo '<div class="card full">';
        echo '<h4>Sabotage Impact Planner</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Spies Sent<input id="sabSpies" type="number" min="1" value="15000"></label>';
        echo '<label>Your Covert Tech %<input id="sabTech" type="number" min="0" value="12"></label>';
        echo '<label>Enemy Anti-Covert Power<input id="sabEnemyAc" type="number" min="0" value="12000"></label>';
        echo '<label>Enemy Armory Size<input id="sabArmory" type="number" min="0" value="300000"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var spies=Math.max(1,parseFloat(document.getElementById(\'sabSpies\').value)||1);var tech=Math.max(0,parseFloat(document.getElementById(\'sabTech\').value)||0);var enemy=Math.max(1,parseFloat(document.getElementById(\'sabEnemyAc\').value)||1);var armory=Math.max(0,parseFloat(document.getElementById(\'sabArmory\').value)||0);var covert=spies*(1+tech/100);var success=Math.max(2,Math.min(98,50+((covert-enemy)/enemy)*38));var damagePct=Math.max(0.01,Math.min(0.22,0.03+Math.max(0,success-50)/220));var estDamage=armory*damagePct;var expectedLoss=(success/100)*(spies*0.05)+((100-success)/100)*(spies*0.50);document.getElementById(\'sabOut\').innerHTML=\'Success Chance: \'+success.toFixed(1)+\'%<br>Estimated Armory Damage Index: \'+Math.round(estDamage).toLocaleString()+\'<br>Expected Spy Loss: \'+Math.round(expectedLoss).toLocaleString()+\'\';})();">Estimate Sabotage</button>';
        echo '<div id="sabOut" class="calc-output">Model follows high-risk covert doctrine with larger failure losses.</div>';
        echo '</div>';
    }

    if (($main === 'military' && $sub === 'fleet') || ($main === 'empire' && $sub === 'planets') || ($main === 'help' && $sub === 'mechanics')) {
        echo '<div class="card full">';
        echo '<h4>Planet Conquest Planner</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Fleet Strength<input id="conqFleet" type="number" min="0" value="100000"></label>';
        echo '<label>Target Ground Defense<input id="conqDef" type="number" min="0" value="85000"></label>';
        echo '<label>Beacon Strength<input id="conqBeacon" type="number" min="0" value="100"></label>';
        echo '<label>Attempts Today<input id="conqAttempts" type="number" min="0" max="5" value="0"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var fleet=Math.max(0,parseFloat(document.getElementById(\'conqFleet\').value)||0);var def=Math.max(1,parseFloat(document.getElementById(\'conqDef\').value)||1);var beacon=Math.max(0,parseFloat(document.getElementById(\'conqBeacon\').value)||0);var attempts=Math.max(0,parseFloat(document.getElementById(\'conqAttempts\').value)||0);var ratio=(fleet*(1+(beacon/1000)))/def;var success=Math.max(1,Math.min(97,42+((ratio-1)*45)));var blocked=attempts>=1;var fleetLossPct=Math.max(0.03,Math.min(0.45,0.20-(ratio-1)*0.08));if(blocked){document.getElementById(\'conqOut\').innerHTML=\'Daily limit reached: plan next conquest cycle (24h cadence).\';return;}document.getElementById(\'conqOut\').innerHTML=\'Conquest Success Chance: \'+success.toFixed(1)+\'%<br>Estimated Fleet Risk on Failure: \'+Math.round(fleetLossPct*100)+\'%<br>Reminder: conquest attempts are cadence-limited.\';})();">Estimate Conquest</button>';
        echo '<div id="conqOut" class="calc-output">Plan conquest around fleet risk, beacon context, and daily cadence limits.</div>';
        echo '</div>';
    }

    if (($main === 'diplomacy' && ($sub === 'relations' || $sub === 'alliance')) || ($main === 'help' && $sub === 'support')) {
        echo '<div class="card full">';
        echo '<h4>Diplomacy Policy Engine</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Hits on Same Target (7d)<input id="dipHits" type="number" min="0" value="2"></label>';
        echo '<label>Raids on Same Target (7d)<input id="dipRaids" type="number" min="0" value="1"></label>';
        echo '<label>Alliance Tension Level (0-100)<input id="dipTension" type="number" min="0" max="100" value="35"></label>';
        echo '<label>Incoming Threat Index (0-100)<input id="dipThreat" type="number" min="0" max="100" value="40"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var hits=Math.max(0,parseFloat(document.getElementById(\'dipHits\').value)||0);var raids=Math.max(0,parseFloat(document.getElementById(\'dipRaids\').value)||0);var tension=Math.max(0,Math.min(100,parseFloat(document.getElementById(\'dipTension\').value)||0));var threat=Math.max(0,Math.min(100,parseFloat(document.getElementById(\'dipThreat\').value)||0));var pressure=(hits*10)+(raids*12)+(tension*0.35)+(threat*0.4);var tier=\'Stable\';if(pressure>=95){tier=\'High Escalation\';}else if(pressure>=65){tier=\'Tense\';}else if(pressure>=40){tier=\'Watch\';}var recommendation=(tier===\'High Escalation\')?\'Pause repeat hits, coordinate alliance posture, open direct channel.\':(tier===\'Tense\')?\'Shift to selective targets, diversify operations, document incidents.\':(tier===\'Watch\')?\'Maintain spacing discipline, monitor retaliation signals.\':\'Proceed with standard posture and periodic review.\';document.getElementById(\'dipOut\').innerHTML=\'Policy Tier: \'+tier+\'<br>Escalation Score: \'+pressure.toFixed(1)+\'<br>Recommendation: \'+recommendation;})();">Evaluate Policy</button>';
        echo '<div id="dipOut" class="calc-output">Use this engine to avoid over-farming patterns and unnecessary alliance escalation.</div>';
        echo '</div>';
    }

    if (($main === 'economy' && $sub === 'technology') || ($main === 'economy' && $sub === 'production') || ($main === 'help' && $sub === 'mechanics')) {
        echo '<div class="card full">';
        echo '<h4>Upgrade ROI Workbench</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Upgrade Cost (Naquadah)<input id="roiCost" type="number" min="0" value="500000"></label>';
        echo '<label>Extra Income / Turn<input id="roiIncome" type="number" min="0" value="15000"></label>';
        echo '<label>Extra UP / Turn<input id="roiUp" type="number" min="0" value="400"></label>';
        echo '<label>Horizon (turns)<input id="roiTurns" type="number" min="1" max="500" value="72"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var cost=Math.max(0,parseFloat(document.getElementById(\'roiCost\').value)||0);var inc=Math.max(0,parseFloat(document.getElementById(\'roiIncome\').value)||0);var up=Math.max(0,parseFloat(document.getElementById(\'roiUp\').value)||0);var turns=Math.max(1,Math.min(500,parseFloat(document.getElementById(\'roiTurns\').value)||1));var valuePerUp=120;var gross=(inc*turns)+(up*valuePerUp*turns);var net=gross-cost;var payback=cost/Math.max((inc+(up*valuePerUp)),1);var verdict=(net>0)?\'Positive ROI\':\'Negative ROI\';document.getElementById(\'roiOut\').innerHTML=\'Projected Gross Value: \'+Math.round(gross).toLocaleString()+\'<br>Projected Net Value: \'+Math.round(net).toLocaleString()+\'<br>Payback: \'+payback.toFixed(1)+\' turns | Verdict: \'+verdict;})();">Run ROI</button>';
        echo '<div id="roiOut" class="calc-output">Compare upgrades by payback time before committing strategic reserves.</div>';
        echo '</div>';
    }

    if (($main === 'universe' && $sub === 'objects') || ($main === 'universe' && $sub === 'expedition')) {
        echo '<div class="card full">';
        echo '<h4>Debris Recovery Estimator</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Debris Metal<input id="debrisMetal" type="number" min="0" value="450000"></label>';
        echo '<label>Debris Crystal<input id="debrisCrystal" type="number" min="0" value="320000"></label>';
        echo '<label>Recycler Capacity per Ship<input id="recyclerCap" type="number" min="1" value="20000"></label>';
        echo '<label>Travel Time (minutes)<input id="recyclerTime" type="number" min="1" value="18"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var m=Math.max(0,parseFloat(document.getElementById(\'debrisMetal\').value)||0);var c=Math.max(0,parseFloat(document.getElementById(\'debrisCrystal\').value)||0);var cap=Math.max(1,parseFloat(document.getElementById(\'recyclerCap\').value)||1);var t=Math.max(1,parseFloat(document.getElementById(\'recyclerTime\').value)||1);var total=m+c;var rec=Math.ceil(total/cap);var hourly=Math.round((60/t)*total);document.getElementById(\'debrisOut\').innerHTML=\'Total Debris: \'+Math.round(total).toLocaleString()+\' | Recyclers Needed: \'+rec.toLocaleString()+\'<br>Recovery Throughput/Hour: \'+hourly.toLocaleString()+\' resources\';})();">Estimate Recovery</button>';
        echo '<div id="debrisOut" class="calc-output">Compute recycler fleet size before dispatching recovery waves.</div>';
        echo '</div>';
    }

    if ($main === 'universe' && $sub === 'expedition') {
        echo '<div class="card full">';
        echo '<h4>Expedition Outcome Simulator</h4>';
        echo '<div class="calc-grid">';
        echo '<label>Fleet Value<input id="expFleetValue" type="number" min="1" value="650000"></label>';
        echo '<label>Escort Strength<input id="expEscort" type="number" min="0" value="120000"></label>';
        echo '<label>Astro Tech Level<input id="expAstro" type="number" min="0" value="6"></label>';
        echo '<label>Missions Today<input id="expMissions" type="number" min="0" max="20" value="3"></label>';
        echo '</div>';
        echo '<button class="calc-btn" type="button" onclick="(function(){var fv=Math.max(1,parseFloat(document.getElementById(\'expFleetValue\').value)||1);var es=Math.max(0,parseFloat(document.getElementById(\'expEscort\').value)||0);var astro=Math.max(0,parseFloat(document.getElementById(\'expAstro\').value)||0);var missions=Math.max(0,Math.min(20,parseFloat(document.getElementById(\'expMissions\').value)||0));var safeChance=Math.max(10,Math.min(96,58+(astro*2)+(es/Math.max(fv,1))*20-(missions*1.5)));var haul=Math.round((fv*0.05)+(astro*12000));var risk=Math.max(4,Math.min(80,35-(astro*1.4)+(missions*2)));document.getElementById(\'expOut\').innerHTML=\'Safe Return Chance: \'+safeChance.toFixed(1)+\'%<br>Estimated Resource Haul: \'+haul.toLocaleString()+\'<br>Incident Risk Index: \'+risk.toFixed(1)+\'%\';})();">Simulate Expedition</button>';
        echo '<div id="expOut" class="calc-output">Use this to pace daily expedition waves and avoid over-commitment.</div>';
        echo '</div>';
    }

    if ($main === 'help' && $sub === 'glossary') {
        echo '<div class="card full">';
        echo '<h4>Command Abbreviations Table</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Term</th><th align="left">Meaning</th></tr>';
        echo '<tr><td>AT</td><td>Attack Turns</td></tr>';
        echo '<tr><td>CT</td><td>Covert Turns</td></tr>';
        echo '<tr><td>MT</td><td>Market Turns</td></tr>';
        echo '<tr><td>UU</td><td>Untrained Units</td></tr>';
        echo '<tr><td>UP</td><td>Unit Production</td></tr>';
        echo '<tr><td>MS</td><td>Mothership</td></tr>';
        echo '<tr><td>TIP</td><td>Turn Income Produced</td></tr>';
        echo '<tr><td>RAL</td><td>Realm Alert Level</td></tr>';
        echo '</table>';
        echo '</div>';
    }

    if ($main === 'empire' && ($sub === 'home' || $sub === 'overview')) {
        $planetCount = count($planets);
        echo '<div class="card full">';
        echo '<h4>Empire Operations Board</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">System</th><th align="left">Current State</th><th align="left">Recommended Focus</th></tr>';
        echo '<tr><td>Economy</td><td>On Hand: ' . fnum($bank->onHand ?? 0) . '</td><td>Maintain reserve and push market optimization</td></tr>';
        echo '<tr><td>Military</td><td>Army: ' . fnum($userStats->armySize ?? 0) . '</td><td>Balance offense/defense and covert ratios</td></tr>';
        echo '<tr><td>Production</td><td>UP/Turn: ' . fnum($baseData->up ?? 0) . '</td><td>Prioritize high ROI upgrades</td></tr>';
        echo '<tr><td>Territory</td><td>Planets: ' . fnum($planetCount) . '</td><td>Schedule conquest by fleet readiness</td></tr>';
        echo '</table>';
        echo '</div>';
    }
}

function blueprintCatalog(): array {
    return [
        1 => ['name' => 'Raptor Interceptor', 'hull' => 'Interceptor', 'tier' => 1, 'copy_cost' => 75000, 'base_metal' => 4200, 'base_crystal' => 2400, 'base_deuterium' => 1200, 'base_turns' => 5, 'base_power' => 22],
        2 => ['name' => 'Valkyrie Frigate', 'hull' => 'Frigate', 'tier' => 2, 'copy_cost' => 145000, 'base_metal' => 9800, 'base_crystal' => 5600, 'base_deuterium' => 3400, 'base_turns' => 8, 'base_power' => 52],
        3 => ['name' => 'Argent Cruiser', 'hull' => 'Cruiser', 'tier' => 3, 'copy_cost' => 290000, 'base_metal' => 22000, 'base_crystal' => 13000, 'base_deuterium' => 8200, 'base_turns' => 12, 'base_power' => 124],
        4 => ['name' => 'Leviathan Battleship', 'hull' => 'Battleship', 'tier' => 4, 'copy_cost' => 520000, 'base_metal' => 46000, 'base_crystal' => 29000, 'base_deuterium' => 17000, 'base_turns' => 18, 'base_power' => 248],
        5 => ['name' => 'Aurora Carrier', 'hull' => 'Carrier', 'tier' => 5, 'copy_cost' => 880000, 'base_metal' => 78000, 'base_crystal' => 51000, 'base_deuterium' => 30000, 'base_turns' => 24, 'base_power' => 410],
        6 => ['name' => 'Aegis Dreadnought', 'hull' => 'Dreadnought', 'tier' => 6, 'copy_cost' => 1280000, 'base_metal' => 125000, 'base_crystal' => 86000, 'base_deuterium' => 52000, 'base_turns' => 32, 'base_power' => 680],
    ];
}

function blueprintEnsureTables(Game $s, array $catalog): void {
    foreach ($catalog as $id => $bp) {
        $id = (int)$id;
        $name = preg_replace('/[^A-Za-z0-9 _-]/', '', (string)$bp['name']);
        $hull = preg_replace('/[^A-Za-z0-9 _-]/', '', (string)$bp['hull']);
        $tier = (int)$bp['tier'];
        $copy = (int)$bp['copy_cost'];
        $m = (int)$bp['base_metal'];
        $c = (int)$bp['base_crystal'];
        $d = (int)$bp['base_deuterium'];
        $t = (int)$bp['base_turns'];
        $p = (int)$bp['base_power'];
        $s->query("REPLACE INTO blueprint_catalog (blueprint_id,bp_name,hull_class,bp_kind,target_key,tier,copy_cost,base_metal,base_crystal,base_deuterium,base_turns,base_power)
            VALUES (" . $id . ", '" . $name . "', '" . $hull . "', 'ship', '', " . $tier . ", " . $copy . ", " . $m . ", " . $c . ", " . $d . ", " . $t . ", " . $p . ")");
    }
}

function blueprintOrderCosts(array $bp, int $qty, int $me, int $te): array {
    $qty = max(1, $qty);
    $materialFactor = max(0.55, 1 - min(0.45, $me * 0.02));
    $timeFactor = max(0.45, 1 - min(0.55, $te * 0.03));
    $metal = (int)round(((int)$bp['base_metal'] * $qty) * $materialFactor);
    $crystal = (int)round(((int)$bp['base_crystal'] * $qty) * $materialFactor);
    $deuterium = (int)round(((int)$bp['base_deuterium'] * $qty) * $materialFactor);
    $turns = max(1, (int)ceil((((int)$bp['base_turns'] * $qty) * $timeFactor) / 6));
    $power = (int)$bp['base_power'] * $qty;
    return ['metal' => $metal, 'crystal' => $crystal, 'deuterium' => $deuterium, 'turns' => $turns, 'power' => $power];
}

function universeSeedSystem(int $uid, int $index): array {
    $seed = (($uid + 97) * 1259 + ($index * 4051)) & 0x7fffffff;
    $starClasses = ['Red Dwarf', 'Yellow Main Sequence', 'Blue Giant', 'White Dwarf', 'Neutron', 'Binary'];
    $biomes = ['Lush', 'Arid', 'Frozen', 'Volcanic', 'Toxic', 'Oceanic', 'Irradiated', 'Relic'];
    $hazards = ['Calm', 'Radiation Storms', 'Acid Rain', 'Electro Tempests', 'Cryo Squalls', 'Pirate Activity'];

    $star = universePick($seed, $starClasses);
    $biome = universePick($seed, $biomes);
    $hazard = universePick($seed, $hazards);
    $richness = universeRand($seed, 20, 98);
    $sentinel = universeRand($seed, 0, 5);
    $planets = universeRand($seed, 2, 12);
    $moons = universeRand($seed, 0, 18);
    $glyphA = strtoupper(dechex(universeRand($seed, 16, 255)));
    $glyphB = strtoupper(dechex(universeRand($seed, 16, 255)));
    $seedKey = 'NMS-' . $uid . '-' . $index . '-' . $glyphA . $glyphB;

    return [
        'index' => $index,
        'seedKey' => $seedKey,
        'star' => $star,
        'biome' => $biome,
        'hazard' => $hazard,
        'richness' => $richness,
        'sentinel' => $sentinel,
        'planets' => $planets,
        'moons' => $moons,
    ];
}

function universeSeedSlice(int $uid, int $page, int $perPage): array {
    $total = 50000;
    $page = max(1, $page);
    $perPage = max(10, min(100, $perPage));
    $maxPage = max(1, (int)ceil($total / $perPage));
    if ($page > $maxPage) {
        $page = $maxPage;
    }
    $start = (($page - 1) * $perPage) + 1;
    $end = min($total, $start + $perPage - 1);

    $rows = [];
    for ($i = $start; $i <= $end; $i++) {
        $rows[] = universeSeedSystem($uid, $i);
    }

    return ['rows' => $rows, 'page' => $page, 'perPage' => $perPage, 'maxPage' => $maxPage, 'start' => $start, 'end' => $end, 'total' => $total];
}

function pageSafeToken(string $value): string {
    $clean = preg_replace('/[^A-Za-z0-9 _:\/-]/', '', $value);
    return str_replace("'", "''", $clean ?? '');
}

function militaryTroopCatalog(): array {
    $legions = ['Aegis', 'Vanguard', 'Tempest', 'Orion', 'Helios', 'Nyx', 'Atlas', 'Leviathan', 'Draco', 'Sentinel', 'Obsidian', 'Solaris'];
    $roles = [
        ['rank' => 'Cadet', 'title' => 'Initiate', 'class' => 'Line', 'subclass' => 'Infantry', 'type' => 'Assault', 'subtype' => 'Rifle', 'a1' => 'Resolve', 'a2' => 'Awareness'],
        ['rank' => 'Specialist', 'title' => 'Pathfinder', 'class' => 'Line', 'subclass' => 'Infantry', 'type' => 'Recon', 'subtype' => 'Scout', 'a1' => 'Awareness', 'a2' => 'Mobility'],
        ['rank' => 'Corporal', 'title' => 'Breacher', 'class' => 'Line', 'subclass' => 'Shock', 'type' => 'Assault', 'subtype' => 'Breach', 'a1' => 'Breach', 'a2' => 'Resolve'],
        ['rank' => 'Sergeant', 'title' => 'Shieldbearer', 'class' => 'Line', 'subclass' => 'Defender', 'type' => 'Bulwark', 'subtype' => 'Aegis', 'a1' => 'Fortitude', 'a2' => 'Discipline'],
        ['rank' => 'Staff Sergeant', 'title' => 'Siege Marshal', 'class' => 'Heavy', 'subclass' => 'Artillery', 'type' => 'Siege', 'subtype' => 'Plasma', 'a1' => 'Barrage', 'a2' => 'Control'],
        ['rank' => 'Gunnery Sergeant', 'title' => 'Bastion Gunner', 'class' => 'Heavy', 'subclass' => 'Artillery', 'type' => 'Support', 'subtype' => 'Suppressor', 'a1' => 'Control', 'a2' => 'Discipline'],
        ['rank' => 'Lieutenant', 'title' => 'Field Commander', 'class' => 'Command', 'subclass' => 'Tactics', 'type' => 'Leadership', 'subtype' => 'Battleline', 'a1' => 'Command', 'a2' => 'Tactics'],
        ['rank' => 'Captain', 'title' => 'Strike Captain', 'class' => 'Command', 'subclass' => 'Tactics', 'type' => 'Assault', 'subtype' => 'Spearhead', 'a1' => 'Command', 'a2' => 'Breach'],
        ['rank' => 'Major', 'title' => 'Vanguard Major', 'class' => 'Command', 'subclass' => 'Doctrine', 'type' => 'Coordination', 'subtype' => 'Joint Ops', 'a1' => 'Doctrine', 'a2' => 'Discipline'],
        ['rank' => 'Commander', 'title' => 'Wing Commander', 'class' => 'Aerospace', 'subclass' => 'Interdiction', 'type' => 'Air Superiority', 'subtype' => 'Interceptor', 'a1' => 'Mobility', 'a2' => 'Control'],
        ['rank' => 'Commodore', 'title' => 'Orbital Overseer', 'class' => 'Aerospace', 'subclass' => 'Orbital', 'type' => 'Support', 'subtype' => 'Orbital Fire', 'a1' => 'Command', 'a2' => 'Barrage'],
        ['rank' => 'Colonel', 'title' => 'Task Colonel', 'class' => 'Heavy', 'subclass' => 'Armor', 'type' => 'Shock', 'subtype' => 'Breaker', 'a1' => 'Fortitude', 'a2' => 'Breach'],
        ['rank' => 'Brigadier', 'title' => 'Doctrine Brigadier', 'class' => 'Command', 'subclass' => 'Doctrine', 'type' => 'Planning', 'subtype' => 'War Room', 'a1' => 'Doctrine', 'a2' => 'Command'],
        ['rank' => 'General', 'title' => 'Battle General', 'class' => 'Command', 'subclass' => 'High Command', 'type' => 'Leadership', 'subtype' => 'Theater', 'a1' => 'Command', 'a2' => 'Resolve'],
        ['rank' => 'Marshal', 'title' => 'Front Marshal', 'class' => 'Command', 'subclass' => 'High Command', 'type' => 'Leadership', 'subtype' => 'Grand Strategy', 'a1' => 'Doctrine', 'a2' => 'Discipline'],
        ['rank' => 'Shadow Operative', 'title' => 'Ghost Lance', 'class' => 'Covert', 'subclass' => 'Infiltration', 'type' => 'Stealth', 'subtype' => 'Ghost', 'a1' => 'Stealth', 'a2' => 'Awareness'],
        ['rank' => 'Phantom Operative', 'title' => 'Nightblade', 'class' => 'Covert', 'subclass' => 'Sabotage', 'type' => 'Disruption', 'subtype' => 'Saboteur', 'a1' => 'Stealth', 'a2' => 'Breach'],
        ['rank' => 'Counter Agent', 'title' => 'Signal Warden', 'class' => 'Security', 'subclass' => 'Counterintel', 'type' => 'Counter-Covert', 'subtype' => 'Interceptor', 'a1' => 'Awareness', 'a2' => 'Control'],
        ['rank' => 'Warden', 'title' => 'Citadel Warden', 'class' => 'Security', 'subclass' => 'Fortress', 'type' => 'Defense', 'subtype' => 'Sentinel', 'a1' => 'Fortitude', 'a2' => 'Discipline'],
        ['rank' => 'Ascendant', 'title' => 'Star Legate', 'class' => 'Elite', 'subclass' => 'Ascended', 'type' => 'Mythic', 'subtype' => 'Paragon', 'a1' => 'Resolve', 'a2' => 'Command'],
        ['rank' => 'Bombardier', 'title' => 'Siege Bombardier', 'class' => 'Heavy', 'subclass' => 'Artillery', 'type' => 'Siege', 'subtype' => 'Bombardment', 'a1' => 'Barrage', 'a2' => 'Breach'],
        ['rank' => 'Guardian', 'title' => 'Aegis Guardian', 'class' => 'Heavy', 'subclass' => 'Artillery', 'type' => 'Bulwark', 'subtype' => 'Guardian', 'a1' => 'Fortitude', 'a2' => 'Control'],
    ];

    $rows = [];
    $id = 1;
    foreach ($legions as $li => $legion) {
        foreach ($roles as $ri => $role) {
            $tier = $ri + 1;
            $power = (int)(80 + ($tier * 34) + ($li * 7));
            $attack = (int)round($power * (1.02 + (($ri % 3) * 0.08)));
            $defense = (int)round($power * (0.96 + (($ri % 4) * 0.07)));
            $covert = (int)round($power * (0.70 + (($ri % 5) * 0.06)));
            $anti = (int)round($power * (0.68 + (($ri % 6) * 0.05)));
            $mobility = max(24, (int)(170 - ($tier * 4) - ($li % 3)));
            $morale = (int)(52 + ($tier * 2) + ($li % 8));
            $logistics = (int)(42 + ($tier * 2) + ($li % 6));
            $tactics = (int)(30 + ($tier * 3) + ($li % 5));
            $resilience = (int)(34 + ($tier * 3) + ($li % 7));
            $discipline = (int)(38 + ($tier * 2) + ($li % 9));
            $subA = (int)(26 + ($tier * 2) + ($li % 4));
            $subB = (int)(24 + ($tier * 2) + (($li + $ri) % 6));
            $code = 'TRP-' . str_pad((string)$id, 3, '0', STR_PAD_LEFT);
            $name = $legion . ' ' . $role['title'];
            $title = $role['rank'] . ' of ' . $legion;

            $rows[] = [
                'troop_id' => $id,
                'troop_code' => $code,
                'troop_name' => $name,
                'troop_rank' => $role['rank'],
                'troop_title' => $title,
                'class_name' => $role['class'],
                'class_subclass' => $role['subclass'],
                'troop_type' => $role['type'],
                'troop_subtype' => $role['subtype'],
                'power_stat' => $power,
                'attack_stat' => $attack,
                'defense_stat' => $defense,
                'covert_stat' => $covert,
                'anti_covert_stat' => $anti,
                'mobility_stat' => $mobility,
                'morale_stat' => $morale,
                'logistics_stat' => $logistics,
                'tactic_substat' => $tactics,
                'resilience_substat' => $resilience,
                'discipline_substat' => $discipline,
                'attribute_primary' => $role['a1'],
                'attribute_secondary' => $role['a2'],
                'sub_attribute_a' => $subA,
                'sub_attribute_b' => $subB,
                'legion_name' => $legion,
                'tier' => $tier,
            ];
            $id++;
        }
    }

    return $rows;
}

function militaryTroopRoleField(array $troopMeta): string {
    $unitField = 'attack';
    $className = strtolower((string)$troopMeta['class_name']);
    $typeName = strtolower((string)$troopMeta['troop_type']);
    if ($className === 'covert') {
        $unitField = 'covert';
    }
    if ($className === 'security' || $typeName === 'counter-covert') {
        $unitField = 'anticovert';
    }
    if ($className === 'heavy' || $typeName === 'defense' || $typeName === 'bulwark') {
        $unitField = 'defense';
    }
    return $unitField;
}

function militaryRecruitCosts(array $troopMeta, int $qty): array {
    $qty = max(1, $qty);
    return [
        'turns' => max(1, (int)ceil($qty / 20)),
        'units' => $qty,
        'naq' => (int)round(((int)$troopMeta['power_stat'] * 120) * $qty),
        'food' => (int)round(((int)$troopMeta['morale_stat'] * 2) * $qty),
        'water' => (int)round(((int)$troopMeta['logistics_stat'] * 2) * $qty),
        'deuterium' => (int)round(((int)$troopMeta['mobility_stat'] * 4) * $qty),
    ];
}

function militaryRecruitApply(Game $s, int $uid, array $troopMeta, int $qty): string {
    $qty = max(1, min(500, $qty));
    $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
    $turns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
    $resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
    $res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'food' => 0, 'water' => 0, 'population' => 0];
    $unitQ = $s->query("SELECT untrained,attack,defense,covert,anticovert FROM units WHERE uid=" . $uid . " LIMIT 1");
    $unitsObj = $unitQ ? $unitQ->fetch_object() : (object)['untrained' => 0, 'attack' => 0, 'defense' => 0, 'covert' => 0, 'anticovert' => 0];
    $bankQ = $s->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
    $bankObj = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];

    $cost = militaryRecruitCosts($troopMeta, $qty);
    if ($turns < (int)$cost['turns']) {
        return 'Troop recruitment failed: insufficient action turns.';
    }
    if ((int)$unitsObj->untrained < (int)$cost['units']) {
        return 'Troop recruitment failed: insufficient untrained units.';
    }
    if ((int)$bankObj->onHand < (int)$cost['naq']) {
        return 'Troop recruitment failed: insufficient Naquadah.';
    }
    if ((int)$res->food < (int)$cost['food'] || (int)$res->water < (int)$cost['water'] || (int)$res->deuterium < (int)$cost['deuterium']) {
        return 'Troop recruitment failed: insufficient food/water/deuterium reserves.';
    }

    $unitField = militaryTroopRoleField($troopMeta);
    $xpGain = max(2, (int)ceil($qty / 10));
    $readinessGain = max(1, (int)ceil($qty / 80));

    $s->query("UPDATE bank SET onHand=onHand-" . (int)$cost['naq'] . " WHERE uid=" . $uid . " LIMIT 1");
    $s->query("UPDATE player_resources SET food=food-" . (int)$cost['food'] . ", water=water-" . (int)$cost['water'] . ", deuterium=deuterium-" . (int)$cost['deuterium'] . " WHERE uid=" . $uid . " LIMIT 1");
    $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . (int)$cost['turns'] . ") WHERE uid=" . $uid . " LIMIT 1");
    $s->query("UPDATE units SET untrained=untrained-" . (int)$cost['units'] . ", " . $unitField . "=" . $unitField . "+" . $qty . " WHERE uid=" . $uid . " LIMIT 1");
    $s->query("UPDATE military_command_state SET drill_xp=drill_xp+" . $xpGain . ", readiness_index=LEAST(100, readiness_index+" . $readinessGain . ") WHERE uid=" . $uid . " LIMIT 1");

    return 'Troop recruitment complete: ' . fnum($qty) . 'x ' . (string)$troopMeta['troop_name'] . ' assigned to ' . strtoupper($unitField) . ' corps.';
}

function militaryQueueProcessReady(Game $s, int $uid, array $troopById, int $limit = 25): array {
    $limit = max(1, min(100, $limit));
    $queueQ = $s->query("SELECT queue_id, troop_id, quantity, eta_seconds, priority_order, UNIX_TIMESTAMP(created_at) AS created_ts
        FROM military_troop_queue
        WHERE uid=" . $uid . " AND status='queued'
        ORDER BY priority_order ASC, queue_id ASC LIMIT " . $limit);
    $processed = 0;
    $failed = 0;
    $waiting = 0;

    if ($queueQ) {
        while ($qItem = $queueQ->fetch_object()) {
            $elapsed = max(0, time() - (int)$qItem->created_ts);
            if ($elapsed < (int)$qItem->eta_seconds) {
                $waiting++;
                continue;
            }
            $troopMeta = $troopById[(int)$qItem->troop_id] ?? null;
            if ($troopMeta === null) {
                $s->query("UPDATE military_troop_queue SET status='failed', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                $failed++;
                continue;
            }
            $applyResult = militaryRecruitApply($s, $uid, $troopMeta, (int)$qItem->quantity);
            if (strpos($applyResult, 'Troop recruitment complete:') === 0) {
                $s->query("UPDATE military_troop_queue SET status='done', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                $processed++;
            } else {
                $s->query("UPDATE military_troop_queue SET status='failed', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                $failed++;
            }
        }
    }

    return ['processed' => $processed, 'waiting' => $waiting, 'failed' => $failed];
}

function militaryQueueNormalizePriorities(Game $s, int $uid): void {
    $q = $s->query("SELECT queue_id FROM military_troop_queue WHERE uid=" . $uid . " AND status='queued' ORDER BY priority_order ASC, queue_id ASC");
    if (!$q) {
        return;
    }
    $prio = 1;
    while ($row = $q->fetch_object()) {
        $s->query("UPDATE military_troop_queue SET priority_order=" . $prio . " WHERE queue_id=" . (int)$row->queue_id . " AND uid=" . $uid . " LIMIT 1");
        $prio++;
    }
}

function operationsQueueNormalizePriorities(Game $s, int $uid): void {
    $q = $s->query("SELECT queue_id FROM operations_turn_queue WHERE uid=" . $uid . " AND status='queued' ORDER BY priority_order ASC, queue_id ASC");
    if (!$q) {
        return;
    }
    $prio = 1;
    while ($row = $q->fetch_object()) {
        $s->query("UPDATE operations_turn_queue SET priority_order=" . $prio . " WHERE queue_id=" . (int)$row->queue_id . " AND uid=" . $uid . " LIMIT 1");
        $prio++;
    }
}

function operationsApplyCycleAction(Game $s, int $uid, array $cfg): string {
    $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
    $turns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
    $resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
    $res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'food' => 0, 'water' => 0, 'population' => 0];
    $unitQ = $s->query("SELECT untrained,attack,defense,covert,anticovert FROM units WHERE uid=" . $uid . " LIMIT 1");
    $unitsObj = $unitQ ? $unitQ->fetch_object() : (object)['untrained' => 0, 'attack' => 0, 'defense' => 0, 'covert' => 0, 'anticovert' => 0];
    $bankQ = $s->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
    $bankObj = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];

    if ($turns < (int)$cfg['turn_cost']) {
        return 'RTS cycle failed: insufficient action turns.';
    }
    if ((int)$bankObj->onHand < (int)$cfg['naq_cost']) {
        return 'RTS cycle failed: insufficient Naquadah reserves.';
    }
    if ((int)$res->metal < (int)$cfg['metal_cost'] || (int)$res->crystal < (int)$cfg['crystal_cost'] || (int)$res->deuterium < (int)$cfg['deut_cost'] || (int)$res->food < (int)$cfg['food_cost'] || (int)$res->water < (int)$cfg['water_cost']) {
        return 'RTS cycle failed: insufficient strategic resources.';
    }
    if ((int)$unitsObj->untrained < (int)$cfg['need_untrained']) {
        return 'RTS cycle failed: insufficient reserve personnel.';
    }

    $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . (int)$cfg['turn_cost'] . ") WHERE uid=" . $uid . " LIMIT 1");
    $s->query("UPDATE bank SET onHand=onHand-" . (int)$cfg['naq_cost'] . " WHERE uid=" . $uid . " LIMIT 1");
    $s->query("UPDATE player_resources SET
        metal=metal-" . (int)$cfg['metal_cost'] . ",
        crystal=crystal-" . (int)$cfg['crystal_cost'] . ",
        deuterium=deuterium-" . (int)$cfg['deut_cost'] . ",
        food=food-" . (int)$cfg['food_cost'] . ",
        water=water-" . (int)$cfg['water_cost'] . "
        WHERE uid=" . $uid . " LIMIT 1");
    $s->query("UPDATE units SET
        untrained=GREATEST(0,untrained+" . (int)$cfg['untrained_delta'] . "),
        attack=GREATEST(0,attack+" . (int)$cfg['attack_delta'] . "),
        defense=GREATEST(0,defense+" . (int)$cfg['defense_delta'] . "),
        covert=GREATEST(0,covert+" . (int)$cfg['covert_delta'] . "),
        anticovert=GREATEST(0,anticovert+" . (int)$cfg['anticovert_delta'] . ")
        WHERE uid=" . $uid . " LIMIT 1");
    $s->query("UPDATE operations_rts_state SET
        command_xp=command_xp+" . (int)$cfg['xp_gain'] . ",
        cycle_index=cycle_index+1,
        frontline_pressure=LEAST(100,GREATEST(0,frontline_pressure+" . (int)$cfg['pressure_delta'] . ")),
        reserve_integrity=LEAST(100,GREATEST(0,reserve_integrity+" . (int)$cfg['reserve_delta'] . ")),
        morale_index=LEAST(100,GREATEST(0,morale_index+" . (int)$cfg['morale_delta'] . ")),
        last_cycle_at=NOW()
        WHERE uid=" . $uid . " LIMIT 1");

    return 'RTS cycle complete: ' . (string)$cfg['label'] . ' executed successfully.';
}

function operationsQueueProcessReady(Game $s, int $uid, array $opsCatalog, int $limit = 10): array {
    $limit = max(1, min(50, $limit));
    $queueQ = $s->query("SELECT queue_id, operation_code, eta_seconds, priority_order, UNIX_TIMESTAMP(created_at) AS created_ts
        FROM operations_turn_queue
        WHERE uid=" . $uid . " AND status='queued'
        ORDER BY priority_order ASC, queue_id ASC LIMIT " . $limit);
    $processed = 0;
    $failed = 0;
    $waiting = 0;

    if ($queueQ) {
        while ($qItem = $queueQ->fetch_object()) {
            $elapsed = max(0, time() - (int)$qItem->created_ts);
            if ($elapsed < (int)$qItem->eta_seconds) {
                $waiting++;
                continue;
            }
            $opCode = (string)$qItem->operation_code;
            if (!isset($opsCatalog[$opCode])) {
                $s->query("UPDATE operations_turn_queue SET status='failed', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                $failed++;
                continue;
            }
            $applyResult = operationsApplyCycleAction($s, $uid, $opsCatalog[$opCode]);
            if (strpos($applyResult, 'RTS cycle complete:') === 0) {
                $s->query("UPDATE operations_turn_queue SET status='done', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                $processed++;
            } else {
                $s->query("UPDATE operations_turn_queue SET status='failed', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                $failed++;
            }
        }
    }

    return ['processed' => $processed, 'waiting' => $waiting, 'failed' => $failed];
}

function powerGridTick(Game $s, int $uid): array {
    $stateQ = $s->query("SELECT grid_level,stability_index,storage_capacity,stored_energy,generation_boost,load_mode,blackout_risk,UNIX_TIMESTAMP(last_tick_at) AS last_tick_ts
        FROM power_grid_state WHERE uid=" . $uid . " LIMIT 1");
    $state = $stateQ ? $stateQ->fetch_object() : null;
    if (!$state) {
        return ['generation' => 0, 'load' => 0, 'net' => 0, 'ticks' => 0];
    }

    $nodeQ = $s->query("SELECT node_id,node_name,node_type,level,output_mw,load_mw,integrity,status FROM power_grid_nodes WHERE uid=" . $uid . "");
    $generation = 0;
    $load = 0;
    $boost = (int)$state->generation_boost;
    $techLevelQ = $s->query("SELECT level FROM stargate_tech_levels WHERE uid=" . $uid . " AND tech_key='arkknit_endfield_power' LIMIT 1");
    $arkknitLevel = 0;
    if ($techLevelQ && $techLevelQ->num_rows > 0) {
        $arkknitLevel = (int)($techLevelQ->fetch_object()->level ?? 0);
    }
    $endfield = $arkknitLevel > 0 ? formalArknitEndfieldPower($arkknitLevel, 100, (int)$state->stability_index, (int)$state->blackout_risk) : null;
    if ($nodeQ) {
        while ($node = $nodeQ->fetch_object()) {
            if ((string)$node->status !== 'active') {
                continue;
            }
            $generation += formalPowerNodeOutput((float)$node->output_mw, (int)$node->level, (int)$node->integrity, $boost, (string)$node->node_type);
            $load += formalPowerNodeLoad((float)$node->load_mw, (int)$node->integrity, (string)$state->load_mode);
        }
    }

    $boostedGen = $generation;
    if ($endfield) {
        $boostedGen = (int)round($boostedGen * (1 + ($endfield['generation'] / 100.0)));
    }
    $net = $boostedGen - $load;

    $lastTickTs = (int)($state->last_tick_ts ?? 0);
    $nowTs = time();
    $intervalSec = 300;
    $ticks = 0;
    if ($lastTickTs > 0) {
        $ticks = max(0, min(48, (int)floor(($nowTs - $lastTickTs) / $intervalSec)));
    }

    if ($ticks > 0) {
        $storedEnergy = (int)$state->stored_energy;
        $storageCap = max(10000, (int)$state->storage_capacity);
        $stability = (int)$state->stability_index;
        $risk = (int)$state->blackout_risk;

        $delta = formalPowerGridDelta($net, $ticks, 8.0);
        $stateUpdate = formalPowerGridState($stability, $risk, $storedEnergy, $storageCap, $ticks, $delta);
        $storedEnergy = (int)$stateUpdate['stored_energy'];
        $stability = (int)$stateUpdate['stability_index'];
        $risk = (int)$stateUpdate['blackout_risk'];

        $s->query("UPDATE power_grid_state SET
            stored_energy=" . $storedEnergy . ",
            stability_index=" . $stability . ",
            blackout_risk=" . $risk . ",
            last_tick_at=FROM_UNIXTIME(" . $nowTs . ")
            WHERE uid=" . $uid . " LIMIT 1");
    }

    return ['generation' => $boostedGen, 'load' => $load, 'net' => $net, 'ticks' => $ticks];
}

function powerGridUpgradeNode(Game $s, int $uid, int $nodeId): string {
    if ($nodeId <= 0) {
        return 'Power grid upgrade failed: invalid node id.';
    }
    $nodeQ = $s->query("SELECT node_id,node_name,node_type,level,output_mw,load_mw,integrity,status FROM power_grid_nodes WHERE node_id=" . $nodeId . " AND uid=" . $uid . " LIMIT 1");
    $node = $nodeQ ? $nodeQ->fetch_object() : null;
    if (!$node) {
        return 'Power grid upgrade failed: node not found.';
    }

    $level = (int)$node->level;
    $turnCost = max(1, formalTimeValue(1, $level, 1.12));
    $naqCost = formalCostValue(18000, $level, 1.35, 0.08);
    $metalCost = formalCostValue(12000, $level, 1.30, 0.09);
    $crystalCost = formalCostValue(7000, $level, 1.28, 0.08);
    $deutCost = formalCostValue(3200, $level, 1.26, 0.07);

    $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
    $turns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
    $resQ = $s->query("SELECT metal,crystal,deuterium FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
    $res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0];
    $bankQ = $s->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
    $bankObj = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];

    if ($turns < $turnCost) {
        return 'Power grid upgrade failed: insufficient action turns.';
    }
    if ((int)$bankObj->onHand < $naqCost) {
        return 'Power grid upgrade failed: insufficient Naquadah.';
    }
    if ((int)$res->metal < $metalCost || (int)$res->crystal < $crystalCost || (int)$res->deuterium < $deutCost) {
        return 'Power grid upgrade failed: insufficient metal/crystal/deuterium.';
    }

    $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . $turnCost . ") WHERE uid=" . $uid . " LIMIT 1");
    $s->query("UPDATE bank SET onHand=onHand-" . $naqCost . " WHERE uid=" . $uid . " LIMIT 1");
    $s->query("UPDATE player_resources SET metal=metal-" . $metalCost . ", crystal=crystal-" . $crystalCost . ", deuterium=deuterium-" . $deutCost . " WHERE uid=" . $uid . " LIMIT 1");

    $newLevel = $level + 1;
    $outputInc = ((string)$node->node_type === 'generator') ? formalPowerValue(26, $newLevel, 1.12) : (((string)$node->node_type === 'relay') ? formalPowerValue(8, $newLevel, 1.10) : 0);
    $loadInc = ((string)$node->node_type === 'storage') ? formalPowerValue(8, $newLevel, 1.06) : formalPowerValue(5, $newLevel, 1.05);
    $integrityInc = 3;

    $s->query("UPDATE power_grid_nodes SET
        level=" . $newLevel . ",
        output_mw=output_mw+" . $outputInc . ",
        load_mw=load_mw+" . $loadInc . ",
        integrity=LEAST(100,integrity+" . $integrityInc . ")
        WHERE node_id=" . $nodeId . " AND uid=" . $uid . " LIMIT 1");
    $s->query("UPDATE power_grid_state SET
        grid_level=LEAST(30,grid_level+1),
        storage_capacity=storage_capacity+" . (800 + ($newLevel * 90)) . ",
        stability_index=LEAST(100,stability_index+2),
        blackout_risk=GREATEST(0,blackout_risk-1)
        WHERE uid=" . $uid . " LIMIT 1");

    return 'Power grid node upgraded: ' . (string)$node->node_name . ' is now level ' . fnum($newLevel) . '.';
}

function powerGridQueueProcessReady(Game $s, int $uid, int $limit = 10): array {
    $limit = max(1, min(50, $limit));
    $queueQ = $s->query("SELECT queue_id,action_code,target_node_id,eta_seconds,UNIX_TIMESTAMP(created_at) AS created_ts
        FROM power_grid_queue
        WHERE uid=" . $uid . " AND status='queued'
        ORDER BY priority_order ASC, queue_id ASC LIMIT " . $limit);
    $processed = 0;
    $failed = 0;
    $waiting = 0;

    if ($queueQ) {
        while ($qItem = $queueQ->fetch_object()) {
            $elapsed = max(0, time() - (int)$qItem->created_ts);
            if ($elapsed < (int)$qItem->eta_seconds) {
                $waiting++;
                continue;
            }
            $result = '';
            if ((string)$qItem->action_code === 'upgrade_node') {
                $result = powerGridUpgradeNode($s, $uid, (int)$qItem->target_node_id);
            } else {
                $result = 'Power grid queue failed: unknown action code.';
            }
            if (strpos($result, 'Power grid node upgraded:') === 0) {
                $s->query("UPDATE power_grid_queue SET status='done', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                $processed++;
            } else {
                $s->query("UPDATE power_grid_queue SET status='failed', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                $failed++;
            }
        }
    }

    return ['processed' => $processed, 'waiting' => $waiting, 'failed' => $failed];
}

function powerGridQueueNormalizePriorities(Game $s, int $uid): void {
    $q = $s->query("SELECT queue_id FROM power_grid_queue WHERE uid=" . $uid . " AND status='queued' ORDER BY priority_order ASC, queue_id ASC");
    if (!$q) {
        return;
    }
    $prio = 1;
    while ($row = $q->fetch_object()) {
        $s->query("UPDATE power_grid_queue SET priority_order=" . $prio . " WHERE queue_id=" . (int)$row->queue_id . " AND uid=" . $uid . " LIMIT 1");
        $prio++;
    }
}

function universeStoryActs(): array {
    $actTitles = [
        1 => 'Ashes of the Homeworld',
        2 => 'Lanes of First Contact',
        3 => 'Echoes in the Nebula',
        4 => 'The Shattered Accord',
        5 => 'Warfront of Three Suns',
        6 => 'Signals from the Deep Gate',
        7 => 'Storm over the Starbases',
        8 => 'The Covert Constellation',
        9 => 'Ruin Keys of the Ancients',
        10 => 'Collapse at Event Horizon',
        11 => 'The Last Coalition',
        12 => 'Crown of the Infinite Map',
    ];
    $chapterLabels = [
        1 => 'Rising Threat',
        2 => 'Counteroffensive',
        3 => 'Resolution',
    ];

    $acts = [];
    foreach ($actTitles as $actNo => $actTitle) {
        $acts[$actNo] = [
            'title' => $actTitle,
            'chapters' => [
                1 => 'Chapter 1: ' . $chapterLabels[1],
                2 => 'Chapter 2: ' . $chapterLabels[2],
                3 => 'Chapter 3: ' . $chapterLabels[3],
            ],
        ];
    }
    return $acts;
}

$main = isset($_GET['id']) ? preg_replace('/[^a-z]/', '', strtolower((string)$_GET['id'])) : 'empire';
$sub = isset($_GET['atype']) ? preg_replace('/[^a-z]/', '', strtolower((string)$_GET['atype'])) : '';

$mainTitles = [
    'empire' => 'Empire Command',
    'military' => 'Military Directorate',
    'operations' => 'Operations Center',
    'economy' => 'Economic Network',
    'diplomacy' => 'Diplomacy Office',
    'intel' => 'Intelligence Bureau',
    'community' => 'Community & Updates',
    'help' => 'Guides & Help Desk',
    'universe' => 'Universe Observatory',
    'research' => 'Research Directorate',
];

$subDefaults = [
    'empire' => 'home',
    'military' => 'personnel',
    'operations' => 'attack',
    'economy' => 'banking',
    'diplomacy' => 'alliance',
    'intel' => 'rankings',
    'community' => 'forums',
    'help' => 'newplayer',
    'universe' => 'galaxies',
    'research' => 'tree',
];

$subLabels = [
    'empire' => ['home' => 'Home', 'overview' => 'Overview', 'planets' => 'Planets', 'command' => 'Command', 'progress' => 'Progression', 'logistics' => 'Logistics Hub', 'doctrine' => 'Doctrine Board'],
    'military' => ['personnel' => 'Personnel', 'troops' => 'Troop Catalog', 'armory' => 'Armory', 'artillery' => 'Artillery', 'training' => 'Training', 'fleet' => 'Fleet', 'navy' => 'Navy Ops', 'defensegrid' => 'Defense Grid'],
    'operations' => ['attack' => 'Attack', 'raid' => 'Raid', 'spy' => 'Spy', 'logs' => 'Combat Logs', 'commandqueue' => 'Command Queue', 'diplomacyops' => 'Diplomatic Ops', 'rts' => 'RTS Turn System'],
    'economy' => ['banking' => 'Banking', 'market' => 'Market', 'technology' => 'Technology', 'production' => 'Production', 'resources' => 'Resource Hub', 'buildings' => 'OGame Buildings', 'logistics' => 'Supply Logistics', 'treasury' => 'Treasury Policy', 'store' => 'In-Game Store', 'battlepass' => 'Battle Pass', 'seasonpass' => 'Season Pass'],
    'diplomacy' => ['alliance' => 'Alliance', 'relations' => 'Relations', 'messages' => 'Messages', 'commander' => 'Commander Chain', 'governance' => 'Commander Governance', 'treaties' => 'Treaties', 'councils' => 'Councils'],
    'intel' => ['rankings' => 'Rankings', 'reports' => 'Battle Reports', 'threats' => 'Threat Matrix', 'map' => 'Sector Map', 'signals' => 'Signal Watch', 'dossiers' => 'Target Dossiers'],
    'community' => ['forums' => 'Forums', 'updates' => 'Updates', 'contact' => 'Contact', 'faq' => 'FAQ', 'events' => 'Events', 'academy' => 'Academy'],
    'help' => ['newplayer' => 'New Player', 'mechanics' => 'Mechanics', 'glossary' => 'Glossary', 'support' => 'Support', 'troubleshooting' => 'Troubleshooting', 'hotkeys' => 'Quick Commands'],
    'universe' => ['galaxies' => 'Galaxies', 'planets' => 'Planets & Moons', 'objects' => 'Interstellar Objects', 'expedition' => 'Expedition', 'bases' => 'Stations & Bases', 'travel' => 'Jumpgate & Hyperspace', 'lanes' => 'Transit Lanes', 'anomalies' => 'Anomaly Index', 'seeds' => 'Universe Seeds', 'events' => 'Universe Events', 'worldboss' => 'World Boss', 'story' => 'Story Campaign'],
    'research' => ['tree' => 'Research Tree', 'techlib' => 'Technology Tree', 'infrastructure' => 'Tech Library Buildings', 'classes' => 'Class Library', 'talents' => 'Talent Library', 'stargate' => 'Empire Tech', 'projects' => 'Projects', 'labs' => 'Lab Network', 'blueprints' => 'Blueprint Systems'],
];

$systemDetails = [
    'empire' => [
        'home' => [
            'brief' => 'Primary empire home page with strategic status, subsystem links, and planning context.',
            'functions' => ['Review command-level KPIs at a glance', 'Route quickly to core empire systems', 'Track readiness trends before committing turns'],
            'features' => ['Operational status cards', 'Subsystem routing matrix', 'Readiness and reserve indicators'],
            'logic' => ['Values blend bank, resources, personnel, and planetary data', 'Readiness is derived from economy and force balance', 'Home page is tuned for rapid command decisions'],
        ],
        'overview' => [
            'brief' => 'Central empire dashboard showing economy, army growth, and strategic readiness.',
            'functions' => ['View economy and production snapshots', 'Open base, technology, and progress modules', 'Track current military scale'],
            'features' => ['Live stat panel', 'Quick action shortcuts', 'Command feed compatible'],
            'logic' => ['Income and production are turn-based values', 'Army size updates from unit tables', 'Treasury values pull from bank and hand balances'],
        ],
        'planets' => [
            'brief' => 'Planet registry for territory visibility and expansion planning.',
            'functions' => ['List discovered planets', 'Show size and bonus metadata', 'Support growth path planning'],
            'features' => ['Table view for ownership info', 'Safe empty-state messaging', 'Integrated with empire context'],
            'logic' => ['Planet rows are loaded per player id', 'No planets returns an informative fallback', 'Bonuses act as strategic specialization signals'],
        ],
        'command' => [
            'brief' => 'Displays command structure and leadership chain context.',
            'functions' => ['Show commander and rank context', 'Jump to diplomacy relations', 'Open alliance roster tools'],
            'features' => ['Leadership summary card', 'Diplomacy shortcuts', 'Alliance workflow links'],
            'logic' => ['Commander relationship affects coordination flow', 'Race and rank influence strategic role', 'Diplomacy actions are profile-driven'],
        ],
        'progress' => [
            'brief' => 'Progression panel for growth priorities and expansion sequencing.',
            'functions' => ['Open progress dashboard', 'Present upgrade priorities', 'Guide scaling decisions'],
            'features' => ['Priority list', 'Module deep-link', 'Growth strategy guidance'],
            'logic' => ['Higher UP increases military velocity', 'Planet capacity lifts macro growth ceiling', 'Economic stability supports sustained warfare'],
        ],
        'logistics' => [
            'brief' => 'Empire-wide supply network that routes resources between economy, war, and expansion programs.',
            'functions' => ['Route resources across command programs', 'Maintain a stable reserve floor', 'Balance war spend with growth investment'],
            'features' => ['Supply route map', 'Reserve floor indicators', 'War spend discipline guidance'],
            'logic' => ['Every action consumes from shared Naquadah and strategic reserves', 'Reserve floors protect against operational lock', 'Supply balance directly affects campaign tempo'],
        ],
        'doctrine' => [
            'brief' => 'Central board for war, economy, and intelligence posture alignment.',
            'functions' => ['Set synchronized command posture', 'Align war and economy priorities', 'Govern campaign risk tolerance'],
            'features' => ['Doctrine track table', 'Posture guidance', 'Risk governance reference'],
            'logic' => ['Doctrine should match current force and treasury state', 'Frequent doctrine shifts waste tempo', 'Risk posture gates how aggressively to commit turns'],
        ],
    ],
    'military' => [
        'personnel' => [
            'brief' => 'Military personnel composition and combat role distribution.',
            'functions' => ['Break down unit classes', 'Expose untrained reserve depth', 'Guide training allocation'],
            'features' => ['Role-by-role unit table', 'Readable totals', 'Linked to training decisions'],
            'logic' => ['Untrained units are conversion input', 'Role balance impacts attack/defense outcomes', 'Covert and anti-covert stats affect intel warfare'],
        ],
        'troops' => [
            'brief' => 'Expanded roster library with 240 troops, ranks, titles, classes, and combat metadata.',
            'functions' => ['Browse 240 troop identities', 'Filter by class and legion doctrine', 'Compare stats, sub-stats, and attributes'],
            'features' => ['Paged 240-row catalog', 'Class filters and quick doctrine links', 'Stats and sub-attribute matrix'],
            'logic' => ['Troop rows are deterministic and balanced by tier', 'Sub-stats scale by role and legion profile', 'Attribute pairings define tactical specialization lanes'],
        ],
        'armory' => [
            'brief' => 'Equipment readiness and force amplification center.',
            'functions' => ['Open armory controls', 'Tune loadout direction', 'Prepare for mission types'],
            'features' => ['Armory quick-link', 'Readiness briefing', 'Battle prep guidance'],
            'logic' => ['Equipment investment alters combat effectiveness', 'Balanced loadouts reduce tactical gaps', 'Repairs preserve long-term force value'],
        ],
        'training' => [
            'brief' => 'Unit conversion operations from reserve to specialized roles.',
            'functions' => ['Open train and untrain modules', 'Shift force composition', 'Adjust to campaign needs'],
            'features' => ['Dual workflow for train/untrain', 'Fast operational links', 'Role conversion guidance'],
            'logic' => ['Training spends reserves into active roles', 'Untraining restores flexibility at a tradeoff', 'Composition changes should follow mission forecasts'],
        ],
        'fleet' => [
            'brief' => 'Fleet mobility and deployment readiness control.',
            'functions' => ['Open fleet dock', 'Coordinate movement posture', 'Stage force projection'],
            'features' => ['Dock entry shortcut', 'Deployment guidance', 'Readiness framing'],
            'logic' => ['Fleet positioning influences reaction speed', 'Readiness windows affect mission timing', 'Sustained operations require economy support'],
        ],
        'navy' => [
            'brief' => 'Space navy board for cruiser, destroyer, and capital-ship task forces.',
            'functions' => ['Organize ship task forces', 'Route warship production toward fleet doctrine', 'Align navy posture with gate defense'],
            'features' => ['Ship task-force catalog', 'Warship role guidance', 'Capital-ship progression context'],
            'logic' => ['Capital ships amplify fleet power per action turn', 'Navy composition should counter expected opponent fleets', 'Sustainment cost grows with hull class'],
        ],
        'defensegrid' => [
            'brief' => 'Planetary and orbital defense line commander for static security.',
            'functions' => ['Budget defensive structure upgrades', 'Cover high-value planets and moons', 'Balance defense spend against invasion risk'],
            'features' => ['Defense line matrix', 'Coverage recommendation list', 'Invasion-risk budget guidance'],
            'logic' => ['Defense lines deter opportunistic raids', 'Over-defense starves offense and economy', 'Coverage should follow threat matrix pressure'],
        ],
    ],
    'operations' => [
        'attack' => [
            'brief' => 'Direct strike planning and hostile target engagement.',
            'functions' => ['Open target ranking list', 'Select enemy profiles', 'Initiate offensive planning'],
            'features' => ['Targeting jump-link', 'Mission overview', 'Engagement staging cues'],
            'logic' => ['Attacks consume action turns', 'Outcome quality depends on force matchups', 'Intel prior to strike reduces risk'],
        ],
        'raid' => [
            'brief' => 'Fast resource extraction missions against exposed opponents.',
            'functions' => ['Plan high-speed raids', 'Identify weaker logistics targets', 'Cycle opportunistic operations'],
            'features' => ['Raid doctrine guidance', 'Risk-reward framing', 'Quick mission context'],
            'logic' => ['Raids prioritize economy disruption', 'Repeated raids raise retaliation probability', 'Execution cadence must respect turn budget'],
        ],
        'spy' => [
            'brief' => 'Covert intelligence collection and pre-war reconnaissance.',
            'functions' => ['Open spy module', 'Gather enemy indicators', 'Validate strike assumptions'],
            'features' => ['Spy workflow shortcut', 'Recon brief', 'Counter-risk planning cues'],
            'logic' => ['Covert success depends on role strength', 'Anti-covert defenses reduce penetration', 'Intel quality drives mission confidence'],
        ],
        'logs' => [
            'brief' => 'Post-operation analysis and outcome review center.',
            'functions' => ['Open combat logs', 'Review mission outcomes', 'Refine strategic doctrine'],
            'features' => ['Action history access', 'Debrief framing', 'Feedback loop support'],
            'logic' => ['Historical outcomes reveal matchup patterns', 'Loss analysis informs retraining', 'Frequent review improves tactical consistency'],
        ],
        'rts' => [
            'brief' => 'Turn-based RTS command console for action-turn cycles, doctrine shifts, and queued operations.',
            'functions' => ['Queue recon, assault, fortify, logistics, and sabotage cycles', 'Run one or many cycles as ETAs complete', 'Adjust doctrine and tempo by campaign phase'],
            'features' => ['Persistent RTS command state', 'Priority queue with ETA and controls', 'Action-turn synchronized cycle execution'],
            'logic' => ['Every cycle spends action turns and strategic resources', 'Queue processing validates readiness before execution', 'State metrics track pressure, reserves, morale, and command XP'],
        ],
        'commandqueue' => [
            'brief' => 'Central queue authority that orders all pending build, research, and mission tasks.',
            'functions' => ['Review pending empire tasks', 'Reprioritize queue slots', 'Clear stuck or obsolete orders'],
            'features' => ['Task queue view', 'Priority reordering controls', 'ETC and resource preview'],
            'logic' => ['Queue slots are gated by builder capacity', 'Priority changes affect completion order only', 'Interrupted tasks return partial resources'],
        ],
        'diplomacyops' => [
            'brief' => 'Covert and political operations channel for influence, sanctions, and alliance signaling.',
            'functions' => ['Run political influence missions', 'Coordinate alliance signaling', 'Manage sanction and warning posture'],
            'features' => ['Influence mission brief', 'Sanction action list', 'Alliance signaling log'],
            'logic' => ['Political actions spend turns without direct combat', 'Visible postures shape rival response patterns', 'Influence missions compound with intel coverage'],
        ],
    ],
    'economy' => [
        'banking' => [
            'brief' => 'Treasury management for liquidity, safety, and war funding.',
            'functions' => ['Show on-hand and banked resources', 'Open bank module', 'Guide reserve strategy'],
            'features' => ['Dual-balance view', 'Direct bank access', 'Funding policy hints'],
            'logic' => ['On-hand funds support immediate actions', 'Banked funds protect longer-term reserves', 'Liquidity planning stabilizes campaign pacing'],
        ],
        'market' => [
            'brief' => 'Resource trade hub for economic optimization.',
            'functions' => ['Open market', 'Adjust resource mix', 'Capture trade opportunities'],
            'features' => ['Market shortcut', 'Trade operation context', 'Economy tuning support'],
            'logic' => ['Market timing affects purchasing power', 'Overextension can starve military spending', 'Balanced trading smooths growth volatility'],
        ],
        'technology' => [
            'brief' => 'Research and development for systemic empire scaling.',
            'functions' => ['Open technology tree', 'Prioritize upgrades', 'Improve combat and economy efficiency'],
            'features' => ['Tech module link', 'Upgrade planning overview', 'Cross-system growth context'],
            'logic' => ['Technology compounds over time', 'Research priorities should reflect strategy', 'Early economic tech often improves long-term tempo'],
        ],
        'production' => [
            'brief' => 'Production doctrine for army throughput and mining momentum.',
            'functions' => ['Advise UP investments', 'Balance miners and combat roles', 'Protect economic infrastructure'],
            'features' => ['Doctrine checklist', 'Scale-up guidance', 'Force-economy balance prompts'],
            'logic' => ['UP directly affects unit generation', 'Over-militarization can stall growth', 'Defensive coverage preserves production gains'],
        ],
        'resources' => [
            'brief' => 'OGame-style resource economy command for mining, sustainment, and population growth.',
            'functions' => ['Track 5 strategic resources', 'Upgrade production structures', 'Trade resources for tactical needs'],
            'features' => ['Resource stockpile view', 'Production rates by line', 'Structure level overview and controls'],
            'logic' => ['Resources tick on 30-minute cadence', 'Structure levels amplify resource rates', 'Food and water shortages reduce population'],
        ],
        'buildings' => [
            'brief' => 'Central OGame-style construction control for economy, facilities, lunar structures, and defense lines.',
            'functions' => ['Upgrade building catalog entries', 'Allocate strategic resources to infrastructure', 'Coordinate economy and military construction timing'],
            'features' => ['Category-based building matrix', 'Live level tracking and next-cost preview', 'Direct integration with Resource HQ, Fleet, and Hyperspace systems'],
            'logic' => ['Each building scales with tiered cost formulas', 'Energy supports advanced construction programs', 'Balanced building progression improves empire efficiency and survivability'],
        ],
        'logistics' => [
            'brief' => 'Supply line board for transport capacity, merchant convoys, and route security.',
            'functions' => ['Route bulk resource convoys', 'Protect trade lanes from raiding', 'Expand transport fleet capacity'],
            'features' => ['Convoy route planner', 'Lane security rating', 'Capacity and cost preview'],
            'logic' => ['Convoys move resources on fixed turn timers', 'Unguarded lanes invite interception', 'Larger fleets raise route throughput per turn'],
        ],
        'treasury' => [
            'brief' => 'Strategic reserve management for war chest, premium currency, and bonus payout tracking.',
            'functions' => ['Track Naquadah and premium balances', 'Set war-chest spending floors', 'Monitor bonus payouts and claim windows'],
            'features' => ['Balance ledger', 'Reserve floor controls', 'Payout claim tracker'],
            'logic' => ['War chest floors protect campaign funding', 'Premium balances convert to convenience, not power', 'Bonus windows reward timed construction spikes'],
        ],
        'store' => [
            'brief' => 'Contribution shop and premium catalog for cosmetic and convenience purchases.',
            'functions' => ['Browse premium offers', 'Review contribution reward tiers', 'Redeem cosmetic and convenience items'],
            'features' => ['Offer catalog', 'Contribution tier ladder', 'Item claim flow'],
            'logic' => ['Purchases are convenience-focused and power-neutral', 'Contribution tiers reward consistent support', 'Claims should be redeemed before season reset'],
        ],
        'battlepass' => [
            'brief' => 'Seasonal battle-pass track with activity rewards across operations and economy.',
            'functions' => ['Track pass level progress', 'Complete seasonal operation goals', 'Claim reward milestones'],
            'features' => ['Pass progress bar', 'Goal checklist', 'Milestone reward table'],
            'logic' => ['Pass XP flows from action-turn activity', 'Milestones unlock on fixed thresholds', 'Season end resets unclaimed progress'],
        ],
        'seasonpass' => [
            'brief' => 'Seasonal content roadmap for balance updates, campaigns, and event windows.',
            'functions' => ['Review current season schedule', 'Preview upcoming campaign themes', 'Plan around season reset timing'],
            'features' => ['Season timeline', 'Campaign theme previews', 'Reset countdown reference'],
            'logic' => ['Seasons rotate balance and content cadence', 'Plan upgrades around reset windows', 'Season themes shift optimal build priorities'],
        ],
    ],
    'diplomacy' => [
        'alliance' => [
            'brief' => 'Alliance coordination and bloc-level strategic organization.',
            'functions' => ['Open alliance roster', 'Coordinate member roles', 'Manage coalition focus'],
            'features' => ['Roster link', 'Coordination framing', 'Team strategy context'],
            'logic' => ['Alliance structure increases strategic reach', 'Role clarity reduces operational friction', 'Collective response deters opportunistic attacks'],
        ],
        'relations' => [
            'brief' => 'Inter-empire stance management for peace and conflict control.',
            'functions' => ['Set relation stance', 'Review profile-based options', 'Signal diplomatic intent'],
            'features' => ['Profile action shortcut', 'Stance guidance', 'Conflict-state awareness'],
            'logic' => ['Relations influence engagement probability', 'Hostile posture raises military pressure', 'Clear stance policy supports alliance coherence'],
        ],
        'messages' => [
            'brief' => 'Secure communication channel for diplomacy and operations.',
            'functions' => ['Open inbox', 'Coordinate operations', 'Exchange strategic updates'],
            'features' => ['Messaging link', 'Diplomatic communication scope', 'Operational syncing support'],
            'logic' => ['Fast communication improves response time', 'Message clarity reduces coordination errors', 'Thread discipline preserves audit context'],
        ],
        'commander' => [
            'brief' => 'Commander assignment and support-chain administration.',
            'functions' => ['Open commander tools', 'Manage parent chain context', 'Support command transfer workflows'],
            'features' => ['Commander shortcut', 'Chain visibility cues', 'Support-flow alignment'],
            'logic' => ['Command chain affects organizational flow', 'Support transfers should match hierarchy goals', 'Leadership stability improves campaign execution'],
        ],
        'governance' => [
            'brief' => 'OGame-style commander governance systems and policy options center.',
            'functions' => ['Manage 18 governance systems', 'Tune commander options/settings', 'Balance doctrine by campaign phase'],
            'features' => ['Governance module entry point', 'Settings and option profiles', 'Per-system visual icon matrix'],
            'logic' => ['Each governance system scales through level upgrades', 'Enabled/disabled systems alter effective strategic posture', 'Commander settings influence policy response cadence'],
        ],
        'treaties' => [
            'brief' => 'Treaty management for non-aggression, trade, and mutual defense agreements.',
            'functions' => ['Propose and review treaty drafts', 'Manage NAP and trade terms', 'Honor or break agreements with reputation impact'],
            'features' => ['Treaty status board', 'Term templates', 'Reputation consequence tracker'],
            'logic' => ['Treaties reduce raid risk with partners', 'Breaking terms costs reputation and invites retribution', 'Trade clauses smooth resource imbalances'],
        ],
        'councils' => [
            'brief' => 'Alliance council chamber for ranks, voting, and war-plan approval.',
            'functions' => ['Assign council ranks', 'Hold motion votes', 'Approve alliance war plans'],
            'features' => ['Council seat matrix', 'Motion and vote ledger', 'War-plan approval gate'],
            'logic' => ['Rank structure defines command authority', 'Votes gate large alliance decisions', 'War plans bundle targets into one approval cycle'],
        ],
    ],
    'intel' => [
        'rankings' => [
            'brief' => 'Global standings for threat assessment and target selection.',
            'functions' => ['Open rankings', 'Track rival growth', 'Discover trend changes'],
            'features' => ['Rank console link', 'Comparative visibility', 'Trend awareness'],
            'logic' => ['Rapid rank gain can indicate power spikes', 'Rank brackets can guide target difficulty', 'Ranking deltas inform risk posture'],
        ],
        'reports' => [
            'brief' => 'Mission report intelligence for operational quality control.',
            'functions' => ['Open action reports', 'Review losses and gains', 'Update mission tactics'],
            'features' => ['Report module link', 'Outcome-focused analysis flow', 'Decision feedback loop'],
            'logic' => ['Consistent report review improves efficiency', 'Loss patterns reveal composition issues', 'Action context supports tactical iteration'],
        ],
        'threats' => [
            'brief' => 'Threat matrix for hostile indicators and escalation risk.',
            'functions' => ['Surface key danger signals', 'Highlight hostile patterns', 'Guide defensive posture'],
            'features' => ['Risk checklist', 'Strategic warning panel', 'Escalation awareness'],
            'logic' => ['Repeated raid contact increases conflict probability', 'Hostile command chains can signal coalition pressure', 'Nearby growth spikes can shift regional balance'],
        ],
        'map' => [
            'brief' => 'Sector-level influence estimation using profile intelligence.',
            'functions' => ['Frame territory influence zones', 'Correlate race/rank/alliance data', 'Support expansion route planning'],
            'features' => ['Strategic mapping brief', 'Influence modeling hints', 'Expansion planning context'],
            'logic' => ['Influence follows power and alliance concentration', 'Regional pressure informs defensive placements', 'Map intelligence should be updated from fresh scans'],
        ],
        'signals' => [
            'brief' => 'Signal intercept board for faction transmissions, fleet movement whispers, and anomaly pings.',
            'functions' => ['Triage intercepted transmissions', 'Correlate signals with known operations', 'Escalate high-confidence threats'],
            'features' => ['Signal feed with confidence tags', 'Correlation table', 'Escalation queue'],
            'logic' => ['Signal confidence rises with intel level', 'Ambiguous signals cost turns to verify', 'Escalated signals feed the threat matrix'],
        ],
        'dossiers' => [
            'brief' => 'Enemy commander dossier library built from reports, scans, and history.',
            'functions' => ['Open target dossiers', 'Track build and defense patterns', 'Time follow-up strikes to response cycles'],
            'features' => ['Dossier index', 'Pattern history view', 'Strike-timing hints'],
            'logic' => ['Dossiers aggregate repeated report data', 'Pattern shifts signal doctrine changes', 'Fresh data beats stale intel every time'],
        ],
    ],
    'community' => [
        'forums' => [
            'brief' => 'Community collaboration space for strategy and social coordination.',
            'functions' => ['Open forum portal', 'Join public discussions', 'Share strategic insights'],
            'features' => ['External forum link', 'Community visibility', 'Knowledge exchange channel'],
            'logic' => ['Shared intelligence can improve alliance performance', 'Public posts can reveal intent if overexposed', 'Community participation strengthens retention'],
        ],
        'updates' => [
            'brief' => 'Patch and balance awareness panel for meta adaptation.',
            'functions' => ['Open updates/faq', 'Read change notes', 'Adjust strategic priorities'],
            'features' => ['Update feed access', 'Meta-change visibility', 'Balance tracking support'],
            'logic' => ['Patch notes can shift optimal builds', 'Early adaptation yields competitive edge', 'Tracking updates reduces strategic drift'],
        ],
        'contact' => [
            'brief' => 'Staff communication lane for moderation and support routing.',
            'functions' => ['Open messaging channel', 'Report operational issues', 'Coordinate moderator follow-up'],
            'features' => ['Contact pathway shortcut', 'Escalation guidance', 'Support routing context'],
            'logic' => ['Clear issue details reduce resolution time', 'Timestamped reports improve traceability', 'Proper channel use preserves support workflow'],
        ],
        'faq' => [
            'brief' => 'Rules and common answers to reduce onboarding friction.',
            'functions' => ['Open FAQ module', 'Review core policies', 'Understand progression norms'],
            'features' => ['Policy and guidance access', 'Beginner-friendly references', 'Rule clarification hub'],
            'logic' => ['Rule knowledge prevents avoidable penalties', 'Policy alignment improves community health', 'Frequent FAQ review reduces repeated errors'],
        ],
        'events' => [
            'brief' => 'Community event board for tournaments, giveaways, and scheduled campaign windows.',
            'functions' => ['Browse scheduled events', 'Enter tournament brackets', 'Track event reward deadlines'],
            'features' => ['Event calendar', 'Tournament bracket list', 'Reward deadline tracker'],
            'logic' => ['Events rotate on seasonal cadence', 'Tournament rewards track placement tiers', 'Deadlines reset unclaimed prizes'],
        ],
        'academy' => [
            'brief' => 'Strategy academy library with guides, build orders, and advanced doctrine articles.',
            'functions' => ['Read core strategy guides', 'Learn opening build orders', 'Study advanced combat and economy doctrine'],
            'features' => ['Guide index', 'Build-order walkthroughs', 'Doctrine article library'],
            'logic' => ['Guides codify tested strategies', 'Build orders assume unharassed openings', 'Meta evolves with patch cadence'],
        ],
    ],
    'help' => [
        'newplayer' => [
            'brief' => 'Step-by-step early game launch sequence for stable growth.',
            'functions' => ['Outline opening priorities', 'Guide safe expansion rhythm', 'Reduce beginner misplays'],
            'features' => ['Ordered launch checklist', 'Beginner strategy framing', 'Resource safety guidance'],
            'logic' => ['Balanced training lowers early vulnerability', 'Reserve funds protect against shocks', 'Scouting before attack improves odds'],
        ],
        'mechanics' => [
            'brief' => 'Core systems summary explaining turns, combat, and scaling.',
            'functions' => ['Explain action turn economy', 'Highlight combat score impact', 'Describe tech and support rules'],
            'features' => ['Mechanics bullet reference', 'System relationship clarity', 'Practical doctrine hints'],
            'logic' => ['Offensive actions are turn-gated', 'Military score influences ranking pressure', 'Transfer and growth systems reward planning discipline'],
        ],
        'glossary' => [
            'brief' => 'Terminology reference for all key game concepts.',
            'functions' => ['Define core resources', 'Clarify command-chain terms', 'Support quick interpretation'],
            'features' => ['Keyword definitions', 'Fast lookup format', 'New player comprehension support'],
            'logic' => ['Common vocabulary reduces coordination errors', 'Shared terminology improves alliance execution', 'Concept clarity supports faster decision cycles'],
        ],
        'support' => [
            'brief' => 'Issue reporting protocol for account and gameplay incidents.',
            'functions' => ['Provide support reporting guidance', 'Direct users to contact channels', 'Improve issue triage quality'],
            'features' => ['Support workflow brief', 'Evidence checklist cues', 'Escalation context'],
            'logic' => ['Detailed reports are resolved faster', 'Including players and timestamps improves verification', 'Channel discipline prevents lost requests'],
        ],
        'troubleshooting' => [
            'brief' => 'Common error resolution guide for loading, banking, and report display issues.',
            'functions' => ['Diagnose load and render failures', 'Resolve stale state and cache problems', 'Escalate persistent bugs with evidence'],
            'features' => ['Symptom checklist', 'Step-by-step fixes', 'Escalation path'],
            'logic' => ['Most issues resolve with a hard refresh', 'Timestamps and screenshots speed diagnosis', 'Recurring bugs should be reported after one retry'],
        ],
        'hotkeys' => [
            'brief' => 'Keyboard shortcut reference for fast page and command navigation.',
            'functions' => ['List global page shortcuts', 'Show command hotkeys', 'Speed up repeated workflows'],
            'features' => ['Shortcut table', 'Suite-level bindings', 'Command key reference'],
            'logic' => ['Shortcuts reduce clicks on repeat actions', 'Bindings follow suite order for muscle memory', 'Conflicts should be reported for remap'],
        ],
    ],
    'universe' => [
        'galaxies' => [
            'brief' => 'Strategic map of galaxy clusters, sector lanes, and expansion pressure points.',
            'functions' => ['Survey galaxy clusters', 'Track moon and habitability density', 'Support macro colonization pathing'],
            'features' => ['Cluster summary grid', 'Per-galaxy readiness indicators', 'Expansion lane overview'],
            'logic' => ['Galaxy spread reduces campaign congestion', 'High moon density improves tactical flexibility', 'Balanced lane usage improves long-term resilience'],
        ],
        'planets' => [
            'brief' => 'Planetary registry with moon classes, biomes, and resource signatures.',
            'functions' => ['Inspect worlds and moons', 'Prioritize colonization targets', 'Link biome profile to economy strategy'],
            'features' => ['Planet/moon table', 'Biome visibility', 'Resource profile summaries'],
            'logic' => ['Habitability influences colony slot efficiency', 'Biome composition shapes mining and defense roles', 'Moon count helps expedition staging and surveillance'],
        ],
        'objects' => [
            'brief' => 'Interstellar object scanner for debris, nebula, asteroid, and anomaly logistics.',
            'functions' => ['Review object density', 'Plan recycler and scout loops', 'Estimate anomaly opportunities'],
            'features' => ['Object matrix by galaxy', 'Debris recovery tools', 'Route planning context'],
            'logic' => ['Debris-heavy zones raise recovery value', 'Nebulae increase uncertainty in movement timing', 'Wormhole lanes can alter strike projection windows'],
        ],
        'expedition' => [
            'brief' => 'OGame-style expedition and colonization planner with mission control actions.',
            'functions' => ['Stage expeditions', 'Run attack/spy/raid target dispatch', 'Balance colony growth versus military readiness'],
            'features' => ['Mission matrix', 'Target dispatch controls', 'Expansion doctrine checklist'],
            'logic' => ['Expedition risk scales with mission cadence', 'Colonization should preserve reserve economy', 'Multi-front dispatch requires covert and combat redundancy'],
        ],
        'bases' => [
            'brief' => 'Orbital infrastructure command for Space Stations, Starbases, and Moon Bases.',
            'functions' => ['Upgrade orbital installations', 'Increase fleet staging capacity', 'Improve expedition safety and scanning'],
            'features' => ['Persistent base levels', 'Resource-based upgrade controls', 'Integration with fleet and expedition modules'],
            'logic' => ['Space Stations unlock deep-space logistics', 'Starbases require station maturity and improve defense projection', 'Moon Bases require Starbases and boost scan/survival multipliers'],
        ],
        'travel' => [
            'brief' => 'Hyperspace command layer for Jump Gates, Stargates, and interstellar lane routing.',
            'functions' => ['Upgrade gate infrastructure', 'Map travel routes by threat and distance', 'Launch transfer, expedition, and colonization transits'],
            'features' => ['Persistent travel routes', 'Transit queue with ETA/return states', 'Fuel and sustainment cost simulation'],
            'logic' => ['Jump Gates bootstrap lane access', 'Stargates improve deep-route safety and throughput', 'Hyperspace Core levels reduce cooldown and improve long-haul efficiency'],
        ],
        'events' => [
            'brief' => 'Universe-wide event engine with rotating crisis signals and response tracks by galaxy.',
            'functions' => ['Scan for active crisis events', 'Resolve event nodes for campaign points', 'Stabilize threat pressure before boss phases'],
            'features' => ['Persistent event cycle state', 'Event point progression', 'Threat scaling tied to operations'],
            'logic' => ['Event scans consume action turns and logistics fuel', 'Successful responses increase campaign progression', 'Unmanaged threats increase world boss pressure'],
        ],
        'worldboss' => [
            'brief' => 'Galaxy-class world boss system with spawn, assault, and defeat reward loops.',
            'functions' => ['Spawn sector boss encounters', 'Launch action-turn assaults', 'Cycle reward payouts and next-tier scaling'],
            'features' => ['Persistent boss HP state', 'Boss phase statuses', 'Defeat rewards and escalation'],
            'logic' => ['Boss assaults spend turns and combat reserves', 'Boss HP scales by level and threat pressure', 'Defeats grant economy and campaign progression rewards'],
        ],
        'story' => [
            'brief' => 'Narrative campaign system with prologue, 12 acts, chapter progression, and story logs.',
            'functions' => ['Unlock and run prologue mission flow', 'Advance through 12 acts with chapter checkpoints', 'Record per-log story entries for campaign history'],
            'features' => ['Act/chapter progression state', 'Prologue unlock gate', 'Persistent story log timeline'],
            'logic' => ['Story progression spends turns and consumes campaign momentum', 'Act completion gates later narrative phases', 'Story logs preserve key commander outcomes'],
        ],
        'lanes' => [
            'brief' => 'Hyperspace lane registry mapping secure routes, risk bands, and travel time by galaxy.',
            'functions' => ['Compare lane risk and ETA', 'Route convoys along secure lanes', 'Expose flanks on contested routes'],
            'features' => ['Lane table with risk bands', 'ETA and fuel preview', 'Route planner context'],
            'logic' => ['Secure lanes cost more fuel per hop', 'Contested lanes speed transits but invite interception', 'Lane density determines regional mobility'],
        ],
        'anomalies' => [
            'brief' => 'Anomaly catalog for asteroid belts, debris fields, wormholes, and temporal glitches.',
            'functions' => ['Survey anomaly signatures', 'Route expeditions to high-value objects', 'Sell anomaly data on the intel market'],
            'features' => ['Anomaly signature table', 'Value and risk ratings', 'Data-sale option'],
            'logic' => ['Anomaly value scales with scan depth', 'High-risk anomalies demand escort coverage', 'Sold intel reveals location to buyers'],
        ],
        'seeds' => [
            'brief' => 'Expansion seed planner for colony placement, biome matching, and sector concentration.',
            'functions' => ['Match biome to colony role', 'Spread seeds across galaxies', 'Balance concentration against risk'],
            'features' => ['Seed placement grid', 'Biome-role matching table', 'Concentration risk gauge'],
            'logic' => ['Biome bonuses shape colony specialization', 'Spread reduces wipe risk but slows coordination', 'Concentration trades resilience for tempo'],
        ],
    ],
    'research' => [
        'tree' => [
            'brief' => 'Master research tree with domain-tier progression, level systems, and core stat scaffolding.',
            'functions' => ['Browse research domains and tiers', 'Track level systems and XP to next tier', 'Review top-level stat and sub-stat baselines'],
            'features' => ['10-domain tree matrix', 'Level progression panel', 'Stats and sub-stats board'],
            'logic' => ['Research level scales with cumulative tech progression', 'Tier costs increase per domain stage', 'Sub-stats influence specialized outcomes'],
        ],
        'techlib' => [
            'brief' => 'Technology tree library focused on implementation branches and throughput disciplines.',
            'functions' => ['Browse technology domain ladders', 'Compare tech tier costs and power', 'Route upgrades to military or economy goals'],
            'features' => ['Per-domain technology nodes', 'Power and cost summaries', 'Cross-link to existing tech module'],
            'logic' => ['Technology throughput compounds with level systems', 'Branch selection changes empire specialization', 'Balanced sequencing reduces bottlenecks'],
        ],
        'classes' => [
            'brief' => 'Expanded doctrine class library with 90 classes and mapped subclasses, types, and sub-types.',
            'functions' => ['Inspect class doctrine models', 'Audit type and subtype coverage', 'Map classes to mission roles'],
            'features' => ['90 class rows', 'Subclass pairings', 'Type and subtype categorization'],
            'logic' => ['Class doctrine defines build intent', 'Subtype detail refines tactical usage', 'Coverage supports flexible campaign design'],
        ],
        'talents' => [
            'brief' => 'Talent library containing 240 unique entries split across research and technology branches.',
            'functions' => ['Browse research talents', 'Browse technology talents', 'Review tier and effect progression'],
            'features' => ['240 talent index', 'Branch and tier filtering table', 'Effect strings for planning'],
            'logic' => ['Talent tiers scale in progression bands', 'Branch choice impacts growth profile', 'Effects stack with tech and level systems'],
        ],
        'stargate' => [
            'brief' => 'Full empire technology command for gate science, power systems, fleet integration, and threat-response research.',
            'functions' => ['Upgrade empire-specific technologies', 'Spend Naquadah plus strategic resources on research', 'Scale deep-space mobility and defensive doctrine'],
            'features' => ['Multi-domain empire tech catalog', 'Per-tech level tracking', 'Integrated economy and hyperspace dependencies'],
            'logic' => ['Each upgrade scales in cost by level', 'Energy and deuterium become core late-tier constraints', 'Technology compounding improves interstellar campaign tempo'],
        ],
        'infrastructure' => [
            'brief' => 'Infrastructure research line for orbital, station, and deep-space construction science.',
            'functions' => ['Research orbital construction tiers', 'Unlock station and moon-base branches', 'Reduce building cost multipliers'],
            'features' => ['Infrastructure tech ladder', 'Cost reduction previews', 'Station dependency map'],
            'logic' => ['Infrastructure tiers gate late-game buildings', 'Cost reductions compound across the empire', 'Dependencies force sequential planning'],
        ],
        'projects' => [
            'brief' => 'Long-horizon empire projects with staged funding and milestone payouts.',
            'functions' => ['Start strategic projects', 'Fund project stages with resources', 'Collect milestone payouts'],
            'features' => ['Project board', 'Stage funding tracker', 'Milestone reward table'],
            'logic' => ['Projects lock resources until completion', 'Staged funding protects partial progress', 'Milestones pay out on fixed thresholds'],
        ],
        'labs' => [
            'brief' => 'Research laboratory management for capacity, slots, and scientist allocation.',
            'functions' => ['Upgrade lab capacity', 'Manage research slots', 'Allocate scientists to active branches'],
            'features' => ['Lab level controls', 'Slot queue view', 'Scientist allocation table'],
            'logic' => ['Higher labs unlock parallel research slots', 'Scientist count speeds active projects', 'Slot discipline avoids idle research time'],
        ],
        'blueprints' => [
            'brief' => 'Blueprint archive for unlocked construction patterns and exclusive tech recipes.',
            'functions' => ['Browse discovered blueprints', 'Claim build permission for exclusive structures', 'Track blueprint drop sources'],
            'features' => ['Blueprint index', 'Claim flow', 'Drop-source tracker'],
            'logic' => ['Blueprints gate exclusive late-game builds', 'Drops come from world boss and expedition loot', 'Claims are consumed once per blueprint'],
        ],
    ],
];

if (!isset($mainTitles[$main])) {
    $main = 'empire';
}
if ($sub === '' || !isset($subLabels[$main][$sub])) {
    $sub = $subDefaults[$main];
}

$uid = (int)$_SESSION['userid'];
$requestedPage = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$requestedPerPage = isset($_GET['pp']) ? (int)$_GET['pp'] : 50;
$cmd = isset($_GET['cmd']) ? preg_replace('/[^a-z_]/', '', strtolower((string)$_GET['cmd'])) : '';
$troopClassFilter = isset($_GET['tcclass']) ? preg_replace('/[^a-z]/', '', strtolower((string)$_GET['tcclass'])) : 'all';
$troopLegionFilter = isset($_GET['tclegion']) ? preg_replace('/[^a-z]/', '', strtolower((string)$_GET['tclegion'])) : 'all';
$troopPage = isset($_GET['tp']) ? (int)$_GET['tp'] : 1;
$troopPickId = isset($_GET['tpid']) ? (int)$_GET['tpid'] : 0;
$troopPickQty = isset($_GET['tqty']) ? (int)$_GET['tqty'] : 1;
$troopQueueId = isset($_GET['tqid']) ? (int)$_GET['tqid'] : 0;
$opsQueueId = isset($_GET['oqid']) ? (int)$_GET['oqid'] : 0;
$eventTargetGalaxy = isset($_GET['gal']) ? (int)$_GET['gal'] : 1;
$targetWorld = isset($_GET['target']) ? (int)$_GET['target'] : 0;
$targetMoonNo = isset($_GET['moon']) ? (int)$_GET['moon'] : 0;
$fieldBuildCode = isset($_GET['bld']) ? preg_replace('/[^a-z]/', '', strtolower((string)$_GET['bld'])) : '';
$fieldTargetType = isset($_GET['ftype']) ? preg_replace('/[^a-z]/', '', strtolower((string)$_GET['ftype'])) : 'planet';
$bpId = isset($_GET['bp']) ? (int)$_GET['bp'] : 0;
$bpQty = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;
$bpMode = isset($_GET['mode']) ? preg_replace('/[^a-z]/', '', strtolower((string)$_GET['mode'])) : 'me';
$s->updatePower($uid);

$baseData = $s->baseVars();
$personnel = $s->getPersonnel($uid);
$bank = $s->bank();
$userStats = $s->getUserInfo($uid);
$uCfg = universeConfig();
$pageActionStatus = '';
$planetRegistryRows = [];
$ownedMoonRows = [];
$planetRegistryQ = $s->query("SELECT pid, plnt_name, plnt_size, isHome FROM planets WHERE uid=" . $uid . " ORDER BY isHome DESC, pid ASC LIMIT 100");
if ($planetRegistryQ) {
    while ($planetRow = $planetRegistryQ->fetch_assoc()) {
        $planetRegistryRows[] = $planetRow;
    }
}
$ownedMoonQ = $s->query("SELECT moon_id, moon_name, pid FROM moon_data WHERE uid=" . $uid . " ORDER BY moon_id ASC LIMIT 100");
if ($ownedMoonQ) {
    while ($moonRow = $ownedMoonQ->fetch_assoc()) {
        $ownedMoonRows[] = $moonRow;
    }
}

if ($main === 'universe' && $cmd === 'rename_entity') {
    $entityType = isset($_GET['entity']) ? preg_replace('/[^a-z]/', '', strtolower((string)$_GET['entity'])) : '';
    $newNameRaw = trim((string)(isset($_GET['new_name']) ? $_GET['new_name'] : ''));
    $finalName = dbSafeEntityName($newNameRaw);
    $fallbackName = 'Unnamed';
    if ($entityType === 'planet') {
        $pid = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;
        $fallbackName = $pid > 0 ? ('Planet ' . $pid) : 'Planet';
        if ($pid > 0) {
            if ($finalName === '') {
                $finalName = dbSafeEntityName($fallbackName);
            }
            $s->query("UPDATE planets SET plnt_name='" . $finalName . "' WHERE uid=" . $uid . " AND pid=" . $pid . " LIMIT 1");
            $pageActionStatus = 'Planet renamed to ' . h($finalName) . '.';
        } else {
            $pageActionStatus = 'Planet rename failed: choose a colony first.';
        }
    } elseif ($entityType === 'moon') {
        $moonId = isset($_GET['moon_id']) ? (int)$_GET['moon_id'] : 0;
        $fallbackName = $moonId > 0 ? ('Moon ' . $moonId) : 'Moon';
        if ($moonId > 0) {
            if ($finalName === '') {
                $finalName = dbSafeEntityName($fallbackName);
            }
            $existingMoonQ = $s->query("SELECT moon_id FROM moon_data WHERE uid=" . $uid . " AND moon_id=" . $moonId . " LIMIT 1");
            if (!$existingMoonQ || $existingMoonQ->num_rows === 0) {
                $s->query("INSERT INTO moon_data (uid, moon_name, pid) VALUES (" . $uid . ", '', 0)");
            }
            $s->query("UPDATE moon_data SET moon_name='" . $finalName . "' WHERE uid=" . $uid . " AND moon_id=" . $moonId . " LIMIT 1");
            $pageActionStatus = 'Moon renamed to ' . h($finalName) . '.';
        } else {
            $pageActionStatus = 'Moon rename failed: choose a moon first.';
        }
    } else {
        $pageActionStatus = 'Naming failed: unsupported entity type.';
    }
}

$planets = $s->getUserPlanets($uid);
$universeActionStatus = '';
if ($main === 'universe' && $cmd === 'colonize') {
    $universeActionStatus = universeColonizeWorld($s, $uid, $uCfg, $planets, $targetWorld);
    $planets = $s->getUserPlanets($uid);
}
$universe = buildUniverseSnapshot($uid, $planets);
$worldSlice = universeWorldSlice($uid, $planets, $uCfg, $requestedPage, $requestedPerPage);
$techView = $s->viewTech();
$researchHub = buildResearchDirectorate($uid, $techView, $personnel);
$resourceHub = resourceEnsureAndTick($s, $uid, $baseData, $planets, $techView);

$blueprintCatalog = blueprintCatalog();
$blueprintOwned = [];
$blueprintHangar = [];
$blueprintBuildingCatalog = [];
$seedSlice = ['rows' => [], 'page' => 1, 'perPage' => 25, 'maxPage' => 1, 'start' => 0, 'end' => 0, 'total' => 0];
$seedBookmarks = [];
$operationsRtsCatalog = [
    'recon' => ['label' => 'Deep Recon Sweep', 'turn_cost' => 2, 'naq_cost' => 0, 'metal_cost' => 0, 'crystal_cost' => 0, 'deut_cost' => 2200, 'food_cost' => 900, 'water_cost' => 700, 'need_untrained' => 0, 'untrained_delta' => 0, 'attack_delta' => 0, 'defense_delta' => 0, 'covert_delta' => 35, 'anticovert_delta' => 20, 'xp_gain' => 8, 'pressure_delta' => 2, 'reserve_delta' => -1, 'morale_delta' => 1, 'eta_seconds' => 210],
    'assault' => ['label' => 'Shock Assault Wave', 'turn_cost' => 4, 'naq_cost' => 90000, 'metal_cost' => 0, 'crystal_cost' => 0, 'deut_cost' => 7200, 'food_cost' => 4200, 'water_cost' => 0, 'need_untrained' => 60, 'untrained_delta' => -60, 'attack_delta' => 120, 'defense_delta' => 45, 'covert_delta' => 0, 'anticovert_delta' => 0, 'xp_gain' => 15, 'pressure_delta' => 6, 'reserve_delta' => -3, 'morale_delta' => 2, 'eta_seconds' => 300],
    'fortify' => ['label' => 'Defense Fortification Cycle', 'turn_cost' => 3, 'naq_cost' => 0, 'metal_cost' => 22000, 'crystal_cost' => 14000, 'deut_cost' => 0, 'food_cost' => 0, 'water_cost' => 0, 'need_untrained' => 0, 'untrained_delta' => 0, 'attack_delta' => 0, 'defense_delta' => 140, 'covert_delta' => 0, 'anticovert_delta' => 60, 'xp_gain' => 12, 'pressure_delta' => -2, 'reserve_delta' => 5, 'morale_delta' => 1, 'eta_seconds' => 260],
    'logistics' => ['label' => 'Reserve Logistics Surge', 'turn_cost' => 2, 'naq_cost' => 65000, 'metal_cost' => 0, 'crystal_cost' => 0, 'deut_cost' => 0, 'food_cost' => 3000, 'water_cost' => 3000, 'need_untrained' => 0, 'untrained_delta' => 260, 'attack_delta' => 0, 'defense_delta' => 0, 'covert_delta' => 0, 'anticovert_delta' => 0, 'xp_gain' => 9, 'pressure_delta' => -1, 'reserve_delta' => 4, 'morale_delta' => 2, 'eta_seconds' => 240],
    'sabotage' => ['label' => 'Covert Sabotage Grid', 'turn_cost' => 3, 'naq_cost' => 50000, 'metal_cost' => 0, 'crystal_cost' => 0, 'deut_cost' => 4600, 'food_cost' => 0, 'water_cost' => 0, 'need_untrained' => 0, 'untrained_delta' => 0, 'attack_delta' => 0, 'defense_delta' => 0, 'covert_delta' => 90, 'anticovert_delta' => 0, 'xp_gain' => 13, 'pressure_delta' => 4, 'reserve_delta' => -2, 'morale_delta' => 0, 'eta_seconds' => 280],
];
$operationsRtsState = null;
$operationsRtsTurnBalance = 0;
$universeEventState = null;
$universeBossState = null;
$universeStoryState = null;
$universeStoryActs = universeStoryActs();
$universeColonyProfiles = [];
$universeColonyFields = [];

if (($main === 'economy' && ($sub === 'store' || $sub === 'battlepass' || $sub === 'seasonpass')) || ($main === 'economy' && strpos($cmd, 'store_') === 0) || ($main === 'economy' && strpos($cmd, 'pass_') === 0)) {
    $s->query("CREATE TABLE IF NOT EXISTS economy_store_catalog (
        item_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        item_key VARCHAR(40) NOT NULL UNIQUE,
        item_name VARCHAR(80) NOT NULL,
        item_type VARCHAR(24) NOT NULL DEFAULT 'resource',
        price_nq BIGINT NOT NULL DEFAULT 0,
        price_metal BIGINT NOT NULL DEFAULT 0,
        price_crystal BIGINT NOT NULL DEFAULT 0,
        price_deut BIGINT NOT NULL DEFAULT 0,
        price_energy BIGINT NOT NULL DEFAULT 0,
        reward_amount BIGINT NOT NULL DEFAULT 0,
        reward_label VARCHAR(120) NOT NULL DEFAULT '',
        rarity VARCHAR(16) NOT NULL DEFAULT 'common',
        active TINYINT(1) NOT NULL DEFAULT 1
    )");
    $s->query("CREATE TABLE IF NOT EXISTS economy_store_purchases (
        uid INT NOT NULL,
        item_key VARCHAR(40) NOT NULL,
        purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(uid, item_key)
    )");
    $s->query("CREATE TABLE IF NOT EXISTS economy_pass_progress (
        uid INT NOT NULL PRIMARY KEY,
        season_id INT NOT NULL DEFAULT 1,
        battle_pass_level INT NOT NULL DEFAULT 0,
        battle_pass_xp INT NOT NULL DEFAULT 0,
        season_pass_level INT NOT NULL DEFAULT 0,
        season_pass_xp INT NOT NULL DEFAULT 0,
        last_claimed_level INT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $s->query("INSERT IGNORE INTO economy_pass_progress (uid) VALUES (" . $uid . ")");
    $s->query("CREATE TABLE IF NOT EXISTS economy_pass_claims (
        uid INT NOT NULL,
        pass_type VARCHAR(20) NOT NULL,
        level INT NOT NULL,
        reward_key VARCHAR(64) NOT NULL,
        claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(uid, pass_type, level, reward_key)
    )");
    $storeCatalog = [
        ['item_key' => 'energy_boost', 'item_name' => 'Energy Burst', 'item_type' => 'resource', 'price_nq' => 25000, 'price_metal' => 0, 'price_crystal' => 0, 'price_deut' => 0, 'price_energy' => 0, 'reward_amount' => 120000, 'reward_label' => 'Energy', 'rarity' => 'common'],
        ['item_key' => 'water_refill', 'item_name' => 'Water Refill', 'item_type' => 'resource', 'price_nq' => 18000, 'price_metal' => 0, 'price_crystal' => 0, 'price_deut' => 0, 'price_energy' => 0, 'reward_amount' => 90000, 'reward_label' => 'Water', 'rarity' => 'common'],
        ['item_key' => 'crystal_reserve', 'item_name' => 'Crystal Reserve', 'item_type' => 'resource', 'price_nq' => 32000, 'price_metal' => 0, 'price_crystal' => 0, 'price_deut' => 0, 'price_energy' => 0, 'reward_amount' => 140000, 'reward_label' => 'Crystal', 'rarity' => 'common'],
        ['item_key' => 'food_cache', 'item_name' => 'Food Cache', 'item_type' => 'resource', 'price_nq' => 28000, 'price_metal' => 0, 'price_crystal' => 0, 'price_deut' => 0, 'price_energy' => 0, 'reward_amount' => 110000, 'reward_label' => 'Food', 'rarity' => 'common'],
        ['item_key' => 'command_cache', 'item_name' => 'Command Cache', 'item_type' => 'resource', 'price_nq' => 50000, 'price_metal' => 0, 'price_crystal' => 0, 'price_deut' => 0, 'price_energy' => 0, 'reward_amount' => 250000, 'reward_label' => 'Naquadah', 'rarity' => 'rare'],
        ['item_key' => 'fleet_booster', 'item_name' => 'Fleet Booster', 'item_type' => 'boost', 'price_nq' => 80000, 'price_metal' => 0, 'price_crystal' => 0, 'price_deut' => 0, 'price_energy' => 0, 'reward_amount' => 1, 'reward_label' => 'Fleet tempo boost', 'rarity' => 'epic'],
    ];
    foreach ($storeCatalog as $item) {
        $s->query("INSERT IGNORE INTO economy_store_catalog (item_key, item_name, item_type, price_nq, price_metal, price_crystal, price_deut, price_energy, reward_amount, reward_label, rarity) VALUES ('" . pageSafeToken($item['item_key']) . "', '" . pageSafeToken($item['item_name']) . "', '" . pageSafeToken($item['item_type']) . "', " . (int)$item['price_nq'] . ", " . (int)$item['price_metal'] . ", " . (int)$item['price_crystal'] . ", " . (int)$item['price_deut'] . ", " . (int)$item['price_energy'] . ", " . (int)$item['reward_amount'] . ", '" . pageSafeToken($item['reward_label']) . "', '" . pageSafeToken($item['rarity']) . "')");
    }
    $passProgressQ = $s->query("SELECT season_id, battle_pass_level, battle_pass_xp, season_pass_level, season_pass_xp, last_claimed_level FROM economy_pass_progress WHERE uid=" . $uid . " LIMIT 1");
    $passProgress = $passProgressQ && $passProgressQ->num_rows > 0 ? $passProgressQ->fetch_object() : (object)['season_id' => 1, 'battle_pass_level' => 0, 'battle_pass_xp' => 0, 'season_pass_level' => 0, 'season_pass_xp' => 0, 'last_claimed_level' => 0];
    $purchasedQ = $s->query("SELECT item_key FROM economy_store_purchases WHERE uid=" . $uid . "");
    $purchasedKeys = [];
    if ($purchasedQ) {
        while ($row = $purchasedQ->fetch_object()) {
            $purchasedKeys[(string)$row->item_key] = true;
        }
    }
    $storeRows = [];
    $storeQ = $s->query("SELECT item_key, item_name, item_type, price_nq, price_metal, price_crystal, price_deut, price_energy, reward_amount, reward_label, rarity FROM economy_store_catalog WHERE active=1 ORDER BY price_nq ASC");
    if ($storeQ) {
        while ($row = $storeQ->fetch_assoc()) {
            $storeRows[] = $row;
        }
    }
    if (isset($_GET['cmd']) && $cmd === 'store_purchase') {
        $itemKey = isset($_GET['item']) ? preg_replace('/[^a-z_]/', '', strtolower((string)$_GET['item'])) : '';
        $itemRow = null;
        $resourceCurrent = $resourceHub['current'] ?? ['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'food' => 0, 'water' => 0, 'population' => 0, 'energy' => 0];
        foreach ($storeRows as $row) {
            if ($row['item_key'] === $itemKey) {
                $itemRow = $row;
                break;
            }
        }
        if ($itemRow === null) {
            $pageActionStatus = 'Store item not found.';
        } else {
            $canAfford = true;
            $canAfford = $canAfford && ((int)$bank->onHand >= (int)$itemRow['price_nq']);
            $canAfford = $canAfford && ((int)$resourceCurrent['metal'] >= (int)$itemRow['price_metal']);
            $canAfford = $canAfford && ((int)$resourceCurrent['crystal'] >= (int)$itemRow['price_crystal']);
            $canAfford = $canAfford && ((int)$resourceCurrent['deuterium'] >= (int)$itemRow['price_deut']);
            $canAfford = $canAfford && ((int)$resourceCurrent['energy'] >= (int)$itemRow['price_energy']);
            if (!$canAfford) {
                $pageActionStatus = 'Insufficient resources for that store purchase.';
            } elseif (isset($purchasedKeys[$itemKey])) {
                $pageActionStatus = 'That item has already been purchased.';
            } else {
                $s->query("UPDATE bank SET onHand=onHand-" . (int)$itemRow['price_nq'] . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE player_resources SET metal=metal-" . (int)$itemRow['price_metal'] . ", crystal=crystal-" . (int)$itemRow['price_crystal'] . ", deuterium=deuterium-" . (int)$itemRow['price_deut'] . ", energy=energy-" . (int)$itemRow['price_energy'] . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("INSERT INTO economy_store_purchases (uid, item_key) VALUES (" . $uid . ", '" . pageSafeToken($itemKey) . "')");
                if ((string)$itemRow['item_type'] === 'resource') {
                    $rewardLabel = (string)$itemRow['reward_label'];
                    if ($rewardLabel === 'Naquadah') {
                        $s->query("UPDATE bank SET onHand=onHand+" . (int)$itemRow['reward_amount'] . " WHERE uid=" . $uid . " LIMIT 1");
                    } elseif ($rewardLabel === 'Metal') {
                        $s->query("UPDATE player_resources SET metal=metal+" . (int)$itemRow['reward_amount'] . " WHERE uid=" . $uid . " LIMIT 1");
                    } elseif ($rewardLabel === 'Crystal') {
                        $s->query("UPDATE player_resources SET crystal=crystal+" . (int)$itemRow['reward_amount'] . " WHERE uid=" . $uid . " LIMIT 1");
                    } elseif ($rewardLabel === 'Deuterium') {
                        $s->query("UPDATE player_resources SET deuterium=deuterium+" . (int)$itemRow['reward_amount'] . " WHERE uid=" . $uid . " LIMIT 1");
                    } elseif ($rewardLabel === 'Food') {
                        $s->query("UPDATE player_resources SET food=food+" . (int)$itemRow['reward_amount'] . " WHERE uid=" . $uid . " LIMIT 1");
                    } elseif ($rewardLabel === 'Water') {
                        $s->query("UPDATE player_resources SET water=water+" . (int)$itemRow['reward_amount'] . " WHERE uid=" . $uid . " LIMIT 1");
                    } else {
                        $s->query("UPDATE player_resources SET energy=energy+" . (int)$itemRow['reward_amount'] . " WHERE uid=" . $uid . " LIMIT 1");
                    }
                } else {
                    $s->query("UPDATE economy_pass_progress SET battle_pass_xp=battle_pass_xp+100, season_pass_xp=season_pass_xp+120 WHERE uid=" . $uid . " LIMIT 1");
                }
                $pageActionStatus = 'Purchased ' . h($itemRow['item_name']) . '.';
                $purchasedKeys[$itemKey] = true;
            }
        }
    }
    if (isset($_GET['cmd']) && $cmd === 'pass_claim') {
        $passType = isset($_GET['pass']) ? preg_replace('/[^a-z]/', '', strtolower((string)$_GET['pass'])) : 'battle';
        $level = isset($_GET['level']) ? (int)$_GET['level'] : 0;
        $rewardKey = isset($_GET['reward']) ? preg_replace('/[^a-z_]/', '', strtolower((string)$_GET['reward'])) : '';
        if ($passType === 'battle') {
            $currentLevel = (int)$passProgress->battle_pass_level;
            $xp = (int)$passProgress->battle_pass_xp;
            $targetLevel = max(1, min(100, $level));
            if ($targetLevel > $currentLevel) {
                $s->query("UPDATE economy_pass_progress SET battle_pass_level=" . $targetLevel . ", battle_pass_xp=" . $xp . " WHERE uid=" . $uid . " LIMIT 1");
                $passProgress->battle_pass_level = $targetLevel;
            }
            $rewardKey = $rewardKey !== '' ? $rewardKey : 'battle_reward_' . $targetLevel;
        } else {
            $rewardKey = $rewardKey !== '' ? $rewardKey : 'season_reward_' . $level;
        }
        $claimQ = $s->query("SELECT 1 FROM economy_pass_claims WHERE uid=" . $uid . " AND pass_type='" . pageSafeToken($passType) . "' AND level=" . $level . " AND reward_key='" . pageSafeToken($rewardKey) . "' LIMIT 1");
        if ($claimQ && $claimQ->num_rows > 0) {
            $pageActionStatus = 'Reward already claimed.';
        } else {
            $s->query("INSERT INTO economy_pass_claims (uid, pass_type, level, reward_key) VALUES (" . $uid . ", '" . pageSafeToken($passType) . "', " . $level . ", '" . pageSafeToken($rewardKey) . "')");
            if ($passType === 'battle') {
                $rewardAmount = 150000;
                $rewardLabel = 'Energy';
                if ($level % 5 === 0) {
                    $rewardAmount = 220000;
                    $rewardLabel = 'Naquadah';
                } elseif ($level % 3 === 0) {
                    $rewardAmount = 90000;
                    $rewardLabel = 'Water';
                } elseif ($level % 2 === 0) {
                    $rewardAmount = 120000;
                    $rewardLabel = 'Metal';
                }
                if ($rewardLabel === 'Naquadah') {
                    $s->query("UPDATE bank SET onHand=onHand+" . $rewardAmount . " WHERE uid=" . $uid . " LIMIT 1");
                } elseif ($rewardLabel === 'Metal') {
                    $s->query("UPDATE player_resources SET metal=metal+" . $rewardAmount . " WHERE uid=" . $uid . " LIMIT 1");
                } elseif ($rewardLabel === 'Water') {
                    $s->query("UPDATE player_resources SET water=water+" . $rewardAmount . " WHERE uid=" . $uid . " LIMIT 1");
                } else {
                    $s->query("UPDATE player_resources SET energy=energy+" . $rewardAmount . " WHERE uid=" . $uid . " LIMIT 1");
                }
            } else {
                $rewardAmount = 180000;
                $rewardLabel = 'Crystal';
                if ($level % 5 === 0) {
                    $rewardAmount = 300000;
                    $rewardLabel = 'Food';
                } elseif ($level % 3 === 0) {
                    $rewardAmount = 110000;
                    $rewardLabel = 'Deuterium';
                } elseif ($level % 2 === 0) {
                    $rewardAmount = 140000;
                    $rewardLabel = 'Water';
                }
                if ($rewardLabel === 'Water') {
                    $s->query("UPDATE player_resources SET water=water+" . $rewardAmount . " WHERE uid=" . $uid . " LIMIT 1");
                } elseif ($rewardLabel === 'Deuterium') {
                    $s->query("UPDATE player_resources SET deuterium=deuterium+" . $rewardAmount . " WHERE uid=" . $uid . " LIMIT 1");
                } elseif ($rewardLabel === 'Food') {
                    $s->query("UPDATE player_resources SET food=food+" . $rewardAmount . " WHERE uid=" . $uid . " LIMIT 1");
                } else {
                    $s->query("UPDATE player_resources SET crystal=crystal+" . $rewardAmount . " WHERE uid=" . $uid . " LIMIT 1");
                }
            }
            $pageActionStatus = 'Claimed pass reward for level ' . $level . '.';
        }
    }
    if (isset($_GET['cmd']) && $cmd === 'pass_gain') {
        $xpGain = isset($_GET['xp']) ? (int)$_GET['xp'] : 120;
        $s->query("UPDATE economy_pass_progress SET battle_pass_xp=battle_pass_xp+" . $xpGain . ", season_pass_xp=season_pass_xp+" . (int)round($xpGain * 0.8) . " WHERE uid=" . $uid . " LIMIT 1");
        $pageActionStatus = 'Pass experience updated.';
    }
    $passProgressQ = $s->query("SELECT season_id, battle_pass_level, battle_pass_xp, season_pass_level, season_pass_xp, last_claimed_level FROM economy_pass_progress WHERE uid=" . $uid . " LIMIT 1");
    $passProgress = $passProgressQ && $passProgressQ->num_rows > 0 ? $passProgressQ->fetch_object() : (object)['season_id' => 1, 'battle_pass_level' => 0, 'battle_pass_xp' => 0, 'season_pass_level' => 0, 'season_pass_xp' => 0, 'last_claimed_level' => 0];
    $battleLevels = [];
    for ($i = 1; $i <= 10; $i++) {
        $battleLevels[] = ['level' => $i, 'xp' => $i * 120, 'reward' => 'Tier ' . $i . ' reward', 'bonus' => ($i % 2 === 0 ? 'Naquadah' : 'Energy')];
    }
    $seasonLevels = [];
    for ($i = 1; $i <= 10; $i++) {
        $seasonLevels[] = ['level' => $i, 'xp' => $i * 160, 'reward' => 'Season ' . $i . ' reward', 'bonus' => ($i % 2 === 0 ? 'Water' : 'Metal')];
    }
    $claimedPassRows = [];
    $claimedPassQ = $s->query("SELECT pass_type, level, reward_key FROM economy_pass_claims WHERE uid=" . $uid . "");
    if ($claimedPassQ) {
        while ($row = $claimedPassQ->fetch_assoc()) {
            $claimedPassRows[] = $row;
        }
    }
    $claimedPassSet = [];
    foreach ($claimedPassRows as $claimedRow) {
        $claimedPassSet[(string)$claimedRow['pass_type'] . ':' . (int)$claimedRow['level']] = true;
    }
}

if ($main === 'military' || strpos($cmd, 'mil_') === 0) {
    $s->query("CREATE TABLE IF NOT EXISTS military_command_state ( -- This table is created in the new SQL migration file
        uid INT NOT NULL PRIMARY KEY,
        readiness_index INT NOT NULL DEFAULT 50,
        drill_xp INT NOT NULL DEFAULT 0,
        navy_focus VARCHAR(24) NOT NULL DEFAULT 'balanced',
        defense_posture VARCHAR(24) NOT NULL DEFAULT 'standard',
        logistics_posture VARCHAR(24) NOT NULL DEFAULT 'steady',
        war_games INT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    $troopCountQ = $s->query("SELECT COUNT(*) AS c FROM military_troop_catalog");
    $troopCount = $troopCountQ ? (int)($troopCountQ->fetch_object()->c ?? 0) : 0;
    if ($troopCount !== 240) {
        foreach ($troopCatalog as $t) {
            $s->query("REPLACE INTO military_troop_catalog (
                troop_id, troop_code, troop_name, troop_rank, troop_title, class_name, class_subclass,
                troop_type, troop_subtype, power_stat, attack_stat, defense_stat, covert_stat, anti_covert_stat,
                mobility_stat, morale_stat, logistics_stat, tactic_substat, resilience_substat, discipline_substat,
                attribute_primary, attribute_secondary, sub_attribute_a, sub_attribute_b, legion_name, tier
            ) VALUES (
                " . (int)$t['troop_id'] . ",
                '" . pageSafeToken((string)$t['troop_code']) . "',
                '" . pageSafeToken((string)$t['troop_name']) . "',
                '" . pageSafeToken((string)$t['troop_rank']) . "',
                '" . pageSafeToken((string)$t['troop_title']) . "',
                '" . pageSafeToken((string)$t['class_name']) . "',
                '" . pageSafeToken((string)$t['class_subclass']) . "',
                '" . pageSafeToken((string)$t['troop_type']) . "',
                '" . pageSafeToken((string)$t['troop_subtype']) . "',
                " . (int)$t['power_stat'] . ",
                " . (int)$t['attack_stat'] . ",
                " . (int)$t['defense_stat'] . ",
                " . (int)$t['covert_stat'] . ",
                " . (int)$t['anti_covert_stat'] . ",
                " . (int)$t['mobility_stat'] . ",
                " . (int)$t['morale_stat'] . ",
                " . (int)$t['logistics_stat'] . ",
                " . (int)$t['tactic_substat'] . ",
                " . (int)$t['resilience_substat'] . ",
                " . (int)$t['discipline_substat'] . ",
                '" . pageSafeToken((string)$t['attribute_primary']) . "',
                '" . pageSafeToken((string)$t['attribute_secondary']) . "',
                " . (int)$t['sub_attribute_a'] . ",
                " . (int)$t['sub_attribute_b'] . ",
                '" . pageSafeToken((string)$t['legion_name']) . "',
                " . (int)$t['tier'] . "
            )");
        }
    }

    $troopById = [];
    foreach ($troopCatalog as $t) {
        $troopById[(int)$t['troop_id']] = $t;
    }

    if (strpos($cmd, 'mil_') === 0) {
        $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
        $turns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
        $resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
        $res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'food' => 0, 'water' => 0, 'population' => 0];
        $unitQ = $s->query("SELECT untrained,attack,defense,covert,anticovert FROM units WHERE uid=" . $uid . " LIMIT 1");
        $unitsObj = $unitQ ? $unitQ->fetch_object() : (object)['untrained' => 0, 'attack' => 0, 'defense' => 0, 'covert' => 0, 'anticovert' => 0];
        $bankObj = $bank ?: (object)['onHand' => 0];

        if ($cmd === 'mil_personnel_drill') {
            $needTurns = 2;
            $needFood = 2600;
            $needWater = 2400;
            $needUu = 140;
            if ($turns < $needTurns) {
                $pageActionStatus = 'Personnel drill failed: insufficient action turns.';
            } elseif ((int)$res->food < $needFood || (int)$res->water < $needWater) {
                $pageActionStatus = 'Personnel drill failed: insufficient food/water reserves.';
            } elseif ((int)$unitsObj->untrained < $needUu) {
                $pageActionStatus = 'Personnel drill failed: insufficient untrained units.';
            } else {
                $atkGain = 70;
                $defGain = 45;
                $covGain = 25;
                $s->query("UPDATE player_resources SET food=food-" . $needFood . ", water=water-" . $needWater . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . $needTurns . ") WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE units SET untrained=untrained-" . $needUu . ", attack=attack+" . $atkGain . ", defense=defense+" . $defGain . ", covert=covert+" . $covGain . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE military_command_state SET drill_xp=drill_xp+12, readiness_index=LEAST(100, readiness_index+4) WHERE uid=" . $uid . " LIMIT 1");
                $pageActionStatus = 'Personnel drill complete: +' . fnum($atkGain) . ' ATK, +' . fnum($defGain) . ' DEF, +' . fnum($covGain) . ' COV.';
            }
        }

        if ($cmd === 'mil_armory_refit') {
            $needTurns = 2;
            $needMetal = 18000;
            $needCrystal = 11000;
            $needDeut = 7000;
            if ($turns < $needTurns) {
                $pageActionStatus = 'Armory refit failed: insufficient action turns.';
            } elseif ((int)$res->metal < $needMetal || (int)$res->crystal < $needCrystal || (int)$res->deuterium < $needDeut) {
                $pageActionStatus = 'Armory refit failed: insufficient strategic resources.';
            } else {
                $s->query("UPDATE player_resources SET metal=metal-" . $needMetal . ", crystal=crystal-" . $needCrystal . ", deuterium=deuterium-" . $needDeut . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . $needTurns . ") WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE shipyard SET dock_efficiency=LEAST(40, dock_efficiency+1) WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE military_command_state SET readiness_index=LEAST(100, readiness_index+3) WHERE uid=" . $uid . " LIMIT 1");
                $pageActionStatus = 'Armory refit complete: dock efficiency improved and readiness increased.';
            }
        }

        if ($cmd === 'mil_training_surge') {
            $needTurns = 1;
            $needNaq = 120000;
            if ($turns < $needTurns) {
                $pageActionStatus = 'Training surge failed: insufficient action turns.';
            } elseif ((int)$bankObj->onHand < $needNaq) {
                $pageActionStatus = 'Training surge failed: insufficient Naquadah.';
            } else {
                $crewGain = 850;
                $s->query("UPDATE bank SET onHand=onHand-" . $needNaq . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . $needTurns . ") WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE units SET untrained=untrained+" . $crewGain . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE military_command_state SET logistics_posture='surge', readiness_index=LEAST(100, readiness_index+2) WHERE uid=" . $uid . " LIMIT 1");
                $pageActionStatus = 'Training surge complete: +' . fnum($crewGain) . ' untrained units ready for specialization.';
            }
        }

        if ($cmd === 'mil_fleet_wargame') {
            $needTurns = 3;
            $needDeut = 9500;
            $needFood = 6000;
            if ($turns < $needTurns) {
                $pageActionStatus = 'Fleet war-game failed: insufficient action turns.';
            } elseif ((int)$res->deuterium < $needDeut || (int)$res->food < $needFood) {
                $pageActionStatus = 'Fleet war-game failed: insufficient deuterium/food.';
            } else {
                $s->query("UPDATE player_resources SET deuterium=deuterium-" . $needDeut . ", food=food-" . $needFood . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . $needTurns . ") WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE military_command_state SET war_games=war_games+1, readiness_index=LEAST(100, readiness_index+5), navy_focus='aggressive' WHERE uid=" . $uid . " LIMIT 1");
                $pageActionStatus = 'Fleet war-game successful: navy focus shifted to aggressive and readiness increased.';
            }
        }

        if ($cmd === 'mil_setfocus_aggressive' || $cmd === 'mil_setfocus_balanced' || $cmd === 'mil_setfocus_defensive') {
            $focus = 'balanced';
            if ($cmd === 'mil_setfocus_aggressive') {
                $focus = 'aggressive';
            }
            if ($cmd === 'mil_setfocus_defensive') {
                $focus = 'defensive';
            }
            $s->query("UPDATE military_command_state SET navy_focus='" . $focus . "' WHERE uid=" . $uid . " LIMIT 1");
            $pageActionStatus = 'Navy focus updated to ' . ucfirst($focus) . '.';
        }

        if ($cmd === 'mil_recruit_troop') {
            $qty = max(1, min(500, $troopPickQty));
            $troopMeta = $troopById[$troopPickId] ?? null;
            if ($troopMeta === null) {
                $pageActionStatus = 'Troop recruitment failed: troop profile not found.';
            } else {
                $pageActionStatus = militaryRecruitApply($s, $uid, $troopMeta, $qty);
            }
        }

        if ($cmd === 'mil_queue_recruit') {
            $qty = max(1, min(500, $troopPickQty));
            $troopMeta = $troopById[$troopPickId] ?? null;
            if ($troopMeta === null) {
                $pageActionStatus = 'Recruitment queue failed: troop profile not found.';
            } else {
                $queuedCountQ = $s->query("SELECT COUNT(*) AS c FROM military_troop_queue WHERE uid=" . $uid . " AND status='queued'");
                $queuedCount = $queuedCountQ ? (int)($queuedCountQ->fetch_object()->c ?? 0) : 0;
                if ($queuedCount >= 60) {
                    $pageActionStatus = 'Recruitment queue failed: queue is at capacity (60 queued batches).';
                } else {
                $eta = max(120, (int)(120 + ($qty * 8) + ((int)$troopMeta['tier'] * 18)));
                $prioQ = $s->query("SELECT COALESCE(MAX(priority_order), 0) AS p FROM military_troop_queue WHERE uid=" . $uid . "");
                $nextPrio = $prioQ ? ((int)($prioQ->fetch_object()->p ?? 0) + 1) : 1;
                $s->query("INSERT INTO military_troop_queue (uid, troop_id, quantity, priority_order, eta_seconds, status) VALUES (" . $uid . ", " . (int)$troopPickId . ", " . $qty . ", " . $nextPrio . ", " . $eta . ", 'queued')");
                militaryQueueNormalizePriorities($s, $uid);
                $pageActionStatus = 'Recruitment queued: ' . fnum($qty) . 'x ' . (string)$troopMeta['troop_name'] . ' (ETA ' . fnum($eta) . 's).';
                }
            }
        }

        if ($cmd === 'mil_queue_process') {
            $queueQ = $s->query("SELECT queue_id, troop_id, quantity, eta_seconds, priority_order, UNIX_TIMESTAMP(created_at) AS created_ts
                FROM military_troop_queue
                WHERE uid=" . $uid . " AND status='queued'
                ORDER BY priority_order ASC, queue_id ASC LIMIT 1");
            $qItem = $queueQ ? $queueQ->fetch_object() : null;
            if (!$qItem) {
                $pageActionStatus = 'Queue process: no queued troop batches found.';
            } else {
                $elapsed = max(0, time() - (int)$qItem->created_ts);
                if ($elapsed < (int)$qItem->eta_seconds) {
                    $remain = (int)$qItem->eta_seconds - $elapsed;
                    $pageActionStatus = 'Queue process: batch still in training (' . fnum($remain) . 's remaining).';
                } else {
                    $troopMeta = $troopById[(int)$qItem->troop_id] ?? null;
                    if ($troopMeta === null) {
                        $s->query("UPDATE military_troop_queue SET status='failed', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                        $pageActionStatus = 'Queue process failed: troop profile missing.';
                    } else {
                        $applyResult = militaryRecruitApply($s, $uid, $troopMeta, (int)$qItem->quantity);
                        if (strpos($applyResult, 'Troop recruitment complete:') === 0) {
                            $s->query("UPDATE military_troop_queue SET status='done', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                            $pageActionStatus = 'Queue process complete: ' . $applyResult;
                        } else {
                            $s->query("UPDATE military_troop_queue SET status='failed', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                            $pageActionStatus = 'Queue process failed: ' . $applyResult;
                        }
                    }
                }
            }
        }

        if ($cmd === 'mil_queue_process_all') {
            $sync = militaryQueueProcessReady($s, $uid, $troopById, 25);
            militaryQueueNormalizePriorities($s, $uid);
            $pageActionStatus = 'Queue process all: processed ' . fnum((int)$sync['processed']) . ', waiting ' . fnum((int)$sync['waiting']) . ', failed ' . fnum((int)$sync['failed']) . '.';
        }

        if ($cmd === 'mil_queue_cancel') {
            if ($troopQueueId <= 0) {
                $pageActionStatus = 'Queue cancel failed: invalid queue id.';
            } else {
                $rowQ = $s->query("SELECT status FROM military_troop_queue WHERE queue_id=" . $troopQueueId . " AND uid=" . $uid . " LIMIT 1");
                $row = $rowQ ? $rowQ->fetch_object() : null;
                if (!$row) {
                    $pageActionStatus = 'Queue cancel failed: queue batch not found.';
                } elseif ((string)$row->status !== 'queued') {
                    $pageActionStatus = 'Queue cancel skipped: batch is already ' . h((string)$row->status) . '.';
                } else {
                    $s->query("UPDATE military_troop_queue SET status='cancelled', completed_at=NOW() WHERE queue_id=" . $troopQueueId . " AND uid=" . $uid . " LIMIT 1");
                    militaryQueueNormalizePriorities($s, $uid);
                    $pageActionStatus = 'Queue batch #' . fnum($troopQueueId) . ' cancelled.';
                }
            }
        }

        if ($cmd === 'mil_queue_cancel_all') {
            $countQ = $s->query("SELECT COUNT(*) AS c FROM military_troop_queue WHERE uid=" . $uid . " AND status='queued'");
            $cancelCount = $countQ ? (int)($countQ->fetch_object()->c ?? 0) : 0;
            if ($cancelCount <= 0) {
                $pageActionStatus = 'Queue cancel-all skipped: no queued batches found.';
            } else {
                $s->query("UPDATE military_troop_queue SET status='cancelled', completed_at=NOW() WHERE uid=" . $uid . " AND status='queued'");
                militaryQueueNormalizePriorities($s, $uid);
                $pageActionStatus = 'Queue cancel-all complete: ' . fnum($cancelCount) . ' queued batches cancelled.';
            }
        }

        if ($cmd === 'mil_queue_retry') {
            if ($troopQueueId <= 0) {
                $pageActionStatus = 'Queue retry failed: invalid queue id.';
            } else {
                $rowQ = $s->query("SELECT status FROM military_troop_queue WHERE queue_id=" . $troopQueueId . " AND uid=" . $uid . " LIMIT 1");
                $row = $rowQ ? $rowQ->fetch_object() : null;
                if (!$row) {
                    $pageActionStatus = 'Queue retry failed: queue batch not found.';
                } else {
                    $statusNow = (string)$row->status;
                    if ($statusNow !== 'failed' && $statusNow !== 'cancelled') {
                        $pageActionStatus = 'Queue retry skipped: only failed/cancelled batches can be retried.';
                    } else {
                        $prioQ = $s->query("SELECT COALESCE(MAX(priority_order), 0) AS p FROM military_troop_queue WHERE uid=" . $uid . "");
                        $nextPrio = $prioQ ? ((int)($prioQ->fetch_object()->p ?? 0) + 1) : 1;
                        $s->query("UPDATE military_troop_queue SET status='queued', created_at=NOW(), completed_at=NULL, priority_order=" . $nextPrio . " WHERE queue_id=" . $troopQueueId . " AND uid=" . $uid . " LIMIT 1");
                        militaryQueueNormalizePriorities($s, $uid);
                        $pageActionStatus = 'Queue batch #' . fnum($troopQueueId) . ' moved back to queued status.';
                    }
                }
            }
        }

        if ($cmd === 'mil_queue_clear_history') {
            $countQ = $s->query("SELECT COUNT(*) AS c FROM military_troop_queue WHERE uid=" . $uid . " AND status<>'queued'");
            $clearCount = $countQ ? (int)($countQ->fetch_object()->c ?? 0) : 0;
            $s->query("DELETE FROM military_troop_queue WHERE uid=" . $uid . " AND status<>'queued'");
            militaryQueueNormalizePriorities($s, $uid);
            $pageActionStatus = 'Queue history cleared: removed ' . fnum($clearCount) . ' completed/cancelled/failed rows.';
        }

        if ($cmd === 'mil_queue_up' || $cmd === 'mil_queue_down') {
            if ($troopQueueId <= 0) {
                $pageActionStatus = 'Queue priority update failed: invalid queue id.';
            } else {
                $selfQ = $s->query("SELECT queue_id, priority_order, status FROM military_troop_queue WHERE queue_id=" . $troopQueueId . " AND uid=" . $uid . " LIMIT 1");
                $self = $selfQ ? $selfQ->fetch_object() : null;
                if (!$self) {
                    $pageActionStatus = 'Queue priority update failed: queue batch not found.';
                } elseif ((string)$self->status !== 'queued') {
                    $pageActionStatus = 'Queue priority update skipped: batch is already ' . h((string)$self->status) . '.';
                } else {
                    $cmp = ($cmd === 'mil_queue_up') ? '<' : '>';
                    $dir = ($cmd === 'mil_queue_up') ? 'DESC' : 'ASC';
                    $adjQ = $s->query("SELECT queue_id, priority_order FROM military_troop_queue
                        WHERE uid=" . $uid . " AND status='queued' AND priority_order " . $cmp . " " . (int)$self->priority_order . "
                        ORDER BY priority_order " . $dir . ", queue_id " . $dir . " LIMIT 1");
                    $adj = $adjQ ? $adjQ->fetch_object() : null;
                    if (!$adj) {
                        $pageActionStatus = ($cmd === 'mil_queue_up') ? 'Queue batch is already highest priority.' : 'Queue batch is already lowest priority.';
                    } else {
                        $selfPrio = (int)$self->priority_order;
                        $adjPrio = (int)$adj->priority_order;
                        $s->query("UPDATE military_troop_queue SET priority_order=" . $adjPrio . " WHERE queue_id=" . (int)$self->queue_id . " AND uid=" . $uid . " LIMIT 1");
                        $s->query("UPDATE military_troop_queue SET priority_order=" . $selfPrio . " WHERE queue_id=" . (int)$adj->queue_id . " AND uid=" . $uid . " LIMIT 1");
                        militaryQueueNormalizePriorities($s, $uid);
                        $pageActionStatus = 'Queue priority updated for batch #' . fnum((int)$self->queue_id) . '.';
                    }
                }
            }
        }

        if ($sub === 'troops' && $cmd === '') {
            $sync = militaryQueueProcessReady($s, $uid, $troopById, 10);
            militaryQueueNormalizePriorities($s, $uid);
            if ((int)$sync['processed'] > 0 || (int)$sync['failed'] > 0) {
                $pageActionStatus = 'Auto queue sync: processed ' . fnum((int)$sync['processed']) . ', failed ' . fnum((int)$sync['failed']) . ', still waiting ' . fnum((int)$sync['waiting']) . '.';
            }
        }

        if ($cmd === 'mil_defense_harden') {
            $needTurns = 2;
            $needMetal = 16000;
            $needCrystal = 9000;
            if ($turns < $needTurns) {
                $pageActionStatus = 'Defense hardening failed: insufficient action turns.';
            } elseif ((int)$res->metal < $needMetal || (int)$res->crystal < $needCrystal) {
                $pageActionStatus = 'Defense hardening failed: insufficient metal/crystal.';
            } else {
                $s->query("UPDATE player_resources SET metal=metal-" . $needMetal . ", crystal=crystal-" . $needCrystal . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . $needTurns . ") WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE units SET defense=defense+120, anticovert=anticovert+45 WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE military_command_state SET defense_posture='fortified', readiness_index=LEAST(100, readiness_index+4) WHERE uid=" . $uid . " LIMIT 1");
                $pageActionStatus = 'Defense hardening complete: +120 defense and +45 anti-covert units integrated.';
            }
        }
    }
}

if ($main === 'operations' || strpos($cmd, 'ops_') === 0) {
    $s->query("CREATE TABLE IF NOT EXISTS operations_turn_queue (
        queue_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        uid INT NOT NULL,
        operation_code VARCHAR(30) NOT NULL,
        operation_label VARCHAR(80) NOT NULL,
        turn_cost INT NOT NULL DEFAULT 1,
        eta_seconds INT NOT NULL DEFAULT 180,
        reward_focus VARCHAR(30) NOT NULL DEFAULT 'mixed',
        priority_order INT NOT NULL DEFAULT 0,
        status VARCHAR(20) NOT NULL DEFAULT 'queued',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL DEFAULT NULL
    )");

    $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
    $operationsRtsTurnBalance = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
    $stateQ = $s->query("SELECT doctrine, tempo_mode, theater_level, command_xp, cycle_index, frontline_pressure, reserve_integrity, morale_index, UNIX_TIMESTAMP(last_cycle_at) AS last_cycle_ts
        FROM operations_rts_state WHERE uid=" . $uid . " LIMIT 1");
    $operationsRtsState = $stateQ ? $stateQ->fetch_object() : null;

    if (strpos($cmd, 'ops_') === 0) {
        if ($cmd === 'ops_set_doctrine_aggressive' || $cmd === 'ops_set_doctrine_balanced' || $cmd === 'ops_set_doctrine_defensive' || $cmd === 'ops_set_doctrine_covert') {
            $doctrine = 'balanced';
            if ($cmd === 'ops_set_doctrine_aggressive') {
                $doctrine = 'aggressive';
            }
            if ($cmd === 'ops_set_doctrine_defensive') {
                $doctrine = 'defensive';
            }
            if ($cmd === 'ops_set_doctrine_covert') {
                $doctrine = 'covert';
            }
            $s->query("UPDATE operations_rts_state SET doctrine='" . $doctrine . "' WHERE uid=" . $uid . " LIMIT 1");
            $pageActionStatus = 'RTS doctrine updated to ' . ucfirst($doctrine) . '.';
        }

        if ($cmd === 'ops_set_tempo_standard' || $cmd === 'ops_set_tempo_surge' || $cmd === 'ops_set_tempo_overwatch') {
            $tempo = 'standard';
            if ($cmd === 'ops_set_tempo_surge') {
                $tempo = 'surge';
            }
            if ($cmd === 'ops_set_tempo_overwatch') {
                $tempo = 'overwatch';
            }
            $s->query("UPDATE operations_rts_state SET tempo_mode='" . $tempo . "' WHERE uid=" . $uid . " LIMIT 1");
            $pageActionStatus = 'RTS tempo mode updated to ' . ucfirst($tempo) . '.';
        }

        if ($cmd === 'ops_queue_recon' || $cmd === 'ops_queue_assault' || $cmd === 'ops_queue_fortify' || $cmd === 'ops_queue_logistics' || $cmd === 'ops_queue_sabotage') {
            $opCode = str_replace('ops_queue_', '', $cmd);
            if (!isset($operationsRtsCatalog[$opCode])) {
                $pageActionStatus = 'RTS queue failed: operation code not recognized.';
            } else {
                $queuedCountQ = $s->query("SELECT COUNT(*) AS c FROM operations_turn_queue WHERE uid=" . $uid . " AND status='queued'");
                $queuedCount = $queuedCountQ ? (int)($queuedCountQ->fetch_object()->c ?? 0) : 0;
                if ($queuedCount >= 40) {
                    $pageActionStatus = 'RTS queue failed: queue is at capacity (40 queued operations).';
                } else {
                    $opCfg = $operationsRtsCatalog[$opCode];
                    $prioQ = $s->query("SELECT COALESCE(MAX(priority_order), 0) AS p FROM operations_turn_queue WHERE uid=" . $uid . "");
                    $nextPrio = $prioQ ? ((int)($prioQ->fetch_object()->p ?? 0) + 1) : 1;
                    $s->query("INSERT INTO operations_turn_queue (uid, operation_code, operation_label, turn_cost, eta_seconds, reward_focus, priority_order, status)
                        VALUES (" . $uid . ", '" . pageSafeToken($opCode) . "', '" . pageSafeToken((string)$opCfg['label']) . "', " . (int)$opCfg['turn_cost'] . ", " . (int)$opCfg['eta_seconds'] . ", 'mixed', " . $nextPrio . ", 'queued')");
                    operationsQueueNormalizePriorities($s, $uid);
                    $pageActionStatus = 'RTS queue updated: ' . (string)$opCfg['label'] . ' added with ETA ' . fnum((int)$opCfg['eta_seconds']) . 's.';
                }
            }
        }

        if ($cmd === 'ops_cycle_run') {
            $queueQ = $s->query("SELECT queue_id, operation_code, eta_seconds, priority_order, UNIX_TIMESTAMP(created_at) AS created_ts
                FROM operations_turn_queue
                WHERE uid=" . $uid . " AND status='queued'
                ORDER BY priority_order ASC, queue_id ASC LIMIT 1");
            $qItem = $queueQ ? $queueQ->fetch_object() : null;
            if (!$qItem) {
                $pageActionStatus = 'RTS cycle run: no queued operations found.';
            } else {
                $elapsed = max(0, time() - (int)$qItem->created_ts);
                if ($elapsed < (int)$qItem->eta_seconds) {
                    $remain = (int)$qItem->eta_seconds - $elapsed;
                    $pageActionStatus = 'RTS cycle run: next operation still preparing (' . fnum($remain) . 's remaining).';
                } elseif (!isset($operationsRtsCatalog[(string)$qItem->operation_code])) {
                    $s->query("UPDATE operations_turn_queue SET status='failed', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                    $pageActionStatus = 'RTS cycle run failed: operation profile missing.';
                } else {
                    $opCfg = $operationsRtsCatalog[(string)$qItem->operation_code];
                    $applyResult = operationsApplyCycleAction($s, $uid, $opCfg);
                    if (strpos($applyResult, 'RTS cycle complete:') === 0) {
                        $s->query("UPDATE operations_turn_queue SET status='done', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                        $pageActionStatus = $applyResult;
                    } else {
                        $s->query("UPDATE operations_turn_queue SET status='failed', completed_at=NOW() WHERE queue_id=" . (int)$qItem->queue_id . " AND uid=" . $uid . " LIMIT 1");
                        $pageActionStatus = $applyResult;
                    }
                }
            }
            operationsQueueNormalizePriorities($s, $uid);
        }

        if ($cmd === 'ops_cycle_run_all') {
            $sync = operationsQueueProcessReady($s, $uid, $operationsRtsCatalog, 12);
            operationsQueueNormalizePriorities($s, $uid);
            $pageActionStatus = 'RTS cycle run-all: processed ' . fnum((int)$sync['processed']) . ', waiting ' . fnum((int)$sync['waiting']) . ', failed ' . fnum((int)$sync['failed']) . '.';
        }

        if ($cmd === 'ops_queue_cancel') {
            if ($opsQueueId <= 0) {
                $pageActionStatus = 'RTS queue cancel failed: invalid queue id.';
            } else {
                $rowQ = $s->query("SELECT status FROM operations_turn_queue WHERE queue_id=" . $opsQueueId . " AND uid=" . $uid . " LIMIT 1");
                $row = $rowQ ? $rowQ->fetch_object() : null;
                if (!$row) {
                    $pageActionStatus = 'RTS queue cancel failed: queue row not found.';
                } elseif ((string)$row->status !== 'queued') {
                    $pageActionStatus = 'RTS queue cancel skipped: row is already ' . h((string)$row->status) . '.';
                } else {
                    $s->query("UPDATE operations_turn_queue SET status='cancelled', completed_at=NOW() WHERE queue_id=" . $opsQueueId . " AND uid=" . $uid . " LIMIT 1");
                    operationsQueueNormalizePriorities($s, $uid);
                    $pageActionStatus = 'RTS queue row #' . fnum($opsQueueId) . ' cancelled.';
                }
            }
        }

        if ($cmd === 'ops_queue_up' || $cmd === 'ops_queue_down') {
            if ($opsQueueId <= 0) {
                $pageActionStatus = 'RTS queue priority update failed: invalid queue id.';
            } else {
                $selfQ = $s->query("SELECT queue_id, priority_order, status FROM operations_turn_queue WHERE queue_id=" . $opsQueueId . " AND uid=" . $uid . " LIMIT 1");
                $self = $selfQ ? $selfQ->fetch_object() : null;
                if (!$self) {
                    $pageActionStatus = 'RTS queue priority update failed: queue row not found.';
                } elseif ((string)$self->status !== 'queued') {
                    $pageActionStatus = 'RTS queue priority update skipped: row is already ' . h((string)$self->status) . '.';
                } else {
                    $cmp = ($cmd === 'ops_queue_up') ? '<' : '>';
                    $dir = ($cmd === 'ops_queue_up') ? 'DESC' : 'ASC';
                    $adjQ = $s->query("SELECT queue_id, priority_order FROM operations_turn_queue
                        WHERE uid=" . $uid . " AND status='queued' AND priority_order " . $cmp . " " . (int)$self->priority_order . "
                        ORDER BY priority_order " . $dir . ", queue_id " . $dir . " LIMIT 1");
                    $adj = $adjQ ? $adjQ->fetch_object() : null;
                    if (!$adj) {
                        $pageActionStatus = ($cmd === 'ops_queue_up') ? 'RTS queue row is already highest priority.' : 'RTS queue row is already lowest priority.';
                    } else {
                        $selfPrio = (int)$self->priority_order;
                        $adjPrio = (int)$adj->priority_order;
                        $s->query("UPDATE operations_turn_queue SET priority_order=" . $adjPrio . " WHERE queue_id=" . (int)$self->queue_id . " AND uid=" . $uid . " LIMIT 1");
                        $s->query("UPDATE operations_turn_queue SET priority_order=" . $selfPrio . " WHERE queue_id=" . (int)$adj->queue_id . " AND uid=" . $uid . " LIMIT 1");
                        operationsQueueNormalizePriorities($s, $uid);
                        $pageActionStatus = 'RTS queue priority updated for row #' . fnum((int)$self->queue_id) . '.';
                    }
                }
            }
        }
    }

    if ($sub === 'rts' && $cmd === '') {
        $sync = operationsQueueProcessReady($s, $uid, $operationsRtsCatalog, 6);
        operationsQueueNormalizePriorities($s, $uid);
        if ((int)$sync['processed'] > 0 || (int)$sync['failed'] > 0) {
            $pageActionStatus = 'RTS auto-sync: processed ' . fnum((int)$sync['processed']) . ', failed ' . fnum((int)$sync['failed']) . ', still waiting ' . fnum((int)$sync['waiting']) . '.';
        }
    }

    $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
    $operationsRtsTurnBalance = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
    $stateQ = $s->query("SELECT doctrine, tempo_mode, theater_level, command_xp, cycle_index, frontline_pressure, reserve_integrity, morale_index, UNIX_TIMESTAMP(last_cycle_at) AS last_cycle_ts
        FROM operations_rts_state WHERE uid=" . $uid . " LIMIT 1");
    $operationsRtsState = $stateQ ? $stateQ->fetch_object() : null;
}

if ($main === 'universe' || strpos($cmd, 'uni_') === 0) {
    $fieldWorldIndex = $targetWorld > 0 ? $targetWorld : (int)$worldSlice['start'];
    $fieldWorldIndex = max(1, min((int)$uCfg['maxWorlds'], $fieldWorldIndex));
    $fieldTargetType = ($fieldTargetType === 'moon') ? 'moon' : 'planet';
    $selectedWorld = universeWorldByIndex($uid, $planets, $fieldWorldIndex, $uCfg);

    $seedWorlds = $worldSlice['rows'];
    $seedWorlds[] = $selectedWorld;
    universeSeedWorldCities($s, $uid, $seedWorlds);

    $moonCount = (int)($selectedWorld['moons'] ?? 0);

    $fieldBuildCatalog = [
        'habdome' => ['name' => 'Habitat Dome', 'turns' => 1, 'naq' => 18000, 'metal' => 12000, 'crystal' => 8000, 'deut' => 2000, 'food' => 1200, 'water' => 1200, 'power' => 12, 'pop' => 0],
        'foundry' => ['name' => 'Foundry Grid', 'turns' => 2, 'naq' => 26000, 'metal' => 20000, 'crystal' => 11000, 'deut' => 4000, 'food' => 900, 'water' => 900, 'power' => 18, 'pop' => 120],
        'reactor' => ['name' => 'Fusion Reactor Node', 'turns' => 2, 'naq' => 30000, 'metal' => 18000, 'crystal' => 17000, 'deut' => 6000, 'food' => 500, 'water' => 500, 'power' => -20, 'pop' => 80],
        'hydrolab' => ['name' => 'Hydro Lab', 'turns' => 1, 'naq' => 16000, 'metal' => 9000, 'crystal' => 9000, 'deut' => 2500, 'food' => 0, 'water' => 0, 'power' => 9, 'pop' => 60],
        'bastion' => ['name' => 'Bastion District', 'turns' => 2, 'naq' => 22000, 'metal' => 17000, 'crystal' => 9000, 'deut' => 3800, 'food' => 700, 'water' => 700, 'power' => 14, 'pop' => 110],
    ];

    if (strpos($cmd, 'uni_') === 0) {
        if ($cmd === 'uni_create_plague' || $cmd === 'uni_create_moon_plague' || $cmd === 'uni_create_biome_plague') {
            $targetType = 'planet';
            $moonNo = 0;
            $biomeName = (string)$selectedWorld['biome'];
            if ($cmd === 'uni_create_moon_plague') {
                $targetType = 'moon';
                $moonNo = max(1, $targetMoonNo > 0 ? $targetMoonNo : 1);
            } elseif ($cmd === 'uni_create_biome_plague') {
                $targetType = 'biome';
                $biomeName = (string)$selectedWorld['biome'];
            }
            $pageActionStatus = universeCreatePlague($s, $uid, $selectedWorld, $targetType, $moonNo, $biomeName);
        }

        if ($cmd === 'uni_create_water' || $cmd === 'uni_create_moon_water' || $cmd === 'uni_create_biome_water') {
            $targetType = 'planet';
            $moonNo = 0;
            $biomeName = (string)$selectedWorld['biome'];
            if ($cmd === 'uni_create_moon_water') {
                $targetType = 'moon';
                $moonNo = max(1, $targetMoonNo > 0 ? $targetMoonNo : 1);
            } elseif ($cmd === 'uni_create_biome_water') {
                $targetType = 'biome';
                $biomeName = (string)$selectedWorld['biome'];
            }
            $pageActionStatus = universeCreateWater($s, $uid, $selectedWorld, $targetType, $moonNo, $biomeName);
        }

        if ($cmd === 'uni_event_scan') {
            $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
            $turns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
            $resQ = $s->query("SELECT deuterium FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
            $res = $resQ ? $resQ->fetch_object() : (object)['deuterium' => 0];
            if ($turns < 1) {
                $pageActionStatus = 'Universe event scan failed: insufficient action turns.';
            } elseif ((int)$res->deuterium < 1800) {
                $pageActionStatus = 'Universe event scan failed: insufficient deuterium.';
            } else {
                $eventPool = [
                    ['name' => 'Aether Storm Corridor', 'type' => 'hazard', 'points' => 8],
                    ['name' => 'Derelict Gate Relay', 'type' => 'salvage', 'points' => 10],
                    ['name' => 'Rogue Raider Armada', 'type' => 'combat', 'points' => 12],
                    ['name' => 'Void Plague Quarantine', 'type' => 'relief', 'points' => 9],
                    ['name' => 'Ancient Signal Cascade', 'type' => 'intel', 'points' => 11],
                ];
                $pickSeed = abs(crc32((string)$uid . '|' . (string)time()));
                $pick = $eventPool[$pickSeed % count($eventPool)];
                $galaxyMax = max(1, (int)$uCfg['galaxies']);
                $galaxyNo = max(1, min($galaxyMax, $eventTargetGalaxy));
                $threatGain = 2 + ($pickSeed % 4);
                $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-1) WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE player_resources SET deuterium=deuterium-1800 WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE universe_event_state SET
                    current_event='" . pageSafeToken((string)$pick['name']) . "',
                    event_cycle=event_cycle+1,
                    threat_level=LEAST(100,threat_level+" . (int)$threatGain . "),
                    last_event_at=NOW()
                    WHERE uid=" . $uid . " LIMIT 1");
                $s->query("INSERT INTO universe_event_log (uid,galaxy_no,event_name,event_type,reward_points,resolution_status)
                    VALUES (" . $uid . "," . $galaxyNo . ",'" . pageSafeToken((string)$pick['name']) . "','" . pageSafeToken((string)$pick['type']) . "'," . (int)$pick['points'] . ",'open')");
                $pageActionStatus = 'Universe event detected in G' . fnum($galaxyNo) . ': ' . (string)$pick['name'] . '.';
            }
        }

        if ($cmd === 'uni_galaxy_raid_trial') {
            $galaxyNo = max(1, (int)($_GET['g'] ?? 1));
            $systemNo = max(1, (int)($_GET['s'] ?? 1));
            $raidProfile = formalGalaxyRaidProfile($galaxyNo, $systemNo, max(0, min(15, (int)($evt->threat_level ?? 0))));
            $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
            $turns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
            $resQ = $s->query("SELECT deuterium FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
            $res = $resQ ? $resQ->fetch_object() : (object)['deuterium' => 0];
            if ($turns < (int)$raidProfile['turns']) {
                $pageActionStatus = 'Galaxy raid trial failed: insufficient action turns.';
            } elseif ((int)$res->deuterium < 3200) {
                $pageActionStatus = 'Galaxy raid trial failed: insufficient deuterium.';
            } else {
                $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . (int)$raidProfile['turns'] . ") WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE player_resources SET deuterium=deuterium-3200 WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE bank SET onHand=onHand+" . (int)$raidProfile['reward'] . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE universe_event_state SET event_points=event_points+" . (int)$raidProfile['reward'] . " WHERE uid=" . $uid . " LIMIT 1");
                $pageActionStatus = 'Galaxy raid trial launched against ' . h((string)$raidProfile['target']) . ' with risk ' . fnum((int)$raidProfile['risk']) . '% and reward ' . fnum((int)$raidProfile['reward']) . '.';
            }
        }

        if ($cmd === 'uni_event_resolve') {
            $eventQ = $s->query("SELECT event_id,event_name,reward_points FROM universe_event_log
                WHERE uid=" . $uid . " AND resolution_status='open'
                ORDER BY event_id ASC LIMIT 1");
            $event = $eventQ ? $eventQ->fetch_object() : null;
            if (!$event) {
                $pageActionStatus = 'Universe event resolve: no open events.';
            } else {
                $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
                $turns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
                $resQ = $s->query("SELECT food,water FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
                $res = $resQ ? $resQ->fetch_object() : (object)['food' => 0, 'water' => 0];
                if ($turns < 2) {
                    $pageActionStatus = 'Universe event resolve failed: insufficient action turns.';
                } elseif ((int)$res->food < 2200 || (int)$res->water < 2200) {
                    $pageActionStatus = 'Universe event resolve failed: insufficient food/water.';
                } else {
                    $rewardPoints = max(6, (int)$event->reward_points);
                    $naqReward = 65000 + ($rewardPoints * 1800);
                    $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-2) WHERE uid=" . $uid . " LIMIT 1");
                    $s->query("UPDATE player_resources SET food=food-2200, water=water-2200 WHERE uid=" . $uid . " LIMIT 1");
                    $s->query("UPDATE bank SET onHand=onHand+" . $naqReward . " WHERE uid=" . $uid . " LIMIT 1");
                    $s->query("UPDATE universe_event_state SET
                        event_points=event_points+" . $rewardPoints . ",
                        threat_level=GREATEST(0,threat_level-5),
                        current_event='Front Stabilized'
                        WHERE uid=" . $uid . " LIMIT 1");
                    $s->query("UPDATE universe_event_log SET resolution_status='resolved',resolved_at=NOW() WHERE event_id=" . (int)$event->event_id . " AND uid=" . $uid . " LIMIT 1");
                    $pageActionStatus = 'Universe event resolved: ' . (string)$event->event_name . ' (+'. fnum($rewardPoints) . ' event points).';
                }
            }
        }

        if ($cmd === 'uni_boss_spawn') {
            $bossQ = $s->query("SELECT status,boss_level FROM universe_world_boss WHERE uid=" . $uid . " LIMIT 1");
            $boss = $bossQ ? $bossQ->fetch_object() : null;
            $eventQ = $s->query("SELECT event_points,threat_level FROM universe_event_state WHERE uid=" . $uid . " LIMIT 1");
            $evt = $eventQ ? $eventQ->fetch_object() : (object)['event_points' => 0, 'threat_level' => 0];
            if ($boss && (string)$boss->status === 'active') {
                $pageActionStatus = 'World boss spawn skipped: a boss is already active.';
            } elseif ((int)$evt->event_points < 25) {
                $pageActionStatus = 'World boss spawn failed: need at least 25 event points.';
            } else {
                $nextLevel = max(1, (int)($boss->boss_level ?? 1));
                $hpMax = formalBossHp($nextLevel, (int)$evt->threat_level);
                $bossNames = ['Leviathan of Orion', 'Rift Tyrant', 'Abyssal Colossus', 'Gatebreaker Behemoth'];
                $pickName = $bossNames[abs(crc32((string)$uid . '|' . (string)$nextLevel)) % count($bossNames)];
                $s->query("UPDATE universe_world_boss SET
                    boss_name='" . pageSafeToken($pickName) . "',
                    boss_hp=" . $hpMax . ",
                    boss_hp_max=" . $hpMax . ",
                    status='active',
                    last_spawn_at=NOW()
                    WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE universe_event_state SET event_points=GREATEST(0,event_points-25) WHERE uid=" . $uid . " LIMIT 1");
                $pageActionStatus = 'World boss spawned: ' . $pickName . ' with ' . fnum($hpMax) . ' HP.';
            }
        }

        if ($cmd === 'uni_boss_attack') {
            $bossQ = $s->query("SELECT boss_name,boss_level,boss_hp,boss_hp_max,status FROM universe_world_boss WHERE uid=" . $uid . " LIMIT 1");
            $boss = $bossQ ? $bossQ->fetch_object() : null;
            if (!$boss || (string)$boss->status !== 'active' || (int)$boss->boss_hp <= 0) {
                $pageActionStatus = 'World boss attack failed: no active boss encounter.';
            } else {
                $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
                $turns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
                $unitQ = $s->query("SELECT attack,defense,covert,anticovert FROM units WHERE uid=" . $uid . " LIMIT 1");
                $unitsObj = $unitQ ? $unitQ->fetch_object() : (object)['attack' => 0, 'defense' => 0, 'covert' => 0, 'anticovert' => 0];
                $needTurns = 3;
                if ($turns < $needTurns) {
                    $pageActionStatus = 'World boss attack failed: insufficient action turns.';
                } else {
                    $damage = max(8000, (int)round(((int)$unitsObj->attack * 11) + ((int)$unitsObj->covert * 5) + ((int)$unitsObj->defense * 3) + ((int)$unitsObj->anticovert * 2)));
                    $damage = min($damage, (int)$boss->boss_hp);
                    $newHp = max(0, (int)$boss->boss_hp - $damage);
                    $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . $needTurns . ") WHERE uid=" . $uid . " LIMIT 1");
                    if ($newHp <= 0) {
                        $naqReward = 220000 + ((int)$boss->boss_level * 95000);
                        $xpReward = 45 + ((int)$boss->boss_level * 12);
                        $s->query("UPDATE universe_world_boss SET
                            status='defeated',
                            boss_hp=0,
                            boss_level=boss_level+1,
                            last_defeated_at=NOW()
                            WHERE uid=" . $uid . " LIMIT 1");
                        $s->query("UPDATE bank SET onHand=onHand+" . $naqReward . " WHERE uid=" . $uid . " LIMIT 1");
                        $s->query("UPDATE universe_event_state SET
                            event_points=event_points+" . $xpReward . ",
                            threat_level=GREATEST(0,threat_level-12),
                            current_event='Boss Front Cleared'
                            WHERE uid=" . $uid . " LIMIT 1");
                        $s->query("INSERT INTO universe_story_log (uid,act_no,chapter_no,entry_code,entry_text)
                            VALUES (" . $uid . ",1,1,'boss_defeat','Commander strike wing defeated the active world boss and stabilized the star lanes.')");
                        $pageActionStatus = 'World boss defeated: ' . (string)$boss->boss_name . ' eliminated. Rewards issued.';
                    } else {
                        $s->query("UPDATE universe_world_boss SET boss_hp=" . $newHp . " WHERE uid=" . $uid . " LIMIT 1");
                        $pageActionStatus = 'World boss hit confirmed: -' . fnum($damage) . ' HP on ' . (string)$boss->boss_name . '.';
                    }
                }
            }
        }

        if ($cmd === 'uni_story_unlock_prologue') {
            $storyQ = $s->query("SELECT prologue_unlocked FROM universe_story_progress WHERE uid=" . $uid . " LIMIT 1");
            $story = $storyQ ? $storyQ->fetch_object() : (object)['prologue_unlocked' => 0];
            if ((int)$story->prologue_unlocked === 1) {
                $pageActionStatus = 'Story prologue is already unlocked.';
            } else {
                $s->query("UPDATE universe_story_progress SET prologue_unlocked=1,last_story_at=NOW() WHERE uid=" . $uid . " LIMIT 1");
                $s->query("INSERT INTO universe_story_log (uid,act_no,chapter_no,entry_code,entry_text)
                    VALUES (" . $uid . ",0,0,'prologue_unlock','Prologue unlocked: the expedition council authorized the first interstellar response doctrine.')");
                $pageActionStatus = 'Story prologue unlocked.';
            }
        }

        if ($cmd === 'uni_story_advance') {
            $storyQ = $s->query("SELECT prologue_unlocked,current_act,current_chapter,chapter_points,completed_acts FROM universe_story_progress WHERE uid=" . $uid . " LIMIT 1");
            $story = $storyQ ? $storyQ->fetch_object() : null;
            $eventQ = $s->query("SELECT event_points FROM universe_event_state WHERE uid=" . $uid . " LIMIT 1");
            $evt = $eventQ ? $eventQ->fetch_object() : (object)['event_points' => 0];
            $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
            $turns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
            if (!$story || (int)$story->prologue_unlocked !== 1) {
                $pageActionStatus = 'Story advance failed: unlock prologue first.';
            } elseif ($turns < 1) {
                $pageActionStatus = 'Story advance failed: insufficient action turns.';
            } elseif ((int)$evt->event_points < 6) {
                $pageActionStatus = 'Story advance failed: insufficient event points.';
            } elseif ((int)$story->current_act > 12) {
                $pageActionStatus = 'Story campaign complete: all 12 acts are finished.';
            } else {
                $act = max(1, (int)$story->current_act);
                $chapter = max(1, (int)$story->current_chapter);
                $nextAct = $act;
                $nextChapter = $chapter + 1;
                $completedActs = (int)$story->completed_acts;
                if ($nextChapter > 3) {
                    $completedActs = max($completedActs, $act);
                    $nextAct = $act + 1;
                    $nextChapter = 1;
                }
                if ($nextAct > 12) {
                    $nextAct = 13;
                    $nextChapter = 1;
                }

                $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-1) WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE universe_event_state SET event_points=GREATEST(0,event_points-6) WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE universe_story_progress SET
                    current_act=" . $nextAct . ",
                    current_chapter=" . $nextChapter . ",
                    chapter_points=chapter_points+1,
                    completed_acts=" . $completedActs . ",
                    last_story_at=NOW()
                    WHERE uid=" . $uid . " LIMIT 1");

                $entryTitle = isset($universeStoryActs[$act]) ? $universeStoryActs[$act]['title'] : 'Campaign Finale';
                $entryText = 'Advanced story checkpoint in Act ' . $act . ': ' . $entryTitle . ' (Chapter ' . $chapter . ').';
                $s->query("INSERT INTO universe_story_log (uid,act_no,chapter_no,entry_code,entry_text)
                    VALUES (" . $uid . "," . $act . "," . $chapter . ",'chapter_advance','" . pageSafeToken($entryText) . "')");
                $pageActionStatus = 'Story progressed: Act ' . fnum($act) . ', Chapter ' . fnum($chapter) . ' complete.';
            }
        }

        if ($cmd === 'uni_story_log_victory' || $cmd === 'uni_story_log_discovery' || $cmd === 'uni_story_log_loss') {
            $storyQ = $s->query("SELECT current_act,current_chapter FROM universe_story_progress WHERE uid=" . $uid . " LIMIT 1");
            $story = $storyQ ? $storyQ->fetch_object() : (object)['current_act' => 1, 'current_chapter' => 1];
            $code = 'story_note';
            $text = 'Commander filed a campaign note.';
            if ($cmd === 'uni_story_log_victory') {
                $code = 'victory';
                $text = 'Victory log: fleet and ground detachments secured the objective and stabilized the sector.';
            }
            if ($cmd === 'uni_story_log_discovery') {
                $code = 'discovery';
                $text = 'Discovery log: scouts recovered an ancient data fragment linked to gate network anomalies.';
            }
            if ($cmd === 'uni_story_log_loss') {
                $code = 'loss';
                $text = 'Loss log: strike wing regrouped after attrition and marked the sector for reinforcement.';
            }
            $s->query("INSERT INTO universe_story_log (uid,act_no,chapter_no,entry_code,entry_text)
                VALUES (" . $uid . "," . (int)$story->current_act . "," . (int)$story->current_chapter . ",'" . $code . "','" . pageSafeToken($text) . "')");
            $pageActionStatus = 'Story log recorded: ' . ucfirst($code) . '.';
        }

        if ($cmd === 'uni_city_found') {
            $targetType = ($fieldTargetType === 'moon') ? 'moon' : 'planet';
            $moonNo = ($targetType === 'moon') ? max(1, $targetMoonNo) : 0;
            if ($targetType === 'moon' && $moonNo > $moonCount) {
                $pageActionStatus = 'City founding failed: moon index is out of range for this world.';
            } else {
                $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
                $turns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
                $bankQ = $s->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
                $bankObj = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];
                if ($turns < 1) {
                    $pageActionStatus = 'City founding failed: insufficient action turns.';
                } elseif ((int)$bankObj->onHand < 42000) {
                    $pageActionStatus = 'City founding failed: insufficient Naquadah.';
                } else {
                    $cityName = ($targetType === 'planet')
                        ? ('City-' . $fieldWorldIndex . '-' . strtoupper(substr((string)$selectedWorld['biome'], 0, 3)))
                        : ('MoonCity-' . $fieldWorldIndex . '-' . $moonNo . '-' . strtoupper(substr((string)$selectedWorld['moonBiome'], 0, 3)));
                    $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-1) WHERE uid=" . $uid . " LIMIT 1");
                    $s->query("UPDATE bank SET onHand=onHand-42000 WHERE uid=" . $uid . " LIMIT 1");
                    $s->query("UPDATE universe_colony_profiles SET city_name='" . pageSafeToken($cityName) . "' WHERE uid=" . $uid . " AND world_index=" . $fieldWorldIndex . " AND target_type='" . $targetType . "' AND moon_no=" . $moonNo . " LIMIT 1");
                    $pageActionStatus = 'City founded: ' . $cityName . ' established on ' . ucfirst($targetType) . ' zone.';
                }
            }
        }

        if ($cmd === 'uni_field_expand') {
            $targetType = ($fieldTargetType === 'moon') ? 'moon' : 'planet';
            $moonNo = ($targetType === 'moon') ? max(1, $targetMoonNo) : 0;
            $profileQ = $s->query("SELECT field_total,infrastructure_tier FROM universe_colony_profiles WHERE uid=" . $uid . " AND world_index=" . $fieldWorldIndex . " AND target_type='" . $targetType . "' AND moon_no=" . $moonNo . " LIMIT 1");
            $profile = $profileQ ? $profileQ->fetch_object() : null;
            if (!$profile) {
                $pageActionStatus = 'Field expansion failed: colony profile missing.';
            } else {
                $tier = max(1, (int)$profile->infrastructure_tier);
                $needTurns = 2;
                $needNaq = 26000 + ($tier * 12000);
                $needMetal = 18000 + ($tier * 9000);
                $needCrystal = 12000 + ($tier * 7000);
                $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
                $turns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
                $bankQ = $s->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
                $bankObj = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];
                $resQ = $s->query("SELECT metal,crystal FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
                $res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0];
                if ($turns < $needTurns) {
                    $pageActionStatus = 'Field expansion failed: insufficient action turns.';
                } elseif ((int)$bankObj->onHand < $needNaq || (int)$res->metal < $needMetal || (int)$res->crystal < $needCrystal) {
                    $pageActionStatus = 'Field expansion failed: insufficient Naquadah/metal/crystal.';
                } else {
                    $addFields = ($targetType === 'planet') ? 3 : 2;
                    $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . $needTurns . ") WHERE uid=" . $uid . " LIMIT 1");
                    $s->query("UPDATE bank SET onHand=onHand-" . $needNaq . " WHERE uid=" . $uid . " LIMIT 1");
                    $s->query("UPDATE player_resources SET metal=metal-" . $needMetal . ", crystal=crystal-" . $needCrystal . " WHERE uid=" . $uid . " LIMIT 1");
                    $s->query("UPDATE universe_colony_profiles SET field_total=field_total+" . $addFields . ", infrastructure_tier=infrastructure_tier+1 WHERE uid=" . $uid . " AND world_index=" . $fieldWorldIndex . " AND target_type='" . $targetType . "' AND moon_no=" . $moonNo . " LIMIT 1");
                    $pageActionStatus = 'Field expansion complete: +' . fnum($addFields) . ' build fields unlocked.';
                }
            }
        }

        if ($cmd === 'uni_field_build') {
            $targetType = ($fieldTargetType === 'moon') ? 'moon' : 'planet';
            $moonNo = ($targetType === 'moon') ? max(1, $targetMoonNo) : 0;
            if (!isset($fieldBuildCatalog[$fieldBuildCode])) {
                $pageActionStatus = 'Field build failed: unknown building blueprint.';
            } else {
                $profileQ = $s->query("SELECT field_total,field_used FROM universe_colony_profiles WHERE uid=" . $uid . " AND world_index=" . $fieldWorldIndex . " AND target_type='" . $targetType . "' AND moon_no=" . $moonNo . " LIMIT 1");
                $profile = $profileQ ? $profileQ->fetch_object() : null;
                if (!$profile) {
                    $pageActionStatus = 'Field build failed: colony profile missing.';
                } elseif ((int)$profile->field_used >= (int)$profile->field_total) {
                    $pageActionStatus = 'Field build failed: no free fields. Expand first.';
                } else {
                    $cfg = $fieldBuildCatalog[$fieldBuildCode];
                    $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
                    $turns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
                    $bankQ = $s->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
                    $bankObj = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];
                    $resQ = $s->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
                    $res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'food' => 0, 'water' => 0, 'population' => 0];
                    if ($turns < (int)$cfg['turns']) {
                        $pageActionStatus = 'Field build failed: insufficient action turns.';
                    } elseif ((int)$bankObj->onHand < (int)$cfg['naq']) {
                        $pageActionStatus = 'Field build failed: insufficient Naquadah.';
                    } elseif ((int)$res->metal < (int)$cfg['metal'] || (int)$res->crystal < (int)$cfg['crystal'] || (int)$res->deuterium < (int)$cfg['deut'] || (int)$res->food < (int)$cfg['food'] || (int)$res->water < (int)$cfg['water'] || (int)$res->population < (int)$cfg['pop']) {
                        $pageActionStatus = 'Field build failed: insufficient resources/population.';
                    } else {
                        $nextSlot = ((int)$profile->field_used) + 1;
                        $s->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . (int)$cfg['turns'] . ") WHERE uid=" . $uid . " LIMIT 1");
                        $s->query("UPDATE bank SET onHand=onHand-" . (int)$cfg['naq'] . " WHERE uid=" . $uid . " LIMIT 1");
                        $s->query("UPDATE player_resources SET
                            metal=metal-" . (int)$cfg['metal'] . ",
                            crystal=crystal-" . (int)$cfg['crystal'] . ",
                            deuterium=deuterium-" . (int)$cfg['deut'] . ",
                            food=food-" . (int)$cfg['food'] . ",
                            water=water-" . (int)$cfg['water'] . ",
                            population=GREATEST(0,population-" . (int)$cfg['pop'] . ")
                            WHERE uid=" . $uid . " LIMIT 1");
                        $s->query("INSERT INTO universe_colony_fields
                            (uid,world_index,target_type,moon_no,slot_no,building_code,building_name,building_level,power_draw,population_use)
                            VALUES (
                                " . $uid . ",
                                " . $fieldWorldIndex . ",
                                '" . $targetType . "',
                                " . $moonNo . ",
                                " . $nextSlot . ",
                                '" . pageSafeToken($fieldBuildCode) . "',
                                '" . pageSafeToken((string)$cfg['name']) . "',
                                1,
                                " . (int)$cfg['power'] . ",
                                " . (int)$cfg['pop'] . "
                            )");
                        $s->query("UPDATE universe_colony_profiles SET field_used=field_used+1 WHERE uid=" . $uid . " AND world_index=" . $fieldWorldIndex . " AND target_type='" . $targetType . "' AND moon_no=" . $moonNo . " LIMIT 1");
                        $pageActionStatus = 'Field build complete: ' . (string)$cfg['name'] . ' placed in slot #' . fnum($nextSlot) . '.';
                    }
                }
            }
        }
    }

    $eventStateQ = $s->query("SELECT event_cycle,current_event,event_points,threat_level,UNIX_TIMESTAMP(last_event_at) AS last_event_ts
        FROM universe_event_state WHERE uid=" . $uid . " LIMIT 1");
    $universeEventState = $eventStateQ ? $eventStateQ->fetch_object() : null;
    $bossStateQ = $s->query("SELECT boss_name,boss_level,boss_hp,boss_hp_max,status,UNIX_TIMESTAMP(last_spawn_at) AS last_spawn_ts,UNIX_TIMESTAMP(last_defeated_at) AS last_defeat_ts
        FROM universe_world_boss WHERE uid=" . $uid . " LIMIT 1");
    $universeBossState = $bossStateQ ? $bossStateQ->fetch_object() : null;
    $storyStateQ = $s->query("SELECT prologue_unlocked,current_act,current_chapter,chapter_points,completed_acts,UNIX_TIMESTAMP(last_story_at) AS last_story_ts
        FROM universe_story_progress WHERE uid=" . $uid . " LIMIT 1");
    $universeStoryState = $storyStateQ ? $storyStateQ->fetch_object() : null;

    $profilesQ = $s->query("SELECT world_index,target_type,moon_no,world_type,biome,sub_biome,city_name,district_focus,field_total,field_used,infrastructure_tier
        FROM universe_colony_profiles
        WHERE uid=" . $uid . " AND world_index=" . $fieldWorldIndex . "
        ORDER BY target_type ASC, moon_no ASC");
    if ($profilesQ) {
        while ($pr = $profilesQ->fetch_assoc()) {
            $universeColonyProfiles[] = $pr;
        }
    }

    $fieldsQ = $s->query("SELECT field_id,world_index,target_type,moon_no,slot_no,building_code,building_name,building_level,power_draw,population_use,UNIX_TIMESTAMP(created_at) AS created_ts
        FROM universe_colony_fields
        WHERE uid=" . $uid . " AND world_index=" . $fieldWorldIndex . "
        ORDER BY target_type ASC, moon_no ASC, slot_no ASC LIMIT 60");
    if ($fieldsQ) {
        while ($fr = $fieldsQ->fetch_assoc()) {
            $universeColonyFields[] = $fr;
        }
    }
}

if ($main === 'research') {
    $ogameCatalog = ogameTechCatalog();
    $ogameCatalogByKey = [];
    foreach ($ogameCatalog as $og) {
        $ogameCatalogByKey[$og['key']] = $og;
    }
    $ogameLevels = ogameTechEnsureTables($s, $uid, $ogameCatalog);

    $infraLevels = [
        'research_campus' => 0,
        'data_vault' => 0,
        'simulation_core' => 0,
        'quantum_archive' => 0,
        'ai_directorate' => 0,
    ];
    $infraHas = $s->query("SHOW TABLES LIKE 'research_infrastructure'");
    if ($infraHas && $infraHas->num_rows > 0) {
        $infraQ = $s->query("SELECT research_campus, data_vault, simulation_core, quantum_archive, ai_directorate FROM research_infrastructure WHERE uid=" . (int)$uid . " LIMIT 1");
        if ($infraQ && $infraQ->num_rows > 0) {
            $i = $infraQ->fetch_object();
            foreach (array_keys($infraLevels) as $infraKey) {
                $infraLevels[$infraKey] = (int)($i->$infraKey ?? 0);
            }
        }
    }
    $infraCostDiscount = min(45.0, ($infraLevels['data_vault'] * 1.5) + ($infraLevels['quantum_archive'] * 1.0) + ($infraLevels['ai_directorate'] * 0.5));
    $infraResearchSpeed = 1 + (($infraLevels['research_campus'] * 0.03) + ($infraLevels['simulation_core'] * 0.015) + ($infraLevels['ai_directorate'] * 0.02));

    if ($cmd === 'ogame_research') {
        $researchKey = isset($_GET['key']) ? preg_replace('/[^a-z0-9_]/', '', strtolower((string)$_GET['key'])) : '';
        $pageActionStatus = ogameResearchAction($s, $uid, $researchKey, $ogameCatalogByKey, $ogameLevels, $resourceHub['current'] ?? [], $bank, $infraCostDiscount);
        $ogameLevels = ogameTechEnsureTables($s, $uid, $ogameCatalog);
        $resourceHub = resourceEnsureAndTick($s, $uid, $baseData, $planets, $techView);
        $bank = $s->bank();
    }
}

if (($main === 'research' && $sub === 'blueprints') || ($main === 'universe' && $sub === 'seeds') || strpos($cmd, 'bp_') === 0 || $cmd === 'seed_bookmark') {
    $bpBuildQ = $s->query("SELECT blueprint_id,bp_name,hull_class,bp_kind,target_key,tier,copy_cost,base_metal,base_crystal,base_deuterium,base_turns,base_power
        FROM blueprint_catalog WHERE bp_kind='building' ORDER BY tier ASC, blueprint_id ASC");
    if ($bpBuildQ) {
        while ($r = $bpBuildQ->fetch_assoc()) {
            $blueprintBuildingCatalog[(int)$r['blueprint_id']] = [
                'name' => (string)$r['bp_name'],
                'hull_class' => (string)$r['hull_class'],
                'target_key' => (string)$r['target_key'],
                'tier' => (int)$r['tier'],
                'copy_cost' => (int)$r['copy_cost'],
                'base_metal' => (int)$r['base_metal'],
                'base_crystal' => (int)$r['base_crystal'],
                'base_deuterium' => (int)$r['base_deuterium'],
                'base_turns' => (int)$r['base_turns'],
                'base_power' => (int)$r['base_power'],
            ];
        }
    }

    foreach ($blueprintCatalog as $id => $bp) {
        if (isset($blueprintBuildingCatalog[(int)$id])) { continue; }
        $s->query("INSERT IGNORE INTO player_blueprints (uid, blueprint_id) VALUES (" . $uid . ", " . (int)$id . ")");
    }

    if (strpos($cmd, 'bp_') === 0 && (isset($blueprintCatalog[$bpId]) || isset($blueprintBuildingCatalog[$bpId]))) {
        $bp = isset($blueprintCatalog[$bpId]) ? $blueprintCatalog[$bpId] : $blueprintBuildingCatalog[$bpId];
        $isFieldBuilding = !isset($blueprintCatalog[$bpId]);
        $resQ = $s->query("SELECT metal,crystal,deuterium FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
        $resObj = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0];
        $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
        $turnObj = $turnQ ? $turnQ->fetch_object() : (object)['actionTurns' => 0];
        $bankObj = $bank ?: (object)['onHand' => 0];
        $bpRowQ = $s->query("SELECT owned_copies, me_level, te_level, run_count FROM player_blueprints WHERE uid=" . $uid . " AND blueprint_id=" . $bpId . " LIMIT 1");
        $bpRow = $bpRowQ ? $bpRowQ->fetch_object() : (object)['owned_copies' => 0, 'me_level' => 0, 'te_level' => 0, 'run_count' => 0];

        if ($cmd === 'bp_acquire') {
            $copyCost = (int)$bp['copy_cost'];
            if ((int)$bankObj->onHand < $copyCost) {
                $pageActionStatus = 'Blueprint acquisition failed: insufficient Naquadah.';
            } else {
                $s->query("UPDATE bank SET onHand=onHand-" . $copyCost . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE player_blueprints SET owned_copies=owned_copies+1 WHERE uid=" . $uid . " AND blueprint_id=" . $bpId . " LIMIT 1");
                $pageActionStatus = 'Blueprint acquired: ' . (string)$bp['name'] . '.';
            }
        }

        if ($cmd === 'bp_research') {
            $mode = ($bpMode === 'te') ? 'te' : 'me';
            $curLevel = (int)(($mode === 'me') ? $bpRow->me_level : $bpRow->te_level);
            $nextLevel = $curLevel + 1;
            $costM = (int)round(((int)$bp['base_metal'] * 0.60) * pow(1.22, $curLevel));
            $costC = (int)round(((int)$bp['base_crystal'] * 0.65) * pow(1.24, $curLevel));
            $costD = (int)round(((int)$bp['base_deuterium'] * 0.70) * pow(1.20, $curLevel));
            $turnCost = max(2, (int)ceil(((int)$bp['base_turns'] * $nextLevel) / 2));

            if ((int)$bpRow->owned_copies < 1) {
                $pageActionStatus = 'Blueprint research failed: acquire a copy first.';
            } elseif ((int)$turnObj->actionTurns < $turnCost) {
                $pageActionStatus = 'Blueprint research failed: insufficient action turns.';
            } elseif ((int)$resObj->metal < $costM || (int)$resObj->crystal < $costC || (int)$resObj->deuterium < $costD) {
                $pageActionStatus = 'Blueprint research failed: insufficient strategic resources.';
            } else {
                $s->query("UPDATE player_resources SET metal=metal-" . $costM . ", crystal=crystal-" . $costC . ", deuterium=deuterium-" . $costD . " WHERE uid=" . $uid . " LIMIT 1");
                $s->query("UPDATE userdata SET actionTurns=GREATEST(0, actionTurns-" . $turnCost . ") WHERE uid=" . $uid . " LIMIT 1");
                if ($mode === 'me') {
                    $s->query("UPDATE player_blueprints SET me_level=me_level+1 WHERE uid=" . $uid . " AND blueprint_id=" . $bpId . " LIMIT 1");
                    $pageActionStatus = 'Material Efficiency upgraded for ' . (string)$bp['name'] . ' to ME ' . $nextLevel . '.';
                } else {
                    $s->query("UPDATE player_blueprints SET te_level=te_level+1 WHERE uid=" . $uid . " AND blueprint_id=" . $bpId . " LIMIT 1");
                    $pageActionStatus = 'Time Efficiency upgraded for ' . (string)$bp['name'] . ' to TE ' . $nextLevel . '.';
                }
            }
        }

        if ($cmd === 'bp_build') {
            if ($isFieldBuilding) {
                $pageActionStatus = 'Manufacturing failed: field building blueprints unlock construction in the Colony Grid, not fleet hangar production.';
            } else {
                $qty = max(1, min(200, $bpQty));
                $costs = blueprintOrderCosts($bp, $qty, (int)$bpRow->me_level, (int)$bpRow->te_level);
                if ((int)$bpRow->owned_copies < 1) {
                    $pageActionStatus = 'Manufacturing failed: blueprint copy not owned.';
                } elseif ((int)$turnObj->actionTurns < (int)$costs['turns']) {
                    $pageActionStatus = 'Manufacturing failed: insufficient action turns.';
                } elseif ((int)$resObj->metal < (int)$costs['metal'] || (int)$resObj->crystal < (int)$costs['crystal'] || (int)$resObj->deuterium < (int)$costs['deuterium']) {
                    $pageActionStatus = 'Manufacturing failed: insufficient resources.';
                } else {
                    $hull = preg_replace('/[^A-Za-z0-9 _-]/', '', (string)$bp['hull']);
                    $s->query("UPDATE player_resources SET metal=metal-" . (int)$costs['metal'] . ", crystal=crystal-" . (int)$costs['crystal'] . ", deuterium=deuterium-" . (int)$costs['deuterium'] . " WHERE uid=" . $uid . " LIMIT 1");
                    $s->query("UPDATE userdata SET actionTurns=GREATEST(0, actionTurns-" . (int)$costs['turns'] . ") WHERE uid=" . $uid . " LIMIT 1");
                    $s->query("INSERT INTO blueprint_hangar (uid, blueprint_id, hull_class, quantity, total_power)
                        VALUES (" . $uid . ", " . $bpId . ", '" . $hull . "', " . $qty . ", " . (int)$costs['power'] . ")
                        ON DUPLICATE KEY UPDATE quantity=quantity+" . $qty . ", total_power=total_power+" . (int)$costs['power']);
                    $s->query("UPDATE player_blueprints SET run_count=run_count+" . $qty . " WHERE uid=" . $uid . " AND blueprint_id=" . $bpId . " LIMIT 1");
                    $pageActionStatus = 'Manufacturing complete: ' . fnum($qty) . 'x ' . (string)$bp['name'] . ' added to blueprint hangar.';
                }
            }
        }
    }

    if ($main === 'universe' && $cmd === 'seed_bookmark' && $targetWorld > 0) {
        $seedSys = universeSeedSystem($uid, $targetWorld);
        $note = preg_replace('/[^A-Za-z0-9 _:-]/', '', $seedSys['star'] . ' | ' . $seedSys['biome']);
        $seedKey = preg_replace('/[^A-Za-z0-9-]/', '', (string)$seedSys['seedKey']);
        $s->query("INSERT IGNORE INTO universe_seed_bookmarks (uid, seed_index, seed_key, note)
            VALUES (" . $uid . ", " . $targetWorld . ", '" . $seedKey . "', '" . $note . "')");
        $pageActionStatus = 'Universe seed bookmarked: #' . $targetWorld . ' (' . $seedSys['seedKey'] . ').';
    }

    $ownedQ = $s->query("SELECT blueprint_id, owned_copies, me_level, te_level, run_count FROM player_blueprints WHERE uid=" . $uid);
    if ($ownedQ) {
        while ($r = $ownedQ->fetch_assoc()) {
            $blueprintOwned[(int)$r['blueprint_id']] = [
                'owned_copies' => (int)$r['owned_copies'],
                'me_level' => (int)$r['me_level'],
                'te_level' => (int)$r['te_level'],
                'run_count' => (int)$r['run_count'],
            ];
        }
    }

    $hangarQ = $s->query("SELECT blueprint_id, quantity, total_power FROM blueprint_hangar WHERE uid=" . $uid);
    if ($hangarQ) {
        while ($r = $hangarQ->fetch_assoc()) {
            $blueprintHangar[(int)$r['blueprint_id']] = ['quantity' => (int)$r['quantity'], 'total_power' => (int)$r['total_power']];
        }
    }

    if ($main === 'universe' && $sub === 'seeds') {
        $seedSlice = universeSeedSlice($uid, $requestedPage, 25);
        $bookQ = $s->query("SELECT seed_index, seed_key, note, created_at FROM universe_seed_bookmarks WHERE uid=" . $uid . " ORDER BY seed_index ASC LIMIT 40");
        if ($bookQ) {
            while ($r = $bookQ->fetch_assoc()) {
                $seedBookmarks[] = $r;
            }
        }
    }
}

$title = $mainTitles[$main];
$subTitle = $subLabels[$main][$sub];

echo '<div class="page-hub page-hub-shell">';
echo '<div class="page-hub-head">';
echo '<div class="page-hub-copy">';
echo '<h3>' . h($title) . ' - ' . h($subTitle) . '</h3>';
echo '<p>Universe Civilization: Empire at Wars command view • Page: ' . h($main) . ' / ' . h($sub) . ' | Player: ' . h($_SESSION['username']) . '</p>';
echo '</div>';
echo '<div class="page-hub-badge">' . h(ucfirst($main)) . ' / ' . h(ucfirst($sub)) . '</div>';
echo '</div>';
if ($universeActionStatus !== '') {
    echo '<div class="card full page-status"><strong>' . h($universeActionStatus) . '</strong></div>';
}
if ($pageActionStatus !== '') {
    echo '<div class="card full page-status"><strong>' . h($pageActionStatus) . '</strong></div>';
}

echo '<div class="page-subnav-title">Sub Pages</div>';
echo '<div class="page-subnav">';
foreach ($subLabels[$main] as $subKey => $subName) {
    $activeClass = ($subKey === $sub) ? ' class="active"' : '';
    echo '<a' . $activeClass . ' href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'' . h($main) . '\',\'' . h($subKey) . '\'); return false">' . h($subName) . '</a>';
}
echo '</div>';

$featureButtons = [
    'empire' => [
        ['label' => 'Base', 'js' => "sendData('base','get','mainDisplay'); return false"],
        ['label' => 'Progress', 'js' => "sendData('progress','get','mainDisplay'); return false"],
        ['label' => 'Bank', 'js' => "sendData('bank','get','mainDisplay'); return false"],
        ['label' => 'Research', 'js' => "sendData('pages','get','research','tree'); return false"],
        ['label' => 'Logistics Hub', 'js' => "sendData('pages','get','empire','logistics'); return false"],
    ],
    'military' => [
        ['label' => 'Armory', 'js' => "sendData('armory','get','mainDisplay'); return false"],
        ['label' => 'Artillery', 'js' => "sendData('artillery','get','mainDisplay'); return false"],
        ['label' => 'Training', 'js' => "sendData('train','get','mainDisplay'); return false"],
        ['label' => 'Troop Catalog', 'js' => "sendData('pages','get','military','troops'); return false"],
        ['label' => 'Fleet Dock', 'js' => "sendData('fleetdock','get','mainDisplay'); return false"],
        ['label' => 'Navy Ops', 'js' => "sendData('pages','get','military','navy'); return false"],
        ['label' => 'Mega Forge', 'js' => "sendData('megaforge','get','mainDisplay'); return false"],
        ['label' => 'Stations', 'js' => "sendData('stations','get','mainDisplay'); return false"],
        ['label' => 'Hyperspace', 'js' => "sendData('hyperspace','get','mainDisplay'); return false"],
    ],
    'operations' => [
        ['label' => 'Targets', 'js' => "sendData('rank','get','mainDisplay'); return false"],
        ['label' => 'Spy', 'js' => "sendData('spy','get','mainDisplay'); return false"],
        ['label' => 'Combat Logs', 'js' => "sendData('logs','get','mainDisplay'); return false"],
        ['label' => 'Action Reports', 'js' => "sendData('actionLogs','get','mainDisplay'); return false"],
        ['label' => 'RTS Turn System', 'js' => "sendData('pages','get','operations','rts'); return false"],
        ['label' => 'Command Queue', 'js' => "sendData('pages','get','operations','commandqueue'); return false"],
    ],
    'economy' => [
        ['label' => 'Bank', 'js' => "sendData('bank','get','mainDisplay'); return false"],
        ['label' => 'Market', 'js' => "sendData('market','get','mainDisplay'); return false"],
        ['label' => 'Resource HQ', 'js' => "sendData('resourcehq','get','mainDisplay'); return false"],
        ['label' => 'OGame Buildings', 'js' => "sendData('ogamebuildings','get','mainDisplay'); return false"],
        ['label' => 'Store', 'js' => "sendData('pages','get','economy','store'); return false"],
        ['label' => 'Battle Pass', 'js' => "sendData('pages','get','economy','battlepass'); return false"],
        ['label' => 'Season Pass', 'js' => "sendData('pages','get','economy','seasonpass'); return false"],
        ['label' => 'Supply Logistics', 'js' => "sendData('pages','get','economy','logistics'); return false"],
        ['label' => 'Technology', 'js' => "sendData('technology','get','mainDisplay'); return false"],
        ['label' => 'Empire Tech', 'js' => "sendData('stargatetech','get','mainDisplay'); return false"],
    ],
    'diplomacy' => [
        ['label' => 'Messages', 'js' => "sendData('messages','get','mainDisplay'); return false"],
        ['label' => 'Alliance', 'js' => "sendData('ally_mlist','get','mainDisplay'); return false"],
        ['label' => 'Relations', 'js' => "sendData('pages','get','diplomacy','relations'); return false"],
        ['label' => 'Treaties', 'js' => "sendData('pages','get','diplomacy','treaties'); return false"],
        ['label' => 'Commander Systems', 'js' => "sendData('commandergov','get','mainDisplay'); return false"],
    ],
    'intel' => [
        ['label' => 'Rankings', 'js' => "sendData('rank','get','mainDisplay'); return false"],
        ['label' => 'Reports', 'js' => "sendData('actionLogs','get','mainDisplay'); return false"],
        ['label' => 'Signal Watch', 'js' => "sendData('pages','get','intel','signals'); return false"],
        ['label' => 'Spy', 'js' => "sendData('spy','get','mainDisplay'); return false"],
    ],
    'community' => [
        ['label' => 'Forums', 'js' => "window.open('forums/','_blank'); return false"],
        ['label' => 'Updates', 'js' => "sendData('faq','get','mainDisplay'); return false"],
        ['label' => 'Contact', 'js' => "sendData('messages','get','mainDisplay'); return false"],
    ],
    'help' => [
        ['label' => 'Guide', 'js' => "sendData('pages','get','help','newplayer'); return false"],
        ['label' => 'Mechanics', 'js' => "sendData('pages','get','help','mechanics'); return false"],
        ['label' => 'Glossary', 'js' => "sendData('pages','get','help','glossary'); return false"],
        ['label' => 'Troubleshooting', 'js' => "sendData('pages','get','help','troubleshooting'); return false"],
    ],
    'universe' => [
        ['label' => 'Galaxy Map', 'js' => "sendData('pages','get','universe','galaxies'); return false"],
        ['label' => 'Transit Lanes', 'js' => "sendData('pages','get','universe','lanes'); return false"],
        ['label' => 'Universe Events', 'js' => "sendData('pages','get','universe','events'); return false"],
        ['label' => 'World Boss', 'js' => "sendData('pages','get','universe','worldboss'); return false"],
        ['label' => 'Story Campaign', 'js' => "sendData('pages','get','universe','story'); return false"],
        ['label' => 'Stations', 'js' => "sendData('stations','get','mainDisplay'); return false"],
        ['label' => 'Hyperspace', 'js' => "sendData('hyperspace','get','mainDisplay'); return false"],
        ['label' => 'Expedition', 'js' => "sendData('pages','get','universe','expedition'); return false"],
    ],
    'research' => [
        ['label' => 'Research Tree', 'js' => "sendData('pages','get','research','tree'); return false"],
        ['label' => 'Technology Tree', 'js' => "sendData('pages','get','research','techlib'); return false"],
        ['label' => 'Tech Library Buildings', 'js' => "sendData('techlib','get','mainDisplay'); return false"],
        ['label' => 'Projects', 'js' => "sendData('pages','get','research','projects'); return false"],
        ['label' => 'Classes', 'js' => "sendData('pages','get','research','classes'); return false"],
        ['label' => 'Talents', 'js' => "sendData('pages','get','research','talents'); return false"],
        ['label' => 'Empire Tech', 'js' => "sendData('stargatetech','get','mainDisplay'); return false"],
    ],
];

if (isset($featureButtons[$main]) && count($featureButtons[$main]) > 0) {
    echo '<div class="page-subnav-title">Feature Actions</div>';
    echo '<div class="page-subnav feature-subnav">';
    foreach ($featureButtons[$main] as $btn) {
        echo '<a href="javascript:void(0)" onclick="' . h($btn['js']) . '">' . h($btn['label']) . '</a>';
    }
    echo '</div>';
}

$subPageGroups = [
    'empire' => [
        'Command Home' => [
            ['home', 'Empire Home'],
            ['overview', 'Operations Overview'],
            ['command', 'Command Structure'],
        ],
        'Empire Layers' => [
            ['logistics', 'Logistics Hub'],
            ['doctrine', 'Doctrine Board'],
        ],
    ],
    'military' => [
        'Command Layers' => [
            ['personnel', 'Personnel'],
            ['troops', 'Troop Catalog'],
            ['fleet', 'Fleet'],
            ['navy', 'Navy Ops'],
            ['defensegrid', 'Defense Grid'],
        ],
    ],
    'operations' => [
        'Mission Routing' => [
            ['attack', 'Attack Missions'],
            ['raid', 'Raid Missions'],
            ['spy', 'Spy Network'],
            ['rts', 'RTS Turn System'],
            ['commandqueue', 'Command Queue'],
            ['diplomacyops', 'Diplomatic Ops'],
        ],
    ],
    'economy' => [
        'Economic Layers' => [
            ['banking', 'Banking'],
            ['resources', 'Resource Hub'],
            ['store', 'In-Game Store'],
            ['battlepass', 'Battle Pass'],
            ['seasonpass', 'Season Pass'],
            ['logistics', 'Supply Logistics'],
            ['treasury', 'Treasury Policy'],
        ],
    ],
    'diplomacy' => [
        'Diplomacy Layers' => [
            ['relations', 'Relations'],
            ['governance', 'Commander Governance'],
            ['treaties', 'Treaties'],
            ['councils', 'Councils'],
        ],
    ],
    'intel' => [
        'Intelligence Layers' => [
            ['rankings', 'Rankings'],
            ['reports', 'Battle Reports'],
            ['signals', 'Signal Watch'],
            ['dossiers', 'Target Dossiers'],
        ],
    ],
    'community' => [
        'Community Layers' => [
            ['forums', 'Forums'],
            ['events', 'Events'],
            ['academy', 'Academy'],
        ],
    ],
    'help' => [
        'Help Layers' => [
            ['newplayer', 'New Player'],
            ['mechanics', 'Mechanics'],
            ['troubleshooting', 'Troubleshooting'],
            ['hotkeys', 'Quick Commands'],
        ],
    ],
    'universe' => [
        'Universe Layers' => [
            ['galaxies', 'Galaxies'],
            ['planets', 'Planets & Moons'],
            ['lanes', 'Transit Lanes'],
            ['anomalies', 'Anomaly Index'],
            ['events', 'Universe Events'],
            ['worldboss', 'World Boss'],
            ['story', 'Story Campaign'],
        ],
    ],
    'research' => [
        'Research Layers' => [
            ['tree', 'Research Tree'],
            ['techlib', 'Technology Tree'],
            ['projects', 'Projects'],
            ['labs', 'Lab Network'],
        ],
    ],
];

if (isset($subPageGroups[$main])) {
    echo '<div class="card full"><h4>Sub Menu Groups</h4>';
    foreach ($subPageGroups[$main] as $groupTitle => $groupItems) {
        echo '<p><strong>' . h($groupTitle) . ':</strong></p>';
        echo '<div class="page-subnav feature-subnav">';
        foreach ($groupItems as $item) {
            echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'' . h($main) . '\',\'' . h($item[0]) . '\'); return false">' . h($item[1]) . '</a>';
        }
        echo '</div>';
    }
    echo '</div>';
}

echo '<div class="page-grid">';

if ($main === 'empire' && $sub === 'home') {
    $armySize = (int)($userStats->armySize ?? 0);
    $treasury = (int)($bank->onHand ?? 0);
    $income = (int)($baseData->income ?? 0);
    $up = (int)($baseData->up ?? 0);
    $planetCount = count($planets);
    $turnQ = $s->query("SELECT actionTurns FROM userdata WHERE uid=" . (int)$uid . " LIMIT 1");
    $actionTurns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
    $reservePressure = formalResourcePressure($income, $treasury);
    $readiness = formalReadinessIndex($armySize, $up, $planetCount, $treasury, max(1, (int)$uCfg['maxColonies']));
    $colonyCap = max(1, (int)$uCfg['maxColonies']);
    $colonyUsage = (int)round(($planetCount / $colonyCap) * 100);
    $warPosture = ($readiness >= 62 || ($armySize >= 180000 && $up >= 260));
    $postureLabel = $warPosture ? 'War Posture' : 'Growth Posture';
    $critTurns = $warPosture ? 10 : 6;
    $warnTurns = $warPosture ? 18 : 12;
    $critReserve = $warPosture ? 6 : 4;
    $warnReserve = $warPosture ? 10 : 7;
    $critReadiness = $warPosture ? 48 : 35;
    $warnReadiness = $warPosture ? 66 : 52;
    $warnColonyUsage = $warPosture ? 85 : 93;
    $warnUpFloor = $warPosture ? 260 : 150;
    $foodFloor = $warPosture ? max(22000, (int)$resourceHub['rates']['food'] * 4) : max(14000, (int)$resourceHub['rates']['food'] * 3);
    $waterFloor = $warPosture ? max(22000, (int)$resourceHub['rates']['water'] * 4) : max(14000, (int)$resourceHub['rates']['water'] * 3);
    $energyFloor = $warPosture ? max(22000, (int)$resourceHub['rates']['energy'] * 4) : max(14000, (int)$resourceHub['rates']['energy'] * 3);
    $alerts = [];

    if ($actionTurns <= $critTurns) {
        $alerts[] = ['level' => 'Critical', 'message' => 'Low action turns (' . fnum($actionTurns) . '). Prioritize turn-efficient moves.'];
    } elseif ($actionTurns <= $warnTurns) {
        $alerts[] = ['level' => 'Warning', 'message' => 'Action turns are tightening (' . fnum($actionTurns) . '). Queue only high-value actions.'];
    }

    if ($reservePressure <= $critReserve) {
        $alerts[] = ['level' => 'Critical', 'message' => 'Reserve runway is short (' . fnum($reservePressure) . ' turns). Stabilize treasury before escalation.'];
    } elseif ($reservePressure <= $warnReserve) {
        $alerts[] = ['level' => 'Warning', 'message' => 'Reserve runway is moderate (' . fnum($reservePressure) . ' turns). Limit optional spending.'];
    }

    if ((int)$resourceHub['current']['food'] < $foodFloor || (int)$resourceHub['current']['water'] < $waterFloor || (int)$resourceHub['current']['energy'] < $energyFloor) {
        $alerts[] = ['level' => 'Critical', 'message' => 'Food/Water/Energy buffers are low. Population stability risk is rising.'];
    }

    if ($up < $warnUpFloor) {
        $alerts[] = ['level' => 'Warning', 'message' => 'Unit production is under tactical pace (' . fnum($up) . '/turn). Consider progression and infrastructure upgrades.'];
    }

    if ($colonyUsage >= $warnColonyUsage) {
        $alerts[] = ['level' => 'Warning', 'message' => 'Colony capacity is near cap (' . fnum($planetCount) . '/' . fnum($colonyCap) . '). Plan expansion unlocks.'];
    }

    if ($readiness <= $critReadiness) {
        $alerts[] = ['level' => 'Critical', 'message' => 'Readiness is low (' . fnum($readiness) . '%). Avoid multi-front operations until stabilized.'];
    } elseif ($readiness <= $warnReadiness) {
        $alerts[] = ['level' => 'Warning', 'message' => 'Readiness is moderate (' . fnum($readiness) . '%). Use selective operations and preserve reserves.'];
    }

    if (count($alerts) === 0) {
        $alerts[] = ['level' => 'Stable', 'message' => 'All core command indicators are stable. Safe window for growth or offensive planning.'];
    }

    echo '<div class="card full">';
    echo '<h4>Empire Command Alerts</h4>';
    echo '<table class="mini-table" border="0" width="100%">';
    echo '<tr><th align="left">Operational Posture</th><td>' . h($postureLabel) . '</td></tr>';
    echo '<tr><th align="left">Severity</th><th align="left">Alert</th></tr>';
    foreach ($alerts as $alert) {
        echo '<tr><td><strong>' . h($alert['level']) . '</strong></td><td>' . h($alert['message']) . '</td></tr>';
    }
    echo '</table>';
    echo '</div>';

    echo '<div class="card">';
    echo '<h4>Empire Command Snapshot</h4>';
    echo '<p><strong>Readiness Index:</strong> ' . fnum($readiness) . '%</p>';
    echo '<p><strong>Treasury On Hand:</strong> ' . fnum($treasury) . ' Naquadah</p>';
    echo '<p><strong>Army Size:</strong> ' . fnum($armySize) . '</p>';
    echo '<p><strong>Planets Controlled:</strong> ' . fnum($planetCount) . ' / ' . fnum((int)$uCfg['maxColonies']) . '</p>';
    echo '</div>';

    echo '<div class="card">';
    echo '<h4>Command Signals</h4>';
    echo '<p><strong>Income/Turn:</strong> ' . fnum($income) . '</p>';
    echo '<p><strong>Unit Production/Turn:</strong> ' . fnum($up) . '</p>';
    echo '<p><strong>Reserve Coverage:</strong> ' . fnum($reservePressure) . ' turns</p>';
    echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'empire\',\'overview\'); return false">Open Operations Overview</a></p>';
    echo '</div>';

    echo '<div class="card full">';
    echo '<h4>Empire Systems Home Matrix</h4>';
    echo '<table class="mini-table" border="0" width="100%">';
    echo '<tr><th align="left">System</th><th align="left">Current Detail</th><th align="left">Primary Action</th><th align="left">Information Focus</th></tr>';
    echo '<tr><td>Territory</td><td>' . fnum($planetCount) . ' colonies with moon and bonus metadata</td><td><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'empire\',\'planets\'); return false">Open Planets</a></td><td>Expansion slots, biome potential, moon utility</td></tr>';
    echo '<tr><td>Command</td><td>Commander chain, rank, and alliance posture</td><td><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'empire\',\'command\'); return false">Open Command</a></td><td>Diplomatic leverage and coordination tempo</td></tr>';
    echo '<tr><td>Progression</td><td>Growth pacing and upgrade sequencing</td><td><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'empire\',\'progress\'); return false">Open Progress</a></td><td>UP scaling, planet cap, economy acceleration</td></tr>';
    echo '<tr><td>Logistics</td><td>Resource routing and reserve floor management</td><td><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'empire\',\'logistics\'); return false">Open Logistics</a></td><td>War spend discipline and buffer policy</td></tr>';
    echo '<tr><td>Doctrine</td><td>War, economy, and intel posture alignment</td><td><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'empire\',\'doctrine\'); return false">Open Doctrine</a></td><td>Campaign stance and risk governance</td></tr>';
    echo '</table>';
    echo '</div>';

    echo '<div class="card full">';
    echo '<h4>Empire Home Information Board</h4>';
    echo '<p><strong>Operational Guidance:</strong> Use this home page to validate reserve health, force readiness, and expansion headroom before issuing attack, research, or build commands.</p>';
    echo '<p><strong>Recommended Loop:</strong> Home -> Logistics -> Military/Fleet -> Operations -> Home (re-check readiness).</p>';
    echo '</div>';
}

if ($main === 'empire' && $sub === 'overview') {
    echo '<div class="card"><h4>Empire Snapshot</h4>';
    echo '<p><strong>Army Size:</strong> ' . fnum($userStats->armySize ?? 0) . '</p>';
    echo '<p><strong>Treasury:</strong> ' . fnum($bank->onHand ?? 0) . ' Naquadah</p>';
    echo '<p><strong>Income/Turn:</strong> ' . fnum($baseData->income ?? 0) . '</p>';
    echo '<p><strong>Unit Production:</strong> ' . fnum($baseData->up ?? 0) . '</p>';
    echo '</div>';
    echo '<div class="card"><h4>Quick Actions</h4>';
    echo '<p><a href="javascript:void(0)" onclick="sendData(\'base\',\'get\',\'mainDisplay\'); return false">Open Base Module</a></p>';
    echo '<p><a href="javascript:void(0)" onclick="sendData(\'technology\',\'get\',\'mainDisplay\'); return false">Open Technology</a></p>';
    echo '<p><a href="javascript:void(0)" onclick="sendData(\'progress\',\'get\',\'mainDisplay\'); return false">Open Progress</a></p>';
    echo '</div>';

    echo '<div class="card full"><h4>Seven-Resource Command Stockpile</h4>';
    echo '<table class="mini-table" border="0" width="100%"><tr><th align="left">Resource</th><th align="left">Current</th><th align="left">Production / Turn</th></tr>';
    echo '<tr><td>Metal</td><td>' . fnum($resourceHub['current']['metal']) . '</td><td>' . fnum($resourceHub['rates']['metal']) . '</td></tr>';
    echo '<tr><td>Crystal</td><td>' . fnum($resourceHub['current']['crystal']) . '</td><td>' . fnum($resourceHub['rates']['crystal']) . '</td></tr>';
    echo '<tr><td>Deuterium</td><td>' . fnum($resourceHub['current']['deuterium']) . '</td><td>' . fnum($resourceHub['rates']['deuterium']) . '</td></tr>';
    echo '<tr><td>Food</td><td>' . fnum($resourceHub['current']['food']) . '</td><td>' . fnum($resourceHub['rates']['food']) . '</td></tr>';
    echo '<tr><td>Water</td><td>' . fnum($resourceHub['current']['water']) . '</td><td>' . fnum($resourceHub['rates']['water']) . '</td></tr>';
    echo '<tr><td>Population</td><td>' . fnum($resourceHub['current']['population']) . '</td><td>' . fnum($resourceHub['rates']['population']) . '</td></tr>';
    echo '<tr><td>Energy</td><td>' . fnum($resourceHub['current']['energy']) . '</td><td>' . fnum($resourceHub['rates']['energy']) . '</td></tr>';
    echo '</table></div>';
}

if ($main === 'empire' && $sub === 'planets') {
    echo '<div class="card full"><h4>Planet Registry</h4>';
    echo '<p><strong>Colonization Capacity:</strong> ' . fnum($uCfg['maxColonies']) . ' worlds | <strong>Moon Capacity:</strong> ' . fnum($uCfg['maxMoons']) . '</p>';
    echo '<p><strong>Owned Colonies:</strong> ' . fnum(count($planets)) . ' | <strong>Open Colony Slots:</strong> ' . fnum(max(0, $uCfg['maxColonies'] - count($planets))) . '</p>';
    if (count($planets) === 0) {
        echo '<p>No planets discovered in your registry yet.</p>';
    } else {
        echo '<table width="100%" border="0"><tr><th align="left">Planet</th><th align="left">Size</th><th align="left">Bonus</th><th align="left">Moons</th><th align="left">Moon Class</th><th align="left">World Slot</th></tr>';
        foreach ($planets as $idx => $planet) {
            $moonSeed = (($uid + 31) * 103 + (($idx + 1) * 17)) & 0x7fffffff;
            $moonCount = universeRand($moonSeed, 0, 3);
            $moonClass = $moonCount > 0 ? universePick($moonSeed, ['Rocky', 'Icy', 'Metallic', 'Ruined']) : '-';
            echo '<tr><td>' . h($planet['name']) . '</td><td>' . h($planet['size']) . '</td><td>' . h($planet['bonus']) . '</td><td>' . fnum($moonCount) . '</td><td>' . h($moonClass) . '</td><td>#' . fnum($idx + 1) . '</td></tr>';
        }
        echo '</table>';
    }
    echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'expedition\'); return false">Open Colonization Mission Control</a></p>';
    echo '</div>';
}

if ($main === 'empire' && $sub === 'command') {
    echo '<div class="card"><h4>Command Chain</h4>';
    echo '<p><strong>Commander:</strong> ' . h($userStats->cmdrName ?? 'None') . '</p>';
    echo '<p><strong>Title:</strong> ' . h(formalTitleDisplay((string)($userStats->title ?? 'Rookie Commander'), (string)($userStats->titleBand ?? 'Novice'), (int)($userStats->prestige ?? 0))) . '</p>';
    echo '<p><strong>Race:</strong> ' . h($userStats->race ?? '') . '</p>';
    echo '<p><strong>Rank:</strong> ' . h($userStats->rank ?? '') . '</p>';
    echo '</div>';
    echo '<div class="card"><h4>Diplomatic Actions</h4>';
    echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'diplomacy\',\'relations\'); return false">Manage Relations</a></p>';
    echo '<p><a href="javascript:void(0)" onclick="sendData(\'ally_mlist\',\'get\',\'mainDisplay\'); return false">Alliance Member List</a></p>';
    echo '</div>';
}

if ($main === 'empire' && $sub === 'progress') {
    echo '<div class="card"><h4>Progress Status</h4>';
    echo '<p>Track your expansion level, unit production growth, and military readiness across the four core growth pillars.</p>';
    echo '<p><a href="javascript:void(0)" onclick="sendData(\'progress\',\'get\',\'mainDisplay\'); return false">Open Progress Dashboard</a></p>';
    echo '</div>';
    echo '<div class="card full"><h4>Upgrade Priorities</h4>';
    echo '<table class="mini-table" border="0" width="100%">';
    echo '<tr><th align="left">Priority</th><th align="left">Action</th><th align="left">Outcome</th></tr>';
    echo '<tr><td>1</td><td>Increase Unit Production</td><td>More military velocity per turn cycle</td></tr>';
    echo '<tr><td>2</td><td>Expand Planet Capacity</td><td>Higher macro growth ceiling and colony slots</td></tr>';
    echo '<tr><td>3</td><td>Boost Economy/Turn</td><td>Sustained funding for war and expansion</td></tr>';
    echo '<tr><td>4</td><td>Raise Reserve Floor</td><td>Protection against operational lockouts</td></tr>';
    echo '</table></div>';
    echo '<div class="card"><h4>Growth Milestones</h4>';
    echo '<ul><li><strong>Early:</strong> stabilize resource lines and reach self-sustaining mining</li><li><strong>Mid:</strong> open interstellar lanes and second colony wave</li><li><strong>Late:</strong> transition to capital-ship fleet and full reserve posture</li></ul>';
    echo '</div>';
}

if ($main === 'empire' && $sub === 'logistics') {
    echo '<div class="card"><h4>Logistics Hub</h4><p>Route resources between economy, war, and expansion programs with a stable reserve floor.</p></div>';
    echo '<div class="card full"><h4>Supply Priorities</h4>';
    echo '<table class="mini-table" border="0" width="100%">';
    echo '<tr><th align="left">Lane</th><th align="left">Primary Feed</th><th align="left">Reserve Floor</th></tr>';
    echo '<tr><td>War Spend</td><td>Naquadah + metal war chest</td><td>35% of on-hand</td></tr>';
    echo '<tr><td>Economy Growth</td><td>Resource building upgrades</td><td>30% of income</td></tr>';
    echo '<tr><td>Expansion</td><td>Colony and expedition funding</td><td>15% of income</td></tr>';
    echo '<tr><td>Research</td><td>Strategic resource allocation</td><td>20% of income</td></tr>';
    echo '</table></div>';
    echo '<div class="card"><h4>Active Supply Links</h4><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'economy\',\'resources\'); return false">Resource Hub</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'economy\',\'logistics\'); return false">Supply Logistics</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'economy\',\'treasury\'); return false">Treasury Policy</a></p></div>';
}

if ($main === 'empire' && $sub === 'doctrine') {
    echo '<div class="card full"><h4>Doctrine Board</h4><p>Set command posture for conflict, growth, and intelligence in one synchronized board.</p><table class="mini-table" border="0" width="100%"><tr><th align="left">Track</th><th align="left">Current Focus</th><th align="left">Review Cadence</th></tr><tr><td>War</td><td>Fleet pressure with controlled risk</td><td>Per campaign phase</td></tr><tr><td>Economy</td><td>Compounding production and reserve retention</td><td>Per 30-min turn block</td></tr><tr><td>Intel</td><td>Scouting before commitment</td><td>Before each strike wave</td></tr></table></div>';
    echo '<div class="card"><h4>Doctrine Shift Warning</h4><p>Frequent posture changes waste tempo. Commit to a doctrine for at least one full turn cycle before re-evaluating.</p></div>';
    echo '<div class="card"><h4>Risk Governance</h4><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_set_doctrine_balanced\'); return false">Set Balanced Doctrine</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_set_doctrine_aggressive\'); return false">Set Aggressive Doctrine</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_set_doctrine_defensive\'); return false">Set Defensive Doctrine</a></p></div>';
}

if ($main === 'military') {
    $mStateQ = $s->query("SELECT readiness_index, drill_xp, navy_focus, defense_posture, logistics_posture, war_games FROM military_command_state WHERE uid=" . $uid . " LIMIT 1");
    $mState = $mStateQ ? $mStateQ->fetch_object() : (object)['readiness_index' => 50, 'drill_xp' => 0, 'navy_focus' => 'balanced', 'defense_posture' => 'standard', 'logistics_posture' => 'steady', 'war_games' => 0];

    echo '<div class="card full"><h4>Military Command Console</h4>';
    echo '<p><strong>Readiness:</strong> ' . fnum((int)$mState->readiness_index) . '% | <strong>Drill XP:</strong> ' . fnum((int)$mState->drill_xp) . ' | <strong>Navy Focus:</strong> ' . h((string)$mState->navy_focus) . ' | <strong>Defense Posture:</strong> ' . h((string)$mState->defense_posture) . ' | <strong>War Games:</strong> ' . fnum((int)$mState->war_games) . '</p>';
    echo '<div class="page-subnav feature-subnav">';
    echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'' . h($sub) . '&cmd=mil_personnel_drill\'); return false">Personnel Drill</a>';
    echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'' . h($sub) . '&cmd=mil_armory_refit\'); return false">Armory Refit</a>';
    echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'' . h($sub) . '&cmd=mil_training_surge\'); return false">Training Surge</a>';
    echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'' . h($sub) . '&cmd=mil_fleet_wargame\'); return false">Fleet War-Game</a>';
    echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'' . h($sub) . '&cmd=mil_defense_harden\'); return false">Defense Harden</a>';
    echo '</div>';
    echo '<p style="margin-top:8px;"><strong>Navy Focus Sub Buttons:</strong> '
        . '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'' . h($sub) . '&cmd=mil_setfocus_aggressive\'); return false">Aggressive</a> | '
        . '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'' . h($sub) . '&cmd=mil_setfocus_balanced\'); return false">Balanced</a> | '
        . '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'' . h($sub) . '&cmd=mil_setfocus_defensive\'); return false">Defensive</a></p>';
    echo '<p><label>Command Dropdown '
        . '<select id="milCommandDropdown">'
        . '<option value="mil_personnel_drill">Personnel Drill</option>'
        . '<option value="mil_armory_refit">Armory Refit</option>'
        . '<option value="mil_training_surge">Training Surge</option>'
        . '<option value="mil_fleet_wargame">Fleet War-Game</option>'
        . '<option value="mil_defense_harden">Defense Harden</option>'
        . '<option value="mil_setfocus_aggressive">Set Focus: Aggressive</option>'
        . '<option value="mil_setfocus_balanced">Set Focus: Balanced</option>'
        . '<option value="mil_setfocus_defensive">Set Focus: Defensive</option>'
        . '</select></label> '
        . '<a href="javascript:void(0)" onclick="(function(){var x=document.getElementById(\'milCommandDropdown\'); if(x){sendData(\'pages\',\'get\',\'military\',\'' . h($sub) . '&cmd=\'+x.value);} return false;})(); return false">Execute Command</a></p>';
    echo '</div>';

    if ($sub === 'personnel') {
        echo '<div class="card full"><h4>Personnel Breakdown</h4>';
        echo '<table width="100%" border="0">';
        echo '<tr><td>Untrained Units</td><td>' . fnum($personnel->uuCount ?? 0) . '</td></tr>';
        echo '<tr><td>Attack Units</td><td>' . fnum($personnel->attackCount ?? 0) . '</td></tr>';
        echo '<tr><td>Defense Units</td><td>' . fnum($personnel->defenseCount ?? 0) . '</td></tr>';
        echo '<tr><td>Covert Units</td><td>' . fnum($personnel->covertCount ?? 0) . '</td></tr>';
        echo '<tr><td>Anti-Covert Units</td><td>' . fnum($personnel->anticovertCount ?? 0) . '</td></tr>';
        echo '</table>';
        echo '<div class="page-subnav feature-subnav"><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'personnel&cmd=mil_personnel_drill\'); return false">Run Personnel Drill</a><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'training&cmd=mil_training_surge\'); return false">Run Training Surge</a></div>';
        echo '</div>';
    }
    if ($sub === 'troops') {
        $allTroops = militaryTroopCatalog();
        $classOptions = ['all' => 'All Classes'];
        $legionOptions = ['all' => 'All Legions'];
        foreach ($allTroops as $tt) {
            $ck = strtolower((string)$tt['class_name']);
            $lk = strtolower((string)$tt['legion_name']);
            if (!isset($classOptions[$ck])) {
                $classOptions[$ck] = (string)$tt['class_name'];
            }
            if (!isset($legionOptions[$lk])) {
                $legionOptions[$lk] = (string)$tt['legion_name'];
            }
        }
        if (!isset($classOptions[$troopClassFilter])) {
            $troopClassFilter = 'all';
        }
        if (!isset($legionOptions[$troopLegionFilter])) {
            $troopLegionFilter = 'all';
        }

        $filteredTroops = [];
        foreach ($allTroops as $tt) {
            $classMatch = ($troopClassFilter === 'all') || (strtolower((string)$tt['class_name']) === $troopClassFilter);
            $legionMatch = ($troopLegionFilter === 'all') || (strtolower((string)$tt['legion_name']) === $troopLegionFilter);
            if ($classMatch && $legionMatch) {
                $filteredTroops[] = $tt;
            }
        }

        $perPage = 24;
        $totalTroops = count($filteredTroops);
        $maxTroopPage = max(1, (int)ceil($totalTroops / $perPage));
        $troopPage = max(1, min($maxTroopPage, $troopPage));
        $startIndex = ($troopPage - 1) * $perPage;
        $sliceTroops = array_slice($filteredTroops, $startIndex, $perPage);

        echo '<div class="card full"><h4>240 Troop Rank, Title, and Attribute Library</h4>';
        echo '<p><strong>Total Troops:</strong> 240 | <strong>Classes:</strong> ' . fnum(count($classOptions) - 1) . ' | <strong>Legions:</strong> ' . fnum(count($legionOptions) - 1) . '</p>';
        echo '<p><strong>Filters:</strong> Class=' . h($classOptions[$troopClassFilter]) . ' | Legion=' . h($legionOptions[$troopLegionFilter]) . ' | Showing ' . fnum($startIndex + 1) . '-' . fnum(min($totalTroops, $startIndex + $perPage)) . ' of ' . fnum($totalTroops) . '</p>';
        echo '<div class="page-subnav feature-subnav">';
        foreach ($classOptions as $classKey => $className) {
            echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($classKey) . '&tclegion=' . h($troopLegionFilter) . '&tp=1\'); return false">' . h($className) . '</a>';
        }
        echo '</div>';
        echo '<div class="page-subnav feature-subnav">';
        foreach ($legionOptions as $legionKey => $legionName) {
            echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($legionKey) . '&tp=1\'); return false">' . h($legionName) . '</a>';
        }
        echo '</div>';
        echo '</div>';

        echo '<div class="card full"><h4>Troop Recruitment Control</h4>';
        echo '<p>Recruit selected troop profiles directly from untrained reserves into military corps using turns and resources.</p>';
        echo '<p><label>Troop Profile '
            . '<select id="troopRecruitSelect">';
        foreach ($sliceTroops as $tt) {
            echo '<option value="' . (int)$tt['troop_id'] . '">' . h((string)$tt['troop_code']) . ' - ' . h((string)$tt['troop_rank']) . ' - ' . h((string)$tt['troop_name']) . '</option>';
        }
        echo '</select></label> '
            . '<label>Quantity <input id="troopRecruitQty" type="number" min="1" max="500" value="25" style="width:80px;" /></label> '
            . '<a href="javascript:void(0)" onclick="(function(){var p=document.getElementById(\'troopRecruitSelect\');var q=document.getElementById(\'troopRecruitQty\');if(p&&q){var qv=parseInt(q.value,10);if(!qv||qv<1){qv=1;}if(qv>500){qv=500;}sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_recruit_troop&tpid=\'+p.value+\'&tqty=\'+qv);}return false;})(); return false">Recruit Instantly</a> | '
            . '<a href="javascript:void(0)" onclick="(function(){var p=document.getElementById(\'troopRecruitSelect\');var q=document.getElementById(\'troopRecruitQty\');if(p&&q){var qv=parseInt(q.value,10);if(!qv||qv<1){qv=1;}if(qv>500){qv=500;}sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_queue_recruit&tpid=\'+p.value+\'&tqty=\'+qv);}return false;})(); return false">Add To Queue</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_queue_process\'); return false">Process Next Ready Queue Batch</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_queue_process_all\'); return false">Process All Ready Queue Batches</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_queue_cancel_all\'); return false">Cancel All Queued Batches</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_queue_clear_history\'); return false">Clear Completed/Cancelled/Failed History</a></p>';
        echo '</div>';

        $queueRows = [];
        $queueQ = $s->query("SELECT queue_id, troop_id, quantity, priority_order, eta_seconds, status, UNIX_TIMESTAMP(created_at) AS created_ts
            FROM military_troop_queue
            WHERE uid=" . $uid . "
            ORDER BY status='queued' DESC, priority_order ASC, queue_id ASC LIMIT 12");
        if ($queueQ) {
            while ($qr = $queueQ->fetch_assoc()) {
                $queueRows[] = $qr;
            }
        }

        echo '<div class="card full"><h4>Recruitment Queue</h4>';
        if (count($queueRows) === 0) {
            echo '<p>No queued troop batches yet.</p>';
        } else {
            echo '<table class="mini-table" border="0" width="100%">';
            echo '<tr><th align="left">Queue ID</th><th align="left">Priority</th><th align="left">Troop</th><th align="left">Qty</th><th align="left">ETA</th><th align="left">Status</th><th align="left">Action</th></tr>';
            foreach ($queueRows as $qr) {
                $tId = (int)($qr['troop_id'] ?? 0);
                $tName = isset($troopById[$tId]) ? (string)$troopById[$tId]['troop_name'] : ('Troop #' . $tId);
                $prioNum = (int)($qr['priority_order'] ?? 0);
                $etaSec = (int)($qr['eta_seconds'] ?? 0);
                $createdTs = (int)($qr['created_ts'] ?? time());
                $elapsed = max(0, time() - $createdTs);
                $remaining = max(0, $etaSec - $elapsed);
                $statusName = (string)($qr['status'] ?? 'queued');
                $etaText = ($statusName === 'queued') ? (fnum($remaining) . 's') : '0s';
                echo '<tr>';
                echo '<td>#' . fnum((int)$qr['queue_id']) . '</td>';
                echo '<td>' . fnum($prioNum) . '</td>';
                echo '<td>' . h($tName) . '</td>';
                echo '<td>' . fnum((int)($qr['quantity'] ?? 0)) . '</td>';
                echo '<td>' . h($etaText) . '</td>';
                echo '<td>' . h($statusName) . '</td>';
                if ($statusName === 'queued') {
                    echo '<td><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_queue_up&tqid=' . (int)$qr['queue_id'] . '\'); return false">Up</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_queue_down&tqid=' . (int)$qr['queue_id'] . '\'); return false">Down</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_queue_cancel&tqid=' . (int)$qr['queue_id'] . '\'); return false">Cancel</a></td>';
                } elseif ($statusName === 'failed' || $statusName === 'cancelled') {
                    echo '<td><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_queue_retry&tqid=' . (int)$qr['queue_id'] . '\'); return false">Retry</a></td>';
                } else {
                    echo '<td>-</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</div>';

        echo '<div class="card full"><h4>Troop Matrix</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Image</th><th align="left">Code</th><th align="left">Name</th><th align="left">Rank & Title</th><th align="left">Class Tree</th><th align="left">Type Tree</th><th align="left">Stats</th><th align="left">Sub Stats / Attributes</th><th align="left">Action</th></tr>';
        foreach ($sliceTroops as $tt) {
            $statsText = 'PWR ' . fnum((int)$tt['power_stat'])
                . ' | ATK ' . fnum((int)$tt['attack_stat'])
                . ' | DEF ' . fnum((int)$tt['defense_stat'])
                . ' | COV ' . fnum((int)$tt['covert_stat'])
                . ' | A-COV ' . fnum((int)$tt['anti_covert_stat'])
                . ' | MOB ' . fnum((int)$tt['mobility_stat'])
                . ' | MOR ' . fnum((int)$tt['morale_stat'])
                . ' | LOG ' . fnum((int)$tt['logistics_stat']);
            $subText = 'TAC ' . fnum((int)$tt['tactic_substat'])
                . ' | RES ' . fnum((int)$tt['resilience_substat'])
                . ' | DIS ' . fnum((int)$tt['discipline_substat'])
                . ' | ' . h((string)$tt['attribute_primary']) . '/' . h((string)$tt['attribute_secondary'])
                . ' | SUB-A ' . fnum((int)$tt['sub_attribute_a'])
                . ' | SUB-B ' . fnum((int)$tt['sub_attribute_b']);
            echo '<tr>';
            echo '<td><img src="images/units/troops/' . h((string)$tt['troop_code']) . '.jpg" alt="' . h((string)$tt['troop_name']) . '" width="60" style="vertical-align: middle;" /></td>';
            echo '<td>' . h((string)$tt['troop_code']) . '</td>';
            echo '<td>' . h((string)$tt['troop_name']) . ' <br><small>Legion: ' . h((string)$tt['legion_name']) . ' | Tier ' . fnum((int)$tt['tier']) . '</small></td>';
            echo '<td>' . h((string)$tt['troop_rank']) . '<br><small>' . h((string)$tt['troop_title']) . '</small></td>';
            echo '<td>' . h((string)$tt['class_name']) . ' / ' . h((string)$tt['class_subclass']) . '</td>';
            echo '<td>' . h((string)$tt['troop_type']) . ' / ' . h((string)$tt['troop_subtype']) . '</td>';
            echo '<td>' . $statsText . '</td>';
            echo '<td>' . $subText . '</td>';
            echo '<td><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $troopPage . '&cmd=mil_recruit_troop&tpid=' . (int)$tt['troop_id'] . '&tqty=10\'); return false">Recruit x10</a></td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '<p><strong>Pages:</strong> ';
        for ($p = 1; $p <= $maxTroopPage; $p++) {
            $label = ($p === $troopPage) ? ('[' . $p . ']') : (string)$p;
            echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'troops&tcclass=' . h($troopClassFilter) . '&tclegion=' . h($troopLegionFilter) . '&tp=' . $p . '\'); return false">' . h($label) . '</a> ';
        }
        echo '</p>';
        echo '</div>';
    }
    if ($sub === 'armory') {
        echo '<div class="card"><h4>Armory Control</h4><p>Manage attack/defense equipment loadouts and repair weapons.</p><p><a href="javascript:void(0)" onclick="sendData(\'armory\',\'get\',\'mainDisplay\'); return false">Open Armory</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'armory&cmd=mil_armory_refit\'); return false">Run Armory Refit</a></p></div>';
        echo '<div class="card full"><h4>Loadout Guidance</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Mission Type</th><th align="left">Recommended Loadout</th><th align="left">Note</th></tr>';
        echo '<tr><td>Assault</td><td>Attack-weighted equipment</td><td>Maximize first-wave damage</td></tr>';
        echo '<tr><td>Defense</td><td>Defense-weighted equipment</td><td>Shrink raid windows</td></tr>';
        echo '<tr><td>Recon</td><td>Covert-weighted equipment</td><td>Improve spy penetration</td></tr>';
        echo '<tr><td>Endurance</td><td>Logistics-weighted equipment</td><td>Sustain long expedition chains</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Repair Discipline</h4><p>Run repairs between campaigns. Damaged equipment reduces effective force power more than unit losses alone.</p></div>';
    }
    if ($sub === 'training') {
        echo '<div class="card"><h4>Training Command</h4><p>Convert untrained units into combat-ready specialists.</p><p><a href="javascript:void(0)" onclick="sendData(\'train\',\'get\',\'mainDisplay\'); return false">Open Training</a></p></div>';
        echo '<div class="card"><h4>Demobilization</h4><p>Reverse assignments when strategy shifts.</p><p><a href="javascript:void(0)" onclick="sendData(\'untrain\',\'get\',\'mainDisplay\'); return false">Open Untrain</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'training&cmd=mil_training_surge\'); return false">Run Training Surge</a></p></div>';
        echo '<div class="card full"><h4>Force Composition Plan</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Role</th><th align="left">Target Share</th><th align="left">Trained From</th></tr>';
        echo '<tr><td>Attack</td><td>~45%</td><td>Primary combat drill</td></tr>';
        echo '<tr><td>Defense</td><td>~25%</td><td>Garrison drill</td></tr>';
        echo '<tr><td>Covert</td><td>~15%</td><td>Spy academy</td></tr>';
        echo '<tr><td>Anti-Covert</td><td>~15%</td><td>Counter-intel drill</td></tr>';
        echo '</table></div>';
    }
    if ($sub === 'fleet') {
        echo '<div class="card"><h4>Fleet Operations</h4><p>Deploy, reposition, and monitor fleet readiness.</p><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\'); return false">Open Fleet Dock</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'objects\'); return false">Scan Debris Fields</a></p></div>';
        echo '<div class="card"><h4>Shipyard and Mothership Controls</h4><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'upgrade_shipyard\'); return false">Upgrade Shipyard</a></p><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'upgrade_bay\'); return false">Upgrade Mothership Bay</a></p><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\',\'build\'); return false">Open Starship Factory Build Page</a></p><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\',\'catalog\'); return false">Open 90 Starship Catalog</a></p><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\',\'classes\'); return false">Open Class and Subclass Matrix</a></p><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\',\'types\'); return false">Open Type and Subtype Matrix</a></p><p><a href="javascript:void(0)" onclick="sendData(\'megaforge\',\'get\',\'mainDisplay\'); return false">Open 90-Class Mega Forge</a></p></div>';
        echo '<div class="card"><h4>Orbital Installations</h4><p>Expand stations and bases to improve fleet staging and defensive projection.</p><p><a href="javascript:void(0)" onclick="sendData(\'stations\',\'get\',\'mainDisplay\'); return false">Open Stations Command</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'bases\'); return false">Open Universe Base Matrix</a></p></div>';
        echo '<div class="card"><h4>Interstellar Travel Network</h4><p>Use Jump Gates, Stargates, and hyperspace lanes for long-range force projection.</p><p><a href="javascript:void(0)" onclick="sendData(\'hyperspace\',\'get\',\'mainDisplay\'); return false">Open Hyperspace Transit Command</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'travel\'); return false">Open Universe Travel Matrix</a></p></div>';
    }
    if ($sub === 'navy') {
        echo '<div class="card"><h4>Navy Operations</h4><p>Coordinate fleet waves, escort sequencing, and timing windows by operation type.</p><p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\'); return false">Open Fleet Dock</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'navy&cmd=mil_fleet_wargame\'); return false">Run Fleet War-Game</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'navy&cmd=mil_setfocus_aggressive\'); return false">Set Aggressive Focus</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'navy&cmd=mil_setfocus_defensive\'); return false">Set Defensive Focus</a></p></div>';
        echo '<div class="card full"><h4>Task Force Roles</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Hull Class</th><th align="left">Primary Role</th><th align="left">Escort Profile</th></tr>';
        echo '<tr><td>Frigate / Corvette</td><td>Recon and raiding</td><td>Fast, low sustainment</td></tr>';
        echo '<tr><td>Cruiser / Destroyer</td><td>Assault line</td><td>Balanced escort waves</td></tr>';
        echo '<tr><td>Battleship / Carrier</td><td>Capital projection</td><td>Heavy escort required</td></tr>';
        echo '<tr><td>Mothership</td><td>Command and logistics</td><td>Full defensive screen</td></tr>';
        echo '</table></div>';
    }
    if ($sub === 'defensegrid') {
        echo '<div class="card full"><h4>Defense Grid</h4><p>Layer defense systems across planets, stations, and fleet routes to reduce raid exposure.</p><p><a href="javascript:void(0)" onclick="sendData(\'stations\',\'get\',\'mainDisplay\'); return false">Open Stations Defense</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'defensegrid&cmd=mil_defense_harden\'); return false">Run Defense Hardening</a></p></div>';
        echo '<div class="card full"><h4>Coverage Layers</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Layer</th><th align="left">Protects</th><th align="left">Priority</th></tr>';
        echo '<tr><td>Planetary Defense</td><td>Resource buildings and economy</td><td>High-value planets first</td></tr>';
        echo '<tr><td>Lunar Structures</td><td>Fleet staging and gates</td><td>Strategic chokepoints</td></tr>';
        echo '<tr><td>Station Defense</td><td>Expedition and trade hubs</td><td>Active logistics routes</td></tr>';
        echo '<tr><td>Route Patrols</td><td>Convoy lanes</td><td>Under repeated raid pressure</td></tr>';
        echo '</table></div>';
    }
}

if ($main === 'operations') {
    if ($sub === 'attack') {
        echo '<div class="card"><h4>Attack Missions</h4><p>Launch direct strikes against enemy empires.</p><p><a href="javascript:void(0)" onclick="sendData(\'rank\',\'get\',\'mainDisplay\'); return false">Select Targets</a></p></div>';
        echo '<div class="card full"><h4>Strike Checklist</h4>';
        echo '<ul><li>Spy the target to confirm defense strength</li><li>Match attack force to intel (attack vs defense stat)</li><li>Confirm action-turn budget for the full wave</li><li>Set relation stance before committing</li><li>Review logs after the wave to refine doctrine</li></ul>';
        echo '</div>';
        echo '<div class="card"><h4>Target Selection Tips</h4><p>Prefer rivals with lower defense/covert ratings than your attack power. Rapid rank gainers are often worth investigating first.</p></div>';
    }
    if ($sub === 'raid') {
        echo '<div class="card"><h4>Raid Missions</h4><p>Execute high-speed resource raids for rapid gains.</p><p>Use player profiles to trigger raid actions.</p></div>';
        echo '<div class="card full"><h4>Raid Targeting Rules</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Signal</th><th align="left">Interpretation</th></tr>';
        echo '<tr><td>Low defense coverage</td><td>High-value extraction window</td></tr>';
        echo '<tr><td>Resource surplus on profile</td><td>Good haul expectation</td></tr>';
        echo '<tr><td>Repeated raid history</td><td>Higher retaliation risk</td></tr>';
        echo '<tr><td>Allied or treaty-bound</td><td>Skip - honor agreements</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Raid Cadence</h4><p>Respect your action-turn budget and bank returns between cycles. Chaining raids into deep space requires logistics coverage.</p></div>';
    }
    if ($sub === 'spy') {
        echo '<div class="card"><h4>Spy Network</h4><p>Gather intel before committing forces.</p><p><a href="javascript:void(0)" onclick="sendData(\'spy\',\'get\',\'mainDisplay\'); return false">Open Spy Module</a></p></div>';
        echo '<div class="card full"><h4>Recon Value Matrix</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Intel Goal</th><th align="left">Method</th><th align="left">Decision Impact</th></tr>';
        echo '<tr><td>Defense strength</td><td>Covert scan</td><td>Force sizing</td></tr>';
        echo '<tr><td>Fleet deployment</td><td>Movement watch</td><td>Strike timing</td></tr>';
        echo '<tr><td>Resource posture</td><td>Profile review</td><td>Raid targeting</td></tr>';
        echo '<tr><td>Alliance links</td><td>Roster check</td><td>Escalation risk</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Anti-Covert Balance</h4><p>Every spy mission can be countered. Keep anti-covert units roughly proportional to your spy usage to avoid feeding enemy intel.</p></div>';
    }
    if ($sub === 'logs') {
        echo '<div class="card"><h4>Combat Logs</h4><p>Review outcomes and refine strategy.</p><p><a href="javascript:void(0)" onclick="sendData(\'logs\',\'get\',\'mainDisplay\'); return false">Open Logs</a></p></div>';
        echo '<div class="card full"><h4>Debrief Review Points</h4>';
        echo '<ul><li>Compare expected vs actual losses per wave</li><li>Identify composition weaknesses (e.g. missing anti-covert)</li><li>Track raid retaliation frequency against your targets</li><li>Update target dossiers with observed patterns</li></ul>';
        echo '</div>';
        echo '<div class="card"><h4>Feedback Loop</h4><p>Consistent log review is the cheapest way to raise combat efficiency without spending a single turn.</p></div>';
    }
    if ($sub === 'commandqueue') {
        echo '<div class="card full"><h4>Command Queue</h4><p>Queue mission phases: recon, strike, raid, and recovery to avoid turn waste.</p><table class="mini-table" border="0" width="100%"><tr><th align="left">Phase</th><th align="left">Recommended Action</th></tr><tr><td>1</td><td>Spy and verify defenses</td></tr><tr><td>2</td><td>Launch primary attack wave</td></tr><tr><td>3</td><td>Execute raid follow-up</td></tr><tr><td>4</td><td>Recover and reposition</td></tr></table></div>';
        echo '<div class="card"><h4>Queue Discipline</h4><p>Only hold phases that have confirmed targets. Empty queue slots invite off-cycle spending and wasted turn windows.</p></div>';
        echo '<div class="card"><h4>Live Queue</h4><p>Open the RTS console to inspect the current priority queue and ETAs.</p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts\'); return false">Open RTS Command Console</a></p></div>';
    }
    if ($sub === 'diplomacyops') {
        echo '<div class="card"><h4>Diplomatic Operations</h4><p>Use messages and relation changes to reduce escalation before or after operations.</p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'diplomacy\',\'messages\'); return false">Open Messaging</a></p></div>';
        echo '<div class="card full"><h4>Escalation Ladder</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Rung</th><th align="left">Action</th><th align="left">Purpose</th></tr>';
        echo '<tr><td>1</td><td>Neutral stance</td><td>Signal non-aggression intent</td></tr>';
        echo '<tr><td>2</td><td>Direct message</td><td>Clarify intent before escalation</td></tr>';
        echo '<tr><td>3</td><td>War stance</td><td>Formalize conflict posture</td></tr>';
        echo '<tr><td>4</td><td>Alliance support call</td><td>Coordinate coalition response</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Sanction Signals</h4><p>Public stance changes and warnings shape rival behavior. Keep alliance leadership informed before major posture shifts.</p></div>';
    }
    if ($sub === 'rts') {
        $opsState = $operationsRtsState ?: (object)[
            'doctrine' => 'balanced',
            'tempo_mode' => 'standard',
            'theater_level' => 1,
            'command_xp' => 0,
            'cycle_index' => 0,
            'frontline_pressure' => 45,
            'reserve_integrity' => 60,
            'morale_index' => 55,
            'last_cycle_ts' => 0,
        ];
        $lastCycleText = ((int)$opsState->last_cycle_ts > 0) ? date('Y-m-d H:i:s', (int)$opsState->last_cycle_ts) : 'Never';
        echo '<div class="card full"><div class="feature-hero"><img src="images/ui/operations-console.svg" alt="Operations console" /><div><h4>RTS Turn-Based Operations Console</h4><p>Command your theater with live turn cycles, queue priority, and doctrine shifts.</p></div></div>';
        echo '<p><strong>Action Turns:</strong> ' . fnum((int)$operationsRtsTurnBalance) . ' | <strong>Doctrine:</strong> ' . h((string)$opsState->doctrine) . ' | <strong>Tempo:</strong> ' . h((string)$opsState->tempo_mode) . '</p>';
        echo '<p><strong>Theater Level:</strong> ' . fnum((int)$opsState->theater_level) . ' | <strong>Command XP:</strong> ' . fnum((int)$opsState->command_xp) . ' | <strong>Cycle Index:</strong> ' . fnum((int)$opsState->cycle_index) . '</p>';
        echo '<p><strong>Frontline Pressure:</strong> ' . fnum((int)$opsState->frontline_pressure) . ' | <strong>Reserve Integrity:</strong> ' . fnum((int)$opsState->reserve_integrity) . ' | <strong>Morale:</strong> ' . fnum((int)$opsState->morale_index) . '</p>';
        echo '<p><strong>Last Cycle:</strong> ' . h($lastCycleText) . '</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_set_doctrine_aggressive\'); return false">Doctrine: Aggressive</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_set_doctrine_balanced\'); return false">Balanced</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_set_doctrine_defensive\'); return false">Defensive</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_set_doctrine_covert\'); return false">Covert</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_set_tempo_standard\'); return false">Tempo: Standard</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_set_tempo_surge\'); return false">Surge</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_set_tempo_overwatch\'); return false">Overwatch</a></p>';
        echo '</div>';

        echo '<div class="card"><h4>Queue Operations</h4>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_queue_recon\'); return false">Queue Recon</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_queue_assault\'); return false">Queue Assault</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_queue_fortify\'); return false">Queue Fortify</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_queue_logistics\'); return false">Queue Logistics</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_queue_sabotage\'); return false">Queue Sabotage</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_cycle_run\'); return false">Run Next Ready Cycle</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_cycle_run_all\'); return false">Run All Ready Cycles</a></p>';
        echo '</div>';

        $opsQueueRows = [];
        $opsQueueQ = $s->query("SELECT queue_id, operation_code, operation_label, turn_cost, eta_seconds, status, priority_order, UNIX_TIMESTAMP(created_at) AS created_ts
            FROM operations_turn_queue
            WHERE uid=" . $uid . "
            ORDER BY status='queued' DESC, priority_order ASC, queue_id ASC LIMIT 20");
        if ($opsQueueQ) {
            while ($row = $opsQueueQ->fetch_assoc()) {
                $opsQueueRows[] = $row;
            }
        }

        echo '<div class="card full"><h4>RTS Queue Status</h4>';
        if (count($opsQueueRows) === 0) {
            echo '<p>No RTS operations queued yet.</p>';
        } else {
            echo '<table class="mini-table" border="0" width="100%">';
            echo '<tr><th align="left">Queue ID</th><th align="left">Priority</th><th align="left">Operation</th><th align="left">Turn Cost</th><th align="left">ETA</th><th align="left">Status</th><th align="left">Actions</th></tr>';
            foreach ($opsQueueRows as $row) {
                $statusName = (string)($row['status'] ?? 'queued');
                $etaSec = (int)($row['eta_seconds'] ?? 0);
                $createdTs = (int)($row['created_ts'] ?? time());
                $elapsed = max(0, time() - $createdTs);
                $remaining = max(0, $etaSec - $elapsed);
                $etaText = ($statusName === 'queued') ? (fnum($remaining) . 's') : '0s';
                echo '<tr>';
                echo '<td>#' . fnum((int)$row['queue_id']) . '</td>';
                echo '<td>' . fnum((int)$row['priority_order']) . '</td>';
                echo '<td>' . h((string)$row['operation_label']) . '</td>';
                echo '<td>' . fnum((int)$row['turn_cost']) . '</td>';
                echo '<td>' . h($etaText) . '</td>';
                echo '<td>' . h($statusName) . '</td>';
                if ($statusName === 'queued') {
                    echo '<td><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_queue_up&oqid=' . (int)$row['queue_id'] . '\'); return false">Up</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_queue_down&oqid=' . (int)$row['queue_id'] . '\'); return false">Down</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'rts&cmd=ops_queue_cancel&oqid=' . (int)$row['queue_id'] . '\'); return false">Cancel</a></td>';
                } else {
                    echo '<td>-</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</div>';
    }
}

if ($main === 'economy') {
    if ($sub === 'banking') {
        echo '<div class="card"><h4>Banking Control</h4>';
        echo '<p><strong>On Hand:</strong> ' . fnum($bank->onHand ?? 0) . '</p>';
        echo '<p><strong>In Bank:</strong> ' . fnum($bank->inBank ?? 0) . '</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'bank\',\'get\',\'mainDisplay\'); return false">Open Bank Module</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Resource Vaults</h4>';
        echo '<p><strong>Metal:</strong> ' . fnum($resourceHub['current']['metal']) . ' | <strong>Crystal:</strong> ' . fnum($resourceHub['current']['crystal']) . ' | <strong>Deuterium:</strong> ' . fnum($resourceHub['current']['deuterium']) . '</p>';
        echo '<p><strong>Food:</strong> ' . fnum($resourceHub['current']['food']) . ' | <strong>Water:</strong> ' . fnum($resourceHub['current']['water']) . ' | <strong>Population:</strong> ' . fnum($resourceHub['current']['population']) . ' | <strong>Energy:</strong> ' . fnum($resourceHub['current']['energy']) . '</p>';
        echo '</div>';
    }
    if ($sub === 'market') {
        echo '<div class="card"><h4>Market Trade</h4><p>Buy and sell resources to tune your economy.</p><p><a href="javascript:void(0)" onclick="sendData(\'market\',\'get\',\'mainDisplay\'); return false">Open Market</a></p></div>';
        echo '<div class="card full"><h4>Trade Guidance</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Need</th><th align="left">Approach</th><th align="left">Risk</th></tr>';
        echo '<tr><td>Fuel shortfall</td><td>Buy deuterium during build pauses</td><td>Price spikes near war windows</td></tr>';
        echo '<tr><td>Build sprint</td><td>Buy metal just before construction</td><td>Holding metal invites raids</td></tr>';
        echo '<tr><td>Surplus dump</td><td>Sell excess food/water</td><td>Depletes colony sustainment</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Market Timing</h4><p>Trade during quiet windows. Overextension on market swings can starve military spending at the worst moment.</p></div>';
    }
    if ($sub === 'technology') {
        echo '<div class="card"><h4>Technology Tree</h4><p>Advance economy, combat, covert, and empire-era systems.</p><p><a href="javascript:void(0)" onclick="sendData(\'technology\',\'get\',\'mainDisplay\'); return false">Open Technology</a></p><p><a href="javascript:void(0)" onclick="sendData(\'stargatetech\',\'get\',\'mainDisplay\'); return false">Open Empire Technology Command</a></p></div>';
        echo '<div class="card full"><h4>Research Priorities</h4>';
        echo '<ul><li>Economy tech first - it compounds every other system</li><li>Combat tech before campaign waves - not during them</li><li>Covert and anti-covert tech together to keep intel parity</li><li>Empire-era tech for gate and fleet scaling in late game</li></ul>';
        echo '</div>';
        echo '<div class="card"><h4>Tech Budget</h4><p>Reserve ~20% of income for research. Idle labs are pure lost tempo.</p></div>';
    }
    if ($sub === 'production') {
        echo '<div class="card"><h4>Production Planning</h4><p>Focus on unit production and mining throughput to scale your empire.</p><ul><li>Upgrade UP first for faster growth</li><li>Balance miners vs combat readiness</li><li>Protect income assets with defense</li></ul></div>';
        echo '<div class="card"><h4>Resource Command</h4><p><a href="javascript:void(0)" onclick="sendData(\'resourcehq\',\'get\',\'mainDisplay\'); return false">Open Resource HQ</a></p></div>';
        echo '<div class="card"><h4>Infrastructure Build Grid</h4><p><a href="javascript:void(0)" onclick="sendData(\'ogamebuildings\',\'get\',\'mainDisplay\'); return false">Open OGame Buildings Command</a></p></div>';

        echo '<div class="card full"><h4>OGame-Style Resource Output Grid</h4>';
        echo '<table class="mini-table" border="0" width="100%"><tr><th align="left">Line</th><th align="left">Per Turn</th><th align="left">Notes</th></tr>';
        echo '<tr><td>Metal Mines</td><td>' . fnum($resourceHub['rates']['metal']) . '</td><td>Primary build material for warships and infrastructure.</td></tr>';
        echo '<tr><td>Crystal Plants</td><td>' . fnum($resourceHub['rates']['crystal']) . '</td><td>Advanced systems and tech fabrication material.</td></tr>';
        echo '<tr><td>Deuterium Synthesizers</td><td>' . fnum($resourceHub['rates']['deuterium']) . '</td><td>Fuel and high-tier fleet operations resource.</td></tr>';
        echo '<tr><td>Hydroponics (Food)</td><td>' . fnum($resourceHub['rates']['food']) . '</td><td>Population upkeep and colony stability.</td></tr>';
        echo '<tr><td>Atmospheric Condensers (Water)</td><td>' . fnum($resourceHub['rates']['water']) . '</td><td>Life support and growth multiplier.</td></tr>';
        echo '<tr><td>Population Growth</td><td>' . fnum($resourceHub['rates']['population']) . '</td><td>Workforce growth with food/water dependence.</td></tr>';
        echo '<tr><td>Energy Reactors</td><td>' . fnum($resourceHub['rates']['energy']) . '</td><td>Power grid output for gates, bases, and industry.</td></tr>';
        echo '</table></div>';
    }

    if ($sub === 'resources') {
        echo '<div class="card"><h4>Resource Headquarters</h4>';
        echo '<p>Manage OGame-style resource mining, food and water sustainment, and population growth.</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'resourcehq\',\'get\',\'mainDisplay\'); return false">Open Resource HQ Module</a></p>';
        echo '</div>';

        echo '<div class="card"><h4>Current Structure Levels</h4>';
        echo '<p><strong>Metal Mine:</strong> ' . fnum($resourceHub['structures']['metal_mine']) . '</p>';
        echo '<p><strong>Crystal Lab:</strong> ' . fnum($resourceHub['structures']['crystal_lab']) . '</p>';
        echo '<p><strong>Deuterium Refinery:</strong> ' . fnum($resourceHub['structures']['deuterium_refinery']) . '</p>';
        echo '<p><strong>Hydroponics:</strong> ' . fnum($resourceHub['structures']['hydroponics']) . '</p>';
        echo '<p><strong>Water Plant:</strong> ' . fnum($resourceHub['structures']['water_plant']) . '</p>';
        echo '<p><strong>Habitat Dome:</strong> ' . fnum($resourceHub['structures']['habitat_dome']) . '</p>';
        echo '<p><strong>Energy Reactor:</strong> ' . fnum($resourceHub['structures']['energy_reactor']) . '</p>';
        echo '</div>';

        echo '<div class="card full"><h4>Resource Status</h4>';
        echo '<p><strong>Metal:</strong> ' . fnum($resourceHub['current']['metal']) . ' | <strong>Crystal:</strong> ' . fnum($resourceHub['current']['crystal']) . ' | <strong>Deuterium:</strong> ' . fnum($resourceHub['current']['deuterium']) . '</p>';
        echo '<p><strong>Food:</strong> ' . fnum($resourceHub['current']['food']) . ' | <strong>Water:</strong> ' . fnum($resourceHub['current']['water']) . ' | <strong>Population:</strong> ' . fnum($resourceHub['current']['population']) . ' | <strong>Energy:</strong> ' . fnum($resourceHub['current']['energy']) . '</p>';
        echo '</div>';
    }

    if ($sub === 'buildings') {
        echo '<div class="card"><h4>OGame Building Matrix</h4>';
        echo '<p>Build and upgrade classic structures across resources, facilities, lunar systems, and defenses.</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'ogamebuildings\',\'get\',\'mainDisplay\'); return false">Open OGame Buildings Command</a></p>';
        echo '</div>';

        echo '<div class="card"><h4>Build Strategy</h4>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'resourcehq\',\'get\',\'mainDisplay\'); return false">Resource HQ</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\'); return false">Fleet Dock</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'hyperspace\',\'get\',\'mainDisplay\'); return false">Hyperspace Transit</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Infrastructure Guidance</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Priority</th><th align="left">Why</th><th align="left">Typical Phase</th></tr>';
        echo '<tr><td>Resource Buildings</td><td>Drive compounding growth and sustain all other build lines.</td><td>Early game foundation</td></tr>';
        echo '<tr><td>Facilities</td><td>Improve construction speed and unlock advanced systems.</td><td>Early-mid transition</td></tr>';
        echo '<tr><td>Lunar Structures</td><td>Enable long-range deployment and strategic mobility.</td><td>Mid game expansion</td></tr>';
        echo '<tr><td>Defense Layers</td><td>Protect economy and fleets from raid pressure.</td><td>Any phase under threat</td></tr>';
        echo '</table></div>';
    }

    if ($sub === 'logistics') {
        echo '<div class="card"><h4>Supply Logistics</h4><p>Balance transport and spending across combat, expansion, and research lanes.</p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'empire\',\'logistics\'); return false">Open Empire Logistics Hub</a></p></div>';
        echo '<div class="card full"><h4>Convoy Priorities</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Cargo</th><th align="left">Route</th><th align="left">Guard Level</th></tr>';
        echo '<tr><td>Construction metal</td><td>High-production to building colonies</td><td>Medium</td></tr>';
        echo '<tr><td>Fuel convoys</td><td>To gate staging planets</td><td>High</td></tr>';
        echo '<tr><td>Surplus dump</td><td>From idle colonies to bank</td><td>Low</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Lane Security</h4><p>Route convoys along secure hyperspace lanes and escort high-value cargo when raid pressure is elevated.</p></div>';
    }

    if ($sub === 'treasury') {
        echo '<div class="card full"><h4>Treasury Policy</h4><p>Set reserve thresholds to avoid operational lock during spikes in war spending.</p><ul><li>War reserve: 35%</li><li>Research reserve: 20%</li><li>Expansion reserve: 15%</li><li>Flexible capital: 30%</li></ul></div>';
        echo '<div class="card"><h4>Naquadah Ledger</h4><p><strong>On Hand:</strong> ' . fnum($bank->onHand ?? 0) . ' | <strong>In Bank:</strong> ' . fnum($bank->inBank ?? 0) . '</p><p><a href="javascript:void(0)" onclick="sendData(\'bank\',\'get\',\'mainDisplay\'); return false">Open Bank</a></p></div>';
        echo '<div class="card"><h4>Operational Lock Warning</h4><p>If spending pushes you below the war reserve, operations queue automatically. Rebuild reserves before opening new fronts.</p></div>';
    }

    if ($sub === 'store') {
        echo '<div class="card full"><div class="feature-hero"><img src="images/ui/empire-portal.svg" alt="Empire portal" /><div><h4>In-Game Store</h4><p>Spend Naquadah on instant strategic boosts and resource relief.</p></div></div></div>';
        echo '<div class="card full"><h4>Economy Pulse</h4>';
        echo '<p><strong>Naquadah:</strong> ' . fnum((int)($bank->onHand ?? 0)) . ' | <strong>Energy:</strong> ' . fnum((int)$resourceHub['current']['energy']) . ' | <strong>Water:</strong> ' . fnum((int)$resourceHub['current']['water']) . '</p>';
        echo '</div>';
        echo '<div class="store-grid">';
        foreach ($storeRows as $item) {
            $purchased = isset($purchasedKeys[$item['item_key']]);
            $buttonLabel = $purchased ? 'Owned' : 'Purchase';
            echo '<div class="card">';
            echo '<h4>' . h($item['item_name']) . '</h4>';
            echo '<p><strong>Type:</strong> ' . h($item['item_type']) . ' | <strong>Rarity:</strong> ' . h($item['rarity']) . '</p>';
            echo '<p><strong>Cost:</strong> ' . fnum((int)$item['price_nq']) . ' Naquadah</p>';
            echo '<p><strong>Reward:</strong> ' . fnum((int)$item['reward_amount']) . ' ' . h($item['reward_label']) . '</p>';
            echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'economy\',\'store&cmd=store_purchase&item=' . h($item['item_key']) . '\'); return false">' . h($buttonLabel) . '</a></p>';
            echo '</div>';
        }
        echo '</div>';
    }

    if ($sub === 'battlepass') {
        echo '<div class="card full"><div class="feature-hero"><img src="images/ui/operations-console.svg" alt="Battle pass" /><div><h4>Battle Pass</h4><p>Climb the combat track and unlock rewards by earning pass XP through missions and operations.</p></div></div></div>';
        echo '<div class="card full"><h4>Progress Summary</h4>';
        echo '<p><strong>Level:</strong> ' . fnum((int)$passProgress->battle_pass_level) . ' | <strong>XP:</strong> ' . fnum((int)$passProgress->battle_pass_xp) . ' | <strong>Next Threshold:</strong> ' . fnum((int)((int)$passProgress->battle_pass_level + 1) * 120) . '</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'economy\',\'battlepass&cmd=pass_gain&xp=120\'); return false">Gain 120 XP</a></p>';
        echo '</div>';
        echo '<div class="pass-grid">';
        foreach ($battleLevels as $lvl) {
            $isClaimed = isset($claimedPassSet['battle:' . (int)$lvl['level']]);
            $claimable = 'Locked';
            if ((int)$passProgress->battle_pass_level >= (int)$lvl['level']) {
                $claimable = $isClaimed ? 'Claimed' : 'Claim';
            }
            echo '<div class="card">';
            echo '<h4>Level ' . fnum((int)$lvl['level']) . '</h4>';
            echo '<p><strong>XP Needed:</strong> ' . fnum((int)$lvl['xp']) . '</p>';
            echo '<p><strong>Reward:</strong> ' . h($lvl['reward']) . ' (' . h($lvl['bonus']) . ')</p>';
            echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'economy\',\'battlepass&cmd=pass_claim&pass=battle&level=' . (int)$lvl['level'] . '\'); return false">' . h($claimable) . '</a></p>';
            echo '</div>';
        }
        echo '</div>';
    }

    if ($sub === 'seasonpass') {
        echo '<div class="card full"><div class="feature-hero"><img src="images/ui/universe-archive.svg" alt="Season pass" /><div><h4>Season Pass</h4><p>Advance through the seasonal progression track for long-term empire rewards.</p></div></div></div>';
        echo '<div class="card full"><h4>Progress Summary</h4>';
        echo '<p><strong>Level:</strong> ' . fnum((int)$passProgress->season_pass_level) . ' | <strong>XP:</strong> ' . fnum((int)$passProgress->season_pass_xp) . ' | <strong>Next Threshold:</strong> ' . fnum((int)((int)$passProgress->season_pass_level + 1) * 160) . '</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'economy\',\'seasonpass&cmd=pass_gain&xp=160\'); return false">Gain 160 XP</a></p>';
        echo '</div>';
        echo '<div class="pass-grid">';
        foreach ($seasonLevels as $lvl) {
            $isClaimed = isset($claimedPassSet['season:' . (int)$lvl['level']]);
            $claimable = 'Locked';
            if ((int)$passProgress->season_pass_level >= (int)$lvl['level']) {
                $claimable = $isClaimed ? 'Claimed' : 'Claim';
            }
            echo '<div class="card">';
            echo '<h4>Level ' . fnum((int)$lvl['level']) . '</h4>';
            echo '<p><strong>XP Needed:</strong> ' . fnum((int)$lvl['xp']) . '</p>';
            echo '<p><strong>Reward:</strong> ' . h($lvl['reward']) . ' (' . h($lvl['bonus']) . ')</p>';
            echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'economy\',\'seasonpass&cmd=pass_claim&pass=season&level=' . (int)$lvl['level'] . '\'); return false">' . h($claimable) . '</a></p>';
            echo '</div>';
        }
        echo '</div>';
    }
}

if ($main === 'diplomacy') {
    if ($sub === 'alliance') {
        echo '<div class="card"><h4>Alliance Management</h4><p>Coordinate allies, officer chains, and power blocs.</p><p><a href="javascript:void(0)" onclick="sendData(\'ally_mlist\',\'get\',\'mainDisplay\'); return false">Open Alliance Roster</a></p></div>';
        echo '<div class="card full"><h4>Bloc Coordination</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Function</th><th align="left">Best Practice</th></tr>';
        echo '<tr><td>Officer chain</td><td>Keep clear role assignments to avoid decision lag</td></tr>';
        echo '<tr><td>War coordination</td><td>Announce campaigns before opening fire</td></tr>';
        echo '<tr><td>Resource support</td><td>Route surplus through bank transfers</td></tr>';
        echo '<tr><td>Defense pacts</td><td>Confirm pact scope in treaty desk</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Collective Deterrence</h4><p>Coordinated alliances deter opportunistic raids. Active rosters react faster than defensive structures.</p></div>';
    }
    if ($sub === 'relations') {
        echo '<div class="card"><h4>Relations Desk</h4><p>Set war, neutral, and peace stances with other empires through player profile actions.</p><p><a href="javascript:void(0)" onclick="sendData(\'user\',\'get\',\'' . $uid . '\'); return false">Open Profile Actions</a></p></div>';
        echo '<div class="card full"><h4>Stance Policy</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Stance</th><th align="left">Signal</th><th align="left">Risk Profile</th></tr>';
        echo '<tr><td>Peace</td><td>Non-aggression intent</td><td>Low, but invites opportunists</td></tr>';
        echo '<tr><td>Neutral</td><td>Default posture</td><td>Balanced</td></tr>';
        echo '<tr><td>War</td><td>Formal conflict</td><td>High retaliation expectation</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Conflict-State Awareness</h4><p>Keep stances consistent with treaties and alliance policy. Contradictory signals cost diplomatic trust.</p></div>';
    }
    if ($sub === 'messages') {
        echo '<div class="card"><h4>Secure Messaging</h4><p>Send diplomatic messages and coordinate attacks.</p><p><a href="javascript:void(0)" onclick="sendData(\'messages\',\'get\',\'mainDisplay\'); return false">Open Inbox</a></p></div>';
        echo '<div class="card full"><h4>Comms Discipline</h4>';
        echo '<ul><li>Use threads for campaign planning - not one-off pings</li><li>Timestamp operational requests for audit clarity</li><li>Keep alliance leadership on escalation messages</li><li>Archive resolved threads for post-war review</li></ul>';
        echo '</div>';
        echo '<div class="card"><h4>Response Tempo</h4><p>Fast replies reduce mis-coordination. Stale threads are a common source of friendly-fire incidents.</p></div>';
    }
    if ($sub === 'commander') {
        echo '<div class="card"><h4>Commander Chain</h4><p>Assign commanders and issue support transfers from player profile pages.</p><p><a href="javascript:void(0)" onclick="sendData(\'user\',\'get\',\'' . $uid . '\'); return false">Open Commander Tools</a></p></div>';
        echo '<div class="card full"><h4>Chain Structure</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Role</th><th align="left">Responsibility</th><th align="left">Transfer Focus</th></tr>';
        echo '<tr><td>Empire Commander</td><td>Overall doctrine</td><td>Policy and war approval</td></tr>';
        echo '<tr><td>Deputy</td><td>Operations continuity</td><td>Coverage during absence</td></tr>';
        echo '<tr><td>War Officer</td><td>Campaign coordination</td><td>Support transfers to fronts</td></tr>';
        echo '<tr><td>Intel Officer</td><td>Threat reporting</td><td>Recon tasking</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Stability</h4><p>Frequent commander changes reset support chains. Assign for a season, not a session.</p></div>';
    }
    if ($sub === 'governance') {
        echo '<div class="card"><h4>Commander Governance Systems</h4><p>Activate OGame-style commander governance with 18 policy systems, option profiles, and strategic settings.</p><p><a href="javascript:void(0)" onclick="sendData(\'commandergov\',\'get\',\'mainDisplay\'); return false">Open Commander Governance Console</a></p></div>';
        echo '<div class="card"><h4>Command Links</h4><p><a href="javascript:void(0)" onclick="sendData(\'user\',\'get\',\'' . $uid . '\'); return false">Commander Chain Actions</a></p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'operations\',\'logs\'); return false">Operation Logs</a></p><p><a href="javascript:void(0)" onclick="sendData(\'stargatetech\',\'get\',\'mainDisplay\'); return false">Empire Technology</a></p></div>';
        echo '<div class="card full"><h4>Governance Doctrine Overview</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Track</th><th align="left">Purpose</th><th align="left">Typical Focus</th></tr>';
        echo '<tr><td>Policy Administration</td><td>Keep laws and command directives synchronized.</td><td>Balanced/Technocracy setups</td></tr>';
        echo '<tr><td>War Governance</td><td>Accelerate conflict response and chain-of-command control.</td><td>Militarist/Warlord setups</td></tr>';
        echo '<tr><td>Economic Governance</td><td>Optimize markets, quotas, and logistics governance.</td><td>Mercantile/Architect setups</td></tr>';
        echo '<tr><td>Intelligence Governance</td><td>Harden covert resilience and strategic awareness.</td><td>Shadow/High-alert setups</td></tr>';
        echo '</table></div>';
    }
    if ($sub === 'treaties') {
        echo '<div class="card full"><h4>Treaty Desk</h4><p>Track active pacts and peace windows. Use this before multi-target operations.</p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'diplomacy\',\'relations\'); return false">Open Relations</a></p></div>';
        echo '<div class="card full"><h4>Treaty Types</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Treaty</th><th align="left">Covers</th><th align="left">Consequence of Breach</th></tr>';
        echo '<tr><td>Non-Aggression Pact</td><td>No raids or strikes between parties</td><td>Reputation loss + retaliation risk</td></tr>';
        echo '<tr><td>Trade Agreement</td><td>Favored exchange terms</td><td>Trade access revoked</td></tr>';
        echo '<tr><td>Mutual Defense</td><td>Joint response to third-party attacks</td><td>Alliance trust erosion</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Before Multi-Target Ops</h4><p>Verify none of your intended targets are covered by active pacts. A single accidental breach can collapse regional alignment.</p></div>';
    }
    if ($sub === 'councils') {
        echo '<div class="card"><h4>Council Chamber</h4><p>Coordinate alliance leadership votes and campaign-wide directives.</p><p><a href="javascript:void(0)" onclick="sendData(\'ally_mlist\',\'get\',\'mainDisplay\'); return false">Open Alliance Roster</a></p></div>';
        echo '<div class="card full"><h4>Council Functions</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Function</th><th align="left">Scope</th><th align="left">Approval Type</th></tr>';
        echo '<tr><td>Ranks</td><td>Officer assignments</td><td>Leader approval</td></tr>';
        echo '<tr><td>Votes</td><td>Major policy decisions</td><td>Majority motion</td></tr>';
        echo '<tr><td>War plans</td><td>Bundled campaign targets</td><td>Single approval cycle</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Motion Discipline</h4><p>Vote before campaigns, not during them. Approved war plans bundle targets into one clean mandate.</p></div>';
    }
}

if ($main === 'intel') {
    if ($sub === 'rankings') {
        echo '<div class="card"><h4>Rankings Console</h4><p>Monitor global power standings and rival growth.</p><p><a href="javascript:void(0)" onclick="sendData(\'rank\',\'get\',\'mainDisplay\'); return false">Open Rankings</a></p></div>';
        echo '<div class="card full"><h4>Ranking Signals</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Signal</th><th align="left">Meaning</th><th align="left">Response</th></tr>';
        echo '<tr><td>Rapid climb</td><td>Power spike / new strategy</td><td>Investigate before it matures</td></tr>';
        echo '<tr><td>Stagnation</td><td>Building pause or attrition</td><td>Possible raid window</td></tr>';
        echo '<tr><td>Bracket cluster</td><td>Regional contest forming</td><td>Plan coalition posture</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Ranking Deltas</h4><p>Compare standings across weeks, not just today. Direction of change is a stronger threat indicator than absolute rank.</p></div>';
    }
    if ($sub === 'reports') {
        echo '<div class="card"><h4>Battle Reports</h4><p>Digest mission outcomes and casualty analytics.</p><p><a href="javascript:void(0)" onclick="sendData(\'actionLogs\',\'get\',\'mainDisplay\'); return false">Open Action Reports</a></p></div>';
        echo '<div class="card full"><h4>Report Review Flow</h4>';
        echo '<ul><li>Sort reports by outcome - losses first</li><li>Check loss composition vs expectation</li><li>Flag repeated losses to the same target</li><li>Update dossiers with observed defense patterns</li><li>Archive clean wins for trend reference</li></ul>';
        echo '</div>';
        echo '<div class="card"><h4>Efficiency Metric</h4><p>Track resource gained per loss. Missions with low ratios should be re-planned, not repeated.</p></div>';
    }
    if ($sub === 'threats') {
        echo '<div class="card"><h4>Threat Matrix</h4><p>High threat indicators:</p><ul><li>Rapid rank ascension nearby</li><li>Hostile commander chains</li><li>Repeated raid contact</li></ul></div>';
        echo '<div class="card full"><h4>Threat Escalation Levels</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Level</th><th align="left">Indicators</th><th align="left">Posture</th></tr>';
        echo '<tr><td>Watch</td><td>New hostile stance, one raid probe</td><td>Verify with spy</td></tr>';
        echo '<tr><td>Elevated</td><td>Repeated probes, rank climb</td><td>Raise defense coverage</td></tr>';
        echo '<tr><td>Critical</td><td>Coordinated alliance movement</td><td>Call coalition, prepare response</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Escalation Review</h4><p>Re-evaluate the threat matrix every turn cycle. Threats that sit at Critical without action are the most dangerous.</p></div>';
    }
    if ($sub === 'map') {
        echo '<div class="card"><h4>Sector Map</h4><p>Use race, rank, and alliance data from profile scans to map influence zones.</p></div>';
        echo '<div class="card full"><h4>Influence Modeling</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Factor</th><th align="left">Weight Trend</th><th align="left">Use</th></tr>';
        echo '<tr><td>Military rank</td><td>High</td><td>Core pressure baseline</td></tr>';
        echo '<tr><td>Alliance size</td><td>High</td><td>Coalition reach</td></tr>';
        echo '<tr><td>Race concentrations</td><td>Medium</td><td>Regional alignment</td></tr>';
        echo '<tr><td>Recent activity</td><td>Medium</td><td>Current threat focus</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Fresh-Data Rule</h4><p>Influence estimates decay. Re-scan profiles before any major expansion or strike decision.</p></div>';
    }
    if ($sub === 'signals') {
        echo '<div class="card"><h4>Signal Watch</h4><p>Monitor sudden rank jumps, repeated scout activity, and hostile message bursts.</p></div>';
        echo '<div class="card full"><h4>Signal Types</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Signal</th><th align="left">Confidence</th><th align="left">Action</th></tr>';
        echo '<tr><td>Rank jump</td><td>High</td><td>Check profile and defenses</td></tr>';
        echo '<tr><td>Repeated scouts</td><td>High</td><td>Raise anti-covert readiness</td></tr>';
        echo '<tr><td>Message burst</td><td>Medium</td><td>Alliance coordination likely</td></tr>';
        echo '<tr><td>Anomaly ping</td><td>Low</td><td>Monitor, verify before acting</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Signal Confidence</h4><p>Confidence rises with your intel level. Verify low-confidence signals before spending turns.</p></div>';
    }
    if ($sub === 'dossiers') {
        echo '<div class="card full"><h4>Target Dossiers</h4><p>Build dossiers for high-value rivals before campaign waves.</p><p><a href="javascript:void(0)" onclick="sendData(\'rank\',\'get\',\'mainDisplay\'); return false">Open Rankings</a></p></div>';
        echo '<div class="card full"><h4>Dossier Fields</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Field</th><th align="left">Source</th><th align="left">Strike Use</th></tr>';
        echo '<tr><td>Defense strength</td><td>Spy reports</td><td>Force sizing</td></tr>';
        echo '<tr><td>Fleet timing</td><td>Movement logs</td><td>Strike windows</td></tr>';
        echo '<tr><td>Build pattern</td><td>Profile scans</td><td>Raid targeting</td></tr>';
        echo '<tr><td>Response cycle</td><td>Combat history</td><td>Follow-up timing</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Fresh Data Wins</h4><p>Recon the target the same cycle as the strike. Stale dossiers cause under-sized waves.</p></div>';
    }
}

if ($main === 'universe') {
    if ($sub === 'galaxies') {
        echo '<div class="card"><h4>Universe Control Seed</h4>';
        echo '<p><strong>Seed:</strong> U-' . h($universe['seed']) . '</p>';
        echo '<p><strong>Galaxy Clusters:</strong> ' . fnum($uCfg['galaxies']) . '</p>';
        echo '<p><strong>Systems per Galaxy:</strong> ' . fnum((int)($uCfg['systemsPerGalaxy'] ?? $uCfg['sectorsPerGalaxy'])) . ' | <strong>Positions per System:</strong> ' . fnum((int)($uCfg['positionsPerSystem'] ?? $uCfg['orbitsPerSector'])) . '</p>';
        echo '<p><strong>Total Worlds:</strong> ' . fnum($uCfg['maxWorlds']) . '</p>';
        echo '<p><strong>Colonizable Worlds:</strong> ~' . fnum((int)floor($uCfg['maxWorlds'] * 0.31)) . ' <em style="color:#aaa">(after NPC territory claim)</em></p>';
        echo '<p><strong>NPC Territory:</strong> ~' . fnum((int)floor($uCfg['maxWorlds'] * 0.40)) . ' worlds held by alien factions';
        if (isset($universe['summary']['npcWorlds']) && $universe['summary']['npcWorlds'] > 0) {
            echo ' <em style="color:#aaa">(sample: ' . fnum($universe['summary']['npcWorlds']) . ' of ' . fnum($universe['summary']['totalWorlds']) . ' sampled worlds)</em>';
        }
        echo '</p>';
        echo '</div>';

        echo '<div class="card"><h4>Expansion Command</h4>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets\'); return false">Open Planet Registry</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'objects\'); return false">Scan Interstellar Objects</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'expedition\'); return false">Open Expedition Control</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Galaxy Cluster Matrix</h4>';
        echo '<table class="mini-table" border="0" width="100%"><tr><th align="left">Galaxy</th><th align="left">Systems</th><th align="left">Positions/System</th><th align="left">Worlds</th><th align="left">Avg Habitability</th><th align="left">Moon Count</th><th align="left">Raid Trial</th></tr>';
        $galSampleMax = min(12, (int)$uCfg['galaxies']);
        for ($g = 1; $g <= $galSampleMax; $g++) {
            $systemsPerGalaxy = (int)($uCfg['systemsPerGalaxy'] ?? $uCfg['sectorsPerGalaxy']);
            $positionsPerSystem = (int)($uCfg['positionsPerSystem'] ?? $uCfg['orbitsPerSector']);
            $worldsPerGalaxy = (int)($systemsPerGalaxy * $positionsPerSystem);
            $gSeed = (($uid + $g) * 31337) & 0x7fffffff;
            $avgHab = universeRand($gSeed, 44, 69);
            $moonCount = universeRand($gSeed, (int)floor($worldsPerGalaxy * 0.6), (int)floor($worldsPerGalaxy * 1.4));
            $raidProfile = formalGalaxyRaidProfile($g, max(1, min($systemsPerGalaxy, 3 + ($g % 4))), 4 + ($g % 3));
            echo '<tr><td>G' . fnum($g) . '</td><td>' . fnum($systemsPerGalaxy) . '</td><td>' . fnum($positionsPerSystem) . '</td><td>' . fnum($worldsPerGalaxy) . '</td><td>' . fnum($avgHab) . '%</td><td>' . fnum($moonCount) . '</td><td><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'galaxies&cmd=uni_galaxy_raid_trial&g=' . $g . '&s=' . max(1, min($systemsPerGalaxy, 3 + ($g % 4))) . '\'); return false">' . h($raidProfile['target']) . '</a></td></tr>';
        }
        echo '</table></div>';

        echo '<div class="card full"><h4>OGame Coordinate Systems</h4>';
        echo '<table class="mini-table" border="0" width="100%"><tr><th align="left">Layer</th><th align="left">Range</th><th align="left">Role</th></tr>';
        echo '<tr><td>Galaxy</td><td>1-' . fnum((int)$uCfg['galaxies']) . '</td><td>Macro strategic region</td></tr>';
        echo '<tr><td>System</td><td>1-' . fnum((int)($uCfg['systemsPerGalaxy'] ?? $uCfg['sectorsPerGalaxy'])) . '</td><td>Primary travel and targeting lane</td></tr>';
        echo '<tr><td>Position</td><td>1-' . fnum((int)($uCfg['positionsPerSystem'] ?? $uCfg['orbitsPerSector'])) . '</td><td>Planet slot and moon anchor</td></tr>';
        echo '</table>';
        echo '</div>';

        echo '<div class="card full"><h4>Planet and Moon Registry</h4>';
        echo '<p>Showing worlds ' . fnum($worldSlice['start']) . '-' . fnum($worldSlice['end']) . ' of ' . fnum($worldSlice['total']) . ' | Page ' . fnum($worldSlice['page']) . ' / ' . fnum($worldSlice['maxPage']) . ' &mdash; <em>Click any planet or moon to view details</em></p>';

        $prev = max(1, $worldSlice['page'] - 1);
        $next = min($worldSlice['maxPage'], $worldSlice['page'] + 1);
        $qsBase = "modules/pages.php?id=universe&atype=galaxies&pp=" . (int)$worldSlice['perPage'] . "&time=";
        echo '<p>';
        echo '<a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $qsBase . '\'+(new Date().getTime())+\'&p=1\',true); return false" style="margin-right:8px;">First</a>';
        echo '<a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $qsBase . '\'+(new Date().getTime())+\'&p=' . $prev . '\',true); return false" style="margin-right:8px;">Prev</a>';
        echo '<a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $qsBase . '\'+(new Date().getTime())+\'&p=' . $next . '\',true); return false" style="margin-right:8px;">Next</a>';
        echo '<a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $qsBase . '\'+(new Date().getTime())+\'&p=' . $worldSlice['maxPage'] . '\',true); return false">Last</a>';
        echo '</p>';

        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">#</th><th align="left">Coordinate</th><th align="left">World</th><th align="left">Type</th><th align="left">Biome</th><th align="left">Sub-Biome</th><th align="left">Habitability</th><th align="left">Moons</th><th align="left">Moon Class</th><th align="left">Plague</th><th align="left">Water</th><th align="left">Status</th></tr>';
            foreach ($worldSlice['rows'] as $w) {
                $plagueRows = universePlagueRowsForWorld($s, $uid, (int)$w['idx']);
                $plagueLabel = universePlagueSummaryText($plagueRows);
                $waterRows = universeWaterRowsForWorld($s, $uid, (int)$w['idx']);
                $waterLabel = universeWaterSummaryText($waterRows);
                $pd = htmlspecialchars(json_encode([
                    'idx'    => $w['idx'],
                    'coord'  => $w['coord'],  'name'  => $w['name'],  'type'  => $w['type'],
                    'biome'  => $w['biome'],  'subBiome' => $w['subBiome'], 'hab'   => $w['habitability'], 'slots' => $w['slots'],
                    'metal'  => $w['metal'],  'crystal' => $w['crystal'], 'deut' => $w['deut'],
                    'moons'  => $w['moons'],  'moonClass' => $w['moonClass'], 'moonBiome' => $w['moonBiome'], 'moonSubBiome' => $w['moonSubBiome'], 'owner' => $w['owner'],
                    'npcRace' => $w['npcRace'] ?? '', 'npcName' => $w['npcName'] ?? '', 'npcAlignment' => $w['npcAlignment'] ?? '', 'npcFocus' => $w['npcFocus'] ?? '', 'npcPower' => (int)($w['npcPower'] ?? 0),
                ]), ENT_QUOTES);
                $moonOnclick = '';
                if ($w['moons'] > 0) {
                    $md = htmlspecialchars(json_encode([
                        'parent' => $w['name'], 'coord' => $w['coord'],
                        'count'  => $w['moons'], 'class' => $w['moonClass'], 'moonBiome' => $w['moonBiome'], 'moonSubBiome' => $w['moonSubBiome'],
                    ]), ENT_QUOTES);
                    $moonOnclick = ' onclick="showMoonDetail(' . $md . ')" style="cursor:pointer;text-decoration:underline;color:#8cf"';
                }
                echo '<tr>';
                echo '<td>#' . fnum($w['idx']) . '</td>';
                echo '<td>' . h($w['coord']) . '</td>';
                echo '<td><a href="javascript:void(0)" onclick="showPlanetDetail(' . $pd . ')" style="color:#adf">' . h($w['name']) . '</a></td>';
                echo '<td>' . h($w['type']) . '</td>';
                echo '<td>' . h($w['biome']) . '</td>';
                echo '<td>' . h($w['subBiome']) . '</td>';
                echo '<td>' . fnum($w['habitability']) . '%</td>';
                echo '<td' . $moonOnclick . '>' . fnum($w['moons']) . '</td>';
                echo '<td' . $moonOnclick . '>' . h($w['moonClass']) . '</td>';
                echo '<td>' . h($plagueLabel) . '</td>';
                echo '<td>' . h($waterLabel) . '</td>';
                echo '<td>';
                if (($w['npcRace'] ?? '') !== '' && ($w['owner'] ?? '') !== 'Unclaimed') {
                    $npcAlignColor = ($w['npcAlignment'] === 'friendly') ? '#6f6' : (($w['npcAlignment'] === 'neutral') ? '#ff9' : '#f77');
                    echo '<span title="' . h($w['npcDescription'] ?? '') . '">' . h($w['npcName'] . ' Territory') . ' <em style="color:' . $npcAlignColor . '">[' . h($w['npcAlignment']) . ']</em></span>';
                } else {
                    echo h($w['owner']);
                }
                echo '</td>';
                echo '</tr>';
            }
        echo '</table>';
        echo '</div>';
    }

    if ($sub === 'planets') {
        echo '<div class="card"><h4>Colony Totals</h4>';
        echo '<p><strong>Owned Colonies:</strong> ' . fnum(count($planets)) . ' / ' . fnum($uCfg['maxColonies']) . '</p>';
        echo '<p><strong>Available Colony Slots:</strong> ' . fnum(max(0, $uCfg['maxColonies'] - count($planets))) . '</p>';
        echo '<p><strong>Universe Worlds:</strong> ' . fnum($uCfg['maxWorlds']) . ' | <strong>Moon Capacity:</strong> ' . fnum($uCfg['maxMoons']) . '</p>';
        echo '</div>';

        echo '<div class="card"><h4>Planetary Actions</h4>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'empire\',\'planets\'); return false">Open Empire Planet Module</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\'); return false">Open Fleet Dock</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'technology\',\'get\',\'mainDisplay\'); return false">Open Technology Upgrades</a></p>';
        echo '</div>';

        echo '<div class="card"><h4>Colony Sustainment</h4>';
        echo '<p><strong>Food Reserves:</strong> ' . fnum($resourceHub['current']['food']) . '</p>';
        echo '<p><strong>Water Reserves:</strong> ' . fnum($resourceHub['current']['water']) . '</p>';
        echo '<p><strong>Energy Grid:</strong> ' . fnum($resourceHub['current']['energy']) . '</p>';
        echo '<p><strong>Population:</strong> ' . fnum($resourceHub['current']['population']) . '</p>';
        echo '</div>';

        echo '<div class="card full"><h4>Rename Owned Holdings</h4>';
        echo '<form method="get" action="modules/pages.php">';
        echo '<input type="hidden" name="id" value="universe">';
        echo '<input type="hidden" name="atype" value="planets">';
        echo '<input type="hidden" name="time" value="' . h((string)time()) . '">';
        echo '<input type="hidden" name="cmd" value="rename_entity">';
        echo '<p><label>Entity <select name="entity"><option value="planet">Planet</option><option value="moon">Moon</option></select></label> ';
        if (count($planetRegistryRows) > 0) {
            echo '<label>Planet <select name="pid">';
            foreach ($planetRegistryRows as $planetRow) {
                echo '<option value="' . (int)$planetRow['pid'] . '">' . h((string)$planetRow['plnt_name']) . '</option>';
            }
            echo '</select></label> ';
        } else {
            echo '<span>No colonies yet.</span> ';
        }
        if (count($ownedMoonRows) > 0) {
            echo '<label>Moon <select name="moon_id">';
            foreach ($ownedMoonRows as $moonRow) {
                echo '<option value="' . (int)$moonRow['moon_id'] . '">' . h((string)($moonRow['moon_name'] !== '' ? $moonRow['moon_name'] : ('Moon #' . $moonRow['moon_id']))) . '</option>';
            }
            echo '</select></label> ';
        } else {
            echo '<span>No moon records yet.</span> ';
        }
        echo '<label>New Name <input type="text" name="new_name" maxlength="64" value="" /></label> ';
        echo '<button type="submit">Rename</button></p>';
        echo '</form>';
        echo '</div>';

        echo '<div class="card full"><h4>Planet, Moon, and Biome Registry</h4>';
        echo '<p>Showing worlds ' . fnum($worldSlice['start']) . '-' . fnum($worldSlice['end']) . ' of ' . fnum($worldSlice['total']) . ' | Page ' . fnum($worldSlice['page']) . ' / ' . fnum($worldSlice['maxPage']) . '</p>';
        $prev = max(1, $worldSlice['page'] - 1);
        $next = min($worldSlice['maxPage'], $worldSlice['page'] + 1);
        $qsBase = "modules/pages.php?id=universe&atype=planets&pp=" . (int)$worldSlice['perPage'] . "&time=";
        echo '<p>';
        echo '<a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $qsBase . '\'+(new Date().getTime())+\'&p=1\',true); return false" style="margin-right:8px;">First</a>';
        echo '<a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $qsBase . '\'+(new Date().getTime())+\'&p=' . $prev . '\',true); return false" style="margin-right:8px;">Prev</a>';
        echo '<a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $qsBase . '\'+(new Date().getTime())+\'&p=' . $next . '\',true); return false" style="margin-right:8px;">Next</a>';
        echo '<a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $qsBase . '\'+(new Date().getTime())+\'&p=' . $worldSlice['maxPage'] . '\',true); return false">Last</a>';
        echo '</p>';
        echo '<p><em>Click any planet or moon count to view details</em></p>';

        $actBase = "modules/pages.php?id=universe&atype=planets&p=" . (int)$worldSlice['page'] . "&pp=" . (int)$worldSlice['perPage'] . "&cmd=colonize&target=";
        $autoTarget = 0;
        foreach ($worldSlice['rows'] as $cand) {
            if ((string)$cand['owner'] === 'Unclaimed' && (int)$cand['habitability'] >= 46) {
                $autoTarget = (int)$cand['idx'];
                break;
            }
        }

        echo '<div class="card"><h4>Colonization Console</h4>';
        echo '<p>Requirements: 46%+ habitability, action turns, Naquadah, deuterium, food, water, and population.</p>';
        if ($autoTarget > 0) {
            echo '<p><a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $actBase . $autoTarget . '&time=\'+(new Date().getTime()),true); return false">Auto-Colonize Best Candidate On This Page</a></p>';
        } else {
            echo '<p>No eligible unclaimed world on this page. Try next page or lower requirements in strategy.</p>';
        }
        echo '</div>';

        echo '<table class="mini-table" border="0" width="100%"><tr><th align="left">#</th><th align="left">Coordinate</th><th align="left">World</th><th align="left">Type</th><th align="left">Biome</th><th align="left">Sub-Biome</th><th align="left">Habitability</th><th align="left">Moons</th><th align="left">Resource Signature</th><th align="left">Plague</th><th align="left">Water</th><th align="left">Status</th><th align="left">Action</th></tr>';
        foreach ($worldSlice['rows'] as $w) {
            $plagueRows = universePlagueRowsForWorld($s, $uid, (int)$w['idx']);
            $plagueLabel = universePlagueSummaryText($plagueRows);
            $waterRows = universeWaterRowsForWorld($s, $uid, (int)$w['idx']);
            $waterLabel = universeWaterSummaryText($waterRows);
            $resSig = 'M' . fnum($w['metal']) . ' / C' . fnum($w['crystal']) . ' / D' . fnum($w['deut']);
            $moonSig = ($w['moons'] > 0) ? (fnum($w['moons']) . ' (' . h($w['moonClass']) . ')') : '0';
            $costs = universeColonizeCosts($w);
            $pd = htmlspecialchars(json_encode([
            'idx'    => $w['idx'],
                'coord'  => $w['coord'],  'name'  => $w['name'],  'type'  => $w['type'],
                'biome'  => $w['biome'],  'subBiome' => $w['subBiome'], 'hab'   => $w['habitability'], 'slots' => $w['slots'],
                'metal'  => $w['metal'],  'crystal' => $w['crystal'], 'deut' => $w['deut'],
                'moons'  => $w['moons'],  'moonClass' => $w['moonClass'], 'moonBiome' => $w['moonBiome'], 'moonSubBiome' => $w['moonSubBiome'], 'owner' => $w['owner'],
                'npcRace' => $w['npcRace'] ?? '', 'npcName' => $w['npcName'] ?? '', 'npcAlignment' => $w['npcAlignment'] ?? '', 'npcFocus' => $w['npcFocus'] ?? '', 'npcPower' => (int)($w['npcPower'] ?? 0),
            ]), ENT_QUOTES);
            $moonOnclick = '';
            if ($w['moons'] > 0) {
                $md = htmlspecialchars(json_encode([
                    'parent' => $w['name'], 'coord' => $w['coord'],
                    'count'  => $w['moons'], 'class' => $w['moonClass'], 'moonBiome' => $w['moonBiome'], 'moonSubBiome' => $w['moonSubBiome'],
                ]), ENT_QUOTES);
                $moonOnclick = ' onclick="showMoonDetail(' . $md . ')" style="cursor:pointer;text-decoration:underline;color:#8cf"';
            }
            echo '<tr>';
            echo '<td>#' . fnum($w['idx']) . '</td>';
            echo '<td>' . h($w['coord']) . '</td>';
            echo '<td><a href="javascript:void(0)" onclick="showPlanetDetail(' . $pd . ')" style="color:#adf">' . h($w['name']) . '</a></td>';
            echo '<td>' . h($w['type']) . '</td>';
            echo '<td>' . h($w['biome']) . '</td>';
            echo '<td>' . h($w['subBiome']) . '</td>';
            echo '<td>' . fnum($w['habitability']) . '%</td>';
            echo '<td' . $moonOnclick . '>' . $moonSig . '</td>';
            echo '<td>' . $resSig . '</td>';
            echo '<td>' . h($plagueLabel) . '</td>';
            echo '<td>' . h($waterLabel) . '</td>';
            echo '<td>';
            if (($w['npcRace'] ?? '') !== '' && ($w['owner'] ?? '') !== 'Unclaimed') {
                $npcAlignColor = ($w['npcAlignment'] === 'friendly') ? '#6f6' : (($w['npcAlignment'] === 'neutral') ? '#ff9' : '#f77');
                echo '<span title="' . h($w['npcDescription'] ?? '') . '">' . h($w['npcName'] . ' Territory') . ' <em style="color:' . $npcAlignColor . '">[' . h($w['npcAlignment']) . ']</em></span>';
            } else {
                echo h($w['owner']);
            }
            echo '</td>';
            if ((string)$w['owner'] === 'Unclaimed' && (int)$w['habitability'] >= 46) {
                echo '<td><a href="javascript:void(0)" onclick="httpRequest(\'GET\',\'' . $actBase . (int)$w['idx'] . '&time=\'+(new Date().getTime()),true); return false">Colonize</a><br><small>' . fnum($costs['naq']) . ' Naq / ' . fnum($costs['turns']) . 'T</small></td>';
            } elseif ((string)$w['owner'] === 'Unclaimed') {
                echo '<td><small>Needs 46%+ hab</small><br><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . (int)$w['idx'] . '\'); return false">Open Build</a></td>';
            } else {
                echo '<td><small>Owned</small><br><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . (int)$w['idx'] . '\'); return false">Open Build</a></td>';
            }
            echo '</tr>';
        }
        echo '</table></div>';

        $selectedBuildWorld = max(1, min((int)$uCfg['maxWorlds'], $targetWorld > 0 ? $targetWorld : (int)$worldSlice['start']));
        $selectedBuildData = universeWorldByIndex($uid, $planets, $selectedBuildWorld, $uCfg);
        echo '<div class="card full"><h4>Planet and Moon Field Build Console</h4>';
        echo '<p><strong>Selected World:</strong> #' . fnum($selectedBuildWorld) . ' ' . h((string)$selectedBuildData['coord']) . ' | <strong>Biome:</strong> ' . h((string)$selectedBuildData['biome']) . ' / ' . h((string)$selectedBuildData['subBiome']) . '</p>';
        echo '<p><strong>Primary Moon Biome:</strong> ' . h((string)$selectedBuildData['moonBiome']) . ' / ' . h((string)$selectedBuildData['moonSubBiome']) . ' | <strong>Moon Count:</strong> ' . fnum((int)$selectedBuildData['moons']) . '</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . $selectedBuildWorld . '&cmd=uni_city_found&ftype=planet\'); return false">Found Planet City</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . $selectedBuildWorld . '&cmd=uni_field_expand&ftype=planet\'); return false">Expand Planet Fields</a></p>';
        $selectedWorldPlagues = universePlagueRowsForWorld($s, $uid, $selectedBuildWorld);
        echo '<p><strong>Plague Controls:</strong> <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . $selectedBuildWorld . '&cmd=uni_create_plague\'); return false">Create Planet Plague</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . $selectedBuildWorld . '&moon=1&ftype=moon&cmd=uni_create_moon_plague\'); return false">Create Moon Plague</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . $selectedBuildWorld . '&cmd=uni_create_biome_plague\'); return false">Create Biome Plague</a></p>';
        $selectedWorldWater = universeWaterRowsForWorld($s, $uid, $selectedBuildWorld);
        echo '<p><strong>Water Controls:</strong> <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . $selectedBuildWorld . '&cmd=uni_create_water\'); return false">Create Planet Water Source</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . $selectedBuildWorld . '&moon=1&ftype=moon&cmd=uni_create_moon_water\'); return false">Create Moon Water Source</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . $selectedBuildWorld . '&cmd=uni_create_biome_water\'); return false">Create Biome Water Source</a></p>';
        if (count($selectedWorldPlagues) === 0) {
            echo '<p>No active plagues on this world yet. Seeds can be created for the planet, a moon, or the biome profile.</p>';
        } else {
            echo '<ul>';
            foreach ($selectedWorldPlagues as $plague) {
                $targetLabel = ((string)$plague['target_type'] === 'moon') ? ('Moon #' . fnum((int)$plague['moon_no'])) : (((string)$plague['target_type'] === 'biome') ? 'Biome' : 'Planet');
                echo '<li><strong>' . h((string)$plague['plague_name']) . '</strong> on ' . h($targetLabel) . ' (severity ' . fnum((int)$plague['severity']) . '): ' . h((string)$plague['symptom']) . ' Effect ' . fnum(abs((int)$plague['effect_value'])) . ' ' . h((string)$plague['effect_type']) . '.</li>';
            }
            echo '</ul>';
        }
        if (count($selectedWorldWater) === 0) {
            echo '<p>No active water sources on this world yet. Water can be seeded for the planet, a moon, or the biome profile.</p>';
        } else {
            echo '<ul>';
            foreach ($selectedWorldWater as $water) {
                $targetLabel = ((string)$water['target_type'] === 'moon') ? ('Moon #' . fnum((int)$water['moon_no'])) : (((string)$water['target_type'] === 'biome') ? 'Biome' : 'Planet');
                echo '<li><strong>' . h((string)$water['water_name']) . '</strong> on ' . h($targetLabel) . ' (potency ' . fnum((int)$water['potency']) . '): ' . h((string)$water['description']) . ' Output ' . fnum((int)$water['effect_value']) . ' ' . h((string)$water['effect_type']) . '.</li>';
            }
            echo '</ul>';
        }
        if ((int)$selectedBuildData['moons'] > 0) {
            echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . $selectedBuildWorld . '&moon=1&ftype=moon&cmd=uni_city_found\'); return false">Found Moon City #1</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . $selectedBuildWorld . '&moon=1&ftype=moon&cmd=uni_field_expand\'); return false">Expand Moon #1 Fields</a></p>';
        }
        echo '<p><strong>Build:</strong> <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . $selectedBuildWorld . '&ftype=planet&cmd=uni_field_build&bld=habdome\'); return false">Habitat Dome</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . $selectedBuildWorld . '&ftype=planet&cmd=uni_field_build&bld=foundry\'); return false">Foundry Grid</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . $selectedBuildWorld . '&ftype=planet&cmd=uni_field_build&bld=reactor\'); return false">Fusion Reactor</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . $selectedBuildWorld . '&ftype=planet&cmd=uni_field_build&bld=hydrolab\'); return false">Hydro Lab</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . $selectedBuildWorld . '&ftype=planet&cmd=uni_field_build&bld=bastion\'); return false">Bastion District</a></p>';
        echo '<p><strong>Moon Build #1:</strong> <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . $selectedBuildWorld . '&moon=1&ftype=moon&cmd=uni_field_build&bld=habdome\'); return false">Habitat Dome</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . $selectedBuildWorld . '&moon=1&ftype=moon&cmd=uni_field_build&bld=reactor\'); return false">Fusion Reactor</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets&target=' . $selectedBuildWorld . '&moon=1&ftype=moon&cmd=uni_field_build&bld=bastion\'); return false">Bastion District</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>City Profiles and Field Capacity</h4>';
        if (count($universeColonyProfiles) === 0) {
            echo '<p>No colony field profiles found for selected world.</p>';
        } else {
            echo '<table class="mini-table" border="0" width="100%">';
            echo '<tr><th align="left">Target</th><th align="left">City</th><th align="left">Biome</th><th align="left">Sub-Biome</th><th align="left">Fields Used</th><th align="left">Infrastructure Tier</th></tr>';
            foreach ($universeColonyProfiles as $pr) {
                $targetLabel = ((string)$pr['target_type'] === 'moon') ? ('Moon #' . fnum((int)$pr['moon_no'])) : 'Planet';
                echo '<tr>';
                echo '<td>' . h($targetLabel) . '</td>';
                echo '<td>' . h((string)$pr['city_name']) . '</td>';
                echo '<td>' . h((string)$pr['biome']) . '</td>';
                echo '<td>' . h((string)$pr['sub_biome']) . '</td>';
                echo '<td>' . fnum((int)$pr['field_used']) . ' / ' . fnum((int)$pr['field_total']) . '</td>';
                echo '<td>T' . fnum((int)$pr['infrastructure_tier']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</div>';

        echo '<div class="card full"><h4>Field Build Log</h4>';
        if (count($universeColonyFields) === 0) {
            echo '<p>No field builds yet on this world.</p>';
        } else {
            echo '<table class="mini-table" border="0" width="100%">';
            echo '<tr><th align="left">Field</th><th align="left">Target</th><th align="left">Building</th><th align="left">Power Draw</th><th align="left">Population Use</th><th align="left">Built At</th></tr>';
            foreach ($universeColonyFields as $fb) {
                $targetLabel = ((string)$fb['target_type'] === 'moon') ? ('Moon #' . fnum((int)$fb['moon_no'])) : 'Planet';
                echo '<tr>';
                echo '<td>#' . fnum((int)$fb['slot_no']) . '</td>';
                echo '<td>' . h($targetLabel) . '</td>';
                echo '<td>' . h((string)$fb['building_name']) . ' (Lv' . fnum((int)$fb['building_level']) . ')</td>';
                echo '<td>' . fnum((int)$fb['power_draw']) . '</td>';
                echo '<td>' . fnum((int)$fb['population_use']) . '</td>';
                echo '<td>' . h(date('Y-m-d H:i:s', (int)$fb['created_ts'])) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</div>';
    }

    if ($sub === 'objects') {
        echo '<div class="card"><h4>Interstellar Recovery</h4>';
        echo '<p>Use debris and asteroid routes to power recycler loops and rebuild tempo.</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'market\',\'get\',\'mainDisplay\'); return false">Open Market Logistics</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'bank\',\'get\',\'mainDisplay\'); return false">Open Treasury Routing</a></p>';
        echo '</div>';

        echo '<div class="card"><h4>Scout Loop</h4>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'spy\',\'get\',\'mainDisplay\'); return false">Open Spy Module</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'rank\',\'get\',\'mainDisplay\'); return false">Open Regional Rankings</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Interstellar Object Density Matrix</h4>';
        echo '<table class="mini-table" border="0" width="100%"><tr><th align="left">Galaxy</th><th align="left">Asteroid Belts</th><th align="left">Debris Fields</th><th align="left">Nebulae</th><th align="left">Comet Streams</th><th align="left">Wormholes</th><th align="left">Ancient Ruins</th></tr>';
        foreach ($universe['objects'] as $obj) {
            echo '<tr><td>' . h($obj['galaxy']) . '</td><td>' . fnum($obj['asteroidBelts']) . '</td><td>' . fnum($obj['debrisFields']) . '</td><td>' . fnum($obj['nebulae']) . '</td><td>' . fnum($obj['cometStreams']) . '</td><td>' . fnum($obj['wormholes']) . '</td><td>' . fnum($obj['ancientRuins']) . '</td></tr>';
        }
        echo '</table></div>';
    }

    if ($sub === 'expedition') {
        echo '<div class="card"><h4>Mission Dispatch</h4>';
        echo '<p>Target UID dispatch:</p>';
        echo '<p><input id="uniTargetUid" type="number" min="1" value="1" style="width:110px"> ';
        echo '<a href="javascript:void(0)" onclick="var t=parseInt(document.getElementById(\'uniTargetUid\').value,10)||0;if(t>0){sendData(\'action\',\'get\',t,\'spy\');} return false">Spy</a> | ';
        echo '<a href="javascript:void(0)" onclick="var t=parseInt(document.getElementById(\'uniTargetUid\').value,10)||0;if(t>0){sendData(\'action\',\'get\',t,\'raid\');} return false">Raid</a> | ';
        echo '<a href="javascript:void(0)" onclick="var t=parseInt(document.getElementById(\'uniTargetUid\').value,10)||0;if(t>0){sendData(\'action\',\'get\',t,\'attack\');} return false">Attack</a></p>';
        echo '</div>';

        echo '<div class="card"><h4>Expansion Workflows</h4>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\'); return false">Fleet Dock</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'stations\',\'get\',\'mainDisplay\'); return false">Orbital Stations Command</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'hyperspace\',\'get\',\'mainDisplay\'); return false">Jumpgate and Hyperspace Command</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'upgrade_shipyard\'); return false">Upgrade Shipyard</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'upgrade_bay\'); return false">Upgrade Mothership Bay</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'planets\'); return false">Candidate Worlds</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'technology\',\'get\',\'mainDisplay\'); return false">Astro/Tech Upgrades</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Expedition and Colonization Doctrine</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Mission</th><th align="left">Purpose</th><th align="left">Typical Cost</th><th align="left">Risk Tier</th></tr>';
        echo '<tr><td>Deep Expedition</td><td>Find debris, anomalies, and bonus resources</td><td>Fleet + covert turns</td><td>Medium</td></tr>';
        echo '<tr><td>Colonization Wave</td><td>Claim high-habitability worlds with moon potential</td><td>Fleet + economy reserve</td><td>Medium-High</td></tr>';
        echo '<tr><td>Debris Recovery</td><td>Recycle post-combat fields into growth capital</td><td>Recycler allocation + travel time</td><td>Low</td></tr>';
        echo '<tr><td>Rapid Strike Route</td><td>Use wormhole lanes for pressure projection</td><td>Attack turns + logistics</td><td>High</td></tr>';
        echo '</table></div>';
    }

    if ($sub === 'bases') {
        echo '<div class="card"><h4>Stations and Bases Command</h4>';
        echo '<p>Build Space Stations, Starbases, and Moon Bases to anchor fleet operations and improve expedition consistency.</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'stations\',\'get\',\'mainDisplay\'); return false">Open Orbital Base Command</a></p>';
        echo '</div>';

        echo '<div class="card"><h4>Integration Paths</h4>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'fleet\'); return false">Military Fleet Directorate</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\'); return false">Fleet Dock and Missions</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'expedition\'); return false">Expedition Planner</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Orbital Infrastructure Doctrine</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Installation</th><th align="left">Primary Role</th><th align="left">Synergy</th><th align="left">Suggested Timing</th></tr>';
        echo '<tr><td>Space Station</td><td>Orbital logistics, ship throughput, and fleet support</td><td>Shipyard and Mega Forge output cycles</td><td>Early-mid expansion</td></tr>';
        echo '<tr><td>Starbase</td><td>Defensive projection and warfront staging</td><td>Fleet Dock mission readiness and deterrence</td><td>Mid-game before sustained wars</td></tr>';
        echo '<tr><td>Moon Base</td><td>Surveillance, scan depth, and expedition resilience</td><td>Universe expedition routes and object recovery</td><td>After first stable offensive wing</td></tr>';
        echo '</table></div>';
    }

    if ($sub === 'travel') {
        echo '<div class="card"><h4>Jumpgate and Stargate Transit</h4>';
        echo '<p>Build gate infrastructure and launch hyperspace wings for transfer, expedition, and colonization operations.</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'hyperspace\',\'get\',\'mainDisplay\'); return false">Open Hyperspace Transit Command</a></p>';
        echo '</div>';

        echo '<div class="card"><h4>Travel Integrations</h4>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'military\',\'fleet\'); return false">Military Fleet Directorate</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'fleetdock\',\'get\',\'mainDisplay\'); return false">Fleet Dock and Mission Queue</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'expedition\'); return false">Expedition Operations</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Hyperspace Operations Doctrine</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">System</th><th align="left">Role</th><th align="left">Primary Resource Pressure</th><th align="left">Best Usage Window</th></tr>';
        echo '<tr><td>Jump Gate</td><td>Local lane initialization and deployment tempo</td><td>Metal + deuterium for lane maintenance</td><td>Early expansion and first war mobilization</td></tr>';
        echo '<tr><td>Stargate</td><td>Deep interstellar routing and fleet projection</td><td>Crystal + deuterium for stable long routes</td><td>Mid-game multi-front campaigns</td></tr>';
        echo '<tr><td>Hyperspace Core</td><td>Cooldown compression and transit efficiency</td><td>Deuterium + sustainment (food/water)</td><td>Late-stage expedition and colonization loops</td></tr>';
        echo '</table></div>';
    }

    if ($sub === 'lanes') {
        echo '<div class="card full"><h4>Transit Lanes</h4><p>Use lane planning to distribute fleets and reduce route bottlenecks.</p><table class="mini-table" border="0" width="100%"><tr><th align="left">Lane Type</th><th align="left">Best Use</th></tr><tr><td>Inner Lanes</td><td>Fast military response</td></tr><tr><td>Outer Lanes</td><td>Colonization and deep expeditions</td></tr></table></div>';
        echo '<div class="card full"><h4>Lane Risk Bands</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Band</th><th align="left">ETA</th><th align="left">Interception Risk</th></tr>';
        echo '<tr><td>Secure</td><td>Slowest</td><td>Minimal - convoy escort optional</td></tr>';
        echo '<tr><td>Standard</td><td>Balanced</td><td>Moderate - escort cargo</td></tr>';
        echo '<tr><td>Contested</td><td>Fastest</td><td>High - military transits only</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Lane Discipline</h4><p>Route convoys along secure lanes and reserve contested lanes for strike windows. Congested lanes are where convoys get intercepted.</p></div>';
    }

    if ($sub === 'anomalies') {
        echo '<div class="card"><h4>Anomaly Index</h4><p>Catalog wormholes, ruins, and volatile fields for expedition targeting.</p><p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'objects\'); return false">Open Object Scanner</a></p></div>';
        echo '<div class="card full"><h4>Anomaly Types</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Type</th><th align="left">Risk</th><th align="left">Payout</th></tr>';
        echo '<tr><td>Wormhole</td><td>High</td><td>Instant deep-lane transit</td></tr>';
        echo '<tr><td>Ruins</td><td>Medium</td><td>Artefact and tech loot</td></tr>';
        echo '<tr><td>Volatile Field</td><td>Medium</td><td>Resource spikes</td></tr>';
        echo '<tr><td>Debris Cloud</td><td>Low</td><td>Recyclable fleet remains</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Escort Rule</h4><p>High-risk anomalies demand escort coverage. Scouting value before committing expeditions avoids wasted turns.</p></div>';
    }

    if ($sub === 'seeds') {
        echo '<div class="card"><h4>Seed Planner</h4><p>Colony placement guidance across the procedural universe seed. Match biomes to colony roles and spread expansion for resilience.</p></div>';
        echo '<div class="card full"><h4>Seed Placement Grid</h4>';
        if (count($seedSlice['rows']) === 0) {
            echo '<p>No seed systems available on this page.</p>';
        } else {
            echo '<p>Showing seeds ' . fnum($seedSlice['start']) . '-' . fnum($seedSlice['end']) . ' of ' . fnum($seedSlice['total']) . ' | Page ' . fnum($seedSlice['page']) . ' / ' . fnum($seedSlice['maxPage']) . '</p>';
            echo '<table class="mini-table" border="0" width="100%">';
            echo '<tr><th align="left">Seed</th><th align="left">Key</th><th align="left">Star</th><th align="left">Biome</th><th align="left">Hazard</th><th align="left">Richness</th><th align="left">Sentinels</th><th align="left">Planets</th><th align="left">Moons</th><th align="left">Bookmark</th></tr>';
            foreach ($seedSlice['rows'] as $sw) {
                $isBookmarked = false;
                foreach ($seedBookmarks as $bm) {
                    if ((string)$bm['seed_key'] === (string)$sw['seedKey']) {
                        $isBookmarked = true;
                        break;
                    }
                }
                $bLabel = $isBookmarked ? 'Bookmarked' : 'Bookmark';
                $bCmd = $isBookmarked ? '' : '&cmd=seed_bookmark&target=' . (int)$sw['index'];
                echo '<tr>';
                echo '<td>#' . fnum((int)$sw['index']) . '</td>';
                echo '<td><small>' . h((string)$sw['seedKey']) . '</small></td>';
                echo '<td>' . h((string)$sw['star']) . '</td>';
                echo '<td>' . h((string)$sw['biome']) . '</td>';
                echo '<td>' . h((string)$sw['hazard']) . '</td>';
                echo '<td>' . fnum((int)$sw['richness']) . '%</td>';
                echo '<td>' . fnum((int)$sw['sentinel']) . '</td>';
                echo '<td>' . fnum((int)$sw['planets']) . '</td>';
                echo '<td>' . fnum((int)$sw['moons']) . '</td>';
                echo '<td>';
                if ($bCmd !== '') {
                    echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'seeds' . $bCmd . '\'); return false">' . h($bLabel) . '</a>';
                } else {
                    echo h($bLabel);
                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '<p><strong>Pages:</strong> ';
            for ($sp = 1; $sp <= $seedSlice['maxPage']; $sp++) {
                $sLabel = ($sp === $seedSlice['page']) ? ('[' . $sp . ']') : (string)$sp;
                echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'seeds&p=' . $sp . '\'); return false">' . h($sLabel) . '</a> ';
            }
            echo '</p>';
        }
        echo '</div>';
        echo '<div class="card full"><h4>Biome Match Guidance</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Biome</th><th align="left">Colony Role</th><th align="left">Watch For</th></tr>';
        echo '<tr><td>Lush / Oceanic</td><td>Food and population anchor</td><td>Balanced default</td></tr>';
        echo '<tr><td>Arid / Volcanic</td><td>Metal and crystal extraction</td><td>Water cost</td></tr>';
        echo '<tr><td>Frozen / Toxic</td><td>Deuterium and hazard research</td><td>High upkeep</td></tr>';
        echo '<tr><td>Irradiated / Relic</td><td>Artefact and anomaly hunting</td><td>Sentinel pressure</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Bookmarks</h4><p>Bookmarked seeds persist for quick colony planning. Bookmark spread targets and high-richness systems first.</p></div>';
    }

    if ($sub === 'events') {
        $evt = $universeEventState ?: (object)['event_cycle' => 1, 'current_event' => 'Calm Front', 'event_points' => 0, 'threat_level' => 20, 'last_event_ts' => 0];
        $lastEvtText = ((int)$evt->last_event_ts > 0) ? date('Y-m-d H:i:s', (int)$evt->last_event_ts) : 'Never';
        $openEvents = 0;
        $resolvedEvents = 0;
        $eventStatsQ = $s->query("SELECT
            SUM(CASE WHEN resolution_status='open' THEN 1 ELSE 0 END) AS open_count,
            SUM(CASE WHEN resolution_status='resolved' THEN 1 ELSE 0 END) AS resolved_count
            FROM universe_event_log WHERE uid=" . $uid);
        if ($eventStatsQ) {
            $es = $eventStatsQ->fetch_object();
            $openEvents = (int)($es->open_count ?? 0);
            $resolvedEvents = (int)($es->resolved_count ?? 0);
        }
        echo '<div class="card full"><div class="feature-hero"><img src="images/ui/universe-archive.svg" alt="Universe events" /><div><h4>Universe Event Control</h4><p>Track active anomalies, response cycles, and galactic threat pressure.</p></div></div>';
        echo '<p><strong>Cycle:</strong> ' . fnum((int)$evt->event_cycle) . ' | <strong>Current Event:</strong> ' . h((string)$evt->current_event) . '</p>';
        echo '<p><strong>Event Points:</strong> ' . fnum((int)$evt->event_points) . ' | <strong>Threat Level:</strong> ' . fnum((int)$evt->threat_level) . '</p>';
        echo '<p><strong>Open Events:</strong> ' . fnum($openEvents) . ' | <strong>Resolved:</strong> ' . fnum($resolvedEvents) . ' | <strong>Last Scan:</strong> ' . h($lastEvtText) . '</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'events&cmd=uni_event_scan&gal=1\'); return false">Scan Galaxy 1</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'events&cmd=uni_event_scan&gal=2\'); return false">Scan Galaxy 2</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'events&cmd=uni_event_resolve\'); return false">Resolve Oldest Event</a></p>';
        echo '</div>';

        $eventRows = [];
        $eventLogQ = $s->query("SELECT event_id,galaxy_no,event_name,event_type,resolution_status,reward_points,UNIX_TIMESTAMP(created_at) AS created_ts
            FROM universe_event_log WHERE uid=" . $uid . " ORDER BY event_id DESC LIMIT 12");
        if ($eventLogQ) {
            while ($er = $eventLogQ->fetch_assoc()) {
                $eventRows[] = $er;
            }
        }
        echo '<div class="card full"><h4>Event Timeline</h4>';
        if (count($eventRows) === 0) {
            echo '<p>No event records yet.</p>';
        } else {
            echo '<table class="mini-table" border="0" width="100%">';
            echo '<tr><th align="left">ID</th><th align="left">Galaxy</th><th align="left">Event</th><th align="left">Type</th><th align="left">Status</th><th align="left">Points</th><th align="left">Detected</th></tr>';
            foreach ($eventRows as $er) {
                echo '<tr>';
                echo '<td>#' . fnum((int)$er['event_id']) . '</td>';
                echo '<td>G' . fnum((int)$er['galaxy_no']) . '</td>';
                echo '<td>' . h((string)$er['event_name']) . '</td>';
                echo '<td>' . h((string)$er['event_type']) . '</td>';
                echo '<td>' . h((string)$er['resolution_status']) . '</td>';
                echo '<td>' . fnum((int)$er['reward_points']) . '</td>';
                echo '<td>' . h(date('Y-m-d H:i:s', (int)$er['created_ts'])) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</div>';
    }

    if ($sub === 'worldboss') {
        $boss = $universeBossState ?: (object)['boss_name' => 'Dormant Leviathan', 'boss_level' => 1, 'boss_hp' => 0, 'boss_hp_max' => 0, 'status' => 'idle', 'last_spawn_ts' => 0, 'last_defeat_ts' => 0];
        $evt = $universeEventState ?: (object)['event_points' => 0, 'threat_level' => 20];
        $storyAct = max(1, min(12, (int)($universeStoryState->current_act ?? 1)));
        $arcBoss = formalArcBossProfile((int)$boss->boss_level, (int)$evt->threat_level, $storyAct);
        $hpPct = ((int)$boss->boss_hp_max > 0) ? (int)round(((int)$boss->boss_hp / (int)$boss->boss_hp_max) * 100) : 0;
        $lastSpawnText = ((int)$boss->last_spawn_ts > 0) ? date('Y-m-d H:i:s', (int)$boss->last_spawn_ts) : 'Never';
        $lastDefeatText = ((int)$boss->last_defeat_ts > 0) ? date('Y-m-d H:i:s', (int)$boss->last_defeat_ts) : 'Never';
        echo '<div class="card full"><div class="feature-hero"><img src="images/ui/operations-console.svg" alt="World boss" /><div><h4>Arc Boss Command</h4><p>Deploy your strike wings against the current act boss and turn galactic threat into campaign momentum.</p></div></div>';
        echo '<p><strong>Arc Boss:</strong> ' . h((string)$arcBoss['name']) . ' | <strong>Phase:</strong> ' . h((string)$arcBoss['phase']) . ' | <strong>Level:</strong> ' . fnum((int)$arcBoss['level']) . '</p>';
        echo '<p><strong>Current Encounter:</strong> ' . h((string)$boss->boss_name) . ' | <strong>HP:</strong> ' . fnum((int)$boss->boss_hp) . ' / ' . fnum((int)$boss->boss_hp_max) . ' (' . fnum($hpPct) . '%)</p>';
        echo '<p><strong>Forecast:</strong> ' . fnum((int)$arcBoss['hp']) . ' projected arc HP | <strong>Reward:</strong> ' . fnum((int)$arcBoss['reward']) . ' Naquadah</p>';
        echo '<p><strong>Event Points:</strong> ' . fnum((int)$evt->event_points) . ' | <strong>Threat Level:</strong> ' . fnum((int)$evt->threat_level) . '</p>';
        echo '<p><strong>Last Spawn:</strong> ' . h($lastSpawnText) . ' | <strong>Last Defeat:</strong> ' . h($lastDefeatText) . '</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'worldboss&cmd=uni_boss_spawn\'); return false">Spawn Arc Boss</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'worldboss&cmd=uni_boss_attack\'); return false">Attack Arc Boss</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'events&cmd=uni_event_resolve\'); return false">Stabilize Event Front</a></p>';
        echo '</div>';
    }

    if ($sub === 'story') {
        $story = $universeStoryState ?: (object)['prologue_unlocked' => 0, 'current_act' => 1, 'current_chapter' => 1, 'chapter_points' => 0, 'completed_acts' => 0, 'last_story_ts' => 0];
        $evt = $universeEventState ?: (object)['event_points' => 0];
        $lastStoryText = ((int)$story->last_story_ts > 0) ? date('Y-m-d H:i:s', (int)$story->last_story_ts) : 'Never';
        echo '<div class="card full"><div class="feature-hero"><img src="images/ui/empire-portal.svg" alt="Story campaign" /><div><h4>Story Campaign: Prologue + 12 Acts</h4><p>Advance the campaign narrative and watch the galaxy react to your decisions.</p></div></div>';
        echo '<p><strong>Prologue:</strong> ' . (((int)$story->prologue_unlocked === 1) ? 'Unlocked' : 'Locked') . ' | <strong>Current Act:</strong> ' . fnum((int)$story->current_act) . ' | <strong>Current Chapter:</strong> ' . fnum((int)$story->current_chapter) . '</p>';
        echo '<p><strong>Completed Acts:</strong> ' . fnum((int)$story->completed_acts) . ' / 12 | <strong>Chapter Points:</strong> ' . fnum((int)$story->chapter_points) . ' | <strong>Event Points:</strong> ' . fnum((int)$evt->event_points) . '</p>';
        echo '<p><strong>Last Story Update:</strong> ' . h($lastStoryText) . '</p>';
        echo '<p><strong>Prologue Brief:</strong> "The gate network fractures at the edge of known space. Your command is tasked with holding the lanes, uniting rival fleets, and uncovering the signal behind the collapse."</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'story&cmd=uni_story_unlock_prologue\'); return false">Unlock Prologue</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'story&cmd=uni_story_advance\'); return false">Advance Story Chapter</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'story&cmd=uni_story_log_victory\'); return false">Log Victory</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'story&cmd=uni_story_log_discovery\'); return false">Log Discovery</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'story&cmd=uni_story_log_loss\'); return false">Log Setback</a></p>';
        echo '</div>';

        echo '<div class="card full"><h4>Act and Chapter Matrix</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Act</th><th align="left">Title</th><th align="left">Chapter 1</th><th align="left">Chapter 2</th><th align="left">Chapter 3</th><th align="left">Status</th></tr>';
        foreach ($universeStoryActs as $actNo => $actMeta) {
            $status = 'Locked';
            if ((int)$story->completed_acts >= $actNo) {
                $status = 'Complete';
            } elseif ((int)$story->current_act === (int)$actNo) {
                $status = 'In Progress';
            } elseif ((int)$story->current_act > (int)$actNo) {
                $status = 'Complete';
            } elseif ((int)$story->current_act + 1 === (int)$actNo || (int)$story->prologue_unlocked === 1) {
                $status = 'Unlocked';
            }
            echo '<tr>';
            echo '<td>' . fnum((int)$actNo) . '</td>';
            echo '<td>' . h((string)$actMeta['title']) . '</td>';
            echo '<td>' . h((string)$actMeta['chapters'][1]) . '</td>';
            echo '<td>' . h((string)$actMeta['chapters'][2]) . '</td>';
            echo '<td>' . h((string)$actMeta['chapters'][3]) . '</td>';
            echo '<td>' . h($status) . '</td>';
            echo '</tr>';
        }
        echo '</table></div>';

        $storyLogs = [];
        $storyLogQ = $s->query("SELECT log_id,act_no,chapter_no,entry_code,entry_text,UNIX_TIMESTAMP(created_at) AS created_ts
            FROM universe_story_log WHERE uid=" . $uid . " ORDER BY log_id DESC LIMIT 14");
        if ($storyLogQ) {
            while ($sr = $storyLogQ->fetch_assoc()) {
                $storyLogs[] = $sr;
            }
        }
        echo '<div class="card full"><h4>Per-Log Story Timeline</h4>';
        if (count($storyLogs) === 0) {
            echo '<p>No story logs yet.</p>';
        } else {
            echo '<table class="mini-table" border="0" width="100%">';
            echo '<tr><th align="left">Log</th><th align="left">Act</th><th align="left">Chapter</th><th align="left">Code</th><th align="left">Entry</th><th align="left">Time</th></tr>';
            foreach ($storyLogs as $sr) {
                echo '<tr>';
                echo '<td>#' . fnum((int)$sr['log_id']) . '</td>';
                echo '<td>' . fnum((int)$sr['act_no']) . '</td>';
                echo '<td>' . fnum((int)$sr['chapter_no']) . '</td>';
                echo '<td>' . h((string)$sr['entry_code']) . '</td>';
                echo '<td>' . h((string)$sr['entry_text']) . '</td>';
                echo '<td>' . h(date('Y-m-d H:i:s', (int)$sr['created_ts'])) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</div>';
    }
}

if ($main === 'research') {
    $ogameResearchCount = 0;
    $ogameTechCount = 0;
    $ogameResearchedCount = 0;
    foreach ($ogameCatalog as $og) {
        if (($og['branch'] ?? '') === 'research') {
            $ogameResearchCount++;
        } else {
            $ogameTechCount++;
        }
        if ((int)($ogameLevels[$og['key']] ?? 0) > 0) {
            $ogameResearchedCount++;
        }
    }
    $ogameBankOnHand = (int)($bank->onHand ?? 0);
    $ogameCurrentRes = $resourceHub['current'] ?? [];

    if ($sub === 'tree') {
        $ogameBranches = ogameTreeBranches($ogameCatalog, 'research', $ogameLevels, $infraCostDiscount);
        echo '<div class="card full wows-brief">';
        echo '<div class="wows-hero-shell">';
        echo '<div class="wows-hero-copy">';
        echo '<div class="wows-kicker">Research Command Deck</div>';
        echo '<h4>OGame-Style Research Fleet</h4>';
        echo '<p>Persistent per-commander research programs across ten domains. Levels never reset, next-level costs escalate, and high-tier programs unlock behind prerequisites.</p>';
        echo '<div class="wows-pill-row">';
        echo '<span class="wows-pill">Programs ' . fnum($ogameResearchCount) . '</span>';
        echo '<span class="wows-pill">Active ' . fnum($ogameResearchedCount) . '</span>';
        echo '<span class="wows-pill">Research Flow ' . fnum($infraResearchSpeed) . 'x</span>';
        echo '<span class="wows-pill">Cost Cut ' . fnum($infraCostDiscount) . '%</span>';
        echo '</div>';
        echo '<div class="wows-pill-row">' . formalResearchTreeActionButtons($sub) . '</div>';
        echo '</div>';
        echo '<div class="wows-hero-stats">';
        echo '<div class="wows-stat-card"><span>Research Flow</span><strong>' . fnum($infraResearchSpeed) . 'x</strong></div>';
        echo '<div class="wows-stat-card"><span>Cost Reduction</span><strong>' . fnum($infraCostDiscount) . '%</strong></div>';
        echo '<div class="wows-stat-card"><span>Programs Active</span><strong>' . fnum($ogameResearchedCount) . '</strong></div>';
        echo '<div class="wows-stat-card"><span>Max Level</span><strong>25</strong></div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="card full wows-tree-shell">';
        echo '<div class="wows-tree-toolbar"><div class="wows-tree-title">Research Progression Matrix</div><div class="wows-legend"><span class="wows-legend-item"><span class="wows-legend-swatch unlocked"></span>Unlocked</span><span class="wows-legend-item"><span class="wows-legend-swatch available"></span>Available</span><span class="wows-legend-item"><span class="wows-legend-swatch locked"></span>Locked</span></div></div>';
        renderOgameTreeBoard($ogameBranches, $ogameCurrentRes, $ogameBankOnHand, 'tree');
        echo '</div>';

        echo '<div class="card full wows-info-card"><h4>Research Reserves</h4>';
        echo '<p>' . ogameReserveLine($ogameCurrentRes, $ogameBankOnHand) . '</p>';
        echo '<p>Costs scale with each program level. Infrastructure buildings raise research speed and lower next-level costs.</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'techlib\',\'get\',\'mainDisplay\'); return false">Open Tech Library Buildings</a> · <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'research\',\'talents\'); return false">Open Talent Library</a></p>';
        echo '</div>';
    }

    if ($sub === 'techlib') {
        $ogameBranches = ogameTreeBranches($ogameCatalog, 'technology', $ogameLevels, $infraCostDiscount);
        echo '<div class="card full wows-brief">';
        echo '<div class="wows-hero-shell">';
        echo '<div class="wows-hero-copy">';
        echo '<div class="wows-kicker">Technology Command Deck</div>';
        echo '<h4>OGame-Style Technology Fleet</h4>';
        echo '<p>Persistent technology programs that mirror naval doctrine. Levels persist per commander, next-level costs escalate, and branch readiness gates the deep tier.</p>';
        echo '<div class="wows-pill-row">';
        echo '<span class="wows-pill">Programs ' . fnum($ogameTechCount) . '</span>';
        echo '<span class="wows-pill">Active ' . fnum($ogameResearchedCount) . '</span>';
        echo '<span class="wows-pill">Research Flow ' . fnum($infraResearchSpeed) . 'x</span>';
        echo '<span class="wows-pill">Cost Cut ' . fnum($infraCostDiscount) . '%</span>';
        echo '</div>';
        echo '<div class="wows-pill-row">' . formalResearchTreeActionButtons($sub) . '</div>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'technology\',\'get\',\'mainDisplay\'); return false">Open Legacy Technology Module</a></p>';
        echo '</div>';
        echo '<div class="wows-hero-stats">';
        echo '<div class="wows-stat-card"><span>Research Flow</span><strong>' . fnum($infraResearchSpeed) . 'x</strong></div>';
        echo '<div class="wows-stat-card"><span>Cost Reduction</span><strong>' . fnum($infraCostDiscount) . '%</strong></div>';
        echo '<div class="wows-stat-card"><span>Programs Active</span><strong>' . fnum($ogameResearchedCount) . '</strong></div>';
        echo '<div class="wows-stat-card"><span>Max Level</span><strong>25</strong></div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="card full wows-tree-shell">';
        echo '<div class="wows-tree-toolbar"><div class="wows-tree-title">Technology Branch Matrix</div><div class="wows-legend"><span class="wows-legend-item"><span class="wows-legend-swatch unlocked"></span>Unlocked</span><span class="wows-legend-item"><span class="wows-legend-swatch available"></span>Available</span><span class="wows-legend-item"><span class="wows-legend-swatch locked"></span>Locked</span></div></div>';
        renderOgameTreeBoard($ogameBranches, $ogameCurrentRes, $ogameBankOnHand, 'techlib');
        echo '</div>';

        echo '<div class="card full wows-info-card"><h4>Technology Reserves</h4>';
        echo '<p>' . ogameReserveLine($ogameCurrentRes, $ogameBankOnHand) . '</p>';
        echo '<p><strong>Tech Library Cost Reduction:</strong> ' . fnum($infraCostDiscount) . '% | <strong>Research Speed:</strong> ' . fnum($infraResearchSpeed) . 'x</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'techlib\',\'get\',\'mainDisplay\'); return false">Manage Tech Library Buildings</a></p>';
        echo '</div>';
    }

    if ($sub === 'infrastructure') {
        echo '<div class="card full wows-brief">';
        echo '<div class="wows-hero-shell">';
        echo '<div class="wows-hero-copy">';
        echo '<div class="wows-kicker">Infrastructure Network</div>';
        echo '<h4>Tech Library Buildings</h4>';
        echo '<p>Infrastructure buildings increase research throughput and reduce the cost of empire-era technology advancement.</p>';
        echo '</div>';
        echo '<div class="wows-hero-stats">';
        echo '<div class="wows-stat-card"><span>Research Speed</span><strong>' . fnum($infraResearchSpeed) . 'x</strong></div>';
        echo '<div class="wows-stat-card"><span>Cost Discount</span><strong>' . fnum($infraCostDiscount) . '%</strong></div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="card full wows-info-card"><h4>Facility Status</h4>';
        echo '<p><strong>Research Campus:</strong> ' . fnum($infraLevels['research_campus']) . '</p>';
        echo '<p><strong>Data Vault:</strong> ' . fnum($infraLevels['data_vault']) . '</p>';
        echo '<p><strong>Simulation Core:</strong> ' . fnum($infraLevels['simulation_core']) . '</p>';
        echo '<p><strong>Quantum Archive:</strong> ' . fnum($infraLevels['quantum_archive']) . '</p>';
        echo '<p><strong>AI Research Directorate:</strong> ' . fnum($infraLevels['ai_directorate']) . '</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'techlib\',\'get\',\'mainDisplay\'); return false">Open Tech Library Building Console</a></p>';
        echo '</div>';
    }

    if ($sub === 'classes') {
        echo '<div class="card full wows-brief">';
        echo '<div class="wows-hero-shell">';
        echo '<div class="wows-hero-copy">';
        echo '<div class="wows-kicker">Class Doctrine</div>';
        echo '<h4>Class and Sub-Class Library</h4>';
        echo '<p>90 class entries are generated with matching subclasses, types, and sub-types for deep build planning.</p>';
        echo '</div>';
        echo '<div class="wows-hero-stats">';
        echo '<div class="wows-stat-card"><span>Catalog Entries</span><strong>90</strong></div>';
        echo '<div class="wows-stat-card"><span>Forge Link</span><strong>Active</strong></div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="card full wows-info-card"><h4>Class and Sub-Class Library (90)</h4>';
        echo '<table class="mini-table" border="0" width="100%"><tr><th align="left">ID</th><th align="left">Class</th><th align="left">Sub Class</th><th align="left">Type</th><th align="left">Sub Type</th></tr>';
        foreach ($researchHub['classes'] as $entry) {
            echo '<tr><td>' . fnum($entry['id']) . '</td><td>' . h($entry['className']) . '</td><td>' . h($entry['subClass']) . '</td><td>' . h($entry['type']) . '</td><td>' . h($entry['subType']) . '</td></tr>';
        }
        echo '</table></div>';
    }

    if ($sub === 'talents') {
        echo '<div class="card full wows-brief">';
        echo '<div class="wows-hero-shell">';
        echo '<div class="wows-hero-copy">';
        echo '<div class="wows-kicker">Talent Matrix</div>';
        echo '<h4>Talent Library</h4>';
        echo '<p>The complete research and technology program library. Every talent is persistent per commander, with escalating costs and prerequisite gating.</p>';
        echo '<div class="wows-pill-row">';
        echo '<span class="wows-pill">Research ' . fnum($ogameResearchCount) . '</span>';
        echo '<span class="wows-pill">Technology ' . fnum($ogameTechCount) . '</span>';
        echo '<span class="wows-pill">Active ' . fnum($ogameResearchedCount) . '</span>';
        echo '</div>';
        echo '</div>';
        echo '<div class="wows-hero-stats">';
        echo '<div class="wows-stat-card"><span>Total Programs</span><strong>' . fnum($ogameResearchCount + $ogameTechCount) . '</strong></div>';
        echo '<div class="wows-stat-card"><span>Branch Split</span><strong>' . fnum($ogameResearchCount) . '/' . fnum($ogameTechCount) . '</strong></div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="card full wows-info-card"><h4>Research Reserves</h4>';
        echo '<p>' . ogameReserveLine($ogameCurrentRes, $ogameBankOnHand) . '</p>';
        echo '</div>';

        echo '<div class="card full wows-info-card"><h4>Talent Library (' . fnum($ogameResearchCount + $ogameTechCount) . ')</h4>';
        echo '<table class="mini-table" border="0" width="100%"><tr><th align="left">Branch</th><th align="left">Domain</th><th align="left">Program</th><th align="left">Focus</th><th align="left">Tier</th><th align="left">Level</th><th align="left">Effect</th><th align="left">Next Cost</th><th align="left">Prerequisite</th><th align="left">Action</th></tr>';
        foreach ($ogameCatalog as $og) {
            $ogKey = $og['key'];
            $ogLvl = (int)($ogameLevels[$ogKey] ?? 0);
            $ogCosts = ogameTechNextCosts($og, $ogLvl, $infraCostDiscount);
            $ogReady = ogameTechPrereqMet($ogameLevels, $og);
            echo '<tr>';
            echo '<td>' . h(ucfirst((string)$og['branch'])) . '</td>';
            echo '<td>' . h((string)$og['domain']) . '</td>';
            echo '<td>' . h((string)$og['name']) . '</td>';
            echo '<td>' . h((string)$og['focus']) . '</td>';
            echo '<td>T' . h($og['tier']) . '</td>';
            echo '<td>' . fnum($ogLvl) . ' / ' . fnum((int)$og['max_level']) . '</td>';
            echo '<td>' . h((string)$og['effect']) . '</td>';
            echo '<td>' . fnum($ogCosts['nq']) . ' Nq / ' . fnum($ogCosts['metal']) . ' M / ' . fnum($ogCosts['crystal']) . ' C / ' . fnum($ogCosts['deut']) . ' D / ' . fnum($ogCosts['energy']) . ' E / ' . fnum($ogCosts['turns']) . ' t</td>';
            echo '<td>' . ($ogReady ? '<span style="color:#67cde9;">Met</span>' : h(ogameTechPrereqText($ogameLevels, $og))) . '</td>';
            echo '<td>';
            if ($ogLvl >= (int)$og['max_level']) {
                echo '<button class="public-btn secondary" disabled>Maxed</button>';
            } elseif (!$ogReady) {
                echo '<button class="public-btn secondary" disabled>Locked</button>';
            } else {
                echo '<button class="public-btn" onclick="sendData(\'pages\',\'get\',\'research\',\'talents&cmd=ogame_research&key=' . h($ogKey) . '\'); return false">Research L' . fnum($ogLvl + 1) . '</button>';
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</table></div>';
    }

    if ($sub === 'stargate') {
        echo '<div class="card full wows-brief">';
        echo '<div class="wows-hero-shell">';
        echo '<div class="wows-hero-copy">';
        echo '<div class="wows-kicker">Empire Doctrine</div>';
        echo '<h4>Empire Technology Program</h4>';
        echo '<p>Research complete empire-era technologies including gate science, power matrices, fleet integration, and threat response.</p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'stargatetech\',\'get\',\'mainDisplay\'); return false">Open Empire Technology Command</a></p>';
        echo '</div>';
        echo '<div class="wows-hero-stats">';
        echo '<div class="wows-stat-card"><span>Priority</span><strong>High</strong></div>';
        echo '<div class="wows-stat-card"><span>Threat Response</span><strong>Active</strong></div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="card wows-info-card"><h4>Cross-System Links</h4>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'technology\',\'get\',\'mainDisplay\'); return false">Legacy Technology Module</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'hyperspace\',\'get\',\'mainDisplay\'); return false">Hyperspace Transit Command</a></p>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'stations\',\'get\',\'mainDisplay\'); return false">Stations and Bases Command</a></p>';
        echo '</div>';

        echo '<div class="card full wows-info-card"><h4>Empire Doctrine Priorities</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Phase</th><th align="left">Primary Focus</th><th align="left">Expected Outcome</th></tr>';
        echo '<tr><td>Early</td><td>Naquadah Physics, Gate Dialing Protocols, Capacitor Lattices</td><td>Reliable gate operation and base power continuity</td></tr>';
        echo '<tr><td>Mid</td><td>Fleet Integration and Defense Tech domains</td><td>Safer deep-route deployments and stronger anti-raid posture</td></tr>';
        echo '<tr><td>Late</td><td>Ancient Systems and high-tier threat-response lines</td><td>Maximum interstellar control and campaign endurance</td></tr>';
        echo '</table></div>';
    }

    if ($sub === 'projects') {
        echo '<div class="card full wows-brief">';
        echo '<div class="wows-hero-shell">';
        echo '<div class="wows-hero-copy">';
        echo '<div class="wows-kicker">Project Queue</div>';
        echo '<h4>Research Projects</h4>';
        echo '<p>Track active projects by branch and priority band.</p>';
        echo '</div>';
        echo '<div class="wows-hero-stats">';
        echo '<div class="wows-stat-card"><span>Live Projects</span><strong>3</strong></div>';
        echo '<div class="wows-stat-card"><span>Priority</span><strong>High</strong></div>';
        echo '</div>';
        echo '</div>';
        echo '<ul><li>Military Optimization Project</li><li>Economic Throughput Project</li><li>Gate Stability Project</li></ul></div>';
    }

    if ($sub === 'labs') {
        echo '<div class="card full wows-brief">';
        echo '<div class="wows-hero-shell">';
        echo '<div class="wows-hero-copy">';
        echo '<div class="wows-kicker">Lab Network</div>';
        echo '<h4>Lab Network</h4>';
        echo '<p>Coordinate infrastructure levels across research campuses and archives.</p>';
        echo '</div>';
        echo '<div class="wows-hero-stats">';
        echo '<div class="wows-stat-card"><span>Network</span><strong>Active</strong></div>';
        echo '<div class="wows-stat-card"><span>Facilities</span><strong>Online</strong></div>';
        echo '</div>';
        echo '</div>';
        echo '<p><a href="javascript:void(0)" onclick="sendData(\'techlib\',\'get\',\'mainDisplay\'); return false">Open Tech Library Buildings</a></p></div>';
    }

    if ($sub === 'blueprints') {
        echo '<div class="card full wows-brief">';
        echo '<div class="wows-hero-shell">';
        echo '<div class="wows-hero-copy">';
        echo '<div class="wows-kicker">Blueprint Archive</div>';
        echo '<h4>Blueprint Systems</h4>';
        echo '<p>Acquire copies, research levels, and manufacture exclusive hull classes from the blueprint hangar.</p>';
        echo '</div>';
        echo '<div class="wows-hero-stats">';
        echo '<div class="wows-stat-card"><span>Catalog</span><strong>' . fnum(count($blueprintCatalog)) . '</strong></div>';
        echo '<div class="wows-stat-card"><span>Owned</span><strong>' . fnum(count($blueprintOwned)) . '</strong></div>';
        echo '<div class="wows-stat-card"><span>Hangar</span><strong>' . fnum(count($blueprintHangar)) . '</strong></div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo '<div class="card full wows-info-card"><h4>Blueprint Catalog</h4>';
        if (count($blueprintCatalog) === 0) {
            echo '<p>No blueprints in the catalog yet.</p>';
        } else {
            echo '<table class="mini-table" border="0" width="100%">';
            echo '<tr><th align="left">Hull Class</th><th align="left">Tier</th><th align="left">Copy Cost</th><th align="left">Copies</th><th align="left">ME</th><th align="left">TE</th><th align="left">Runs</th><th align="left">Actions</th></tr>';
            foreach ($blueprintCatalog as $id => $bp) {
                $own = isset($blueprintOwned[$id]) ? $blueprintOwned[$id] : ['owned_copies' => 0, 'me_level' => 0, 'te_level' => 0, 'run_count' => 0];
                $hangar = isset($blueprintHangar[$id]) ? $blueprintHangar[$id]['quantity'] : 0;
                echo '<tr>';
                echo '<td>' . h((string)$bp['name']) . '<br><small>' . h((string)$bp['hull_class']) . '</small></td>';
                echo '<td>T' . fnum((int)$bp['tier']) . '</td>';
                echo '<td>' . fnum((int)$bp['copy_cost']) . ' NQ</td>';
                echo '<td>' . fnum((int)$own['owned_copies']) . '</td>';
                echo '<td>' . fnum((int)$own['me_level']) . '</td>';
                echo '<td>' . fnum((int)$own['te_level']) . '</td>';
                echo '<td>' . fnum((int)$own['run_count']) . ' (' . fnum($hangar) . ' in hangar)</td>';
                echo '<td>';
                if ((int)$own['owned_copies'] === 0) {
                    echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'research\',\'blueprints&cmd=bp_acquire&bp=' . (int)$id . '\'); return false">Acquire</a>';
                } else {
                    echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'research\',\'blueprints&cmd=bp_research&bp=' . (int)$id . '&mode=me\'); return false">ME+1</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'research\',\'blueprints&cmd=bp_research&bp=' . (int)$id . '&mode=te\'); return false">TE+1</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'research\',\'blueprints&cmd=bp_build&bp=' . (int)$id . '&qty=1\'); return false">Build</a>';
                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</div>';

        echo '<div class="card full wows-info-card"><h4>Field Building Blueprints</h4>';
        if (count($blueprintBuildingCatalog) === 0) {
            echo '<p>No field building blueprints found.</p>';
        } else {
            echo '<table class="mini-table" border="0" width="100%">';
            echo '<tr><th align="left">Blueprint</th><th align="left">Tier</th><th align="left">Used By</th><th align="left">Copy Cost</th><th align="left">Copies</th><th align="left">ME</th><th align="left">TE</th><th align="left">Actions</th></tr>';
            foreach ($blueprintBuildingCatalog as $id => $bp) {
                $own = isset($blueprintOwned[$id]) ? $blueprintOwned[$id] : ['owned_copies' => 0, 'me_level' => 0, 'te_level' => 0, 'run_count' => 0];
                echo '<tr>';
                echo '<td>' . h((string)$bp['name']) . '<br><small>' . h((string)$bp['hull_class']) . '</small></td>';
                echo '<td>T' . fnum((int)$bp['tier']) . '</td>';
                echo '<td>' . h((string)$bp['target_key']) . '<br><small>field building</small></td>';
                echo '<td>' . fnum((int)$bp['copy_cost']) . ' NQ</td>';
                echo '<td>' . fnum((int)$own['owned_copies']) . '</td>';
                echo '<td>' . fnum((int)$own['me_level']) . '</td>';
                echo '<td>' . fnum((int)$own['te_level']) . '</td>';
                echo '<td>';
                if ((int)$own['owned_copies'] === 0) {
                    echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'research\',\'blueprints&cmd=bp_acquire&bp=' . (int)$id . '\'); return false">Acquire</a>';
                } else {
                    echo '<a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'research\',\'blueprints&cmd=bp_research&bp=' . (int)$id . '&mode=me\'); return false">ME+1</a> | <a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'research\',\'blueprints&cmd=bp_research&bp=' . (int)$id . '&mode=te\'); return false">TE+1</a> <br><small>Unlocks construction in the Colony Grid.</small>';
                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</div>';

        echo '<div class="card full wows-info-card"><h4>Manufacturing Notes</h4>';
        echo '<ul><li>Copies are consumed on research; each copy unlocks one level of ME or TE.</li><li>ME (materials) reduces build cost, TE (time) reduces build turns.</li><li>Hangar items add their power to fleet readiness when deployed.</li><li>Blueprints drop from world boss loot and deep expeditions.</li><li>Field building blueprints are used by the Colony Grid. Owning a copy unlocks construction; ME research discounts its material cost.</li></ul>';
        echo '</div>';
    }
}

if ($main === 'community') {
    if ($sub === 'forums') {
        echo '<div class="card"><h4>Forums</h4><p>Join strategy discussions, diplomacy talks, and event threads.</p><p><a href="forums/" target="_blank">Open Forums</a></p></div>';
        echo '<div class="card full"><h4>Forum Etiquette</h4><ul><li>Post campaign intel in private alliance boards first</li><li>Cite patch notes when proposing meta shifts</li><li>Keep diplomatic negotiations out of public threads</li></ul></div>';
        echo '<div class="card"><h4>Knowledge Exchange</h4><p>Public strategy sharing strengthens your alliance. Avoid exposing live operational plans.</p></div>';
    }
    if ($sub === 'updates') {
        echo '<div class="card"><h4>Update Feed</h4><p>Read update announcements and balancing notes.</p><p><a href="javascript:void(0)" onclick="sendData(\'faq\',\'get\',\'mainDisplay\'); return false">Open News/FAQ</a></p></div>';
        echo '<div class="card full"><h4>Meta-Change Tracking</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Announcement Type</th><th align="left">Impact</th><th align="left">Action</th></tr>';
        echo '<tr><td>Balance patch</td><td>Stat or cost shifts</td><td>Review build priorities</td></tr>';
        echo '<tr><td>Event window</td><td>Limited-time rewards</td><td>Plan turn budget around it</td></tr>';
        echo '<tr><td>Season reset</td><td>Progression rollover</td><td>Claim pass rewards first</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Adaptation Edge</h4><p>Early adopters of balance changes gain a competitive window before the meta settles.</p></div>';
    }
    if ($sub === 'contact') {
        echo '<div class="card"><h4>Contact Command</h4><p>Reach moderators and administrators through in-game messaging channels.</p><p><a href="javascript:void(0)" onclick="sendData(\'messages\',\'get\',\'mainDisplay\'); return false">Open Messaging</a></p></div>';
        echo '<div class="card full"><h4>Effective Reports</h4><ul><li>Include the affected module and sub-page</li><li>Add mission timestamps and player names</li><li>Describe expected vs observed behavior</li><li>Attach screenshots when available</li></ul></div>';
        echo '<div class="card"><h4>Escalation Path</h4><p>Start with moderation, then administration. Do not ping staff for competitive disputes - use the conflict review channel.</p></div>';
    }
    if ($sub === 'faq') {
        echo '<div class="card"><h4>FAQ</h4><p>Core rules, policy, and progression advice are available here.</p><p><a href="javascript:void(0)" onclick="sendData(\'faq\',\'get\',\'mainDisplay\'); return false">Open FAQ</a></p></div>';
        echo '<div class="card full"><h4>Policy Highlights</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Topic</th><th align="left">Policy</th></tr>';
        echo '<tr><td>Multi-accounting</td><td>One commander per player</td></tr>';
        echo '<tr><td>Support transfers</td><td>1% broker fee applies</td></tr>';
        echo '<tr><td>Raid etiquette</td><td>No raiding under active NAP</td></tr>';
        echo '<tr><td>Exploits</td><td>Report suspected exploits - do not use</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Rule Safety</h4><p>Reading policy before action prevents avoidable penalties. When in doubt, ask a moderator first.</p></div>';
    }
    if ($sub === 'events') {
        echo '<div class="card"><h4>Events Calendar</h4><p>Track alliance events, challenge windows, and campaign checkpoints.</p></div>';
        echo '<div class="card full"><h4>Event Types</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Type</th><th align="left">Cadence</th><th align="left">Reward Track</th></tr>';
        echo '<tr><td>Tournament</td><td>Seasonal</td><td>Placement tiers</td></tr>';
        echo '<tr><td>Campaign</td><td>Rotating</td><td>Campaign points</td></tr>';
        echo '<tr><td>Giveaway</td><td>Periodic</td><td>One-time claims</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Deadline Discipline</h4><p>Unclaimed event prizes expire at reset. Reserve turn budget before tournament windows open.</p></div>';
    }
    if ($sub === 'academy') {
        echo '<div class="card full"><h4>Academy</h4><p>Structured training path for new and returning commanders.</p><ol><li>Basics: economy and turns</li><li>Intermediate: raids and intel</li><li>Advanced: multi-front doctrine</li></ol></div>';
        echo '<div class="card full"><h4>Curriculum Tracks</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Track</th><th align="left">Covers</th><th align="left">Recommended For</th></tr>';
        echo '<tr><td>Foundations</td><td>Resources, turns, training</td><td>New commanders</td></tr>';
        echo '<tr><td>Campaign School</td><td>Targeting, wave sizing, debrief</td><td>Returning officers</td></tr>';
        echo '<tr><td>Grand Strategy</td><td>Doctrine, treaties, councils</td><td>Alliance leaders</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Study Note</h4><p>Academy guides assume unharassed openings. Adapt build orders when under raid pressure.</p></div>';
    }
}

if ($main === 'help') {
    if ($sub === 'newplayer') {
        echo '<div class="card full"><h4>New Player Launch Plan</h4><ol><li>Train a balanced starter army from untrained units.</li><li>Keep a reserve of Naquadah for emergency retraining.</li><li>Upgrade production before expensive wars.</li><li>Scout targets before every major operation.</li></ol></div>';
        echo '<div class="card full"><h4>Opening Rhythm</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Step</th><th align="left">Action</th><th align="left">Why</th></tr>';
        echo '<tr><td>1</td><td>Build resource structures</td><td>Compound income baseline</td></tr>';
        echo '<tr><td>2</td><td>Train balanced starter army</td><td>Deter early opportunistic raids</td></tr>';
        echo '<tr><td>3</td><td>Reserve Naquadah</td><td>Emergency retraining buffer</td></tr>';
        echo '<tr><td>4</td><td>Upgrade production</td><td>Faster growth before conflicts</td></tr>';
        echo '<tr><td>5</td><td>Scout before striking</td><td>Confirm defenses before commit</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>First Cycle Goals</h4><p>Reach self-sustaining mining, a balanced 45/25/15/15 force split, and a full reserve floor before opening your first front.</p></div>';
    }
    if ($sub === 'mechanics') {
        echo '<div class="card"><h4>Core Mechanics</h4><ul><li>Action turns gate all offensive actions.</li><li>Military score influences rank and combat outcomes.</li><li>1% broker fee applies to support transfers.</li><li>Technology upgrades scale growth and resilience.</li></ul></div>';
        echo '<div class="card full"><h4>System Relationships</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">System</th><th align="left">Feeds</th><th align="left">Gated By</th></tr>';
        echo '<tr><td>Resources</td><td>Buildings, fleets, research</td><td>Production structures</td></tr>';
        echo '<tr><td>Action Turns</td><td>Offensive and covert ops</td><td>30-minute cadence</td></tr>';
        echo '<tr><td>Training</td><td>Combat force composition</td><td>Untrained reserve</td></tr>';
        echo '<tr><td>Technology</td><td>All empire systems</td><td>Resource + energy</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Planning Discipline</h4><p>Offensive actions are turn-gated - plan waves across multiple cycles and bank turns before big campaigns.</p></div>';
    }
    if ($sub === 'glossary') {
        echo '<div class="card full"><h4>Glossary</h4><p><strong>Naquadah:</strong> Main currency.</p><p><strong>UP:</strong> Unit production per turn.</p><p><strong>Commander:</strong> Parent node in command chain.</p><p><strong>Action Turn:</strong> Strategic action resource.</p></div>';
        echo '<div class="card full"><h4>Extended Terms</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Term</th><th align="left">Definition</th></tr>';
        echo '<tr><td>Habitability</td><td>Colony viability score - gates build slots</td></tr>';
        echo '<tr><td>Covert / Anti-Covert</td><td>Spy power vs counter-intel power</td></tr>';
        echo '<tr><td>Moon Class</td><td>Lunar body type with strategic bonuses</td></tr>';
        echo '<tr><td>Doctrine</td><td>Aggregate command posture for a cycle</td></tr>';
        echo '<tr><td>Reserve Floor</td><td>Minimum resource % held before spending</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Shared Vocabulary</h4><p>Common terms speed up alliance coordination. Clarify anything ambiguous before ops.</p></div>';
    }
    if ($sub === 'support') {
        echo '<div class="card"><h4>Support Desk</h4><p>For account issues, use in-game contact and community channels. Include mission timestamps and affected players in reports.</p></div>';
        echo '<div class="card full"><h4>Evidence Checklist</h4><ul><li>Commander name and server</li><li>Affected module and sub-page</li><li>Mission timestamps and opponent</li><li>Expected vs observed behavior</li><li>Relevant screenshots</li></ul></div>';
        echo '<div class="card"><h4>Resolution Tempo</h4><p>Complete reports with timestamps resolve faster. Incomplete reports bounce back for clarification.</p></div>';
    }
    if ($sub === 'troubleshooting') {
        echo '<div class="card full"><h4>Troubleshooting</h4><ul><li>If module output stalls, reload and retry the menu action.</li><li>If actions fail, verify enough turns and resources.</li><li>If target actions fail, refresh rank/profile intelligence first.</li></ul></div>';
        echo '<div class="card full"><h4>Symptom Fixes</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Symptom</th><th align="left">Fix</th><th align="left">Escalate If</th></tr>';
        echo '<tr><td>Page renders blank</td><td>Hard refresh + retry action</td><td>Persists after refresh</td></tr>';
        echo '<tr><td>Action rejected</td><td>Check turn/resource balance</td><td>Balances sufficient</td></tr>';
        echo '<tr><td>Target not found</td><td>Refresh rank/profile intel</td><td>Target still missing</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Retry Rule</h4><p>One retry, then report. Repeating the same failing action without evidence wastes both time and turns.</p></div>';
    }
    if ($sub === 'hotkeys') {
        echo '<div class="card"><h4>Quick Commands</h4><p>Use the left command tree and feature action buttons for rapid sub-page switching.</p></div>';
        echo '<div class="card full"><h4>Navigation Patterns</h4>';
        echo '<table class="mini-table" border="0" width="100%">';
        echo '<tr><th align="left">Pattern</th><th align="left">Use</th></tr>';
        echo '<tr><td>Suite tree</td><td>Jump between the 10 main suites</td></tr>';
        echo '<tr><td>Sub-nav buttons</td><td>Switch sub-pages within a suite</td></tr>';
        echo '<tr><td>Feature workbenches</td><td>Run suite-specific quick actions</td></tr>';
        echo '<tr><td>Command dropdowns</td><td>Execute preset commands per console</td></tr>';
        echo '</table></div>';
        echo '<div class="card"><h4>Muscle Memory</h4><p>Repeated workflows (recon &gt; strike &gt; debrief) are fastest when every step stays on one screen.</p></div>';
    }
}

if (isset($systemDetails[$main][$sub])) {
    renderInfoBlock($systemDetails[$main][$sub]);
}

renderMechanicsMatrix($main, $sub);
renderInteractiveCalculators($main, $sub, $baseData, $personnel, $bank);
renderFeatureWorkbenches($main, $sub, $baseData, $personnel, $bank, $userStats, $planets);

echo '</div>';
echo '</div>';

// Planet / moon detail modal (shared across galaxy and planets tabs)
?>
<div id="sgw-detail-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.75);z-index:9999;overflow:auto;">
  <div style="background:#1a1a2e;color:#ccc;border:1px solid #555;border-radius:6px;max-width:520px;margin:60px auto;padding:24px;position:relative;">
    <button onclick="document.getElementById('sgw-detail-modal').style.display='none'" style="position:absolute;top:10px;right:14px;background:none;border:none;color:#aaa;font-size:18px;cursor:pointer;">&#x2715;</button>
    <div id="sgw-detail-body"></div>
  </div>
</div>
<script type="text/javascript">
function showPlanetDetail(d){
    var hab = d.hab || 0;
    var habCol = hab >= 70 ? '#6f6' : (hab >= 45 ? '#ff9' : '#f77');
    var moonStr = d.moons > 0
        ? '<span style="cursor:pointer;color:#8cf;text-decoration:underline" onclick="showMoonDetail({parent:\''+esc(d.name)+'\',coord:\''+esc(d.coord)+'\',count:'+d.moons+',\'class\':\''+esc(d.moonClass)+'\',moonBiome:\''+esc(d.moonBiome || '')+'\',moonSubBiome:\''+esc(d.moonSubBiome || '')+'\'})">'+d.moons+' &times; '+esc(d.moonClass)+'</span>'
        : '<em>None</em>';
    var npcStr = '';
    if(d.npcRace && d.npcAlignment && d.owner !== 'Unclaimed'){
        var alignCol = (d.npcAlignment === 'friendly') ? '#6f6' : ((d.npcAlignment === 'neutral') ? '#ff9' : '#f77');
        npcStr = '<div style="margin-top:10px;padding:10px;background:#14142a;border:1px solid #333;border-radius:6px">'+
            '<strong style="color:'+alignCol+'">&#x1F47E; '+esc(d.npcName)+' Territory</strong> '+
            '<em style="color:'+alignCol+'">['+esc(d.npcAlignment)+']</em>'+
            '<p style="margin:6px 0 0;color:#bbb">'+esc(d.npcDescription || '')+'</p>'+
            '<p style="margin:4px 0 0;color:#aaa"><small>Focus: '+esc(d.npcFocus || '')+' &middot; Estimated Power: '+num(d.npcPower||0)+'</small></p>'+
            '</div>';
    }
    var colonizeBtn = (d.owner === 'Unclaimed' && hab >= 48)
        ? '<p><a href="javascript:void(0)" onclick="sendData(\'pages\',\'get\',\'universe\',\'expedition\'); closeSgwModal();" style="color:#6cf">&#x1F680; Plan Colonization Mission</a></p>'
        : '';
    document.getElementById('sgw-detail-body').innerHTML =
        '<h3 style="margin-top:0;color:#adf">&#127760; '+esc(d.name)+'</h3>'+
        '<table style="width:100%;border-collapse:collapse;font-size:.9em">'+
        row('Coordinate', esc(d.coord))+
        row('World Type', esc(d.type))+
        row('Biome', esc(d.biome))+
        row('Sub-Biome', esc(d.subBiome || 'Frontier Zone'))+
        row('Habitability', '<span style="color:'+habCol+'">'+hab+'%</span>')+
        row('Build Slots', d.slots)+
        row('Metal Deposit', num(d.metal))+
        row('Crystal Deposit', num(d.crystal))+
        row('Deuterium Deposit', num(d.deut))+
        row('Moons', moonStr)+
        row('Status', esc(d.owner))+
        '</table>'+npcStr+colonizeBtn;
    document.getElementById('sgw-detail-modal').style.display = 'block';
}
function showMoonDetail(d){
    var moonClasses = {
        Rocky:   {desc:'Dense basalt crust — ideal for sensor arrays and early bunker construction.',  bonus:'Defense +3%, Scanner range +1'},
        Icy:     {desc:'Frozen volatiles — rich in deuterium ice extraction potential.',               bonus:'Deuterium rate +8%'},
        Metallic:{desc:'High-grade ore concentration — excellent mining substrate.',                   bonus:'Metal rate +6%, Crystal rate +4%'},
        Ruined:  {desc:'Ancient wreckage of unknown origin — yields artefact anomalies on excavation.',bonus:'Expedition anomaly chance +12%'},
    };
    var cls = d['class'] || d.class || '?';
    var info = moonClasses[cls] || {desc:'Unknown lunar body.', bonus:'No data'};
    var moons = [];
    for(var i=1;i<=d.count;i++){
        moons.push('<li>Moon '+i+' &mdash; <strong>'+esc(cls)+'</strong></li>');
    }
    document.getElementById('sgw-detail-body').innerHTML =
        '<h3 style="margin-top:0;color:#8cf">&#127761; '+esc(d.parent)+' &mdash; Moon System</h3>'+
        '<p><strong>Parent Coordinate:</strong> '+esc(d.coord)+'</p>'+
        '<ul style="padding-left:18px">'+moons.join('')+'</ul>'+
        '<table style="width:100%;border-collapse:collapse;font-size:.9em">'+
        row('Moon Class', esc(cls))+
        row('Moon Biome', esc(d.moonBiome || 'Unknown Lunar Zone'))+
        row('Moon Sub-Biome', esc(d.moonSubBiome || 'Uncharted Crater'))+
        row('Classification', info.desc)+
        row('Strategic Bonus', '<span style="color:#8f8">'+info.bonus+'</span>')+
        '</table>'+
        '<p style="margin-top:12px"><a href="javascript:void(0)" onclick="sendData(\'stations\',\'get\',\'mainDisplay\'); closeSgwModal();" style="color:#6cf">&#x1F6F8; Open Station Command</a></p>';
    document.getElementById('sgw-detail-modal').style.display = 'block';
}
function closeSgwModal(){ document.getElementById('sgw-detail-modal').style.display='none'; }
function row(label, val){ return '<tr><td style="padding:4px 8px;border-bottom:1px solid #333;color:#888;width:45%">'+label+'</td><td style="padding:4px 8px;border-bottom:1px solid #333">'+val+'</td></tr>'; }
function esc(s){ var d=document.createElement('div');d.appendChild(document.createTextNode(String(s)));return d.innerHTML; }
function num(n){ return Number(n).toLocaleString(); }
document.getElementById('sgw-detail-modal').addEventListener('click',function(e){ if(e.target===this){ closeSgwModal(); } });
</script>
<?php

echo "Query Count: " . $s->queryCount . "<br>";
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>