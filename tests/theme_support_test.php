<?php
require __DIR__ . '/../base/Theme.class.php';

if (ThemeSupport::normalizeTheme('Stargate') !== 'stargate') {
    fwrite(STDERR, "normalizeTheme failed\n");
    exit(1);
}

if (ThemeSupport::normalizeTheme('OG') !== 'og') {
    fwrite(STDERR, "normalizeTheme default failed\n");
    exit(1);
}

if (ThemeSupport::brandTitle('Universe Civilization: Empire at Wars') !== 'Universe Civilization: Empire at Wars') {
    fwrite(STDERR, "brandTitle failed\n");
    exit(1);
}

if (ThemeSupport::brandSubtitle('Custom subtitle') !== 'Custom subtitle') {
    fwrite(STDERR, "brandSubtitle failed\n");
    exit(1);
}

echo "theme helper checks passed\n";
