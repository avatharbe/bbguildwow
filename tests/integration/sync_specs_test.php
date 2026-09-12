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

class mock_battlenet_character_for_specs extends battlenet_character
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

class mock_battlenet_character_facade_specs extends battlenet
{
	public function __construct(battlenet_character $character)
	{
		$this->character = $character;
	}
}

class wow_api_with_mock_character_specs extends wow_api
{
	public $mock_resource;

	protected function create_battlenet(string $api, string $region, string $apikey, string $locale, string $privkey, string $ext_path = '', int $cache_ttl = 3600, string $edition = 'retail'): battlenet
	{
		return new mock_battlenet_character_facade_specs($this->mock_resource);
	}
}

/**
 * @group integration
 */
class sync_specs_test extends mock_battlenet_test_case
{
	// Distinct per test FILE, not just per test method: phpbb_functional_test_case
	// never resets DB state between classes in the same suite run, and
	// sync_specs() selects every player in the guild with no per-player
	// scoping — sharing guild_id=1 with sibling integration test files would
	// make this file's players visible to (and vice versa) sync_portraits_test.php's
	// and equipment_sync_test.php's sync calls, turning "exactly 1 succeeded"
	// assertions into accidentally-true rather than deliberately-true checks.
	private const GUILD_ID = 30012;

	static protected function setup_extensions()
	{
		return array('avathar/bbguild', 'avathar/bbguildwow');
	}

	private function get_table_prefix(): string
	{
		return self::$config['table_prefix'];
	}

	private function seed_player(string $name, string $realm): int
	{
		$db = $this->get_db();
		$db->sql_query('INSERT INTO ' . $this->get_table_prefix() . 'bb_players ' . $db->sql_build_array('INSERT', array(
			'game_id'         => 'wow',
			'player_name'     => $name,
			'player_realm'    => $realm,
			'player_region'   => 'us',
			'player_guild_id' => self::GUILD_ID,
			'player_status'   => 1,
			'player_spec'     => '',
		)));

		return (int) $db->sql_nextid();
	}

	/**
	 * No real DI container is reachable from inside a phpbb_functional_test_case
	 * subclass (see sync_portraits_test.php for the full explanation). Every
	 * dependency wow_api's constructor needs is directly constructible or,
	 * for $cache, inert — create_battlenet() is overridden below.
	 */
	private function make_api(): wow_api_with_mock_character_specs
	{
		return new wow_api_with_mock_character_specs(
			$this->make_stateful_cache(),
			$this->get_db(),
			$this->get_table_prefix() . 'bb_guild_wow',
			$this->get_table_prefix() . 'bb_players',
			$this->get_table_prefix() . 'bb_ranks',
			new \phpbb\filesystem\filesystem()
		);
	}

	public function test_successful_fetch_sets_active_spec(): void
	{
		$this->configure_mock_routes(array(
			'/token' => array(array('status' => 200, 'body' => array('access_token' => 'tok', 'expires_in' => 3600))),
			'/profile/wow/character/area-152/sajaki/specializations' => array(
				array('status' => 200, 'body' => array('active_specialization' => array('name' => 'Fury'))),
			),
		));

		$player_id = $this->seed_player('Sajaki', 'area-152');

		$api = $this->make_api();
		$api->mock_resource = new mock_battlenet_character_for_specs($this->make_stateful_cache(), self::base_url(), 'us');

		$result = $api->sync_specs(self::GUILD_ID, 'us', 'test_client_id', 'en_US', 'test_client_secret');

		$this->assertSame(1, $result['count']);

		$db = $this->get_db();
		$sql_result = $db->sql_query('SELECT player_spec FROM ' . $this->get_table_prefix() . 'bb_players WHERE player_id = ' . $player_id);
		$this->assertSame('Fury', $db->sql_fetchfield('player_spec'));
		$db->sql_freeresult($sql_result);
	}

	public function test_404_marks_spec_unavailable(): void
	{
		$this->configure_mock_routes(array(
			'/token' => array(array('status' => 200, 'body' => array('access_token' => 'tok', 'expires_in' => 3600))),
			'/profile/wow/character/area-153/sajaki/specializations' => array(
				array('status' => 404, 'body' => array('code' => 404, 'detail' => 'Not Found')),
			),
		));

		// Distinct realm from the other tests in this class — phpbb_functional_test_case
		// does not reset DB state between test methods, and bb_players has a
		// UNIQUE(player_guild_id, player_name, player_realm) constraint.
		$player_id = $this->seed_player('Sajaki', 'area-153');

		$api = $this->make_api();
		$api->mock_resource = new mock_battlenet_character_for_specs($this->make_stateful_cache(), self::base_url(), 'us');

		$api->sync_specs(self::GUILD_ID, 'us', 'test_client_id', 'en_US', 'test_client_secret');

		$db = $this->get_db();
		$sql_result = $db->sql_query('SELECT player_spec FROM ' . $this->get_table_prefix() . 'bb_players WHERE player_id = ' . $player_id);
		$this->assertSame('N/A', $db->sql_fetchfield('player_spec'));
		$db->sql_freeresult($sql_result);
	}

	public function test_empty_active_specialization_name_counts_as_failure(): void
	{
		$this->configure_mock_routes(array(
			'/token' => array(array('status' => 200, 'body' => array('access_token' => 'tok', 'expires_in' => 3600))),
			'/profile/wow/character/area-154/sajaki/specializations' => array(
				array('status' => 200, 'body' => array('active_specialization' => array())),
			),
		));

		// Distinct realm from the other tests in this class — see note above.
		$player_id = $this->seed_player('Sajaki', 'area-154');

		$api = $this->make_api();
		$api->mock_resource = new mock_battlenet_character_for_specs($this->make_stateful_cache(), self::base_url(), 'us');

		$result = $api->sync_specs(self::GUILD_ID, 'us', 'test_client_id', 'en_US', 'test_client_secret');

		$this->assertArrayHasKey('no_spec', $result['errors']);

		$db = $this->get_db();
		$sql_result = $db->sql_query('SELECT player_spec FROM ' . $this->get_table_prefix() . 'bb_players WHERE player_id = ' . $player_id);
		$this->assertSame('N/A', $db->sql_fetchfield('player_spec'));
		$db->sql_freeresult($sql_result);
	}
}
