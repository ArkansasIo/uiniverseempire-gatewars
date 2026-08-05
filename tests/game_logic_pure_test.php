<?php
require_once __DIR__ . '/../config.php';

$game = new Game('copilotpilot', 'SGWLogin123!');
if (!$game->loggedIn || $game->userid !== 1 || $game->access !== 1) {
    fwrite(STDERR, "game_logic_pure_test failed: demo login did not initialize\n");
    exit(1);
}

// --- nextTurn(): minutes until the next 00/30 boundary on a 30-min tick cadence.
// Logic (inclusive bounds, x=2 band overwrites the boundary): 
//   minute 0..29 -> 30-minute; minute 30 -> 30; minute 31..59 -> 60-minute.
$minute = (int) date('i');
$expected = ($minute <= 29) ? (30 - $minute) : (($minute === 30) ? 30 : (60 - $minute));
$actual = $game->nextTurn();
if ($actual !== $expected) {
    fwrite(STDERR, "game_logic_pure_test failed: nextTurn() at minute {$minute} = {$actual}, expected {$expected}\n");
    exit(1);
}

// --- percs(): deterministic boundary bands.
if ($game->percs(0, 100) !== 0.0) {
    fwrite(STDERR, "game_logic_pure_test failed: percs(0,100) should be 0\n");
    exit(1);
}
if ($game->percs(100, 0) !== 0.0) {
    fwrite(STDERR, "game_logic_pure_test failed: percs(100,0) should be 0\n");
    exit(1);
}
if ($game->percs(1, 100) !== 0.0001) {
    fwrite(STDERR, "game_logic_pure_test failed: percs(1,100) should be 0.0001\n");
    exit(1);
}
if ($game->percs(5, 100) !== 0.01) {
    fwrite(STDERR, "game_logic_pure_test failed: percs(5,100) should be 0.01\n");
    exit(1);
}
for ($i = 0; $i < 50; $i++) {
    $p = $game->percs(3000, 1000);
    if ($p < 0.17 || $p > 0.19) {
        fwrite(STDERR, "game_logic_pure_test failed: percs(3000,1000) outside 0.17..0.19: {$p}\n");
        exit(1);
    }
    $p2 = $game->percs(4500, 1000);
    if ($p2 < 0.23 || $p2 > 0.25) {
        fwrite(STDERR, "game_logic_pure_test failed: percs(4500,1000) outside 0.23..0.25: {$p2}\n");
        exit(1);
    }
}

// --- level(): ascension tiers.
$t0 = $game->level(0);
if ($t0['str'] !== 'Non Ascended' || $t0['y'] !== 500000 || $t0['x'] !== 15) {
    fwrite(STDERR, "game_logic_pure_test failed: level(0) wrong: " . json_encode($t0) . "\n");
    exit(1);
}
$t5 = $game->level(5);
if ($t5['str'] !== 'Living God' || $t5['y'] !== 100000000 || $t5['x'] !== 40) {
    fwrite(STDERR, "game_logic_pure_test failed: level(5) wrong: " . json_encode($t5) . "\n");
    exit(1);
}

// --- salt(): deterministic 32-char md5, distinct across inputs.
$s1 = $game->salt('attack');
$s2 = $game->salt('attack');
if ($s1 !== $s2 || strlen($s1) !== 32 || !ctype_xdigit($s1)) {
    fwrite(STDERR, "game_logic_pure_test failed: salt() not deterministic md5 hex\n");
    exit(1);
}
if ($game->salt('attack') === $game->salt('defense')) {
    fwrite(STDERR, "game_logic_pure_test failed: salt() collisions across inputs\n");
    exit(1);
}

// --- fieldtocrypt()/crypttofield(): full round-trip on a synthetic field list.
$game->fields = ['attack', 'defense', 'covert'];
$crypted = $game->fieldtocrypt();
if (count($crypted) !== 3) {
    fwrite(STDERR, "game_logic_pure_test failed: fieldtocrypt() count mismatch\n");
    exit(1);
}
foreach ($crypted as $i => $c) {
    if ($game->crypttofield($c) !== ['attack', 'defense', 'covert'][$i]) {
        fwrite(STDERR, "game_logic_pure_test failed: crypttofield() round-trip failed at index {$i}\n");
        exit(1);
    }
}
if ($game->crypttofield('bogus-token') !== null) {
    fwrite(STDERR, "game_logic_pure_test failed: crypttofield() should return null for unknown token\n");
    exit(1);
}

// --- getRaces(): player race catalog fallback must yield the 5 canonical races.
$races = $game->getRaces();
if (count($races) !== 5) {
    fwrite(STDERR, "game_logic_pure_test failed: getRaces() count = " . count($races) . ", expected 5\n");
    exit(1);
}

// --- autoLoad(): 14-quoted-field fallback payload carries all core stats.
// number_format() embeds thousands separators, so count quoted fields rather
// than splitting on commas.
$payload = $game->autoLoad();
if (!preg_match_all('/"([^"]*)"/', $payload, $m)) {
    fwrite(STDERR, "game_logic_pure_test failed: autoLoad() payload had no quoted fields: {$payload}\n");
    exit(1);
}
$items = $m[1];
$expectedSlots = 14;
if (count($items) !== $expectedSlots) {
    fwrite(STDERR, "game_logic_pure_test failed: autoLoad() slot count = " . count($items) . ", expected {$expectedSlots}\n");
    exit(1);
}
// No-DB runtime fallback: onHand=0, but actionTurns=250 and resource defaults hold.
if ($items[3] !== '250') {
    fwrite(STDERR, "game_logic_pure_test failed: autoLoad() actionTurns slot = {$items[3]}, expected 250\n");
    exit(1);
}
if (!preg_match('/^\d+ minutes$/', $items[6])) {
    fwrite(STDERR, "game_logic_pure_test failed: autoLoad() next-turn slot = {$items[6]}\n");
    exit(1);
}
$resources = array_map(fn($v) => (int)str_replace(',', '', $v), array_slice($items, 7, 7));
if ($resources !== [1200, 900, 600, 80000, 70000, 150000, 15000]) {
    fwrite(STDERR, "game_logic_pure_test failed: autoLoad() resource defaults = " . json_encode($resources) . "\n");
    exit(1);
}

// --- nextTurn() returns within range and matches the 30-min formula for the current minute.
$nt = $game->nextTurn();
if ($nt < 0 || $nt > 30) {
    fwrite(STDERR, "game_logic_pure_test failed: nextTurn() out of range: {$nt}\n");
    exit(1);
}

echo "game logic pure checks passed\n";
