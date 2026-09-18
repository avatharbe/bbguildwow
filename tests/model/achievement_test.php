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

	/**
	 * Routes each of get_player_achievement_tree()'s three queries
	 * (category tree, per-category totals, completed rows) to its own
	 * queued dataset by matching a distinctive SQL substring, rather
	 * than assuming a fixed call order -- sql_fetchrow() then just
	 * shifts off whichever dataset its own sql_query() call was routed
	 * to (result "handles" here are just array keys, not real resources).
	 */
	private function make_tree_db(array $categories, array $totals, array $completed): \PHPUnit\Framework\MockObject\MockObject
	{
		$data = array();
		$next_id = 0;

		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$db->method('sql_escape')->willReturnArgument(0);
		$db->method('sql_query')->willReturnCallback(function ($sql) use (&$data, &$next_id, $categories, $totals, $completed) {
			$id = $next_id++;
			if (str_contains($sql, 'is_guild_category = 0 ORDER BY display_order'))
			{
				$data[$id] = $categories;
			}
			else if (str_contains($sql, 'GROUP BY a.category_id'))
			{
				$data[$id] = $totals;
			}
			else if (str_contains($sql, 'ac.achievements_completed > 0'))
			{
				$data[$id] = $completed;
			}
			else
			{
				$data[$id] = array();
			}
			return $id;
		});
		$db->method('sql_fetchrow')->willReturnCallback(function ($result) use (&$data) {
			return empty($data[$result]) ? false : array_shift($data[$result]);
		});
		$db->method('sql_freeresult')->willReturn(true);

		return $db;
	}

	public function test_get_player_achievement_tree_rolls_up_children_and_surfaces_uncategorized(): void
	{
		$categories = array(
			array('id' => 1, 'name' => 'Quests', 'parent_id' => 0, 'display_order' => 0),
			array('id' => 11, 'name' => 'Outland', 'parent_id' => 1, 'display_order' => 0),
			array('id' => 2, 'name' => 'Exploration', 'parent_id' => 0, 'display_order' => 1),
		);
		$totals = array(
			array('category_id' => 1, 'total_count' => 5, 'completed_count' => 1, 'total_points' => 50, 'earned_points' => 10),
			array('category_id' => 11, 'total_count' => 3, 'completed_count' => 1, 'total_points' => 30, 'earned_points' => 5),
			array('category_id' => 2, 'total_count' => 5, 'completed_count' => 1, 'total_points' => 50, 'earned_points' => 10),
		);
		$completed = array(
			array('title' => 'Quest A', 'description' => '', 'points' => 10, 'icon' => '', 'category_id' => 1, 'achievements_completed' => 3000),
			array('title' => 'Outland Quest B', 'description' => '', 'points' => 5, 'icon' => '', 'category_id' => 11, 'achievements_completed' => 2000),
			array('title' => 'Explore C', 'description' => '', 'points' => 10, 'icon' => '', 'category_id' => 2, 'achievements_completed' => 1000),
			array('title' => 'Mystery D', 'description' => '', 'points' => 5, 'icon' => '', 'category_id' => 0, 'achievements_completed' => 500),
		);

		$model = $this->make_achievement($this->make_tree_db($categories, $totals, $completed));
		$tree = $model->get_player_achievement_tree(42);

		$this->assertCount(3, $tree); // Quests, Exploration, + synthetic Uncategorized

		$quests = $tree[0];
		$this->assertSame(1, $quests['id']);
		// Own (5/1/50/10) rolled up with Outland child's (3/1/30/5).
		$this->assertSame(8, $quests['total_count']);
		$this->assertSame(2, $quests['completed_count']);
		$this->assertSame(80, $quests['total_points']);
		$this->assertSame(15, $quests['earned_points']);
		$this->assertSame(25, $quests['percent']); // 2/8
		$this->assertCount(1, $quests['achievements']);
		$this->assertSame('Quest A', $quests['achievements'][0]['title']);

		$this->assertCount(1, $quests['children']);
		$outland = $quests['children'][0];
		$this->assertSame('Outland', $outland['name']);
		$this->assertSame(3, $outland['total_count']);
		$this->assertSame('Outland Quest B', $outland['achievements'][0]['title']);
		$this->assertSame(array(), $outland['children']);

		$exploration = $tree[1];
		$this->assertSame('Exploration', $exploration['name']);
		$this->assertSame(array(), $exploration['children']);

		$uncategorized = $tree[2];
		$this->assertSame(0, $uncategorized['id']);
		$this->assertSame('Uncategorized', $uncategorized['name']); // LANG_FALLBACK, no language set
		$this->assertSame(1, $uncategorized['total_count']);
		$this->assertSame('Mystery D', $uncategorized['achievements'][0]['title']);
	}

	public function test_get_player_achievement_tree_drops_categories_with_no_achievements(): void
	{
		$categories = array(
			array('id' => 9, 'name' => 'Empty Category', 'parent_id' => 0, 'display_order' => 0),
		);

		$model = $this->make_achievement($this->make_tree_db($categories, array(), array()));
		$tree = $model->get_player_achievement_tree(42);

		$this->assertSame(array(), $tree);
	}
}
