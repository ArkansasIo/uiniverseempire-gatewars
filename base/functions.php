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
// Base::functions.php
function showPage(): void {
    Debug::printMsg("", "showPage()", "Displaying page...");
    $p = basename($_SERVER['PHP_SELF'], ".php");
    $p = TEMPLATES_PATH . $p . ".tpl";
    $output = template(TEMPLATES_PATH . "header.tpl", $GLOBALS['subs']);
    $output .= template($p, $GLOBALS['subs']);
    $output .= template(TEMPLATES_PATH . "footer.tpl", $GLOBALS['subs']);

    echo $output;
}

function addSub(string $subName, mixed $sub): void {
    $GLOBALS['subs']["{" . $subName . "}"] = $sub;
}

function template(string $filepath, array $subs): string|false {
    if (file_exists($filepath)) {
        $text = file_get_contents($filepath);
    } else {
        print "File '$filepath' not found";
        return false;
    }

    foreach ($subs as $sub => $repl) {
        $text = str_replace($sub, $repl, $text);
    }

    ob_start();
    eval("?>" . $text);
    $text = ob_get_contents();
    ob_end_clean();
    return $text;
}
?>