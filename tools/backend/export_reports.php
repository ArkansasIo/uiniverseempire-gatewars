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
// Export SQL snapshots and Excel-compatible CSV reports.

require_once dirname(__DIR__, 2) . '/config.php';

if (!class_exists('mysqli')) {
    fwrite(STDERR, "DB connection failed: mysqli extension not available in this PHP environment" . PHP_EOL);
    exit(1);
}

$mysqli = @new mysqli($conf['db_server'], $conf['db_username'], $conf['db_password'], $conf['db_name']);
if ($mysqli->connect_errno) {
    fwrite(STDERR, "DB connection failed: " . $mysqli->connect_error . PHP_EOL);
    exit(1);
}

$outDir = dirname(__DIR__, 2) . '/exports';
$sqlDir = $outDir . '/sql';
$excelDir = $outDir . '/excel';

if (!is_dir($sqlDir)) {
    mkdir($sqlDir, 0775, true);
}
if (!is_dir($excelDir)) {
    mkdir($excelDir, 0775, true);
}

$stamp = date('Ymd_His');

$queries = [
    'player_core' => "SELECT * FROM vw_player_core ORDER BY uid ASC",
    'player_economy' => "SELECT * FROM vw_player_economy ORDER BY uid ASC",
    'player_military' => "SELECT * FROM vw_player_military ORDER BY uid ASC",
];

foreach ($queries as $name => $sql) {
    $sqlFile = $sqlDir . '/' . $name . '_' . $stamp . '.sql.txt';
    file_put_contents($sqlFile, $sql . PHP_EOL);

    $result = $mysqli->query($sql);
    if (!$result) {
        fwrite(STDERR, "Query failed for {$name}: " . $mysqli->error . PHP_EOL);
        continue;
    }

    $csvPath = $excelDir . '/' . $name . '_' . $stamp . '.csv';
    $fp = fopen($csvPath, 'w');
    if ($fp === false) {
        fwrite(STDERR, "Unable to write {$csvPath}" . PHP_EOL);
        $result->free();
        continue;
    }

    $fields = [];
    while ($field = $result->fetch_field()) {
        $fields[] = $field->name;
    }
    fputcsv($fp, $fields);

    while ($row = $result->fetch_assoc()) {
        fputcsv($fp, $row);
    }

    fclose($fp);
    $result->free();

    echo "Generated: " . $csvPath . PHP_EOL;
}

$mysqli->close();
