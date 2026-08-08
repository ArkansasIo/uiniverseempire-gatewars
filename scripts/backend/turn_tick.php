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
// Turn tick processor (economy + ranks) for cron usage. This delegates to the
// unified GameTick engine restricted to the legacy turn-economy system; the
// full tick (resources, hyperspace, fleet, trade, queues, power grid, purge)
// runs via scripts/backend/game_tick.php.
//
// Usage:
//   php scripts/backend/turn_tick.php
//   php scripts/backend/turn_tick.php --uid=123
//   php scripts/backend/turn_tick.php --dry-run
//   php scripts/backend/turn_tick.php --no-rank

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "CLI only\n";
    exit(1);
}

if (!class_exists('mysqli')) {
    fwrite(STDERR, "Missing PHP MySQL driver in CLI runtime. Install/enable mysqli.\n");
    exit(2);
}

$root = dirname(__DIR__, 2);
require_once $root . "/config.php";

$options = ['systems' => ['turn']];
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
}

$tick = new GameTick();
$result = $tick->run($options);

if ($result['ok']) {
    echo "Turn tick complete" . ($result['dry_run'] ? " (dry-run)" : "") . "\n";
    echo "Players processed: " . $result['processed'] . "\n";
    echo "Naquadah income: " . number_format($result['income_total']) . "\n";
    echo "Unit upkeep: " . number_format($result['upkeep_total']) . "\n";
    echo "Action turns granted: " . number_format($result['turns_granted']) . "\n";
    echo "Untrained produced: " . number_format($result['untrained_granted']) . "\n";
    echo "Rank recalculations: " . $result['rank_recalc'] . "\n";
    echo "Duration: " . $result['duration'] . "s\n";
    exit(0);
}

fwrite(STDERR, "Turn tick failed: " . $result['error'] . "\n");
exit(1);
