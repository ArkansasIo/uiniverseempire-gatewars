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
// Tests for the GameTick turn-tick engine and the admin tick/settings helpers.
// The CLI runtime has no mysqli, so only pure helpers and no-DB fallbacks are
// exercised here; the DB-backed paths are covered by the admin smoke tests.
require_once __DIR__ . '/../config.php';

// --- computeIncome(): formula mirrors Game::turnUpdate() income column.
// (miners+lifers) * (80 + income tech) + planet bonus + race bonus on base.
if (GameTick::computeIncome(100, 50, 10, 500, 0.25) !== 17375) {
    fwrite(STDERR, "game_tick_pure_test failed: computeIncome formula\n");
    exit(1);
}
// Floor protects degenerate inputs.
if (GameTick::computeIncome(0, 0, 0, 0, 0.0) !== 220) {
    fwrite(STDERR, "game_tick_pure_test failed: computeIncome floor\n");
    exit(1);
}
// Negative inputs are clamped.
if (GameTick::computeIncome(-5, -3, -2, -1, -0.5) !== 220) {
    fwrite(STDERR, "game_tick_pure_test failed: computeIncome negative clamp\n");
    exit(1);
}

// --- computeUpkeep(): trained units x rate, never negative.
if (GameTick::computeUpkeep(12345, 1) !== 12345) {
    fwrite(STDERR, "game_tick_pure_test failed: computeUpkeep\n");
    exit(1);
}
if (GameTick::computeUpkeep(100, 0) !== 0) {
    fwrite(STDERR, "game_tick_pure_test failed: computeUpkeep zero rate\n");
    exit(1);
}

// --- applyIncomeUpkeep(): onHand + income - upkeep, floored at zero.
if (GameTick::applyIncomeUpkeep(500, 300, 1000) !== 0) {
    fwrite(STDERR, "game_tick_pure_test failed: applyIncomeUpkeep floor\n");
    exit(1);
}
if (GameTick::applyIncomeUpkeep(500, 300, 100) !== 700) {
    fwrite(STDERR, "game_tick_pure_test failed: applyIncomeUpkeep normal\n");
    exit(1);
}

// --- computeTurnRefill(): bounded by the server cap.
$refill = GameTick::computeTurnRefill(240, 180, 250);
if ($refill !== ['total' => 250, 'granted' => 10]) {
    fwrite(STDERR, "game_tick_pure_test failed: computeTurnRefill cap = " . json_encode($refill) . "\n");
    exit(1);
}
$refill = GameTick::computeTurnRefill(0, 180, 250);
if ($refill !== ['total' => 180, 'granted' => 180]) {
    fwrite(STDERR, "game_tick_pure_test failed: computeTurnRefill empty = " . json_encode($refill) . "\n");
    exit(1);
}
$refill = GameTick::computeTurnRefill(260, 180, 250);
if ($refill['granted'] !== 0 || $refill['total'] !== 250) {
    fwrite(STDERR, "game_tick_pure_test failed: computeTurnRefill over cap = " . json_encode($refill) . "\n");
    exit(1);
}

// --- tickStatus(): safe defaults without a database.
$tick = new GameTick();
$status = $tick->tickStatus();
if ($status['last_run'] !== 0 || $status['last_status'] !== 'never') {
    fwrite(STDERR, "game_tick_pure_test failed: tickStatus defaults\n");
    exit(1);
}
if ($status['upkeep_per_unit'] !== 1 || $status['max_turns'] !== 250 || $status['turns_per_tick'] !== GameTick::TURNS_PER_TICK) {
    fwrite(STDERR, "game_tick_pure_test failed: tickStatus knobs\n");
    exit(1);
}

// --- run(): no database -> explicit error, never crashes.
$result = $tick->run();
if ($result['ok'] !== false || $result['error'] !== 'Database connection is unavailable.') {
    fwrite(STDERR, "game_tick_pure_test failed: run() no-DB error\n");
    exit(1);
}

// --- Admin delegation (no database): runGameTick reports failure cleanly.
$admin = new Admin('copilotpilot', 'SGWLogin123!');
if (!$admin->loggedIn || $admin->userid !== 1) {
    fwrite(STDERR, "game_tick_pure_test failed: admin demo login\n");
    exit(1);
}
if ($admin->isAdmin()) {
    fwrite(STDERR, "game_tick_pure_test failed: demo account must not be admin without a real DB\n");
    exit(1);
}
$result = $admin->runGameTick();
if ($result['ok'] !== false || $result['message'] !== 'Database connection is unavailable.') {
    fwrite(STDERR, "game_tick_pure_test failed: runGameTick no-DB\n");
    exit(1);
}

// --- Admin tickStatus(): default shape present.
$status = $admin->tickStatus();
if (!isset($status['last_run'], $status['last_status'], $status['turns_per_tick'])) {
    fwrite(STDERR, "game_tick_pure_test failed: admin tickStatus shape\n");
    exit(1);
}

// --- Admin player operations (no database): graceful error arrays.
$errors = $admin->deletePlayer(2);
if (!is_array($errors) || count($errors) === 0) {
    fwrite(STDERR, "game_tick_pure_test failed: deletePlayer no-DB errors\n");
    exit(1);
}
$errors = $admin->resetPlayer(2);
if (!is_array($errors) || count($errors) === 0) {
    fwrite(STDERR, "game_tick_pure_test failed: resetPlayer no-DB errors\n");
    exit(1);
}

// --- Admin announcement/maintenance (no database): safe defaults + writes fail.
if ($admin->publishAnnouncement('title', 'body') !== false) {
    fwrite(STDERR, "game_tick_pure_test failed: publishAnnouncement no-DB\n");
    exit(1);
}
if ($admin->setMaintenance(true, 'down') !== false) {
    fwrite(STDERR, "game_tick_pure_test failed: setMaintenance no-DB\n");
    exit(1);
}
$ann = $admin->announcementStatus();
if ($ann['active'] !== false || $ann['title'] !== '' || $ann['body'] !== '') {
    fwrite(STDERR, "game_tick_pure_test failed: announcementStatus defaults\n");
    exit(1);
}
$maint = $admin->maintenanceStatus();
if ($maint['enabled'] !== false) {
    fwrite(STDERR, "game_tick_pure_test failed: maintenanceStatus defaults\n");
    exit(1);
}

// --- Admin mass grants (no database): every target fails, no crash.
$mass = $admin->massGrant('naq', [1, 2, 3], 1000);
if ($mass['ok'] !== 0 || $mass['failed'] !== 3 || $mass['kind'] !== 'naq') {
    fwrite(STDERR, "game_tick_pure_test failed: massGrant no-DB = " . json_encode($mass) . "\n");
    exit(1);
}
if ($admin->allPlayerUids() !== []) {
    fwrite(STDERR, "game_tick_pure_test failed: allPlayerUids no-DB\n");
    exit(1);
}

// --- User prefs (no database): safe defaults, writes fail cleanly.
$u = new User('copilotpilot', 'SGWLogin123!');
$prefs = $u->getUserPrefs(1);
if ($prefs['theme'] !== 'blue' || $prefs['notify_attack'] !== 1 || $prefs['notify_message'] !== 1 || $prefs['notify_market'] !== 1) {
    fwrite(STDERR, "game_tick_pure_test failed: getUserPrefs defaults\n");
    exit(1);
}
if ($u->saveUserPrefs(1, ['theme' => 'white']) !== false) {
    fwrite(STDERR, "game_tick_pure_test failed: saveUserPrefs no-DB\n");
    exit(1);
}
$emailErrors = $u->updateEmail(1, 'bad-email');
if (!in_array('Database connection is unavailable.', $emailErrors, true)) {
    fwrite(STDERR, "game_tick_pure_test failed: updateEmail no-DB\n");
    exit(1);
}
$passErrors = $u->updatePassword(1, '123');
if (!in_array('Database connection is unavailable.', $passErrors, true)) {
    fwrite(STDERR, "game_tick_pure_test failed: updatePassword no-DB\n");
    exit(1);
}

echo "game tick pure checks passed\n";
