-- Game Systems Expansion v1
-- Alliance system, covert ops (sabotage / counter-intel), ascension,
-- trade routes, and colony management support tables.
USE sgw;

-- ---------------------------------------------------------------------------
-- Alliances
-- Founder is the creating user's UID. Members join via users.allyid /
-- users.arank (2 = leader, 1 = member, 0 = none). allybank holds naquadah
-- deposited by members for shared use.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS alliances (
  allyid    INT(11) NOT NULL AUTO_INCREMENT,
  allyname  VARCHAR(64) NOT NULL DEFAULT '',
  `desc`    TEXT NOT NULL,
  forumadd  VARCHAR(255) NOT NULL DEFAULT '',
  isclosed  TINYINT(1) NOT NULL DEFAULT 0,
  allybank  BIGINT(20) NOT NULL DEFAULT 0,
  founder   INT(11) NOT NULL DEFAULT 0,
  founded   VARCHAR(32) NOT NULL DEFAULT '',
  PRIMARY KEY (allyid),
  UNIQUE KEY allyname (allyname)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Alliance treasury ledger. action = deposit / withdraw / raid_loot.
CREATE TABLE IF NOT EXISTS alliance_bank_log (
  logid    INT(11) NOT NULL AUTO_INCREMENT,
  allyid   INT(11) NOT NULL DEFAULT 0,
  uid      INT(11) NOT NULL DEFAULT 0,
  action   VARCHAR(16) NOT NULL DEFAULT 'deposit',
  amount   BIGINT(20) NOT NULL DEFAULT 0,
  time     VARCHAR(32) NOT NULL DEFAULT '',
  PRIMARY KEY (logid),
  KEY allyid (allyid)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------------------------
-- Trade routes
-- A route moves `amount` naquadah from from_uid to to_uid over `turns`
-- game turns. On each tick, `rate` is transferred and `turns` is decreased.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS trade_routes (
  route_id  INT(11) NOT NULL AUTO_INCREMENT,
  from_uid  INT(11) NOT NULL DEFAULT 0,
  to_uid    INT(11) NOT NULL DEFAULT 0,
  amount    BIGINT(20) NOT NULL DEFAULT 0,
  rate      BIGINT(20) NOT NULL DEFAULT 0,
  turns     INT(11) NOT NULL DEFAULT 0,
  total     INT(11) NOT NULL DEFAULT 0,
  status    VARCHAR(16) NOT NULL DEFAULT 'active',
  created   VARCHAR(32) NOT NULL DEFAULT '',
  PRIMARY KEY (route_id),
  KEY from_uid (from_uid),
  KEY to_uid (to_uid)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------------------------
-- Ascension
-- Records every ascension a player performs so the ladder history and
-- requirements can be audited.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ascension_log (
  logid   INT(11) NOT NULL AUTO_INCREMENT,
  uid     INT(11) NOT NULL DEFAULT 0,
  level   INT(11) NOT NULL DEFAULT 0,
  naq     BIGINT(20) NOT NULL DEFAULT 0,
  time    VARCHAR(32) NOT NULL DEFAULT '',
  PRIMARY KEY (logid),
  KEY uid (uid)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------------------------
-- Colony management
-- Tracks colony focus, defense and automation so planet mechanics can be
-- extended without touching the core planets table.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS colony_state (
  uid      INT(11) NOT NULL,
  pid      INT(11) NOT NULL,
  focus    VARCHAR(16) NOT NULL DEFAULT 'balanced',
  defense  BIGINT(20) NOT NULL DEFAULT 0,
  last_colonized VARCHAR(32) NOT NULL DEFAULT '',
  PRIMARY KEY (uid, pid)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- ---------------------------------------------------------------------------
-- actionlog additions used by combat / spy / sabotage logging.
-- The core attack_raid() routine logs to these columns, so a clean install
-- must provide them for combat to record correctly.
-- ---------------------------------------------------------------------------
ALTER TABLE actionlog
  ADD COLUMN IF NOT EXISTS atkSent BIGINT(20) NOT NULL DEFAULT 0 AFTER type,
  ADD COLUMN IF NOT EXISTS atkEquip TEXT NOT NULL AFTER atkSent,
  ADD COLUMN IF NOT EXISTS defSent TEXT NOT NULL AFTER atkEquip,
  ADD COLUMN IF NOT EXISTS defEquip TEXT NOT NULL AFTER defSent,
  ADD COLUMN IF NOT EXISTS atkDead BIGINT(20) NOT NULL DEFAULT 0 AFTER defEquip,
  ADD COLUMN IF NOT EXISTS superAtkDead BIGINT(20) NOT NULL DEFAULT 0 AFTER atkDead,
  ADD COLUMN IF NOT EXISTS atkMercsDead BIGINT(20) NOT NULL DEFAULT 0 AFTER superAtkDead,
  ADD COLUMN IF NOT EXISTS antiDead BIGINT(20) NOT NULL DEFAULT 0 AFTER atkMercsDead,
  ADD COLUMN IF NOT EXISTS superAntiDead BIGINT(20) NOT NULL DEFAULT 0 AFTER antiDead,
  ADD COLUMN IF NOT EXISTS defDead BIGINT(20) NOT NULL DEFAULT 0 AFTER superAntiDead,
  ADD COLUMN IF NOT EXISTS superDefDead BIGINT(20) NOT NULL DEFAULT 0 AFTER defDead,
  ADD COLUMN IF NOT EXISTS defMercsDead BIGINT(20) NOT NULL DEFAULT 0 AFTER superDefDead,
  ADD COLUMN IF NOT EXISTS covDead BIGINT(20) NOT NULL DEFAULT 0 AFTER defMercsDead,
  ADD COLUMN IF NOT EXISTS superCovDead BIGINT(20) NOT NULL DEFAULT 0 AFTER covDead,
  ADD COLUMN IF NOT EXISTS atkWeaponStatus TEXT NOT NULL AFTER superCovDead,
  ADD COLUMN IF NOT EXISTS defWeaponStatus TEXT NOT NULL AFTER atkWeaponStatus;

INSERT INTO app_migrations (migration_key)
VALUES ('20260807_game_systems_v1')
ON DUPLICATE KEY UPDATE migration_key = VALUES(migration_key);
