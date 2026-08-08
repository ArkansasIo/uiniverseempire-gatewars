<?php
/*
 * MIT License
 *
 * Copyright (c) 2026 Universe Civilization : Empire at wars
 * Copyright (c) 2026 Stephen, Universe Civilization : Empire at wars
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
include("../config.php");

$pagegen = new page_gen();
$pagegen->round_to = 4;
$pagegen->start();

$s = new Game();
if (!$s->loggedIn || !isset($_GET['time'])) {
    header("Location: ../index.php"); exit;
}
?>
<div class="card">
  <div class="feature-hero">
    <img src="../images/ui/core-command.svg" alt="FAQ" />
    <div>
      <h4>Frequently Asked Questions</h4>
      <p>Core rules, economy, units, fleets and policy &mdash; answered.</p>
    </div>
  </div>

  <h4>Accounts &amp; Access</h4>
  <p><strong>How do I get started?</strong><br />
  Register from the landing page, pick your race lineage and settle your home planet. Your first objective is growing income and untrained units so you can train a warforce.</p>
  <p><strong>Can I have more than one account?</strong><br />
  No. Multi-accounting is against the rules and can lead to a permanent ban.</p>

  <h4>Turns &amp; Economy</h4>
  <p><strong>What is an action turn?</strong><br />
  Action turns fuel most actions (attacks, training, fleets, recruitment). They refill on the turn cycle up to your cap, so spend them before they hit the ceiling.</p>
  <p><strong>How often does the turn cycle run?</strong><br />
  A turn advances every 30 minutes. Each turn grants action turns, Naquadah income and untrained-unit production. A unified server tick also advances strategic resources and long-running systems.</p>
  <p><strong>What is Naquadah?</strong><br />
  Naquadah is the core currency. Income is generated each turn by your miners and lifers, boosted by your race, income technology and planet bonuses. Upkeep is subtracted from your treasury each turn based on your trained units.</p>
  <p><strong>Why is my unit upkeep so high?</strong><br />
  Every trained unit costs upkeep per turn. Growing a large trained force requires the income to support it &mdash; check the upkeep rate on your status panel.</p>
  <p><strong>What are strategic resources?</strong><br />
  Metal, crystal, deuterium, food, water, population and energy. They are produced by structures on your planets and accumulate every 30 minutes. Energy and food are also consumed by upkeep; running out can slow production or cost population.</p>

  <h4>Units &amp; Training</h4>
  <p><strong>What is the difference between untrained and trained units?</strong><br />
  Untrained units (UU) are raw population produced each turn. Training converts them into attack, defense, covert or other specialist units &mdash; trained units fight but also cost upkeep.</p>
  <p><strong>What are miners and lifers?</strong><br />
  Miners and lifers are civilian units that boost your Naquadah income instead of fighting. They are the engine of a solid economy.</p>
  <p><strong>What are mercenaries?</strong><br />
  Mercenaries (attack/defense) are hired instantly for Naquadah and fight alongside regular units without training time.</p>

  <h4>Fleets, Hyperspace &amp; Trade</h4>
  <p><strong>How do fleet missions work?</strong><br />
  Build ships in the shipyard, launch missions from the fleet dock, and they resolve after their travel time. Hyperspace transits move through enroute &rarr; arrived &rarr; completed states.</p>
  <p><strong>What is deuterium used for?</strong><br />
  Deuterium is fuel. Fleets and hyperspace transits consume deuterium proportional to distance and fleet weight.</p>
  <p><strong>How do trade routes work?</strong><br />
  A trade route ships a fixed amount of a resource from one player to another each turn at a set rate until it completes. Both sender and receiver can see active routes.</p>
  <p><strong>How does the market work?</strong><br />
  List metal, crystal or deuterium for Naquadah (or the reverse). Matching is manual &mdash; a buyer accepts your offer. Watch the economy screen for deals.</p>
  <p><strong>What are bank deposit fees?</strong><br />
  Deposits into the bank storage incur a 5% fee; withdrawals are free. Your bank capacity scales with your planets and ascension level.</p>

  <h4>Combat, Covert &amp; Ranks</h4>
  <p><strong>How does attacking work?</strong><br />
  Your attack power faces the defender's defense power. Power comes from trained units, weapons, technology and race/planet bonuses. Casualties are proportional to the power ratio.</p>
  <p><strong>How does spying work?</strong><br />
  Spy missions compare your covert power against the target's anti-covert power. Higher ratios reveal more intel fields; weak ratios can fail or be detected.</p>
  <p><strong>How are ranks calculated?</strong><br />
  Your power snapshot (military attack/defense/covert totals) is recalculated on the turn cycle and compared across all pilots to build the leaderboards.</p>

  <h4>Policy &amp; Lore</h4>
  <p><strong>What does &quot;TBC&quot; stand for?</strong><br />
  &quot;To Be Coded&quot; &mdash; it marks a feature that is announced but not yet active in this build.</p>
  <p><strong>What does &quot;Lantea&quot; mean or stand for?</strong><br />
  In Stargate canon, Lantea is the homeworld of the Lanteans (the Ancients who settled the Pegasus galaxy) and the seat of Atlantis. In-game it appears as a nod to that legacy.</p>
  <p><strong>How do I report a bug or ask for help?</strong><br />
  Use the Contact page to reach staff with a timestamped report. Clear, detailed reports get resolved faster.</p>
</div>
