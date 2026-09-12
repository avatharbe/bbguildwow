<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

/**
 * Exercises wow_api::sync_guild_members() end to end against a real DB.
 * No Battle.net HTTP call is involved — member_data is already-fetched
 * input to this method, not something it fetches itself.
 *
 * @group integration
 */
class avathar_bbguildwow_roster_sync_test extends phpbb_functional_test_case
{
	static protected function setup_extensions()
	{
		return array('avathar/bbguild', 'avathar/bbguildwow');
	}

	protected function setUp(): void
	{
		parent::setUp();

		// update_wow_roster() calls global $user->add_lang_ext(...) and reads
		// $user->lang['ADMIN_ADD_PLAYER_SUCCESS'] on every newly-inserted
		// player (it's called in-process, not through the board under test's
		// own HTTP-driven request where a real $user is bootstrapped). This
		// test's in-process $GLOBALS['user'] is unset by default. A full real
		// \phpbb\user would need language-file loading wired up for no
		// benefit here — none of this task's assertions touch the comment
		// text — so a minimal stub that satisfies the two calls is enough,
		// same spirit as the $GLOBALS['user'] = new \stdClass() stub already
		// used in tests/api/battlenet_character_test.php (that one doesn't
		// need add_lang_ext(), this one does, hence the anonymous class).
		$GLOBALS['user'] = new class {
			public $lang = array('ADMIN_ADD_PLAYER_SUCCESS' => 'Player %1$s added on %2$s');
			public function add_lang_ext($ext_name, $lang_file) { }
		};
	}

	protected function tearDown(): void
	{
		unset($GLOBALS['user']);
		parent::tearDown();
	}

	private function get_table_prefix(): string
	{
		return self::$config['table_prefix'];
	}

	/**
	 * No real DI container is reachable from inside a phpbb_functional_test_case
	 * subclass (Goutte drives the board under test over real HTTP, in a
	 * separate PHP process — there is no in-process container to fetch).
	 * Every dependency wow_api's constructor needs is directly constructible.
	 */
	private function make_api(): \avathar\bbguildwow\game\wow_api
	{
		return new \avathar\bbguildwow\game\wow_api(
			$this->make_inert_cache(),
			$this->get_db(),
			$this->get_table_prefix() . 'bb_guild_wow',
			$this->get_table_prefix() . 'bb_players',
			$this->get_table_prefix() . 'bb_ranks',
			new \phpbb\filesystem\filesystem()
		);
	}

	/**
	 * sync_guild_members() never reaches any code path that touches the
	 * cache in this test (no create_battlenet() call happens here at all —
	 * that seam is only exercised by the sibling sync_portraits/specs/equipment
	 * tests). Any \phpbb\cache\service-shaped object is inert; build a bare
	 * mock rather than wiring a real cache backend for no purpose.
	 */
	private function make_inert_cache(): \phpbb\cache\service
	{
		return $this->getMockBuilder(\phpbb\cache\service::class)
			->disableOriginalConstructor()
			->getMock();
	}

	private function member(string $name, string $realm_slug, int $level, int $class_id, int $race_id, int $rank): array
	{
		return array(
			'character' => array(
				'name'           => $name,
				'realm'          => array('slug' => $realm_slug),
				'level'          => $level,
				'playable_class' => array('id' => $class_id),
				'playable_race'  => array('id' => $race_id),
			),
			'rank' => $rank,
		);
	}

	/**
	 * Scoped by guild_id, not just name+realm: bb_players has no cross-guild
	 * uniqueness guarantee, and this file's own 'Sajaki'-under-424242 row
	 * sits alongside other integration test files that also seed a
	 * 'Sajaki'/'area-52' row, each under their own distinct guild_id.
	 * Without this filter, whichever row sql_fetchrow() happens to return
	 * first is run-order-dependent, not deterministic.
	 */
	private function fetch_player(string $name, string $realm, int $guild_id): ?array
	{
		$db = $this->get_db();
		$sql = 'SELECT * FROM ' . $this->get_table_prefix() . 'bb_players
			WHERE player_name = \'' . $db->sql_escape($name) . '\'
				AND player_realm = \'' . $db->sql_escape($realm) . '\'
				AND player_guild_id = ' . (int) $guild_id;
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		return $row ?: null;
	}

	public function test_new_member_is_inserted_with_mapped_class_race_faction(): void
	{
		$guild_id = 424242;
		$api = $this->make_api();

		$api->sync_guild_members(
			array($this->member('Sajaki', 'area-52', 80, 1, 2, 0)),
			$guild_id, 'us', 10
		);

		$player = $this->fetch_player('Sajaki', 'area-52', $guild_id);
		$this->assertNotNull($player);
		$this->assertSame(1, (int) $player['player_status']);
		$this->assertSame(80, (int) $player['player_level']);
		$this->assertSame(1, (int) $player['player_class_id']);
		$this->assertSame(2, (int) $player['player_race_id']);
		$this->assertSame(0, (int) $player['player_rank_id']);
	}

	public function test_reappearing_same_name_and_realm_updates_in_place(): void
	{
		$guild_id = 424243;
		$api = $this->make_api();

		$api->sync_guild_members(array($this->member('Thrall', 'area-52', 70, 1, 2, 3)), $guild_id, 'us', 10);
		$first = $this->fetch_player('Thrall', 'area-52', $guild_id);

		// Same identity key (name-realm), different level/class/rank: an
		// in-place UPDATE, not a new row.
		$api->sync_guild_members(array($this->member('Thrall', 'area-52', 80, 4, 2, 1)), $guild_id, 'us', 10);
		$second = $this->fetch_player('Thrall', 'area-52', $guild_id);

		$this->assertSame($first['player_id'], $second['player_id']);
		$this->assertSame(80, (int) $second['player_level']);
		$this->assertSame(4, (int) $second['player_class_id']);
		$this->assertSame(1, (int) $second['player_rank_id']);
		$this->assertSame(1, (int) $second['player_status']);
	}

	public function test_character_rename_soft_deletes_old_name_and_inserts_new_name(): void
	{
		// update_wow_roster() identifies players by "name-realm_slug", not
		// by any Battle.net character id — see plan Scope note #5. A rename
		// is therefore NOT an in-place update: the old key disappears from
		// the roster response (soft-deleted) and the new key is absent from
		// the DB (inserted as a new row). This test locks down that actual
		// behavior, not the "renamed → updated in place" premise originally
		// suggested in tests/integration-tests.md, which doesn't match the code.
		$guild_id = 424244;
		$api = $this->make_api();

		$api->sync_guild_members(array($this->member('Oldname', 'area-52', 80, 1, 2, 0)), $guild_id, 'us', 10);
		$api->sync_guild_members(array($this->member('Newname', 'area-52', 80, 1, 2, 0)), $guild_id, 'us', 10);

		$old = $this->fetch_player('Oldname', 'area-52', $guild_id);
		$new = $this->fetch_player('Newname', 'area-52', $guild_id);

		$this->assertNotNull($old);
		$this->assertSame(0, (int) $old['player_status'], 'old name is soft-deleted, not removed');
		$this->assertNotNull($new);
		$this->assertSame(1, (int) $new['player_status']);
		$this->assertNotSame($old['player_id'], $new['player_id']);
	}

	public function test_departed_character_is_soft_deleted_not_removed(): void
	{
		$guild_id = 424245;
		$api = $this->make_api();

		$api->sync_guild_members(array(
			$this->member('Staying', 'area-52', 80, 1, 2, 0),
			$this->member('Leaving', 'area-52', 80, 3, 1, 0),
		), $guild_id, 'us', 10);

		// Second sync: 'Leaving' no longer in the roster response.
		$api->sync_guild_members(array(
			$this->member('Staying', 'area-52', 80, 1, 2, 0),
		), $guild_id, 'us', 10);

		$leaving = $this->fetch_player('Leaving', 'area-52', $guild_id);
		$staying = $this->fetch_player('Staying', 'area-52', $guild_id);

		$this->assertNotNull($leaving, 'row must survive — bbguild core DKP/raid history references it');
		$this->assertSame(0, (int) $leaving['player_status']);
		$this->assertSame(1, (int) $staying['player_status']);
	}
}
