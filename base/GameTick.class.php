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
// Base::GameTick.class.php
// Turn tick engine: per-tick economy (naquadah income, unit upkeep, action
// turn refill, untrained production) plus rank recalculation.
//
// The engine is callable from cron (scripts/backend/turn_tick.php) and from the
// admin control panel. All math that matters lives in small static helpers so
// it can be unit-tested without a database connection.
//
// Tick tuning knobs are read from app_settings:
//   game_tick.upkeep_per_unit  - naquadah upkeep per trained unit per tick (1)
//   game_tick.max_turns        - action turn cap per player (250)
//   game_tick.turns_per_tick   - action turns granted per tick (180)

class GameTick extends Game
{
	/**
	 * Default action turns granted per tick: 30 minute tick window x 6 turns/minute.
	 */
	public const TURNS_PER_TICK = 180;

	/**
	 * Pure naquadah income formula, mirroring the Income column used by
	 * Game::turnUpdate(): (miners+lifers)*(80+income tech) plus planet income
	 * bonuses plus the race income bonus applied to the worker base.
	 */
	public static function computeIncome(int $miners, int $lifers, int $incomeTech, int $planetBonus, float $raceBonus, int $floor = 220): int
	{
		$miners = max(0, $miners);
		$lifers = max(0, $lifers);
		$incomeTech = max(0, $incomeTech);
		$planetBonus = max(0, $planetBonus);
		$raceBonus = max(0.0, (float)$raceBonus);
		$perWorker = 80 + $incomeTech;
		$base = ($miners + $lifers) * $perWorker;
		$total = $base + $planetBonus + ($raceBonus * $base);
		return max($floor, (int)$total);
	}

	/**
	 * Pure unit upkeep cost for one tick: trained units x per-unit rate.
	 */
	public static function computeUpkeep(int $trainedUnits, int $ratePerUnit): int
	{
		return (int)round(max(0, $trainedUnits) * max(0, $ratePerUnit));
	}

	/**
	 * Pure naquadah balance after income minus upkeep. Never goes below zero.
	 */
	public static function applyIncomeUpkeep(int $onHand, int $income, int $upkeep): int
	{
		return max(0, max(0, $onHand) + max(0, $income) - max(0, $upkeep));
	}

	/**
	 * Pure action-turn refill bounded by the server cap.
	 *
	 * @return array{total:int, granted:int}
	 */
	public static function computeTurnRefill(int $currentTurns, int $perTick, int $maxTurns): array
	{
		$current = max(0, (int)$currentTurns);
		$perTick = max(0, (int)$perTick);
		$max = max($perTick, (int)$maxTurns);
		$total = min($max, $current + $perTick);
		return [
			'total' => $total,
			'granted' => max(0, $total - $current),
		];
	}

	/**
	 * Summary of the most recent tick, read from app_settings.
	 */
	public function tickStatus(): array
	{
		$status = [
			'last_run' => 0,
			'last_duration' => 0.0,
			'last_processed' => 0,
			'last_income' => 0,
			'last_upkeep' => 0,
			'last_turns' => 0,
			'last_units' => 0,
			'last_status' => 'never',
			'upkeep_per_unit' => 1,
			'max_turns' => 250,
			'turns_per_tick' => self::TURNS_PER_TICK,
		];
		if (!$this->connected() || !$this->db_link) {
			return $status;
		}
		$q = $this->query("SELECT `setting_key`, `setting_value` FROM `app_settings` WHERE `setting_key` LIKE 'game_tick.%'");
		if ($q) {
			while ($row = $q->fetch_object()) {
				$key = (string)$row->setting_key;
				$val = (string)$row->setting_value;
				switch ($key) {
					case 'game_tick.last_run':
						$status['last_run'] = (int)$val;
						break;
					case 'game_tick.last_duration':
						$status['last_duration'] = (float)$val;
						break;
					case 'game_tick.last_processed':
						$status['last_processed'] = (int)$val;
						break;
					case 'game_tick.last_income':
						$status['last_income'] = (int)$val;
						break;
					case 'game_tick.last_upkeep':
						$status['last_upkeep'] = (int)$val;
						break;
					case 'game_tick.last_turns':
						$status['last_turns'] = (int)$val;
						break;
					case 'game_tick.last_units':
						$status['last_units'] = (int)$val;
						break;
					case 'game_tick.last_status':
						$status['last_status'] = $val;
						break;
					case 'game_tick.upkeep_per_unit':
						$status['upkeep_per_unit'] = max(0, (int)$val);
						break;
					case 'game_tick.max_turns':
						$status['max_turns'] = max(1, (int)$val);
						break;
					case 'game_tick.turns_per_tick':
						$status['turns_per_tick'] = max(0, (int)$val);
						break;
				}
			}
		}
		return $status;
	}

	/**
	 * Runs one turn tick for every active player (or a single uid).
	 *
	 * @param array $options uid, dry_run, rank
	 * @return array<string,mixed> summary with per-step totals.
	 */
	public function run(array $options = []): array
	{
		$uidFilter = isset($options['uid']) ? (int)$options['uid'] : 0;
		$dryRun = !empty($options['dry_run']);
		$doRank = !array_key_exists('rank', $options) || (bool)$options['rank'];

		$result = [
			'ok' => false,
			'error' => '',
			'dry_run' => $dryRun,
			'processed' => 0,
			'income_total' => 0,
			'upkeep_total' => 0,
			'turns_granted' => 0,
			'untrained_granted' => 0,
			'rank_recalc' => 0,
			'duration' => 0.0,
			'last_run' => time(),
		];
		if (!$this->connected() || !$this->db_link) {
			$result['error'] = 'Database connection is unavailable.';
			return $result;
		}

		$this->ensureTickTables();

		$upkeepRate = max(0, (int)$this->getAppSetting('game_tick.upkeep_per_unit', '1'));
		$maxTurns = max(1, (int)$this->getAppSetting('game_tick.max_turns', '250'));
		$turnsPerTick = max(0, (int)$this->getAppSetting('game_tick.turns_per_tick', (string)self::TURNS_PER_TICK));

		$start = microtime(true);

		$where = $uidFilter > 0 ? " WHERE u.`uid`=" . (int)$uidFilter : "";
		$q = $this->query("SELECT u.`uid` FROM `users` u" . $where . " ORDER BY u.`uid` ASC");
		if (!$q) {
			$result['error'] = 'Unable to list player accounts.';
			return $result;
		}

		$ids = [];
		while ($row = $q->fetch_object()) {
			$ids[] = (int)$row->uid;
		}
		if ($q->free_result) {
			$q->free_result();
		}

		foreach ($ids as $uid) {
			if ($uid <= 0) {
				continue;
			}

			$this->query("INSERT IGNORE INTO `bank` (`uid`,`onHand`,`inBank`) VALUES (" . $uid . ", 0, 0)");
			$this->query("INSERT IGNORE INTO `userdata` (`uid`,`actionTurns`,`rid`,`cid`,`progress`,`alevel`) VALUES (" . $uid . ", 250, 1, 0, 0, 1)");
			$this->query("INSERT IGNORE INTO `units` (`uid`) VALUES (" . $uid . ")");

			$income = $this->incomeFor($uid);
			$up = $this->upFor($uid);
			$trained = $this->trainedUnitsFor($uid);
			$upkeep = self::computeUpkeep($trained, $upkeepRate);
			$currentTurns = $this->currentTurnsFor($uid);
			$onHand = $this->onHandFor($uid);
			$turnRefill = self::computeTurnRefill($currentTurns, $turnsPerTick, $maxTurns);

			$result['income_total'] += $income;
			$result['upkeep_total'] += $upkeep;
			$result['turns_granted'] += $turnRefill['granted'];
			$result['untrained_granted'] += max(0, $up);
			$result['processed']++;

			if ($dryRun) {
				continue;
			}

			$net = self::applyIncomeUpkeep($onHand, $income, $upkeep);
			$stmt = $this->db_link->prepare("UPDATE `bank` SET `onHand`=? WHERE `uid`=? LIMIT 1");
			if ($stmt) {
				$stmt->bind_param("ii", $net, $uid);
				$stmt->execute();
			}

			$stmt = $this->db_link->prepare("UPDATE `userdata` SET `actionTurns`=? WHERE `uid`=? LIMIT 1");
			if ($stmt) {
				$stmt->bind_param("ii", $turnRefill['total'], $uid);
				$stmt->execute();
			}

			$stmt = $this->db_link->prepare("UPDATE `units` SET `untrained`=`untrained` + ? WHERE `uid`=? LIMIT 1");
			if ($stmt) {
				$stmt->bind_param("ii", $up, $uid);
				$stmt->execute();
			}

			if ($doRank) {
				$this->updatePower($uid);
				$result['rank_recalc']++;
			}
		}

		if (!$dryRun && $doRank) {
			$this->recalcOverallRank();
		}

		$result['duration'] = round(microtime(true) - $start, 4);

		if (!$dryRun) {
			$this->setAppSetting('game_tick.last_run', (string)$result['last_run']);
			$this->setAppSetting('game_tick.last_duration', (string)$result['duration']);
			$this->setAppSetting('game_tick.last_processed', (string)$result['processed']);
			$this->setAppSetting('game_tick.last_income', (string)$result['income_total']);
			$this->setAppSetting('game_tick.last_upkeep', (string)$result['upkeep_total']);
			$this->setAppSetting('game_tick.last_turns', (string)$result['turns_granted']);
			$this->setAppSetting('game_tick.last_units', (string)$result['untrained_granted']);
			$this->setAppSetting('game_tick.last_status', $result['error'] === '' ? 'ok' : 'error');
		}

		$result['ok'] = $result['error'] === '';
		return $result;
	}

	/**
	 * Naquadah income for one player this tick (see Game::turnUpdate Income column).
	 */
	private function incomeFor(int $uid): int
	{
		$stmt = $this->db_link->prepare("SELECT
			IFNULL(((units.miners*(80+technology.income)) + (units.lifers*(80+technology.income))
			  + IFNULL(SUM(planets.income_bonus),0)
			  + (race.income_bonus*((units.miners*(80+technology.income)) + (units.lifers*(80+technology.income))))), 220) AS income
			FROM `userdata`
			LEFT JOIN `units` ON units.uid = userdata.uid
			LEFT JOIN `planets` ON planets.uid = userdata.uid
			LEFT JOIN `race` ON race.rid = userdata.rid
			LEFT JOIN `technology` ON technology.uid = userdata.uid
			WHERE userdata.uid = ?
			GROUP BY userdata.uid LIMIT 1");
		if (!$stmt) {
			return 220;
		}
		$stmt->bind_param("i", $uid);
		$stmt->execute();
		$row = $stmt->get_result()->fetch_object();
		return (int)($row->income ?? 220);
	}

	/**
	 * Untrained unit production for one player this tick (see Game::turnUpdate up column).
	 */
	private function upFor(int $uid): int
	{
		$stmt = $this->db_link->prepare("SELECT
			IFNULL(((technology.unitProd*(3+technology.uppl)) + IFNULL(SUM(planets.up_bonus),0)
			  + (race.up_bonus*(technology.unitProd*(3+technology.uppl)))), 10) AS up
			FROM `userdata`
			LEFT JOIN `planets` ON planets.uid = userdata.uid
			LEFT JOIN `race` ON race.rid = userdata.rid
			LEFT JOIN `technology` ON technology.uid = userdata.uid
			WHERE userdata.uid = ?
			GROUP BY userdata.uid LIMIT 1");
		if (!$stmt) {
			return 10;
		}
		$stmt->bind_param("i", $uid);
		$stmt->execute();
		$row = $stmt->get_result()->fetch_object();
		return (int)($row->up ?? 10);
	}

	/**
	 * Count of trained (non-untrained) units for upkeep purposes.
	 */
	private function trainedUnitsFor(int $uid): int
	{
		$stmt = $this->db_link->prepare("SELECT
			IFNULL(`attack`,0)+IFNULL(`superAttack`,0)+IFNULL(`attackMercs`,0)
			+IFNULL(`defense`,0)+IFNULL(`superDefense`,0)+IFNULL(`defenseMercs`,0)
			+IFNULL(`covert`,0)+IFNULL(`superCovert`,0)+IFNULL(`anticovert`,0)+IFNULL(`superAnticovert`,0) AS trained
			FROM `units` WHERE `uid`=? LIMIT 1");
		if (!$stmt) {
			return 0;
		}
		$stmt->bind_param("i", $uid);
		$stmt->execute();
		$row = $stmt->get_result()->fetch_object();
		return (int)($row->trained ?? 0);
	}

	/**
	 * Current action turns for a player.
	 */
	private function currentTurnsFor(int $uid): int
	{
		$stmt = $this->db_link->prepare("SELECT `actionTurns` FROM `userdata` WHERE `uid`=? LIMIT 1");
		if (!$stmt) {
			return 0;
		}
		$stmt->bind_param("i", $uid);
		$stmt->execute();
		$row = $stmt->get_result()->fetch_object();
		return (int)($row->actionTurns ?? 0);
	}

	/**
	 * Current on-hand naquadah for a player.
	 */
	private function onHandFor(int $uid): int
	{
		$stmt = $this->db_link->prepare("SELECT `onHand` FROM `bank` WHERE `uid`=? LIMIT 1");
		if (!$stmt) {
			return 0;
		}
		$stmt->bind_param("i", $uid);
		$stmt->execute();
		$row = $stmt->get_result()->fetch_object();
		return (int)($row->onHand ?? 0);
	}

	/**
	 * Recomputes rank.overall ordering from the power table.
	 */
	private function recalcOverallRank(): void
	{
		$q = $this->query("SELECT `uid`, (IFNULL(`mil_atk`,0)+IFNULL(`mil_def`,0)+IFNULL(`mil_cov`,0)+IFNULL(`mil_anti`,0)) AS total
			FROM `power` ORDER BY total DESC");
		if (!$q) {
			return;
		}
		$pos = 0;
		$stmt = $this->db_link->prepare("UPDATE `rank` SET `overall`=? WHERE `uid`=? LIMIT 1");
		while ($row = $q->fetch_object()) {
			$pos++;
			if ($stmt) {
				$stmt->bind_param("ii", $pos, (int)$row->uid);
				$stmt->execute();
			}
		}
		if ($q->free_result) {
			$q->free_result();
		}
	}

	/**
	 * Ensures the app_settings table exists (idempotent) for tick metadata.
	 */
	private function ensureTickTables(): void
	{
		$this->getAppSetting('game_tick.last_status', '');
	}
}
