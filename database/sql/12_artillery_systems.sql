-- Artillery Systems Expansion
-- Offense/Defense artillery for players with a full taxonomy:
-- major class (offense/defense) -> class -> subclass,
-- type -> subtype, stats -> sub-stats, attributes -> sub-attributes.
-- All three acquisition paths are supported: buy (naquadah + units +
-- resources), convert (trained / untrained units), and sell (scrap).
USE sgw;

-- ---------------------------------------------------------------------------
-- Artillery catalog: definitions for every artillery piece.
-- Battery assignment lives on player_artillery (reserve / offense / defense).
-- `attributes` holds a JSON array of
--   {"name": "...", "value": N, "sub": "...", "sub_value": N}
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS artillery_catalog (
  artillery_id   INT(11) NOT NULL AUTO_INCREMENT,
  artillery_code VARCHAR(16) NOT NULL DEFAULT '',
  artillery_name VARCHAR(120) NOT NULL DEFAULT '',
  artillery_title VARCHAR(160) NOT NULL DEFAULT '',
  major_class    VARCHAR(16) NOT NULL DEFAULT 'offense',
  class_name     VARCHAR(40) NOT NULL DEFAULT '',
  subclass_name  VARCHAR(60) NOT NULL DEFAULT '',
  type_name      VARCHAR(40) NOT NULL DEFAULT '',
  subtype_name   VARCHAR(60) NOT NULL DEFAULT '',
  tier           INT(11) NOT NULL DEFAULT 1,
  power_rating   INT(11) NOT NULL DEFAULT 0,
  attack_stat    INT(11) NOT NULL DEFAULT 0,
  attack_sub     INT(11) NOT NULL DEFAULT 0,
  defense_stat   INT(11) NOT NULL DEFAULT 0,
  defense_sub    INT(11) NOT NULL DEFAULT 0,
  shield_stat    INT(11) NOT NULL DEFAULT 0,
  shield_sub     INT(11) NOT NULL DEFAULT 0,
  accuracy_stat  INT(11) NOT NULL DEFAULT 0,
  accuracy_sub   INT(11) NOT NULL DEFAULT 0,
  range_stat     INT(11) NOT NULL DEFAULT 0,
  range_sub      INT(11) NOT NULL DEFAULT 0,
  reload_stat    INT(11) NOT NULL DEFAULT 0,
  reload_sub     INT(11) NOT NULL DEFAULT 0,
  mobility_stat  INT(11) NOT NULL DEFAULT 0,
  mobility_sub   INT(11) NOT NULL DEFAULT 0,
  naq_cost       BIGINT(20) NOT NULL DEFAULT 0,
  unit_cost      INT(11) NOT NULL DEFAULT 0,
  metal_cost     BIGINT(20) NOT NULL DEFAULT 0,
  crystal_cost   BIGINT(20) NOT NULL DEFAULT 0,
  deut_cost      BIGINT(20) NOT NULL DEFAULT 0,
  food_cost      BIGINT(20) NOT NULL DEFAULT 0,
  water_cost     BIGINT(20) NOT NULL DEFAULT 0,
  pop_cost       BIGINT(20) NOT NULL DEFAULT 0,
  attack_convert INT(11) NOT NULL DEFAULT 0,
  defense_convert INT(11) NOT NULL DEFAULT 0,
  build_time     INT(11) NOT NULL DEFAULT 0,
  attributes     TEXT NOT NULL,
  legacy_key     VARCHAR(32) NOT NULL DEFAULT '',
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (artillery_id),
  UNIQUE KEY idx_art_code (artillery_code),
  KEY idx_art_class (major_class, class_name)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------------------------
-- Player owned artillery. battery is one of: reserve / offense / defense.
-- total_power is quantity * power_rating at the time of acquisition.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS player_artillery (
  uid           INT(11) NOT NULL,
  artillery_id  INT(11) NOT NULL,
  battery       VARCHAR(16) NOT NULL DEFAULT 'reserve',
  quantity      INT(11) NOT NULL DEFAULT 0,
  total_power   BIGINT(20) NOT NULL DEFAULT 0,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (uid, artillery_id, battery)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO app_migrations (migration_key)
VALUES ('20260807_artillery_systems_v1')
ON DUPLICATE KEY UPDATE migration_key = VALUES(migration_key);
