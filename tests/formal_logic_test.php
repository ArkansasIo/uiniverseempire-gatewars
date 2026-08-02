<?php
require __DIR__ . '/../modules/formal_logic.php';

$cost = formalCostValue(1000, 2, 1.2, 0.8);
if ($cost !== 3744) {
    fwrite(STDERR, "formalCostValue failed: {$cost}\n");
    exit(1);
}

$readiness = formalReadinessIndex(180000, 260, 8, 900000, 12);
if ($readiness !== 100) {
    fwrite(STDERR, "formalReadinessIndex failed: {$readiness}\n");
    exit(1);
}

$hp = formalBossHp(4, 12);
if ($hp !== 300000 + (4 * 120000) + (12 * 2500)) {
    fwrite(STDERR, "formalBossHp failed: {$hp}\n");
    exit(1);
}

echo "formal logic checks passed\n";
