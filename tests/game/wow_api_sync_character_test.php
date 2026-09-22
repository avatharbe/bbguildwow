<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguildwow\tests\game;

use PHPUnit\Framework\TestCase;
use avathar\bbguildwow\game\wow_api;
use avathar\bbguildwow\api\battlenet;

/**
 * wow_api subclass overriding the 3 sync_one_* methods and
 * get_game_from_db()/get_ext_path() so sync_character()'s orchestration
 * logic can be tested without a real Symfony container or Battle.net API.
 */
class wow_api_with_stubbed_sync_one extends wow_api
{
	public $specs_result = array('success' => true, 'error_code' => null, 'stop_batch' => false);
	public $profile_result = array('success' => true, 'error_code' => null, 'stop_batch' => false);
	public $equipment_result = array('success' => true, 'error_code' => null, 'stop_batch' => false);
	public $portrait_result = array('success' => true, 'error_code' => null, 'stop_batch' => false);
	public $stub_game;
	public $create_battlenet_called = false;

	/** @var string|null Edition argument captured from the last create_battlenet() call (#362) */
	public $create_battlenet_edition_arg;

	/** @var int Incremented on each sync_one_profile() call (#42), so short-circuit tests can assert it stays 0 */
	public $profile_call_count = 0;

	/** @var int Incremented on each sync_one_equipment() call, so short-circuit tests (#362) can assert it stays 0 */
	public $equipment_call_count = 0;

	/** @var int Incremented on each sync_one_portrait() call, so short-circuit tests (#362) can assert it stays 0 */
	public $portrait_call_count = 0;

	protected function sync_one_specs(array $player, $api, bool $mark_unavailable = true): array
	{
		return $this->specs_result;
	}

	protected function sync_one_profile(array $player, $api): array
	{
		$this->profile_call_count++;
		return $this->profile_result;
	}

	protected function sync_one_equipment(array $player, $api, string $equipment_table, string $stat_table, bool $mark_unavailable = true): array
	{
		$this->equipment_call_count++;
		return $this->equipment_result;
	}

	protected function sync_one_portrait(array $player, $api, string $portrait_dir, string $portrait_rel, string $upload_path, string $phpbb_root_path, bool $mark_unavailable = true): array
	{
		$this->portrait_call_count++;
		return $this->portrait_result;
	}

	protected function get_game_from_db($container)
	{
		return $this->stub_game;
	}

	protected function get_ext_path($container)
	{
		return '/ext/path/';
	}

	protected function create_battlenet(string $api, string $region, string $apikey, string $locale, string $privkey, string $ext_path = '', int $cache_ttl = 3600, string $edition = 'retail'): battlenet
	{
		$this->create_battlenet_called = true;
		$this->create_battlenet_edition_arg = $edition;

		// wow_api_with_stubbed_sync_one is not itself a TestCase (it extends
		// wow_api, a production class), so PHPUnit's getMockBuilder() isn't
		// available here. sync_one_specs()/sync_one_equipment()/sync_one_portrait()
		// are all stubbed below to ignore $api entirely, so any battlenet
		// instance satisfying the return type is sufficient — build one via
		// reflection without running its real (HTTP-touching) constructor.
		return (new \ReflectionClass(battlenet::class))->newInstanceWithoutConstructor();
	}
}

class stub_game
{
	private $apikey;

	public function __construct(string $apikey)
	{
		$this->apikey = $apikey;
	}

	public function getApikey(): string { return $this->apikey; }
	public function get_apilocale(): string { return 'en_US'; }
	public function get_privkey(): string { return 'secret'; }
}

class wow_api_sync_character_test extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();

		// sync_character() reads $phpbb_container/$phpbb_root_path directly
		// (get_game_from_db()/get_ext_path() are stubbed in the subclass
		// below and ignore the container arg, but the equipment table
		// names and upload_path lookups in sync_character() itself are
		// not behind an overridable seam), so both globals must be
		// populated for the "credentials present" test cases to run.
		global $phpbb_container, $phpbb_root_path;

		$phpbb_root_path = '/tmp/';

		$container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
		$container->method('getParameter')->willReturnMap(array(
			array('avathar.bbguildwow.tables.bb_player_equipment', 'bb_player_equipment'),
			array('avathar.bbguildwow.tables.bb_player_item_stat', 'bb_player_item_stat'),
			array('avathar.bbguild.tables.bb_guild', 'bb_guild'),
		));
		$container->method('get')->with('config')->willReturn(array('upload_path' => 'files'));
		$phpbb_container = $container;
	}

	/**
	 * @param array|null $guild_row Row to script the guild-edition SELECT's sql_fetchrow()
	 *                              to return (e.g. ['game_edition' => 'classic_era']). Null
	 *                              leaves the db mock's sql_fetchrow() unconfigured (returns
	 *                              null), matching a guild row that can't be found — edition
	 *                              should then fall back to 'retail'.
	 */
	private function make_api(array $guild_row = null): wow_api_with_stubbed_sync_one
	{
		$cache = $this->createMock(\phpbb\cache\service::class);
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		if ($guild_row !== null)
		{
			$db->method('sql_fetchrow')->willReturn($guild_row);
		}
		$filesystem = new \phpbb\filesystem\filesystem();

		return new wow_api_with_stubbed_sync_one($cache, $db, 'phpbb_guild_wow', 'phpbb_players', 'phpbb_ranks', $filesystem);
	}

	public function test_sync_character_true_when_all_three_succeed(): void
	{
		$api = $this->make_api();
		$api->stub_game = new stub_game('client-id');

		$this->assertTrue($api->sync_character(array('player_id' => 1, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn', 'player_region' => 'eu', 'player_guild_id' => 7)));
		$this->assertTrue($api->create_battlenet_called);
		// No guild row found (db mock's sql_fetchrow() unconfigured) -> edition
		// must fall back to 'retail' rather than erroring or passing null (#362).
		$this->assertSame('retail', $api->create_battlenet_edition_arg);
	}

	public function test_sync_character_resolves_and_passes_guild_edition(): void
	{
		$api = $this->make_api(array('game_edition' => 'classic_era'));
		$api->stub_game = new stub_game('client-id');

		$this->assertTrue($api->sync_character(array('player_id' => 1, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn', 'player_region' => 'eu', 'player_guild_id' => 7)));
		$this->assertSame('classic_era', $api->create_battlenet_edition_arg);
	}

	public function test_sync_character_stop_batch_short_circuits_remaining_syncs(): void
	{
		$api = $this->make_api();
		$api->stub_game = new stub_game('client-id');
		$api->specs_result = array('success' => false, 'error_code' => 500, 'stop_batch' => true);

		$this->assertFalse($api->sync_character(array('player_id' => 1, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn', 'player_region' => 'eu', 'player_guild_id' => 7)));
		$this->assertSame(0, $api->profile_call_count);
		$this->assertSame(0, $api->equipment_call_count);
		$this->assertSame(0, $api->portrait_call_count);
	}

	public function test_sync_character_profile_stop_batch_short_circuits_remaining_syncs(): void
	{
		$api = $this->make_api();
		$api->stub_game = new stub_game('client-id');
		$api->profile_result = array('success' => false, 'error_code' => 500, 'stop_batch' => true);

		$this->assertFalse($api->sync_character(array('player_id' => 1, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn', 'player_region' => 'eu', 'player_guild_id' => 7)));
		$this->assertSame(0, $api->equipment_call_count);
		$this->assertSame(0, $api->portrait_call_count);
	}

	public function test_sync_character_true_even_when_profile_fails_without_stop_batch(): void
	{
		// Gender is best-effort enrichment (#42), not one of the #362 sync
		// contract's core fields — a non-fatal profile failure must not fail
		// the whole character sync the way a specs/equipment/portrait
		// failure does.
		$api = $this->make_api();
		$api->stub_game = new stub_game('client-id');
		$api->profile_result = array('success' => false, 'error_code' => 404, 'stop_batch' => false);

		$this->assertTrue($api->sync_character(array('player_id' => 1, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn', 'player_region' => 'eu', 'player_guild_id' => 7)));
		$this->assertSame(1, $api->equipment_call_count);
		$this->assertSame(1, $api->portrait_call_count);
	}

	public function test_sync_character_false_when_specs_fails(): void
	{
		$api = $this->make_api();
		$api->stub_game = new stub_game('client-id');
		$api->specs_result = array('success' => false, 'error_code' => 404, 'stop_batch' => false);

		$this->assertFalse($api->sync_character(array('player_id' => 1, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn', 'player_region' => 'eu', 'player_guild_id' => 1)));
	}

	public function test_sync_character_false_when_equipment_fails(): void
	{
		$api = $this->make_api();
		$api->stub_game = new stub_game('client-id');
		$api->equipment_result = array('success' => false, 'error_code' => 500, 'stop_batch' => true);

		$this->assertFalse($api->sync_character(array('player_id' => 1, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn', 'player_region' => 'eu', 'player_guild_id' => 1)));
	}

	public function test_sync_character_false_when_portrait_fails(): void
	{
		$api = $this->make_api();
		$api->stub_game = new stub_game('client-id');
		$api->portrait_result = array('success' => false, 'error_code' => 'no_avatar', 'stop_batch' => false);

		$this->assertFalse($api->sync_character(array('player_id' => 1, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn', 'player_region' => 'eu', 'player_guild_id' => 1)));
	}

	public function test_sync_character_false_when_credentials_missing_and_never_calls_create_battlenet(): void
	{
		$api = $this->make_api();
		$api->stub_game = new stub_game(''); // empty apikey

		$this->assertFalse($api->sync_character(array('player_id' => 1, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn', 'player_region' => 'eu')));
		$this->assertFalse($api->create_battlenet_called);
	}

	public function test_sync_character_false_when_game_row_missing(): void
	{
		$api = $this->make_api();
		$api->stub_game = null;

		$this->assertFalse($api->sync_character(array('player_id' => 1, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn', 'player_region' => 'eu')));
		$this->assertFalse($api->create_battlenet_called);
	}
}
