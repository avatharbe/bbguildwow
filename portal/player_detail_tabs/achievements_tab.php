<?php
/**
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Achievements player-detail tab (bbguildwow#44 / bbguild#365). Surfaces
 * the existing guild-level achievement model's player_id support
 * (achievement::get_tracked_achievements()) as a per-character view,
 * following the same player_detail_tab_interface pattern #375 already
 * established for talents/raid-progression/pvp. Unlike those three, this
 * one has a real data source from day one — no placeholder needed.
 *
 * Display-only: does not touch the guild-level achievement browser
 * (portal\modules\achievements) or its sync logic.
 */

namespace avathar\bbguildwow\portal\player_detail_tabs;

use avathar\bbguild\portal\player_detail_tab_interface;
use avathar\bbguildwow\model\achievement;
use phpbb\template\template;

class achievements_tab implements player_detail_tab_interface
{
	/** @var achievement */
	protected $achievement_model;

	/** @var template */
	protected $template;

	public function __construct(achievement $achievement_model, template $template)
	{
		$this->achievement_model = $achievement_model;
		$this->template = $template;
	}

	public function get_tab_name(): string
	{
		return 'WOW_ACHIEVEMENTS';
	}

	public function get_tab_slug(): string
	{
		return 'achievements';
	}

	public function get_tab_order(): int
	{
		return 40;
	}

	public function is_available(int $player_id, string $game_id): bool
	{
		return $game_id === 'wow';
	}

	public function render(int $player_id): ?string
	{
		$this->achievement_model->setGameId('wow');

		// get_tracked_achievements() serves both the guild-level browser's
		// list view and this per-character view — passing guild_id=0 with
		// a non-zero player_id switches it to the player_id filter branch.
		// It returns every tracked row (completed and still-in-progress);
		// this tab only surfaces completed ones.
		[$tracked] = $this->achievement_model->get_tracked_achievements(0, 0, $player_id);
		$completed = array_values(array_filter(
			$tracked,
			fn (array $row) => (int) $row['achievements_completed'] > 0
		));

		usort($completed, fn (array $a, array $b) => (int) $b['achievements_completed'] <=> (int) $a['achievements_completed']);

		$total_points = 0;
		foreach ($completed as $row)
		{
			$total_points += (int) $row['points'];

			$timestamp = (int) $row['achievements_completed'];
			if ($timestamp > 9999999999)
			{
				// Battle.net timestamps are in milliseconds.
				$timestamp = (int) ($timestamp / 1000);
			}

			$this->template->assign_block_vars('wow_achievement_row', array(
				'TITLE'       => $row['title'],
				'DESCRIPTION' => $row['description'],
				'POINTS'      => (int) $row['points'],
				'ICON'        => $row['icon'],
				'DATE'        => date('d/m/Y', $timestamp),
			));
		}

		$this->template->assign_vars(array(
			'WOW_ACHIEVEMENT_COUNT'  => count($completed),
			'WOW_ACHIEVEMENT_POINTS' => $total_points,
		));

		return '@avathar_bbguildwow/portal/achievements_tab.html';
	}
}
