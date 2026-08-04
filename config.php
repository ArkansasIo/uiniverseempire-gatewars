<?php
// Config File
session_cache_expire(180);
session_start();

function cfg_env($key, $default) {
	$value = getenv($key);
	return ($value === false || $value === '') ? $default : $value;
}

// General Information
$legacyTitle = defined('SGW_LEGACY_TITLE') ? SGW_LEGACY_TITLE : null;
$legacySubtitle = defined('SGW_LEGACY_SUBTITLE') ? SGW_LEGACY_SUBTITLE : null;
$subs['{TITLE}'] = cfg_env('SGW_TITLE', $legacyTitle ?: 'Stargate Wars');
$subs['{SUBTITLE}'] = cfg_env('SGW_SUBTITLE', $legacySubtitle ?: 'Strategic command and empire operations across the Stargate network');
$subs['{ADMIN_EMAIL}'] = cfg_env('SGW_ADMIN_EMAIL', "test.com");			# Person to email if something goes wrong
$subs['{HEAD_STUFF}'] = "";								# Stuff to put in <head>(left blank intentionally)

// Database Information
$legacyDbServer = getenv('SGW_DB_HOST') ?: null;
$legacyDbName = getenv('SGW_DB_NAME') ?: null;
$legacyDbUser = getenv('SGW_DB_USER') ?: null;
$legacyDbPass = getenv('SGW_DB_PASS') ?: null;
$conf['db_server'] = cfg_env('SGW_DB_HOST', $legacyDbServer ?: 'localhost');
$conf['db_name']  = cfg_env('SGW_DB_NAME', $legacyDbName ?: 'sgw');
$conf['db_username']  = cfg_env('SGW_DB_USER', $legacyDbUser ?: 'sgw');
$conf['db_password']  = cfg_env('SGW_DB_PASS', $legacyDbPass ?: 'sgwpass');
$conf['db_prefix'] = "";							# Prefix for DB tables
// Set Error Reporting
//error_reporting(E_ALL | E_STRICT);

define("PATH", dirname(__FILE__));
define("SCRIPT_PATH",PATH."/base/");
define("TEMPLATES_PATH",PATH."/templates/");
define("DEBUG",false);

include(SCRIPT_PATH."Chive.class.php");
include(SCRIPT_PATH."User.class.php");
include(SCRIPT_PATH."Debug.class.php");
include(SCRIPT_PATH."functions.php");
include(SCRIPT_PATH."Theme.class.php");
include(SCRIPT_PATH."Game.class.php");

// Optional developer/machine specific overrides.
$localConfig = PATH . "/config.local.php";
if (is_file($localConfig)) {
	include($localConfig);
}
?>
