<?php
/*
 * MIT License
 *
 * Copyright (c) 2026 Universe Civilization : Empire at wars
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
