<?php
/**
 * WoW API adapter
 *
 * Implements game_api_interface by wrapping the Battle.net API classes.
 * Updated for the new Game Data / Profile API (2024+).
 *
 * @package   bbguild_wow v2.0
 * @copyright 2018 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild_wow\game;

use avathar\bbguild\model\games\game_api_interface;
use avathar\bbguild_wow\api\battlenet;

/**
 * Class wow_api
 *
 * Adapts the Battle.net SDK to the bbGuild game_api_interface.
 *
 * @package avathar\bbguild_wow\game
 */
class wow_api implements game_api_interface
{
	/** Cache key for class ID→name map */
	const CACHE_KEY_CLASSES = 'bbguild_wow_playable_classes';

	/** Cache key for race ID→name map */
	const CACHE_KEY_RACES = 'bbguild_wow_playable_races';

	/** Cache TTL for static data: 7 days */
	const STATIC_CACHE_TTL = 604800;

	/** @var \phpbb\cache\service */
	private $cache;

	/** @var \phpbb\db\driver\driver_interface */
	private $db;

	/** @var string */
	private $guild_wow_table;

	/** @var string */
	private $bb_players_table;

	/** @var string */
	private $bb_ranks_table;

	/**
	 * @param \phpbb\cache\service              $cache
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param string                            $guild_wow_table
	 * @param string                            $bb_players_table
	 * @param string                            $bb_ranks_table
	 */
	public function __construct(\phpbb\cache\service $cache, \phpbb\db\driver\driver_interface $db, $guild_wow_table, $bb_players_table, $bb_ranks_table)
	{
		$this->cache = $cache;
		$this->db = $db;
		$this->guild_wow_table = $guild_wow_table;
		$this->bb_players_table = $bb_players_table;
		$this->bb_ranks_table = $bb_ranks_table;
	}

	/**
	 * Convert a realm or guild name to a URL slug.
	 *
	 * Rules: lowercase, spaces → hyphens, strip apostrophes and accents.
	 * Examples: "Area 52" → "area-52", "Mal'Ganis" → "malganis"
	 *
	 * @param string $name
	 * @return string
	 */
	public function to_slug(string $name): string
	{
		$slug = mb_strtolower($name, 'UTF-8');

		// Strip apostrophes (e.g. Mal'Ganis → malganis)
		$slug = str_replace("'", '', $slug);

		// Spaces → hyphens
		$slug = str_replace(' ', '-', $slug);

		// Collapse multiple hyphens
		$slug = preg_replace('/-+/', '-', $slug);

		return trim($slug, '-');
	}

	/**
	 * @inheritdoc
	 */
	public function fetch_guild_data(string $guild_name, string $realm, string $region, array $params)
	{
		global $phpbb_container;

		$game = $this->get_game_from_db($phpbb_container);
		if (!$game || trim($game->getApikey()) == '')
		{
			return false;
		}

		$ext_path = $this->get_ext_path($phpbb_container);
		$realm_slug = $this->to_slug($realm);
		$name_slug = $this->to_slug($guild_name);
		$edition = isset($params['edition']) ? $params['edition'] : 'retail';

		// Fetch guild profile
		$api = new battlenet('guild', $region, $game->getApikey(), $game->get_apilocale(), $game->get_privkey(), $ext_path, $this->cache, 3600, $edition);
		$guild_data = $api->guild->getGuild($realm_slug, $name_slug);
		unset($api);

		$result = array();
		if (isset($guild_data['response']) && is_array($guild_data['response']))
		{
			$result = $guild_data['response'];
		}

		// Fetch roster if requested
		if (in_array('members', $params))
		{
			$api = new battlenet('guild', $region, $game->getApikey(), $game->get_apilocale(), $game->get_privkey(), $ext_path, $this->cache, 3600, $edition);
			$roster_data = $api->guild->getRoster($realm_slug, $name_slug);
			unset($api);

			if (isset($roster_data['response']['members']))
			{
				$result['members'] = $roster_data['response']['members'];
			}
		}

		// Attach metadata for downstream processing
		$result['_region'] = $region;
		$result['_realm'] = $realm;
		$result['_realm_slug'] = $realm_slug;
		$result['_edition'] = $edition;

		return $result;
	}

	/**
	 * @inheritdoc
	 */
	public function process_guild_data(array $raw_data, array $params): array
	{
		$result = array();

		// Achievement points — available in guild profile
		$result['achievementpoints'] = isset($raw_data['achievement_points']) ? (int) $raw_data['achievement_points'] : 0;

		// Guild level removed in modern API
		$result['level'] = 0;

		// Battlegroup removed in modern API
		$result['battlegroup'] = '';

		// Faction: new API uses faction.type = 'ALLIANCE' or 'HORDE'
		$result['faction'] = 2; // default Horde
		$result['faction_name'] = 'Horde';
		if (isset($raw_data['faction']['type']))
		{
			if ($raw_data['faction']['type'] === 'ALLIANCE')
			{
				$result['faction'] = 1;
				$result['faction_name'] = 'Alliance';
			}
		}

		// Guild armory URL — only for retail (Classic has no official armory)
		$result['guildarmoryurl'] = '';
		$edition = $raw_data['_edition'] ?? 'retail';
		if (isset($raw_data['name']) && $edition === 'retail')
		{
			$region = $raw_data['_region'] ?? '';
			$realm_slug = $raw_data['_realm_slug'] ?? $this->to_slug($raw_data['_realm'] ?? '');
			$guild_slug = $this->to_slug($raw_data['name']);
			$result['guildarmoryurl'] = sprintf('https://worldofwarcraft.blizzard.com/en-%s/guild/%s/%s/%s', $region, $region, $realm_slug, $guild_slug);
		}

		// Guild crest emblem
		$result['emblempath'] = '';
		if (isset($raw_data['crest']))
		{
			$region = $raw_data['_region'] ?? '';
			$guild_name = $raw_data['name'] ?? '';
			$realm = $raw_data['_realm'] ?? '';
			$result['emblempath'] = $this->create_emblem($raw_data['crest'], $result['faction'], $guild_name, $realm, $region);
		}

		// Member data
		$result['members'] = isset($raw_data['members']) ? $raw_data['members'] : array();
		$result['playercount'] = count($result['members']);

		return $result;
	}

	/**
	 * @inheritdoc
	 */
	public function fetch_character_data(string $name, string $realm, string $region, string $edition = 'retail')
	{
		global $phpbb_container;

		$game = $this->get_game_from_db($phpbb_container);
		if (!$game || trim($game->getApikey()) == '')
		{
			return false;
		}

		$ext_path = $this->get_ext_path($phpbb_container);
		$realm_slug = $this->to_slug($realm);

		$api = new battlenet('character', $region, $game->getApikey(), $game->get_apilocale(), $game->get_privkey(), $ext_path, $this->cache, 3600, $edition);
		$data = $api->character->getCharacter($realm_slug, $name);
		unset($api);

		if (isset($data['response']))
		{
			return $data['response'];
		}

		return $data;
	}

	/**
	 * @inheritdoc
	 */
	public function get_player_armory_url(string $name, string $realm, string $region, string $edition = 'retail'): string
	{
		// Classic has no official armory website
		if ($edition !== 'retail')
		{
			return '';
		}
		$realm_slug = $this->to_slug($realm);
		return sprintf('https://worldofwarcraft.blizzard.com/en-%s/character/%s/%s/%s', $region, $region, $realm_slug, mb_strtolower($name, 'UTF-8'));
	}

	/**
	 * @inheritdoc
	 */
	public function get_player_portrait_url(array $player_data): string
	{
		// Portrait URLs are fetched via the Character Media API and stored
		// in player_portrait_url. This method returns an already-stored URL
		// if available, otherwise empty (portraits are synced separately).
		if (isset($player_data['player_portrait_url']) && !empty($player_data['player_portrait_url']))
		{
			return $player_data['player_portrait_url'];
		}

		return '';
	}

	/**
	 * Fetch character portraits from the Character Media API.
	 *
	 * Processes players that have an empty portrait URL, with a time guard
	 * to stay within PHP's execution limit. Re-running covers more players.
	 *
	 * @param int    $guild_id
	 * @param string $region
	 * @param string $apikey
	 * @param string $locale
	 * @param string $privkey
	 * @return array Result with 'success', 'message', 'count'
	 */
	public function sync_portraits(int $guild_id, string $region, string $apikey, string $locale, string $privkey, string $edition = 'retail'): array
	{
		global $phpbb_root_path, $phpbb_container;
		$db = $this->db;

		// Use phpBB's configured upload path (default: 'files')
		$upload_path = $phpbb_container->get('config')['upload_path'];
		$portrait_rel = $upload_path . '/bbguild_wow/portraits/';
		$portrait_dir = $phpbb_root_path . $portrait_rel;
		if (!is_dir($portrait_dir))
		{
			@mkdir($portrait_dir, 0755, true);
		}

		// Get players without local portraits (empty, NULL, or still pointing to external URLs)
		$sql = 'SELECT player_id, player_name, player_realm, player_region
			FROM ' . $this->bb_players_table . '
			WHERE player_guild_id = ' . $guild_id . '
				AND game_id = \'wow\'
				AND player_status = 1
				AND (player_portrait_url = \'\'
					OR player_portrait_url IS NULL
					OR player_portrait_url LIKE \'http%\')
			ORDER BY player_id';
		$result = $db->sql_query($sql);

		$players = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$players[] = $row;
		}
		$db->sql_freeresult($result);

		if (empty($players))
		{
			return array('success' => true, 'message' => 'All player portraits are up to date.', 'count' => 0);
		}

		$api = new battlenet('character', $region, $apikey, $locale, $privkey, '', $this->cache, 3600, $edition);

		$time_start = time();
		$time_limit = 20;
		$fetched = 0;
		$failed = 0;
		$errors = array(); // error_code => [player_name, ...]

		foreach ($players as $player)
		{
			if ((time() - $time_start) >= $time_limit)
			{
				break;
			}

			$realm_slug = $player['player_realm'];
			$char_name = $player['player_name'];

			$response = $api->character->getCharacterMedia($realm_slug, $char_name);
			$data = isset($response['response']) ? $response['response'] : null;
			$http_code = isset($response['response_headers']['http_code']) ? (int) $response['response_headers']['http_code'] : 0;

			if (!is_array($data) || isset($data['code']))
			{
				$error_code = isset($data['code']) ? (int) $data['code'] : $http_code;
				if ($error_code === 0)
				{
					$error_code = 'unknown';
				}
				$errors[$error_code][] = $player['player_name'];
				$failed++;

				// Mark as unavailable so this player is not retried next batch
				if ($http_code === 404)
				{
					$db->sql_query('UPDATE ' . $this->bb_players_table .
						" SET player_portrait_url = 'N/A'" .
						' WHERE player_id = ' . (int) $player['player_id']);
				}

				// Stop batch early on server errors (5xx) — API is likely down
				if ($http_code >= 500)
				{
					break;
				}
				continue;
			}

			// Extract avatar URL from assets array
			$avatar_url = '';
			if (isset($data['assets']) && is_array($data['assets']))
			{
				foreach ($data['assets'] as $asset)
				{
					if (isset($asset['key']) && $asset['key'] === 'avatar' && isset($asset['value']))
					{
						$avatar_url = $asset['value'];
						break;
					}
				}
			}

			if (!empty($avatar_url))
			{
				// Download and cache portrait locally
				$local_path = $this->download_portrait($avatar_url, $portrait_dir, $portrait_rel, (int) $player['player_id']);

				$stored_url = !empty($local_path) ? $local_path : $avatar_url;

				$db->sql_query('UPDATE ' . $this->bb_players_table .
					" SET player_portrait_url = '" . $db->sql_escape($stored_url) . "'" .
					' WHERE player_id = ' . (int) $player['player_id']);
				$fetched++;
			}
			else
			{
				$errors['no_avatar'][] = $player['player_name'];
				$failed++;
			}
		}

		unset($api);

		$remaining = count($players) - $fetched - $failed;
		$message = sprintf('Fetched %d portraits.', $fetched);
		if (!empty($errors))
		{
			$parts = array();
			foreach ($errors as $code => $names)
			{
				$parts[] = sprintf('%s: %s', $this->error_label($code), implode(', ', $names));
			}
			$message .= sprintf(' %d failed [%s].', $failed, implode('; ', $parts));
		}
		if ($remaining > 0)
		{
			$message .= sprintf(' %d remaining.', $remaining);
		}

		return array('success' => true, 'message' => $message, 'count' => $fetched, 'errors' => $errors);
	}

	/**
	 * Fetch active specializations from the Character Specializations API.
	 *
	 * Processes players that have an empty spec, with a time guard.
	 *
	 * @param int    $guild_id
	 * @param string $region
	 * @param string $apikey
	 * @param string $locale
	 * @param string $privkey
	 * @return array Result with 'success', 'message', 'count'
	 */
	public function sync_specs(int $guild_id, string $region, string $apikey, string $locale, string $privkey, string $edition = 'retail'): array
	{
		$db = $this->db;

		// Get players without specs
		$sql = 'SELECT player_id, player_name, player_realm
			FROM ' . $this->bb_players_table . '
			WHERE player_guild_id = ' . $guild_id . '
				AND game_id = \'wow\'
				AND player_status = 1
				AND (player_spec = \'\' OR player_spec IS NULL)
			ORDER BY player_id';
		$result = $db->sql_query($sql);

		$players = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$players[] = $row;
		}
		$db->sql_freeresult($result);

		if (empty($players))
		{
			return array('success' => true, 'message' => 'All player specs are up to date.', 'count' => 0);
		}

		$api = new battlenet('character', $region, $apikey, $locale, $privkey, '', $this->cache, 3600, $edition);

		$time_start = time();
		$time_limit = 20;
		$fetched = 0;
		$failed = 0;
		$errors = array(); // error_code => [player_name, ...]

		foreach ($players as $player)
		{
			if ((time() - $time_start) >= $time_limit)
			{
				break;
			}

			$response = $api->character->getCharacterSpecializations(
				$player['player_realm'],
				$player['player_name']
			);
			$data = isset($response['response']) ? $response['response'] : null;
			$http_code = isset($response['response_headers']['http_code']) ? (int) $response['response_headers']['http_code'] : 0;

			if (!is_array($data) || isset($data['code']))
			{
				$error_code = isset($data['code']) ? (int) $data['code'] : $http_code;
				if ($error_code === 0)
				{
					$error_code = 'unknown';
				}
				$errors[$error_code][] = $player['player_name'];
				$failed++;

				// Mark as unavailable so this player is not retried next batch
				if ($http_code === 404)
				{
					$db->sql_query('UPDATE ' . $this->bb_players_table .
						" SET player_spec = 'N/A'" .
						' WHERE player_id = ' . (int) $player['player_id']);
				}

				// Stop batch early on server errors (5xx) — API is likely down
				if ($http_code >= 500)
				{
					break;
				}
				continue;
			}

			$spec_name = '';
			if (isset($data['active_specialization']['name']))
			{
				$spec_name = $data['active_specialization']['name'];
			}

			if (empty($spec_name))
			{
				$spec_name = 'N/A';
				$errors['no_spec'][] = $player['player_name'];
				$failed++;
			}
			else
			{
				$fetched++;
			}

			$db->sql_query('UPDATE ' . $this->bb_players_table .
				" SET player_spec = '" . $db->sql_escape($spec_name) . "'" .
				' WHERE player_id = ' . (int) $player['player_id']);
		}

		unset($api);

		$remaining = count($players) - $fetched - $failed;
		$message = sprintf('Fetched %d specs.', $fetched);
		if (!empty($errors))
		{
			$parts = array();
			foreach ($errors as $code => $names)
			{
				$parts[] = sprintf('%s: %s', $this->error_label($code), implode(', ', $names));
			}
			$message .= sprintf(' %d failed [%s].', $failed, implode('; ', $parts));
		}
		if ($remaining > 0)
		{
			$message .= sprintf(' %d remaining.', $remaining);
		}

		return array('success' => true, 'message' => $message, 'count' => $fetched, 'errors' => $errors);
	}

	/**
	 * Return a human-readable label for an API error code.
	 *
	 * @param int|string $code HTTP status code or error key
	 * @return string
	 */
	private function error_label($code): string
	{
		$labels = array(
			404       => '404 Not Found',
			403       => '403 Forbidden',
			500       => '500 Server Error',
			502       => '502 Bad Gateway',
			503       => '503 Service Unavailable',
			504       => '504 Gateway Timeout',
			'no_avatar' => 'No avatar data',
			'no_spec'   => 'No spec data',
			'unknown'   => 'Unknown error',
		);

		return isset($labels[$code]) ? $labels[$code] : 'HTTP ' . $code;
	}

	/**
	 * Download a portrait image and store it locally.
	 *
	 * @param string $url          Remote image URL
	 * @param string $portrait_dir Absolute directory path
	 * @param string $portrait_rel Relative directory path (for DB storage)
	 * @param int    $player_id    Player ID for filename
	 * @return string Local relative path, or empty on failure
	 */
	private function download_portrait(string $url, string $portrait_dir, string $portrait_rel, int $player_id): string
	{
		$image_data = @file_get_contents($url);
		if ($image_data === false || strlen($image_data) < 100)
		{
			return '';
		}

		$filename = $player_id . '.jpg';
		$local_file = $portrait_dir . $filename;

		if (@file_put_contents($local_file, $image_data) === false)
		{
			return '';
		}

		return $portrait_rel . $filename;
	}

	/**
	 * @inheritdoc
	 */
	public function sync_guild_members(array $member_data, int $guild_id, string $region, int $min_level): void
	{
		if (empty($member_data))
		{
			return;
		}

		$this->sync_wow_ranks($member_data, $guild_id);
		$this->update_wow_roster($member_data, $guild_id, $region, $min_level);
	}

	/**
	 * Synchronise WoW guild ranks from Battle.net API data.
	 *
	 * @param array $member_data Raw member array from Battle.net API (new format)
	 * @param int   $guild_id
	 */
	private function sync_wow_ranks(array $member_data, int $guild_id): void
	{
		$newranks = array();
		foreach ($member_data as $new)
		{
			$rank = (int) $new['rank'];
			if (!isset($newranks[$rank]))
			{
				$newranks[$rank] = 0;
			}
			$newranks[$rank]++;
		}
		ksort($newranks);

		// Get existing ranks
		$sql = 'SELECT rank_id FROM ' . $this->bb_ranks_table . '
				WHERE guild_id = ' . (int) $guild_id . ' AND rank_id < 90
				ORDER BY rank_id ASC';
		$result = $this->db->sql_query($sql);
		$oldranks = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$oldranks[(int) $row['rank_id']] = 0;
		}
		$this->db->sql_freeresult($result);

		// Insert ranks that don't exist yet
		$diff = array_diff_key($newranks, $oldranks);
		foreach ($diff as $rank_id => $count)
		{
			$sql = 'DELETE FROM ' . $this->bb_ranks_table . '
					WHERE rank_id = ' . (int) $rank_id . '
					AND guild_id = ' . (int) $guild_id;
			$this->db->sql_query($sql);

			$query = $this->db->sql_build_array('INSERT', array(
				'rank_id'     => (int) $rank_id,
				'rank_name'   => 'Rank' . $rank_id,
				'rank_hide'   => 0,
				'rank_prefix' => '',
				'rank_suffix' => '',
				'guild_id'    => (int) $guild_id,
			));
			$this->db->sql_query('INSERT INTO ' . $this->bb_ranks_table . $query);
		}
	}

	/**
	 * Update the WoW guild roster from Battle.net API data.
	 *
	 * New API response format per member:
	 * {
	 *   "character": {
	 *     "name": "Arthas",
	 *     "id": 12345,
	 *     "realm": { "slug": "area-52", "id": 1566 },
	 *     "playable_class": { "id": 6 },
	 *     "playable_race": { "id": 1 },
	 *     "level": 80
	 *   },
	 *   "rank": 0
	 * }
	 *
	 * @param array  $member_data Raw member array from Battle.net API
	 * @param int    $guild_id
	 * @param string $region
	 * @param int    $min_level
	 */
	private function update_wow_roster(array $member_data, int $guild_id, string $region, int $min_level): void
	{
		global $user, $phpbb_container;

		// Ensure the bbguild admin language file is loaded
		$user->add_lang_ext('avathar/bbguild', 'admin');

		$player_ids = array();
		$oldplayers = array();
		$newplayers = array();

		// Get existing players
		$sql = 'SELECT player_name, player_id, player_realm FROM ' . $this->bb_players_table . '
				WHERE player_guild_id = ' . (int) $guild_id . "
				AND game_id = 'wow'
				ORDER BY player_name ASC";
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$oldplayers[] = $row['player_name'] . '-' . $row['player_realm'];
			$player_ids[bin2hex($row['player_name'] . '-' . $row['player_realm'])] = $row['player_id'];
		}
		$this->db->sql_freeresult($result);

		foreach ($member_data as $mb)
		{
			$char = $mb['character'];
			$realm_slug = isset($char['realm']['slug']) ? $char['realm']['slug'] : 'unknown';
			$newplayers[] = $char['name'] . '-' . $realm_slug;
		}

		$to_add = array_diff($newplayers, $oldplayers);

		$this->db->sql_transaction('begin');

		foreach ($member_data as $mb)
		{
			$char = $mb['character'];
			$realm_slug = isset($char['realm']['slug']) ? $char['realm']['slug'] : 'unknown';
			$player_key = $char['name'] . '-' . $realm_slug;
			$level = isset($char['level']) ? (int) $char['level'] : 0;

			if (in_array($player_key, $to_add) && $level >= $min_level)
			{
				$class_id = isset($char['playable_class']['id']) ? (int) $char['playable_class']['id'] : 0;
				$race_id = isset($char['playable_race']['id']) ? (int) $char['playable_race']['id'] : 0;
				$armory_url = $this->get_player_armory_url($char['name'], $realm_slug, $region);

				$query = $this->db->sql_build_array('INSERT', array(
					'player_name'         => ucwords($char['name']),
					'player_status'       => 1,
					'player_level'        => $level,
					'player_race_id'      => $race_id,
					'player_class_id'     => $class_id,
					'player_rank_id'      => isset($mb['rank']) ? (int) $mb['rank'] : 1,
					'player_role'         => 'NA',
					'player_realm'        => $realm_slug,
					'player_region'       => $region,
					'player_comment'      => sprintf($user->lang['ADMIN_ADD_PLAYER_SUCCESS'], $char['name'], date('F j, Y, g:i a')),
					'player_joindate'     => time(),
					'player_outdate'      => mktime(0, 0, 0, 12, 31, 2030),
					'player_guild_id'     => (int) $guild_id,
					'player_gender_id'    => 0,
					'player_achiev'       => 0,
					'player_armory_url'   => $armory_url,
					'phpbb_user_id'       => 0,
					'game_id'             => 'wow',
					'player_portrait_url' => '',
					'player_title'        => '',
					'last_update'         => time(),
				));
				$this->db->sql_query('INSERT INTO ' . $this->bb_players_table . $query);
			}
		}

		// Update existing players
		$to_update = array_intersect($newplayers, $oldplayers);
		foreach ($member_data as $mb)
		{
			$char = $mb['character'];
			$realm_slug = isset($char['realm']['slug']) ? $char['realm']['slug'] : 'unknown';
			$player_key = $char['name'] . '-' . $realm_slug;

			if (in_array($player_key, $to_update))
			{
				$player_id = (int) $player_ids[bin2hex($player_key)];
				$class_id = isset($char['playable_class']['id']) ? (int) $char['playable_class']['id'] : 0;
				$race_id = isset($char['playable_race']['id']) ? (int) $char['playable_race']['id'] : 0;

				$sql_ary = array(
					'player_name'         => ucwords($char['name']),
					'player_level'        => isset($char['level']) ? (int) $char['level'] : 0,
					'player_race_id'      => $race_id,
					'player_realm'        => $realm_slug,
					'player_region'       => $region,
					'player_class_id'     => $class_id,
					'player_rank_id'      => (int) $mb['rank'],
					'player_guild_id'     => (int) $guild_id,
					'player_armory_url'   => $this->get_player_armory_url($char['name'], $realm_slug, $region),
					'last_update'         => time(),
				);

				$sql = 'UPDATE ' . $this->bb_players_table . '
						SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
						WHERE player_id = ' . $player_id;
				$this->db->sql_query($sql);
			}
		}

		$this->db->sql_transaction('commit');
	}

	/**
	 * Fetch and cache the playable class ID→name map from the API.
	 *
	 * @param string $region
	 * @return array Map of class_id => class_name
	 */
	public function get_playable_classes(string $region, string $edition = 'retail'): array
	{
		$cache_key = self::CACHE_KEY_CLASSES . '_' . $edition . '_' . $region;
		$cached = $this->cache->get($cache_key);
		if ($cached !== false)
		{
			return $cached;
		}

		global $phpbb_container;
		$game = $this->get_game_from_db($phpbb_container);
		if (!$game || trim($game->getApikey()) == '')
		{
			return array();
		}

		$ext_path = $this->get_ext_path($phpbb_container);
		$api = new battlenet('playable-data', $region, $game->getApikey(), $game->get_apilocale(), $game->get_privkey(), $ext_path, $this->cache, 3600, $edition);
		$data = $api->static_data->getPlayableClasses();
		unset($api);

		$map = array();
		if (isset($data['response']['classes']))
		{
			foreach ($data['response']['classes'] as $class)
			{
				if (isset($class['id']) && isset($class['name']))
				{
					$map[(int) $class['id']] = $class['name'];
				}
			}
		}

		if (!empty($map))
		{
			$this->cache->put($cache_key, $map, self::STATIC_CACHE_TTL);
		}

		return $map;
	}

	/**
	 * Fetch and cache the playable race ID→name map from the API.
	 *
	 * @param string $region
	 * @return array Map of race_id => race_name
	 */
	public function get_playable_races(string $region, string $edition = 'retail'): array
	{
		$cache_key = self::CACHE_KEY_RACES . '_' . $edition . '_' . $region;
		$cached = $this->cache->get($cache_key);
		if ($cached !== false)
		{
			return $cached;
		}

		global $phpbb_container;
		$game = $this->get_game_from_db($phpbb_container);
		if (!$game || trim($game->getApikey()) == '')
		{
			return array();
		}

		$ext_path = $this->get_ext_path($phpbb_container);
		$api = new battlenet('playable-data', $region, $game->getApikey(), $game->get_apilocale(), $game->get_privkey(), $ext_path, $this->cache, 3600, $edition);
		$data = $api->static_data->getPlayableRaces();
		unset($api);

		$map = array();
		if (isset($data['response']['races']))
		{
			foreach ($data['response']['races'] as $race)
			{
				if (isset($race['id']) && isset($race['name']))
				{
					$map[(int) $race['id']] = $race['name'];
				}
			}
		}

		if (!empty($map))
		{
			$this->cache->put($cache_key, $map, self::STATIC_CACHE_TTL);
		}

		return $map;
	}

	/**
	 * @inheritdoc
	 */
	public function requires_api_key(): bool
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function save_guild_extension(int $guild_id, array $processed): void
	{
		$row = array(
			'guild_id'           => $guild_id,
			'battlegroup'        => isset($processed['battlegroup']) ? $processed['battlegroup'] : '',
			'level'              => isset($processed['level']) ? $processed['level'] : 0,
			'achievementpoints'  => isset($processed['achievementpoints']) ? $processed['achievementpoints'] : 0,
			'guildarmoryurl'     => isset($processed['guildarmoryurl']) ? $processed['guildarmoryurl'] : '',
		);

		// Check if row exists
		$sql = 'SELECT guild_id FROM ' . $this->guild_wow_table . ' WHERE guild_id = ' . (int) $guild_id;
		$result = $this->db->sql_query($sql);
		$exists = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($exists)
		{
			$update = $row;
			unset($update['guild_id']);
			$query = $this->db->sql_build_array('UPDATE', $update);
			$this->db->sql_query('UPDATE ' . $this->guild_wow_table . ' SET ' . $query . ' WHERE guild_id = ' . (int) $guild_id);
		}
		else
		{
			$query = $this->db->sql_build_array('INSERT', $row);
			$this->db->sql_query('INSERT INTO ' . $this->guild_wow_table . $query);
		}
	}

	/**
	 * Create a WoW Guild emblem image from Battle.net crest data.
	 *
	 * New API crest format:
	 * {
	 *   "emblem":     { "id": 123, "media": { "key": { "href": "..." } }, "color": { "id": 1, "rgba": { "r":255,"g":0,"b":0,"a":1 } } },
	 *   "border":     { "id": 0,   "media": { "key": { "href": "..." } }, "color": { "id": 1, "rgba": { "r":255,"g":0,"b":0,"a":1 } } },
	 *   "background": { "color": { "id": 1, "rgba": { "r":0,"g":0,"b":0,"a":1 } } }
	 * }
	 *
	 * Uses local emblem/border PNGs when available, falls back to API media endpoint.
	 *
	 * @param array  $crest      Crest data from guild profile
	 * @param int    $faction    Guild faction (1=Alliance, 2=Horde)
	 * @param string $guild_name Guild name
	 * @param string $realm      Realm name
	 * @param string $region     Region code
	 * @param int    $width      Output image width
	 * @return string Path to generated emblem image, or empty string
	 */
	private function create_emblem(array $crest, int $faction, string $guild_name, string $realm, string $region, int $width = 175): string
	{
		if (!isset($crest['emblem']['id']) || !isset($crest['border']['id']))
		{
			return '';
		}

		global $phpbb_root_path, $phpbb_container;
		$wow_ext_path = $phpbb_container->get('ext.manager')->get_extension_path('avathar/bbguild_wow', true);

		// Store emblems in phpBB's upload directory (files/bbguild_wow/emblems/)
		$upload_path = $phpbb_container->get('config')['upload_path'];
		$emblem_rel = $upload_path . '/bbguild_wow/emblems/';
		$emblem_dir = $phpbb_root_path . $emblem_rel;
		if (!is_dir($emblem_dir))
		{
			@mkdir($emblem_dir, 0755, true);
		}

		$safe_name = str_replace(' ', '_', $guild_name);
		$filename = $region . '_' . $realm . '_' . $safe_name . '.png';
		$imgfile = $emblem_dir . $filename;

		// Return cached image if fresh (< 24h)
		if (file_exists($imgfile) && (filemtime($imgfile) + 86400) > time())
		{
			$existing = @imagecreatefrompng($imgfile);
			if ($existing !== false && imagesx($existing) == $width)
			{
				imagedestroy($existing);
				return $emblem_rel . $filename;
			}
			if ($existing !== false)
			{
				imagedestroy($existing);
			}
		}

		$emblem_id = (int) $crest['emblem']['id'];
		$border_id = (int) $crest['border']['id'];

		// Load emblem PNG: try local file first, then fetch from API
		$emblem = $this->load_crest_asset($wow_ext_path, 'emblems', 'emblem', $emblem_id, $region);
		if ($emblem === false)
		{
			return '';
		}

		// Load border PNG
		$border = $this->load_crest_asset($wow_ext_path, 'borders', 'border', $border_id, $region);
		if ($border === false)
		{
			imagedestroy($emblem);
			return '';
		}

		// Extract RGBA colors
		$emblem_rgba = $crest['emblem']['color']['rgba'] ?? array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 1);
		$border_rgba = $crest['border']['color']['rgba'] ?? array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 1);
		$bg_rgba = $crest['background']['color']['rgba'] ?? array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 1);

		// Apply color overlay to emblem
		$emblem_size = array(imagesx($emblem), imagesy($emblem));
		imagelayereffect($emblem, IMG_EFFECT_OVERLAY);
		imagefilledrectangle($emblem, 0, 0, $emblem_size[0], $emblem_size[1],
			imagecolorallocatealpha($emblem, $emblem_rgba['r'], $emblem_rgba['g'], $emblem_rgba['b'], 0));

		// Apply color overlay to border
		$border_size = array(imagesx($border), imagesy($border));
		imagelayereffect($border, IMG_EFFECT_OVERLAY);
		imagefilledrectangle($border, 0, 0, $border_size[0], $border_size[1],
			imagecolorallocatealpha($border, $border_rgba['r'], $border_rgba['g'], $border_rgba['b'], 0));

		// Load static assets (ring, shadow, bg, overlay, hooks)
		$ring_name = ($faction == 1) ? 'alliance' : 'horde';
		$ringURL = $wow_ext_path . 'images/wowapi/static/ring-' . $ring_name . '.png';
		$shadowURL = $wow_ext_path . 'images/wowapi/static/shadow_00.png';
		$bgURL = $wow_ext_path . 'images/wowapi/static/bg_00.png';
		$overlayURL = $wow_ext_path . 'images/wowapi/static/overlay_00.png';
		$hooksURL = $wow_ext_path . 'images/wowapi/static/hooks.png';

		if (!file_exists($ringURL) || !file_exists($shadowURL) || !file_exists($bgURL))
		{
			imagedestroy($emblem);
			imagedestroy($border);
			return '';
		}

		$ring = imagecreatefrompng($ringURL);
		$ring_size = getimagesize($ringURL);
		$shadow = imagecreatefrompng($shadowURL);
		$bg = imagecreatefrompng($bgURL);
		$bg_size = getimagesize($bgURL);

		// Apply background color
		imagelayereffect($bg, IMG_EFFECT_OVERLAY);
		imagefilledrectangle($bg, 0, 0, $bg_size[0], $bg_size[1],
			imagecolorallocatealpha($bg, $bg_rgba['r'], $bg_rgba['g'], $bg_rgba['b'], 0));

		// Composite onto 215x230 canvas
		$imgOut = imagecreatetruecolor(215, 230);
		imagesavealpha($imgOut, true);
		imagealphablending($imgOut, true);
		$trans = imagecolorallocatealpha($imgOut, 0, 0, 0, 127);
		imagefill($imgOut, 0, 0, $trans);

		$x = 20;
		$y = 23;

		imagecopy($imgOut, $ring, 0, 0, 0, 0, $ring_size[0], $ring_size[1]);
		$shadow_size = getimagesize($shadowURL);
		imagecopy($imgOut, $shadow, $x, $y, 0, 0, $shadow_size[0], $shadow_size[1]);
		imagecopy($imgOut, $bg, $x, $y, 0, 0, $bg_size[0], $bg_size[1]);
		imagecopy($imgOut, $emblem, $x + 17, $y + 30, 0, 0, $emblem_size[0], $emblem_size[1]);
		imagecopy($imgOut, $border, $x + 13, $y + 15, 0, 0, $border_size[0], $border_size[1]);

		if (file_exists($overlayURL))
		{
			$overlay = imagecreatefrompng($overlayURL);
			$overlay_size = getimagesize($overlayURL);
			imagecopy($imgOut, $overlay, $x, $y + 2, 0, 0, $overlay_size[0], $overlay_size[1]);
			imagedestroy($overlay);
		}

		if (file_exists($hooksURL))
		{
			$hooks = imagecreatefrompng($hooksURL);
			$hooks_size = getimagesize($hooksURL);
			imagecopy($imgOut, $hooks, $x - 2, $y, 0, 0, $hooks_size[0], $hooks_size[1]);
			imagedestroy($hooks);
		}

		// Scale to target width
		if ($width > 1 && $width < 215)
		{
			$height = (int) (($width / 215) * 230);
			$finalimg = imagecreatetruecolor($width, $height);
			$trans = imagecolorallocatealpha($finalimg, 0, 0, 0, 127);
			imagefill($finalimg, 0, 0, $trans);
			imagesavealpha($finalimg, true);
			imagealphablending($finalimg, true);
			imagecopyresampled($finalimg, $imgOut, 0, 0, 0, 0, $width, $height, 215, 230);
			imagedestroy($imgOut);
		}
		else
		{
			$finalimg = $imgOut;
		}

		imagepng($finalimg, $imgfile);

		imagedestroy($finalimg);
		imagedestroy($emblem);
		imagedestroy($border);
		imagedestroy($ring);
		imagedestroy($shadow);
		imagedestroy($bg);

		return $emblem_rel . $filename;
	}

	/**
	 * Load a crest asset PNG (emblem or border).
	 *
	 * Tries the local file first (images/wowapi/{dir}/{type}_{id}.png),
	 * falls back to fetching the render URL from the API media endpoint
	 * and downloading the image.
	 *
	 * @param string $wow_ext_path Path to bbguild_wow extension
	 * @param string $dir          Local subdirectory ('emblems' or 'borders')
	 * @param string $type         Asset type ('emblem' or 'border')
	 * @param int    $id           Asset ID
	 * @param string $region       API region
	 * @return resource|false GD image resource, or false on failure
	 */
	private function load_crest_asset(string $wow_ext_path, string $dir, string $type, int $id, string $region)
	{
		// Try local file first
		$local_path = $wow_ext_path . 'images/wowapi/' . $dir . '/' . $type . '_' . sprintf('%02d', $id) . '.png';
		if (file_exists($local_path))
		{
			return imagecreatefrompng($local_path);
		}

		// Fetch render URL from API media endpoint
		global $phpbb_container;
		$game = $this->get_game_from_db($phpbb_container);
		if (!$game || trim($game->getApikey()) == '')
		{
			return false;
		}

		$ext_path = $this->get_ext_path($phpbb_container);
		$api = new battlenet('playable-data', $region, $game->getApikey(), $game->get_apilocale(), $game->get_privkey(), $ext_path, $this->cache);

		$data = ($type === 'emblem')
			? $api->static_data->getEmblemMedia($id)
			: $api->static_data->getBorderMedia($id);
		unset($api);

		if (!isset($data['response']['assets'][0]['value']))
		{
			return false;
		}

		$image_url = $data['response']['assets'][0]['value'];

		// Download the image
		$curl = curl_init($image_url);
		if ($curl === false)
		{
			return false;
		}

		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_USERAGENT      => 'bbGuild/2.0 (phpBB)',
		));

		$image_data = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		if ($image_data === false || $http_code !== 200)
		{
			return false;
		}

		// Save locally for future use
		@file_put_contents($local_path, $image_data);

		$img = @imagecreatefromstring($image_data);
		return ($img !== false) ? $img : false;
	}

	/**
	 * Load WoW game record from the database to get API credentials.
	 *
	 * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
	 * @return \avathar\bbguild\model\games\game|null
	 */
	private function get_game_from_db($container)
	{
		try
		{
			$user = $container->get('user');
			$user->add_lang_ext('avathar/bbguild', 'admin');

			$game = new \avathar\bbguild\model\games\game(
				$container->get('dbal.conn'),
				$container->get('cache.driver'),
				$container->get('config'),
				$user,
				$container->get('ext.manager'),
				$container->getParameter('avathar.bbguild.tables.bb_classes'),
				$container->getParameter('avathar.bbguild.tables.bb_races'),
				$container->getParameter('avathar.bbguild.tables.bb_language'),
				$container->getParameter('avathar.bbguild.tables.bb_factions'),
				$container->getParameter('avathar.bbguild.tables.bb_games')
			);
			$game->game_id = 'wow';
			$game->get_game();
			return $game;
		}
		catch (\Exception $e)
		{
			return null;
		}
	}

	/**
	 * Get the bbGuild core extension path.
	 *
	 * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
	 * @return string
	 */
	private function get_ext_path($container)
	{
		$ext_manager = $container->get('ext.manager');
		return $ext_manager->get_extension_path('avathar/bbguild', true);
	}
}
