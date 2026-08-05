<?php
require_once __DIR__ . '/../config.php';

$user = new User('copilotpilot', 'SGWLogin123!');
if (!$user->loggedIn || $user->userid !== 1 || $user->access !== 1) {
    fwrite(STDERR, "runtime fallback test failed: demo login did not initialize correctly\n");
    exit(1);
}

$game = new Game('copilotpilot', 'SGWLogin123!');
if (!$game->loggedIn || $game->userid !== 1 || $game->access !== 1) {
    fwrite(STDERR, "runtime fallback test failed: game bootstrap did not preserve the demo login state\n");
    exit(1);
}

$ranks = $game->getRanks();
if (!is_object($ranks) || !isset($ranks->milAtk, $ranks->milAtkRank, $ranks->milDef, $ranks->milDefRank, $ranks->milCov, $ranks->milCovRank, $ranks->milAnti, $ranks->milAntiRank, $ranks->mil, $ranks->milRank)) {
    fwrite(STDERR, "runtime fallback test failed: military rank data did not return a complete fallback object\n");
    exit(1);
}

$statsPayload = $game->autoLoad();
if (!is_string($statsPayload) || strpos($statsPayload, '250') === false || strpos($statsPayload, 'new Array') === false) {
    fwrite(STDERR, "runtime fallback test failed: stats payload did not return a usable fallback payload\n");
    exit(1);
}

echo "runtime fallback checks passed\n";
