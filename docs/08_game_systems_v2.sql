-- Game Systems Expansion v2
-- Consolidates all CREATE TABLE IF NOT EXISTS and ALTER TABLE ADD COLUMN IF NOT EXISTS
-- statements from PHP modules into a single migration script for performance.

USE sgw;

-- ---------------------------------------------------------------------------
-- Core Player Resources and Structures
-- From modules/ogamebuildings.php, modules/stargatetech.php, modules/hyperspace.php,
-- modules/pages.php, modules/resourcehq.php, modules/stations.php, modules/commandergov.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS player_resources (
    uid INT NOT NULL PRIMARY KEY,
    metal BIGINT NOT NULL DEFAULT 80000,
    crystal BIGINT NOT NULL DEFAULT 60000,
    deuterium BIGINT NOT NULL DEFAULT 45000,
    food BIGINT NOT NULL DEFAULT 55000,
    water BIGINT NOT NULL DEFAULT 55000,
    population BIGINT NOT NULL DEFAULT 120000,
    energy BIGINT NOT NULL DEFAULT 50000,
    last_tick_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE player_resources ADD COLUMN IF NOT EXISTS energy BIGINT NOT NULL DEFAULT 50000;

CREATE TABLE IF NOT EXISTS resource_structures (
    uid INT NOT NULL PRIMARY KEY,
    metal_mine INT NOT NULL DEFAULT 1,
    crystal_lab INT NOT NULL DEFAULT 1,
    deuterium_refinery INT NOT NULL DEFAULT 1,
    hydroponics INT NOT NULL DEFAULT 1,
    water_plant INT NOT NULL DEFAULT 1,
    habitat_dome INT NOT NULL DEFAULT 1,
    energy_reactor INT NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE resource_structures ADD COLUMN IF NOT EXISTS energy_reactor INT NOT NULL DEFAULT 1;

-- ---------------------------------------------------------------------------
-- OGame Buildings
-- From modules/ogamebuildings.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ogame_building_levels (
    uid INT NOT NULL,
    building_key VARCHAR(64) NOT NULL,
    level INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(uid, building_key)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------------------------
-- Unit Catalog and Player Owned Units
-- From modules/unitcatalog.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS unit_catalog (
    unit_id INT(11) NOT NULL AUTO_INCREMENT,
    category VARCHAR(20) NOT NULL,
    unit_code VARCHAR(20) NOT NULL,
    unit_name VARCHAR(120) NOT NULL,
    class VARCHAR(50) NOT NULL,
    subclass VARCHAR(50) NOT NULL,
    tier INT(11) NOT NULL DEFAULT 1,
    attack_power INT(11) NOT NULL DEFAULT 0,
    defense_power INT(11) NOT NULL DEFAULT 0,
    covert_power INT(11) NOT NULL DEFAULT 0,
    income_gen INT(11) NOT NULL DEFAULT 0,
    metal_cost INT(11) NOT NULL DEFAULT 0,
    crystal_cost INT(11) NOT NULL DEFAULT 0,
    deut_cost INT(11) NOT NULL DEFAULT 0,
    food_cost INT(11) NOT NULL DEFAULT 0,
    water_cost INT(11) NOT NULL DEFAULT 0,
    pop_cost INT(11) NOT NULL DEFAULT 0,
    crew_cost INT(11) NOT NULL DEFAULT 0,
    description TEXT,
    PRIMARY KEY (unit_id),
    UNIQUE KEY unit_code (unit_code),
    KEY category (category)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS player_unit_owned (
    uid INT NOT NULL,
    unit_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(uid, unit_id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------------------------
-- Starship Catalog and Player Owned Starships
-- From modules/fleetdock.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS shipyard_starship_catalog (
    starship_id INT NOT NULL PRIMARY KEY,
    ship_code VARCHAR(16) NOT NULL,
    ship_name VARCHAR(120) NOT NULL,
    ship_title VARCHAR(140) NOT NULL,
    class_letter VARCHAR(4) NOT NULL DEFAULT 'D',
    class_subclass VARCHAR(8) NOT NULL DEFAULT 'I',
    ship_type VARCHAR(40) NOT NULL,
    ship_subtype VARCHAR(60) NOT NULL,
    family_name VARCHAR(60) NOT NULL,
    tier INT NOT NULL DEFAULT 1,
    metal_cost INT NOT NULL DEFAULT 0,
    crystal_cost INT NOT NULL DEFAULT 0,
    deut_cost INT NOT NULL DEFAULT 0,
    food_cost INT NOT NULL DEFAULT 0,
    water_cost INT NOT NULL DEFAULT 0,
    pop_cost INT NOT NULL DEFAULT 0,
    crew_required INT NOT NULL DEFAULT 0,
    power_rating INT NOT NULL DEFAULT 0,
    attack_stat INT NOT NULL DEFAULT 0,
    defense_stat INT NOT NULL DEFAULT 0,
    shield_stat INT NOT NULL DEFAULT 0,
    speed_stat INT NOT NULL DEFAULT 0,
    cargo_stat INT NOT NULL DEFAULT 0,
    systems_stat INT NOT NULL DEFAULT 0,
    warp_stat INT NOT NULL DEFAULT 0,
    legacy_key VARCHAR(32) NOT NULL DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS player_starship_owned (
    uid INT NOT NULL,
    starship_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    total_power BIGINT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(uid, starship_id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS shipyard (
    uid INT NOT NULL PRIMARY KEY,
    level INT NOT NULL DEFAULT 1,
    mothership_bay INT NOT NULL DEFAULT 0,
    dock_efficiency INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS fleet (
    uid INT NOT NULL PRIMARY KEY,
    probe INT NOT NULL DEFAULT 0,
    light_fighter INT NOT NULL DEFAULT 0,
    heavy_fighter INT NOT NULL DEFAULT 0,
    cruiser INT NOT NULL DEFAULT 0,
    battleship INT NOT NULL DEFAULT 0,
    carrier INT NOT NULL DEFAULT 0,
    recycler INT NOT NULL DEFAULT 0,
    colony_ship INT NOT NULL DEFAULT 0,
    mothership INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS fleet_missions (
    mission_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL,
    mission_type VARCHAR(24) NOT NULL,
    ship_type VARCHAR(32) NOT NULL,
    ship_count INT NOT NULL DEFAULT 0,
    target_uid INT NOT NULL DEFAULT 0,
    duration_minutes INT NOT NULL DEFAULT 15,
    eta_at DATETIME NOT NULL,
    return_at DATETIME NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'enroute',
    reward_naquadah INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uid_status (uid, status),
    INDEX idx_uid_eta (uid, eta_at),
    INDEX idx_uid_return (uid, return_at)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------------------------
-- Hyperspace Systems
-- From modules/hyperspace.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS hyperspace_systems (
    uid INT NOT NULL PRIMARY KEY,
    jump_gate_level INT NOT NULL DEFAULT 0,
    stargate_level INT NOT NULL DEFAULT 0,
    hyperspace_core_level INT NOT NULL DEFAULT 0,
    lane_stability INT NOT NULL DEFAULT 0,
    range_bonus INT NOT NULL DEFAULT 0,
    cooldown_reduction INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS hyperspace_routes (
    route_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL,
    route_name VARCHAR(80) NOT NULL,
    destination VARCHAR(80) NOT NULL,
    threat_tier INT NOT NULL DEFAULT 1,
    distance_ly INT NOT NULL DEFAULT 10,
    status VARCHAR(16) NOT NULL DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uid_status (uid, status)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS hyperspace_transits (
    transit_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL,
    route_id INT NOT NULL,
    transit_type VARCHAR(20) NOT NULL,
    fleet_tonnage INT NOT NULL DEFAULT 0,
    depart_at DATETIME NOT NULL,
    eta_at DATETIME NOT NULL,
    return_at DATETIME NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'enroute',
    reward_metal INT NOT NULL DEFAULT 0,
    reward_crystal INT NOT NULL DEFAULT 0,
    reward_deuterium INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_uid_status (uid, status),
    INDEX idx_uid_eta (uid, eta_at)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------------------------
-- Stargate Tech Levels and Research Infrastructure
-- From modules/stargatetech.php, modules/techlib.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS stargate_tech_levels (
    uid INT NOT NULL,
    tech_key VARCHAR(64) NOT NULL,
    level INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(uid, tech_key)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS research_infrastructure (
    uid INT NOT NULL PRIMARY KEY,
    research_campus INT NOT NULL DEFAULT 0,
    data_vault INT NOT NULL DEFAULT 0,
    simulation_core INT NOT NULL DEFAULT 0,
    quantum_archive INT NOT NULL DEFAULT 0,
    ai_directorate INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------------------------
-- Economy Store and Battle/Season Pass
-- From modules/pages.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS economy_store_catalog (
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS economy_store_purchases (
    uid INT NOT NULL,
    item_key VARCHAR(40) NOT NULL,
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(uid, item_key)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS economy_pass_progress (
    uid INT NOT NULL PRIMARY KEY,
    season_id INT NOT NULL DEFAULT 1,
    battle_pass_level INT NOT NULL DEFAULT 0,
    battle_pass_xp INT NOT NULL DEFAULT 0,
    season_pass_level INT NOT NULL DEFAULT 0,
    season_pass_xp INT NOT NULL DEFAULT 0,
    last_claimed_level INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS economy_pass_claims (
    uid INT NOT NULL,
    pass_type VARCHAR(20) NOT NULL,
    level INT NOT NULL,
    reward_key VARCHAR(64) NOT NULL,
    claimed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(uid, pass_type, level, reward_key)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------------------------
-- Military Command State and Troop Queue
-- From modules/pages.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS military_command_state (
    uid INT NOT NULL PRIMARY KEY,
    readiness_index INT NOT NULL DEFAULT 50,
    drill_xp INT NOT NULL DEFAULT 0,
    navy_focus VARCHAR(24) NOT NULL DEFAULT 'balanced',
    defense_posture VARCHAR(24) NOT NULL DEFAULT 'standard',
    logistics_posture VARCHAR(24) NOT NULL DEFAULT 'steady',
    war_games INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS military_troop_queue (
    queue_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL,
    troop_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    priority_order INT NOT NULL DEFAULT 0,
    eta_seconds INT NOT NULL DEFAULT 300,
    status VARCHAR(20) NOT NULL DEFAULT 'queued',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE military_troop_queue ADD COLUMN IF NOT EXISTS priority_order INT NOT NULL DEFAULT 0 AFTER quantity;

CREATE TABLE IF NOT EXISTS military_troop_catalog (
    troop_id INT NOT NULL PRIMARY KEY,
    troop_code VARCHAR(20) NOT NULL,
    troop_name VARCHAR(120) NOT NULL,
    troop_rank VARCHAR(60) NOT NULL,
    troop_title VARCHAR(120) NOT NULL,
    class_name VARCHAR(40) NOT NULL,
    class_subclass VARCHAR(60) NOT NULL,
    troop_type VARCHAR(60) NOT NULL,
    troop_subtype VARCHAR(60) NOT NULL,
    power_stat INT NOT NULL DEFAULT 0,
    attack_stat INT NOT NULL DEFAULT 0,
    defense_stat INT NOT NULL DEFAULT 0,
    covert_stat INT NOT NULL DEFAULT 0,
    anti_covert_stat INT NOT NULL DEFAULT 0,
    mobility_stat INT NOT NULL DEFAULT 0,
    morale_stat INT NOT NULL DEFAULT 0,
    logistics_stat INT NOT NULL DEFAULT 0,
    tactic_substat INT NOT NULL DEFAULT 0,
    resilience_substat INT NOT NULL DEFAULT 0,
    discipline_substat INT NOT NULL DEFAULT 0,
    attribute_primary VARCHAR(60) NOT NULL,
    attribute_secondary VARCHAR(60) NOT NULL,
    sub_attribute_a INT NOT NULL DEFAULT 0,
    sub_attribute_b INT NOT NULL DEFAULT 0,
    legion_name VARCHAR(60) NOT NULL,
    tier INT NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------------------------
-- Operations RTS State and Queue
-- From modules/pages.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS operations_rts_state (
    uid INT NOT NULL PRIMARY KEY,
    doctrine VARCHAR(24) NOT NULL DEFAULT 'balanced',
    tempo_mode VARCHAR(24) NOT NULL DEFAULT 'standard',
    theater_level INT NOT NULL DEFAULT 1,
    command_xp INT NOT NULL DEFAULT 0,
    cycle_index INT NOT NULL DEFAULT 0,
    frontline_pressure INT NOT NULL DEFAULT 45,
    reserve_integrity INT NOT NULL DEFAULT 60,
    morale_index INT NOT NULL DEFAULT 55,
    last_cycle_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS operations_turn_queue (
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE operations_turn_queue ADD COLUMN IF NOT EXISTS priority_order INT NOT NULL DEFAULT 0 AFTER reward_focus;

-- ---------------------------------------------------------------------------
-- Universe Events, World Boss, and Story Progress
-- From modules/pages.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS universe_world_plagues (
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS universe_world_water_sources (
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS universe_event_state (
    uid INT NOT NULL PRIMARY KEY,
    event_cycle INT NOT NULL DEFAULT 1,
    current_event VARCHAR(80) NOT NULL DEFAULT 'Calm Front',
    event_points INT NOT NULL DEFAULT 0,
    threat_level INT NOT NULL DEFAULT 20,
    last_event_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS universe_event_log (
    event_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL,
    galaxy_no INT NOT NULL,
    event_name VARCHAR(90) NOT NULL,
    event_type VARCHAR(40) NOT NULL,
    resolution_status VARCHAR(20) NOT NULL DEFAULT 'open',
    reward_points INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS universe_world_boss (
    uid INT NOT NULL PRIMARY KEY,
    boss_name VARCHAR(90) NOT NULL DEFAULT 'Dormant Leviathan',
    boss_level INT NOT NULL DEFAULT 1,
    boss_hp BIGINT NOT NULL DEFAULT 0,
    boss_hp_max BIGINT NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'idle',
    last_spawn_at TIMESTAMP NULL DEFAULT NULL,
    last_defeated_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS universe_story_progress (
    uid INT NOT NULL PRIMARY KEY,
    prologue_unlocked TINYINT(1) NOT NULL DEFAULT 0,
    current_act INT NOT NULL DEFAULT 1,
    current_chapter INT NOT NULL DEFAULT 1,
    chapter_points INT NOT NULL DEFAULT 0,
    completed_acts INT NOT NULL DEFAULT 0,
    last_story_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS universe_story_log (
    log_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL,
    act_no INT NOT NULL DEFAULT 1,
    chapter_no INT NOT NULL DEFAULT 1,
    entry_code VARCHAR(30) NOT NULL,
    entry_text VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS universe_colony_profiles (
    uid INT NOT NULL,
    world_index INT NOT NULL,
    target_type VARCHAR(10) NOT NULL DEFAULT 'planet',
    moon_no INT NOT NULL DEFAULT 0,
    world_type VARCHAR(40) NOT NULL,
    biome VARCHAR(80) NOT NULL,
    sub_biome VARCHAR(80) NOT NULL,
    city_name VARCHAR(90) NOT NULL,
    district_focus VARCHAR(40) NOT NULL DEFAULT 'balanced',
    field_total INT NOT NULL DEFAULT 16,
    field_used INT NOT NULL DEFAULT 0,
    infrastructure_tier INT NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(uid, world_index, target_type, moon_no)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS universe_colony_fields (
    field_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL,
    world_index INT NOT NULL,
    target_type VARCHAR(10) NOT NULL DEFAULT 'planet',
    moon_no INT NOT NULL DEFAULT 0,
    slot_no INT NOT NULL DEFAULT 1,
    building_code VARCHAR(24) NOT NULL,
    building_name VARCHAR(90) NOT NULL,
    building_level INT NOT NULL DEFAULT 1,
    power_draw INT NOT NULL DEFAULT 0,
    population_use INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_field_slot (uid, world_index, target_type, moon_no, slot_no)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------------------------
-- Player Tech Levels
-- From modules/pages.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS player_tech_levels (
    uid INT NOT NULL,
    tech_key VARCHAR(48) NOT NULL,
    level INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (uid, tech_key)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------------------------
-- Blueprint Systems
-- From modules/pages.php
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS player_blueprints (
    uid INT NOT NULL,
    blueprint_id INT NOT NULL,
    owned_copies INT NOT NULL DEFAULT 0,
    me_level INT NOT NULL DEFAULT 0,
    te_level INT NOT NULL DEFAULT 0,
    run_count INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(uid, blueprint_id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS blueprint_hangar (
    uid INT NOT NULL,
    blueprint_id INT NOT NULL,
    hull_class VARCHAR(40) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    total_power BIGINT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(uid, blueprint_id)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS universe_seed_bookmarks (
    uid INT NOT NULL,
    seed_index INT NOT NULL,
    seed_key VARCHAR(64) NOT NULL,
    note VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(uid, seed_index)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------------------------
-- Space Installations
-- From modules/stations.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS space_installations (
    uid INT NOT NULL PRIMARY KEY,
    space_station_level INT NOT NULL DEFAULT 0,
    starbase_level INT NOT NULL DEFAULT 0,
    moon_base_level INT NOT NULL DEFAULT 0,
    defense_grid INT NOT NULL DEFAULT 0,
    dock_matrix INT NOT NULL DEFAULT 0,
    scan_array INT NOT NULL DEFAULT 0,
    starbase_name VARCHAR(64) NOT NULL DEFAULT 'Starbase',
    moon_base_name VARCHAR(64) NOT NULL DEFAULT 'Moon Base',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE space_installations ADD COLUMN IF NOT EXISTS starbase_name VARCHAR(64) NOT NULL DEFAULT 'Starbase';
ALTER TABLE space_installations ADD COLUMN IF NOT EXISTS moon_base_name VARCHAR(64) NOT NULL DEFAULT 'Moon Base';

-- ---------------------------------------------------------------------------
-- Market Listings
-- From modules/market.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS market_listings (
    lid INT(11) NOT NULL AUTO_INCREMENT,
    uid INT(11) NOT NULL,
    resource VARCHAR(32) NOT NULL,
    amount INT(11) NOT NULL DEFAULT 0,
    price_per FLOAT NOT NULL DEFAULT 0,
    created INT(11) NOT NULL DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    PRIMARY KEY (lid),
    KEY idx_active (active,resource)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------------------------
-- Commander Governance Settings and Levels
-- From modules/commandergov.php
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS governance_system_levels (
    uid INT NOT NULL,
    gov_key VARCHAR(64) NOT NULL,
    level INT NOT NULL DEFAULT 0,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY(uid, gov_key)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS commander_settings (
    uid INT NOT NULL PRIMARY KEY,
    commander_mode VARCHAR(24) NOT NULL DEFAULT 'strategist',
    governance_style VARCHAR(24) NOT NULL DEFAULT 'balanced',
    policy_cycle VARCHAR(24) NOT NULL DEFAULT 'adaptive',
    visual_pack VARCHAR(24) NOT NULL DEFAULT 'ogame_classic',
    alert_level VARCHAR(24) NOT NULL DEFAULT 'standard',
    auto_delegate TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------------------------
-- Record migration completion
-- ---------------------------------------------------------------------------
INSERT INTO app_migrations (migration_key)
VALUES ('20260808_game_systems_v2')
ON DUPLICATE KEY UPDATE migration_key = VALUES(migration_key);