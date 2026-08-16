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
            <h3><span class="terminal-icon">&gt;_</span> Stargate Command Terminal</h3>
            <p>Secure real-time transmission log of combat, tactical raids, intelligence reconnaissance, and sabotage telemetry.</p>
        </div>
        <div class="terminal-meta">
            <div class="terminal-pill-meta">OPERATOR: ID #<?= terminal_h($_SESSION['userid']); ?></div>
            <div class="terminal-pill-meta">FILTER: [<?= terminal_h(strtoupper($typeFilter)); ?>]</div>
        </div>
    </div>

    <div class="terminal-filters">
        <?php foreach ($filters as $value => $label) { ?>
            <a class="terminal-btn-filter <?= $value === $typeFilter ? 'is-active' : ''; ?>" href="javascript:void(0)" onclick="sendData('terminal','get','mainDisplay','<?= terminal_h($value); ?>'); return false">
                <span class="filter-bullet"></span> <?= terminal_h($label); ?>
            </a>
        <?php } ?>
    </div>

    <div class="terminal-log">
        <?php if ($rows && $rows->num_rows > 0) { ?>
            <?php while ($row = $rows->fetch_object()) {
                $actor = $row->attacker_name ?: 'Unknown';
                $target = $row->target_name ?: 'Unknown';
                $direction = ((int)$row->uid === (int)$_SESSION['userid']) ? 'OUTBOUND' : 'INBOUND';
                $dirClass = $direction === 'OUTBOUND' ? 'dir-out' : 'dir-in';
                $status = ((int)$row->success === 0) ? 'FAILED' : 'SUCCESS';
                $statusClass = ((int)$row->success === 0) ? 'terminal-failure' : 'terminal-success';
                $summary = $row->phrase ?: ucfirst((string)$row->type);
                $result = ((int)$row->success === 0)
                    ? 'intercepted / repelled'
                    : number_format((float)$row->stolen) . ' resources';
                ?>
                <div class="terminal-line">
                    <div class="terminal-ts"><span class="ts-icon">⏱</span> <?= terminal_h($row->time); ?></div>
                    <div class="terminal-badge-wrap">
                        <span class="terminal-dir <?= $dirClass; ?>"><?= terminal_h($direction); ?></span>
                        <span class="terminal-kind-tag"><?= terminal_h(strtoupper($row->type)); ?></span>
                    </div>
                    <div class="terminal-body">
                        <div class="terminal-route">
                            <span class="terminal-actor"><?= terminal_h($actor); ?></span> 
                            <span class="route-arrow">➔</span> 
                            <span class="terminal-target"><?= terminal_h($target); ?></span>
                            <span class="terminal-status-pill <?= $statusClass; ?>"><?= terminal_h($status); ?></span>
                        </div>
                        <div class="terminal-desc"><?= terminal_h($summary); ?></div>
                        <div class="terminal-stats">
                            <span>RESULT: <strong><?= terminal_h($result); ?></strong></span>
                            <span>TURNS: <strong><?= terminal_h($row->turnsUsed); ?></strong></span>
                            <span>ATK: <strong><?= terminal_h(number_format((float)$row->attackPower)); ?></strong></span>
                            <span>DEF: <strong><?= terminal_h(number_format((float)$row->defensePower)); ?></strong></span>
                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php } else { ?>
            <div class="terminal-empty">
                <div class="empty-icon">⚡</div>
                <div>No telemetry logs recorded for active filter criteria.</div>
            </div>
        <?php } ?>
    </div>

    <div class="terminal-footer-meta">
        <span>SECURITY PROTOCOL: ENCRYPTED-STARS-42</span>
        <span>QUERY COUNT: <?= terminal_h($s->queryCount); ?></span>
    </div>
</div>
<?php
$pagegen->stop();
print('page generation time: ' . $pagegen->gen());
?>
