<?php
require_once __DIR__ . '/../config.php';

$game = new Game();
$races = $game->getRaces();
$expected = [
    1 => 'Ancient',
    2 => 'Nox',
    3 => "Tau'ri",
    4 => 'Asgard',
    5 => "Tok'ra",
];

if (count($races) < count($expected)) {
    fwrite(STDERR, "race catalog test failed: player races were not available\n");
    exit(1);
}

foreach ($expected as $id => $name) {
    $match = array_filter($races, static fn(array $race): bool => (int)($race['id'] ?? 0) === $id && ($race['name'] ?? '') === $name);
    if (!$match) {
        fwrite(STDERR, "race catalog test failed: missing {$name}\n");
        exit(1);
    }
}

echo "race catalog checks passed\n";
