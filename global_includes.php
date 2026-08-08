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
// Central compatibility include for legacy
//-style bootstrap files.
// This keeps the older config-style entry points from breaking while allowing
// the current Universe Civilization: Empire at Wars application to continue using config.php.

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
