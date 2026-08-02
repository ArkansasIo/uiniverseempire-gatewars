<?php
require __DIR__ . '/../modules/entity_name_helpers.php';

$sanitized = sanitizeEntityName("  New Colony / 01  ");
if ($sanitized !== 'New Colony / 01') {
    fwrite(STDERR, "sanitizeEntityName failed: {$sanitized}\n");
    exit(1);
}

$renamed = buildDisplayName('Starbase', 'My Starbase');
if ($renamed !== 'Starbase') {
    fwrite(STDERR, "buildDisplayName failed: {$renamed}\n");
    exit(1);
}

$defaulted = buildDisplayName('', 'My Starbase');
if ($defaulted !== 'My Starbase') {
    fwrite(STDERR, "buildDisplayName default failed: {$defaulted}\n");
    exit(1);
}

echo "entity name helper checks passed\n";
