<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguildwow\tests\integration;

use avathar\bbguildwow\api\battlenet;
use avathar\bbguildwow\api\battlenet_character;
use avathar\bbguildwow\game\wow_api;

/**
 * battlenet_character subclass pointed at the local mock server, in the
 * same spirit as mock_battlenet_guild in oauth_token_lifecycle_test.php.
 */
class mock_battlenet_character_for_portraits extends battlenet_character
{
	public function __construct(\phpbb\cache\service $cache, string $base_url, string $region = 'us', int $cache_ttl = 3600)
	{
		parent::__construct($cache, $region, $cache_ttl);
		$this->api_url = array($region => $base_url);
		$this->token_url = array($region => $base_url . 'token');
		$this->apikey = 'test_client_id';
		$this->privkey = 'test_client_secret';
		$this->locale = 'en_US';
	}
}

/**
 * Returns a battlenet facade whose ->character is already the mock
 * resource — bypasses battlenet's real constructor (which always builds
 * prod-URL resources) entirely.
 */
class mock_battlenet_character_facade extends battlenet
{
	public function __construct(battlenet_character $character)
	{
		$this->character = $character;
	}
}

/**
 * wow_api subclass that redirects create_battlenet() to the mock facade.
 */
class wow_api_with_mock_character extends wow_api
{
	/** @var battlenet_character */
	public $mock_resource;

	protected function create_battlenet(string $api, string $region, string $apikey, string $locale, string $privkey, string $ext_path = '', int $cache_ttl = 3600, string $edition = 'retail'): battlenet
	{
		return new mock_battlenet_character_facade($this->mock_resource);
	}
}

/**
 * @group integration
 */
class sync_portraits_test extends mock_battlenet_test_case
{
	static protected function setup_extensions()
	{
		return array('avathar/bbguild', 'avathar/bbguildwow');
	}

	protected function setUp(): void
	{
		parent::setUp();

		// sync_portraits() reads global $phpbb_container->get('config')['upload_path']
		// directly (it's called in-process, not through the board under test's own
		// HTTP-driven container) — this test's in-process $phpbb_container is the
		// bare phpbb_mock_container_builder the functional test framework leaves
		// behind, which has no 'config' service registered. Populate the one key
		// this code path needs.
		global $phpbb_container;
		if (!$phpbb_container->has('config'))
		{
			$phpbb_container->set('config', new \phpbb\config\config(array('upload_path' => 'files')));
		}
	}

	private function get_table_prefix(): string
	{
		return self::$config['table_prefix'];
	}

	/**
	 * @return int the inserted player_id
	 */
	private function seed_player(string $name, string $realm, string $portrait_url = ''): int
	{
		$db = $this->get_db();
		$db->sql_query('INSERT INTO ' . $this->get_table_prefix() . 'bb_players ' . $db->sql_build_array('INSERT', array(
			'game_id'             => 'wow',
			'player_name'         => $name,
			'player_realm'        => $realm,
			'player_region'       => 'us',
			'player_guild_id'     => 1,
			'player_status'       => 1,
			'player_portrait_url' => $portrait_url,
		)));

		return (int) $db->sql_nextid();
	}

	/**
	 * No real DI container is reachable from inside a phpbb_functional_test_case
	 * subclass (Goutte drives the board under test over real HTTP, in a
	 * separate PHP process — there is no in-process container to fetch).
	 * Every dependency wow_api's constructor needs is either directly
	 * constructible (filesystem, table-name strings via table_prefix) or,
	 * for $cache, inert here: create_battlenet() is overridden below and
	 * never touches $this->cache, so any \phpbb\cache\service stand-in works.
	 */
	private function make_api(): wow_api_with_mock_character
	{
		return new wow_api_with_mock_character(
			$this->make_stateful_cache(),
			$this->get_db(),
			$this->get_table_prefix() . 'bb_guild_wow',
			$this->get_table_prefix() . 'bb_players',
			$this->get_table_prefix() . 'bb_ranks',
			new \phpbb\filesystem\filesystem()
		);
	}

	public function test_successful_fetch_falls_back_to_remote_avatar_url(): void
	{
		// download_portrait() will fail to file_get_contents() this URL in a
		// sandboxed test run (no real network) — the code's own documented
		// fallback is to store the raw avatar URL in that case, which is the
		// only deterministic outcome achievable without extending the mock
		// server to serve raw binary bytes (see plan Scope note #3).
		$this->configure_mock_routes(array(
			'/token' => array(array('status' => 200, 'body' => array('access_token' => 'tok', 'expires_in' => 3600))),
			'/profile/wow/character/area-52/sajaki/character-media' => array(
				array('status' => 200, 'body' => array('assets' => array(
					array('key' => 'avatar', 'value' => 'https://render.worldofwarcraft.com/avatar.jpg'),
					array('key' => 'main', 'value' => 'https://render.worldofwarcraft.com/render.jpg'),
				))),
			),
		));

		$player_id = $this->seed_player('Sajaki', 'area-52');

		$api = $this->make_api();
		$api->mock_resource = new mock_battlenet_character_for_portraits($this->make_stateful_cache(), self::base_url(), 'us');

		$result = $api->sync_portraits(1, 'us', 'test_client_id', 'en_US', 'test_client_secret');

		$this->assertSame(1, $result['count']);

		$db = $this->get_db();
		$sql_result = $db->sql_query('SELECT player_portrait_url, player_render_url FROM ' . $this->get_table_prefix() . 'bb_players WHERE player_id = ' . $player_id);
		$row = $db->sql_fetchrow($sql_result);
		$db->sql_freeresult($sql_result);

		$this->assertSame('https://render.worldofwarcraft.com/avatar.jpg', $row['player_portrait_url']);
		$this->assertSame('https://render.worldofwarcraft.com/render.jpg', $row['player_render_url']);
	}

	public function test_404_marks_portrait_unavailable_and_is_not_retried(): void
	{
		$this->configure_mock_routes(array(
			'/token' => array(array('status' => 200, 'body' => array('access_token' => 'tok', 'expires_in' => 3600))),
			'/profile/wow/character/area-53/sajaki/character-media' => array(
				array('status' => 404, 'body' => array('code' => 404, 'detail' => 'Not Found')),
			),
		));

		// Distinct realm from the other tests in this class — phpbb_functional_test_case
		// does not reset DB state between test methods, and bb_players has a
		// UNIQUE(player_guild_id, player_name, player_realm) constraint.
		$player_id = $this->seed_player('Sajaki', 'area-53');

		$api = $this->make_api();
		$api->mock_resource = new mock_battlenet_character_for_portraits($this->make_stateful_cache(), self::base_url(), 'us');

		$result = $api->sync_portraits(1, 'us', 'test_client_id', 'en_US', 'test_client_secret');

		$this->assertSame(0, $result['count']);

		$db = $this->get_db();
		$sql_result = $db->sql_query('SELECT player_portrait_url FROM ' . $this->get_table_prefix() . 'bb_players WHERE player_id = ' . $player_id);
		$this->assertSame('N/A', $db->sql_fetchfield('player_portrait_url'));
		$db->sql_freeresult($sql_result);
	}

	public function test_missing_avatar_asset_counts_as_failure_without_marking_na(): void
	{
		$this->configure_mock_routes(array(
			'/token' => array(array('status' => 200, 'body' => array('access_token' => 'tok', 'expires_in' => 3600))),
			'/profile/wow/character/area-54/sajaki/character-media' => array(
				array('status' => 200, 'body' => array('assets' => array())),
			),
		));

		// Distinct realm from the other tests in this class — see note above.
		$player_id = $this->seed_player('Sajaki', 'area-54');

		$api = $this->make_api();
		$api->mock_resource = new mock_battlenet_character_for_portraits($this->make_stateful_cache(), self::base_url(), 'us');

		$result = $api->sync_portraits(1, 'us', 'test_client_id', 'en_US', 'test_client_secret');

		$this->assertSame(0, $result['count']);
		$this->assertArrayHasKey('no_avatar', $result['errors']);

		$db = $this->get_db();
		$sql_result = $db->sql_query('SELECT player_portrait_url FROM ' . $this->get_table_prefix() . 'bb_players WHERE player_id = ' . $player_id);
		// Not marked N/A — only a 404 does that; an empty assets array on a
		// 200 response is left as-is so a later sync attempt retries it.
		$this->assertSame('', $db->sql_fetchfield('player_portrait_url'));
		$db->sql_freeresult($sql_result);
	}
}
