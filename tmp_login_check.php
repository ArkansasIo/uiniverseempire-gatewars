<?php
require 'config.php';
$u = new User('copilotpilot', 'SGWLogin123!');
echo $u->loggedIn ? 'loggedin' : 'failed';
