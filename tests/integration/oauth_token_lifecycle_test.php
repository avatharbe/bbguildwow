<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguildwow\tests\integration;

use avathar\bbguildwow\api\battlenet_guild;

/**
 * Points battlenet_guild's api_url/token_url at the local mock server
 * instead of api.blizzard.com/oauth.battle.net.
 */
class mock_battlenet_guild extends battlenet_guild
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
 * Exercises battlenet_resource::fetch_oauth_token()/_authenticatedRequest()
 * for real, through battlenet_guild's public API, against the local mock
 * server — the only way to cover this path, since it's raw curl with
 * nothing to mock at the PHP level. See mock_battlenet_test_case.php.
 *
 * @group integration
 */
class oauth_token_lifecycle_test extends mock_battlenet_test_case
{
	private function guild_response_routes(array $guild_response): array
	{
		return array(
			'/token' => array(
				array('status' => 200, 'body' => array('access_token' => 'tok-abc', 'token_type' => 'Bearer', 'expires_in' => 3600)),
			),
			'/data/wow/guild/area-52/my-guild' => array(
				array('status' => 200, 'body' => $guild_response),
			),
		);
	}

	public function test_first_call_fetches_and_caches_token(): void
	{
		$this->configure_mock_routes($this->guild_response_routes(array('name' => 'My Guild', 'id' => 123)));

		$cache = $this->make_stateful_cache();
		$guild = new mock_battlenet_guild($cache, self::base_url());

		$result = $guild->getGuild('area-52', 'my-guild');

		$this->assertSame('My Guild', $result['response']['name']);
		$this->assertSame(1, $this->mock_call_count('/token'));
		$this->assertSame(1, $this->mock_call_count('/data/wow/guild/area-52/my-guild'));
	}

	public function test_second_call_within_ttl_reuses_cached_token(): void
	{
		$this->configure_mock_routes(array(
			'/token' => array(
				array('status' => 200, 'body' => array('access_token' => 'tok-abc', 'token_type' => 'Bearer', 'expires_in' => 3600)),
			),
			'/data/wow/guild/area-52/my-guild' => array(
				array('status' => 200, 'body' => array('name' => 'My Guild')),
			),
			'/data/wow/guild/area-52/my-guild/roster' => array(
				array('status' => 200, 'body' => array('members' => array())),
			),
		));

		$cache = $this->make_stateful_cache();
		$guild = new mock_battlenet_guild($cache, self::base_url());

		$guild->getGuild('area-52', 'my-guild');
		$guild->getRoster('area-52', 'my-guild');

		// Two different API calls sharing one cache: token fetched once.
		$this->assertSame(1, $this->mock_call_count('/token'));
		$this->assertSame(1, $this->mock_call_count('/data/wow/guild/area-52/my-guild'));
		$this->assertSame(1, $this->mock_call_count('/data/wow/guild/area-52/my-guild/roster'));
	}

	public function test_expired_token_is_refetched(): void
	{
		// Two different resource paths (not the same call repeated): consume()
		// caches successful responses per exact request URI, so calling
		// getGuild() twice would hit that cache on the second call and never
		// reach fetch_oauth_token() at all — using getRoster() for the second
		// call keeps this test actually exercising the token refresh path.
		$this->configure_mock_routes(array(
			'/token' => array(
				array('status' => 200, 'body' => array('access_token' => 'tok-abc', 'token_type' => 'Bearer', 'expires_in' => 3600)),
				array('status' => 200, 'body' => array('access_token' => 'tok-refreshed', 'token_type' => 'Bearer', 'expires_in' => 3600)),
			),
			'/data/wow/guild/area-52/my-guild' => array(
				array('status' => 200, 'body' => array('name' => 'My Guild')),
			),
			'/data/wow/guild/area-52/my-guild/roster' => array(
				array('status' => 200, 'body' => array('members' => array())),
			),
		));

		$cache = $this->make_stateful_cache();
		$guild = new mock_battlenet_guild($cache, self::base_url());

		$guild->getGuild('area-52', 'my-guild');
		$this->assertSame(1, $this->mock_call_count('/token'));

		// Simulate TTL expiry: the cache backend evicting the entry looks
		// identical to the SUT regardless of why the key is gone.
		$cache->destroy('bbguild_wow_oauth_token_us');

		$guild->getRoster('area-52', 'my-guild');
		$this->assertSame(2, $this->mock_call_count('/token'));
	}

	public function test_401_on_resource_call_triggers_refresh_and_retry(): void
	{
		$this->configure_mock_routes(array(
			'/token' => array(
				array('status' => 200, 'body' => array('access_token' => 'tok-abc', 'token_type' => 'Bearer', 'expires_in' => 3600)),
				array('status' => 200, 'body' => array('access_token' => 'tok-refreshed', 'token_type' => 'Bearer', 'expires_in' => 3600)),
			),
			'/data/wow/guild/area-52/my-guild' => array(
				array('status' => 401, 'body' => array('code' => 401, 'detail' => 'Unauthorized')),
				array('status' => 200, 'body' => array('name' => 'My Guild')),
			),
		));

		$cache = $this->make_stateful_cache();
		$guild = new mock_battlenet_guild($cache, self::base_url());

		$result = $guild->getGuild('area-52', 'my-guild');

		$this->assertSame('My Guild', $result['response']['name']);
		$this->assertSame(2, $this->mock_call_count('/token'));
		$this->assertSame(2, $this->mock_call_count('/data/wow/guild/area-52/my-guild'));
	}
}
