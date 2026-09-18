<?php
/**
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Unit test for the achievements player-detail tab (bbguildwow#44 /
 * bbguild#365). Rewritten from a flat paginated list to category-
 * bucketed sections (matching Blizzard's own armory achievements page)
 * after manual review on the local board showed the flat list had no
 * sense of what a character had actually accomplished, just a
 * chronological feed. Mocks the `achievement` model itself here (unlike
 * the earlier version, which exercised a real model instance) since the
 * tab's own bucketing/grouping logic is now the thing worth testing in
 * isolation from get_player_category_progress()'s/
 * get_player_completed_achievements_grouped()'s own query correctness.
 */

namespace avathar\bbguildwow\tests\portal\player_detail_tabs;

use avathar\bbguildwow\model\achievement;
use avathar\bbguildwow\portal\player_detail_tabs\achievements_tab;
use PHPUnit\Framework\TestCase;

class achievements_tab_test extends TestCase
{
	/**
	 * Same recording-template approach used elsewhere this cycle
	 * (e.g. talents_tab_test.php) — achievements_tab's constructor
	 * type-hints the real (concrete) template class, so a mock with
	 * recording callbacks wired in is simpler than a duck-typed fake.
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

	private function make_language(): \PHPUnit\Framework\MockObject\MockObject
	{
		$language = $this->getMockBuilder(\phpbb\language\language::class)
			->disableOriginalConstructor()->getMock();
		$language->method('lang')->willReturnArgument(0);

		return $language;
	}

	public function test_render_groups_achievements_under_their_category_cards(): void
	{
		$categories = [
			['id' => 1, 'name' => 'Quests', 'total_count' => 10, 'completed_count' => 2, 'total_points' => 100, 'earned_points' => 15],
			['id' => 2, 'name' => 'Exploration', 'total_count' => 5, 'completed_count' => 1, 'total_points' => 50, 'earned_points' => 10],
		];
		// Pre-sorted the way get_player_completed_achievements_grouped()
		// itself sorts: category order, then sub-category, then newest
		// completion first. "Quests" has two sub-categories here
		// (Outland, Northrend); "Exploration" has none (sub_name null).
		$rows = [
			['title' => 'Outland Quest A', 'description' => 'Do a thing.', 'points' => 10, 'icon' => 'icon_a',
				'achievements_completed' => 2000, 'top_id' => 1, 'top_name' => 'Quests', 'sub_name' => 'Outland'],
			['title' => 'Northrend Quest B', 'description' => 'Do another thing.', 'points' => 5, 'icon' => 'icon_b',
				'achievements_completed' => 1000, 'top_id' => 1, 'top_name' => 'Quests', 'sub_name' => 'Northrend'],
			['title' => 'Explore Zone C', 'description' => '', 'points' => 10, 'icon' => 'icon_c',
				'achievements_completed' => 1500, 'top_id' => 2, 'top_name' => 'Exploration', 'sub_name' => null],
		];

		$model = $this->getMockBuilder(achievement::class)->disableOriginalConstructor()->getMock();
		$model->expects($this->once())->method('setGameId')->with('wow');
		$model->method('get_player_category_progress')->with(42)->willReturn($categories);
		$model->method('get_player_completed_achievements_grouped')->with(42)->willReturn($rows);

		[$template, $recorder] = $this->make_recording_template();
		$tab = new achievements_tab($model, $template, $this->make_language());

		$path = $tab->render(42);

		$this->assertSame('@avathar_bbguildwow/portal/achievements_tab.html', $path);
		$this->assertSame(3, $recorder->vars['WOW_ACHIEVEMENT_COUNT']); // 2 + 1 across both categories
		$this->assertSame(25, $recorder->vars['WOW_ACHIEVEMENT_POINTS']); // 15 + 10

		// Two category cards, in the order get_player_category_progress() gave them.
		$this->assertCount(2, $recorder->blocks['achiev_category']);
		$this->assertSame('Quests', $recorder->blocks['achiev_category'][0]['NAME']);
		$this->assertSame(20, $recorder->blocks['achiev_category'][0]['PERCENT']); // 2/10
		$this->assertSame('Exploration', $recorder->blocks['achiev_category'][1]['NAME']);
		$this->assertSame(20, $recorder->blocks['achiev_category'][1]['PERCENT']); // 1/5

		// Two sections (one per top category), each carrying its own achievements.
		$this->assertCount(2, $recorder->blocks['achiev_section']);
		$this->assertSame('Quests', $recorder->blocks['achiev_section'][0]['NAME']);
		$this->assertSame('Exploration', $recorder->blocks['achiev_section'][1]['NAME']);

		$quests_rows = $recorder->blocks['achiev_section.achievement_row'] ?? null;
		// Nested block vars are recorded under the dotted key by our
		// recorder's plain assign_block_vars($block, $a) call signature —
		// the tab calls assign_block_vars('achiev_section.achievement_row', ...),
		// so that's the exact key to assert on.
		$this->assertNotNull($quests_rows);
		$this->assertCount(3, $quests_rows); // 2 Quests sub-rows + 1 Exploration row, in call order
		$this->assertSame('Outland Quest A', $quests_rows[0]['TITLE']);
		$this->assertSame('Outland', $quests_rows[0]['SUB_NAME']);
		$this->assertSame('Northrend Quest B', $quests_rows[1]['TITLE']);
		$this->assertSame('Northrend', $quests_rows[1]['SUB_NAME']);
		$this->assertSame('Explore Zone C', $quests_rows[2]['TITLE']);
		$this->assertNull($quests_rows[2]['SUB_NAME']);
	}

	// What: a category the catalog lists but with zero achievements
	// assigned to it (or its children) for this game.
	// Why: total_count=0 would divide-by-zero computing a percentage,
	// and there's nothing meaningful to show a ring or section for —
	// confirms it's skipped rather than crashing or rendering an empty
	// card.
	public function test_render_skips_categories_with_zero_total_achievements(): void
	{
		$categories = [
			['id' => 9, 'name' => 'Empty Category', 'total_count' => 0, 'completed_count' => 0, 'total_points' => 0, 'earned_points' => 0],
		];

		$model = $this->getMockBuilder(achievement::class)->disableOriginalConstructor()->getMock();
		$model->method('get_player_category_progress')->willReturn($categories);
		$model->method('get_player_completed_achievements_grouped')->willReturn([]);

		[$template, $recorder] = $this->make_recording_template();
		$tab = new achievements_tab($model, $template, $this->make_language());

		$tab->render(42);

		$this->assertArrayNotHasKey('achiev_category', $recorder->blocks);
		$this->assertArrayNotHasKey('achiev_section', $recorder->blocks);
		$this->assertSame(0, $recorder->vars['WOW_ACHIEVEMENT_COUNT']);
		$this->assertSame(0, $recorder->vars['WOW_ACHIEVEMENT_POINTS']);
	}

	// What: a completed achievement whose category_id doesn't resolve to
	// any row in the catalog (category_id=0 stub, or a dangling id).
	// Why: get_player_completed_achievements_grouped() returns null
	// category fields for these (LEFT JOIN) rather than dropping the
	// row — confirms the tab surfaces them under a fallback "Uncategorized"
	// section instead of silently losing a real completed achievement.
	public function test_render_surfaces_uncategorized_achievements(): void
	{
		$rows = [
			['title' => 'Mystery Achievement', 'description' => '', 'points' => 5, 'icon' => '',
				'achievements_completed' => 1000, 'top_id' => 0, 'top_name' => null, 'sub_name' => null],
		];

		$model = $this->getMockBuilder(achievement::class)->disableOriginalConstructor()->getMock();
		$model->method('get_player_category_progress')->willReturn([]);
		$model->method('get_player_completed_achievements_grouped')->willReturn($rows);

		[$template, $recorder] = $this->make_recording_template();
		$language = $this->make_language();
		$tab = new achievements_tab($model, $template, $language);

		$tab->render(42);

		$this->assertCount(1, $recorder->blocks['achiev_section']);
		$this->assertSame('WOW_ACHIEVEMENTS_UNCATEGORIZED', $recorder->blocks['achiev_section'][0]['NAME']);
		$this->assertCount(1, $recorder->blocks['achiev_section.achievement_row']);
		$this->assertSame('Mystery Achievement', $recorder->blocks['achiev_section.achievement_row'][0]['TITLE']);
	}

	public function test_is_available_only_for_wow(): void
	{
		$model = $this->getMockBuilder(achievement::class)->disableOriginalConstructor()->getMock();
		[$template] = $this->make_recording_template();
		$tab = new achievements_tab($model, $template, $this->make_language());

		$this->assertTrue($tab->is_available(1, 'wow'));
		$this->assertFalse($tab->is_available(1, 'gw2'));
	}
}
