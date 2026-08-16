-- Master Fresh Game Database Installation Script
-- Universe Civilization: Empire at Wars (v1.5.0)

CREATE DATABASE IF NOT EXISTS sgw CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE sgw;

-- Core users and game state tables
CREATE TABLE IF NOT EXISTS `users` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `uname` varchar(64) NOT NULL,
  `password` varchar(64) NOT NULL,
  `email` varchar(128) NOT NULL,
  `race` int(11) NOT NULL DEFAULT 1,
  `hpname` varchar(64) NOT NULL DEFAULT 'Homeworld',
  `regdate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(45) NOT NULL DEFAULT '127.0.0.1',
  `activated` int(11) NOT NULL DEFAULT 1,
  `admin` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `uname` (`uname`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `userdata` (
  `uid` int(11) NOT NULL,
  `metal` double NOT NULL DEFAULT 1000,
  `crystal` double NOT NULL DEFAULT 500,
  `deuterium` double NOT NULL DEFAULT 200,
  `food` double NOT NULL DEFAULT 1000,
  `water` double NOT NULL DEFAULT 1000,
  `naquadah` double NOT NULL DEFAULT 100,
  `energy` double NOT NULL DEFAULT 100,
  `population` double NOT NULL DEFAULT 500,
  `actionTurns` int(11) NOT NULL DEFAULT 100,
  `bank` double NOT NULL DEFAULT 0,
  `rank` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `actionlog` (
  `actID` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `to_uid` int(11) NOT NULL DEFAULT 0,
  `time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` varchar(32) NOT NULL DEFAULT 'attack',
  `success` int(11) NOT NULL DEFAULT 1,
  `phrase` text NOT NULL,
  `stolen` double NOT NULL DEFAULT 0,
  `turnsUsed` int(11) NOT NULL DEFAULT 1,
  `attackPower` double NOT NULL DEFAULT 0,
  `defensePower` double NOT NULL DEFAULT 0,
  PRIMARY KEY (`actID`),
  KEY `uid` (`uid`),
  KEY `to_uid` (`to_uid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `user_prefs` (
  `uid` int(11) NOT NULL,
  `theme` varchar(32) NOT NULL DEFAULT 'blue',
  `notify_attack` tinyint(1) NOT NULL DEFAULT 1,
  `notify_message` tinyint(1) NOT NULL DEFAULT 1,
  `notify_market` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `app_settings` (
  `setting_key` varchar(128) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `blueprint_catalog` (
  `blueprint_id` int(11) NOT NULL AUTO_INCREMENT,
  `bp_kind` varchar(32) NOT NULL DEFAULT 'building',
  `building_key` varchar(64) NOT NULL,
  `building_name` varchar(128) NOT NULL,
  `category` varchar(64) NOT NULL,
  `tier` int(11) NOT NULL DEFAULT 1,
  `size_requirement` int(11) NOT NULL DEFAULT 1,
  `base_metal` double NOT NULL DEFAULT 0,
  `base_crystal` double NOT NULL DEFAULT 0,
  `base_deuterium` double NOT NULL DEFAULT 0,
  `base_naq` double NOT NULL DEFAULT 0,
  `base_turns` int(11) NOT NULL DEFAULT 1,
  `power_generated` double NOT NULL DEFAULT 0,
  `population_use` double NOT NULL DEFAULT 0,
  `scale_factor` double NOT NULL DEFAULT 1.5,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`blueprint_id`),
  UNIQUE KEY `building_key` (`building_key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Seed default application settings
INSERT INTO `app_settings` (`setting_key`, `setting_value`) VALUES
('maintenance.enabled', '0'),
('announcement.active', '0'),
('announcement.title', 'Welcome to Empire at Wars'),
('announcement.body', 'Stargate network operational.')
ON DUPLICATE KEY UPDATE `setting_value`=VALUES(`setting_value`);
