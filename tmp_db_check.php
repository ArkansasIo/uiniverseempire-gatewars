<?php
require 'config.php';
$mysqli = new mysqli($conf['db_server'], $conf['db_username'], $conf['db_password'], $conf['db_name']);
if ($mysqli->connect_error) {
    echo 'db_error:' . $mysqli->connect_error;
    exit(1);
}
$res = $mysqli->query('SELECT uid, uname, email, password, alevel FROM users LIMIT 5');
while ($row = $res->fetch_assoc()) {
    echo json_encode($row) . PHP_EOL;
}
