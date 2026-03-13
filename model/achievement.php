<?php
/**
 * This file holds the Achievement API class
 *
 * @package   bbguild_wow v2.0
 * @copyright 2018 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace avathar\bbguild_wow\model;

use avathar\bbguild_wow\api\battlenet;
use avathar\bbguild\model\games\game;
use avathar\bbguild\model\player\guilds;

/**
 * This provides data about an individual achievement.
 *
 * @package avathar\bbguild_wow\model
 */
class achievement
{
	public $bb_achievement_track_table;
	public $bb_achievement_table;
	public $bb_achievement_rewards_table;
	public $bb_criteria_track_table;
	public $bb_achievement_criteria_table;
	public $bb_relations_table;
	public $bb_guild_wow_table;
	public $bb_achievement_category_table;

	/**
	 * achievement id
	 * bb_achievement
	 * @var int
	 */
	public $id;

	/**
	 * game id
	 * bb_achievement
	 * @var string
	 */
	public $game_id;

	/**
	 * title of achievement
	 * bb_achievement
	 * @var string
	 */
	protected $title;

	/**
	 * points
	 * bb_achievement
	 * @var int
	 */
	protected $points;

	/**
	 * long description
	 * bb_achievement
	 * @var string
	 */
	protected $description;

	/**
	 * icon
	 *
	 * @var string
	 */
	protected $icon;

	/**
	 * faction ID
	 * bb_achievement
	 * @var int
	 */
	protected $factionId;

	/**
	 * guild if its a guild achievement
	 * bb_achievement
	 * @var string
	 */
	protected $guild_id;

	/**
	 * player_id if its an individual achievement
	 * bb_achievement
	 * @var string
	 */
	protected $player_id;

	/**
	 * oneline description of rewards attached to this achievement.
	 *
	 * @var string
	 */
	protected $reward;

	/***************************************/

	/**
	 * criteria
	 * bb_achievement_criteria
	 * @var array
	 */
	protected $criteria;

	/**
	 * reward
	 * bb_achievement_rewards
	 * @var array
	 */
	protected $rewardItems;

	/**
	 * date of achievement completion.
	 * bb_achievement_track
	 * @type double
	 */
	protected $achievements_completed;

	/***************************************/

	/**
	 * @type game
	 */
	private $game;

	/***************************************/

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 * @return achievement
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getGameId()
	{
		return $this->game_id;
	}

	/**
	 * @param string $game_id
	 * @return achievement
	 */
	public function setGameId($game_id)
	{
		$this->game_id = $game_id;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $title
	 * @return achievement
	 */
	public function setTitle($title)
	{
		$this->title = $title;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getPoints()
	{
		return $this->points;
	}

	/**
	 * @param int $points
	 * @return achievement
	 */
	public function setPoints($points)
	{
		$this->points = $points;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @param string $description
	 * @return achievement
	 */
	public function setDescription($description)
	{
		$this->description = $description;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getIcon()
	{
		return $this->icon;
	}

	/**
	 * @param string $icon
	 * @return achievement
	 */
	public function setIcon($icon)
	{
		$this->icon = $icon;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getFactionId()
	{
		return $this->factionId;
	}

	/**
	 * @param int $factionId
	 * @return achievement
	 */
	public function setFactionId($factionId)
	{
		$this->factionId = $factionId;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getGuildId()
	{
		return $this->guild_id;
	}

	/**
	 * @param string $guild_id
	 */
	public function setGuildId($guild_id)
	{
		$this->guild_id = $guild_id;
	}

	/**
	 * @return string
	 */
	public function getPlayerId()
	{
		return $this->player_id;
	}

	/**
	 * @param string $player_id
	 */
	public function setPlayerId($player_id)
	{
		$this->player_id = $player_id;
	}

	/**
	 * @return string
	 */
	public function getReward()
	{
		return $this->reward;
	}

	/**
	 * @param string $reward
	 */
	public function setReward($reward)
	{
		$this->reward = $reward;
	}

	/*****************************************************/

	/**
	 * @return array
	 */
	public function getRewardItems()
	{
		return $this->rewardItems;
	}

	/**
	 * @param array $rewardItems
	 * @return achievement
	 */
	public function setRewardItems($rewardItems)
	{
		$this->rewardItems = $rewardItems;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getCriteria()
	{
		return $this->criteria;
	}

	/**
	 * @param array $criteria
	 * @return achievement
	 */
	public function setCriteria($criteria)
	{
		$this->criteria = $criteria;
		return $this;
	}

	/**
	 * achievement constructor.
	 *
	 * Table names are injected via the service container.
	 * Use setGame() and setId() to configure game/achievement context.
	 *
	 * @param string $bb_achievement_track_table
	 * @param string $bb_achievement_table
	 * @param string $bb_achievement_rewards_table
	 * @param string $bb_criteria_track_table
	 * @param string $bb_achievement_criteria_table
	 * @param string $bb_relations_table
	 */
	/** @var \avathar\bbguild\model\admin\util */
	protected $util;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\cache\service */
	protected $cache;

	public function __construct(
		\phpbb\db\driver\driver_interface $db,
		\phpbb\cache\service $cache,
		\avathar\bbguild\model\admin\util $util,
		$bb_achievement_track_table, $bb_achievement_table,
		$bb_achievement_rewards_table, $bb_criteria_track_table, $bb_achievement_criteria_table,
		$bb_relations_table, $bb_guild_wow_table, $bb_achievement_category_table)
	{
		$this->db = $db;
		$this->cache = $cache;
		$this->util = $util;
		$this->bb_achievement_track_table = $bb_achievement_track_table;
		$this->bb_achievement_table = $bb_achievement_table;
		$this->bb_achievement_rewards_table = $bb_achievement_rewards_table;
		$this->bb_criteria_track_table = $bb_criteria_track_table;
		$this->bb_achievement_criteria_table = $bb_achievement_criteria_table;
		$this->bb_relations_table = $bb_relations_table;
		$this->bb_guild_wow_table = $bb_guild_wow_table;
		$this->bb_achievement_category_table = $bb_achievement_category_table;
	}

	/**
	 * Set the game context for this achievement instance.
	 *
	 * @param game $game
	 * @param int  $id
	 */
	public function setGame(game $game, $id = 0)
	{
		$this->game = $game;
		$this->game_id = $game->game_id;
		$this->id = $id;
	}

	/**
	 * get achievement (no track info)
	 * @return int
	 */
	public function get_achievement()
	{
		$db = $this->db;
		$i=0;

		$sql_array = array (
			'SELECT' => '
			a.id   AS achievement_id,
			a.game_id,
			a.title,
			a.points,
			a.description,
			a.icon,
			a.factionid,
			a.reward,
			ac.achievements_completed,
			r2.rel_value      AS rewards_item_id ,
			w.description     AS rewardsdescription,
			w.rewards_item_id AS rewards_item_id,
			w.itemlevel       AS itemlevel,
			w.quality         AS quality,
			r1.rel_value      AS criteria_id,
			c.description     AS criteriadescription,
			c.orderindex      AS criteriaorder,
			c.max             AS criteriamax,
			ct.criteria_quantity,
			ct.criteria_timestamp,
			ct.criteria_created ',
			'FROM' => array (
				$this->bb_achievement_table => 'a',
				$this->bb_achievement_track_table => 'ac',
			),
			'LEFT_JOIN' => array(
				array(
					'FROM'  => array($this->bb_relations_table => 'r2'),
					'ON'    => "  a.id = r2.att_value AND r2.attribute_id = 'ACH' AND r2.rel_attr_id = 'REW' " ,
				),
				array(
					'FROM'  => array($this->bb_achievement_rewards_table => 'w'),
					'ON'    => " w.rewards_item_id = r2.rel_value " ,
				),
				array(
					'FROM'  => array($this->bb_relations_table => 'r1'),
					'ON'    => " a.id = r1.att_value AND r1.attribute_id = 'ACH' AND r1.rel_attr_id = 'CRI' " ,
				),
				array(
					'FROM'  => array($this->bb_achievement_criteria_table => 'c'),
					'ON'    => " c.criteria_id = r1.rel_value " ,
				),
				array(
					'FROM'  => array($this->bb_criteria_track_table => 'ct'),
					'ON'    => " ct.criteria_id = c.criteria_id AND ct.guild_id = ac.guild_id " ,
				)),
			'WHERE' =>  'a.id = ac.achievement_id AND a.id = ' . (int) $this->id . " AND a.game_id = '". $this->game_id . "'" ,
		);

		$sql = $db->sql_build_query('SELECT', $sql_array);

		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$i=1;
			$this->title        = $row['title'];
			$this->points       = $row['points'];
			$this->description  = $row['description'];
			$this->icon         = $row['icon'];
			$this->factionId    = $row['factionid'];
			$this->reward       = $row['reward'];
			$this->criteria     = array(
				'criteria_id'          => $row['criteria_id'],
				'criteriadescription'  => $row['criteriadescription'],
				'criteriaorder'        => $row['criteriaorder'],
				'criteriamax'          => $row['criteriamax'],
			);
			$this->rewardItems  = array(
				'rewards_item_id'      => $row['rewards_item_id'],
				'rewardsdescription'   => $row['rewardsdescription'],
				'itemlevel'            => $row['itemlevel'],
				'quality'              => $row['quality'],
			);
		}
		$db->sql_freeresult($result);
		return $i;
	}

	/**
	 * get tracked achievements from local database
	 *
	 * @param     $start
	 * @param     $guild_id
	 * @param int $player_id
	 * @return array
	 */
	public function get_tracked_achievements($start, $guild_id, $player_id = 0)
	{
		$db = $this->db;
		$per_page = 15;

		// Build owner filter: guild or player, but not both with a zero that matches everything
		if ((int) $player_id > 0)
		{
			$owner_filter = 'ac.player_id = ' . (int) $player_id;
		}
		else
		{
			$owner_filter = 'ac.guild_id = ' . (int) $guild_id;
		}

		// Count total (simple query, no joins to criteria/rewards)
		$sql = 'SELECT COUNT(*) AS total
			FROM ' . $this->bb_achievement_track_table . ' ac
			INNER JOIN ' . $this->bb_achievement_table . ' a ON a.id = ac.achievement_id
			WHERE ' . $owner_filter . '
				AND a.game_id = \'' . $db->sql_escape($this->game_id) . '\'';
		$result = $db->sql_query($sql);
		$achievcount = (int) $db->sql_fetchfield('total');
		$db->sql_freeresult($result);

		// Sort
		$sort_order = array(
			0 => array('a.id', 'a.id desc'),
			1 => array('a.title', 'a.title desc'),
			2 => array('a.description', 'a.description desc'),
			3 => array('a.points', 'a.points desc'),
			4 => array('ac.achievements_completed', 'ac.achievements_completed desc'),
		);
		$current_order = $this->util->switch_order($sort_order);

		// Fetch paginated results — flat join, no criteria/rewards (shown in detail view)
		$sql = 'SELECT a.id AS achievement_id, a.game_id, a.title, a.points,
				a.description, a.icon, a.factionid, a.reward,
				ac.achievements_completed, ac.guild_id, ac.player_id
			FROM ' . $this->bb_achievement_track_table . ' ac
			INNER JOIN ' . $this->bb_achievement_table . ' a ON a.id = ac.achievement_id
			WHERE ' . $owner_filter . '
				AND a.game_id = \'' . $db->sql_escape($this->game_id) . '\'
			ORDER BY ' . $current_order['sql'];
		$result = $db->sql_query_limit($sql, $per_page, $start);

		$achievements = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$achievements[] = array(
				'achievement_id'         => $row['achievement_id'],
				'guild_id'               => $row['guild_id'],
				'player_id'              => $row['player_id'],
				'game_id'                => $row['game_id'],
				'title'                  => $row['title'],
				'points'                 => $row['points'],
				'description'            => $row['description'],
				'icon'                   => $row['icon'],
				'factionId'              => $row['factionid'],
				'reward'                 => $row['reward'],
				'achievements_completed' => $row['achievements_completed'],
			);
		}
		$db->sql_freeresult($result);

		return array($achievements, $current_order, $achievcount);
	}


	/**
	 * Sync guild achievements from the Battle.net Game Data API.
	 *
	 * Uses the new Game Data API endpoints:
	 * - Guild achievements: GET /data/wow/guild/{realm}/{name}/achievements (profile namespace)
	 * - Achievement detail: GET /data/wow/achievement/{id} (static namespace)
	 *
	 * @param \avathar\bbguild\model\player\guilds $Guild
	 * @param \avathar\bbguild\model\games\game    $game
	 * @return array Result with 'success' (bool), 'message' (string), 'count' (int)
	 */
	public function setAchievements(guilds $Guild, game $game): array
	{
		$db = $this->db;
		$cache = $this->cache;

		if (!$game->getArmoryEnabled())
		{
			return array('success' => false, 'message' => 'Armory is not enabled for this game. Enable it in ACP Game settings.', 'count' => 0);
		}

		if (!$Guild->isArmoryEnabled())
		{
			return array('success' => false, 'message' => 'Armory is not enabled for this guild. Enable it in ACP Guild settings.', 'count' => 0);
		}

		// Use the guild's own region (guilds can be on different regions within the same game)
		$region = $Guild->getRegion();
		if (empty($region))
		{
			// Fall back to game-level region if guild has none set
			$region = $game->getRegion();
		}
		$apikey = $game->getApikey();
		$privkey = $game->get_privkey();

		if (empty($apikey) || empty($privkey))
		{
			return array('success' => false, 'message' => 'Battle.net API credentials not configured. Set Client ID and Secret in ACP Game settings.', 'count' => 0);
		}

		$realm_slug = $this->make_slug($Guild->getRealm());
		$name_slug = $this->make_slug($Guild->getName());

		if (empty($realm_slug) || empty($name_slug))
		{
			return array('success' => false, 'message' => sprintf('Guild realm or name is empty (realm="%s", name="%s"). Check guild settings.', $Guild->getRealm(), $Guild->getName()), 'count' => 0);
		}

		$locale = $game->get_apilocale();

		// First verify the guild exists by fetching the basic guild profile
		$api = new battlenet('guild', $region, $apikey, $locale, $privkey, '', $cache);

		$guild_response = $api->guild->getGuild($realm_slug, $name_slug);
		$guild_data = isset($guild_response['response']) ? $guild_response['response'] : array();

		if (empty($guild_data) || !is_array($guild_data) || isset($guild_data['code']))
		{
			$http_code = isset($guild_response['response_headers']['http_code']) ? $guild_response['response_headers']['http_code'] : 'unknown';
			$request_url = isset($guild_response['request_url']) ? $guild_response['request_url'] : 'unknown';
			$error_detail = '';
			if (isset($guild_data['code']))
			{
				$error_detail = sprintf('API error %d: %s', $guild_data['code'], isset($guild_data['detail']) ? $guild_data['detail'] : 'Unknown');
			}
			else
			{
				$error_detail = sprintf('Empty response (HTTP %s)', $http_code);
			}
			unset($api);
			return array('success' => false, 'message' => sprintf(
				'%s. Could not find guild "%s" on realm "%s" (region: %s). Request URL: %s',
				$error_detail, $Guild->getName(), $Guild->getRealm(), $region, $request_url
			), 'count' => 0);
		}

		// Now fetch guild achievements
		$response = $api->guild->getAchievements($realm_slug, $name_slug);
		unset($api);

		$data = isset($response['response']) ? $response['response'] : array();

		// Check for API error responses (code + detail format)
		$achiev_url = isset($response['request_url']) ? $response['request_url'] : 'unknown';
		if (empty($data) || !is_array($data))
		{
			$http_code = isset($response['response_headers']['http_code']) ? $response['response_headers']['http_code'] : 'unknown';
			$error = isset($response['error']) ? $response['error'] : '';
			return array('success' => false, 'message' => sprintf('Achievements API returned empty response (HTTP %s). %s URL: %s', $http_code, $error, $achiev_url), 'count' => 0);
		}

		if (isset($data['code']))
		{
			$detail = isset($data['detail']) ? $data['detail'] : 'Unknown error';
			return array('success' => false, 'message' => sprintf('Achievements API error %d: %s. URL: %s', $data['code'], $detail, $achiev_url), 'count' => 0);
		}

		// Clear existing tracking data for this guild
		$db->sql_query('DELETE FROM ' . $this->bb_achievement_track_table . ' WHERE guild_id = ' . (int) $Guild->guildid);
		$db->sql_query('DELETE FROM ' . $this->bb_criteria_track_table . ' WHERE guild_id = ' . (int) $Guild->guildid);

		// Parse the new API response format
		// Response contains: achievements[] with { id, achievement { id, name, ... }, completed_timestamp, criteria { ... } }
		$achievements = isset($data['achievements']) ? $data['achievements'] : array();

		if (empty($achievements))
		{
			return array('success' => false, 'message' => sprintf('API response has no achievements array. Response keys: %s', implode(', ', array_keys($data))), 'count' => 0);
		}

		$track_rows = array();

		foreach ($achievements as $entry)
		{
			$achievement_id = isset($entry['achievement']['id']) ? (int) $entry['achievement']['id'] : 0;
			if ($achievement_id === 0)
			{
				continue;
			}

			$completed_ts = isset($entry['completed_timestamp']) ? (int) $entry['completed_timestamp'] : 0;

			$track_rows[] = array(
				'guild_id'               => (int) $Guild->guildid,
				'player_id'              => 0,
				'achievement_id'         => $achievement_id,
				'achievements_completed' => $completed_ts,
			);
		}

		if (!empty($track_rows))
		{
			$db->sql_multi_insert($this->bb_achievement_track_table, $track_rows);
		}

		// Update guild achievement points from the API total
		if (isset($data['total_points']))
		{
			$db->sql_query('UPDATE ' . $this->bb_guild_wow_table . ' SET achievementpoints = ' . (int) $data['total_points'] .
				' WHERE guild_id = ' . (int) $Guild->guildid);
		}

		$track_count = count($track_rows);

		// Insert basic achievement stubs from the guild response for any
		// achievements not yet in the detail table. This ensures the portal
		// module can display them immediately (title + date).
		$existing_ids = array();
		$sql = 'SELECT id FROM ' . $this->bb_achievement_table . " WHERE game_id = '" . $db->sql_escape($this->game->game_id) . "'";
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$existing_ids[(int) $row['id']] = true;
		}
		$db->sql_freeresult($result);

		$stub_rows = array();
		foreach ($achievements as $entry)
		{
			$aid = isset($entry['achievement']['id']) ? (int) $entry['achievement']['id'] : 0;
			if ($aid > 0 && !isset($existing_ids[$aid]))
			{
				$stub_rows[] = array(
					'id'          => $aid,
					'game_id'     => $this->game->game_id,
					'title'       => isset($entry['achievement']['name']) ? $entry['achievement']['name'] : '',
					'points'      => 0,
					'description' => '',
					'icon'        => '',
					'factionid'   => 2,
					'reward'      => '',
				);
				$existing_ids[$aid] = true;
			}
		}

		if (!empty($stub_rows))
		{
			$db->sql_multi_insert($this->bb_achievement_table, $stub_rows);
		}

		// Now fetch full details for achievements missing icon/points/description.
		// Use a time guard to stay within PHP's execution limit.
		$time_start = time();
		$time_limit = 20; // stop fetching after 20s to leave headroom

		$sql = 'SELECT id FROM ' . $this->bb_achievement_table .
			" WHERE game_id = '" . $db->sql_escape($this->game->game_id) . "'" .
			" AND icon = '' AND points = 0" .
			' ORDER BY id';
		$result = $db->sql_query($sql);

		$incomplete_ids = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$incomplete_ids[] = (int) $row['id'];
		}
		$db->sql_freeresult($result);

		// Reuse a single API instance for all detail fetches (one OAuth token)
		$detail_api = null;
		if (!empty($incomplete_ids))
		{
			$detail_api = new battlenet('achievement', $region, $apikey,
				$game->get_apilocale(), $privkey, '', $cache);
		}

		$detail_count = 0;
		foreach ($incomplete_ids as $achievement_id)
		{
			if ((time() - $time_start) >= $time_limit)
			{
				break;
			}

			$detail = $this->fetch_achievement_detail_from($detail_api, $achievement_id);
			if ($detail !== false)
			{
				$this->update_achievement_detail($detail);
				$detail_count++;
			}
		}
		unset($detail_api);

		// Count how many still need details
		$sql = 'SELECT COUNT(*) AS cnt FROM ' . $this->bb_achievement_table .
			" WHERE game_id = '" . $db->sql_escape($this->game->game_id) . "'" .
			" AND icon = '' AND points = 0";
		$result = $db->sql_query($sql);
		$remaining = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);

		$message = sprintf('Synced %d achievements, fetched details for %d.', $track_count, $detail_count);
		if ($remaining > 0)
		{
			$message .= sprintf(' %d achievements still need details — click "Load from API" again to fetch more.', $remaining);
		}

		return array(
			'success' => true,
			'message' => $message,
			'count'   => $track_count,
		);
	}

	/**
	 * Recursively collect criteria progress from the new API response.
	 *
	 * The new API nests criteria: each entry may have child_criteria[].
	 *
	 * @param array $criteria_data  Criteria node from API response
	 * @param int   $guild_id
	 * @param array &$rows          Accumulator for DB insert rows
	 */
	private function collect_criteria(array $criteria_data, int $guild_id, array &$rows): void
	{
		$criteria_id = isset($criteria_data['id']) ? (int) $criteria_data['id'] : 0;
		if ($criteria_id > 0)
		{
			$rows[] = array(
				'guild_id'           => (int) $guild_id,
				'player_id'          => 0,
				'criteria_id'        => $criteria_id,
				'criteria_quantity'   => isset($criteria_data['amount']) ? (int) $criteria_data['amount'] : 0,
				'criteria_timestamp' => isset($criteria_data['completed_timestamp']) ? (int) $criteria_data['completed_timestamp'] : 0,
				'criteria_created'   => isset($criteria_data['created_timestamp']) ? (int) $criteria_data['created_timestamp'] : 0,
			);
		}

		// Recurse into child criteria
		if (isset($criteria_data['child_criteria']) && is_array($criteria_data['child_criteria']))
		{
			foreach ($criteria_data['child_criteria'] as $child)
			{
				$this->collect_criteria($child, $guild_id, $rows);
			}
		}
	}

	/**
	 * Fetch achievement detail from the static Game Data API.
	 *
	 * @param int    $achievement_id
	 * @param game   $game
	 * @return array|false
	 */
	private function fetch_achievement_detail(int $achievement_id, game $game)
	{
		$cache = $this->cache;

		$api = new battlenet('achievement', $game->getRegion(), $game->getApikey(),
			$game->get_apilocale(), $game->get_privkey(), '', $cache);
		$response = $api->achievement->getAchievementDetail($achievement_id);
		unset($api);

		$data = isset($response['response']) ? $response['response'] : null;
		if (!isset($data) || !is_array($data) || isset($data['code']))
		{
			return false;
		}

		return $data;
	}

	/**
	 * Fetch achievement detail using an existing API instance (avoids re-creating OAuth per call).
	 *
	 * @param battlenet $api
	 * @param int       $achievement_id
	 * @return array|false
	 */
	private function fetch_achievement_detail_from(battlenet $api, int $achievement_id)
	{
		$response = $api->achievement->getAchievementDetail($achievement_id);

		$data = isset($response['response']) ? $response['response'] : null;
		if (!isset($data) || !is_array($data) || isset($data['code']))
		{
			return false;
		}

		return $data;
	}

	/**
	 * Insert an achievement into the local database.
	 *
	 * Maps the new Game Data API fields:
	 * - name (was: title)
	 * - description
	 * - points
	 * - reward_description (was: reward)
	 * - media.assets[0].value (icon URL, was: icon name)
	 * - requirements.faction.type (was: factionId)
	 *
	 * @param array $data Achievement detail from Game Data API
	 */
	private function insert_achievement(array $data): void
	{
		$db = $this->db;

		$this->id = isset($data['id']) ? (int) $data['id'] : 0;
		if ($this->id === 0)
		{
			return;
		}

		$this->game_id = $this->game->game_id;
		$this->title = isset($data['name']) ? $data['name'] : '';
		$this->points = isset($data['points']) ? (int) $data['points'] : 0;
		$this->description = isset($data['description']) ? $data['description'] : '';
		$this->reward = isset($data['reward_description']) ? $data['reward_description'] : '';

		// Icon: new API provides media.assets[] with key/value pairs.
		// The value is a full URL like https://render.worldofwarcraft.com/icons/56/achievement_boss.jpg
		// We store just the icon name (without path and extension) for flexible template rendering.
		$this->icon = '';
		if (isset($data['media']['assets']) && is_array($data['media']['assets']))
		{
			foreach ($data['media']['assets'] as $asset)
			{
				if (isset($asset['key']) && $asset['key'] === 'icon' && isset($asset['value']))
				{
					$icon_value = $asset['value'];
					// Extract icon name from URL: get filename without extension
					$basename = basename($icon_value);
					$this->icon = pathinfo($basename, PATHINFO_FILENAME);
					break;
				}
			}
		}

		// Faction: new API uses requirements.faction.type (ALLIANCE, HORDE, or absent for both)
		$this->factionId = 2; // default: both factions
		if (isset($data['requirements']['faction']['type']))
		{
			$faction_type = strtoupper($data['requirements']['faction']['type']);
			if ($faction_type === 'ALLIANCE')
			{
				$this->factionId = 0;
			}
			elseif ($faction_type === 'HORDE')
			{
				$this->factionId = 1;
			}
		}

		$sql_ary = array(
			'id'          => $this->id,
			'game_id'     => $this->game_id,
			'title'       => $this->title,
			'points'      => $this->points,
			'description' => $this->description,
			'factionid'   => $this->factionId,
			'icon'        => $this->icon,
			'reward'      => $this->reward,
		);

		$db->sql_query('INSERT INTO ' . $this->bb_achievement_table . ' ' . $db->sql_build_array('INSERT', $sql_ary));
	}

	/**
	 * Update an existing achievement stub with full detail from the API.
	 *
	 * @param array $data Achievement detail from Game Data API
	 */
	private function update_achievement_detail(array $data): void
	{
		$db = $this->db;

		$id = isset($data['id']) ? (int) $data['id'] : 0;
		if ($id === 0)
		{
			return;
		}

		$title = isset($data['name']) ? $data['name'] : '';
		$points = isset($data['points']) ? (int) $data['points'] : 0;
		$description = isset($data['description']) ? $data['description'] : '';
		$reward = isset($data['reward_description']) ? $data['reward_description'] : '';

		$icon = '';
		if (isset($data['media']['assets']) && is_array($data['media']['assets']))
		{
			foreach ($data['media']['assets'] as $asset)
			{
				if (isset($asset['key']) && $asset['key'] === 'icon' && isset($asset['value']))
				{
					$icon = pathinfo(basename($asset['value']), PATHINFO_FILENAME);
					break;
				}
			}
		}

		$factionId = 2;
		if (isset($data['requirements']['faction']['type']))
		{
			$faction_type = strtoupper($data['requirements']['faction']['type']);
			if ($faction_type === 'ALLIANCE')
			{
				$factionId = 0;
			}
			elseif ($faction_type === 'HORDE')
			{
				$factionId = 1;
			}
		}

		$sql_ary = array(
			'title'       => $title,
			'points'      => $points,
			'description' => $description,
			'factionid'   => $factionId,
			'icon'        => $icon,
			'reward'      => $reward,
		);

		$db->sql_query('UPDATE ' . $this->bb_achievement_table .
			' SET ' . $db->sql_build_array('UPDATE', $sql_ary) .
			' WHERE id = ' . $id);
	}

	/**
	 * Insert achievement criteria from the detail API into the database.
	 *
	 * The new API nests criteria under criteria.child_criteria[].
	 *
	 * @param array $data Achievement detail from Game Data API
	 */
	private function insert_criteria(array $data): void
	{
		$db = $this->db;

		if (!isset($data['criteria']) || !is_array($data['criteria']))
		{
			return;
		}

		$criteria_rows = array();
		$relation_rows = array();
		$this->flatten_criteria($data['criteria'], (int) $data['id'], $criteria_rows, $relation_rows, 0);

		if (!empty($criteria_rows))
		{
			$db->sql_multi_insert($this->bb_achievement_criteria_table, $criteria_rows);
		}

		if (!empty($relation_rows))
		{
			$db->sql_multi_insert($this->bb_relations_table, $relation_rows);
		}
	}

	/**
	 * Recursively flatten criteria tree for DB storage.
	 *
	 * @param array $node            Criteria node
	 * @param int   $achievement_id  Parent achievement ID
	 * @param array &$criteria_rows  Accumulator for criteria table
	 * @param array &$relation_rows  Accumulator for relations table
	 * @param int   $order           Order index counter
	 */
	private function flatten_criteria(array $node, int $achievement_id, array &$criteria_rows, array &$relation_rows, int $order): void
	{
		$db = $this->db;

		$criteria_id = isset($node['id']) ? (int) $node['id'] : 0;
		if ($criteria_id > 0)
		{
			$db->sql_query('DELETE FROM ' . $this->bb_achievement_criteria_table . ' WHERE criteria_id = ' . $criteria_id);

			$criteria_rows[] = array(
				'criteria_id' => $criteria_id,
				'description' => isset($node['description']) ? $node['description'] : '',
				'orderindex'  => $order,
				'max'         => isset($node['amount']) ? (int) $node['amount'] : 0,
			);

			$db->sql_query('DELETE FROM ' . $this->bb_relations_table .
				" WHERE attribute_id = 'ACH' AND rel_attr_id = 'CRI'" .
				" AND att_value = '" . $achievement_id . "'" .
				" AND rel_value = '" . $criteria_id . "'");

			$relation_rows[] = array(
				'attribute_id' => 'ACH',
				'rel_attr_id'  => 'CRI',
				'att_value'    => $achievement_id,
				'rel_value'    => $criteria_id,
			);
		}

		if (isset($node['child_criteria']) && is_array($node['child_criteria']))
		{
			foreach ($node['child_criteria'] as $idx => $child)
			{
				$this->flatten_criteria($child, $achievement_id, $criteria_rows, $relation_rows, $idx);
			}
		}
	}

	/**
	 * Sync achievement categories from the Battle.net Game Data API.
	 *
	 * Fetches the category index (root + child structure), truncates and
	 * re-inserts all categories, then fetches leaf category details to
	 * map achievements to categories.
	 *
	 * @param game $game
	 * @return array Result with 'success' (bool), 'message' (string), 'count' (int)
	 */
	public function syncCategories(game $game): array
	{
		$db = $this->db;
		$cache = $this->cache;

		if (!$game->getArmoryEnabled())
		{
			return array('success' => false, 'message' => 'Armory is not enabled for this game.', 'count' => 0);
		}

		$region = $game->getRegion();
		$apikey = $game->getApikey();
		$privkey = $game->get_privkey();

		if (empty($apikey) || empty($privkey))
		{
			return array('success' => false, 'message' => 'Battle.net API credentials not configured.', 'count' => 0);
		}

		$locale = $game->get_apilocale();

		// Fetch the category index
		$api = new battlenet('achievement-category', $region, $apikey, $locale, $privkey, '', $cache);
		$response = $api->achievement_category->getCategoryIndex();
		$data = isset($response['response']) ? $response['response'] : null;

		if (!is_array($data) || isset($data['code']))
		{
			$detail = isset($data['detail']) ? $data['detail'] : 'Unknown error';
			unset($api);
			return array('success' => false, 'message' => 'Category index API error: ' . $detail, 'count' => 0);
		}

		// Truncate existing categories
		$db->sql_query('DELETE FROM ' . $this->bb_achievement_category_table . " WHERE game_id = '" . $db->sql_escape($game->game_id) . "'");

		$categories = array();
		$insert_rows = array();
		$order = 0;

		// Root categories
		$root_cats = isset($data['root_categories']) ? $data['root_categories'] : (isset($data['categories']) ? $data['categories'] : array());
		foreach ($root_cats as $cat)
		{
			$cat_id = isset($cat['id']) ? (int) $cat['id'] : 0;
			if ($cat_id === 0)
			{
				continue;
			}
			$insert_rows[] = array(
				'id'            => $cat_id,
				'game_id'       => $game->game_id,
				'parent_id'     => 0,
				'name'          => isset($cat['name']) ? $cat['name'] : '',
				'display_order' => $order++,
			);
			$categories[$cat_id] = true;

			// Subcategories
			if (isset($cat['subcategories']) && is_array($cat['subcategories']))
			{
				$sub_order = 0;
				foreach ($cat['subcategories'] as $sub)
				{
					$sub_id = isset($sub['id']) ? (int) $sub['id'] : 0;
					if ($sub_id === 0)
					{
						continue;
					}
					$insert_rows[] = array(
						'id'            => $sub_id,
						'game_id'       => $game->game_id,
						'parent_id'     => $cat_id,
						'name'          => isset($sub['name']) ? $sub['name'] : '',
						'display_order' => $sub_order++,
					);
					$categories[$sub_id] = true;
				}
			}
		}

		// Also handle guild_categories and character_categories if present
		foreach (array('guild_categories', 'character_categories') as $cat_group)
		{
			if (!isset($data[$cat_group]) || !is_array($data[$cat_group]))
			{
				continue;
			}
			foreach ($data[$cat_group] as $cat)
			{
				$cat_id = isset($cat['id']) ? (int) $cat['id'] : 0;
				if ($cat_id === 0 || isset($categories[$cat_id]))
				{
					continue;
				}
				$insert_rows[] = array(
					'id'            => $cat_id,
					'game_id'       => $game->game_id,
					'parent_id'     => 0,
					'name'          => isset($cat['name']) ? $cat['name'] : '',
					'display_order' => $order++,
				);
				$categories[$cat_id] = true;
			}
		}

		if (!empty($insert_rows))
		{
			$db->sql_multi_insert($this->bb_achievement_category_table, $insert_rows);
		}

		$cat_count = count($insert_rows);

		// Now fetch detail for each leaf category to map achievements to categories.
		// Leaf categories are those that have no children (are not parent_id of any other).
		$parent_ids = array();
		foreach ($insert_rows as $row)
		{
			if ($row['parent_id'] > 0)
			{
				$parent_ids[$row['parent_id']] = true;
			}
		}

		// All categories that are NOT a parent are leaf categories
		$leaf_ids = array();
		foreach ($insert_rows as $row)
		{
			if (!isset($parent_ids[$row['id']]))
			{
				$leaf_ids[] = $row['id'];
			}
		}

		// Also include root categories that have no subcategories (they are their own leaf)
		$time_start = time();
		$time_limit = 20;
		$mapped_count = 0;

		foreach ($leaf_ids as $leaf_id)
		{
			if ((time() - $time_start) >= $time_limit)
			{
				break;
			}

			$detail_response = $api->achievement_category->getCategoryDetail($leaf_id);
			$detail_data = isset($detail_response['response']) ? $detail_response['response'] : null;

			if (!is_array($detail_data) || isset($detail_data['code']))
			{
				continue;
			}

			$achievement_ids = array();
			if (isset($detail_data['achievements']) && is_array($detail_data['achievements']))
			{
				foreach ($detail_data['achievements'] as $ach)
				{
					$aid = isset($ach['id']) ? (int) $ach['id'] : 0;
					if ($aid > 0)
					{
						$achievement_ids[] = $aid;
					}
				}
			}

			if (!empty($achievement_ids))
			{
				$db->sql_query('UPDATE ' . $this->bb_achievement_table .
					' SET category_id = ' . (int) $leaf_id .
					' WHERE ' . $db->sql_in_set('id', $achievement_ids));
				$mapped_count += count($achievement_ids);
			}
		}

		unset($api);

		$message = sprintf('Synced %d categories, mapped %d achievements.', $cat_count, $mapped_count);

		return array(
			'success' => true,
			'message' => $message,
			'count'   => $cat_count,
		);
	}

	/**
	 * Get per-root-category achievement progress for a guild.
	 *
	 * Returns an array of root categories with total/earned points and counts.
	 *
	 * @param int $guild_id
	 * @return array
	 */
	public function getCategoryProgress(int $guild_id): array
	{
		$db = $this->db;

		$sql = 'SELECT ac.id, ac.name, ac.display_order,
				COUNT(a.id) AS total_count,
				COUNT(at.achievement_id) AS completed_count,
				SUM(a.points) AS total_points,
				COALESCE(SUM(CASE WHEN at.achievement_id IS NOT NULL THEN a.points ELSE 0 END), 0) AS earned_points
			FROM ' . $this->bb_achievement_category_table . ' ac
			INNER JOIN ' . $this->bb_achievement_category_table . ' child
				ON (child.parent_id = ac.id OR child.id = ac.id)
			INNER JOIN ' . $this->bb_achievement_table . ' a
				ON a.category_id = child.id AND a.game_id = \'wow\'
			LEFT JOIN ' . $this->bb_achievement_track_table . ' at
				ON at.achievement_id = a.id AND at.guild_id = ' . (int) $guild_id . '
			WHERE ac.parent_id = 0 AND ac.game_id = \'wow\'
			GROUP BY ac.id, ac.name, ac.display_order
			ORDER BY ac.display_order';
		$result = $db->sql_query($sql);

		$categories = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$categories[] = array(
				'id'              => (int) $row['id'],
				'name'            => $row['name'],
				'display_order'   => (int) $row['display_order'],
				'total_count'     => (int) $row['total_count'],
				'completed_count' => (int) $row['completed_count'],
				'total_points'    => (int) $row['total_points'],
				'earned_points'   => (int) $row['earned_points'],
			);
		}
		$db->sql_freeresult($result);

		return $categories;
	}

	/**
	 * Create a URL-safe slug from a name.
	 *
	 * Battle.net slugs are lowercase, spaces become hyphens, accented characters
	 * are transliterated, and apostrophes/special characters are removed.
	 *
	 * @param string $name
	 * @return string
	 */
	private function make_slug(string $name): string
	{
		$slug = trim($name);
		// Blizzard slugs are lowercase with accents preserved (bête-noire, not bete-noire)
		$slug = mb_strtolower($slug, 'UTF-8');
		// Replace spaces with hyphens
		$slug = str_replace(' ', '-', $slug);
		// Remove apostrophes and other punctuation, but keep letters (including accented), digits, hyphens
		$slug = preg_replace('/[^\p{L}\p{N}\-]/u', '', $slug);
		// Collapse multiple hyphens
		$slug = preg_replace('/-+/', '-', $slug);
		return trim($slug, '-');
	}
}
