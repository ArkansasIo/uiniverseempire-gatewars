<?php
require __DIR__ . '/../modules/formal_logic.php';

$buttons = formalResearchTreeActionButtons('tree');
if (strpos($buttons, "Open Technology Tree") === false || strpos($buttons, "sendData('pages','get','research','techlib')") === false) {
    fwrite(STDERR, "Research tree button markup missing the technology link\n");
    exit(1);
}

$buttons = formalResearchTreeActionButtons('techlib');
if (strpos($buttons, "Open Research Tree") === false || strpos($buttons, "sendData('pages','get','research','tree')") === false) {
    fwrite(STDERR, "Technology tree button markup missing the research link\n");
    exit(1);
}

echo "research tree button checks passed\n";
