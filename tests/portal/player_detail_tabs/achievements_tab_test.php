<?php
/**
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Unit test for the achievements player-detail tab (bbguildwow#44 /
 * bbguild#365). The tab itself is now a thin reshape of whatever
 * get_player_achievement_tree() returns (upper-case template-var keys,
 * ms->s timestamp conversion) -- the tree's own shape (category
 * bucketing, recursive children, uncategorized fallback) is covered by
 * model/achievement_test.php instead, in isolation from template
 * rendering.
 */

namespace avathar\bbguildwow\tests\portal\player_detail_tabs;

use avathar\bbguildwow\model\achievement;
use avathar\bbguildwow\portal\player_detail_tabs\achievements_tab;
use PHPUnit\Framework\TestCase;

class achievements_tab_test extends TestCase
{
	private function make_recording_template(): array
	{
		$recorder = (object) ['vars' => []];
		$template = $this->createMock(\phpbb\template\template::class);
		$template->method('assign_vars')->willReturnCallback(function ($a) use ($recorder) {
			$recorder->vars = array_merge($recorder->vars, $a);
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

	public function test_render_reshapes_the_tree_into_template_vars(): void
	{
		$tree = [
			[
				'id' => 1, 'name' => 'Quests', 'total_count' => 10, 'completed_count' => 2,
				'total_points' => 100, 'earned_points' => 15, 'percent' => 20,
				'achievements' => [
					['id' => 101, 'title' => 'Outland Quest A', 'description' => 'Do a thing.', 'points' => 10, 'icon' => 'icon_a', 'achievements_completed' => 2000],
				],
				'children' => [
					[
						'id' => 11, 'name' => 'Outland', 'total_count' => 3, 'completed_count' => 1,
						'total_points' => 30, 'earned_points' => 5, 'percent' => 33,
						'achievements' => [
							['id' => 102, 'title' => 'Nested Quest', 'description' => '', 'points' => 5, 'icon' => '', 'achievements_completed' => 1000],
						],
						'children' => [],
					],
				],
			],
			[
				'id' => 2, 'name' => 'Exploration', 'total_count' => 5, 'completed_count' => 1,
				'total_points' => 50, 'earned_points' => 10, 'percent' => 20,
				'achievements' => [
					['id' => 103, 'title' => 'Explore Zone C', 'description' => '', 'points' => 10, 'icon' => 'icon_c', 'achievements_completed' => 1500],
				],
				'children' => [],
			],
		];

		$model = $this->getMockBuilder(achievement::class)->disableOriginalConstructor()->getMock();
		$model->expects($this->once())->method('setGameId')->with('wow');
		$model->method('get_player_achievement_tree')->with(42)->willReturn($tree);

		[$template, $recorder] = $this->make_recording_template();
		$tab = new achievements_tab($model, $template, $this->make_language());

		$path = $tab->render(42);

		$this->assertSame('@avathar_bbguildwow/portal/achievements_tab.html', $path);
		// completed_count/earned_points sum across ROOT nodes only -- each
		// root's own totals already roll up its children (that's
		// get_player_achievement_tree()'s job), so summing again here
		// would double-count.
		$this->assertSame(3, $recorder->vars['WOW_ACHIEVEMENT_COUNT']); // 2 + 1
		$this->assertSame(25, $recorder->vars['WOW_ACHIEVEMENT_POINTS']); // 15 + 10

		$out = $recorder->vars['WOW_ACHIEV_TREE'];
		$this->assertCount(2, $out);

		$quests = $out[0];
		$this->assertSame(1, $quests['ID']);
		$this->assertSame('Quests', $quests['NAME']);
		$this->assertSame(20, $quests['PERCENT']);
		$this->assertSame(2, $quests['COMPLETED_COUNT']);
		$this->assertSame(10, $quests['TOTAL_COUNT']);
		$this->assertSame(15, $quests['EARNED_POINTS']);
		$this->assertCount(1, $quests['ACHIEVEMENTS']);
		$this->assertSame(101, $quests['ACHIEVEMENTS'][0]['ID']); // bbTips data-wowhead="achievement={{ row.ID }}"
		$this->assertSame('Outland Quest A', $quests['ACHIEVEMENTS'][0]['TITLE']);
		$this->assertSame('01/01/1970', $quests['ACHIEVEMENTS'][0]['DATE']);

		// One level of recursion: Quests > Outland.
		$this->assertCount(1, $quests['CHILDREN']);
		$outland = $quests['CHILDREN'][0];
		$this->assertSame('Outland', $outland['NAME']);
		$this->assertSame('Nested Quest', $outland['ACHIEVEMENTS'][0]['TITLE']);
		$this->assertSame([], $outland['CHILDREN']);

		$exploration = $out[1];
		$this->assertSame('Exploration', $exploration['NAME']);
		$this->assertSame([], $exploration['CHILDREN']);
	}

	public function test_render_converts_millisecond_timestamps(): void
	{
		$tree = [
			[
				'id' => 1, 'name' => 'Quests', 'total_count' => 1, 'completed_count' => 1,
				'total_points' => 10, 'earned_points' => 10, 'percent' => 100,
				'achievements' => [
					// Battle.net completion timestamps come back in
					// milliseconds; anything above the ~y2286 second-scale
					// ceiling gets divided down before date().
					['id' => 104, 'title' => 'A', 'description' => '', 'points' => 10, 'icon' => '', 'achievements_completed' => 1700000000000],
				],
				'children' => [],
			],
		];

		$model = $this->getMockBuilder(achievement::class)->disableOriginalConstructor()->getMock();
		$model->method('get_player_achievement_tree')->willReturn($tree);

		[$template, $recorder] = $this->make_recording_template();
		$tab = new achievements_tab($model, $template, $this->make_language());
		$tab->render(1);

		$this->assertSame(date('d/m/Y', 1700000000), $recorder->vars['WOW_ACHIEV_TREE'][0]['ACHIEVEMENTS'][0]['DATE']);
	}

	public function test_render_handles_an_empty_tree(): void
	{
		$model = $this->getMockBuilder(achievement::class)->disableOriginalConstructor()->getMock();
		$model->method('get_player_achievement_tree')->willReturn([]);

		[$template, $recorder] = $this->make_recording_template();
		$tab = new achievements_tab($model, $template, $this->make_language());
		$tab->render(1);

		$this->assertSame(0, $recorder->vars['WOW_ACHIEVEMENT_COUNT']);
		$this->assertSame(0, $recorder->vars['WOW_ACHIEVEMENT_POINTS']);
		$this->assertSame([], $recorder->vars['WOW_ACHIEV_TREE']);
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
