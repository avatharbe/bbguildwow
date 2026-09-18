<?php
/**
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Unit test for the achievements player-detail tab (bbguildwow#44 /
 * bbguild#365), including its pagination (added as a follow-up after
 * manually checking the tab on the local board only ever showed the
 * first 15 tracked rows regardless of a player's real total). Uses a
 * REAL `achievement` model instance (mocked db/cache/util only) rather
 * than mocking the model itself, so this exercises the actual
 * get_tracked_achievements() query-building and row-mapping the tab
 * depends on, not just "did the tab call the model".
 */

namespace avathar\bbguildwow\tests\portal\player_detail_tabs;

use avathar\bbguildwow\model\achievement;
use avathar\bbguildwow\portal\player_detail_tabs\achievements_tab;
use PHPUnit\Framework\TestCase;

class achievements_tab_test extends TestCase
{
	/**
	 * What: a \phpbb\template\template mock wired to record every
	 * assign_vars()/assign_block_vars() call onto a plain recorder object.
	 * Why: unlike the fake_module_template-style plain fakes used
	 * elsewhere this cycle, achievements_tab's constructor type-hints the
	 * real (concrete) template class, so a duck-typed class of our own
	 * wouldn't satisfy it — createMock() against the real class does,
	 * with the recording behavior wired in via willReturnCallback. The
	 * recorder is a plain object (not a reference) since PHP objects are
	 * passed by handle: the closures below can mutate its properties and
	 * the caller sees the same mutations without needing `use (&...)`.
	 */
	private function make_recording_template(): array
	{
		$recorder = (object) ['vars' => [], 'blocks' => []];
		$template = $this->createMock(\phpbb\template\template::class);
		$template->method('assign_vars')->willReturnCallback(function ($a) use ($recorder) {
			$recorder->vars = array_merge($recorder->vars, $a);
		});
		$template->method('assign_block_vars')->willReturnCallback(function ($block, $a) use ($recorder) {
			$recorder->blocks[$block][] = $a;
		});

		return [$template, $recorder];
	}

	// What: builds a real `achievement` model with mocked db/cache/util.
	// Why: get_tracked_achievements() is real production logic (query
	// building, row mapping) worth exercising directly, not a boundary to
	// mock away — only its actual DB/cache/sort-helper dependencies need
	// doubling.
	private function make_achievement_model($db): achievement
	{
		$cache = $this->getMockBuilder(\phpbb\cache\service::class)
			->disableOriginalConstructor()->getMock();
		// util::switch_order() (called by get_tracked_achievements() to
		// build its ORDER BY) is declared `final`, so it can't be stubbed
		// via a mock — construct a real util instead, backed by a request
		// mock that just returns whatever default switch_order() asks
		// for. The exact resulting sort clause doesn't matter here: this
		// tab does its own newest-first sort on the returned rows
		// regardless of how the DB query itself was ordered.
		$request = $this->createMock(\phpbb\request\request::class);
		$request->method('variable')->willReturnCallback(fn($name, $default) => $default);
		$util = new \avathar\bbguild\model\admin\util($request);

		return new achievement(
			$db, $cache, $util,
			'bb_achievement_track', 'bb_achievement', 'bb_achievement_rewards',
			'bb_criteria_track', 'bb_achievement_criteria', 'bb_relations', 'bb_guild_wow',
			'bb_achievement_category'
		);
	}

	/**
	 * What: a fully-wired achievements_tab, plus the recorder its
	 * template writes to.
	 * Why: render()'s own extra dependencies (db for the guild_id
	 * lookup, request for the 'start' pagination param, pagination/
	 * helper for building the pager) are all pagination-follow-up
	 * additions — centralizing their setup here keeps each test focused
	 * on what it's actually asserting.
	 *
	 * @param \PHPUnit\Framework\MockObject\MockObject $db Same double
	 *     used by both the achievement model and the tab's own guild_id
	 *     lookup query, matching how they'd share one real connection.
	 * @param int $start Value the tab's own 'start' request param should
	 *     resolve to (independent of util's internal switch_order()
	 *     request, which always resolves to its own default).
	 */
	private function build_tab($db, int $start = 0): array
	{
		$model = $this->make_achievement_model($db);
		[$template, $recorder] = $this->make_recording_template();

		$request = $this->createMock(\phpbb\request\request::class);
		$request->method('variable')->with('start', 0)->willReturn($start);

		$pagination = $this->getMockBuilder(\phpbb\pagination::class)
			->disableOriginalConstructor()->getMock();
		$helper = $this->getMockBuilder(\phpbb\controller\helper::class)
			->disableOriginalConstructor()->getMock();
		$helper->method('route')->willReturn('/guild/5/player/42/achievements');

		$tab = new achievements_tab($model, $template, $db, $request, $pagination, $helper, 'bb_players');

		return [$tab, $recorder, $pagination];
	}

	public function test_render_lists_only_completed_achievements_sorted_newest_first(): void
	{
		// What: two completed rows, returned by the (mocked) DB in the
		// same newest-first order a real "ORDER BY achievements_completed
		// desc" query would give.
		// Why: the tab no longer re-sorts client-side (see
		// render()'s '4.1' default_order — sorting is now the SQL query's
		// job, verified below via the captured ORDER BY clause), so this
		// fixture simulates what the DB itself would already hand back,
		// and the SQL-capture assertion is what actually proves the sort
		// request reached the query rather than the fixture just
		// happening to be listed in the right order.
		$rows = [
			['achievement_id' => 2, 'game_id' => 'wow', 'title' => 'Newer', 'points' => 25,
				'description' => 'Newer desc', 'icon' => 'icon2.jpg', 'factionid' => 0, 'reward' => '',
				'achievements_completed' => 2000, 'guild_id' => 0, 'player_id' => 42],
			['achievement_id' => 1, 'game_id' => 'wow', 'title' => 'Older', 'points' => 10,
				'description' => 'Older desc', 'icon' => 'icon1.jpg', 'factionid' => 0, 'reward' => '',
				'achievements_completed' => 1000, 'guild_id' => 0, 'player_id' => 42],
		];

		$captured_sql = array();
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$db->method('sql_query')->willReturn('RESULT');
		$db->method('sql_query_limit')->willReturnCallback(function ($sql) use (&$captured_sql) {
			$captured_sql[] = $sql;
			return 'LIST_RESULT';
		});
		$db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(...array_merge($rows, [false]));
		$db->method('sql_escape')->willReturnArgument(0);
		// Three sequential single-value fetches: get_tracked_achievements()'s
		// COUNT (2 completed), get_player_completed_points()'s SUM (35),
		// then the tab's own guild_id lookup (5).
		$db->method('sql_fetchfield')->willReturnOnConsecutiveCalls(2, 35, 5);

		[$tab, $recorder, $pagination] = $this->build_tab($db);
		$pagination->expects($this->once())->method('generate_template_pagination')
			->with('/guild/5/player/42/achievements', 'pagination', 'start', 2, achievements_tab::PER_PAGE, 0);

		$path = $tab->render(42);

		$this->assertSame('@avathar_bbguildwow/portal/achievements_tab.html', $path);
		$this->assertCount(1, $captured_sql);
		$this->assertStringContainsString('ORDER BY ac.achievements_completed desc', $captured_sql[0]);
		$this->assertSame(2, $recorder->vars['WOW_ACHIEVEMENT_COUNT']);
		$this->assertSame(35, $recorder->vars['WOW_ACHIEVEMENT_POINTS']);
		$this->assertCount(2, $recorder->blocks['wow_achievement_row']);
		$this->assertSame('Newer', $recorder->blocks['wow_achievement_row'][0]['TITLE']); // newest first
		$this->assertSame('Older', $recorder->blocks['wow_achievement_row'][1]['TITLE']);
	}

	// What: no tracked achievement rows at all for this player.
	// Why: confirms the tab degrades cleanly to a "zero" state instead of
	// erroring on an empty result set (no rows to filter/sort/sum).
	public function test_render_reports_zero_when_no_achievements_completed(): void
	{
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$db->method('sql_query')->willReturn('RESULT');
		$db->method('sql_query_limit')->willReturn('LIST_RESULT');
		$db->method('sql_fetchrow')->willReturn(false); // no tracked rows at all
		$db->method('sql_escape')->willReturnArgument(0);
		$db->method('sql_fetchfield')->willReturnOnConsecutiveCalls(0, 0, 5);

		[$tab, $recorder] = $this->build_tab($db);

		$tab->render(99);

		$this->assertSame(0, $recorder->vars['WOW_ACHIEVEMENT_COUNT']);
		$this->assertSame(0, $recorder->vars['WOW_ACHIEVEMENT_POINTS']);
		$this->assertArrayNotHasKey('wow_achievement_row', $recorder->blocks);
	}

	// What: a second page of results (start=15).
	// Why: this is the actual gap found on the local board — the tab
	// used to always fetch start=0 regardless of the URL, so a
	// character with hundreds of achievements could never see past the
	// first 15. Confirms the 'start' request param actually reaches
	// get_tracked_achievements() and the pagination helper.
	public function test_render_respects_start_param_for_page_two(): void
	{
		$row = ['achievement_id' => 20, 'game_id' => 'wow', 'title' => 'Page Two Entry', 'points' => 5,
			'description' => '', 'icon' => '', 'factionid' => 0, 'reward' => '',
			'achievements_completed' => 500, 'guild_id' => 0, 'player_id' => 42];

		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$db->method('sql_query')->willReturn('RESULT');
		$db->method('sql_query_limit')->willReturn('LIST_RESULT');
		$db->method('sql_fetchrow')->willReturnOnConsecutiveCalls($row, false);
		$db->method('sql_escape')->willReturnArgument(0);
		$db->method('sql_fetchfield')->willReturnOnConsecutiveCalls(16, 100, 5);

		[$tab, $recorder, $pagination] = $this->build_tab($db, 15);
		$pagination->expects($this->once())->method('generate_template_pagination')
			->with($this->anything(), 'pagination', 'start', 16, achievements_tab::PER_PAGE, 15);

		$tab->render(42);

		$this->assertCount(1, $recorder->blocks['wow_achievement_row']);
		$this->assertSame('Page Two Entry', $recorder->blocks['wow_achievement_row'][0]['TITLE']);
	}

	// What: the is_available() gate this tab shares with talents/pvp/
	// raid-progression.
	// Why: confirms it hides itself for non-WoW characters, same as its
	// three siblings — worth its own assertion since a future edit could
	// easily drop the game_id check without any other test catching it.
	public function test_is_available_only_for_wow(): void
	{
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		[$tab] = $this->build_tab($db);

		$this->assertTrue($tab->is_available(1, 'wow'));
		$this->assertFalse($tab->is_available(1, 'gw2'));
	}
}
