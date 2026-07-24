<?php
/**
 * bbGuild WoW Extension — routes-don't-crash smoke test
 *
 * @package   bbguildwow v2.0
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

/**
 * Issues an unauthenticated GET to every route in config/routing.yml
 * against arbitrary non-existent IDs. Asserts the response is never a
 * 5xx — a 200/302/401/403/404 is fine, a fatal error is not. Authz
 * behavior is covered separately by sync_routes_authz_test.php; this
 * is just "doesn't crash".
 *
 * @group smoke
 */
class avathar_bbguildwow_smoke_routes_no_500_test extends phpbb_functional_test_case
{
	static protected function setup_extensions()
	{
		return array('avathar/bbguild', 'avathar/bbguildwow');
	}

	/**
	 * @return string[] app.php-relative paths for every route in routing.yml,
	 *                   against arbitrary non-existent IDs.
	 */
	private function route_paths(): array
	{
		return array(
			'app.php/bbguildwow/emblem/1',
			'app.php/bbguildwow/portrait/1',
			'app.php/bbguildwow/achievements/categories/1',
			'app.php/bbguildwow/achievements/list/1/1',
			'app.php/bbguildwow/achievements/detail/1/1',
			'app.php/bbguildwow/sync-roster/1',
			'app.php/bbguildwow/sync-specs/1',
			'app.php/bbguildwow/sync-portraits/1',
			'app.php/bbguildwow/sync-equipment/1',
			'app.php/bbguildwow/sync-categories/1',
			'app.php/bbguildwow/sync-achievements/1',
		);
	}

	public function test_routes_never_return_server_error()
	{
		foreach ($this->route_paths() as $path)
		{
			self::request('GET', $path, array(), false);
			$status = (int) self::$client->getResponse()->getStatus();
			$this->assertLessThan(500, $status, "Route '$path' returned $status");
		}
	}
}
