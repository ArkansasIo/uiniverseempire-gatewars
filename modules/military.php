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
include_once("../config.php");
if (!$s->loggedIn || !$_GET['time']) { print('Invalid request'); die(); }
$u = new Game();
$uid = (int)$_SESSION['userid'];

function mu_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function mu_num($value): string {
    return number_format((float)$value);
}

// Fetch unit counts from the database
$unitQ = $s->query("SELECT * FROM units WHERE uid=" . $uid . " LIMIT 1");
$units = $unitQ ? $unitQ->fetch_object() : null;

// Fetch unit names for the player's race
$raceId = (int)($_SESSION['raceID'] ?? 1);
$unitNamesQ = $s->query("SELECT * FROM unitnames WHERE rid=" . $raceId . " LIMIT 1");
$unitNames = $unitNamesQ ? $unitNamesQ->fetch_object() : null;

// Define the unit types and their display names
$unit_types = [
    'attack' => 'Attack Units',
    'superAttack' => 'Super-Attack Units',
    'defense' => 'Defense Units',
    'superDefense' => 'Super-Defense Units',
    'covert' => 'Covert Units',
    'superCovert' => 'Super-Covert Units',
    'anticovert' => 'Anti-Covert Units',
    'superAnticovert' => 'Super Anti-Covert Units',
    'miners' => 'Miners',
    'lifers' => 'Lifers',
    'untrained' => 'Untrained',
];

?>
<div class="page-hub">
    <div class="page-hub-head">
        <h3>Military Command</h3>
        <p>Review your standing army, from frontline assault troops to covert operatives.</p>
    </div>

    <div class="card full">
        <h4>General Army Composition</h4>
        <table class="mini-table" border="0" width="100%">
            <tr>
                <th align="left">Image</th>
                <th align="left">Unit Type</th>
                <th align="left">Designation</th>
                <th align="left">Quantity</th>
            </tr>
            <?php
            if ($units) {
                foreach ($unit_types as $key => $defaultName) {
                    $quantity = (int)($units->$key ?? 0);
                    // Use race-specific name if available, otherwise use the default
                    $displayName = !empty($unitNames->$key) ? $unitNames->$key : $defaultName;
                    $image_path = "images/units/" . mu_h($key) . ".jpg";
            ?>
            <tr>
                <td><img src="<?= $image_path; ?>" alt="<?= mu_h($displayName); ?>" width="60" style="vertical-align: middle;" /></td>
                <td><?= mu_h($displayName); ?></td>
                <td>(<?= mu_h($key); ?>)</td>
                <td><?= mu_num($quantity); ?></td>
            </tr>
            <?php
                }
            } else {
                echo '<tr><td colspan="4">No unit data available.</td></tr>';
            }
            ?>
        </table>
    </div>

    <div class="card full">
        <h4>Specialized Unit Roster (90-Class)</h4>
        <?php
        $new_units_query = $s->db_link->prepare(
            "SELECT p.quantity, c.unit_code, c.unit_name, c.class, c.subclass, c.tier, c.attack_power, c.defense_power, c.covert_power
             FROM player_unit_owned p
             JOIN unit_catalog c ON p.unit_id = c.unit_id
             WHERE p.uid = ? AND p.quantity > 0 AND c.category = 'military'
             ORDER BY c.tier DESC, c.unit_name ASC"
        );
        $new_units_query->bind_param("i", $uid);
        $new_units_query->execute();
        $new_units_result = $new_units_query->get_result();

        if ($new_units_result && $new_units_result->num_rows > 0) {
        ?>
            <table class="mini-table" border="0" width="100%">
                <tr>
                    <th align="left">Image</th>
                    <th align="left">Unit</th>
                    <th align="left">Class</th>
                    <th align="left">Tier</th>
                    <th align="left">Stats (Atk/Def/Cov)</th>
                    <th align="left">Quantity</th>
                </tr>
                <?php
                while ($unit = $new_units_result->fetch_object()) {
                    $image_path = "images/units/catalog/" . mu_h($unit->unit_code) . ".jpg";
                ?>
                <tr>
                    <td><img src="<?= $image_path; ?>" alt="<?= mu_h($unit->unit_name); ?>" width="60" style="vertical-align: middle;" /></td>
                    <td><?= mu_h($unit->unit_name); ?> <small>(<?= mu_h($unit->unit_code); ?>)</small></td>
                    <td><?= mu_h($unit->class); ?> / <?= mu_h($unit->subclass); ?></td>
                    <td>T<?= mu_h($unit->tier); ?></td>
                    <td><?= mu_num($unit->attack_power); ?> / <?= mu_num($unit->defense_power); ?> / <?= mu_num($unit->covert_power); ?></td>
                    <td><?= mu_num($unit->quantity); ?></td>
                </tr>
                <?php
                }
                ?>
            </table>
        <?php
        } else {
            echo '<p>No specialized military units have been recruited. <a href="javascript:void(0)" onclick="sendData(\'unitcatalog\',\'get\',\'mainDisplay\',\'military\'); return false;">Visit the Unit Catalog to recruit.</a></p>';
        }
        ?>
    </div>

    <div class="card full">
        <h4>Specialized Civilian Roster (90-Class)</h4>
        <?php
        $civ_units_query = $s->db_link->prepare(
            "SELECT p.quantity, c.unit_code, c.unit_name, c.class, c.subclass, c.tier, c.defense_power, c.income_gen
             FROM player_unit_owned p
             JOIN unit_catalog c ON p.unit_id = c.unit_id
             WHERE p.uid = ? AND p.quantity > 0 AND c.category = 'civilian'
             ORDER BY c.tier DESC, c.unit_name ASC"
        );
        $civ_units_query->bind_param("i", $uid);
        $civ_units_query->execute();
        $civ_units_result = $civ_units_query->get_result();

        if ($civ_units_result && $civ_units_result->num_rows > 0) {
        ?>
            <table class="mini-table" border="0" width="100%">
                <tr>
                    <th align="left">Image</th>
                    <th align="left">Unit</th>
                    <th align="left">Class</th>
                    <th align="left">Tier</th>
                    <th align="left">Stats (Def/Income)</th>
                    <th align="left">Quantity</th>
                </tr>
                <?php
                while ($unit = $civ_units_result->fetch_object()) {
                    $image_path = "images/units/catalog/" . mu_h($unit->unit_code) . ".jpg";
                ?>
                <tr>
                    <td><img src="<?= $image_path; ?>" alt="<?= mu_h($unit->unit_name); ?>" width="60" style="vertical-align: middle;" /></td>
                    <td><?= mu_h($unit->unit_name); ?> <small>(<?= mu_h($unit->unit_code); ?>)</small></td>
                    <td><?= mu_h($unit->class); ?> / <?= mu_h($unit->subclass); ?></td>
                    <td>T<?= mu_h($unit->tier); ?></td>
                    <td><?= mu_num($unit->defense_power); ?> / <?= mu_num($unit->income_gen); ?></td>
                    <td><?= mu_num($unit->quantity); ?></td>
                </tr>
                <?php
                }
                ?>
            </table>
        <?php
        } else {
            echo '<p>No specialized civilian units have been recruited. <a href="javascript:void(0)" onclick="sendData(\'unitcatalog\',\'get\',\'mainDisplay\',\'civilian\'); return false;">Visit the Unit Catalog to recruit.</a></p>';
        }
        ?>
    </div>

    <div class="card full">
        <h4>Specialized Government Roster (90-Class)</h4>
        <?php
        $gov_units_query = $s->db_link->prepare(
            "SELECT p.quantity, c.unit_code, c.unit_name, c.class, c.subclass, c.tier, c.defense_power, c.covert_power, c.income_gen
             FROM player_unit_owned p
             JOIN unit_catalog c ON p.unit_id = c.unit_id
             WHERE p.uid = ? AND p.quantity > 0 AND c.category = 'government'
             ORDER BY c.tier DESC, c.unit_name ASC"
        );
        $gov_units_query->bind_param("i", $uid);
        $gov_units_query->execute();
        $gov_units_result = $gov_units_query->get_result();

        if ($gov_units_result && $gov_units_result->num_rows > 0) {
        ?>
            <table class="mini-table" border="0" width="100%">
                <tr>
                    <th align="left">Image</th>
                    <th align="left">Unit</th>
                    <th align="left">Class</th>
                    <th align="left">Tier</th>
                    <th align="left">Stats (Def/Cov/Income)</th>
                    <th align="left">Quantity</th>
                </tr>
                <?php
                while ($unit = $gov_units_result->fetch_object()) {
                    $image_path = "images/units/catalog/" . mu_h($unit->unit_code) . ".jpg";
                ?>
                <tr>
                    <td><img src="<?= $image_path; ?>" alt="<?= mu_h($unit->unit_name); ?>" width="60" style="vertical-align: middle;" /></td>
                    <td><?= mu_h($unit->unit_name); ?> <small>(<?= mu_h($unit->unit_code); ?>)</small></td>
                    <td><?= mu_h($unit->class); ?> / <?= mu_h($unit->subclass); ?></td>
                    <td>T<?= mu_h($unit->tier); ?></td>
                    <td><?= mu_num($unit->defense_power); ?> / <?= mu_num($unit->covert_power); ?> / <?= mu_num($unit->income_gen); ?></td>
                    <td><?= mu_num($unit->quantity); ?></td>
                </tr>
                <?php
                }
                ?>
            </table>
        <?php
        } else {
            echo '<p>No specialized government units have been recruited. <a href="javascript:void(0)" onclick="sendData(\'unitcatalog\',\'get\',\'mainDisplay\',\'government\'); return false;">Visit the Unit Catalog to recruit.</a></p>';
        }
        ?>
    </div>
</div>

```

### 3. How to Access the New Module

You can now view your military units by adding a link to one of the main navigation menus in `templates/index.tpl`. For example:

```html
<a href="javascript:void(0)" onclick="sendData('military','get','mainDisplay'); return false;">Military</a>
```

Clicking this link will load the `military.php` module into the main content area, displaying a table of all your units, each with its own image. This provides a clear and visually engaging overview of your army's composition.

<!--
[PROMPT_SUGGESTION]Can you add image placeholders to the technology tree?[/PROMPT_SUGGESTION]
[PROMPT_SUGGESTION]How do I create a new page for managing alliances?[/PROMPT_SUGGESTION]
-->