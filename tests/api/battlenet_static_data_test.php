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
 * Mirrors tests/api/battlenet_character_test.php's wrapper pattern.
 */
class battlenet_static_data_test_wrapper extends \avathar\bbguildwow\api\battlenet_static_data
{
	/** @var string Last method passed to consume() */
	public $last_method = '';

	/** @var array Last params passed to consume() */
	public $last_params = array();

	public function __construct(\phpbb\cache\service $cache, $region = 'us', $cacheTtl = 3600)
	{
		$this->region = $region;
		$this->cache = $cache;
	}

	public function consume($method, array $params): array
	{
		$this->last_method = $method;
		$this->last_params = $params;
		return array('response' => array(), 'response_headers' => array('http_code' => 200));
	}
}

class battlenet_static_data_test extends TestCase
{
	/** @var battlenet_static_data_test_wrapper */
	protected $static_data;

	protected function setUp(): void
	{
		parent::setUp();
		$cache = $this->createMock(\phpbb\cache\service::class);
		$this->static_data = new battlenet_static_data_test_wrapper($cache, 'eu');
	}

	public function test_get_item_media_endpoint_url(): void
	{
		$this->static_data->getItemMedia(50468);
		$this->assertSame('media/item/50468', $this->static_data->last_method);
	}
}
