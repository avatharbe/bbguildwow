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

class mock_battlenet_character_for_equipment extends battlenet_character
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

class mock_battlenet_character_facade_equipment extends battlenet
{
	public function __construct(battlenet_character $character)
	{
		$this->character = $character;
	}
}

class wow_api_with_mock_character_equipment extends wow_api
{
	public $mock_resource;

	protected function create_battlenet(string $api, string $region, string $apikey, string $locale, string $privkey, string $ext_path = '', int $cache_ttl = 3600, string $edition = 'retail'): battlenet
	{
		return new mock_battlenet_character_facade_equipment($this->mock_resource);
	}
}

/**
 * @group integration
 */
class equipment_sync_test extends mock_battlenet_test_case
{
	// Distinct per test FILE, not just per test method: phpbb_functional_test_case
	// never resets DB state between classes in the same suite run, and
	// sync_equipment() selects every player in the guild with no per-player
	// scoping — sharing guild_id=1 with sibling integration test files would
	// make this file's players visible to (and vice versa) sync_portraits_test.php's
	// and sync_specs_test.php's sync calls, turning "exactly 1 succeeded"
	// assertions into accidentally-true rather than deliberately-true checks.
	private const GUILD_ID = 30013;

	static protected function setup_extensions()
	{
		return array('avathar/bbguild', 'avathar/bbguildwow');
	}

	protected function setUp(): void
	{
		parent::setUp();

		// sync_equipment() reads global $phpbb_container->getParameter(...) for
		// both table names directly (it's called in-process, not through the
		// board under test's own HTTP-driven container). This test's in-process
		// $phpbb_container is the bare phpbb_mock_container_builder the
		// functional test framework leaves behind, which has no parameters
		// registered by default. Populate the two this code path needs.
		global $phpbb_container;
		if (!$phpbb_container->hasParameter('avathar.bbguildwow.tables.bb_player_equipment'))
		{
			$phpbb_container->setParameter('avathar.bbguildwow.tables.bb_player_equipment', $this->get_table_prefix() . 'bb_player_equipment');
		}
		if (!$phpbb_container->hasParameter('avathar.bbguildwow.tables.bb_player_item_stat'))
		{
			$phpbb_container->setParameter('avathar.bbguildwow.tables.bb_player_item_stat', $this->get_table_prefix() . 'bb_player_item_stat');
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
			'player_region'   => 'us',
			'player_guild_id' => self::GUILD_ID,
			'player_status'   => 1,
		)));

		return (int) $db->sql_last_inserted_id();
	}

	/**
	 * No real DI container is reachable from inside a phpbb_functional_test_case
	 * subclass (see sync_portraits_test.php for the full explanation). Every
	 * dependency wow_api's constructor needs is directly constructible or,
	 * for $cache, inert — create_battlenet() is overridden below.
	 */
	private function make_api(): wow_api_with_mock_character_equipment
	{
		return new wow_api_with_mock_character_equipment(
			$this->make_stateful_cache(),
			$this->get_db(),
			$this->get_table_prefix() . 'bb_guild_wow',
			$this->get_table_prefix() . 'bb_players',
			$this->get_table_prefix() . 'bb_ranks',
			new \phpbb\filesystem\filesystem()
		);
	}

	private function equipped_items(array $slots): array
	{
		$items = array();
		foreach ($slots as $slot => $item_id)
		{
			$items[] = array(
				'slot' => array('type' => $slot),
				'item' => array('id' => $item_id),
				'name' => $slot . ' item',
				'level' => array('value' => 400),
				'quality' => array('type' => 'EPIC'),
			);
		}

		return $items;
	}

	public function test_first_sync_inserts_equipment_rows(): void
	{
		$this->configure_mock_routes(array(
			'/token' => array(array('status' => 200, 'body' => array('access_token' => 'tok', 'expires_in' => 3600))),
			'/profile/wow/character/area-252/sajaki/equipment' => array(
				array('status' => 200, 'body' => array('equipped_items' => $this->equipped_items(array('HEAD' => 1001, 'CHEST' => 1002)))),
			),
		));

		$player_id = $this->seed_player('Sajaki', 'area-252');
		$equipment_table = $this->get_table_prefix() . 'bb_player_equipment';

		$api = $this->make_api();
		$api->mock_resource = new mock_battlenet_character_for_equipment($this->make_stateful_cache(), self::base_url(), 'us');

		$result = $api->sync_equipment(self::GUILD_ID, 'us', 'test_client_id', 'en_US', 'test_client_secret');

		$this->assertSame(1, $result['count']);

		$db = $this->get_db();
		$sql_result = $db->sql_query('SELECT slot_type, item_id FROM ' . $equipment_table . ' WHERE player_id = ' . $player_id . ' ORDER BY slot_type');
		$rows = array();
		while ($row = $db->sql_fetchrow($sql_result))
		{
			$rows[$row['slot_type']] = (int) $row['item_id'];
		}
		$db->sql_freeresult($sql_result);

		$this->assertSame(array('CHEST' => 1002, 'HEAD' => 1001), $rows);
	}

	public function test_resync_replaces_changed_item_and_drops_unequipped_slot(): void
	{
		$equipment_table = $this->get_table_prefix() . 'bb_player_equipment';

		// Distinct realm from the other test in this class — phpbb_functional_test_case
		// does not reset DB state between test methods, and bb_players has a
		// UNIQUE(player_guild_id, player_name, player_realm) constraint.
		$player_id = $this->seed_player('Sajaki', 'area-253');

		// First sync: HEAD + CHEST.
		$this->configure_mock_routes(array(
			'/token' => array(array('status' => 200, 'body' => array('access_token' => 'tok', 'expires_in' => 3600))),
			'/profile/wow/character/area-253/sajaki/equipment' => array(
				array('status' => 200, 'body' => array('equipped_items' => $this->equipped_items(array('HEAD' => 1001, 'CHEST' => 1002)))),
			),
		));
		$api = $this->make_api();
		$api->mock_resource = new mock_battlenet_character_for_equipment($this->make_stateful_cache(), self::base_url(), 'us');
		$api->sync_equipment(self::GUILD_ID, 'us', 'test_client_id', 'en_US', 'test_client_secret');

		// The staleness query only inspects the HEAD row's last_update, so
		// back-date it to force this player to be picked up again — there is
		// no way to fast-forward time() itself in this harness.
		$db = $this->get_db();
		$db->sql_query('UPDATE ' . $equipment_table . ' SET last_update = ' . (time() - 90000) . " WHERE player_id = $player_id AND slot_type = 'HEAD'");

		// Second sync: HEAD changed item, CHEST unequipped (no longer present).
		$this->configure_mock_routes(array(
			'/token' => array(array('status' => 200, 'body' => array('access_token' => 'tok', 'expires_in' => 3600))),
			'/profile/wow/character/area-253/sajaki/equipment' => array(
				array('status' => 200, 'body' => array('equipped_items' => $this->equipped_items(array('HEAD' => 9999)))),
			),
		));
		$api2 = $this->make_api();
		$api2->mock_resource = new mock_battlenet_character_for_equipment($this->make_stateful_cache(), self::base_url(), 'us');
		$result = $api2->sync_equipment(self::GUILD_ID, 'us', 'test_client_id', 'en_US', 'test_client_secret');

		$this->assertSame(1, $result['count']);

		$sql_result = $db->sql_query('SELECT slot_type, item_id FROM ' . $equipment_table . ' WHERE player_id = ' . $player_id);
		$rows = array();
		while ($row = $db->sql_fetchrow($sql_result))
		{
			$rows[$row['slot_type']] = (int) $row['item_id'];
		}
		$db->sql_freeresult($sql_result);

		// CHEST is gone (unequipped), HEAD reflects the new item — not both
		// the old and new HEAD rows, proving the delete-then-reinsert wipes
		// the player's prior loadout rather than merging into it.
		$this->assertSame(array('HEAD' => 9999), $rows);
	}
}
