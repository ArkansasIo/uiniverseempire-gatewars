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
include_once("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn) { header("Location: ../index.php"); exit; }

$atype = $_GET['atype'] ?? '';
$touid = (int)($_GET['id'] ?? 0);
$turns = max(1, (int)($_GET['turns'] ?? 15));

if (!in_array($atype, ['attack', 'raid', 'spy'])) {
    echo "Invalid action type.";
    exit;
}
if ($touid <= 0) {
    echo "Invalid target.";
    exit;
}
if ($touid === (int)$_SESSION['userid']) {
    echo "You cannot attack yourself.";
    exit;
}

if ($atype === 'attack' || $atype === 'raid') {
    $actID = $s->attack_raid($atype, $touid, $turns);
} else {
    $actID = $s->spy($touid, $turns);
}

if ($actID) {
    header("Location: actionLogs.php?id=" . $actID . "&time=" . microtime());
} else {
    echo "Action failed. Please check your turns and try again.";
}
exit;
?>