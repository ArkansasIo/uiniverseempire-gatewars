<?php
require 'config.php';
$g = new Game('copilotpilot', 'SGWLogin123!');
$tech = $g->viewTech();
echo gettype($tech) . '|' . ($tech->ttl ?? 0) . PHP_EOL;
