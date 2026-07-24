<?php
/**
 * bbGuild WoW Extension — migration idempotency smoke test
 *
 * @package   bbguildwow v2.0
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

/**
 * Disables bbguildwow (data preserved) and re-enables it, then asserts
 * seeded rows were not duplicated. Disable does not revert schema/data,
 * so re-enabling re-runs every migration's effectively_installed() check
 * against data that's already there — this is the only way to exercise
 * that path without a second fresh install. Catches migrations that
 * mistakenly re-seed or re-create on a second run.
 *
 * @group smoke
 */
class avathar_bbguildwow_smoke_migration_idempotency_test extends phpbb_functional_test_case
{
	static protected function setup_extensions()
	{
		return array('avathar/bbguild', 'avathar/bbguildwow');
	}

	private function count_rows(string $table, string $where): int
	{
		$db = $this->get_db();
		$sql = 'SELECT COUNT(*) AS cnt FROM ' . $table . ' WHERE ' . $where;
		$result = $db->sql_query($sql);
		$count = (int) $db->sql_fetchfield('cnt');
		$db->sql_freeresult($result);

		return $count;
	}

	public function test_reenable_does_not_duplicate_seeded_data()
	{
		$before_games = $this->count_rows($this->get_table_prefix() . 'bb_games', "game_id = 'wow'");
		$before_specs = $this->count_rows($this->get_table_prefix() . 'bb_specializations', "game_id = 'wow'");
		$before_migrations = $this->count_rows($this->get_table_prefix() . 'migrations', "migration_name LIKE '%bbguildwow%'");

		$this->disable_ext('avathar/bbguildwow');
		$this->install_ext('avathar/bbguildwow');

		$after_games = $this->count_rows($this->get_table_prefix() . 'bb_games', "game_id = 'wow'");
		$after_specs = $this->count_rows($this->get_table_prefix() . 'bb_specializations', "game_id = 'wow'");
		$after_migrations = $this->count_rows($this->get_table_prefix() . 'migrations', "migration_name LIKE '%bbguildwow%'");

		$this->assertSame(1, $before_games, 'expected exactly one wow row in bb_games before re-enable');
		$this->assertSame($before_games, $after_games, 'bb_games wow row was duplicated on re-enable');
		$this->assertSame($before_specs, $after_specs, 'bb_specializations wow rows were duplicated on re-enable');
		$this->assertSame($before_migrations, $after_migrations, 'bbguildwow migration rows changed on re-enable');
	}

	private function get_table_prefix(): string
	{
		return self::$config['table_prefix'];
	}
}
