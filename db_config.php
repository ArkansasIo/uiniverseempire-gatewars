<?php
/*
 * MIT License
 *
 * Copyright (c) 2026 Stargate Wars contributors
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
//
//
// File: db_config.php

// The ADOdb db module is now required to run BNT. You
// can find it at http://php.weblogs.com/ADODB. Enter the
// path where it is installed here. We suggest putting
// ADOdb into a subdirectory (adodb) under a subdirectory of BNT called backends.
$ADOdbpath = "backends/adodb";

// Compatibility aliases so the current app's config array can consume the values.
$db_server = 'localhost';
$db_name = 'sgw';
$db_username = 'sgw';
$db_password = 'sgwpass';
$db_prefix = '';
$db_port = '';
$game_name = 'Universe Civilization: Empire at Wars';

// Port to connect to database on. Note : if you do not know the port, set this to "" for default. Ex, MySQL default is 3306
$dbport = "";

/*
Hostname
*/
$ADODB_SESSION_CONNECT = "";
/*
Username + Password
*/
$ADODB_SESSION_USER = "";
$ADODB_SESSION_PWD = "";
/*
Database Name
*/
$ADODB_SESSION_DB = "";

// Define a random crypto key for ADOdb to use for encrypted sessions.
$ADODB_CRYPT_KEY = "";

// Type of the SQL database. This can be anything supported by ADOdb. Here are a few:
// "access" for MS Access databases. You need to create an ODBC DSN.
// "ado" for ADO databases
// "ibase" for Interbase 6 or earlier
// "borland_ibase" for Borland Interbase 6.5 or up
// "mssql" for Microsoft SQL
// "mysql" for MySQL - please don't use this one, it doesn't support transactions, which we now use
// "mysqlt" for MySQLi - needed for transaction support
// "oci8" for Oracle8/9
// "odbc" for a generic ODBC database
// "postgres" for PostgreSQL ver < 7
// "postgres7" for PostgreSQL ver 7 and up
// "sybase" for a SyBase database
// NOTE: only mysqlt works as of this release.
/*
Need to convert this to strict pdo statements...
*/
$ADODB_SESSION_DRIVER = "mysqlt";

// Set this to 1 to use db persistent connections, 0 otherwise - persistent connections can cause load problems!
$db_persistent = 0;

/*
Table prefix for each server
*/
$db_prefix = "";

$website_domain_default = "";

// The following two settings are now set automatically in global_cleanups.
// If it does not work, you'll need to comment them out, and uncomment and set the variables listed below.

// Domain & path of the game on your webserver (used to validate login cookie)
// This is the domain name part of the URL people enter to access your game.
// So if your game is at www.blah.com you would have:
// $gamedomain = "www.blah.com";
// Do not enter slashes for $gamedomain or anything that would come after a slash
// if you get weird errors with cookies then make sure the game domain has TWO dots
// i.e. if you reside your game on http://www.blacknova.net put .blacknova.net as $gamedomain.
// If your game is on http://www.some.site.net put .some.site.net as your game domain. Do not put port numbers in $gamedomain.
$gamedomain = "";

// This is the trailing part of the URL, that is not part of the domain.
// If you enter www.blah.com/blacknova to access the game, you would leave the line as it is.
// If you do not need to specify blacknova, just enter a single slash eg:
// $gamepath = "/bnt/";
?>
