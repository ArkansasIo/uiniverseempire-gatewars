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
// Base::GameTick.class.php
// Unified turn tick engine. One run() advances EVERY time-based game system:
//
//   turn   - naquadah income, unit upkeep, action turn refill, untrained
//            production, rank recalculation (legacy 30-minute turn economy)
//   res    - strategic resource economy (metal/crystal/deuterium/food/water/
//            population/energy) on a 30-minute cadence with stargate bonuses
//   hyper  - hyperspace transit state machine (enroute -> arrived -> completed)
//   fleet  - fleet mission state machine + expedition rewards + ship returns
//   trade  - active naquadah trade routes (rate-per-turn transfers)
//   mil    - military troop training queue (ready batches applied)
//   ops    - RTS operations turn queue (ready cycles applied)
//   grid   - colony power grid catch-up ticks + upgrade node queue
//   market - expired market listing sweep
//   purge  - inactive account purge (30-day last login)
//
// The engine is callable from cron (scripts/backend/game_tick.php /
// scripts/backend/turn_tick.php) and from the admin control panel. All math
// that matters lives in small static helpers so it can be unit-tested without
// a database connection.
//
// Tick tuning knobs are read from app_settings:
//   game_tick.upkeep_per_unit     - naquadah upkeep per trained unit per tick (1)
//   game_tick.max_turns           - action turn cap per player (250)
//   game_tick.turns_per_tick      - action turns granted per tick (180)
//   game_tick.resource_seconds    - strategic resource tick cadence (1800)
//   game_tick.purge_days          - inactive account purge threshold (30)

class GameTick extends Game
{
	/**
	 * Default action turns granted per tick: 30 minute tick window x 6 turns/minute.
	 */
	public const TURNS_PER_TICK = 180;

	/**
	 * Strategic resource economy cadence: one resource tick per 30 minutes.
	 */
	public const RESOURCE_TICK_SECONDS = 1800;

	/**
	 * Power grid catch-up cadence: one grid tick per 5 minutes.
	 */
	public const POWER_GRID_TICK_SECONDS = 300;

	/**
	 * Maximum power grid catch-up ticks per run (bounds expensive runs).
	 */
	public const POWER_GRID_MAX_TICKS = 48;

	/**
	 * Default inactive-account purge threshold in days.
	 */
	public const PURGE_DAYS = 30;

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
	 * Pure trade-route transfer amount: min(rate, remaining amount, on-hand).
	 */
	public static function computeTradeTransfer(int $rate, int $amount, int $onHand): int
	{
		return (int)min(max(0, $rate), max(0, $amount), max(0, $onHand));
	}

	/**
	 * Pure strategic-resource production rates, mirroring scripts/backend/game_tick.php
	 * calcRates() (now driven by the engine).
	 *
	 * @return array{metal:int,crystal:int,deuterium:int,food:int,water:int,population:int,energy:int}
	 */
	public static function resourceRates(array $ctx, array $levels, array $sgBonus): array
	{
		$incomeBase = max(220, (int)($ctx['income'] ?? 0));
		$upBase = max(10, (int)($ctx['up'] ?? 0));
		$techIncome = max(0, (int)($ctx['tech_income'] ?? 0));
		$techProd = max(0, (int)($ctx['tech_unit_prod'] ?? 0));
		$planetCount = max(1, (int)($ctx['planet_count'] ?? 0));

		$prodMul = max(1.0, (float)($sgBonus['production'] ?? 1.0));
		$energyMul = max(1.0, (float)($sgBonus['energy'] ?? 1.0));
		$deutMul = max(1.0, (float)($sgBonus['deuterium'] ?? 1.0));
		$popMul = max(1.0, (float)($sgBonus['population'] ?? 1.0));

		return [
			'metal' => (int)round(((($incomeBase * 0.40) + ($planetCount * 180) + ($upBase * 8) + ($techProd * 20)) * (1 + ((int)($levels['metal_mine'] ?? 1) * 0.12))) * $prodMul),
			'crystal' => (int)round(((($incomeBase * 0.28) + ($planetCount * 140) + ($upBase * 5) + ($techIncome * 16)) * (1 + ((int)($levels['crystal_lab'] ?? 1) * 0.12))) * $prodMul),
			'deuterium' => (int)round(((($incomeBase * 0.18) + ($planetCount * 120) + ($upBase * 3) + ($techIncome * 12)) * (1 + ((int)($levels['deuterium_refinery'] ?? 1) * 0.12))) * $prodMul * $deutMul),
			'food' => (int)round(((($incomeBase * 0.14) + ($planetCount * 220) + ($techIncome * 9)) * (1 + ((int)($levels['hydroponics'] ?? 1) * 0.10))) * $prodMul),
			'water' => (int)round(((($incomeBase * 0.12) + ($planetCount * 240) + ($techIncome * 8)) * (1 + ((int)($levels['water_plant'] ?? 1) * 0.10))) * $prodMul),
			'population' => max(25, (int)round(((($planetCount * 30) + ($upBase * 0.35)) * (1 + ((int)($levels['habitat_dome'] ?? 1) * 0.08))) * $popMul)),
			'energy' => (int)round(((($incomeBase * 0.22) + ($planetCount * 160) + ($techProd * 14) + ($techIncome * 10)) * (1 + ((int)($levels['energy_reactor'] ?? 1) * 0.13))) * $energyMul),
		];
	}

	/**
	 * Pure military recruitment cost of a troop batch (mirrors modules/pages.php).
	 */
	public static function militaryRecruitCosts(array $troopMeta, int $qty): array
	{
		$qty = max(1, $qty);
		return [
			'turns' => max(1, (int)ceil($qty / 20)),
			'units' => $qty,
			'naq' => (int)round(((int)($troopMeta['power_stat'] ?? 0) * 120) * $qty),
			'food' => (int)round(((int)($troopMeta['morale_stat'] ?? 0) * 2) * $qty),
			'water' => (int)round(((int)($troopMeta['logistics_stat'] ?? 0) * 2) * $qty),
			'deuterium' => (int)round(((int)($troopMeta['mobility_stat'] ?? 0) * 4) * $qty),
		];
	}

	/**
	 * Pure unit field a troop batch trains into (mirrors modules/pages.php).
	 */
	public static function militaryTroopRoleField(array $troopMeta): string
	{
		$unitField = 'attack';
		$className = strtolower((string)($troopMeta['class_name'] ?? ''));
		$typeName = strtolower((string)($troopMeta['troop_type'] ?? ''));
		if ($className === 'covert') {
			$unitField = 'covert';
		}
		if ($className === 'security' || $typeName === 'counter-covert') {
			$unitField = 'anticovert';
		}
		if ($className === 'heavy' || $typeName === 'defense' || $typeName === 'bulwark') {
			$unitField = 'defense';
		}
		return $unitField;
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
			'resource_seconds' => self::RESOURCE_TICK_SECONDS,
			'purge_days' => self::PURGE_DAYS,
			'last_resources' => 0,
			'last_hyperspace' => 0,
			'last_fleet' => 0,
			'last_trade' => 0,
			'last_military' => 0,
			'last_ops' => 0,
			'last_power' => 0,
			'last_market' => 0,
			'last_purged' => 0,
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
					case 'game_tick.resource_seconds':
						$status['resource_seconds'] = max(1, (int)$val);
						break;
					case 'game_tick.purge_days':
						$status['purge_days'] = max(1, (int)$val);
						break;
					case 'game_tick.last_resources':
						$status['last_resources'] = (int)$val;
						break;
					case 'game_tick.last_hyperspace':
						$status['last_hyperspace'] = (int)$val;
						break;
					case 'game_tick.last_fleet':
						$status['last_fleet'] = (int)$val;
						break;
					case 'game_tick.last_trade':
						$status['last_trade'] = (int)$val;
						break;
					case 'game_tick.last_military':
						$status['last_military'] = (int)$val;
						break;
					case 'game_tick.last_ops':
						$status['last_ops'] = (int)$val;
						break;
					case 'game_tick.last_power':
						$status['last_power'] = (int)$val;
						break;
					case 'game_tick.last_market':
						$status['last_market'] = (int)$val;
						break;
					case 'game_tick.last_purged':
						$status['last_purged'] = (int)$val;
						break;
				}
			}
		}
		return $status;
	}

	/**
	 * Runs ONE complete game tick: every time-based system for every active
	 * player (or a single uid).
	 *
	 * Supported options:
	 *   uid      - restrict processing to a single player id
	 *   dry_run  - compute and report without writing any data
	 *   rank     - (default true) recalculate overall ranks this tick
	 *   systems  - optional array of system names to restrict processing, e.g.
	 *              ['turn','res','hyper','fleet','trade','mil','ops','grid','market','purge']
	 *
	 * @return array<string,mixed> summary with per-step totals.
	 */
	public function run(array $options = []): array
	{
		$uidFilter = isset($options['uid']) ? (int)$options['uid'] : 0;
		$dryRun = !empty($options['dry_run']);
		$doRank = !array_key_exists('rank', $options) || (bool)$options['rank'];

		$allowed = ['turn', 'res', 'hyper', 'fleet', 'trade', 'mil', 'ops', 'grid', 'market', 'purge'];
		$systems = [];
		if (isset($options['systems']) && is_array($options['systems'])) {
			foreach ($options['systems'] as $sys) {
				if (in_array((string)$sys, $allowed, true)) {
					$systems[(string)$sys] = true;
				}
			}
		}
		if (count($systems) === 0) {
			foreach ($allowed as $sys) {
				$systems[$sys] = true;
			}
		}

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
			'resource_updates' => 0,
			'resource_ticks' => 0,
			'starvation_events' => 0,
			'transits_arrived' => 0,
			'transits_completed' => 0,
			'missions_arrived' => 0,
			'missions_completed' => 0,
			'expedition_rewards' => 0,
			'routes_processed' => 0,
			'routes_completed' => 0,
			'military_done' => 0,
			'military_failed' => 0,
			'military_waiting' => 0,
			'ops_done' => 0,
			'ops_failed' => 0,
			'ops_waiting' => 0,
			'power_ticks' => 0,
			'power_done' => 0,
			'power_failed' => 0,
			'listings_expired' => 0,
			'purged' => 0,
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
		$resourceSeconds = max(1, (int)$this->getAppSetting('game_tick.resource_seconds', (string)self::RESOURCE_TICK_SECONDS));

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
		$q->free_result();

		foreach ($ids as $uid) {
			if ($uid <= 0) {
				continue;
			}

			$this->query("INSERT IGNORE INTO `bank` (`uid`,`onHand`,`inBank`) VALUES (" . $uid . ", 0, 0)");
			$this->query("INSERT IGNORE INTO `userdata` (`uid`,`actionTurns`,`rid`,`cid`,`progress`,`alevel`) VALUES (" . $uid . ", 250, 1, 0, 0, 1)");
			$this->query("INSERT IGNORE INTO `units` (`uid`) VALUES (" . $uid . ")");

			$result['processed']++;

			// --- 1. Legacy turn economy: naq income, upkeep, turns, untrained.
			if (isset($systems['turn'])) {
				$turn = $this->processTurnEconomy($uid, $upkeepRate, $maxTurns, $turnsPerTick, $dryRun);
				$result['income_total'] += $turn['income'];
				$result['upkeep_total'] += $turn['upkeep'];
				$result['turns_granted'] += $turn['turns_granted'];
				$result['untrained_granted'] += $turn['untrained_granted'];
				if ($doRank && !$dryRun) {
					$this->updatePower($uid);
					$result['rank_recalc']++;
				}
			}

			// --- 2. Strategic resource economy (30-minute cadence).
			if (isset($systems['res'])) {
				$res = $this->processResources($uid, $resourceSeconds, $dryRun);
				$result['resource_updates'] += $res['updates'];
				$result['resource_ticks'] += $res['ticks'];
				$result['starvation_events'] += $res['starvation'];
			}

			// --- 3. Hyperspace transits.
			if (isset($systems['hyper'])) {
				$hyper = $this->processHyperspace($uid, $dryRun);
				$result['transits_arrived'] += $hyper['arrived'];
				$result['transits_completed'] += $hyper['completed'];
			}

			// --- 4. Fleet missions.
			if (isset($systems['fleet'])) {
				$fleet = $this->processFleetMissions($uid, $dryRun);
				$result['missions_arrived'] += $fleet['arrived'];
				$result['missions_completed'] += $fleet['completed'];
				$result['expedition_rewards'] += $fleet['expedition_rewards'];
			}

			// --- 5. Military troop queues.
			if (isset($systems['mil'])) {
				$mil = $this->processMilitaryQueue($uid, $dryRun);
				$result['military_done'] += $mil['done'];
				$result['military_failed'] += $mil['failed'];
				$result['military_waiting'] += $mil['waiting'];
			}

			// --- 7. RTS operations turn queues.
			if (isset($systems['ops'])) {
				$ops = $this->processOperationsQueue($uid, $dryRun);
				$result['ops_done'] += $ops['done'];
				$result['ops_failed'] += $ops['failed'];
				$result['ops_waiting'] += $ops['waiting'];
			}

			// --- 8. Colony power grid catch-up + node upgrades.
			if (isset($systems['grid'])) {
				$grid = $this->processPowerGrid($uid, $dryRun);
				$result['power_ticks'] += $grid['ticks'];
				$result['power_done'] += $grid['done'];
				$result['power_failed'] += $grid['failed'];
			}
		}

		if (!$dryRun && $doRank) {
			$this->recalcOverallRank();
		}

		// --- 8. Trade routes (global sweep: a route between two players must
		// only transfer once per tick, so it is processed outside the player loop).
		if (isset($systems['trade'])) {
			$trade = $this->processTradeRoutesTick($uidFilter, $dryRun);
			$result['routes_processed'] += $trade['processed'];
			$result['routes_completed'] += $trade['completed'];
		}

		// --- 9. Expire stale market listings (global sweep).
		if (isset($systems['market'])) {
			$result['listings_expired'] = $this->sweepMarket($dryRun);
		}

		// --- 10. Purge accounts inactive beyond the configured threshold.
		if (isset($systems['purge'])) {
			$result['purged'] = $this->purgeInactivePlayers($dryRun);
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
			$this->setAppSetting('game_tick.last_resources', (string)$result['resource_updates']);
			$this->setAppSetting('game_tick.last_hyperspace', (string)$result['transits_arrived']);
			$this->setAppSetting('game_tick.last_fleet', (string)$result['missions_arrived']);
			$this->setAppSetting('game_tick.last_trade', (string)$result['routes_processed']);
			$this->setAppSetting('game_tick.last_military', (string)$result['military_done']);
			$this->setAppSetting('game_tick.last_ops', (string)$result['ops_done']);
			$this->setAppSetting('game_tick.last_power', (string)$result['power_ticks']);
			$this->setAppSetting('game_tick.last_market', (string)$result['listings_expired']);
			$this->setAppSetting('game_tick.last_purged', (string)$result['purged']);
			$this->setAppSetting('game_tick.last_status', $result['error'] === '' ? 'ok' : 'error');
		}

		$result['ok'] = $result['error'] === '';
		return $result;
	}

	/**
	 * Legacy turn economy for one player: naquadah income, upkeep, action turn
	 * refill and untrained unit production.
	 *
	 * @return array{income:int,upkeep:int,turns_granted:int,untrained_granted:int}
	 */
	private function processTurnEconomy(int $uid, int $upkeepRate, int $maxTurns, int $turnsPerTick, bool $dryRun): array
	{
		$income = $this->incomeFor($uid);
		$up = $this->upFor($uid);
		$trained = $this->trainedUnitsFor($uid);
		$upkeep = self::computeUpkeep($trained, $upkeepRate);
		$currentTurns = $this->currentTurnsFor($uid);
		$onHand = $this->onHandFor($uid);
		$turnRefill = self::computeTurnRefill($currentTurns, $turnsPerTick, $maxTurns);

		if (!$dryRun) {
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
		}

		return [
			'income' => $income,
			'upkeep' => $upkeep,
			'turns_granted' => $turnRefill['granted'],
			'untrained_granted' => max(0, $up),
		];
	}

	/**
	 * Strategic resource economy catch-up for one player on the configured
	 * 30-minute cadence, including food/water/energy upkeep and the
	 * starvation population penalty. Also advances hyperspace systems.
	 *
	 * @return array{updates:int,ticks:int,starvation:int}
	 */
	private function processResources(int $uid, int $resourceSeconds, bool $dryRun): array
	{
		$this->query("INSERT IGNORE INTO `player_resources` (`uid`) VALUES (" . $uid . ")");
		$this->query("INSERT IGNORE INTO `resource_structures` (`uid`) VALUES (" . $uid . ")");
		$this->query("INSERT IGNORE INTO `hyperspace_systems` (`uid`) VALUES (" . $uid . ")");

		$baseRow = $this->query("SELECT
			IFNULL(((units.miners*(80+technology.income)) + (units.lifers*(80+technology.income)) + IFNULL(SUM(planets.income_bonus),0) + (race.income_bonus*((units.miners*(80+technology.income)) + (units.lifers*(80+technology.income))))),220) AS income,
			IFNULL(((technology.unitProd*(3+technology.uppl)) + IFNULL(SUM(planets.up_bonus),0) + (race.up_bonus*(technology.unitProd*(3+technology.uppl)))),10) AS up,
			IFNULL(technology.income,0) AS tech_income,
			IFNULL(technology.unitProd,0) AS tech_unit_prod
			FROM userdata
			LEFT JOIN units ON units.uid = userdata.uid
			LEFT JOIN planets ON planets.uid = userdata.uid
			LEFT JOIN race ON race.rid = userdata.rid
			LEFT JOIN technology ON technology.uid = userdata.uid
			WHERE userdata.uid=" . $uid . "
			GROUP BY userdata.uid");
		$base = $baseRow ? $baseRow->fetch_object() : null;

		$planetRow = $this->query("SELECT COUNT(*) AS c FROM planets WHERE uid=" . $uid);
		$planetCount = (int)($planetRow ? ($planetRow->fetch_object()->c ?? 0) : 0);

		$ctx = [
			'income' => (int)($base->income ?? 220),
			'up' => (int)($base->up ?? 10),
			'tech_income' => (int)($base->tech_income ?? 0),
			'tech_unit_prod' => (int)($base->tech_unit_prod ?? 0),
			'planet_count' => max(1, $planetCount),
		];

		$sRow = $this->query("SELECT metal_mine,crystal_lab,deuterium_refinery,hydroponics,water_plant,habitat_dome,energy_reactor FROM resource_structures WHERE uid=" . $uid . " LIMIT 1");
		$s = $sRow ? $sRow->fetch_object() : null;
		$levels = [
			'metal_mine' => (int)($s->metal_mine ?? 1),
			'crystal_lab' => (int)($s->crystal_lab ?? 1),
			'deuterium_refinery' => (int)($s->deuterium_refinery ?? 1),
			'hydroponics' => (int)($s->hydroponics ?? 1),
			'water_plant' => (int)($s->water_plant ?? 1),
			'habitat_dome' => (int)($s->habitat_dome ?? 1),
			'energy_reactor' => (int)($s->energy_reactor ?? 1),
		];

		$rates = self::resourceRates($ctx, $levels, $this->stargateBonus($uid));

		$rRow = $this->query("SELECT metal,crystal,deuterium,food,water,population,energy,last_tick_at FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
		if (!$rRow) {
			return ['updates' => 0, 'ticks' => 0, 'starvation' => 0];
		}
		$r = $rRow->fetch_object();
		if (!$r) {
			return ['updates' => 0, 'ticks' => 0, 'starvation' => 0];
		}

		$lastTick = strtotime((string)($r->last_tick_at ?? ''));
		if ($lastTick === false) {
			$lastTick = time();
		}
		$ticks = (int)floor(max(0, time() - $lastTick) / $resourceSeconds);

		$starvation = 0;
		if ($ticks > 0) {
			$metal = max(0, (int)$r->metal + ($rates['metal'] * $ticks));
			$crystal = max(0, (int)$r->crystal + ($rates['crystal'] * $ticks));
			$deuterium = max(0, (int)$r->deuterium + ($rates['deuterium'] * $ticks));
			$food = max(0, (int)$r->food + ($rates['food'] * $ticks));
			$water = max(0, (int)$r->water + ($rates['water'] * $ticks));
			$population = max(0, (int)$r->population + ($rates['population'] * $ticks));
			$energy = max(0, (int)$r->energy + ($rates['energy'] * $ticks));

			$foodUse = (int)round($population * 0.008 * $ticks);
			$waterUse = (int)round($population * 0.007 * $ticks);
			$energyUse = (int)round($population * 0.005 * $ticks);

			$food = max(0, $food - $foodUse);
			$water = max(0, $water - $waterUse);
			$energy = max(0, $energy - $energyUse);

			if ($food === 0 || $water === 0 || $energy === 0) {
				$population = max(0, $population - max(150, (int)round($population * 0.02)));
				$starvation = 1;
			}

			if (!$dryRun) {
				$this->query("UPDATE player_resources SET
					metal=" . $metal . ",
					crystal=" . $crystal . ",
					deuterium=" . $deuterium . ",
					food=" . $food . ",
					water=" . $water . ",
					population=" . $population . ",
					energy=" . $energy . ",
					last_tick_at=NOW()
					WHERE uid=" . $uid . " LIMIT 1");
			}
		}

		return ['updates' => $ticks > 0 ? 1 : 0, 'ticks' => $ticks, 'starvation' => $starvation];
	}

	/**
	 * Hyperspace transit state machine for one player: enroute -> arrived
	 * (expedition rewards) -> completed.
	 *
	 * @return array{arrived:int,completed:int}
	 */
	private function processHyperspace(int $uid, bool $dryRun): array
	{
		$arrived = 0;
		$completed = 0;

		$sys = $this->query("SELECT jump_gate_level,stargate_level,hyperspace_core_level FROM hyperspace_systems WHERE uid=" . $uid . " LIMIT 1");
		$sysRow = $sys ? $sys->fetch_object() : null;
		$jump = (int)($sysRow->jump_gate_level ?? 0);
		$stargate = (int)($sysRow->stargate_level ?? 0);
		$core = (int)($sysRow->hyperspace_core_level ?? 0);

		$enroute = $this->query("SELECT transit_id, transit_type FROM hyperspace_transits WHERE uid=" . $uid . " AND status='enroute' AND eta_at <= NOW() ORDER BY transit_id ASC");
		if ($enroute) {
			while ($t = $enroute->fetch_object()) {
				$tid = (int)$t->transit_id;
				$m = 0;
				$c = 0;
				$d = 0;
				if ($t->transit_type === 'expedition') {
					$m = random_int(2500, 12000) + ($core * 240);
					$c = random_int(1800, 9000) + ($stargate * 180);
					$d = random_int(1200, 7600) + ($jump * 140);
					if (!$dryRun) {
						$this->query("UPDATE player_resources SET metal=metal+" . $m . ", crystal=crystal+" . $c . ", deuterium=deuterium+" . $d . " WHERE uid=" . $uid . " LIMIT 1");
					}
				}
				if (!$dryRun) {
					$this->query("UPDATE hyperspace_transits SET status='arrived', reward_metal=" . $m . ", reward_crystal=" . $c . ", reward_deuterium=" . $d . " WHERE transit_id=" . $tid . " AND uid=" . $uid . " LIMIT 1");
				}
				$arrived++;
			}
			$enroute->free();
		}

		$done = $this->query("SELECT transit_id FROM hyperspace_transits WHERE uid=" . $uid . " AND status='arrived' AND return_at <= NOW() ORDER BY transit_id ASC");
		if ($done) {
			while ($t = $done->fetch_object()) {
				$tid = (int)$t->transit_id;
				if (!$dryRun) {
					$this->query("UPDATE hyperspace_transits SET status='completed' WHERE transit_id=" . $tid . " AND uid=" . $uid . " LIMIT 1");
				}
				$completed++;
			}
			$done->free();
		}

		return ['arrived' => $arrived, 'completed' => $completed];
	}

	/**
	 * Fleet mission state machine for one player: enroute -> arrived
	 * (expedition naquadah rewards) -> completed (ships return to fleet).
	 *
	 * @return array{arrived:int,completed:int,expedition_rewards:int}
	 */
	private function processFleetMissions(int $uid, bool $dryRun): array
	{
		$arrived = 0;
		$completed = 0;
		$expeditionRewards = 0;

		$arrivals = $this->query("SELECT mission_id, mission_type, ship_type, ship_count FROM fleet_missions WHERE uid=" . $uid . " AND status='enroute' AND eta_at <= NOW() ORDER BY mission_id ASC");
		if ($arrivals) {
			while ($mission = $arrivals->fetch_object()) {
				$missionId = (int)$mission->mission_id;
				$reward = 0;
				if ($mission->mission_type === 'expedition') {
					$reward = random_int(5000, 65000);
					if (!$dryRun) {
						$this->query("UPDATE bank SET onHand=onHand+" . $reward . " WHERE uid=" . $uid . " LIMIT 1");
						$this->query("UPDATE fleet_missions SET reward_naquadah=" . $reward . " WHERE mission_id=" . $missionId . " AND uid=" . $uid . " LIMIT 1");
					}
					$expeditionRewards++;
				}
				if (!$dryRun) {
					$this->query("UPDATE fleet_missions SET status='arrived' WHERE mission_id=" . $missionId . " AND uid=" . $uid . " LIMIT 1");
				}
				$arrived++;
			}
			$arrivals->free();
		}

		$returns = $this->query("SELECT mission_id, ship_type, ship_count FROM fleet_missions WHERE uid=" . $uid . " AND status='arrived' AND return_at <= NOW() ORDER BY mission_id ASC");
		if ($returns) {
			while ($mission = $returns->fetch_object()) {
				$missionId = (int)$mission->mission_id;
				$shipType = (string)$mission->ship_type;
				$shipCount = max(0, (int)$mission->ship_count);
				if ($shipType !== '' && $shipCount > 0 && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $shipType)) {
					if (!$dryRun) {
						$this->query("UPDATE fleet SET " . $shipType . "=" . $shipType . "+" . $shipCount . " WHERE uid=" . $uid . " LIMIT 1");
					}
				}
				if (!$dryRun) {
					$this->query("UPDATE fleet_missions SET status='completed' WHERE mission_id=" . $missionId . " AND uid=" . $uid . " LIMIT 1");
				}
				$completed++;
			}
			$returns->free();
		}

		return ['arrived' => $arrived, 'completed' => $completed, 'expedition_rewards' => $expeditionRewards];
	}

	/**
	 * Advances active naquadah trade routes for one player (rate-per-turn
	 * transfers; exhausted routes are marked complete).
	 *
	 * @return array{processed:int,completed:int}
	 */
	private function processTradeRoutesTick(int $uid, bool $dryRun): array
	{
		$processed = 0;
		$completed = 0;
		$query = "SELECT tr.route_id, tr.from_uid, tr.to_uid, tr.amount, tr.rate, tr.turns, b.onHand
			FROM trade_routes tr INNER JOIN bank b ON b.uid=tr.from_uid
			WHERE tr.status='active' AND tr.turns > 0";
		if ($uid > 0) {
			$query .= " AND (tr.from_uid=" . (int)$uid . " OR tr.to_uid=" . (int)$uid . ")";
		}
		$q = $this->query($query);
		if (!$q) {
			return ['processed' => 0, 'completed' => 0];
		}
		while ($row = $q->fetch_object()) {
			$routeId = (int)$row->route_id;
			$transfer = self::computeTradeTransfer((int)$row->rate, (int)$row->amount, (int)$row->onHand);
			$nowExhausted = ((int)$row->turns - 1 <= 0) || ((int)$row->amount - $transfer <= 0);
			if ($transfer > 0) {
				if (!$dryRun) {
					$this->query("UPDATE `bank` SET `onHand`=`onHand`-" . $transfer . " WHERE `uid`=" . (int)$row->from_uid . " LIMIT 1");
					$this->query("UPDATE `bank` SET `onHand`=`onHand`+" . $transfer . " WHERE `uid`=" . (int)$row->to_uid . " LIMIT 1");
					$this->query("UPDATE `trade_routes` SET `amount`=`amount`-" . $transfer . ", `turns`=`turns`-1 WHERE `route_id`=" . $routeId . " LIMIT 1");
				}
				$processed++;
			}
			if ($nowExhausted) {
				if (!$dryRun) {
					$this->query("UPDATE `trade_routes` SET `status`='complete' WHERE `route_id`=" . $routeId . " LIMIT 1");
				}
				$completed++;
			}
		}
		$q->free_result();
		return ['processed' => $processed, 'completed' => $completed];
	}

	/**
	 * Applies ready military troop-training batches for one player.
	 *
	 * @return array{done:int,failed:int,waiting:int}
	 */
	private function processMilitaryQueue(int $uid, bool $dryRun): array
	{
		$done = 0;
		$failed = 0;
		$waiting = 0;

		$catQ = $this->query("SELECT troop_id, troop_name, troop_type, class_name, power_stat, morale_stat, logistics_stat, mobility_stat
			FROM military_troop_catalog");
		$troopById = [];
		if ($catQ) {
			while ($t = $catQ->fetch_object()) {
				$troopById[(int)$t->troop_id] = $t;
			}
			$catQ->free();
		}

		$queueQ = $this->query("SELECT queue_id, troop_id, quantity, eta_seconds, UNIX_TIMESTAMP(created_at) AS created_ts
			FROM military_troop_queue
			WHERE uid=" . $uid . " AND status='queued'
			ORDER BY priority_order ASC, queue_id ASC LIMIT 25");
		if (!$queueQ) {
			return ['done' => 0, 'failed' => 0, 'waiting' => 0];
		}
		while ($item = $queueQ->fetch_object()) {
			$elapsed = max(0, time() - (int)$item->created_ts);
			if ($elapsed < (int)$item->eta_seconds) {
				$waiting++;
				continue;
			}
			$meta = $troopById[(int)$item->troop_id] ?? null;
			if ($meta === null) {
				if (!$dryRun) {
					$this->query("UPDATE military_troop_queue SET status='failed', completed_at=NOW() WHERE queue_id=" . (int)$item->queue_id . " AND uid=" . $uid . " LIMIT 1");
				}
				$failed++;
				continue;
			}
			$apply = $this->militaryRecruitApply($uid, $meta, (int)$item->quantity);
			if ($apply === true) {
				if (!$dryRun) {
					$this->query("UPDATE military_troop_queue SET status='done', completed_at=NOW() WHERE queue_id=" . (int)$item->queue_id . " AND uid=" . $uid . " LIMIT 1");
				}
				$done++;
			} else {
				if (!$dryRun) {
					$this->query("UPDATE military_troop_queue SET status='failed', completed_at=NOW() WHERE queue_id=" . (int)$item->queue_id . " AND uid=" . $uid . " LIMIT 1");
				}
				$failed++;
			}
		}
		$queueQ->free();
		return ['done' => $done, 'failed' => $failed, 'waiting' => $waiting];
	}

	/**
	 * Applies ready RTS operations turn-queue cycles for one player.
	 *
	 * @return array{done:int,failed:int,waiting:int}
	 */
	private function processOperationsQueue(int $uid, bool $dryRun): array
	{
		$done = 0;
		$failed = 0;
		$waiting = 0;
		$catalog = self::operationsCatalog();

		$queueQ = $this->query("SELECT queue_id, operation_code, eta_seconds, UNIX_TIMESTAMP(created_at) AS created_ts
			FROM operations_turn_queue
			WHERE uid=" . $uid . " AND status='queued'
			ORDER BY priority_order ASC, queue_id ASC LIMIT 10");
		if (!$queueQ) {
			return ['done' => 0, 'failed' => 0, 'waiting' => 0];
		}
		while ($item = $queueQ->fetch_object()) {
			$elapsed = max(0, time() - (int)$item->created_ts);
			if ($elapsed < (int)$item->eta_seconds) {
				$waiting++;
				continue;
			}
			$code = (string)$item->operation_code;
			if (!isset($catalog[$code])) {
				if (!$dryRun) {
					$this->query("UPDATE operations_turn_queue SET status='failed', completed_at=NOW() WHERE queue_id=" . (int)$item->queue_id . " AND uid=" . $uid . " LIMIT 1");
				}
				$failed++;
				continue;
			}
			$apply = $this->operationsApplyCycleAction($uid, $catalog[$code]);
			if ($apply === true) {
				if (!$dryRun) {
					$this->query("UPDATE operations_turn_queue SET status='done', completed_at=NOW() WHERE queue_id=" . (int)$item->queue_id . " AND uid=" . $uid . " LIMIT 1");
				}
				$done++;
			} else {
				if (!$dryRun) {
					$this->query("UPDATE operations_turn_queue SET status='failed', completed_at=NOW() WHERE queue_id=" . (int)$item->queue_id . " AND uid=" . $uid . " LIMIT 1");
				}
				$failed++;
			}
		}
		$queueQ->free();
		return ['done' => $done, 'failed' => $failed, 'waiting' => $waiting];
	}

	/**
	 * Colony power grid catch-up tick plus ready node-upgrade actions for one
	 * player. Tables are created idempotently if the player never visited the
	 * grid page (missing from older installs).
	 *
	 * @return array{ticks:int,done:int,failed:int}
	 */
	private function processPowerGrid(int $uid, bool $dryRun): array
	{
		$ticks = 0;
		$done = 0;
		$failed = 0;

		$stateQ = $this->query("SELECT grid_level,stability_index,storage_capacity,stored_energy,generation_boost,load_mode,blackout_risk,UNIX_TIMESTAMP(last_tick_at) AS last_tick_ts
			FROM power_grid_state WHERE uid=" . $uid . " LIMIT 1");
		$state = $stateQ ? $stateQ->fetch_object() : null;
		if ($state) {
			$nodeQ = $this->query("SELECT node_id,node_name,node_type,level,output_mw,load_mw,integrity,status FROM power_grid_nodes WHERE uid=" . $uid . "");
			$generation = 0;
			$load = 0;
			$boost = (int)$state->generation_boost;
			$techLevelQ = $this->query("SELECT level FROM stargate_tech_levels WHERE uid=" . $uid . " AND tech_key='arkknit_endfield_power' LIMIT 1");
			$arkknitLevel = 0;
			if ($techLevelQ && $techLevelQ->num_rows > 0) {
				$arkknitLevel = (int)($techLevelQ->fetch_object()->level ?? 0);
			}
			$endfield = $arkknitLevel > 0 ? formalArknitEndfieldPower($arkknitLevel, 100, (int)$state->stability_index, (int)$state->blackout_risk) : null;
			if ($nodeQ) {
				while ($node = $nodeQ->fetch_object()) {
					if ((string)$node->status !== 'active') {
						continue;
					}
					$generation += formalPowerNodeOutput((float)$node->output_mw, (int)$node->level, (int)$node->integrity, $boost, (string)$node->node_type);
					$load += formalPowerNodeLoad((float)$node->load_mw, (int)$node->integrity, (string)$state->load_mode);
				}
				$nodeQ->free();
			}

			$boostedGen = $generation;
			if ($endfield) {
				$boostedGen = (int)round($boostedGen * (1 + ($endfield['generation'] / 100.0)));
			}
			$net = $boostedGen - $load;

			$lastTickTs = (int)($state->last_tick_ts ?? 0);
			$nowTs = time();
			$intervalSec = self::POWER_GRID_TICK_SECONDS;
			$ticks = 0;
			if ($lastTickTs > 0) {
				$ticks = max(0, min(self::POWER_GRID_MAX_TICKS, (int)floor(($nowTs - $lastTickTs) / $intervalSec)));
			}

			if ($ticks > 0) {
				$storedEnergy = (int)$state->stored_energy;
				$storageCap = max(10000, (int)$state->storage_capacity);
				$stability = (int)$state->stability_index;
				$risk = (int)$state->blackout_risk;

				$delta = formalPowerGridDelta($net, $ticks, 8.0);
				$stateUpdate = formalPowerGridState($stability, $risk, $storedEnergy, $storageCap, $ticks, $delta);
				$storedEnergy = (int)$stateUpdate['stored_energy'];
				$stability = (int)$stateUpdate['stability_index'];
				$risk = (int)$stateUpdate['blackout_risk'];

				if (!$dryRun) {
					$this->query("UPDATE power_grid_state SET
						stored_energy=" . $storedEnergy . ",
						stability_index=" . $stability . ",
						blackout_risk=" . $risk . ",
						last_tick_at=FROM_UNIXTIME(" . $nowTs . ")
						WHERE uid=" . $uid . " LIMIT 1");
				}
			}

			$queueQ = $this->query("SELECT queue_id, action_code, target_node_id, eta_seconds, UNIX_TIMESTAMP(created_at) AS created_ts
				FROM power_grid_queue
				WHERE uid=" . $uid . " AND status='queued'
				ORDER BY priority_order ASC, queue_id ASC LIMIT 10");
			if ($queueQ) {
				while ($item = $queueQ->fetch_object()) {
					$elapsed = max(0, time() - (int)$item->created_ts);
					if ($elapsed < (int)$item->eta_seconds) {
						continue;
					}
					if ((string)$item->action_code === 'upgrade_node') {
						$ok = $this->powerGridUpgradeNode($uid, (int)$item->target_node_id);
						if ($ok === true) {
							if (!$dryRun) {
								$this->query("UPDATE power_grid_queue SET status='done', completed_at=NOW() WHERE queue_id=" . (int)$item->queue_id . " AND uid=" . $uid . " LIMIT 1");
							}
							$done++;
						} else {
							if (!$dryRun) {
								$this->query("UPDATE power_grid_queue SET status='failed', completed_at=NOW() WHERE queue_id=" . (int)$item->queue_id . " AND uid=" . $uid . " LIMIT 1");
							}
							$failed++;
						}
					} else {
						if (!$dryRun) {
							$this->query("UPDATE power_grid_queue SET status='failed', completed_at=NOW() WHERE queue_id=" . (int)$item->queue_id . " AND uid=" . $uid . " LIMIT 1");
						}
						$failed++;
					}
				}
				$queueQ->free();
			}
		}

		return ['ticks' => $ticks, 'done' => $done, 'failed' => $failed];
	}

	/**
	 * Expires stale market listings (older than 7 days, still active).
	 */
	private function sweepMarket(bool $dryRun): int
	{
		$expired = 0;
		$cutoff = time() - (7 * 24 * 60 * 60);
		$q = $this->query("SELECT lid FROM market_listings WHERE active=1 AND created < " . (int)$cutoff);
		if (!$q) {
			return 0;
		}
		while ($row = $q->fetch_object()) {
			if (!$dryRun) {
				$this->query("UPDATE market_listings SET active=0 WHERE lid=" . (int)$row->lid . " LIMIT 1");
			}
			$expired++;
		}
		$q->free_result();
		return $expired;
	}

	/**
	 * Purges accounts that have not logged in for more than the configured
	 * number of days, removing all related game rows (mirrors Game::delOld()).
	 */
	private function purgeInactivePlayers(bool $dryRun): int
	{
		$purgeDays = max(1, (int)$this->getAppSetting('game_tick.purge_days', (string)self::PURGE_DAYS));
		$cutoff = time() - ($purgeDays * 24 * 60 * 60);
		$cutoffStr = date('F jS', $cutoff);

		$purged = 0;
		$q = $this->query("SELECT users.uid FROM users WHERE users.lastLogin='" . $this->db_link->real_escape_string($cutoffStr) . "'");
		if (!$q) {
			return 0;
		}
		$tables = ['bank', 'planets', 'power', 'rank', 'technology', 'units', 'userdata', 'weapons', 'player_resources', 'trade_routes', 'fleet_missions', 'hyperspace_transits', 'market_listings'];
		while ($row = $q->fetch_object()) {
			$uid = (int)$row->uid;
			if ($uid <= 0) {
				continue;
			}
			if (!$dryRun) {
				foreach ($tables as $table) {
					$this->query("DELETE FROM `" . $table . "` WHERE `uid`=" . $uid);
				}
				$this->query("DELETE FROM users WHERE uid=" . $uid);
			}
			$purged++;
		}
		$q->free_result();
		return $purged;
	}

	/**
	 * Applies the costs/gains of one military troop batch, mirroring the page
	 * handler. Returns true on success, false on insufficient resources.
	 */
	private function militaryRecruitApply(int $uid, object $troopMeta, int $qty): bool
	{
		$qty = max(1, min(500, $qty));
		$turnQ = $this->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
		$turns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
		$resQ = $this->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
		$res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'food' => 0, 'water' => 0, 'population' => 0];
		$unitQ = $this->query("SELECT untrained,attack,defense,covert,anticovert FROM units WHERE uid=" . $uid . " LIMIT 1");
		$unitsObj = $unitQ ? $unitQ->fetch_object() : (object)['untrained' => 0, 'attack' => 0, 'defense' => 0, 'covert' => 0, 'anticovert' => 0];
		$bankQ = $this->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
		$bankObj = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];

		$meta = (array)$troopMeta;
		$cost = self::militaryRecruitCosts($meta, $qty);
		if ($turns < (int)$cost['turns']) {
			return false;
		}
		if ((int)$unitsObj->untrained < (int)$cost['units']) {
			return false;
		}
		if ((int)$bankObj->onHand < (int)$cost['naq']) {
			return false;
		}
		if ((int)$res->food < (int)$cost['food'] || (int)$res->water < (int)$cost['water'] || (int)$res->deuterium < (int)$cost['deuterium']) {
			return false;
		}

		$unitField = self::militaryTroopRoleField($meta);
		$xpGain = max(2, (int)ceil($qty / 10));
		$readinessGain = max(1, (int)ceil($qty / 80));

		$this->query("UPDATE bank SET onHand=onHand-" . (int)$cost['naq'] . " WHERE uid=" . $uid . " LIMIT 1");
		$this->query("UPDATE player_resources SET food=food-" . (int)$cost['food'] . ", water=water-" . (int)$cost['water'] . ", deuterium=deuterium-" . (int)$cost['deuterium'] . " WHERE uid=" . $uid . " LIMIT 1");
		$this->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . (int)$cost['turns'] . ") WHERE uid=" . $uid . " LIMIT 1");
		$this->query("UPDATE units SET untrained=untrained-" . (int)$cost['units'] . ", " . $unitField . "=" . $unitField . "+" . $qty . " WHERE uid=" . $uid . " LIMIT 1");
		$this->query("UPDATE military_command_state SET drill_xp=drill_xp+" . $xpGain . ", readiness_index=LEAST(100, readiness_index+" . $readinessGain . ") WHERE uid=" . $uid . " LIMIT 1");

		return true;
	}

	/**
	 * Executes one RTS operations cycle for a player, deducting costs and
	 * applying unit/state deltas (mirrors modules/pages.php). Returns true on
	 * success, false on insufficient resources.
	 */
	private function operationsApplyCycleAction(int $uid, array $cfg): bool
	{
		$turnQ = $this->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
		$turns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
		$resQ = $this->query("SELECT metal,crystal,deuterium,food,water,population FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
		$res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0, 'food' => 0, 'water' => 0, 'population' => 0];
		$unitQ = $this->query("SELECT untrained,attack,defense,covert,anticovert FROM units WHERE uid=" . $uid . " LIMIT 1");
		$unitsObj = $unitQ ? $unitQ->fetch_object() : (object)['untrained' => 0, 'attack' => 0, 'defense' => 0, 'covert' => 0, 'anticovert' => 0];
		$bankQ = $this->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
		$bankObj = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];

		if ($turns < (int)$cfg['turn_cost']) {
			return false;
		}
		if ((int)$bankObj->onHand < (int)$cfg['naq_cost']) {
			return false;
		}
		if ((int)$res->metal < (int)$cfg['metal_cost'] || (int)$res->crystal < (int)$cfg['crystal_cost'] || (int)$res->deuterium < (int)$cfg['deut_cost'] || (int)$res->food < (int)$cfg['food_cost'] || (int)$res->water < (int)$cfg['water_cost']) {
			return false;
		}
		if ((int)$unitsObj->untrained < (int)$cfg['need_untrained']) {
			return false;
		}

		$this->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . (int)$cfg['turn_cost'] . ") WHERE uid=" . $uid . " LIMIT 1");
		$this->query("UPDATE bank SET onHand=onHand-" . (int)$cfg['naq_cost'] . " WHERE uid=" . $uid . " LIMIT 1");
		$this->query("UPDATE player_resources SET
			metal=metal-" . (int)$cfg['metal_cost'] . ",
			crystal=crystal-" . (int)$cfg['crystal_cost'] . ",
			deuterium=deuterium-" . (int)$cfg['deut_cost'] . ",
			food=food-" . (int)$cfg['food_cost'] . ",
			water=water-" . (int)$cfg['water_cost'] . "
			WHERE uid=" . $uid . " LIMIT 1");
		$this->query("UPDATE units SET
			untrained=GREATEST(0,untrained+" . (int)$cfg['untrained_delta'] . "),
			attack=GREATEST(0,attack+" . (int)$cfg['attack_delta'] . "),
			defense=GREATEST(0,defense+" . (int)$cfg['defense_delta'] . "),
			covert=GREATEST(0,covert+" . (int)$cfg['covert_delta'] . "),
			anticovert=GREATEST(0,anticovert+" . (int)$cfg['anticovert_delta'] . ")
			WHERE uid=" . $uid . " LIMIT 1");
		$this->query("UPDATE operations_rts_state SET
			command_xp=command_xp+" . (int)$cfg['xp_gain'] . ",
			cycle_index=cycle_index+1,
			frontline_pressure=LEAST(100,GREATEST(0,frontline_pressure+" . (int)$cfg['pressure_delta'] . ")),
			reserve_integrity=LEAST(100,GREATEST(0,reserve_integrity+" . (int)$cfg['reserve_delta'] . ")),
			morale_index=LEAST(100,GREATEST(0,morale_index+" . (int)$cfg['morale_delta'] . ")),
			last_cycle_at=NOW()
			WHERE uid=" . $uid . " LIMIT 1");

		return true;
	}

	/**
	 * Upgrades a power grid node for a player (mirrors modules/pages.php).
	 * Returns true on success, false on insufficient resources.
	 */
	private function powerGridUpgradeNode(int $uid, int $nodeId): bool
	{
		if ($nodeId <= 0) {
			return false;
		}
		$nodeQ = $this->query("SELECT node_id,node_name,node_type,level,output_mw,load_mw,integrity,status FROM power_grid_nodes WHERE node_id=" . $nodeId . " AND uid=" . $uid . " LIMIT 1");
		$node = $nodeQ ? $nodeQ->fetch_object() : null;
		if (!$node) {
			return false;
		}

		$level = (int)$node->level;
		$turnCost = max(1, formalTimeValue(1, $level, 1.12));
		$naqCost = formalCostValue(18000, $level, 1.35, 0.08);
		$metalCost = formalCostValue(12000, $level, 1.30, 0.09);
		$crystalCost = formalCostValue(7000, $level, 1.28, 0.08);
		$deutCost = formalCostValue(3200, $level, 1.26, 0.07);

		$turnQ = $this->query("SELECT actionTurns FROM userdata WHERE uid=" . $uid . " LIMIT 1");
		$turns = $turnQ ? (int)($turnQ->fetch_object()->actionTurns ?? 0) : 0;
		$resQ = $this->query("SELECT metal,crystal,deuterium FROM player_resources WHERE uid=" . $uid . " LIMIT 1");
		$res = $resQ ? $resQ->fetch_object() : (object)['metal' => 0, 'crystal' => 0, 'deuterium' => 0];
		$bankQ = $this->query("SELECT onHand FROM bank WHERE uid=" . $uid . " LIMIT 1");
		$bankObj = $bankQ ? $bankQ->fetch_object() : (object)['onHand' => 0];

		if ($turns < $turnCost) {
			return false;
		}
		if ((int)$bankObj->onHand < $naqCost) {
			return false;
		}
		if ((int)$res->metal < $metalCost || (int)$res->crystal < $crystalCost || (int)$res->deuterium < $deutCost) {
			return false;
		}

		$this->query("UPDATE userdata SET actionTurns=GREATEST(0,actionTurns-" . $turnCost . ") WHERE uid=" . $uid . " LIMIT 1");
		$this->query("UPDATE bank SET onHand=onHand-" . $naqCost . " WHERE uid=" . $uid . " LIMIT 1");
		$this->query("UPDATE player_resources SET metal=metal-" . $metalCost . ", crystal=crystal-" . $crystalCost . ", deuterium=deuterium-" . $deutCost . " WHERE uid=" . $uid . " LIMIT 1");

		$newLevel = $level + 1;
		$outputInc = ((string)$node->node_type === 'generator') ? formalPowerValue(26, $newLevel, 1.12) : (((string)$node->node_type === 'relay') ? formalPowerValue(8, $newLevel, 1.10) : 0);
		$loadInc = ((string)$node->node_type === 'storage') ? formalPowerValue(8, $newLevel, 1.06) : formalPowerValue(5, $newLevel, 1.05);

		$this->query("UPDATE power_grid_nodes SET
			level=" . $newLevel . ",
			output_mw=output_mw+" . $outputInc . ",
			load_mw=load_mw+" . $loadInc . ",
			integrity=LEAST(100,integrity+3)
			WHERE node_id=" . $nodeId . " AND uid=" . $uid . " LIMIT 1");
		$this->query("UPDATE power_grid_state SET
			grid_level=LEAST(30,grid_level+1),
			storage_capacity=storage_capacity+" . (800 + ($newLevel * 90)) . ",
			stability_index=LEAST(100,stability_index+2),
			blackout_risk=GREATEST(0,blackout_risk-1)
			WHERE uid=" . $uid . " LIMIT 1");

		return true;
	}

	/**
	 * RTS operations catalog (mirrors modules/pages.php $operationsRtsCatalog).
	 */
	private static function operationsCatalog(): array
	{
		return [
			'recon' => ['label' => 'Deep Recon Sweep', 'turn_cost' => 2, 'naq_cost' => 0, 'metal_cost' => 0, 'crystal_cost' => 0, 'deut_cost' => 2200, 'food_cost' => 900, 'water_cost' => 700, 'need_untrained' => 0, 'untrained_delta' => 0, 'attack_delta' => 0, 'defense_delta' => 0, 'covert_delta' => 35, 'anticovert_delta' => 20, 'xp_gain' => 8, 'pressure_delta' => 2, 'reserve_delta' => -1, 'morale_delta' => 1, 'eta_seconds' => 210],
			'assault' => ['label' => 'Shock Assault Wave', 'turn_cost' => 4, 'naq_cost' => 90000, 'metal_cost' => 0, 'crystal_cost' => 0, 'deut_cost' => 7200, 'food_cost' => 4200, 'water_cost' => 0, 'need_untrained' => 60, 'untrained_delta' => -60, 'attack_delta' => 120, 'defense_delta' => 45, 'covert_delta' => 0, 'anticovert_delta' => 0, 'xp_gain' => 15, 'pressure_delta' => 6, 'reserve_delta' => -3, 'morale_delta' => 2, 'eta_seconds' => 300],
			'fortify' => ['label' => 'Defense Fortification Cycle', 'turn_cost' => 3, 'naq_cost' => 0, 'metal_cost' => 22000, 'crystal_cost' => 14000, 'deut_cost' => 0, 'food_cost' => 0, 'water_cost' => 0, 'need_untrained' => 0, 'untrained_delta' => 0, 'attack_delta' => 0, 'defense_delta' => 140, 'covert_delta' => 0, 'anticovert_delta' => 60, 'xp_gain' => 12, 'pressure_delta' => -2, 'reserve_delta' => 5, 'morale_delta' => 1, 'eta_seconds' => 260],
			'logistics' => ['label' => 'Reserve Logistics Surge', 'turn_cost' => 2, 'naq_cost' => 65000, 'metal_cost' => 0, 'crystal_cost' => 0, 'deut_cost' => 0, 'food_cost' => 3000, 'water_cost' => 3000, 'need_untrained' => 0, 'untrained_delta' => 260, 'attack_delta' => 0, 'defense_delta' => 0, 'covert_delta' => 0, 'anticovert_delta' => 0, 'xp_gain' => 9, 'pressure_delta' => -1, 'reserve_delta' => 4, 'morale_delta' => 2, 'eta_seconds' => 240],
			'sabotage' => ['label' => 'Covert Sabotage Grid', 'turn_cost' => 3, 'naq_cost' => 50000, 'metal_cost' => 0, 'crystal_cost' => 0, 'deut_cost' => 4600, 'food_cost' => 0, 'water_cost' => 0, 'need_untrained' => 0, 'untrained_delta' => 0, 'attack_delta' => 0, 'defense_delta' => 0, 'covert_delta' => 90, 'anticovert_delta' => 0, 'xp_gain' => 13, 'pressure_delta' => 4, 'reserve_delta' => -2, 'morale_delta' => 0, 'eta_seconds' => 280],
		];
	}

	/**
	 * Stargate technology production bonuses for a player.
	 */
	private function stargateBonus(int $uid): array
	{
		$has = $this->query("SHOW TABLES LIKE 'stargate_tech_levels'");
		if (!$has || $has->num_rows === 0) {
			return self::stargateCoefficients([]);
		}

		$tech = [];
		$res = $this->query("SELECT tech_key, level FROM stargate_tech_levels WHERE uid=" . $uid);
		if ($res) {
			while ($row = $res->fetch_object()) {
				$tech[(string)$row->tech_key] = (int)$row->level;
			}
			$res->free();
		}

		return self::stargateCoefficients($tech);
	}

	/**
	 * Pure stargate tech coefficients (mirrors the engine's stargateBonus()).
	 *
	 * @return array{production:float,energy:float,deuterium:float,population:float}
	 */
	public static function stargateCoefficients(array $levels): array
	{
		$bonus = [
			'production' => 1.0,
			'energy' => 1.0,
			'deuterium' => 1.0,
			'population' => 1.0,
		];

		$bonus['production'] +=
			(($levels['lantian_knowledge_matrix'] ?? 0) * 0.008) +
			(($levels['time_dilation_calculus'] ?? 0) * 0.007) +
			(($levels['transit_manifest_ai'] ?? 0) * 0.005);
		$bonus['energy'] +=
			(($levels['zero_point_theory'] ?? 0) * 0.020) +
			(($levels['zpm_focusing'] ?? 0) * 0.018) +
			(($levels['reactor_overdrive'] ?? 0) * 0.015) +
			(($levels['grid_redundancy'] ?? 0) * 0.010);
		$bonus['deuterium'] +=
			(($levels['wormhole_topology'] ?? 0) * 0.010) +
			(($levels['destiny_navigation'] ?? 0) * 0.008) +
			(($levels['phase_inverters'] ?? 0) * 0.007);
		$bonus['population'] +=
			(($levels['ascension_interface'] ?? 0) * 0.005) +
			(($levels['fortress_polarization'] ?? 0) * 0.004);

		return $bonus;
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
				$uidVal = (int)$row->uid;
				$stmt->bind_param("ii", $pos, $uidVal);
				$stmt->execute();
			}
		}
		$q->free_result();
	}

	/**
	 * Ensures every table the tick engine touches exists (idempotent), so the
	 * engine is safe on fresh installs and legacy databases alike.
	 */
	private function ensureTickTables(): void
	{
		$this->getAppSetting('game_tick.last_status', '');

		$this->query("CREATE TABLE IF NOT EXISTS player_resources (
			uid INT NOT NULL PRIMARY KEY,
			metal BIGINT NOT NULL DEFAULT 80000,
			crystal BIGINT NOT NULL DEFAULT 60000,
			deuterium BIGINT NOT NULL DEFAULT 45000,
			food BIGINT NOT NULL DEFAULT 55000,
			water BIGINT NOT NULL DEFAULT 55000,
			population BIGINT NOT NULL DEFAULT 120000,
			energy BIGINT NOT NULL DEFAULT 50000,
			last_tick_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		)");
		$this->query("ALTER TABLE player_resources ADD COLUMN IF NOT EXISTS energy BIGINT NOT NULL DEFAULT 50000");

		$this->query("CREATE TABLE IF NOT EXISTS resource_structures (
			uid INT NOT NULL PRIMARY KEY,
			metal_mine INT NOT NULL DEFAULT 1,
			crystal_lab INT NOT NULL DEFAULT 1,
			deuterium_refinery INT NOT NULL DEFAULT 1,
			hydroponics INT NOT NULL DEFAULT 1,
			water_plant INT NOT NULL DEFAULT 1,
			habitat_dome INT NOT NULL DEFAULT 1,
			energy_reactor INT NOT NULL DEFAULT 1,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		)");
		$this->query("ALTER TABLE resource_structures ADD COLUMN IF NOT EXISTS energy_reactor INT NOT NULL DEFAULT 1");

		$this->query("CREATE TABLE IF NOT EXISTS hyperspace_systems (
			uid INT NOT NULL PRIMARY KEY,
			jump_gate_level INT NOT NULL DEFAULT 0,
			stargate_level INT NOT NULL DEFAULT 0,
			hyperspace_core_level INT NOT NULL DEFAULT 0,
			lane_stability INT NOT NULL DEFAULT 0,
			range_bonus INT NOT NULL DEFAULT 0,
			cooldown_reduction INT NOT NULL DEFAULT 0,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		)");

		$this->query("CREATE TABLE IF NOT EXISTS hyperspace_transits (
			transit_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			uid INT NOT NULL,
			route_id INT NOT NULL,
			transit_type VARCHAR(20) NOT NULL,
			fleet_tonnage INT NOT NULL DEFAULT 0,
			depart_at DATETIME NOT NULL,
			eta_at DATETIME NOT NULL,
			return_at DATETIME NOT NULL,
			status VARCHAR(16) NOT NULL DEFAULT 'enroute',
			reward_metal INT NOT NULL DEFAULT 0,
			reward_crystal INT NOT NULL DEFAULT 0,
			reward_deuterium INT NOT NULL DEFAULT 0,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_uid_status (uid, status),
			INDEX idx_uid_eta (uid, eta_at)
		)");

		$this->query("CREATE TABLE IF NOT EXISTS fleet_missions (
			mission_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			uid INT NOT NULL,
			mission_type VARCHAR(24) NOT NULL,
			ship_type VARCHAR(32) NOT NULL,
			ship_count INT NOT NULL DEFAULT 0,
			target_uid INT NOT NULL DEFAULT 0,
			duration_minutes INT NOT NULL DEFAULT 15,
			eta_at DATETIME NOT NULL,
			return_at DATETIME NOT NULL,
			status VARCHAR(16) NOT NULL DEFAULT 'enroute',
			reward_naquadah INT NOT NULL DEFAULT 0,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_uid_status (uid, status)
		)");

		$this->query("CREATE TABLE IF NOT EXISTS trade_routes (
			route_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			from_uid INT NOT NULL DEFAULT 0,
			to_uid INT NOT NULL DEFAULT 0,
			amount BIGINT NOT NULL DEFAULT 0,
			rate BIGINT NOT NULL DEFAULT 0,
			turns INT NOT NULL DEFAULT 0,
			total INT NOT NULL DEFAULT 0,
			status VARCHAR(16) NOT NULL DEFAULT 'active',
			created VARCHAR(32) NOT NULL DEFAULT '',
			INDEX idx_from (from_uid),
			INDEX idx_to (to_uid)
		)");

		$this->query("CREATE TABLE IF NOT EXISTS military_troop_queue (
			queue_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			uid INT NOT NULL,
			troop_id INT NOT NULL,
			quantity INT NOT NULL DEFAULT 1,
			priority_order INT NOT NULL DEFAULT 0,
			eta_seconds INT NOT NULL DEFAULT 300,
			status VARCHAR(20) NOT NULL DEFAULT 'queued',
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			completed_at TIMESTAMP NULL DEFAULT NULL,
			INDEX idx_uid_status (uid, status)
		)");

		$this->query("CREATE TABLE IF NOT EXISTS operations_turn_queue (
			queue_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			uid INT NOT NULL,
			operation_code VARCHAR(30) NOT NULL,
			operation_label VARCHAR(80) NOT NULL DEFAULT '',
			turn_cost INT NOT NULL DEFAULT 1,
			eta_seconds INT NOT NULL DEFAULT 180,
			reward_focus VARCHAR(30) NOT NULL DEFAULT 'mixed',
			priority_order INT NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'queued',
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			completed_at TIMESTAMP NULL DEFAULT NULL,
			INDEX idx_uid_status (uid, status)
		)");

		$this->query("CREATE TABLE IF NOT EXISTS operations_rts_state (
			uid INT NOT NULL PRIMARY KEY,
			doctrine VARCHAR(24) NOT NULL DEFAULT 'balanced',
			tempo_mode VARCHAR(24) NOT NULL DEFAULT 'standard',
			theater_level INT NOT NULL DEFAULT 1,
			command_xp INT NOT NULL DEFAULT 0,
			cycle_index INT NOT NULL DEFAULT 0,
			frontline_pressure INT NOT NULL DEFAULT 45,
			reserve_integrity INT NOT NULL DEFAULT 60,
			morale_index INT NOT NULL DEFAULT 55,
			last_cycle_at TIMESTAMP NULL DEFAULT NULL,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		)");

		$this->query("CREATE TABLE IF NOT EXISTS market_listings (
			lid INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			uid INT NOT NULL,
			resource VARCHAR(32) NOT NULL,
			amount INT NOT NULL DEFAULT 0,
			price_per FLOAT NOT NULL DEFAULT 0,
			created INT NOT NULL DEFAULT 0,
			active TINYINT(1) DEFAULT 1,
			INDEX idx_active_created (active, created)
		)");

		$this->query("CREATE TABLE IF NOT EXISTS power_grid_state (
			uid INT NOT NULL PRIMARY KEY,
			grid_level INT NOT NULL DEFAULT 1,
			stability_index INT NOT NULL DEFAULT 50,
			storage_capacity INT NOT NULL DEFAULT 20000,
			stored_energy INT NOT NULL DEFAULT 5000,
			generation_boost INT NOT NULL DEFAULT 0,
			load_mode VARCHAR(16) NOT NULL DEFAULT 'balanced',
			blackout_risk INT NOT NULL DEFAULT 10,
			last_tick_at TIMESTAMP NULL DEFAULT NULL,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		)");

		$this->query("CREATE TABLE IF NOT EXISTS power_grid_nodes (
			node_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			uid INT NOT NULL,
			node_name VARCHAR(80) NOT NULL DEFAULT '',
			node_type VARCHAR(16) NOT NULL DEFAULT 'generator',
			level INT NOT NULL DEFAULT 1,
			output_mw INT NOT NULL DEFAULT 0,
			load_mw INT NOT NULL DEFAULT 0,
			integrity INT NOT NULL DEFAULT 100,
			status VARCHAR(16) NOT NULL DEFAULT 'active',
			INDEX idx_uid (uid)
		)");

		$this->query("CREATE TABLE IF NOT EXISTS power_grid_queue (
			queue_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			uid INT NOT NULL,
			action_code VARCHAR(24) NOT NULL DEFAULT 'upgrade_node',
			target_node_id INT NOT NULL DEFAULT 0,
			eta_seconds INT NOT NULL DEFAULT 300,
			priority_order INT NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'queued',
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			completed_at TIMESTAMP NULL DEFAULT NULL,
			INDEX idx_uid_status (uid, status)
		)");
	}
}
