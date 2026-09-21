<?php
/**
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Unit tests for the Talents player-detail tab (bbguildwow#45).
 */

namespace avathar\bbguildwow\tests\portal\player_detail_tabs;

use avathar\bbguildwow\game\wow_api;
use avathar\bbguildwow\portal\player_detail_tabs\talents_tab;
use PHPUnit\Framework\TestCase;

class talents_tab_test extends TestCase
{
	/**
	 * Same recording-template approach as achievements_tab_test.php's
	 * make_recording_template() -- talents_tab's constructor type-hints
	 * the real (concrete) \phpbb\template\template class, so a mock with
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

	/** A minimal but realistic getCharacterSpecializations() response shape, confirmed against a live API call (bbguildwow#45). */
	private function sample_specializations_response(): array
	{
		return array(
			'active_specialization' => array('id' => 71, 'name' => 'Arms'),
			'specializations' => array(
				array(
					'specialization' => array('id' => 71, 'name' => 'Arms'),
					'loadouts' => array(
						array('is_active' => false, 'selected_class_talents' => array(
							array('rank' => 1, 'tooltip' => array('talent' => array('name' => 'Inactive Loadout Talent'))),
						)),
						array(
							'is_active' => true,
							'selected_class_talents' => array(
								array('rank' => 1, 'tooltip' => array('talent' => array('name' => 'Battle Stance'), 'spell_tooltip' => array('description' => 'A combat stance.', 'spell' => array('id' => 386164)))),
								array('rank' => 1), // no tooltip at all -- seen in live responses, must be skipped
							),
							'selected_spec_talents' => array(
								array('rank' => 2, 'tooltip' => array('talent' => array('name' => 'Deep Wounds'))),
							),
							'selected_hero_talents' => array(),
						),
					),
				),
				array(
					'specialization' => array('id' => 72, 'name' => 'Fury'),
					'loadouts' => array(
						array('is_active' => true, 'selected_class_talents' => array(
							array('rank' => 1, 'tooltip' => array('talent' => array('name' => 'Should not appear — not the active spec'))),
						)),
					),
				),
			),
		);
	}

	public function test_render_shows_active_specs_active_loadout_talents(): void
	{
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$db->method('sql_query')->willReturn('RESULT');
		$db->method('sql_fetchrow')->willReturn(array(
			'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn', 'player_region' => 'eu', 'game_edition' => 'retail',
		));

		$wow_api = $this->getMockBuilder(wow_api::class)->disableOriginalConstructor()->getMock();
		$wow_api->expects($this->once())->method('fetch_character_talents')
			->with('Sajaki', 'argent-dawn', 'eu', 'retail')
			->willReturn($this->sample_specializations_response());

		[$template, $recorder] = $this->make_recording_template();
		$tab = new talents_tab($wow_api, $db, $template, 'bb_players', 'bb_guild');

		$path = $tab->render(42);

		$this->assertSame('@avathar_bbguildwow/portal/talents_tab.html', $path);
		$this->assertTrue($recorder->vars['WOW_TALENTS_HAS_DATA']);
		$this->assertSame('Arms', $recorder->vars['WOW_TALENTS_SPEC_NAME']);

		// Only the active loadout's talents, from the active (Arms) spec —
		// not the inactive loadout, and not Fury's talents.
		$this->assertCount(1, $recorder->blocks['class_talent_row']); // the untooltipped entry was skipped
		$this->assertSame('Battle Stance', $recorder->blocks['class_talent_row'][0]['NAME']);
		$this->assertFalse($recorder->blocks['class_talent_row'][0]['S_MULTI_RANK']);
		$this->assertSame(386164, $recorder->blocks['class_talent_row'][0]['SPELL_ID']);
		// No global $phpbb_container in the test harness -- degrades to no
		// bbTips link rather than fataling (bbguildwow#379-adjacent fix).
		$this->assertSame('', $recorder->blocks['class_talent_row'][0]['LINK']);

		$this->assertCount(1, $recorder->blocks['spec_talent_row']);
		$this->assertSame('Deep Wounds', $recorder->blocks['spec_talent_row'][0]['NAME']);
		$this->assertSame(2, $recorder->blocks['spec_talent_row'][0]['RANK']);
		$this->assertTrue($recorder->blocks['spec_talent_row'][0]['S_MULTI_RANK']);

		$this->assertArrayNotHasKey('hero_talent_row', $recorder->blocks); // empty array -> no block rows
	}

	public function test_render_no_data_when_api_returns_false(): void
	{
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$db->method('sql_query')->willReturn('RESULT');
		$db->method('sql_fetchrow')->willReturn(array(
			'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn', 'player_region' => 'eu', 'game_edition' => 'retail',
		));

		$wow_api = $this->getMockBuilder(wow_api::class)->disableOriginalConstructor()->getMock();
		$wow_api->method('fetch_character_talents')->willReturn(false); // missing credentials / API error

		[$template, $recorder] = $this->make_recording_template();
		$tab = new talents_tab($wow_api, $db, $template, 'bb_players', 'bb_guild');

		$tab->render(42);

		$this->assertFalse($recorder->vars['WOW_TALENTS_HAS_DATA']);
		$this->assertSame('', $recorder->vars['WOW_TALENTS_SPEC_NAME']);
		$this->assertArrayNotHasKey('class_talent_row', $recorder->blocks);
	}

	public function test_render_returns_null_when_player_not_found(): void
	{
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$db->method('sql_query')->willReturn('RESULT');
		$db->method('sql_fetchrow')->willReturn(false);

		$wow_api = $this->getMockBuilder(wow_api::class)->disableOriginalConstructor()->getMock();
		$wow_api->expects($this->never())->method('fetch_character_talents');

		[$template] = $this->make_recording_template();
		$tab = new talents_tab($wow_api, $db, $template, 'bb_players', 'bb_guild');

		$this->assertNull($tab->render(999));
	}

	public function test_render_no_data_when_api_throws(): void
	{
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$db->method('sql_query')->willReturn('RESULT');
		$db->method('sql_fetchrow')->willReturn(array(
			'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn', 'player_region' => 'eu', 'game_edition' => 'retail',
		));

		$wow_api = $this->getMockBuilder(wow_api::class)->disableOriginalConstructor()->getMock();
		$wow_api->method('fetch_character_talents')->willThrowException(new \RuntimeException('API down'));

		[$template, $recorder] = $this->make_recording_template();
		$tab = new talents_tab($wow_api, $db, $template, 'bb_players', 'bb_guild');

		$path = $tab->render(42);

		$this->assertSame('@avathar_bbguildwow/portal/talents_tab.html', $path); // degrades gracefully, doesn't fatal
		$this->assertFalse($recorder->vars['WOW_TALENTS_HAS_DATA']);
	}

	public function test_is_available_only_for_wow(): void
	{
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$wow_api = $this->getMockBuilder(wow_api::class)->disableOriginalConstructor()->getMock();
		[$template] = $this->make_recording_template();
		$tab = new talents_tab($wow_api, $db, $template, 'bb_players', 'bb_guild');

		$this->assertTrue($tab->is_available(1, 'wow'));
		$this->assertFalse($tab->is_available(1, 'gw2'));
	}
}
