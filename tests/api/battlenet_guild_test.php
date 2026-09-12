<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguildwow\tests\api;

use PHPUnit\Framework\TestCase;

/**
 * Test subclass that captures consume() calls instead of making HTTP requests.
 */
class battlenet_guild_test_wrapper extends \avathar\bbguildwow\api\battlenet_guild
{
	/** @var string Last method passed to consume() */
	public $last_method = '';

	/** @var array Last params passed to consume() */
	public $last_params = array();

	/**
	 * Override constructor to skip parent's trigger_error check.
	 */
	public function __construct(\phpbb\cache\service $cache, $region = 'us', $cacheTtl = 3600)
	{
		$this->region = $region;
		$this->cache = $cache;
	}

	/**
	 * Intercept consume() to capture the method and params.
	 */
	public function consume($method, array $params): array
	{
		$this->last_method = $method;
		$this->last_params = $params;
		return array('response' => array(), 'response_headers' => array('http_code' => 200));
	}
}

class battlenet_guild_test extends TestCase
{
	/** @var battlenet_guild_test_wrapper */
	protected $guild;

	protected function setUp(): void
	{
		parent::setUp();
		$cache = $this->createMock(\phpbb\cache\service::class);
		$this->guild = new battlenet_guild_test_wrapper($cache, 'eu');
	}

	public function test_activity_endpoint_url(): void
	{
		$this->guild->getActivity('area-52', 'Test-Guild');

		$this->assertSame('area-52/Test-Guild/activity', $this->guild->last_method);
	}

	public function test_activity_empty_name_triggers_error(): void
	{
		$GLOBALS['user'] = new \stdClass();
		$GLOBALS['user']->lang = array(
			'WOWAPI_NO_GUILD' => 'No guild specified',
		);

		$this->expectException(\avathar\bbguildwow\api\battlenet_api_exception::class);
		$this->expectExceptionMessage('No guild specified');
		$this->guild->getActivity('area-52', '');
	}

	public function test_activity_empty_realm_triggers_error(): void
	{
		$GLOBALS['user'] = new \stdClass();
		$GLOBALS['user']->lang = array(
			'WOWAPI_NO_REALMS' => 'No realm specified',
		);

		$this->expectException(\avathar\bbguildwow\api\battlenet_api_exception::class);
		$this->expectExceptionMessage('No realm specified');
		$this->guild->getActivity('', 'Test-Guild');
	}

	protected function tearDown(): void
	{
		unset($GLOBALS['user']);
		parent::tearDown();
	}
}
