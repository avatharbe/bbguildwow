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
 * Stub for battlenet::$guild (public, untyped) so getActivity() can be
 * controlled without a real Battle.net HTTP call.
 */
class stub_guild_activity_resource
{
	public $activity_response;
	public $last_realm_slug;
	public $last_name_slug;

	public function getActivity(string $realm_slug, string $name_slug): array
	{
		$this->last_realm_slug = $realm_slug;
		$this->last_name_slug = $name_slug;

		return $this->activity_response;
	}
}

/**
 * wow_api subclass overriding get_game_from_db()/get_ext_path()/create_battlenet()
 * so fetch_guild_activity() can be tested without a real Symfony container or
 * Battle.net API — mirrors wow_api_sync_character_test.php's approach.
 */
class wow_api_with_stubbed_activity extends wow_api
{
	public $stub_game;
	public $stub_guild_resource;
	public $create_battlenet_called = false;

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

		$instance = (new \ReflectionClass(battlenet::class))->newInstanceWithoutConstructor();
		$instance->guild = $this->stub_guild_resource;

		return $instance;
	}
}

class stub_game_for_activity
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

class wow_api_fetch_guild_activity_test extends TestCase
{
	private function make_api(): wow_api_with_stubbed_activity
	{
		$cache = $this->createMock(\phpbb\cache\service::class);
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$filesystem = new \phpbb\filesystem\filesystem();

		return new wow_api_with_stubbed_activity($cache, $db, 'phpbb_guild_wow', 'phpbb_players', 'phpbb_ranks', $filesystem);
	}

	public function test_returns_false_when_credentials_missing(): void
	{
		$api = $this->make_api();
		$api->stub_game = new stub_game_for_activity(''); // empty apikey

		$result = $api->fetch_guild_activity('Test Guild', 'Area 52', 'us');

		$this->assertFalse($result);
		$this->assertFalse($api->create_battlenet_called);
	}

	public function test_returns_false_when_game_row_missing(): void
	{
		$api = $this->make_api();
		$api->stub_game = null;

		$result = $api->fetch_guild_activity('Test Guild', 'Area 52', 'us');

		$this->assertFalse($result);
		$this->assertFalse($api->create_battlenet_called);
	}

	public function test_returns_activity_response_on_success(): void
	{
		$api = $this->make_api();
		$api->stub_game = new stub_game_for_activity('client-id');
		$api->stub_guild_resource = new stub_guild_activity_resource();
		$api->stub_guild_resource->activity_response = array(
			'response' => array(
				'activities' => array(
					array('activity' => array('type' => 'CHARACTER_ACHIEVEMENT'), 'timestamp' => 1700000000000),
				),
			),
		);

		$result = $api->fetch_guild_activity('Test Guild', 'Area 52', 'us');

		$this->assertIsArray($result);
		$this->assertCount(1, $result['activities']);
		$this->assertSame('area-52', $api->stub_guild_resource->last_realm_slug);
		$this->assertSame('test-guild', $api->stub_guild_resource->last_name_slug);
	}

	public function test_returns_false_when_response_missing(): void
	{
		$api = $this->make_api();
		$api->stub_game = new stub_game_for_activity('client-id');
		$api->stub_guild_resource = new stub_guild_activity_resource();
		$api->stub_guild_resource->activity_response = array('code' => 404, 'detail' => 'Not found');

		$result = $api->fetch_guild_activity('Test Guild', 'Area 52', 'us');

		$this->assertFalse($result);
	}
}
