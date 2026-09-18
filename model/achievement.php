<?php
/**
 * This file holds the Achievement API class
 *
 * @package   bbguildwow v2.0
 * @copyright 2018 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace avathar\bbguildwow\model;

use avathar\bbguildwow\api\battlenet;
use avathar\bbguild\model\games\game;
use avathar\bbguild\model\player\guilds;
use phpbb\language\language;

/**
 * This provides data about an individual achievement.
 *
 * @package avathar\bbguildwow\model
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

	/**
	 * Game edition for API namespace selection.
	 * @var string
	 */
	private $edition = 'retail';

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
	 * @see \avathar\bbguildwow\game\wow_api::create_battlenet() — identical
	 * seam, duplicated here because achievement has no shared base with wow_api.
	 */
	protected function create_battlenet(string $api, string $region, string $apikey, string $locale, string $privkey, string $ext_path = '', int $cache_ttl = 3600, string $edition = 'retail'): battlenet
	{
		return new battlenet($api, $region, $apikey, $locale, $privkey, $ext_path, $this->cache, $cache_ttl, $edition);
	}

	/**
	 * @var language|null Set via set_language() — optional so unit/integration
	 *                    tests that construct achievement directly (no DI
	 *                    container) keep working against the fallback
	 *                    strings in lang().
	 */
	private $language;

	/**
	 * Setter-injected (not a constructor arg) so existing call sites —
	 * including several tests that construct achievement directly — don't
	 * need updating. See lang().
	 *
	 * @param language $language
	 */
	public function set_language(language $language): void
	{
		$this->language = $language;
	}

	/**
	 * Resolve a user-facing message through phpBB's language framework when
	 * available, falling back to the English text below otherwise (e.g. in
	 * tests that construct this class without a DI container and never call
	 * set_language()). Keep LANG_FALLBACK in sync with the matching keys in
	 * language/en/wow.php.
	 *
	 * @param string $key
	 * @param mixed  ...$args
	 * @return string
	 */
	private function lang(string $key, ...$args): string
	{
		if ($this->language !== null)
		{
			$this->language->add_lang('wow', 'avathar/bbguildwow');
			return $args ? $this->language->lang($key, ...$args) : $this->language->lang($key);
		}

		$fallback = self::LANG_FALLBACK[$key] ?? $key;
		return $args ? vsprintf($fallback, $args) : $fallback;
	}

	private const LANG_FALLBACK = array(
		'WOW_ACH_ARMORY_DISABLED_GAME'    => 'Armory is not enabled for this game. Enable it in ACP Game settings.',
		'WOW_ACH_ARMORY_DISABLED_GUILD'   => 'Armory is not enabled for this guild. Enable it in ACP Guild settings.',
		'WOW_ACH_CREDENTIALS_MISSING'     => 'Battle.net API credentials not configured. Set Client ID and Secret in ACP Game settings.',
		'WOW_ACH_GUILD_SLUG_EMPTY'        => 'Guild realm or name is empty (realm="%s", name="%s"). Check guild settings.',
		'WOW_ACH_API_ERROR_DETAIL'        => 'API error %d: %s',
		'WOW_ACH_UNKNOWN_SHORT'           => 'Unknown',
		'WOW_ACH_EMPTY_RESPONSE_HTTP'     => 'Empty response (HTTP %s)',
		'WOW_ACH_GUILD_NOT_FOUND_DETAIL'  => '%s. Could not find guild "%s" on realm "%s" (region: %s). Request URL: %s',
		'WOW_ACH_API_EMPTY_RESPONSE'      => 'Achievements API returned empty response (HTTP %s). %s URL: %s',
		'WOW_ACH_UNKNOWN_ERROR'           => 'Unknown error',
		'WOW_ACH_API_ERROR'               => 'Achievements API error %d: %s. URL: %s',
		'WOW_ACH_NO_ACHIEVEMENTS_ARRAY'   => 'API response has no achievements array. Response keys: %s',
		'WOW_ACH_SYNCED_RESULT'           => 'Synced %d achievements, fetched details for %d.',
		'WOW_ACH_REMAINING_DETAILS'       => ' %d achievements still need details — click "Load from API" again to fetch more.',
		'WOW_ACH_CAT_ARMORY_DISABLED'     => 'Armory is not enabled for this game.',
		'WOW_ACH_CAT_CREDENTIALS_MISSING' => 'Battle.net API credentials not configured.',
		'WOW_ACH_CAT_API_ERROR'           => 'Category index API error: %s',
		'WOW_ACH_CAT_SYNCED_RESULT'       => 'Synced %d categories, inserted %d new achievements, mapped %d.',
		'WOW_ACH_CAT_REMAINING'           => ' %d achievements still need category mapping — click "Sync Categories" again.',
		'WOW_ACHIEVEMENTS_UNCATEGORIZED'  => 'Uncategorized',
	);

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
	 * @param string $edition
	 */
	public function setEdition(string $edition)
	{
		$this->edition = $edition;
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
			'WHERE' =>  'a.id = ac.achievement_id AND a.id = ' . (int) $this->id . " AND a.game_id = '". $db->sql_escape($this->game_id) . "'" ,
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
	 * @param        $start
	 * @param        $guild_id
	 * @param int    $player_id
	 * @param bool   $completed_only When true, excludes tracked-but-not-
	 *               yet-completed rows from both the count and the page
	 *               (bbguildwow#44's achievements tab only ever displays
	 *               completed ones — added so its pagination total isn't
	 *               inflated by in-progress rows it filters out anyway).
	 *               Defaults false to preserve the ACP achievement list's
	 *               existing behaviour (acp/achievement_module.php).
	 * @param string $default_order util::switch_order()'s default sort
	 *               (element1.element2 — see $sort_order below for the
	 *               index map), used only when the request has no
	 *               explicit `o` param of its own. Defaults to '0.0'
	 *               (id ascending), same as before this param existed;
	 *               the achievements tab passes '4.1' (completion date
	 *               descending) so paginated pages are genuinely the
	 *               next-most-recent 15, not just an id-ordered page
	 *               re-sorted internally.
	 * @return array
	 */
	public function get_tracked_achievements($start, $guild_id, $player_id = 0, $completed_only = false, $default_order = '0.0')
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

		if ($completed_only)
		{
			$owner_filter .= ' AND ac.achievements_completed > 0';
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
		$current_order = $this->util->switch_order($sort_order, \avathar\bbguild\model\admin\constants::URI_ORDER, $default_order);

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
	 * Store one character's completed achievements from a Battle.net
	 * character-achievements API response (bbguildwow#44's sync side).
	 * Mirrors setAchievements()'s response parsing, scoped to player_id
	 * instead of guild_id -- track_rows carry guild_id=0/player_id=X,
	 * the mirror image of setAchievements()'s guild_id=X/player_id=0, per
	 * get_tracked_achievements()'s "not both with a zero that matches
	 * everything" owner-filter contract.
	 *
	 * Unlike setAchievements(), this does NOT fetch full achievement
	 * detail (title/points/description/icon) for catalog entries it
	 * doesn't already know about -- that's a separate, expensive,
	 * time-budgeted step already owned by the guild-level sync. It does
	 * still insert minimal stub rows (ensure_achievement_stubs()) so a
	 * character's achievement isn't silently dropped by
	 * get_tracked_achievements()'s INNER JOIN against the catalog table
	 * just because no guild sync has run yet.
	 *
	 * @param int   $player_id
	 * @param array $data Parsed 'response' body from
	 *                    battlenet_character::getCharacterAchievements()
	 * @return array{success: bool, count: int}
	 */
	public function set_player_achievements(int $player_id, array $data): array
	{
		$achievements = isset($data['achievements']) ? $data['achievements'] : array();
		if (empty($achievements))
		{
			return array('success' => false, 'count' => 0);
		}

		$this->db->sql_query('DELETE FROM ' . $this->bb_achievement_track_table . ' WHERE player_id = ' . $player_id);

		$track_rows = array();
		foreach ($achievements as $entry)
		{
			$achievement_id = isset($entry['achievement']['id']) ? (int) $entry['achievement']['id'] : 0;
			if ($achievement_id === 0)
			{
				continue;
			}

			$track_rows[] = array(
				'guild_id'               => 0,
				'player_id'              => $player_id,
				'achievement_id'         => $achievement_id,
				'achievements_completed' => isset($entry['completed_timestamp']) ? (int) $entry['completed_timestamp'] : 0,
			);
		}

		if (!empty($track_rows))
		{
			$this->db->sql_multi_insert($this->bb_achievement_track_table, $track_rows);
			$this->ensure_achievement_stubs($achievements);
		}

		return array('success' => true, 'count' => count($track_rows));
	}

	/**
	 * Insert minimal achievement-catalog rows (id/game_id/title only) for
	 * any achievement id referenced by an API response that isn't already
	 * in the catalog table, so get_tracked_achievements()'s INNER JOIN
	 * against it doesn't silently drop a real tracked row. Full detail
	 * (points/description/icon) is filled in separately, on a time
	 * budget, by setAchievements()'s own detail-fetch pass -- this only
	 * guarantees *something* displayable (title + date) exists immediately.
	 *
	 * @param array $achievements Raw 'achievements' array from a Battle.net
	 *                            guild- or character-achievements response
	 */
	private function ensure_achievement_stubs(array $achievements): void
	{
		$db = $this->db;

		$existing_ids = array();
		$sql = 'SELECT id FROM ' . $this->bb_achievement_table . " WHERE game_id = '" . $db->sql_escape($this->game_id) . "'";
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
					'game_id'     => $this->game_id,
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
	}


	/**
	 * Sync guild achievements from the Battle.net Game Data API.
	 *
	 * Uses the new Game Data API endpoints:
	 * - Guild achievements: GET /data/wow/guild/{realm}/{name}/achievements (profile namespace)
	 * - Achievement detail: GET /data/wow/achievement/{id} (static namespace)
	 *
	 * @param guilds $Guild
	 * @param game    $game
	 * @return array Result with 'success' (bool), 'message' (string), 'count' (int)
	 */
	public function setAchievements(guilds $Guild, game $game): array
	{
		$db = $this->db;

		if (!$game->getArmoryEnabled())
		{
			return array('success' => false, 'message' => $this->lang('WOW_ACH_ARMORY_DISABLED_GAME'), 'count' => 0);
		}

		if (!$Guild->isArmoryEnabled())
		{
			return array('success' => false, 'message' => $this->lang('WOW_ACH_ARMORY_DISABLED_GUILD'), 'count' => 0);
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
			return array('success' => false, 'message' => $this->lang('WOW_ACH_CREDENTIALS_MISSING'), 'count' => 0);
		}

		$realm_slug = $this->make_slug($Guild->getRealm());
		$name_slug = $this->make_slug($Guild->getName());

		if (empty($realm_slug) || empty($name_slug))
		{
			return array('success' => false, 'message' => $this->lang('WOW_ACH_GUILD_SLUG_EMPTY', $Guild->getRealm(), $Guild->getName()), 'count' => 0);
		}

		$locale = $game->get_apilocale();

		// First verify the guild exists by fetching the basic guild profile
		$api = $this->create_battlenet('guild', $region, $apikey, $locale, $privkey, '', 3600, $this->edition);

		$guild_response = $api->guild->getGuild($realm_slug, $name_slug);
		$guild_data = isset($guild_response['response']) ? $guild_response['response'] : array();

		if (empty($guild_data) || !is_array($guild_data) || isset($guild_data['code']))
		{
			$http_code = isset($guild_response['response_headers']['http_code']) ? $guild_response['response_headers']['http_code'] : 'unknown';
			$request_url = isset($guild_response['request_url']) ? $guild_response['request_url'] : 'unknown';
			$error_detail = '';
			if (isset($guild_data['code']))
			{
				$error_detail = $this->lang('WOW_ACH_API_ERROR_DETAIL', $guild_data['code'], isset($guild_data['detail']) ? $guild_data['detail'] : $this->lang('WOW_ACH_UNKNOWN_SHORT'));
			}
			else
			{
				$error_detail = $this->lang('WOW_ACH_EMPTY_RESPONSE_HTTP', $http_code);
			}
			unset($api);
			return array('success' => false, 'message' => $this->lang(
				'WOW_ACH_GUILD_NOT_FOUND_DETAIL',
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
			return array('success' => false, 'message' => $this->lang('WOW_ACH_API_EMPTY_RESPONSE', $http_code, $error, $achiev_url), 'count' => 0);
		}

		if (isset($data['code']))
		{
			$detail = isset($data['detail']) ? $data['detail'] : $this->lang('WOW_ACH_UNKNOWN_ERROR');
			return array('success' => false, 'message' => $this->lang('WOW_ACH_API_ERROR', $data['code'], $detail, $achiev_url), 'count' => 0);
		}

		// Clear existing tracking data for this guild
		$db->sql_query('DELETE FROM ' . $this->bb_achievement_track_table . ' WHERE guild_id = ' . (int) $Guild->guildid);
		$db->sql_query('DELETE FROM ' . $this->bb_criteria_track_table . ' WHERE guild_id = ' . (int) $Guild->guildid);

		// Parse the new API response format
		// Response contains: achievements[] with { id, achievement { id, name, ... }, completed_timestamp, criteria { ... } }
		$achievements = isset($data['achievements']) ? $data['achievements'] : array();

		if (empty($achievements))
		{
			return array('success' => false, 'message' => $this->lang('WOW_ACH_NO_ACHIEVEMENTS_ARRAY', implode(', ', array_keys($data))), 'count' => 0);
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

		$this->ensure_achievement_stubs($achievements);

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
			$detail_api = $this->create_battlenet('achievement', $region, $apikey,
				$game->get_apilocale(), $privkey, '', 3600, $this->edition);
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

		$message = $this->lang('WOW_ACH_SYNCED_RESULT', $track_count, $detail_count);
		if ($remaining > 0)
		{
			$message .= $this->lang('WOW_ACH_REMAINING_DETAILS', $remaining);
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
		$api = $this->create_battlenet('achievement', $game->getRegion(), $game->getApikey(),
			$game->get_apilocale(), $game->get_privkey(), '', 3600, $this->edition);
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
			else if ($faction_type === 'HORDE')
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

		// Set category_id from the API response if available
		if (isset($data['category']['id']))
		{
			$sql_ary['category_id'] = (int) $data['category']['id'];
		}

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
			else if ($faction_type === 'HORDE')
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

		// Set category_id from the API response if available
		if (isset($data['category']['id']))
		{
			$sql_ary['category_id'] = (int) $data['category']['id'];
		}

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

		if (!$game->getArmoryEnabled())
		{
			return array('success' => false, 'message' => $this->lang('WOW_ACH_CAT_ARMORY_DISABLED'), 'count' => 0);
		}

		$region = $game->getRegion();
		$apikey = $game->getApikey();
		$privkey = $game->get_privkey();

		if (empty($apikey) || empty($privkey))
		{
			return array('success' => false, 'message' => $this->lang('WOW_ACH_CAT_CREDENTIALS_MISSING'), 'count' => 0);
		}

		$locale = $game->get_apilocale();

		// Fetch the category index
		$api = $this->create_battlenet('achievement-category', $region, $apikey, $locale, $privkey, '', 3600, $this->edition);
		$response = $api->achievement_category->getCategoryIndex();
		$data = isset($response['response']) ? $response['response'] : null;

		if (!is_array($data) || isset($data['code']))
		{
			$detail = isset($data['detail']) ? $data['detail'] : $this->lang('WOW_ACH_UNKNOWN_ERROR');
			unset($api);
			return array('success' => false, 'message' => $this->lang('WOW_ACH_CAT_API_ERROR', $detail), 'count' => 0);
		}

		// Categories are NOT truncated and rebuilt from scratch on every
		// run -- a child discovered via getCategoryDetail() below (see
		// "Character-scope only" further down) needs to survive into the
		// next run, both so it doesn't have to be rediscovered from
		// scratch every time (this call is already 20s-time-budgeted)
		// and so achievements already mapped to it don't end up pointing
		// at a category row that no longer exists. Existing rows are
		// loaded up front instead, and only genuinely new ones get
		// inserted below.
		$categories = array();
		// Ids whose subtree is character-scope (root_categories and their
		// real descendants) rather than guild-scope (guild_categories /
		// character_categories). Only character-scope leaves get their
		// real children auto-discovered below -- the guild-scope branch's
		// existing flat layout is left exactly as-is (out of scope here;
		// its achievements are already correctly mapped today, just not
		// grouped under their real parent).
		$character_scope = array();
		// parent_id of every category already known, from a prior run --
		// merged with this run's own $insert_rows further down to compute
		// the full, current leaf set (a leaf newly discovered in a
		// previous run must not be treated as a "leaf candidate" again
		// once ITS OWN children exist).
		$existing_parent_of = array();

		$sql = 'SELECT id, parent_id, is_guild_category FROM ' . $this->bb_achievement_category_table .
			' WHERE game_id = \'' . $db->sql_escape($game->game_id) . '\'';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$cat_id = (int) $row['id'];
			$categories[$cat_id] = true;
			$existing_parent_of[$cat_id] = (int) $row['parent_id'];
			if (!$row['is_guild_category'])
			{
				$character_scope[$cat_id] = true;
			}
		}
		$db->sql_freeresult($result);

		$insert_rows = array();
		$order = 0;

		// Root categories. The Index's own `subcategories` field is
		// unreliable -- it reports 0 children for categories that
		// getCategoryDetail() shows clearly have them (confirmed live:
		// "Dungeons & Raids" reports subcategories=0 here but 22 via
		// Detail) -- so real children are discovered below, via Detail,
		// same as achievement mapping already was.
		$root_cats = isset($data['root_categories']) ? $data['root_categories'] : (isset($data['categories']) ? $data['categories'] : array());
		foreach ($root_cats as $cat)
		{
			$cat_id = isset($cat['id']) ? (int) $cat['id'] : 0;
			if ($cat_id === 0)
			{
				continue;
			}
			$character_scope[$cat_id] = true;
			if (isset($categories[$cat_id]))
			{
				// Already known from a previous run -- don't re-insert
				// (the id is the primary key), just keep its display
				// order for the ordering pass below.
				$order++;
				continue;
			}
			$insert_rows[] = array(
				'id'                 => $cat_id,
				'game_id'            => $game->game_id,
				'parent_id'          => 0,
				'name'               => isset($cat['name']) ? $cat['name'] : '',
				'display_order'      => $order++,
				'is_guild_category'  => 0,
			);
			$categories[$cat_id] = true;
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
					'id'                 => $cat_id,
					'game_id'            => $game->game_id,
					'parent_id'          => 0,
					'name'               => isset($cat['name']) ? $cat['name'] : '',
					'display_order'      => $order++,
					'is_guild_category'  => 1,
				);
				$categories[$cat_id] = true;
			}
		}

		if (!empty($insert_rows))
		{
			$db->sql_multi_insert($this->bb_achievement_category_table, $insert_rows);
		}

		// Total categories known after this run -- existing rows plus
		// whatever's new above; incremented further below as the leaf
		// loop discovers real children.
		$cat_count = count($categories);

		// Now fetch detail for each leaf category to map achievements to
		// categories, and (character-scope only) discover its real
		// children from the same response's `subcategories` field, so a
		// later sync run picks them up as leaves in their own right.
		// Leaf categories are those that have no children (are not
		// parent_id of any other) among the FULL known set -- existing
		// rows from a previous run plus whatever's newly inserted this
		// run -- not just this run's own inserts, or a category whose
		// children were discovered in a prior run would wrongly be
		// treated as a leaf again here.
		$parent_id_of = $existing_parent_of;
		foreach ($insert_rows as $row)
		{
			$parent_id_of[$row['id']] = $row['parent_id'];
		}

		$parent_ids = array();
		foreach ($parent_id_of as $id => $parent_id)
		{
			if ($parent_id > 0)
			{
				$parent_ids[$parent_id] = true;
			}
		}

		// All categories that are NOT a parent are leaf categories
		$leaf_ids = array();
		foreach ($parent_id_of as $id => $parent_id)
		{
			if (!isset($parent_ids[$id]))
			{
				$leaf_ids[] = $id;
			}
		}

		// Also include root categories that have no subcategories (they are their own leaf)
		// Skip leaf categories where ALL local achievements already have this category_id,
		// so re-running covers more ground instead of repeating the same leaves.
		$already_mapped = array();
		$sql = 'SELECT category_id, COUNT(*) AS cnt FROM ' . $this->bb_achievement_table .
			" WHERE game_id = '" . $db->sql_escape($game->game_id) . "' AND category_id > 0" .
			' GROUP BY category_id';
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$already_mapped[(int) $row['category_id']] = (int) $row['cnt'];
		}
		$db->sql_freeresult($result);

		// Put unmapped leaves first so new categories get processed before revisiting old ones
		$unmapped_leaves = array();
		$mapped_leaves = array();
		foreach ($leaf_ids as $leaf_id)
		{
			if (isset($already_mapped[$leaf_id]))
			{
				$mapped_leaves[] = $leaf_id;
			}
			else
			{
				$unmapped_leaves[] = $leaf_id;
			}
		}
		$ordered_leaves = array_merge($unmapped_leaves, $mapped_leaves);

		// Load existing achievement IDs so we can insert stubs for missing ones.
		// PK is just `id` (not composite with game_id), so check all IDs regardless of game.
		$existing_ids = array();
		$sql = 'SELECT id FROM ' . $this->bb_achievement_table;
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$existing_ids[(int) $row['id']] = true;
		}
		$db->sql_freeresult($result);

		$time_start = time();
		$time_limit = 20;
		$mapped_count = 0;
		$inserted_count = 0;

		foreach ($ordered_leaves as $leaf_id)
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
			$stub_rows = array();
			if (isset($detail_data['achievements']) && is_array($detail_data['achievements']))
			{
				foreach ($detail_data['achievements'] as $ach)
				{
					$aid = isset($ach['id']) ? (int) $ach['id'] : 0;
					if ($aid === 0)
					{
						continue;
					}
					$achievement_ids[] = $aid;

					// Insert stub for achievements not yet in the table
					if (!isset($existing_ids[$aid]))
					{
						$stub_rows[] = array(
							'id'          => $aid,
							'game_id'     => $game->game_id,
							'title'       => isset($ach['name']) ? $ach['name'] : '',
							'points'      => 0,
							'description' => '',
							'icon'        => '',
							'factionid'   => 2,
							'reward'      => '',
							'category_id' => (int) $leaf_id,
						);
						$existing_ids[$aid] = true;
					}
				}
			}

			if (!empty($stub_rows))
			{
				$db->sql_multi_insert($this->bb_achievement_table, $stub_rows);
				$inserted_count += count($stub_rows);
			}

			if (!empty($achievement_ids))
			{
				// Update category_id for achievements already in the table
				$db->sql_query('UPDATE ' . $this->bb_achievement_table .
					' SET category_id = ' . (int) $leaf_id .
					' WHERE ' . $db->sql_in_set('id', $achievement_ids) .
					' AND category_id <> ' . (int) $leaf_id);
				$mapped_count += count($achievement_ids);
			}

			// Character-scope only: discover this leaf's real children
			// (Detail's `subcategories`, unlike the Index's, actually
			// lists them) so a later sync run maps their achievements
			// under their real parent instead of leaving them unmapped.
			if (isset($character_scope[$leaf_id]) && isset($detail_data['subcategories']) && is_array($detail_data['subcategories']))
			{
				$child_rows = array();
				$sub_order = 0;
				foreach ($detail_data['subcategories'] as $sub)
				{
					$sub_id = isset($sub['id']) ? (int) $sub['id'] : 0;
					if ($sub_id === 0 || isset($categories[$sub_id]))
					{
						continue;
					}
					$child_rows[] = array(
						'id'                => $sub_id,
						'game_id'           => $game->game_id,
						'parent_id'         => $leaf_id,
						'name'              => isset($sub['name']) ? $sub['name'] : '',
						'display_order'     => $sub_order++,
						'is_guild_category' => 0,
					);
					$categories[$sub_id] = true;
					$character_scope[$sub_id] = true;
				}

				if (!empty($child_rows))
				{
					$db->sql_multi_insert($this->bb_achievement_category_table, $child_rows);
					$cat_count += count($child_rows);
				}
			}
		}

		unset($api);

		// Count achievements still without a category
		$sql = 'SELECT COUNT(*) AS cnt FROM ' . $this->bb_achievement_table .
			" WHERE game_id = '" . $db->sql_escape($game->game_id) . "' AND category_id = 0";
		$result = $db->sql_query($sql);
		$unmapped_remaining = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);

		$message = $this->lang('WOW_ACH_CAT_SYNCED_RESULT', $cat_count, $inserted_count, $mapped_count);
		if ($unmapped_remaining > 0)
		{
			$message .= $this->lang('WOW_ACH_CAT_REMAINING', $unmapped_remaining);
		}

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
				SUM(CASE WHEN at.achievements_completed > 0 THEN 1 ELSE 0 END) AS completed_count,
				SUM(a.points) AS total_points,
				COALESCE(SUM(CASE WHEN at.achievements_completed > 0 THEN a.points ELSE 0 END), 0) AS earned_points
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
	 * Character-scope achievement categories for the current game as a
	 * flat id-keyed list (id, name, parent_id) -- excludes the guild-scope
	 * branch (is_guild_category) entirely, matching Blizzard's own
	 * character armory page, which never shows a "Guild" section.
	 * Insertion order matches display_order (the query's ORDER BY), which
	 * get_player_achievement_tree() relies on to avoid a second sort.
	 *
	 * @return array id => ['id', 'name', 'parent_id', 'display_order']
	 */
	private function get_character_category_tree(): array
	{
		$db = $this->db;

		$sql = 'SELECT id, name, parent_id, display_order FROM ' . $this->bb_achievement_category_table .
			' WHERE game_id = \'' . $db->sql_escape($this->game_id) . '\' AND is_guild_category = 0' .
			' ORDER BY display_order';
		$result = $db->sql_query($sql);

		$categories = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$categories[(int) $row['id']] = array(
				'id'            => (int) $row['id'],
				'name'          => $row['name'],
				'parent_id'     => (int) $row['parent_id'],
				'display_order' => (int) $row['display_order'],
			);
		}
		$db->sql_freeresult($result);

		return $categories;
	}

	/**
	 * A player's per-category achievement totals, own achievements only
	 * (not rolled up to ancestors -- get_player_achievement_tree() does
	 * that walk). Character-scope categories only.
	 *
	 * @param int $player_id
	 * @return array category_id => ['total_count', 'completed_count', 'total_points', 'earned_points']
	 */
	private function get_player_category_totals(int $player_id): array
	{
		$db = $this->db;

		$sql = 'SELECT a.category_id,
				COUNT(*) AS total_count,
				SUM(CASE WHEN at.achievements_completed > 0 THEN 1 ELSE 0 END) AS completed_count,
				SUM(a.points) AS total_points,
				COALESCE(SUM(CASE WHEN at.achievements_completed > 0 THEN a.points ELSE 0 END), 0) AS earned_points
			FROM ' . $this->bb_achievement_table . ' a
			INNER JOIN ' . $this->bb_achievement_category_table . ' cat
				ON cat.id = a.category_id AND cat.is_guild_category = 0
			LEFT JOIN ' . $this->bb_achievement_track_table . ' at
				ON at.achievement_id = a.id AND at.player_id = ' . $player_id . '
			WHERE a.game_id = \'' . $db->sql_escape($this->game_id) . '\'
			GROUP BY a.category_id';
		$result = $db->sql_query($sql);

		$totals = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$totals[(int) $row['category_id']] = array(
				'total_count'     => (int) $row['total_count'],
				'completed_count' => (int) $row['completed_count'],
				'total_points'    => (int) $row['total_points'],
				'earned_points'   => (int) $row['earned_points'],
			);
		}
		$db->sql_freeresult($result);

		return $totals;
	}

	/**
	 * A player's completed achievements, each carrying its own leaf
	 * category_id (not rolled up). Character-scope categories only, plus
	 * genuinely uncategorized ones (category_id=0, e.g. a stub row from
	 * ensure_achievement_stubs() before a full detail sync has run) --
	 * those are kept (not silently dropped) rather than excluded like the
	 * guild-scope branch is.
	 *
	 * @param int $player_id
	 * @return array
	 */
	private function get_player_completed_achievements(int $player_id): array
	{
		$db = $this->db;

		$sql = 'SELECT a.title, a.description, a.points, a.icon, a.category_id, ac.achievements_completed
			FROM ' . $this->bb_achievement_track_table . ' ac
			INNER JOIN ' . $this->bb_achievement_table . ' a ON a.id = ac.achievement_id
			LEFT JOIN ' . $this->bb_achievement_category_table . ' cat ON cat.id = a.category_id
			WHERE ac.player_id = ' . $player_id . '
				AND ac.achievements_completed > 0
				AND a.game_id = \'' . $db->sql_escape($this->game_id) . '\'
				AND (cat.id IS NULL OR cat.is_guild_category = 0)
			ORDER BY ac.achievements_completed DESC';
		$result = $db->sql_query($sql);

		$rows = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$rows[] = array(
				'title'                  => $row['title'],
				'description'            => $row['description'],
				'points'                 => (int) $row['points'],
				'icon'                   => $row['icon'],
				'category_id'            => (int) $row['category_id'],
				'achievements_completed' => (int) $row['achievements_completed'],
			);
		}
		$db->sql_freeresult($result);

		return $rows;
	}

	/**
	 * A player's character-scope achievement categories as a tree: one
	 * node per root category, each carrying its own completed
	 * achievements plus, recursively, any real children -- however deep
	 * Blizzard's actual category tree happens to go for that branch (in
	 * practice almost always exactly one level, confirmed by inspecting
	 * the live API, but the walk itself doesn't assume that -- it stops
	 * naturally once a node has no children, whatever the real depth).
	 * A node with total_count=0 (nothing in the catalog for it or its
	 * children) is dropped rather than shown as an empty card/tab.
	 *
	 * @param int $player_id
	 * @return array list of root nodes, each:
	 *     ['id', 'name', 'total_count', 'completed_count', 'total_points',
	 *      'earned_points', 'percent', 'achievements' => [...], 'children' => [...same shape]]
	 */
	public function get_player_achievement_tree(int $player_id): array
	{
		$categories = $this->get_character_category_tree();
		$totals = $this->get_player_category_totals($player_id);
		$rows = $this->get_player_completed_achievements($player_id);

		$achievements_by_category = array();
		foreach ($rows as $row)
		{
			$achievements_by_category[$row['category_id']][] = $row;
		}

		// Built by iterating $categories, which is already in
		// display_order -- so every children_by_parent[$id] list (roots
		// included) comes out pre-sorted, no extra sort needed.
		$children_by_parent = array();
		foreach ($categories as $cat)
		{
			$children_by_parent[$cat['parent_id']][] = $cat['id'];
		}

		$build = function ($cat_id, $depth) use (&$build, $categories, $totals, $achievements_by_category, $children_by_parent) {
			// Defensive cap -- real data never goes anywhere near this
			// deep (confirmed at most 2 levels live), this just stops a
			// malformed/cyclic parent_id chain from looping forever.
			if ($depth > 8 || !isset($categories[$cat_id]))
			{
				return null;
			}

			$own = isset($totals[$cat_id]) ? $totals[$cat_id] :
				array('total_count' => 0, 'completed_count' => 0, 'total_points' => 0, 'earned_points' => 0);
			$total_count = $own['total_count'];
			$completed_count = $own['completed_count'];
			$total_points = $own['total_points'];
			$earned_points = $own['earned_points'];

			$children = array();
			foreach (($children_by_parent[$cat_id] ?? array()) as $child_id)
			{
				$child_node = $build($child_id, $depth + 1);
				if ($child_node === null || $child_node['total_count'] === 0)
				{
					continue;
				}
				$children[] = $child_node;
				$total_count += $child_node['total_count'];
				$completed_count += $child_node['completed_count'];
				$total_points += $child_node['total_points'];
				$earned_points += $child_node['earned_points'];
			}

			return array(
				'id'              => $cat_id,
				'name'            => $categories[$cat_id]['name'],
				'total_count'     => $total_count,
				'completed_count' => $completed_count,
				'total_points'    => $total_points,
				'earned_points'   => $earned_points,
				'percent'         => $total_count > 0 ? (int) round($completed_count / $total_count * 100) : 0,
				'achievements'    => $achievements_by_category[$cat_id] ?? array(),
				'children'        => $children,
			);
		};

		$roots = array();
		foreach (($children_by_parent[0] ?? array()) as $root_id)
		{
			$node = $build($root_id, 0);
			if ($node !== null && $node['total_count'] > 0)
			{
				$roots[] = $node;
			}
		}

		// Achievements with no resolvable category (category_id=0, e.g.
		// a stub row inserted before a full detail sync has run) have no
		// matching node in $categories at all -- surface them under a
		// synthetic fallback root rather than silently dropping them.
		$uncategorized = $achievements_by_category[0] ?? array();
		if (!empty($uncategorized))
		{
			$roots[] = array(
				'id'              => 0,
				'name'            => $this->lang('WOW_ACHIEVEMENTS_UNCATEGORIZED'),
				'total_count'     => count($uncategorized),
				'completed_count' => count($uncategorized),
				'total_points'    => array_sum(array_column($uncategorized, 'points')),
				'earned_points'   => array_sum(array_column($uncategorized, 'points')),
				'percent'         => 100,
				'achievements'    => $uncategorized,
				'children'        => array(),
			);
		}

		return $roots;
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
