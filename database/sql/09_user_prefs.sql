-- Pilot Settings - per-player preferences and announcement/maintenance state
USE sgw;

-- Per-player UI/notification preferences used by the in-game Pilot Settings
-- page (modules/settings.php). The game also creates this table on demand via
-- User::ensureUserPrefsTable(), so this migration only matters for a clean
-- install performed with db_migrate.sh.
CREATE TABLE IF NOT EXISTS user_prefs (
  uid INT(11) NOT NULL,
  theme VARCHAR(16) NOT NULL DEFAULT 'blue',
  notify_attack TINYINT(1) NOT NULL DEFAULT 1,
  notify_message TINYINT(1) NOT NULL DEFAULT 1,
  notify_market TINYINT(1) NOT NULL DEFAULT 1,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (uid)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Announcement banner state read by index.php (announcement.active controls
-- visibility; title/body hold the latest published content).
INSERT INTO app_settings (setting_key, setting_value)
VALUES
  ('announcement.active', '0'),
  ('announcement.title', ''),
  ('announcement.body', ''),
  ('maintenance.enabled', '0'),
  ('maintenance.message', '')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO app_migrations (migration_key)
VALUES ('20260807_user_prefs_v1')
ON DUPLICATE KEY UPDATE migration_key = VALUES(migration_key);
