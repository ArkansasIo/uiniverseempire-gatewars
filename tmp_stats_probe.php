<?php
require 'config.php';
session_start();
$_SESSION['userid'] = 1;
$_SESSION['username'] = 'copilotpilot';
$g = new Game('copilotpilot', 'SGWLogin123!');
echo $g->autoLoad();
