<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguildwow\tests\api;

use PHPUnit\Framework\TestCase;

/**
 * Concrete subclass of the abstract battlenet_resource for testing.
 * Sets methods_allowed to ['*'] so consume() won't reject any method.
 */
class battlenet_resource_test_wrapper extends \avathar\bbguildwow\api\battlenet_resource
{
	protected $methods_allowed = array('*');
	protected $endpoint = 'test/endpoint';
	protected $cacheTtl = 3600;

	/**
	 * Override constructor to skip the trigger_error on empty methods_allowed.
	 */
	public function __construct(\phpbb\cache\service $cache, $region = 'us', $cacheTtl = 3600)
	{
		$this->region = $region;
		$this->cache = $cache;
		$this->cacheTtl = $cacheTtl;
	}

	/**
	 * Expose get_namespace() for testing.
	 */
	public function test_get_namespace(): string
	{
		return $this->get_namespace();
	}

	/**
	 * Expose fetch_oauth_token() for testing.
	 */
	public function test_fetch_oauth_token($force = false)
	{
		return $this->fetch_oauth_token($force);
	}

	/**
	 * Expose _getCachedResult() for testing.
	 */
	public function test_getCachedResult(string $key)
	{
		return $this->_getCachedResult($key);
	}
}

class battlenet_resource_test extends TestCase
{
	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\cache\service */
	protected $cache;

	protected function setUp(): void
	{
		parent::setUp();
		// phpbb\cache\service proxies get/put via __call, so we must use
		// addMethods() to register them before PHPUnit can stub them.
		$this->cache = $this->getMockBuilder(\phpbb\cache\service::class)
			->disableOriginalConstructor()
			->addMethods(array('get', 'put'))
			->getMock();
	}

	// ── get_namespace() ────────────────────────────────────

	public function namespace_data(): array
	{
		return array(
			'retail eu dynamic'       => array('retail',       'eu', 'dynamic', 'dynamic-eu'),
			'retail us profile'       => array('retail',       'us', 'profile', 'profile-us'),
			'retail kr static'        => array('retail',       'kr', 'static',  'static-kr'),
			'classic_era us dynamic'  => array('classic_era',  'us', 'dynamic', 'dynamic-classic1x-us'),
			'classic_prog eu dynamic' => array('classic_prog', 'eu', 'dynamic', 'dynamic-classic-eu'),
			'classic_ann kr profile'  => array('classic_ann',  'kr', 'profile', 'profile-classicann-kr'),
			'classic_era tw static'   => array('classic_era',  'tw', 'static',  'static-classic1x-tw'),
		);
	}

	/**
	 * @dataProvider namespace_data
	 */
	public function test_get_namespace(string $edition, string $region, string $ns_type, string $expected): void
	{
		$resource = new battlenet_resource_test_wrapper($this->cache, $region);
		$resource->edition = $edition;
		$resource->namespace_type = $ns_type;
		$this->assertSame($expected, $resource->test_get_namespace());
	}

	// ── _getCachedResult() ─────────────────────────────────

	public function test_cache_miss_returns_false(): void
	{
		$this->cache->method('get')->willReturn(false);
		$resource = new battlenet_resource_test_wrapper($this->cache);
		$this->assertFalse($resource->test_getCachedResult('nonexistent_key'));
	}

	public function test_cache_hit_returns_data(): void
	{
		$cached_data = array('response' => array('name' => 'Sajaki'), 'response_headers' => array());
		$this->cache->method('get')->willReturn($cached_data);
		$resource = new battlenet_resource_test_wrapper($this->cache);
		$this->assertSame($cached_data, $resource->test_getCachedResult('some_key'));
	}

	// ── fetch_oauth_token() ────────────────────────────────

	public function test_oauth_token_returns_cached_token(): void
	{
		$this->cache->method('get')
			->with('bbguild_wow_oauth_token_eu')
			->willReturn('cached_token_abc');

		$resource = new battlenet_resource_test_wrapper($this->cache, 'eu');
		$resource->apikey = 'test_key';
		$resource->privkey = 'test_secret';

		$this->assertSame('cached_token_abc', $resource->test_fetch_oauth_token());
	}

	public function test_oauth_token_unknown_region_returns_false(): void
	{
		$this->cache->method('get')->willReturn(false);

		$resource = new battlenet_resource_test_wrapper($this->cache, 'invalid_region');
		$resource->apikey = 'test_key';
		$resource->privkey = 'test_secret';

		$this->assertFalse($resource->test_fetch_oauth_token());
	}

	// ── EDITION_INFIXES constant ───────────────────────────

	public function test_edition_infixes_keys(): void
	{
		$infixes = \avathar\bbguildwow\api\battlenet_resource::EDITION_INFIXES;
		$this->assertArrayHasKey('retail', $infixes);
		$this->assertArrayHasKey('classic_era', $infixes);
		$this->assertArrayHasKey('classic_prog', $infixes);
		$this->assertArrayHasKey('classic_ann', $infixes);
		$this->assertSame('', $infixes['retail']);
	}
}
