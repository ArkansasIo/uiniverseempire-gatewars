<?php
// Central compatibility include for legacy Blacknova-style bootstrap files.
// This keeps the older config-style entry points from breaking while allowing
// the current Stargate Wars application to continue using config.php.

if (is_file(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

if (is_file(__DIR__ . '/db_config.php')) {
    require_once __DIR__ . '/db_config.php';
}

if (!isset($conf) || !is_array($conf)) {
    $conf = [];
}

if (!isset($conf['db_server']) && isset($db_server)) {
    $conf['db_server'] = $db_server;
}
if (!isset($conf['db_name']) && isset($db_name)) {
    $conf['db_name'] = $db_name;
}
if (!isset($conf['db_username']) && isset($db_username)) {
    $conf['db_username'] = $db_username;
}
if (!isset($conf['db_password']) && isset($db_password)) {
    $conf['db_password'] = $db_password;
}
if (!isset($conf['db_prefix']) && isset($db_prefix)) {
    $conf['db_prefix'] = $db_prefix;
}

if (!isset($subs) || !is_array($subs)) {
    $subs = [];
}

if (!isset($subs['{TITLE}']) && isset($game_name)) {
    $subs['{TITLE}'] = $game_name;
}
if (!isset($subs['{SUBTITLE}']) && isset($game_name)) {
    $subs['{SUBTITLE}'] = $game_name . ' live';
}

if (!isset($conf['db_port']) && isset($dbport)) {
    $conf['db_port'] = $dbport;
}
?>
