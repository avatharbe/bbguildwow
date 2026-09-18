<?php
/**
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Achievements player-detail tab (bbguildwow#44 / bbguild#365). Surfaces
 * the existing guild-level achievement model's player_id support as a
 * per-character view, following the same player_detail_tab_interface
 * pattern #375 already established for talents/raid-progression/pvp.
 *
 * Bucketed by category, matching Blizzard's own armory achievements
 * page: a row of category cards (name, points, a completion-percentage
 * ring) at the top, then each category's completed achievements listed
 * underneath in a collapsible <details> section — with a further
 * sub-heading split for categories that have child categories (e.g.
 * "Quests" > "Outland"), matching the armory's own category/sub-tab
 * structure. Single page, no AJAX drill-down (a deliberate, smaller-
 * scope choice than fully mirroring portal\modules\achievements's
 * 3-level AJAX browser).
 *
 * The first version of this tab (a flat, paginated "recent completions"
 * list) is gone — this replaces it entirely rather than adding to it,
 * once real category data made the flat list's actual gap obvious: it
 * had no sense of what a character had actually accomplished game-wide,
 * just a chronological feed.
 *
 * Display-only: does not touch the guild-level achievement browser
 * (portal\modules\achievements) or its sync logic.
 */

namespace avathar\bbguildwow\portal\player_detail_tabs;

use avathar\bbguild\portal\player_detail_tab_interface;
use avathar\bbguildwow\model\achievement;
use phpbb\language\language;
use phpbb\template\template;

class achievements_tab implements player_detail_tab_interface
{
	/** @var achievement */
	protected $achievement_model;

	/** @var template */
	protected $template;

	/** @var language */
	protected $language;

	public function __construct(achievement $achievement_model, template $template, language $language)
	{
		$this->achievement_model = $achievement_model;
		$this->template = $template;
		$this->language = $language;
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
		$this->language->add_lang('wow', 'avathar/bbguildwow');

		$categories = $this->achievement_model->get_player_category_progress($player_id);
		$rows = $this->achievement_model->get_player_completed_achievements_grouped($player_id);

		// Bucket the flat, pre-sorted row list into [top_id => [sub_key => [...rows]]],
		// preserving sub-group order of first appearance (which get_player_completed_achievements_grouped()
		// already sorted by category display_order / name).
		$buckets = array();
		foreach ($rows as $row)
		{
			$top_id = $row['top_id'];
			$sub_key = $row['sub_name'] ?? '';
			$buckets[$top_id][$sub_key]['sub_name'] = $row['sub_name'];
			$buckets[$top_id][$sub_key]['achievements'][] = $row;
		}

		$total_points = 0;
		$total_completed = 0;

		foreach ($categories as $cat)
		{
			$total_points += $cat['earned_points'];
			$total_completed += $cat['completed_count'];

			if ($cat['total_count'] === 0)
			{
				// A category the catalog knows about but with zero
				// achievements assigned to it (or its children) for this
				// game -- nothing to show a ring or section for.
				continue;
			}

			$percent = $cat['total_count'] > 0 ? (int) round($cat['completed_count'] / $cat['total_count'] * 100) : 0;

			$this->template->assign_block_vars('achiev_category', array(
				'ID'              => $cat['id'],
				'NAME'            => $cat['name'],
				'PERCENT'         => $percent,
				'COMPLETED_COUNT' => $cat['completed_count'],
				'TOTAL_COUNT'     => $cat['total_count'],
				'EARNED_POINTS'   => $cat['earned_points'],
				'S_EMPTY'         => ($cat['completed_count'] === 0),
			));

			if (!empty($buckets[$cat['id']]))
			{
				$this->assign_section($cat['id'], $cat['name'], $buckets[$cat['id']]);
				unset($buckets[$cat['id']]);
			}
		}

		// Anything left in $buckets belongs to a category outside the
		// catalog's own top-level list entirely (category_id=0 stub rows,
		// or a category row that's itself somehow missing) -- surface it
		// rather than silently dropping real completed achievements.
		foreach ($buckets as $leftover_top_id => $groups)
		{
			$name = null;
			foreach ($groups as $group)
			{
				if (!empty($group['achievements'][0]['top_name']))
				{
					$name = $group['achievements'][0]['top_name'];
					break;
				}
			}
			$this->assign_section($leftover_top_id, $name ?? $this->language->lang('WOW_ACHIEVEMENTS_UNCATEGORIZED'), $groups);
		}

		$this->template->assign_vars(array(
			'WOW_ACHIEVEMENT_COUNT'  => $total_completed,
			'WOW_ACHIEVEMENT_POINTS' => $total_points,
		));

		return '@avathar_bbguildwow/portal/achievements_tab.html';
	}

	/**
	 * Assign one category's <details> section: the section itself, then
	 * one achievement_row per achievement, nested under it. A group with
	 * a non-null sub_name (the category has children, e.g. "Quests" >
	 * "Outland") gets its sub_name carried on every row in that group so
	 * the template can render a sub-heading whenever it changes —
	 * flattened rather than a third block-nesting level, which phpBB's
	 * template engine doesn't reliably support beyond two.
	 *
	 * @param int    $top_id
	 * @param string $top_name
	 * @param array  $groups [sub_key => ['sub_name' => string|null, 'achievements' => array]]
	 */
	private function assign_section(int $top_id, string $top_name, array $groups): void
	{
		$this->template->assign_block_vars('achiev_section', array(
			'ID'   => $top_id,
			'NAME' => $top_name,
		));

		foreach ($groups as $group)
		{
			foreach ($group['achievements'] as $row)
			{
				$timestamp = $row['achievements_completed'];
				if ($timestamp > 9999999999)
				{
					// Battle.net timestamps are in milliseconds.
					$timestamp = (int) ($timestamp / 1000);
				}

				$this->template->assign_block_vars('achiev_section.achievement_row', array(
					'SUB_NAME'    => $group['sub_name'],
					'TITLE'       => $row['title'],
					'DESCRIPTION' => $row['description'],
					'POINTS'      => $row['points'],
					'ICON'        => $row['icon'],
					'DATE'        => date('d/m/Y', $timestamp),
				));
			}
		}
	}
}
