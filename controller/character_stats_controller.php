<?php
/**
 * Character stats AJAX controller (#364)
 *
 * Exposes the Battle.net-backed sections of the player detail page (stats,
 * professions, Mythic+ profile, PvP summary) as a JSON endpoint, fetched
 * async by the page's JS after initial render — these are live API calls
 * (1h-cached at the HTTP layer, but uncached on first view per character),
 * and previously ran synchronously inside on_player_detail_display(),
 * blocking every character page view on up to 4 live Battle.net requests.
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\controller;

use avathar\bbguildwow\game\wow_api;
use phpbb\db\driver\driver_interface;
use Symfony\Component\HttpFoundation\JsonResponse;

class character_stats_controller
{
	/** @var wow_api */
	protected $wow_api;

	/** @var driver_interface */
	protected $db;

	/** @var string */
	protected $players_table;

	/** @var string */
	protected $guild_table;

	public function __construct(
		wow_api $wow_api,
		driver_interface $db,
		string $players_table,
		string $guild_table
	)
	{
		$this->wow_api = $wow_api;
		$this->db = $db;
		$this->players_table = $players_table;
		$this->guild_table = $guild_table;
	}

	/**
	 * @param int $player_id
	 * @return JsonResponse
	 */
	public function stats($player_id)
	{
		$player_id = (int) $player_id;

		$sql = 'SELECT p.game_id, p.player_name, p.player_realm, p.player_region, g.game_edition
			FROM ' . $this->players_table . ' p
			LEFT JOIN ' . $this->guild_table . ' g ON g.id = p.player_guild_id
			WHERE p.player_id = ' . $player_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row || $row['game_id'] !== 'wow')
		{
			return new JsonResponse(array('error' => 'not_found'), 404);
		}

		$name = $row['player_name'];
		$realm = $row['player_realm'];
		$region = $row['player_region'];
		$edition = !empty($row['game_edition']) ? $row['game_edition'] : 'retail';
		$is_retail = ($edition === 'retail');

		return new JsonResponse(array(
			'stats'       => $this->build_stats($name, $realm, $region, $edition),
			'professions' => $this->build_professions($name, $realm, $region, $edition),
			'mplus'       => $is_retail ? $this->build_mplus($name, $realm, $region, $edition) : null,
			'pvp'         => $is_retail ? $this->build_pvp($name, $realm, $region, $edition) : null,
		));
	}

	protected function build_stats(string $name, string $realm, string $region, string $edition): array
	{
		$raw_stats = $this->wow_api->fetch_character_stats($name, $realm, $region, $edition);
		if (!$raw_stats)
		{
			return array();
		}

		$out = array();

		$stat_keys = array('strength', 'agility', 'intellect', 'stamina', 'armor', 'versatility');
		foreach ($stat_keys as $key)
		{
			if (isset($raw_stats[$key]))
			{
				$value = is_array($raw_stats[$key]) ? ($raw_stats[$key]['effective'] ?? $raw_stats[$key]['value'] ?? 0) : $raw_stats[$key];
				$out[] = array('name' => str_replace('_', ' ', ucfirst($key)), 'key' => $key, 'value' => (int) $value, 'pct' => false);
			}
		}

		$rating_keys = array(
			'melee_crit' => 'crit',
			'melee_haste' => 'haste',
			'mastery' => 'mastery',
			'versatility_damage_done_bonus' => 'versatility',
		);
		foreach ($rating_keys as $api_key => $display_key)
		{
			if (isset($raw_stats[$api_key]))
			{
				$val = is_array($raw_stats[$api_key]) ? ($raw_stats[$api_key]['value'] ?? 0) : $raw_stats[$api_key];
				$out[] = array('name' => str_replace('_', ' ', ucfirst($display_key)), 'key' => $display_key . '_pct', 'value' => round((float) $val, 2), 'pct' => true);
			}
		}

		return $out;
	}

	protected function build_professions(string $name, string $realm, string $region, string $edition): array
	{
		$raw_prof = $this->wow_api->fetch_character_professions($name, $realm, $region, $edition);
		if (!$raw_prof)
		{
			return array();
		}

		$out = array();

		foreach (array('primaries', 'secondaries') as $group)
		{
			if (!isset($raw_prof[$group]))
			{
				continue;
			}

			foreach ($raw_prof[$group] as $prof)
			{
				$skill_points = 0;
				$max_points = 0;
				if (!empty($prof['tiers']))
				{
					$last_tier = end($prof['tiers']);
					$skill_points = $last_tier['skill_points'] ?? 0;
					$max_points = $last_tier['max_skill_points'] ?? 0;
				}

				if ($group === 'secondaries' && $skill_points <= 0)
				{
					continue;
				}

				$out[] = array('name' => $prof['profession']['name'] ?? '', 'skill' => $skill_points, 'max' => $max_points);
			}
		}

		return $out;
	}

	protected function build_mplus(string $name, string $realm, string $region, string $edition): array
	{
		$raw_mplus = $this->wow_api->fetch_mythic_keystone_profile($name, $realm, $region, $edition);
		$rating = 0;
		$color = '';
		$runs = array();

		if ($raw_mplus)
		{
			if (isset($raw_mplus['current_mythic_rating']))
			{
				$r = $raw_mplus['current_mythic_rating'];
				$rating = (int) round((float) ($r['rating'] ?? 0));
				if (isset($r['color']))
				{
					$c = $r['color'];
					$color = sprintf('#%02x%02x%02x', $c['r'] ?? 0, $c['g'] ?? 0, $c['b'] ?? 0);
				}
			}

			if (isset($raw_mplus['current_period']['best_runs']))
			{
				foreach ($raw_mplus['current_period']['best_runs'] as $run)
				{
					$upgrades = (int) ($run['keystone_upgrades'] ?? 0);
					$duration_ms = (int) ($run['duration'] ?? 0);
					$minutes = floor($duration_ms / 60000);
					$seconds = floor(($duration_ms % 60000) / 1000);

					$runs[] = array(
						'dungeon'  => $run['dungeon']['name'] ?? '',
						'level'    => (int) ($run['keystone_level'] ?? 0),
						'time'     => sprintf('%d:%02d', $minutes, $seconds),
						'upgrades' => str_repeat('+', $upgrades),
						'timed'    => $upgrades > 0,
					);
				}
			}
		}

		return array('rating' => $rating, 'color' => $color, 'runs' => $runs);
	}

	protected function build_pvp(string $name, string $realm, string $region, string $edition): array
	{
		$raw_pvp = $this->wow_api->fetch_pvp_summary($name, $realm, $region, $edition);
		$honor_level = 0;

		if ($raw_pvp && isset($raw_pvp['honor_level']))
		{
			$honor_level = (int) $raw_pvp['honor_level'];
		}

		return array('honor_level' => $honor_level);
	}
}
