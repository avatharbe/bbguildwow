<?php
/**
 * WoW API adapter
 *
 * Implements game_api_interface by wrapping the Battle.net API classes.
 * Updated for the new Game Data / Profile API (2024+).
 *
 * @package   bbguildwow v2.0
 * @copyright 2018 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\game;

use avathar\bbguild\model\games\game_api_interface;
use avathar\bbguildwow\api\battlenet;
use avathar\bbguildwow\model\achievement;
use phpbb\language\language;

/**
 * Class wow_api
 *
 * Adapts the Battle.net SDK to the bbGuild game_api_interface.
 *
 * @package avathar\bbguildwow\game
 */
class wow_api implements game_api_interface
{
	/** Cache key for class ID→name map */
	const CACHE_KEY_CLASSES = 'bbguild_wow_playable_classes';

	/** Cache key for race ID→name map */
	const CACHE_KEY_RACES = 'bbguild_wow_playable_races';

	/** Cache key prefix for resolved item icon URLs, keyed by item_id */
	const CACHE_KEY_ITEM_ICON = 'bbguild_wow_item_icon';

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

	/** @var \phpbb\filesystem\filesystem */
	private $filesystem;

	/**
	 * @var language|null Set via set_language() — optional so unit tests that
	 *                    construct wow_api directly (no DI container) keep
	 *                    working against the fallback strings in lang().
	 */
	private $language;

	/**
	 * @var achievement|null Set via set_achievement_model() — same
	 *                       optional-setter reasoning as $language;
	 *                       required for sync_achievements()/
	 *                       sync_one_achievements() to actually write
	 *                       anything, but its absence doesn't break any
	 *                       other wow_api method or existing test.
	 */
	private $achievement_model;

	/**
	 * @param \phpbb\cache\service              $cache
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param string                            $guild_wow_table
	 * @param string                            $bb_players_table
	 * @param string                            $bb_ranks_table
	 * @param \phpbb\filesystem\filesystem       $filesystem
	 */
	public function __construct(\phpbb\cache\service $cache, \phpbb\db\driver\driver_interface $db, $guild_wow_table, $bb_players_table, $bb_ranks_table, \phpbb\filesystem\filesystem $filesystem)
	{
		$this->cache = $cache;
		$this->db = $db;
		$this->guild_wow_table = $guild_wow_table;
		$this->bb_players_table = $bb_players_table;
		$this->bb_ranks_table = $bb_ranks_table;
		$this->filesystem = $filesystem;
	}

	/**
	 * Setter-injected (not a constructor arg) so existing call sites —
	 * including several unit tests that construct wow_api directly —
	 * don't need updating. See lang().
	 *
	 * @param language $language
	 */
	public function set_language(language $language): void
	{
		$this->language = $language;
	}

	/**
	 * Setter-injected for the same reason as set_language() above.
	 *
	 * @param achievement $achievement_model
	 */
	public function set_achievement_model(achievement $achievement_model): void
	{
		$this->achievement_model = $achievement_model;
	}

	/**
	 * Resolve a user-facing message through phpBB's language framework when
	 * available, falling back to the English text below otherwise (e.g. in
	 * unit tests that construct this class without a DI container and never
	 * call set_language()). Keep LANG_FALLBACK in sync with the matching
	 * keys in language/en/wow.php.
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
		'WOW_API_PORTRAITS_UP_TO_DATE' => 'All player portraits are up to date.',
		'WOW_API_PORTRAITS_FETCHED'    => 'Fetched %d portraits.',
		'WOW_API_SPECS_UP_TO_DATE'     => 'All player specs are up to date.',
		'WOW_API_SPECS_FETCHED'        => 'Fetched %d specs.',
		'WOW_API_EQUIPMENT_UP_TO_DATE' => 'All player equipment is up to date.',
		'WOW_API_EQUIPMENT_FETCHED'    => 'Fetched equipment for %d players.',
		'WOW_API_BATCH_FAILED'         => ' %d failed [%s].',
		'WOW_API_BATCH_REMAINING'      => ' %d remaining.',
		'WOW_API_ERR_404'              => '404 Not Found',
		'WOW_API_ERR_403'              => '403 Forbidden',
		'WOW_API_ERR_500'              => '500 Server Error',
		'WOW_API_ERR_502'              => '502 Bad Gateway',
		'WOW_API_ERR_503'              => '503 Service Unavailable',
		'WOW_API_ERR_504'              => '504 Gateway Timeout',
		'WOW_API_ERR_NO_AVATAR'        => 'No avatar data',
		'WOW_API_ERR_NO_SPEC'          => 'No spec data',
		'WOW_API_ERR_UNKNOWN'          => 'Unknown error',
		'WOW_API_ERR_HTTP_CODE'        => 'HTTP %s',
		'WOW_API_FACTION_ALLIANCE'     => 'Alliance',
		'WOW_API_FACTION_HORDE'        => 'Horde',
		'WOW_API_DEFAULT_RANK_NAME'    => 'Rank%d',
	);

	/**
	 * Create a Battle.net API facade. Extracted so tests can override this
	 * one seam and point the returned facade's resource objects at a local
	 * mock server — none of battlenet_resource's HTTP calls are otherwise
	 * interceptable (raw curl, no injectable client).
	 *
	 * @param string $api      One of battlenet's supported API types
	 * @param string $region
	 * @param string $apikey
	 * @param string $locale
	 * @param string $privkey
	 * @param string $ext_path
	 * @param int    $cache_ttl
	 * @param string $edition
	 * @return battlenet
	 */
	protected function create_battlenet(string $api, string $region, string $apikey, string $locale, string $privkey, string $ext_path = '', int $cache_ttl = 3600, string $edition = 'retail'): battlenet
	{
		return new battlenet($api, $region, $apikey, $locale, $privkey, $ext_path, $this->cache, $cache_ttl, $edition);
	}

	/**
	 * Ensure a directory exists, best-effort (matches the tolerant
	 * semantics of the @mkdir() calls this replaces — sync continues
	 * even if the directory can't be created, individual file writes
	 * will just fail and get skipped).
	 *
	 * @param string $dir
	 */
	private function ensure_dir(string $dir): void
	{
		if ($this->filesystem->exists($dir))
		{
			return;
		}

		try
		{
			$this->filesystem->mkdir($dir, 0755);
		}
		catch (\phpbb\filesystem\exception\filesystem_exception $e)
		{
			// best-effort; see docblock
		}
	}

	/**
	 * Write a local file, best-effort.
	 *
	 * @param string $path
	 * @param string $content
	 * @return bool True on success, false on failure.
	 */
	private function write_file(string $path, string $content): bool
	{
		try
		{
			$this->filesystem->dump_file($path, $content);
			return true;
		}
		catch (\phpbb\filesystem\exception\filesystem_exception $e)
		{
			return false;
		}
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
		$api = $this->create_battlenet('guild', $region, $game->getApikey(), $game->get_apilocale(), $game->get_privkey(), $ext_path, 3600, $edition);
		$guild_data = $api->guild->getGuild($realm_slug, $name_slug);
		unset($api);

		$result = array();
		if (isset($guild_data['response']) && is_array($guild_data['response']))
		{
			$result = $guild_data['response'];
		}

		// Carry the exact request URL up for logging/diagnostics. consume() always
		// records it on the raw response (even on a 404), but it is otherwise
		// dropped here since only ['response'] is kept.
		if (isset($guild_data['request_url']))
		{
			$result['_request_url'] = $guild_data['request_url'];
		}

		// Fetch roster if requested
		if (in_array('members', $params))
		{
			$api = $this->create_battlenet('guild', $region, $game->getApikey(), $game->get_apilocale(), $game->get_privkey(), $ext_path, 3600, $edition);
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
	 * Fetch the guild's recent activity feed (boss kills, achievements,
	 * roster changes, item loots) from the Battle.net Guild Activity API.
	 *
	 * @param string $guild_name
	 * @param string $realm
	 * @param string $region
	 * @param string $edition
	 * @return array|false The raw activity response, or false on missing
	 *                      credentials or a malformed/error response.
	 */
	public function fetch_guild_activity(string $guild_name, string $realm, string $region, string $edition = 'retail')
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

		$api = $this->create_battlenet('guild', $region, $game->getApikey(), $game->get_apilocale(), $game->get_privkey(), $ext_path, 3600, $edition);
		$activity_data = $api->guild->getActivity($realm_slug, $name_slug);
		unset($api);

		if (!isset($activity_data['response']) || !is_array($activity_data['response']))
		{
			return false;
		}

		return $activity_data['response'];
	}

	/**
	 * Sync a guild's fetched activity feed into bb_news, skipping entries
	 * already recorded (by news_source_key) so repeated cron runs don't
	 * duplicate rows.
	 *
	 * NOTE: the exact Battle.net Guild Activity response shape (activity
	 * type strings, nested field names beyond `activity.type`/`timestamp`/
	 * `character.name`) has not been verified against a live guild — the
	 * text built here is deliberately generic (type + character name only)
	 * rather than guessing at per-type field mappings that could be wrong.
	 * Dedup/storage correctness does not depend on that mapping being
	 * exact. Revisit once a real response is available (#10 follow-up).
	 *
	 * @param int   $guild_id
	 * @param array $activities Raw entries from fetch_guild_activity()'s
	 *                          response['activities'] array.
	 * @return array ['inserted' => int, 'skipped' => int, 'total' => int]
	 */
	public function sync_guild_activity(int $guild_id, array $activities): array
	{
		global $phpbb_container;

		$news_table = $phpbb_container->getParameter('avathar.bbguild.tables.bb_news');

		$existing_keys = $this->get_existing_activity_keys($guild_id, $news_table);

		$inserted = 0;
		$skipped = 0;

		foreach ($activities as $activity)
		{
			$key = $this->build_activity_source_key($guild_id, $activity);

			if (isset($existing_keys[$key]))
			{
				$skipped++;
				continue;
			}

			$this->insert_activity_news($guild_id, $news_table, $activity, $key);
			$existing_keys[$key] = true;
			$inserted++;
		}

		return array('inserted' => $inserted, 'skipped' => $skipped, 'total' => count($activities));
	}

	/**
	 * @param int    $guild_id
	 * @param string $news_table
	 * @return array Set (news_source_key => true) of already-recorded keys.
	 */
	private function get_existing_activity_keys(int $guild_id, string $news_table): array
	{
		$sql = 'SELECT news_source_key FROM ' . $news_table .
			' WHERE guild_id = ' . $guild_id . " AND news_source = 'api'";
		$result = $this->db->sql_query($sql);

		$keys = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$keys[$row['news_source_key']] = true;
		}
		$this->db->sql_freeresult($result);

		return $keys;
	}

	/**
	 * Builds a stable, structure-based dedup key — independent of whether
	 * describe_activity_headline()'s text mapping is exactly right, since
	 * it hashes the raw activity entry rather than the derived text.
	 *
	 * @param int   $guild_id
	 * @param array $activity
	 * @return string
	 */
	private function build_activity_source_key(int $guild_id, array $activity): string
	{
		$timestamp = isset($activity['timestamp']) ? (string) $activity['timestamp'] : '';
		$type = isset($activity['activity']['type']) ? (string) $activity['activity']['type'] : 'unknown';

		return 'wow_activity_' . $guild_id . '_' . $timestamp . '_' . $type . '_' . md5(json_encode($activity));
	}

	/**
	 * @param int    $guild_id
	 * @param string $news_table
	 * @param array  $activity
	 * @param string $source_key
	 */
	private function insert_activity_news(int $guild_id, string $news_table, array $activity, string $source_key): void
	{
		// Plain server-generated label, no bbcode/HTML involved — stored as-is
		// (empty uid/bitfield/options=0), matching how generate_text_for_display()
		// already renders an empty bitfield as plain text (see guild_news.php).
		$headline = $this->describe_activity_headline($activity);

		$timestamp = isset($activity['timestamp']) ? (int) ((int) $activity['timestamp'] / 1000) : time();

		$data = array(
			'guild_id'        => $guild_id,
			'news_headline'   => $headline,
			'news_message'    => $headline,
			'news_date'       => $timestamp,
			'user_id'         => 0,
			'bbcode_bitfield' => '',
			'bbcode_uid'      => '',
			'bbcode_options'  => 0,
			'news_source'     => 'api',
			'news_source_key' => $source_key,
		);

		$sql = 'INSERT INTO ' . $news_table . ' ' . $this->db->sql_build_array('INSERT', $data);
		$this->db->sql_query($sql);
	}

	/**
	 * Best-effort, deliberately generic activity label — see the
	 * sync_guild_activity() docblock for why this doesn't attempt
	 * per-type field mapping.
	 *
	 * @param array $activity
	 * @return string
	 */
	private function describe_activity_headline(array $activity): string
	{
		$type = isset($activity['activity']['type']) ? (string) $activity['activity']['type'] : 'activity';
		$label = ucwords(strtolower(str_replace('_', ' ', $type)));

		$character = isset($activity['character']['name']) ? (string) $activity['character']['name'] : '';

		return $character !== '' ? $character . ' — ' . $label : $label;
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

		// Faction: new API uses faction.type = 'ALLIANCE' or 'HORDE'. Only report
		// a faction when the API actually provides one; otherwise leave it out of
		// $result so update_guild_battleNet() preserves the guild's stored faction
		// instead of defaulting everyone to Horde on an incomplete response (#29).
		$faction = 2; // fallback used only for emblem ring rendering below
		if (isset($raw_data['faction']['type']))
		{
			if ($raw_data['faction']['type'] === 'ALLIANCE')
			{
				$faction = 1;
				$result['faction'] = 1;
				$result['faction_name'] = $this->lang('WOW_API_FACTION_ALLIANCE');
			}
			else if ($raw_data['faction']['type'] === 'HORDE')
			{
				$faction = 2;
				$result['faction'] = 2;
				$result['faction_name'] = $this->lang('WOW_API_FACTION_HORDE');
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
			$result['emblempath'] = $this->create_emblem($raw_data['crest'], $faction, $guild_name, $realm, $region);
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

		$api = $this->create_battlenet('character', $region, $game->getApikey(), $game->get_apilocale(), $game->get_privkey(), $ext_path, 3600, $edition);
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
	 * Sync one character's active specialization. Extracted from
	 * sync_specs()'s per-player loop body so it can be shared with
	 * sync_character() (#362).
	 *
	 * @param array     $player           Row with at least player_id, player_name, player_realm
	 * @param battlenet $api              Facade with ->character set
	 * @param bool      $mark_unavailable Whether to write the 'N/A' sentinel on a 404. Safe
	 *                                    to default true for the guild-batch callers below,
	 *                                    whose own SELECT only ever returns players with an
	 *                                    already-empty player_spec — the sentinel can only
	 *                                    overwrite emptiness there. sync_character() (#362)
	 *                                    has no such guarantee (any stale player row, spec
	 *                                    populated or not) and must pass false to avoid
	 *                                    blanking real data on a transient 404.
	 * @return array{success: bool, error_code: string|int|null, stop_batch: bool}
	 */
	protected function sync_one_specs(array $player, battlenet $api, bool $mark_unavailable = true): array
	{
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

			if ($http_code === 404 && $mark_unavailable)
			{
				$this->db->sql_query('UPDATE ' . $this->bb_players_table .
					" SET player_spec = 'N/A'" .
					' WHERE player_id = ' . (int) $player['player_id']);
			}

			return array('success' => false, 'error_code' => $error_code, 'stop_batch' => $http_code >= 500);
		}

		$spec_name = '';
		if (isset($data['active_specialization']['name']))
		{
			$spec_name = $data['active_specialization']['name'];
		}

		$success = true;
		$error_code = null;
		if (empty($spec_name))
		{
			$spec_name = 'N/A';
			$success = false;
			$error_code = 'no_spec';
		}

		$this->db->sql_query('UPDATE ' . $this->bb_players_table .
			" SET player_spec = '" . $this->db->sql_escape($spec_name) . "'" .
			' WHERE player_id = ' . (int) $player['player_id']);

		return array('success' => $success, 'error_code' => $error_code, 'stop_batch' => false);
	}

	/**
	 * Sync one character's gender from the base character profile.
	 *
	 * Guild roster sync (sync_guild_members()) can't populate this — the
	 * Guild Roster API doesn't return gender, only name/level/class/race/rank
	 * — so every roster-synced character defaults to Male until this runs
	 * (#42). No 404 sentinel: unlike spec/portrait, there's no meaningful
	 * "N/A" gender, so a transient failure just leaves the existing value
	 * (correct or still-default) untouched rather than blanking anything.
	 *
	 * @param array     $player Row with at least player_id, player_name, player_realm
	 * @param battlenet $api    Facade with ->character set
	 * @return array{success: bool, error_code: string|int|null, stop_batch: bool}
	 */
	protected function sync_one_profile(array $player, battlenet $api): array
	{
		$response = $api->character->getCharacter($player['player_realm'], $player['player_name']);
		$data = isset($response['response']) ? $response['response'] : null;
		$http_code = isset($response['response_headers']['http_code']) ? (int) $response['response_headers']['http_code'] : 0;

		if (!is_array($data) || isset($data['code']))
		{
			$error_code = isset($data['code']) ? (int) $data['code'] : $http_code;
			if ($error_code === 0)
			{
				$error_code = 'unknown';
			}

			return array('success' => false, 'error_code' => $error_code, 'stop_batch' => $http_code >= 500);
		}

		if (isset($data['gender']['type']))
		{
			$gender_id = ($data['gender']['type'] === 'FEMALE') ? 1 : 0;
			$this->db->sql_query('UPDATE ' . $this->bb_players_table .
				' SET player_gender_id = ' . $gender_id .
				' WHERE player_id = ' . (int) $player['player_id']);
		}

		return array('success' => true, 'error_code' => null, 'stop_batch' => false);
	}

	/**
	 * Sync one character's equipment. Extracted from sync_equipment()'s
	 * per-player loop body so it can be shared with sync_character() (#362).
	 *
	 * @param array     $player          Row with at least player_id, player_name, player_realm, player_region
	 * @param battlenet $api             Facade with ->character set
	 * @param string    $equipment_table
	 * @param string    $stat_table
	 * @return array{success: bool, error_code: string|int|null, stop_batch: bool}
	 */
	protected function sync_one_equipment(array $player, battlenet $api, string $equipment_table, string $stat_table): array
	{
		$response = $api->character->getCharacterEquipment(
			$player['player_realm'],
			$player['player_name']
		);
		$data = isset($response['response']) ? $response['response'] : null;
		$http_code = isset($response['response_headers']['http_code']) ? (int) $response['response_headers']['http_code'] : 0;

		if (!is_array($data) || isset($data['code']) || !isset($data['equipped_items']))
		{
			$error_code = isset($data['code']) ? (int) $data['code'] : $http_code;
			if ($error_code === 0)
			{
				$error_code = 'unknown';
			}

			return array('success' => false, 'error_code' => $error_code, 'stop_batch' => $http_code >= 500);
		}

		$now = time();
		$player_id = (int) $player['player_id'];

		$this->db->sql_query('DELETE FROM ' . $equipment_table . ' WHERE player_id = ' . $player_id);
		$this->db->sql_query('DELETE FROM ' . $stat_table . ' WHERE player_id = ' . $player_id);

		foreach ($data['equipped_items'] as $item)
		{
			$parsed = $this->parse_equipped_item($item);
			if ($parsed['slot_type'] === '')
			{
				continue;
			}

			$parsed['equipment']['icon_url'] = $this->resolve_item_icon_url((int) $parsed['equipment']['item_id'], $player['player_region']);

			$sql_ary = array_merge(
				array('player_id' => $player_id, 'slot_type' => $parsed['slot_type'], 'last_update' => $now),
				$parsed['equipment']
			);
			$this->db->sql_query('INSERT INTO ' . $equipment_table . ' ' . $this->db->sql_build_array('INSERT', $sql_ary));

			foreach ($parsed['stats'] as $stat)
			{
				$stat_ary = array(
					'player_id'  => $player_id,
					'slot_type'  => $parsed['slot_type'],
					'stat_type'  => $stat['stat_type'],
					'stat_value' => (int) $stat['stat_value'],
				);
				$this->db->sql_query('INSERT INTO ' . $stat_table . ' ' . $this->db->sql_build_array('INSERT', $stat_ary));
			}
		}

		return array('success' => true, 'error_code' => null, 'stop_batch' => false);
	}

	/**
	 * Sync one character's portrait/render. Extracted from sync_portraits()'s
	 * per-player loop body so it can be shared with sync_character() (#362).
	 *
	 * @param array     $player        Row with at least player_id, player_name, player_realm
	 * @param battlenet $api           Facade with ->character set
	 * @param string    $portrait_dir  Absolute filesystem path
	 * @param string    $portrait_rel  Path relative to phpBB root, stored in DB
	 * @param string    $upload_path      phpBB's configured upload_path
	 * @param string    $phpbb_root_path
	 * @param bool      $mark_unavailable Whether to write the 'N/A' sentinel on a 404. See
	 *                                    sync_one_specs()'s matching parameter for the full
	 *                                    rationale — same guild-batch-safe/sync_character-unsafe
	 *                                    distinction applies here.
	 * @return array{success: bool, error_code: string|int|null, stop_batch: bool}
	 */
	protected function sync_one_portrait(array $player, battlenet $api, string $portrait_dir, string $portrait_rel, string $upload_path, string $phpbb_root_path, bool $mark_unavailable = true): array
	{
		$response = $api->character->getCharacterMedia($player['player_realm'], $player['player_name']);
		$data = isset($response['response']) ? $response['response'] : null;
		$http_code = isset($response['response_headers']['http_code']) ? (int) $response['response_headers']['http_code'] : 0;

		if (!is_array($data) || isset($data['code']))
		{
			$error_code = isset($data['code']) ? (int) $data['code'] : $http_code;
			if ($error_code === 0)
			{
				$error_code = 'unknown';
			}

			if ($http_code === 404 && $mark_unavailable)
			{
				$this->db->sql_query('UPDATE ' . $this->bb_players_table .
					" SET player_portrait_url = 'N/A'" .
					' WHERE player_id = ' . (int) $player['player_id']);
			}

			return array('success' => false, 'error_code' => $error_code, 'stop_batch' => $http_code >= 500);
		}

		$avatar_url = '';
		$render_url = '';
		if (isset($data['assets']) && is_array($data['assets']))
		{
			foreach ($data['assets'] as $asset)
			{
				if (isset($asset['key']) && isset($asset['value']))
				{
					if ($asset['key'] === 'avatar')
					{
						$avatar_url = $asset['value'];
					}
					else if ($asset['key'] === 'main')
					{
						$render_url = $asset['value'];
					}
				}
			}
		}

		if (empty($avatar_url))
		{
			return array('success' => false, 'error_code' => 'no_avatar', 'stop_batch' => false);
		}

		$local_path = $this->download_portrait($avatar_url, $portrait_dir, $portrait_rel, (int) $player['player_id']);
		$stored_url = !empty($local_path) ? $local_path : $avatar_url;

		$render_rel = $upload_path . '/bbguildwow/renders/';
		$render_dir = $phpbb_root_path . $render_rel;
		$this->ensure_dir($render_dir);
		$stored_render = '';
		if (!empty($render_url))
		{
			$render_local = $this->download_portrait($render_url, $render_dir, $render_rel, (int) $player['player_id']);
			$stored_render = !empty($render_local) ? $render_local : $render_url;
		}

		$sql_update = "SET player_portrait_url = '" . $this->db->sql_escape($stored_url) . "'";
		if (!empty($stored_render))
		{
			$sql_update .= ", player_render_url = '" . $this->db->sql_escape($stored_render) . "'";
		}
		$this->db->sql_query('UPDATE ' . $this->bb_players_table . ' ' . $sql_update .
			' WHERE player_id = ' . (int) $player['player_id']);

		return array('success' => true, 'error_code' => null, 'stop_batch' => false);
	}

	/**
	 * Sync one character's completed achievements (bbguildwow#44's sync
	 * side). Extracted as its own sync_one_*() so it follows the same
	 * shape as sync_one_specs()/sync_one_equipment()/sync_one_portrait(),
	 * even though today only sync_achievements() (the guild-batch method
	 * below) calls it -- not sync_character() -- per the explicit choice
	 * to keep this a guild-batch "backfill missing data" action, not part
	 * of the always-on per-character cron.
	 *
	 * @param array     $player Row with at least player_id, player_name, player_realm
	 * @param battlenet $api    Facade with ->character set
	 * @return array{success: bool, error_code: string|int|null, stop_batch: bool}
	 */
	protected function sync_one_achievements(array $player, battlenet $api): array
	{
		$response = $api->character->getCharacterAchievements(
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

			return array('success' => false, 'error_code' => $error_code, 'stop_batch' => $http_code >= 500);
		}

		if ($this->achievement_model === null)
		{
			// Misconfiguration (set_achievement_model() never called) --
			// not a per-character problem, so stop the whole batch rather
			// than burning API calls on every remaining player only to
			// fail the same way each time.
			return array('success' => false, 'error_code' => 'unknown', 'stop_batch' => true);
		}

		$result = $this->achievement_model->set_player_achievements((int) $player['player_id'], $data);

		return array('success' => $result['success'], 'error_code' => $result['success'] ? null : 'unknown', 'stop_batch' => false);
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

		$upload_path = $phpbb_container->get('config')['upload_path'];
		$portrait_rel = $upload_path . '/bbguildwow/portraits/';
		$portrait_dir = $phpbb_root_path . $portrait_rel;
		$this->ensure_dir($portrait_dir);

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
			return array('success' => true, 'message' => $this->lang('WOW_API_PORTRAITS_UP_TO_DATE'), 'count' => 0);
		}

		$api = $this->create_battlenet('character', $region, $apikey, $locale, $privkey, '', 3600, $edition);

		$time_start = time();
		$time_limit = 20;
		$fetched = 0;
		$failed = 0;
		$errors = array();

		foreach ($players as $player)
		{
			if ((time() - $time_start) >= $time_limit)
			{
				break;
			}

			$outcome = $this->sync_one_portrait($player, $api, $portrait_dir, $portrait_rel, $upload_path, $phpbb_root_path);

			if ($outcome['success'])
			{
				$fetched++;
			}
			else
			{
				$errors[$outcome['error_code']][] = $player['player_name'];
				$failed++;

				if ($outcome['stop_batch'])
				{
					break;
				}
			}
		}

		unset($api);

		$remaining = count($players) - $fetched - $failed;
		$message = $this->lang('WOW_API_PORTRAITS_FETCHED', $fetched);
		if (!empty($errors))
		{
			$parts = array();
			foreach ($errors as $code => $names)
			{
				$parts[] = sprintf('%s: %s', $this->error_label($code), implode(', ', $names));
			}
			$message .= $this->lang('WOW_API_BATCH_FAILED', $failed, implode('; ', $parts));
		}
		if ($remaining > 0)
		{
			$message .= $this->lang('WOW_API_BATCH_REMAINING', $remaining);
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
			return array('success' => true, 'message' => $this->lang('WOW_API_SPECS_UP_TO_DATE'), 'count' => 0);
		}

		$api = $this->create_battlenet('character', $region, $apikey, $locale, $privkey, '', 3600, $edition);

		$time_start = time();
		$time_limit = 20;
		$fetched = 0;
		$failed = 0;
		$errors = array();

		foreach ($players as $player)
		{
			if ((time() - $time_start) >= $time_limit)
			{
				break;
			}

			$outcome = $this->sync_one_specs($player, $api);

			if ($outcome['success'])
			{
				$fetched++;
			}
			else
			{
				$errors[$outcome['error_code']][] = $player['player_name'];
				$failed++;

				if ($outcome['stop_batch'])
				{
					break;
				}
			}
		}

		unset($api);

		$remaining = count($players) - $fetched - $failed;
		$message = $this->lang('WOW_API_SPECS_FETCHED', $fetched);
		if (!empty($errors))
		{
			$parts = array();
			foreach ($errors as $code => $names)
			{
				$parts[] = sprintf('%s: %s', $this->error_label($code), implode(', ', $names));
			}
			$message .= $this->lang('WOW_API_BATCH_FAILED', $failed, implode('; ', $parts));
		}
		if ($remaining > 0)
		{
			$message .= $this->lang('WOW_API_BATCH_REMAINING', $remaining);
		}

		return array('success' => true, 'message' => $message, 'count' => $fetched, 'errors' => $errors);
	}

	/**
	 * Guild-batch achievement sync (bbguildwow#44's sync side) -- an
	 * ACP-triggered "backfill missing data" action, same shape as
	 * sync_specs()/sync_equipment()/sync_portraits(): time-budgeted per
	 * request, meant to be called repeatedly by JS polling until
	 * 'remaining' reaches 0 (large guilds take multiple requests; see
	 * bbguildwow#44's follow-up discussion on why this isn't a single
	 * blocking call).
	 *
	 * Unlike specs (player_spec = '' is the "needs sync" marker),
	 * achievements have no single column to check -- "needs sync" here
	 * means "no tracked achievement row for this player yet at all".
	 * That makes this a one-time backfill per character, same as specs:
	 * once a character has been synced here, a later re-click of this
	 * same action won't pick them up again to catch newly-earned
	 * achievements. Keeping characters continuously fresh is a
	 * deliberately separate concern from this action, same as how specs/
	 * equipment/portrait freshness is owned by the per-character cron
	 * (sync_character()), not this button -- extending that ongoing-
	 * freshness path to achievements is out of scope here (see
	 * sync_one_achievements()'s docblock).
	 *
	 * @param int    $guild_id
	 * @param string $region
	 * @param string $apikey
	 * @param string $locale
	 * @param string $privkey
	 * @param string $edition
	 * @return array Result with 'success', 'message', 'count'
	 */
	public function sync_achievements(int $guild_id, string $region, string $apikey, string $locale, string $privkey, string $edition = 'retail'): array
	{
		global $phpbb_container;
		$db = $this->db;

		$track_table = $phpbb_container->getParameter('avathar.bbguildwow.tables.bb_achievement_track');

		$sql = 'SELECT p.player_id, p.player_name, p.player_realm
			FROM ' . $this->bb_players_table . ' p
			WHERE p.player_guild_id = ' . $guild_id . '
				AND p.game_id = \'wow\'
				AND p.player_status = 1
				AND NOT EXISTS (
					SELECT 1 FROM ' . $track_table . ' ac WHERE ac.player_id = p.player_id
				)
			ORDER BY p.player_id';
		$result = $db->sql_query($sql);

		$players = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$players[] = $row;
		}
		$db->sql_freeresult($result);

		if (empty($players))
		{
			return array('success' => true, 'message' => $this->lang('WOW_API_ACHIEVEMENTS_UP_TO_DATE'), 'count' => 0);
		}

		$api = $this->create_battlenet('character', $region, $apikey, $locale, $privkey, '', 3600, $edition);

		$time_start = time();
		$time_limit = 20;
		$fetched = 0;
		$failed = 0;
		$errors = array();

		foreach ($players as $player)
		{
			if ((time() - $time_start) >= $time_limit)
			{
				break;
			}

			$outcome = $this->sync_one_achievements($player, $api);

			if ($outcome['success'])
			{
				$fetched++;
			}
			else
			{
				$errors[$outcome['error_code']][] = $player['player_name'];
				$failed++;

				if ($outcome['stop_batch'])
				{
					break;
				}
			}
		}

		unset($api);

		$remaining = count($players) - $fetched - $failed;
		$message = $this->lang('WOW_API_ACHIEVEMENTS_FETCHED', $fetched);
		if (!empty($errors))
		{
			$parts = array();
			foreach ($errors as $code => $names)
			{
				$parts[] = sprintf('%s: %s', $this->error_label($code), implode(', ', $names));
			}
			$message .= $this->lang('WOW_API_BATCH_FAILED', $failed, implode('; ', $parts));
		}
		if ($remaining > 0)
		{
			$message .= $this->lang('WOW_API_BATCH_REMAINING', $remaining);
		}

		return array('success' => true, 'message' => $message, 'count' => $fetched, 'errors' => $errors);
	}

	/**
	 * Fetch character statistics (primary/secondary stats) from the API.
	 * Results are cached via the API layer (1h TTL).
	 *
	 * @param string $name    Character name
	 * @param string $realm   Realm slug
	 * @param string $region  Region code
	 * @param string $edition Game edition
	 * @return array|false Parsed stats array or false on failure
	 */
	public function fetch_character_stats(string $name, string $realm, string $region, string $edition = 'retail')
	{
		global $phpbb_container;

		$game = $this->get_game_from_db($phpbb_container);
		if (!$game || trim($game->getApikey()) == '')
		{
			return false;
		}

		$ext_path = $this->get_ext_path($phpbb_container);
		$api = $this->create_battlenet('character', $region, $game->getApikey(), $game->get_apilocale(), $game->get_privkey(), $ext_path, 3600, $edition);
		$data = $api->character->getCharacterStatistics($this->to_slug($realm), $name);
		unset($api);

		if (isset($data['response']) && !isset($data['response']['code']))
		{
			return $data['response'];
		}

		return false;
	}

	/**
	 * Fetch character professions from the API.
	 * Results are cached via the API layer (1h TTL).
	 *
	 * @param string $name    Character name
	 * @param string $realm   Realm slug
	 * @param string $region  Region code
	 * @param string $edition Game edition
	 * @return array|false Parsed professions array or false on failure
	 */
	public function fetch_character_professions(string $name, string $realm, string $region, string $edition = 'retail')
	{
		global $phpbb_container;

		$game = $this->get_game_from_db($phpbb_container);
		if (!$game || trim($game->getApikey()) == '')
		{
			return false;
		}

		$ext_path = $this->get_ext_path($phpbb_container);
		$api = $this->create_battlenet('character', $region, $game->getApikey(), $game->get_apilocale(), $game->get_privkey(), $ext_path, 3600, $edition);
		$data = $api->character->getCharacterProfessions($this->to_slug($realm), $name);
		unset($api);

		if (isset($data['response']) && !isset($data['response']['code']))
		{
			return $data['response'];
		}

		return false;
	}

	/**
	 * Fetch character Mythic Keystone profile from the API.
	 * Results are cached via the API layer (1h TTL).
	 *
	 * @param string $name    Character name
	 * @param string $realm   Realm slug
	 * @param string $region  Region code
	 * @param string $edition Game edition
	 * @return array|false Parsed M+ data or false on failure
	 */
	public function fetch_mythic_keystone_profile(string $name, string $realm, string $region, string $edition = 'retail')
	{
		global $phpbb_container;

		$game = $this->get_game_from_db($phpbb_container);
		if (!$game || trim($game->getApikey()) == '')
		{
			return false;
		}

		$ext_path = $this->get_ext_path($phpbb_container);
		$api = $this->create_battlenet('character', $region, $game->getApikey(), $game->get_apilocale(), $game->get_privkey(), $ext_path, 3600, $edition);
		$data = $api->character->getCharacterMythicKeystoneProfile($this->to_slug($realm), $name);
		unset($api);

		if (isset($data['response']) && !isset($data['response']['code']))
		{
			return $data['response'];
		}

		return false;
	}

	/**
	 * Fetch character PvP summary from the API.
	 * Results are cached via the API layer (1h TTL).
	 *
	 * @param string $name    Character name
	 * @param string $realm   Realm slug
	 * @param string $region  Region code
	 * @param string $edition Game edition
	 * @return array|false Parsed PvP data or false on failure
	 */
	public function fetch_pvp_summary(string $name, string $realm, string $region, string $edition = 'retail')
	{
		global $phpbb_container;

		$game = $this->get_game_from_db($phpbb_container);
		if (!$game || trim($game->getApikey()) == '')
		{
			return false;
		}

		$ext_path = $this->get_ext_path($phpbb_container);
		$api = $this->create_battlenet('character', $region, $game->getApikey(), $game->get_apilocale(), $game->get_privkey(), $ext_path, 3600, $edition);
		$data = $api->character->getCharacterPvPSummary($this->to_slug($realm), $name);
		unset($api);

		if (isset($data['response']) && !isset($data['response']['code']))
		{
			return $data['response'];
		}

		return false;
	}

	/**
	 * Fetch character equipment from the Character Equipment API and cache in DB.
	 *
	 * Processes players whose equipment hasn't been synced recently (>24h),
	 * with a time guard to stay within PHP's execution limit.
	 *
	 * @param int    $guild_id
	 * @param string $region
	 * @param string $apikey
	 * @param string $locale
	 * @param string $privkey
	 * @param string $edition
	 * @return array Result with 'success', 'message', 'count'
	 */
	public function sync_equipment(int $guild_id, string $region, string $apikey, string $locale, string $privkey, string $edition = 'retail'): array
	{
		global $phpbb_container;
		$db = $this->db;

		$equipment_table = $phpbb_container->getParameter('avathar.bbguildwow.tables.bb_player_equipment');
		$stat_table = $phpbb_container->getParameter('avathar.bbguildwow.tables.bb_player_item_stat');
		$stale_threshold = time() - 86400;

		$sql = 'SELECT p.player_id, p.player_name, p.player_realm, p.player_region
			FROM ' . $this->bb_players_table . ' p
			LEFT JOIN ' . $equipment_table . ' e
				ON e.player_id = p.player_id AND e.slot_type = \'HEAD\'
			WHERE p.player_guild_id = ' . $guild_id . '
				AND p.game_id = \'wow\'
				AND p.player_status = 1
				AND (e.player_id IS NULL OR e.last_update < ' . $stale_threshold . ')
			ORDER BY p.player_id';
		$result = $db->sql_query($sql);

		$players = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$players[] = $row;
		}
		$db->sql_freeresult($result);

		if (empty($players))
		{
			return array('success' => true, 'message' => $this->lang('WOW_API_EQUIPMENT_UP_TO_DATE'), 'count' => 0);
		}

		$api = $this->create_battlenet('character', $region, $apikey, $locale, $privkey, '', 3600, $edition);

		$time_start = time();
		$time_limit = 20;
		$fetched = 0;
		$failed = 0;
		$errors = array();

		foreach ($players as $player)
		{
			if ((time() - $time_start) >= $time_limit)
			{
				break;
			}

			$outcome = $this->sync_one_equipment($player, $api, $equipment_table, $stat_table);

			if ($outcome['success'])
			{
				$fetched++;
			}
			else
			{
				$errors[$outcome['error_code']][] = $player['player_name'];
				$failed++;

				if ($outcome['stop_batch'])
				{
					break;
				}
			}
		}

		unset($api);

		$remaining = count($players) - $fetched - $failed;
		$message = $this->lang('WOW_API_EQUIPMENT_FETCHED', $fetched);
		if (!empty($errors))
		{
			$parts = array();
			foreach ($errors as $code => $names)
			{
				$parts[] = sprintf('%s: %s', $this->error_label($code), implode(', ', $names));
			}
			$message .= $this->lang('WOW_API_BATCH_FAILED', $failed, implode('; ', $parts));
		}
		if ($remaining > 0)
		{
			$message .= $this->lang('WOW_API_BATCH_REMAINING', $remaining);
		}

		return array('success' => true, 'message' => $message, 'count' => $fetched, 'errors' => $errors);
	}

	/**
	 * Sync one character's specs, equipment, and portrait — the
	 * character_sync_interface implementation's actual orchestrator (#362).
	 * Returns true only if all three sub-syncs succeed.
	 *
	 * @param array $player_row Full bb_players row
	 * @return bool
	 */
	public function sync_character(array $player_row): bool
	{
		global $phpbb_container, $phpbb_root_path;

		$game = $this->get_game_from_db($phpbb_container);
		if (!$game || trim($game->getApikey()) == '')
		{
			return false;
		}

		// Resolve the owning guild's edition — mirrors
		// controller/portrait_controller.php's do_sync_roster() pattern. Every
		// other single-character wow_api method threads $edition explicitly;
		// omitting it here would 404 every request for Classic-edition guilds
		// forever, since the Battle.net namespace differs per edition (#362).
		$sql = 'SELECT game_edition FROM ' . $phpbb_container->getParameter('avathar.bbguild.tables.bb_guild') .
			' WHERE id = ' . (int) $player_row['player_guild_id'];
		$result = $this->db->sql_query($sql);
		$guild_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$edition = !empty($guild_row['game_edition']) ? $guild_row['game_edition'] : 'retail';

		$ext_path = $this->get_ext_path($phpbb_container);
		$api = $this->create_battlenet('character', $player_row['player_region'], $game->getApikey(), $game->get_apilocale(), $game->get_privkey(), $ext_path, 3600, $edition);

		// mark_unavailable=false on both 404-sentinel-writing sub-syncs: unlike
		// the guild-batch callers, sync_character() can be handed ANY stale
		// character (bbguild core's character_sync cron has no filter on
		// whether player_spec/player_portrait_url are already populated), so a
		// transient 404 must never blank out real, previously-synced data.
		$specs_outcome = $this->sync_one_specs($player_row, $api, false);
		if ($specs_outcome['stop_batch'])
		{
			unset($api);
			return false;
		}

		// Best-effort: gender is enrichment, not one of the #362 sync
		// contract's core fields, so a failure here doesn't fail the whole
		// character sync — only a 5xx aborts early, same budget-protection
		// reasoning as the other sub-syncs.
		$profile_outcome = $this->sync_one_profile($player_row, $api);
		if ($profile_outcome['stop_batch'])
		{
			unset($api);
			return false;
		}

		$equipment_table = $phpbb_container->getParameter('avathar.bbguildwow.tables.bb_player_equipment');
		$stat_table = $phpbb_container->getParameter('avathar.bbguildwow.tables.bb_player_item_stat');
		$equipment_outcome = $this->sync_one_equipment($player_row, $api, $equipment_table, $stat_table);
		if ($equipment_outcome['stop_batch'])
		{
			unset($api);
			return false;
		}

		$upload_path = $phpbb_container->get('config')['upload_path'];
		$portrait_rel = $upload_path . '/bbguildwow/portraits/';
		$portrait_dir = $phpbb_root_path . $portrait_rel;
		$this->ensure_dir($portrait_dir);
		$portrait_outcome = $this->sync_one_portrait($player_row, $api, $portrait_dir, $portrait_rel, $upload_path, $phpbb_root_path, false);
		if ($portrait_outcome['stop_batch'])
		{
			unset($api);
			return false;
		}

		unset($api);

		return $specs_outcome['success'] && $equipment_outcome['success'] && $portrait_outcome['success'];
	}

	/**
	 * Return a human-readable label for an API error code.
	 *
	 * @param int|string $code HTTP status code or error key
	 * @return string
	 */
	private function error_label($code): string
	{
		$keys = array(
			404         => 'WOW_API_ERR_404',
			403         => 'WOW_API_ERR_403',
			500         => 'WOW_API_ERR_500',
			502         => 'WOW_API_ERR_502',
			503         => 'WOW_API_ERR_503',
			504         => 'WOW_API_ERR_504',
			'no_avatar' => 'WOW_API_ERR_NO_AVATAR',
			'no_spec'   => 'WOW_API_ERR_NO_SPEC',
			'unknown'   => 'WOW_API_ERR_UNKNOWN',
		);

		return isset($keys[$code]) ? $this->lang($keys[$code]) : $this->lang('WOW_API_ERR_HTTP_CODE', $code);
	}

	/**
	 * Map one Battle.net equipped_items[] entry to storable rows.
	 *
	 * @param array $item One element of getCharacterEquipment()['equipped_items']
	 * @return array ['slot_type'=>string, 'equipment'=>array, 'stats'=>array]
	 */
	public function parse_equipped_item(array $item): array
	{
		$slot_type = isset($item['slot']['type']) ? (string) $item['slot']['type'] : '';

		// Permanent enchant only (WowHead ench= takes a single id).
		$enchant_id = 0;
		if (!empty($item['enchantments']) && is_array($item['enchantments']))
		{
			foreach ($item['enchantments'] as $ench)
			{
				if (isset($ench['enchantment_slot']['type']) && $ench['enchantment_slot']['type'] === 'PERMANENT')
				{
					$enchant_id = isset($ench['enchantment_id']) ? (int) $ench['enchantment_id'] : 0;
					break;
				}
			}
		}

		$gem_ids = $this->join_item_ids($item['sockets'] ?? array(), 'item');
		$bonus_ids = (!empty($item['bonus_list']) && is_array($item['bonus_list']))
			? implode(':', array_map('intval', $item['bonus_list'])) : '';
		$set_item_ids = isset($item['set']['items']) && is_array($item['set']['items'])
			? $this->join_item_ids($item['set']['items'], 'item') : '';

		// icon_url is resolved separately (sync_one_equipment() calls
		// resolve_item_icon_url()) — item.media.id here is a media *reference*
		// id, not the render-CDN file id, so it can't be turned into a URL
		// without an extra API round-trip (see #41).
		$icon_url = '';

		// Dedupe by stat_type: bb_player_item_stat has PRIMARY KEY
		// (player_id, slot_type, stat_type), so two rows with the same
		// stat_type would fatally abort the sync INSERT. Keyed by
		// stat_type while building (last non-negated occurrence wins),
		// then re-indexed to a plain 0-indexed list, which also keeps a
		// stable order (first-seen stat_type position, last value).
		$stats_by_type = array();
		if (!empty($item['stats']) && is_array($item['stats']))
		{
			foreach ($item['stats'] as $stat)
			{
				if (!empty($stat['is_negated']))
				{
					continue;
				}
				$stat_type = isset($stat['type']['type']) ? (string) $stat['type']['type'] : '';
				if ($stat_type === '')
				{
					continue;
				}
				$stats_by_type[$stat_type] = array('stat_type' => $stat_type, 'stat_value' => isset($stat['value']) ? (int) $stat['value'] : 0);
			}
		}
		$stats = array_values($stats_by_type);

		return array(
			'slot_type' => $slot_type,
			'equipment' => array(
				'item_id'      => isset($item['item']['id']) ? (int) $item['item']['id'] : 0,
				'item_name'    => isset($item['name']) ? (string) $item['name'] : '',
				'item_level'   => isset($item['level']['value']) ? (int) $item['level']['value'] : 0,
				'quality'      => isset($item['quality']['type']) ? (string) $item['quality']['type'] : '',
				'icon_url'     => $icon_url,
				'enchant_id'   => $enchant_id,
				'gem_ids'      => $gem_ids,
				'bonus_ids'    => $bonus_ids,
				'set_item_ids' => $set_item_ids,
			),
			'stats' => $stats,
		);
	}

	/**
	 * Resolve an item's icon render URL via Battle.net's item-media endpoint,
	 * cached by item_id (icon art never changes, and the same item is
	 * equipped by many characters/guilds — see #41).
	 *
	 * Self-contained rather than reusing a caller-supplied facade: the
	 * 'character'-typed battlenet facade sync_one_equipment() already has
	 * doesn't populate ->static_data (each battlenet instance only builds
	 * the one resource type it was constructed for — see battlenet.php's
	 * switch). Mirrors load_crest_asset()'s pattern of building its own
	 * dedicated 'playable-data' facade. Only reaches the container/API on
	 * an actual cache miss, so a fully cache-warm sync never pays for it.
	 *
	 * @param int    $item_id
	 * @param string $region
	 * @return string Icon render URL, or '' if unresolvable
	 */
	protected function resolve_item_icon_url(int $item_id, string $region): string
	{
		if ($item_id <= 0)
		{
			return '';
		}

		$cache_key = self::CACHE_KEY_ITEM_ICON . '_' . $item_id;
		$cached = $this->cache->get($cache_key);
		if ($cached !== false)
		{
			return $cached;
		}

		global $phpbb_container;
		$game = $this->get_game_from_db($phpbb_container);
		if (!$game || trim($game->getApikey()) == '')
		{
			return '';
		}

		$ext_path = $this->get_ext_path($phpbb_container);
		$api = $this->create_battlenet('playable-data', $region, $game->getApikey(), $game->get_apilocale(), $game->get_privkey(), $ext_path);
		if (!isset($api->static_data))
		{
			// Defensive: a caller-overridden create_battlenet() (e.g. a test
			// double built for a different battlenet resource type) could
			// return a facade that never populated ->static_data. Degrade to
			// no-icon rather than a fatal call-on-null — this is best-effort
			// enrichment, not a required field.
			return '';
		}
		$data = $api->static_data->getItemMedia($item_id);
		unset($api);

		$icon_url = '';
		if (isset($data['response']['assets']) && is_array($data['response']['assets']))
		{
			foreach ($data['response']['assets'] as $asset)
			{
				if (isset($asset['key'], $asset['value']) && $asset['key'] === 'icon')
				{
					$icon_url = (string) $asset['value'];
					break;
				}
			}
		}

		if ($icon_url !== '')
		{
			$this->cache->put($cache_key, $icon_url, self::STATIC_CACHE_TTL);
		}

		return $icon_url;
	}

	/**
	 * Colon-join the nested item ids of a list (sockets / set items).
	 *
	 * @param array  $list
	 * @param string $key  Nested key holding ['id'] (e.g. 'item')
	 * @return string
	 */
	private function join_item_ids(array $list, string $key): string
	{
		$ids = array();
		foreach ($list as $entry)
		{
			if (isset($entry[$key]['id']))
			{
				$ids[] = (int) $entry[$key]['id'];
			}
		}
		return implode(':', $ids);
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

		if (!$this->write_file($local_file, $image_data))
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
				'rank_name'   => $this->lang('WOW_API_DEFAULT_RANK_NAME', $rank_id),
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
					// A character deactivated by an earlier sync (or by hand) that is
					// back in the roster response is active again. Sync mirrors the API.
					'player_status'       => 1,
					'player_armory_url'   => $this->get_player_armory_url($char['name'], $realm_slug, $region),
					'last_update'         => time(),
				);

				$sql = 'UPDATE ' . $this->bb_players_table . '
						SET ' . $this->db->sql_build_array('UPDATE', $sql_ary) . '
						WHERE player_id = ' . $player_id;
				$this->db->sql_query($sql);
			}
		}

		// Characters in the DB but absent from the roster response have left the
		// guild. Soft-delete them: bb_players rows are referenced by DKP and raid
		// history in bbguild core, so the row must survive. player_outdate is
		// deliberately untouched - it is a date the user sets in the UCP character
		// form, not something a sync should overwrite.
		//
		// Note this is not affected by $min_level: $newplayers is built from every
		// member in the response regardless of level, so a character below the
		// min_armory threshold is simply never inserted - it is not treated as
		// departed.
		$to_deactivate = array_diff($oldplayers, $newplayers);
		if (!empty($to_deactivate))
		{
			$departed_ids = array();
			foreach ($to_deactivate as $player_key)
			{
				$hex = bin2hex($player_key);
				if (isset($player_ids[$hex]))
				{
					$departed_ids[] = (int) $player_ids[$hex];
				}
			}

			if (!empty($departed_ids))
			{
				$sql = 'UPDATE ' . $this->bb_players_table . '
						SET player_status = 0, last_update = ' . time() . '
						WHERE ' . $this->db->sql_in_set('player_id', $departed_ids);
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
		$api = $this->create_battlenet('playable-data', $region, $game->getApikey(), $game->get_apilocale(), $game->get_privkey(), $ext_path, 3600, $edition);
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
		$api = $this->create_battlenet('playable-data', $region, $game->getApikey(), $game->get_apilocale(), $game->get_privkey(), $ext_path, 3600, $edition);
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
		$wow_ext_path = $phpbb_container->get('ext.manager')->get_extension_path('avathar/bbguildwow', true);

		// Store emblems in phpBB's upload directory (files/bbguildwow/emblems/)
		$upload_path = $phpbb_container->get('config')['upload_path'];
		$emblem_rel = $upload_path . '/bbguildwow/emblems/';
		$emblem_dir = $phpbb_root_path . $emblem_rel;
		$this->ensure_dir($emblem_dir);

		$safe_name = str_replace(' ', '_', $guild_name);
		$filename = $region . '_' . $realm . '_' . $safe_name . '.png';
		$imgfile = $emblem_dir . $filename;

		// Return cached image if fresh (< 24h)
		if ($this->filesystem->exists($imgfile) && (filemtime($imgfile) + 86400) > time())
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

		if (!$this->filesystem->exists($ringURL) || !$this->filesystem->exists($shadowURL) || !$this->filesystem->exists($bgURL))
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

		if ($this->filesystem->exists($overlayURL))
		{
			$overlay = imagecreatefrompng($overlayURL);
			$overlay_size = getimagesize($overlayURL);
			imagecopy($imgOut, $overlay, $x, $y + 2, 0, 0, $overlay_size[0], $overlay_size[1]);
			imagedestroy($overlay);
		}

		if ($this->filesystem->exists($hooksURL))
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
	 * @param string $wow_ext_path Path to bbguildwow extension
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
		if ($this->filesystem->exists($local_path))
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
		$api = $this->create_battlenet('playable-data', $region, $game->getApikey(), $game->get_apilocale(), $game->get_privkey(), $ext_path);

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

		// Save locally for future use (best-effort)
		$this->write_file($local_path, $image_data);

		$img = @imagecreatefromstring($image_data);
		return ($img !== false) ? $img : false;
	}

	/**
	 * Load WoW game record from the database to get API credentials.
	 *
	 * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
	 * @return \avathar\bbguild\model\games\game|null
	 */
	protected function get_game_from_db($container)
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
	protected function get_ext_path($container)
	{
		$ext_manager = $container->get('ext.manager');
		return $ext_manager->get_extension_path('avathar/bbguild', true);
	}
}
