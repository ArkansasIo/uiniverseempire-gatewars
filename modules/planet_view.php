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

// Fetch the user's planets from the database
$planets = $u->getUserPlanets($_SESSION['userid']);

echo "<h2>Your Colonies</h2>";

if (empty($planets)) {
    echo "<p>You have not established any colonies yet. Use a Colony Ship to settle a new world.</p>";
} else {
    echo "<div style='display: flex; flex-wrap: wrap; gap: 20px;'>";

    // This is a mapping from the 'text' field in the 'planets' table to an image type.
    // This logic will need to be expanded based on the actual data in the `planetsize` table.
    $type_map = [
        'Small Terran World' => 'terran',
        'Arid Planet' => 'desert',
        'Ice World' => 'ice',
        'Volcanic Planet' => 'volcanic',
        'Oceanic World' => 'oceanic',
        'Gas Giant' => 'gas',
    ];

    foreach ($planets as $planet) {
        // Determine the image path based on planet type.
        $planet_type_key = $planet['text'] ?? 'Small Terran World';
        $image_type = $type_map[$planet_type_key] ?? 'terran';
        $image_path = "images/planets/" . htmlspecialchars($image_type) . "_01.jpg";

        echo "<div class='auth-card' style='width: 200px; text-align: center;'>";
        echo "    <img src='{$image_path}' alt='" . htmlspecialchars($planet['plnt_name']) . "' style='width: 100%; height: auto; border-radius: 4px;' />";
        echo "    <h3 style='margin: 10px 0 5px;'>" . htmlspecialchars($planet['plnt_name']) . "</h3>";
        echo "    <p style='margin: 0; font-size: 0.9em;'>Type: " . htmlspecialchars($planet['text']) . "</p>";
        echo "    <p style='margin: 0; font-size: 0.9em;'>Size: " . htmlspecialchars($planet['plnt_size']) . "</p>";
        echo "</div>";
    }
    echo "</div>";
}

echo "</div>";

?>