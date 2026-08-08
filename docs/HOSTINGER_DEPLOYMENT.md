# Hostinger deployment guide

> **Version 1.5.0**

This project is a PHP/MySQL application. A typical Hostinger deployment is:

1. Upload the project files to the public web root.
2. Create a MySQL database and user in hPanel.
3. Import the SQL files.
4. Configure the database connection.
5. Verify the app loads at your domain.

## 1) Upload the files

In Hostinger File Manager:

- Open the public_html folder.
- Upload the contents of this repository into it.
- Keep the project root structure intact, including:
  - index.php
  - config.php
  - db_config.php
  - modules/
  - templates/
  - base/
  - database/

If your domain should serve the game from a subfolder, upload into that folder instead of public_html.

## 2) Create the database

In Hostinger hPanel:

- Open MySQL Databases.
- Create a new database and a user.
- Assign the user to the database.
- Save the database name, username, and password.
- Use those exact values in config.local.php, especially the database name and username.

## 3) Import the SQL

Use phpMyAdmin from Hostinger and import the SQL files in this order:

1. database/sql/01_create_database.sql
2. database/sql/02_import_core_schema.sql
3. database/sql/03_backend_tables.sql
4. database/sql/04_reporting_views.sql
5. database/sql/05_seed_backend_defaults.sql
6. database/sql/06_starship_planet_moon_details.sql
7. database/sql/07_unit_catalog_seed.sql
8. database/sql/08_game_systems_v2.sql
8. game.sql

If you prefer a minimal bootstrap, the README also references importing the initial database and game.sql.

## 4) Configure the app

Create a file named config.local.php in the project root.

A ready-to-edit version is already included in the repository root:

- config.local.php

You can also use the example file as a reference:

- config.local.php.example

Example:

```php
<?php
$subs['{TITLE}'] = 'Universe Civilization: Empire at Wars';
$subs['{SUBTITLE}'] = 'Strategic command and empire operations across the universe';
$subs['{ADMIN_EMAIL}'] = 'admin@example.com';

$conf['db_server'] = 'localhost';
$conf['db_name'] = 'your_database_name';
$conf['db_username'] = 'your_database_user';
$conf['db_password'] = 'your_database_password';
$conf['db_prefix'] = '';
```

Important:

- Hostinger MySQL usually uses localhost as the host.
- Keep the database prefix empty unless you specifically need one.

## 5) ADOdb requirement

This project references ADOdb in db_config.php. If the app shows database or include errors, upload the ADOdb library to:

- backends/adodb/

You can download ADOdb from the official project site and place the library files there.

## 6) PHP version

Use PHP 8.1 or 8.2 if possible. The app is PHP-based and should work best on a modern PHP runtime.

## 7) Test the site

After upload and database setup:

- Visit your domain.
- If the site displays the landing page but login fails, check the database connection first.
- If you see include or class errors, confirm ADOdb is present and the path in db_config.php is correct.

## 8) Default local test account

The repository README notes a local demo account:

- Username: copilotpilot
- Password: SGWLogin123!

If the demo data is imported correctly, that account should be available on the live site as well.
