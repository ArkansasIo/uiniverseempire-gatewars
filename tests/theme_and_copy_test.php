<?php
$indexTpl = file_get_contents(__DIR__ . '/../templates/index.tpl');
if ($indexTpl === false) {
    fwrite(STDERR, "Unable to read templates/index.tpl\n");
    exit(1);
}

if (strpos($indexTpl, 'Universe Civilization: Empire at Wars') === false) {
    fwrite(STDERR, "theme/copy test failed: expected Universe Civilization: Empire at Wars branding in templates/index.tpl\n");
    exit(1);
}

if (strpos($indexTpl, 'data-theme="stargate"') === false) {
    fwrite(STDERR, "theme/copy test failed: expected the Stargate theme option in templates/index.tpl\n");
    exit(1);
}

$mainCss = file_get_contents(__DIR__ . '/../main.css');
if ($mainCss === false) {
    fwrite(STDERR, "Unable to read main.css\n");
    exit(1);
}

if (strpos($mainCss, 'body.theme-stargate') === false) {
    fwrite(STDERR, "theme/copy test failed: expected a Stargate theme definition in main.css\n");
    exit(1);
}

echo "theme and copy checks passed\n";
