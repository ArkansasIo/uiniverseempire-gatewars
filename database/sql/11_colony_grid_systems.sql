-- Colony Grid Systems v1
-- 9-class field grid (small to large), field building catalog with blueprint
-- gating, planetary power grid, Alliance Industrial Complex (AIC), and the
-- AI factory unit producer.
USE sgw;

-- ---------------------------------------------------------------------------
-- 1. Colony profiles: overall size class (1 = small .. 9 = huge) + power grid
-- ---------------------------------------------------------------------------
ALTER TABLE universe_colony_profiles
  ADD COLUMN IF NOT EXISTS size_class INT NOT NULL DEFAULT 1 AFTER world_type,
  ADD COLUMN IF NOT EXISTS power_capacity BIGINT NOT NULL DEFAULT 0 AFTER field_used,
  ADD COLUMN IF NOT EXISTS power_consumption BIGINT NOT NULL DEFAULT 0 AFTER power_capacity,
  ADD COLUMN IF NOT EXISTS power_storage BIGINT NOT NULL DEFAULT 0 AFTER power_consumption,
  ADD COLUMN IF NOT EXISTS grid_stability INT NOT NULL DEFAULT 100 AFTER power_storage;

-- Built field buildings get a per-slot class + generation info
ALTER TABLE universe_colony_fields
  ADD COLUMN IF NOT EXISTS slot_class INT NOT NULL DEFAULT 1 AFTER slot_no,
  ADD COLUMN IF NOT EXISTS building_key VARCHAR(32) NOT NULL DEFAULT '' AFTER building_code,
  ADD COLUMN IF NOT EXISTS power_generated BIGINT NOT NULL DEFAULT 0 AFTER power_draw;

-- ---------------------------------------------------------------------------
-- 2. Field building catalog
--    size_requirement 1..9 : minimum slot class required to host the building.
--    blueprint_id : 0 = always buildable, otherwise must own >=1 copy of the
--                   blueprint in player_blueprints.
--    power_generated < 0 : produces power; > 0 : consumes power.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS field_building_catalog (
  building_key     VARCHAR(32) NOT NULL PRIMARY KEY,
  building_name    VARCHAR(90) NOT NULL,
  category         VARCHAR(32) NOT NULL,
  tier             INT NOT NULL DEFAULT 1,
  size_requirement INT NOT NULL DEFAULT 1,
  blueprint_id     INT NOT NULL DEFAULT 0,
  base_metal       BIGINT NOT NULL DEFAULT 0,
  base_crystal     BIGINT NOT NULL DEFAULT 0,
  base_deuterium   BIGINT NOT NULL DEFAULT 0,
  base_food        BIGINT NOT NULL DEFAULT 0,
  base_water       BIGINT NOT NULL DEFAULT 0,
  base_naq         BIGINT NOT NULL DEFAULT 0,
  base_turns       INT NOT NULL DEFAULT 1,
  power_generated  BIGINT NOT NULL DEFAULT 0,
  population_use   BIGINT NOT NULL DEFAULT 0,
  scale_factor     DECIMAL(5,2) NOT NULL DEFAULT 2.00,
  notes            TEXT NOT NULL
);

INSERT INTO field_building_catalog
  (building_key, building_name, category, tier, size_requirement, blueprint_id, base_metal, base_crystal, base_deuterium, base_food, base_water, base_naq, base_turns, power_generated, population_use, scale_factor, notes)
VALUES
  ('metal_mine',       'Metal Mine',                'Mines',       1, 1, 101, 120,  60,    0, 0, 0,  1000,   1,    8,    0, 1.50, 'Increases metal production.'),
  ('crystal_mine',     'Crystal Mine',              'Mines',       1, 1, 102, 100,  80,    0, 0, 0,  1000,   1,    6,    0, 1.50, 'Increases crystal production.'),
  ('deuterium_synth',  'Deuterium Synthesizer',     'Mines',       1, 2, 103, 225, 120,    0, 0, 0,  1500,   1,    4,    0, 1.50, 'Increases deuterium output.'),
  ('naquadah_mine',    'Naquadah Mine',             'Mines',       2, 4, 104,6000,4000, 2000, 0, 0, 25000,   3,   25,   60, 1.60, 'Refines the rare power source of the galaxy.'),
  ('hydroponics',      'Hydroponics Farm',          'Mines',       1, 1, 105,  60,  40,    0, 0, 0,   800,   1,    2,    0, 1.50, 'Grows food for your population.'),
  ('water_plant',      'Water Purification Plant',  'Mines',       1, 1, 106,  60,  40,    0, 0, 0,   800,   1,    2,    0, 1.50, 'Purifies water for your population.'),
  ('solar_plant',      'Solar Plant',               'Power',       1, 1, 107,  75,  30,    0, 0, 0,   900,   1,  -20,    0, 1.50, 'Generates basic energy output.'),
  ('geothermal_array', 'Geothermal Array',          'Power',       2, 2, 108, 300, 200,    0, 0, 0,  3000,   1,  -45,    0, 1.60, 'Volcanic heat harnessed into the grid.'),
  ('fusion_reactor',   'Fusion Reactor',            'Power',       3, 4, 109,1200, 800,  400, 0, 0,  8000,   2, -130,    0, 1.80, 'High-end energy generation from deuterium.'),
  ('naquadah_reactor', 'Naquadah Reactor',          'Power',       4, 6, 110,15000,12000,6000, 0, 0, 60000,   3, -420,   30, 1.70, 'A small reactor core burning refined naquadah.'),
  ('arc_reactor',      'Arc Reactor Plant',         'Power',       5, 8, 111,120000,90000,50000, 0, 0, 400000,  5,-1600,  120, 1.80, 'Advanced power factory for the largest colonies.'),
  ('power_capacitor',  'Power Capacitor Bank',      'Power',       2, 3, 112,5000,4000,1000, 0, 0, 12000,   1,    0,   10, 1.60, 'Adds energy storage buffers to the grid.'),
  ('robotics_factory', 'Robotics Factory',          'Factories',   1, 2, 113, 400, 120,  200, 0, 0,  4000,   1,    6,    0, 2.00, 'Speeds up construction by 10% per level.'),
  ('aic_factory',      'Alliance Industrial Complex','Factories',  6, 8, 114,250000,200000,100000, 0, 0, 900000,  6,  250,  500, 2.00, 'AIC. Massive construction speed boost and unlocks tier 5+ builds.'),
  ('ai_factory',       'AI Factory',                'Factories',   2, 3, 115,8000,6000,3000, 0, 0, 30000,   2,   12,   40, 1.70, 'Produces AI workers, drones, and cores.'),
  ('nanite_factory',   'Nanite Factory',            'Factories',   6, 7, 116,1000000,500000,100000, 0, 0, 2500000,  8, 400,  800, 2.00, 'Extreme build speed acceleration.'),
  ('shipyard',         'Shipyard',                  'Factories',   2, 4, 117, 600, 300,  150, 0, 0,  8000,   2,    8,    0, 2.00, 'Unlocks fleet and defense production.'),
  ('space_dock',       'Space Dock',                'Factories',   3, 5, 118,20000,20000,10000, 0, 0, 50000,   3,   60,  100, 2.00, 'Supports fleet repair cycles.'),
  ('habitat_dome',     'Habitat Dome',              'Facilities',  1, 1, 119,4000,2000, 500, 0, 0,  3000,   1,    2,    0, 2.00, 'Shelters population on hostile worlds.'),
  ('research_lab',     'Research Lab',              'Facilities',  2, 2, 120, 200, 400,  200, 0, 0,  5000,   1,    5,   20, 2.00, 'Boosts research development.'),
  ('terraformer',      'Terraformer',               'Facilities',  5, 8, 121,   0,50000,100000, 0, 0, 300000,   4,  500,  100, 2.00, 'Expands planet build space.'),
  ('alliance_depot',   'Alliance Depot',            'Facilities',  2, 3, 122,20000,40000,   0, 0, 0, 25000,   2,   20,    0, 2.00, 'Fleet support logistics for allies.'),
  ('missile_silo',     'Missile Silo',              'Facilities',  2, 2, 123,20000,20000,1000, 0, 0, 20000,   2,   10,    0, 2.00, 'Stores missile defenses.'),
  ('bastion',          'Bastion District',          'Defense',     2, 2, 124,17000, 9000, 3800, 0, 0, 22000,   2,   14,  110, 1.80, 'Armored garrison district.'),
  ('shield_gen',       'Shield Generator',          'Defense',     3, 3, 125,35000,35000, 5000, 0, 0, 40000,   2,   60,   40, 1.80, 'Projects a defensive shield envelope.'),
  ('turret_bunker',    'Turret Bunker',             'Defense',     2, 2, 126,12000, 6000, 2000, 0, 0, 18000,   1,    8,   30, 1.80, 'Houses planetary turret batteries.'),
  ('planetary_cannon', 'Planetary Cannon',          'Defense',     4, 6, 127,90000,90000,40000, 0, 0, 180000,   4,  300,  200, 1.80, 'Top-tier planetary defense emplacement.'),
  ('lunar_base',       'Lunar Base',                'Lunar',       1, 1, 128,20000,40000,20000, 0, 0, 25000,   2,   10,    0, 2.00, 'Unlocks moon infrastructure.'),
  ('sensor_phalanx',   'Sensor Phalanx',            'Lunar',       3, 3, 129,20000,40000,20000, 0, 0, 30000,   2,   25,    0, 2.00, 'Scans nearby fleet movements.'),
  ('jump_gate',        'Jump Gate',                 'Lunar',       5, 7, 130,2000000,4000000,2000000, 0, 0, 8000000, 10,  800,  500, 2.00, 'Instant moon-to-moon fleet transfer.'),
  ('stargate_ring',    'Stargate Ring',             'Facilities',  6, 9, 131,5000000,5000000,3000000, 0, 0, 12000000, 12, 1500, 2000, 2.00, 'Wormhole transit hub. Requires a class 9 field.'),
  ('orbital_ring',     'Orbital Ring',              'Facilities',  5, 9, 132,3000000,3000000,2000000, 0, 0, 6000000, 10, 1200, 1500, 2.00, 'Massive orbital infrastructure. Requires a class 9 field.')
ON DUPLICATE KEY UPDATE building_name = VALUES(building_name), size_requirement = VALUES(size_requirement),
  base_metal = VALUES(base_metal), base_crystal = VALUES(base_crystal), base_deuterium = VALUES(base_deuterium),
  base_naq = VALUES(base_naq), power_generated = VALUES(power_generated);

-- ---------------------------------------------------------------------------
-- 3. Blueprint system: building blueprints (kind 'building') join the existing
--    starship hull blueprints (kind 'ship') in blueprint_catalog. The table is
--    normally created at runtime by blueprintEnsureTables(), so ensure it exists
--    here too to keep this migration self-contained.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS blueprint_catalog (
  blueprint_id INT NOT NULL PRIMARY KEY,
  bp_name VARCHAR(96) NOT NULL,
  hull_class VARCHAR(40) NOT NULL,
  bp_kind VARCHAR(16) NOT NULL DEFAULT 'ship',
  target_key VARCHAR(32) NOT NULL DEFAULT '',
  tier INT NOT NULL DEFAULT 1,
  copy_cost INT NOT NULL DEFAULT 0,
  base_metal INT NOT NULL DEFAULT 0,
  base_crystal INT NOT NULL DEFAULT 0,
  base_deuterium INT NOT NULL DEFAULT 0,
  base_turns INT NOT NULL DEFAULT 1,
  base_power INT NOT NULL DEFAULT 0
);

ALTER TABLE blueprint_catalog
  ADD COLUMN IF NOT EXISTS bp_kind VARCHAR(16) NOT NULL DEFAULT 'ship' AFTER hull_class,
  ADD COLUMN IF NOT EXISTS target_key VARCHAR(32) NOT NULL DEFAULT '' AFTER bp_kind;

INSERT INTO blueprint_catalog
  (blueprint_id, bp_name, hull_class, bp_kind, target_key, tier, copy_cost, base_metal, base_crystal, base_deuterium, base_turns, base_power)
VALUES
  (101, 'Metal Mine Blueprint',             'Mines',       'building', 'metal_mine',       1,   300,   120,   60,    0,  1, 8),
  (102, 'Crystal Mine Blueprint',           'Mines',       'building', 'crystal_mine',     1,   300,   100,   80,    0,  1, 6),
  (103, 'Deuterium Synthesizer Blueprint',  'Mines',       'building', 'deuterium_synth',  1,   450,   225,  120,    0,  1, 4),
  (104, 'Naquadah Mine Blueprint',          'Mines',       'building', 'naquadah_mine',    2,  7500,  6000, 4000, 2000,  3, 25),
  (105, 'Hydroponics Farm Blueprint',       'Mines',       'building', 'hydroponics',      1,   240,    60,   40,    0,  1, 2),
  (106, 'Water Purification Blueprint',     'Mines',       'building', 'water_plant',      1,   240,    60,   40,    0,  1, 2),
  (107, 'Solar Plant Blueprint',            'Power',       'building', 'solar_plant',      1,   270,    75,   30,    0,  1, 20),
  (108, 'Geothermal Array Blueprint',       'Power',       'building', 'geothermal_array', 2,   900,   300,  200,    0,  1, 45),
  (109, 'Fusion Reactor Blueprint',         'Power',       'building', 'fusion_reactor',   3,  2400,  1200,  800,  400,  2, 130),
  (110, 'Naquadah Reactor Blueprint',       'Power',       'building', 'naquadah_reactor', 4, 18000, 15000,12000, 6000,  3, 420),
  (111, 'Arc Reactor Plant Blueprint',      'Power',       'building', 'arc_reactor',      5, 120000,120000,90000,50000,  5, 1600),
  (112, 'Power Capacitor Blueprint',        'Power',       'building', 'power_capacitor',  2,  3600,  5000, 4000, 1000,  1, 0),
  (113, 'Robotics Factory Blueprint',       'Factories',   'building', 'robotics_factory', 1,  1200,   400,  120,  200,  1, 6),
  (114, 'AIC Blueprint',                    'Factories',   'building', 'aic_factory',      6, 270000,250000,200000,100000, 6, 250),
  (115, 'AI Factory Blueprint',             'Factories',   'building', 'ai_factory',       2,  9000,  8000, 6000, 3000,  2, 12),
  (116, 'Nanite Factory Blueprint',         'Factories',   'building', 'nanite_factory',   6, 750000,1000000,500000,100000, 8, 400),
  (117, 'Shipyard Blueprint',               'Factories',   'building', 'shipyard',         2,  2400,   600,  300,  150,  2, 8),
  (118, 'Space Dock Blueprint',             'Factories',   'building', 'space_dock',       3, 15000, 20000,20000,10000,  3, 60),
  (119, 'Habitat Dome Blueprint',           'Facilities',  'building', 'habitat_dome',     1,   900,  4000, 2000,  500,  1, 2),
  (120, 'Research Lab Blueprint',           'Facilities',  'building', 'research_lab',     2,  1500,   200,  400,  200,  1, 5),
  (121, 'Terraformer Blueprint',            'Facilities',  'building', 'terraformer',      5,  90000,    0, 50000,100000,  4, 500),
  (122, 'Alliance Depot Blueprint',         'Facilities',  'building', 'alliance_depot',   2,  7500, 20000,40000,    0,  2, 20),
  (123, 'Missile Silo Blueprint',           'Facilities',  'building', 'missile_silo',     2,  6000, 20000,20000, 1000,  2, 10),
  (124, 'Bastion District Blueprint',       'Defense',     'building', 'bastion',          2,  6600, 17000, 9000, 3800,  2, 14),
  (125, 'Shield Generator Blueprint',       'Defense',     'building', 'shield_gen',       3, 12000, 35000,35000, 5000,  2, 60),
  (126, 'Turret Bunker Blueprint',          'Defense',     'building', 'turret_bunker',    2,  5400, 12000, 6000, 2000,  1, 8),
  (127, 'Planetary Cannon Blueprint',       'Defense',     'building', 'planetary_cannon', 4, 54000, 90000,90000,40000,  4, 300),
  (128, 'Lunar Base Blueprint',             'Lunar',       'building', 'lunar_base',       1,  7500, 20000,40000,20000,  2, 10),
  (129, 'Sensor Phalanx Blueprint',         'Lunar',       'building', 'sensor_phalanx',   3,  9000, 20000,40000,20000,  2, 25),
  (130, 'Jump Gate Blueprint',              'Lunar',       'building', 'jump_gate',        5, 2400000,2000000,4000000,2000000, 10, 800),
  (131, 'Stargate Ring Blueprint',          'Facilities',  'building', 'stargate_ring',    6, 3600000,5000000,5000000,3000000, 12, 1500),
  (132, 'Orbital Ring Blueprint',           'Facilities',  'building', 'orbital_ring',     5, 1800000,3000000,3000000,2000000, 10, 1200)
ON DUPLICATE KEY UPDATE bp_name = VALUES(bp_name), bp_kind = VALUES(bp_kind), target_key = VALUES(target_key);

-- ---------------------------------------------------------------------------
-- 4. AI factory unit storage
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS player_ai_units (
  uid        INT NOT NULL,
  unit_type  VARCHAR(24) NOT NULL,
  quantity   BIGINT NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (uid, unit_type)
);

INSERT INTO app_migrations (migration_key)
VALUES ('20260807_colony_grid_v1')
ON DUPLICATE KEY UPDATE migration_key = VALUES(migration_key);
