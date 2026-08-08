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
$s = new Game();
?>
<div class="card">
  <div class="feature-hero">
    <img src="../images/galaxy3.JPG" alt="Command Feed" />
    <div>
      <h4>Command Feed &mdash; Update Log</h4>
      <p>Every build of Universe Civilization: Empire at Wars ships through this feed. Newest first.</p>
    </div>
  </div>

  <p style="margin-top:10px;"><strong>Build 2026-08-07 &mdash; Unified Command</strong></p>
  <ul>
    <li><strong>Unified game tick engine.</strong> A single process now advances the turn economy, strategic resources, hyperspace transits, fleet missions, trade routes, military training queues, RTS operations, colony power grids, market listings and inactive-account cleanup.</li>
    <li><strong>Turn economy.</strong> Naquadah income, unit upkeep, action-turn refill and untrained production on the 30-minute turn cadence.</li>
    <li><strong>Strategic resources.</strong> Metal, crystal, deuterium, food, water, population and energy production on 30-minute slots.</li>
    <li><strong>Trade routes</strong> now transfer exactly once per tick.</li>
    <li><strong>Admin Control Panel.</strong> Staff login, player reset/delete, announcements, maintenance mode, mass grants and one-click tick control.</li>
    <li><strong>In-game settings.</strong> Email, password, theme picker and notification preferences.</li>
    <li><strong>Artillery system.</strong> A 180-piece offense/defense catalog.</li>
    <li><strong>Colony grid.</strong> Field buildings, power grid and AI factory.</li>
    <li><strong>Alliance, sabotage, ascension, spy and trade-route systems.</strong></li>
  </ul>

  <p style="margin-top:12px;"><strong>Build 2026-08-06 &mdash; Admin &amp; Stability</strong></p>
  <ul>
    <li>Admin control panel system with a staff operations layer.</li>
    <li>In-game settings, staff login and turn-tick engine.</li>
    <li>Runtime fatal and warning cleanup; removed dev probe files.</li>
    <li>Bound DB connect timeout so unreachable hosts fail fast instead of hanging.</li>
    <li>Remember-me login, MIT headers and legacy <code>mysql_*</code> call fixes.</li>
  </ul>

  <p style="margin-top:12px;"><strong>Build 2026-08-05 &mdash; v1.5 Foundation</strong></p>
  <ul>
    <li>Four-theme system: White, OG, Blue and Stargate.</li>
    <li>OGame-style research and technology tree with a pure logic module.</li>
    <li>Hardened XMLHttpRequest layer and userlist server program.</li>
    <li>Player banking and resource initialization.</li>
    <li>v1.5.0 documentation set and game-logic unit tests.</li>
  </ul>

  <p style="margin-top:14px; font-size:12px;">Full patch history is tracked in <code>PATCHLOG.md</code> at the repository root.</p>
</div>
