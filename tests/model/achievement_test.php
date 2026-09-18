<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Unit tests for achievement::set_player_achievements() (bbguildwow#44's
 * sync side) and the ensure_achievement_stubs() helper it shares with
 * setAchievements() (guild-level sync).
 */

namespace avathar\bbguildwow\tests\model;

use avathar\bbguildwow\model\achievement;
use PHPUnit\Framework\TestCase;

class achievement_test extends TestCase
{
	private function make_achievement($db): achievement
	{
		$cache = $this->getMockBuilder(\phpbb\cache\service::class)
			->disableOriginalConstructor()->getMock();
		$util = $this->getMockBuilder(\avathar\bbguild\model\admin\util::class)
			->disableOriginalConstructor()->getMock();

		$model = new achievement(
			$db, $cache, $util,
			'bb_achievement_track', 'bb_achievement', 'bb_achievement_rewards',
			'bb_criteria_track', 'bb_achievement_criteria', 'bb_relations', 'bb_guild_wow',
			'bb_achievement_category'
		);
		$model->setGameId('wow');

		return $model;
	}

	public function test_set_player_achievements_deletes_old_rows_and_inserts_new_ones(): void
	{
		$deleted_sql = '';
		$multi_insert_calls = array();

		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$db->method('sql_query')->willReturnCallback(function ($sql) use (&$deleted_sql) {
			if (str_starts_with($sql, 'DELETE'))
			{
				$deleted_sql = $sql;
			}
			return true;
		});
		$db->method('sql_escape')->willReturnArgument(0);
		// ensure_achievement_stubs()'s catalog check: pretend achievement id
		// 6 already exists, so only id 7 gets a stub row.
		$db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(array('id' => 6), false);
		$db->method('sql_multi_insert')->willReturnCallback(function ($table, $rows) use (&$multi_insert_calls) {
			$multi_insert_calls[] = array('table' => $table, 'rows' => $rows);
		});

		$model = $this->make_achievement($db);

		$result = $model->set_player_achievements(42, array('achievements' => array(
			array('achievement' => array('id' => 6, 'name' => 'Level 10'), 'completed_timestamp' => 1000),
			array('achievement' => array('id' => 7, 'name' => 'Level 20'), 'completed_timestamp' => 0), // still in progress
		)));

		$this->assertSame(array('success' => true, 'count' => 2), $result);
		$this->assertStringContainsString('DELETE FROM bb_achievement_track', $deleted_sql);
		$this->assertStringContainsString('WHERE player_id = 42', $deleted_sql);

		$this->assertCount(2, $multi_insert_calls); // track rows, then catalog stubs
		$this->assertSame('bb_achievement_track', $multi_insert_calls[0]['table']);
		$this->assertSame(
			array(
				array('guild_id' => 0, 'player_id' => 42, 'achievement_id' => 6, 'achievements_completed' => 1000),
				array('guild_id' => 0, 'player_id' => 42, 'achievement_id' => 7, 'achievements_completed' => 0),
			),
			$multi_insert_calls[0]['rows']
		);

		$this->assertSame('bb_achievement', $multi_insert_calls[1]['table']);
		$this->assertCount(1, $multi_insert_calls[1]['rows']); // only id 7 was missing from the catalog
		$this->assertSame(7, $multi_insert_calls[1]['rows'][0]['id']);
		$this->assertSame('Level 20', $multi_insert_calls[1]['rows'][0]['title']);
		$this->assertSame('wow', $multi_insert_calls[1]['rows'][0]['game_id']);
	}

	public function test_set_player_achievements_empty_response_is_a_no_op(): void
	{
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$db->expects($this->never())->method('sql_query');
		$db->expects($this->never())->method('sql_multi_insert');

		$model = $this->make_achievement($db);
		$result = $model->set_player_achievements(42, array('achievements' => array()));

		$this->assertSame(array('success' => false, 'count' => 0), $result);
	}

	public function test_set_player_achievements_skips_entries_with_no_achievement_id(): void
	{
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$db->method('sql_query')->willReturn(true);
		$db->method('sql_escape')->willReturnArgument(0);
		$db->method('sql_fetchrow')->willReturn(false);

		$inserted_rows = null;
		$db->method('sql_multi_insert')->willReturnCallback(function ($table, $rows) use (&$inserted_rows) {
			if ($table === 'bb_achievement_track')
			{
				$inserted_rows = $rows;
			}
		});

		$model = $this->make_achievement($db);
		$result = $model->set_player_achievements(42, array('achievements' => array(
			array('completed_timestamp' => 1000), // no 'achievement' key at all
		)));

		$this->assertSame(array('success' => true, 'count' => 0), $result);
		$this->assertNull($inserted_rows); // nothing valid to insert
	}
}
