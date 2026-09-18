<?php
/**
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Raid Progression player-detail tab (#375) — placeholder pending the roadmap's
 * Gameworld extension (bb_bosstable/bb_zonetable, 2.3.0). Ships as a visible
 * "coming soon" tab so the bar matches the target design; a future ticket fills
 * in real content.
 */

namespace avathar\bbguildwow\portal\player_detail_tabs;

use avathar\bbguild\portal\player_detail_tab_interface;
use phpbb\language\language;
use phpbb\template\template;

class raid_progression_tab implements player_detail_tab_interface
{
	/** @var template */
	protected $template;

	/** @var language */
	protected $language;

	public function __construct(template $template, language $language)
	{
		$this->template = $template;
		$this->language = $language;
	}

	public function get_tab_name(): string
	{
		return 'WOW_RAID_PROGRESSION';
	}

	public function get_tab_slug(): string
	{
		return 'raid-progression';
	}

	public function get_tab_order(): int
	{
		return 20;
	}

	public function is_available(int $player_id, string $game_id): bool
	{
		// Deliberately hidden for the 2.1.0 release: this tab has no real
		// data source yet (blocked on Gameworld#16) and is only a "coming
		// soon" placeholder. Re-enable (drop back to
		// `return $game_id === 'wow';`) once #46 lands -- milestoned 2.2.0.
		return false;
	}

	public function render(int $player_id): ?string
	{
		$this->template->assign_vars(array(
			'TAB_LABEL' => $this->language->lang('WOW_RAID_PROGRESSION'),
		));

		return '@avathar_bbguildwow/portal/player_detail_tab_placeholder.html';
	}
}
