<?php
/**
 * bbGuild WoW Extension — sync route authorization
 *
 * @package   bbguildwow v2.0
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

/**
 * Regression guard for the missing-permission-check bug fixed in
 * controller/portrait_controller.php and
 * controller/achievement_sync_controller.php: all six sync routes were
 * previously reachable by anyone with the URL, letting an anonymous or
 * non-admin visitor trigger guild-roster overwrites, achievement-category
 * rebuilds, and Battle.net API calls. Every route now requires the
 * a_bbguild ACP permission, checked before any guild lookup or API work.
 *
 * The routes take a guild_id but the permission check happens first, so
 * a non-existent guild_id is sufficient here — no guild/game fixture is
 * required to exercise the authorization gate itself.
 *
 * @group functional
 */
class avathar_bbguildwow_sync_routes_authz_test extends phpbb_functional_test_case
{
	/** @var bool */
	private static $extensions_enabled = false;

	protected function setUp(): void
	{
		parent::setUp();

		// Enabling bbguild core + bbguildwow runs their full migration
		// chains; do it once for the whole class rather than per test.
		if (!self::$extensions_enabled)
		{
			$extension_manager = $this->get_extension_manager();
			$extension_manager->enable('avathar/bbguild');
			$extension_manager->enable('avathar/bbguildwow');
			self::$extensions_enabled = true;
		}

		$this->purge_cache();
	}

	/**
	 * @return string[] app.php-relative paths for the six sync routes,
	 *                   against an arbitrary non-existent guild_id.
	 */
	private function sync_route_paths(): array
	{
		return array(
			'app.php/bbguildwow/sync-roster/1',
			'app.php/bbguildwow/sync-specs/1',
			'app.php/bbguildwow/sync-portraits/1',
			'app.php/bbguildwow/sync-equipment/1',
			'app.php/bbguildwow/sync-categories/1',
			'app.php/bbguildwow/sync-achievements/1',
		);
	}

	public function test_anonymous_requests_are_forbidden()
	{
		foreach ($this->sync_route_paths() as $path)
		{
			self::request('GET', $path, array(), false);
			self::assert_response_status_code(403);
		}
	}

	public function test_non_admin_requests_are_forbidden()
	{
		$this->create_user('bbguildwow_syncauthz');
		$this->login('bbguildwow_syncauthz');

		foreach ($this->sync_route_paths() as $path)
		{
			self::request('GET', $path, array(), false);
			self::assert_response_status_code(403);
		}

		$this->logout();
	}

	public function test_admin_requests_pass_the_permission_gate()
	{
		$this->login('admin');

		foreach ($this->sync_route_paths() as $path)
		{
			self::request('GET', $path, array(), false);
			$status = self::$client->getResponse()->getStatus();
			$this->assertNotEquals(403, $status, "Admin request to $path was rejected by the permission gate.");
		}

		$this->logout();
	}
}
