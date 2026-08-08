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
// Unified game tick processor for cron usage. One run advances every
// time-based game system through the GameTick engine:
//   turn economy, strategic resources, hyperspace transits, fleet missions,
//   trade routes, military queues, RTS operations, power grid, market sweep
//   and inactive account purge.
//
// Usage:
//   php scripts/backend/game_tick.php
//   php scripts/backend/game_tick.php --uid=123
//   php scripts/backend/game_tick.php --dry-run
//   php scripts/backend/game_tick.php --no-rank
//   php scripts/backend/game_tick.php --systems=turn,res,hyper

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

$root = dirname(__DIR__, 2);
require_once $root . "/config.php";

if (!class_exists('mysqli')) {
    fwrite(STDERR, "Missing PHP MySQL driver in CLI runtime. Install/enable mysqli.\n");
    exit(2);
}

$options = [];
foreach ($argv as $arg) {
    if (strpos($arg, "--uid=") === 0) {
        $options['uid'] = (int)substr($arg, 6);
    }
    if ($arg === "--dry-run") {
        $options['dry_run'] = true;
    }
    if ($arg === "--no-rank") {
        $options['rank'] = false;
    }
    if (strpos($arg, "--systems=") === 0) {
        $raw = explode(',', substr($arg, 10));
        $systems = [];
        foreach ($raw as $sys) {
            $sys = trim($sys);
            if ($sys !== '') {
                $systems[] = $sys;
            }
        }
        if (count($systems) > 0) {
            $options['systems'] = $systems;
        }
    }
}

$tick = new GameTick();
$result = $tick->run($options);

if (!$result['ok']) {
    fwrite(STDERR, "Game tick failed: " . $result['error'] . "\n");
    exit(1);
}

echo "Game tick complete" . ($result['dry_run'] ? " (dry-run)" : "") . "\n";
echo "------------------------------------\n";
echo "Players processed: " . $result['processed'] . "\n";
echo "\n[Turn economy]\n";
echo "  Naquadah income:   " . number_format($result['income_total']) . "\n";
echo "  Unit upkeep:       " . number_format($result['upkeep_total']) . "\n";
echo "  Action turns:      " . number_format($result['turns_granted']) . "\n";
echo "  Untrained units:   " . number_format($result['untrained_granted']) . "\n";
echo "  Rank recalculated: " . $result['rank_recalc'] . "\n";
echo "\n[Fast cadence]\n";
echo "  Turns granted:     " . number_format($result['fast_turns_granted']) . "\n";
echo "  Resource minutes:  " . $result['fast_resource_minutes'] . "\n";
echo "  Resource updates:  " . $result['fast_resource_updates'] . "\n";
echo "\n[Strategic resources]\n";
echo "  Updates applied:   " . $result['resource_updates'] . "\n";
echo "  Tick slots:        " . $result['resource_ticks'] . "\n";
echo "  Starvation events: " . $result['starvation_events'] . "\n";
echo "\n[Hyperspace transits]\n";
echo "  Arrived:           " . $result['transits_arrived'] . "\n";
echo "  Completed:         " . $result['transits_completed'] . "\n";
echo "\n[Fleet missions]\n";
echo "  Arrived:           " . $result['missions_arrived'] . "\n";
echo "  Completed:         " . $result['missions_completed'] . "\n";
echo "  Expedition payouts:" . $result['expedition_rewards'] . "\n";
echo "\n[Trade routes]\n";
echo "  Transfers:         " . $result['routes_processed'] . "\n";
echo "  Completed:         " . $result['routes_completed'] . "\n";
echo "\n[Military queues]\n";
echo "  Done:              " . $result['military_done'] . "\n";
echo "  Failed:            " . $result['military_failed'] . "\n";
echo "  Waiting:           " . $result['military_waiting'] . "\n";
echo "\n[RTS operations]\n";
echo "  Done:              " . $result['ops_done'] . "\n";
echo "  Failed:            " . $result['ops_failed'] . "\n";
echo "  Waiting:           " . $result['ops_waiting'] . "\n";
echo "\n[Power grid]\n";
echo "  Catch-up ticks:    " . $result['power_ticks'] . "\n";
echo "  Node upgrades done:" . $result['power_done'] . "\n";
echo "  Node upgrades fail:" . $result['power_failed'] . "\n";
echo "\n[Maintenance]\n";
echo "  Listings expired:  " . $result['listings_expired'] . "\n";
echo "  Accounts purged:   " . $result['purged'] . "\n";
echo "------------------------------------\n";
echo "Duration: " . $result['duration'] . "s\n";
exit(0);
