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

class mock_battlenet_character_for_sync_character extends battlenet_character
{
	public function __construct(\phpbb\cache\service $cache, string $base_url, string $region = 'eu', int $cache_ttl = 3600)
	{
		parent::__construct($cache, $region, $cache_ttl);
		$this->api_url = array($region => $base_url);
		$this->token_url = array($region => $base_url . 'token');
		$this->apikey = 'test_client_id';
		$this->privkey = 'test_client_secret';
		$this->locale = 'en_US';
	}
}

class mock_battlenet_character_facade_sync_character extends battlenet
{
	public function __construct(battlenet_character $character)
	{
		$this->character = $character;
	}
}

class wow_api_with_mock_character_sync_character extends wow_api
{
	public $mock_resource;

	protected function create_battlenet(string $api, string $region, string $apikey, string $locale, string $privkey, string $ext_path = '', int $cache_ttl = 3600, string $edition = 'retail'): battlenet
	{
		return new mock_battlenet_character_facade_sync_character($this->mock_resource);
	}
}

/**
 * @group integration
 */
class sync_character_test extends mock_battlenet_test_case
{
	// Own guild_id + realm range, distinct from sync_specs_test.php (30012),
	// sync_portraits_test.php, and equipment_sync_test.php — same reason as
	// those files: no per-player scoping in the queries under test.
	private const GUILD_ID = 30020;

	static protected function setup_extensions()
	{
		return array('avathar/bbguild', 'avathar/bbguildwow');
	}

	protected function setUp(): void
	{
		parent::setUp();

		// wow_api::sync_character() -> get_game_from_db() calls
		// $phpbb_container->get('user') directly (it's called in-process, not
		// through the board under test's own HTTP-driven container, unlike
		// sync_specs_test.php/sync_portraits_test.php/equipment_sync_test.php
		// which exercise their wow_api methods via portrait_controller.php's
		// AJAX routes and so get a fully-populated container for free). This
		// test's in-process $phpbb_container is the bare
		// phpbb_mock_container_builder the functional test framework leaves
		// behind, which has no 'user' service — get_game_from_db()'s bare
		// `catch (\Exception $e)` swallows the resulting "Could not find
		// service: user" completely silently, making sync_character() return
		// false before ever reaching the specs/equipment/portrait sync it's
		// actually testing. avathar\bbguild\model\games\game's constructor
		// strictly type-hints \phpbb\user, so a bare anonymous-class stub
		// (matching the $GLOBALS['user'] stubs in roster_sync_test.php/
		// achievement_sync_test.php) would TypeError — and TypeError extends
		// \Error, not \Exception, so get_game_from_db()'s bare
		// `catch (\Exception $e)` would NOT catch it, turning a clean
		// assertion failure into an uncaught fatal. Use a real PHPUnit mock
		// of \phpbb\user instead, which satisfies the type hint since it's
		// an actual subclass. disableOriginalConstructor() skips needing
		// \phpbb\user's own constructor args; onlyMethods(['add_lang_ext'])
		// stubs get_game_from_db()'s direct call to a no-op; ->lang is set
		// directly as a real property, which shadows \phpbb\user's
		// __get('lang') magic method for reads (there's no __set, so this
		// is safe) — satisfying game's constructor's 5 region lang key
		// reads.
		global $phpbb_container;
		if (!$phpbb_container->has('user'))
		{
			$user_stub = $this->getMockBuilder(\phpbb\user::class)
				->disableOriginalConstructor()
				->onlyMethods(array('add_lang_ext'))
				->getMock();
			$user_stub->lang = array(
				'REGIONEU' => 'Europe',
				'REGIONKR' => 'Korea',
				'REGIONSEA' => 'South-East Asia',
				'REGIONTW' => 'Taiwan',
				'REGIONUS' => 'United States',
			);
			$phpbb_container->set('user', $user_stub);
		}
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
			'player_region'   => 'eu',
			'player_guild_id' => self::GUILD_ID,
			'player_status'   => 1,
			'player_spec'     => '',
			'player_portrait_url' => '',
		)));

		return (int) $db->sql_nextid();
	}

	private function seed_wow_game_credentials(): void
	{
		$db = $this->get_db();
		$db->sql_query('UPDATE ' . $this->get_table_prefix() . "bb_games SET
			apikey = 'test_client_id',
			privkey = 'test_client_secret',
			apilocale = 'en_US',
			region = 'eu'
			WHERE game_id = 'wow'");
	}

	private function make_api(): wow_api_with_mock_character_sync_character
	{
		return new wow_api_with_mock_character_sync_character(
			$this->make_stateful_cache(),
			$this->get_db(),
			$this->get_table_prefix() . 'bb_guild_wow',
			$this->get_table_prefix() . 'bb_players',
			$this->get_table_prefix() . 'bb_ranks',
			new \phpbb\filesystem\filesystem()
		);
	}

	public function test_sync_character_writes_specs_equipment_and_portrait(): void
	{
		$this->seed_wow_game_credentials();

		$this->configure_mock_routes(array(
			'/token' => array(array('status' => 200, 'body' => array('access_token' => 'tok', 'expires_in' => 3600))),
			'/profile/wow/character/area-160/sajaki/specializations' => array(
				array('status' => 200, 'body' => array('active_specialization' => array('name' => 'Fury'))),
			),
			'/profile/wow/character/area-160/sajaki/equipment' => array(
				array('status' => 200, 'body' => array('equipped_items' => array())),
			),
			'/profile/wow/character/area-160/sajaki/character-media' => array(
				array('status' => 200, 'body' => array('assets' => array(
					array('key' => 'avatar', 'value' => 'http://example.invalid/avatar.jpg'),
				))),
			),
		));

		$player_id = $this->seed_player('Sajaki', 'area-160');

		$api = $this->make_api();
		$api->mock_resource = new mock_battlenet_character_for_sync_character($this->make_stateful_cache(), self::base_url(), 'eu');

		$result = $api->sync_character(array(
			'player_id' => $player_id,
			'player_name' => 'Sajaki',
			'player_realm' => 'area-160',
			'player_region' => 'eu',
			'player_guild_id' => self::GUILD_ID,
		));

		$this->assertTrue($result);

		$db = $this->get_db();
		$sql_result = $db->sql_query('SELECT player_spec, player_portrait_url FROM ' . $this->get_table_prefix() . 'bb_players WHERE player_id = ' . $player_id);
		$row = $db->sql_fetchrow($sql_result);
		$db->sql_freeresult($sql_result);

		$this->assertSame('Fury', $row['player_spec']);
		$this->assertSame('http://example.invalid/avatar.jpg', $row['player_portrait_url']);
	}

	public function test_sync_character_returns_false_when_equipment_fetch_fails(): void
	{
		$this->seed_wow_game_credentials();

		$this->configure_mock_routes(array(
			'/token' => array(array('status' => 200, 'body' => array('access_token' => 'tok', 'expires_in' => 3600))),
			'/profile/wow/character/area-161/sajaki/specializations' => array(
				array('status' => 200, 'body' => array('active_specialization' => array('name' => 'Fury'))),
			),
			'/profile/wow/character/area-161/sajaki/equipment' => array(
				array('status' => 500, 'body' => array('code' => 500)),
			),
			'/profile/wow/character/area-161/sajaki/character-media' => array(
				array('status' => 200, 'body' => array('assets' => array(
					array('key' => 'avatar', 'value' => 'http://example.invalid/avatar.jpg'),
				))),
			),
		));

		$player_id = $this->seed_player('Sajaki', 'area-161');

		$api = $this->make_api();
		$api->mock_resource = new mock_battlenet_character_for_sync_character($this->make_stateful_cache(), self::base_url(), 'eu');

		$result = $api->sync_character(array(
			'player_id' => $player_id,
			'player_name' => 'Sajaki',
			'player_realm' => 'area-161',
			'player_region' => 'eu',
			'player_guild_id' => self::GUILD_ID,
		));

		$this->assertFalse($result);
	}
}
