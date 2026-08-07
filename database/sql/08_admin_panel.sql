-- Admin Control Panel - schema additions
USE sgw;

-- Staff action audit log used by the admin control panel.
CREATE TABLE IF NOT EXISTS admin_log (
  logID INT(11) NOT NULL AUTO_INCREMENT,
  uid INT(11) NOT NULL DEFAULT 0,
  username VARCHAR(64) NOT NULL DEFAULT '',
  action VARCHAR(64) NOT NULL,
  target_uid INT(11) NOT NULL DEFAULT 0,
  details TEXT NOT NULL,
  ip_address VARCHAR(45) NOT NULL DEFAULT '',
  `time` VARCHAR(32) NOT NULL,
  PRIMARY KEY (logID),
  KEY idx_admin_log_uid (uid),
  KEY idx_admin_log_action (action),
  KEY idx_admin_log_time (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Player ban flag consumed by User::isRealUser() at login time.
ALTER TABLE users ADD COLUMN IF NOT EXISTS banned tinyint(1) NOT NULL DEFAULT 0;

-- Bootstrap: grant the seed account (uid 1) administrator access so the
-- panel can be reached after the first migration. Change this to your own
-- account id/username before going live.
UPDATE users SET alevel = 4 WHERE uid = 1 AND alevel < 4;

-- Seed admin-facing settings.
INSERT INTO app_settings (setting_key, setting_value)
VALUES
  ('admin.announcement', ''),
  ('site.maintenance_mode', 'off'),
  ('site.turn_interval_minutes', '30'),
  ('economy.default_reserve_ratio', '0.20'),
  ('operations.max_attack_turns_per_action', '15'),
  ('operations.max_covert_turns_per_action', '15')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO app_migrations (migration_key)
VALUES ('20260807_admin_panel_v1')
ON DUPLICATE KEY UPDATE migration_key = VALUES(migration_key);
