<?php
/*
 * MIT License
 *
 * Copyright (c) 2026 Universe Civilization : Empire at wars
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
$s->updatePower($_SESSION['userid']);

function terminal_h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$typeFilter = $_GET['atype'] ?? 'all';
$allowedTypes = ['all', 'attack', 'raid', 'spy', 'sab'];
if (!in_array($typeFilter, $allowedTypes, true)) {
    $typeFilter = 'all';
}

$query = "SELECT a.`actID`, a.`time`, a.`type`, a.`success`, a.`phrase`, a.`stolen`, a.`turnsUsed`, a.`attackPower`, a.`defensePower`,
                 attacker.`uname` AS attacker_name, target.`uname` AS target_name
          FROM actionlog a
          LEFT JOIN users attacker ON attacker.uid = a.uid
          LEFT JOIN users target ON target.uid = a.to_uid
          WHERE (a.uid = ? OR a.to_uid = ?)";
if ($typeFilter !== 'all') {
    $query .= " AND a.`type` = ?";
}
$query .= " ORDER BY a.actID DESC LIMIT 25";

$stmt = $s->db_link->prepare($query);
if ($typeFilter === 'all') {
    $stmt->bind_param("ii", $_SESSION['userid'], $_SESSION['userid']);
} else {
    $stmt->bind_param("iis", $_SESSION['userid'], $_SESSION['userid'], $typeFilter);
}
$stmt->execute();
$rows = $stmt->get_result();

$filters = [
    'all' => 'All',
    'attack' => 'Attack',
    'raid' => 'Raid',
    'spy' => 'Spy',
    'sab' => 'Sabotage',
];
?>
<div class="terminal-shell">
    <div class="terminal-head">
        <div>
            <h3>Terminal Log System</h3>
            <p>Live command output from recent combat and covert action events.</p>
        </div>
        <div class="terminal-meta">
            <div>Player: <?= terminal_h($_SESSION['userid']); ?></div>
            <div>Mode: <?= terminal_h(strtoupper($typeFilter)); ?></div>
        </div>
    </div>

    <div class="terminal-filters">
        <?php foreach ($filters as $value => $label) { ?>
            <a class="<?= $value === $typeFilter ? 'is-active' : ''; ?>" href="javascript:void(0)" onclick="sendData('terminal','get','mainDisplay','<?= terminal_h($value); ?>'); return false"><?= terminal_h($label); ?></a>
        <?php } ?>
    </div>

    <div class="terminal-log">
        <?php if ($rows && $rows->num_rows > 0) { ?>
            <?php while ($row = $rows->fetch_object()) {
                $actor = $row->attacker_name ?: 'Unknown';
                $target = $row->target_name ?: 'Unknown';
                $direction = ((int)$row->uid === (int)$_SESSION['userid']) ? 'OUT' : 'IN';
                $status = ((int)$row->success === 0) ? 'FAIL' : 'OK';
                $statusClass = ((int)$row->success === 0) ? 'terminal-failure' : 'terminal-success';
                $summary = $row->phrase ?: ucfirst((string)$row->type);
                $result = ((int)$row->success === 0)
                    ? 'defended'
                    : number_format((float)$row->stolen);
                ?>
                <div class="terminal-line">
                    <div class="terminal-ts"><?= terminal_h($row->time); ?></div>
                    <div class="terminal-kind"><?= terminal_h($direction . ' ' . $row->type . ' ' . $status); ?></div>
                    <div class="terminal-body">
                        <span class="terminal-target"><?= terminal_h($actor); ?></span>
                        -> <span class="terminal-target"><?= terminal_h($target); ?></span>
                        | <?= terminal_h($summary); ?>
                        | <span class="<?= $statusClass; ?>"><?= terminal_h($status); ?></span>
                        | Result: <?= terminal_h($result); ?>
                        | Turns: <?= terminal_h($row->turnsUsed); ?>
                        | Atk: <?= terminal_h(number_format((float)$row->attackPower)); ?>
                        | Def: <?= terminal_h(number_format((float)$row->defensePower)); ?>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="terminal-empty">No terminal events captured for this filter.</div>
        <?php } ?>
    </div>

    <div style="margin-top:12px;font-size:11px;color:#8edfd0;">Query Count: <?= terminal_h($s->queryCount); ?></div>
</div>
<?php
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>
