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
