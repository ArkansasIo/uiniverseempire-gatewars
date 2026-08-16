<?php
$host = 'localhost';
$user = 'root';
$pass = '';

$conn = @mysqli_connect($host, $user, $pass);
if (!$conn) {
    die("MySQL connection failed: " . mysqli_connect_error());
}

echo "Connected to MySQL successfully.\n";

$sqlFiles = glob(__DIR__ . '/database/sql/*.sql');
sort($sqlFiles);

foreach ($sqlFiles as $file) {
    echo "Executing " . basename($file) . "... ";
    $sql = file_get_contents($file);
    if (mysqli_multi_query($conn, $sql)) {
        do {
            if ($result = mysqli_store_result($conn)) {
                mysqli_free_result($result);
            }
        } while (mysqli_more_results($conn) && mysqli_next_result($conn));
        echo "SUCCESS\n";
    } else {
        echo "ERROR: " . mysqli_error($conn) . "\n";
    }
}

mysqli_close($conn);
echo "All database files installed/executed successfully!\n";
