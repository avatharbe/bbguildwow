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
 * underneath in a collapsible <details> section. A category with real
 * children (e.g. "Dungeons & Raids" > "Classic" / "Cataclysm Dungeon" /
 * ...) unfurls into a tab strip instead of one flat list, and a child
 * with children of its own would unfurl the same way again -- the tree
 * itself decides how many levels there are (in practice almost always
 * exactly one, confirmed against the live API, but nothing here assumes
 * that). Single page, no AJAX drill-down (a deliberate, smaller-scope
 * choice than fully mirroring portal\modules\achievements's 3-level AJAX
 * browser).
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

		$tree = array_map(array($this, 'format_node'), $this->achievement_model->get_player_achievement_tree($player_id));

		$this->template->assign_vars(array(
			'WOW_ACHIEVEMENT_COUNT'  => array_sum(array_column($tree, 'COMPLETED_COUNT')),
			'WOW_ACHIEVEMENT_POINTS' => array_sum(array_column($tree, 'EARNED_POINTS')),
			'WOW_ACHIEV_TREE'        => $tree,
		));

		return '@avathar_bbguildwow/portal/achievements_tab.html';
	}

	/**
	 * Recursively reshape one get_player_achievement_tree() node (and its
	 * children) into the upper-case template-var keys the .html file
	 * expects, formatting each of the node's own completed-achievement
	 * rows (Battle.net timestamps are in milliseconds) along the way.
	 *
	 * @param array $node
	 * @return array
	 */
	private function format_node(array $node): array
	{
		return array(
			'ID'              => $node['id'],
			'NAME'            => $node['name'],
			'PERCENT'         => $node['percent'],
			'COMPLETED_COUNT' => $node['completed_count'],
			'TOTAL_COUNT'     => $node['total_count'],
			'EARNED_POINTS'   => $node['earned_points'],
			'ACHIEVEMENTS'    => array_map(array($this, 'format_achievement_row'), $node['achievements']),
			'CHILDREN'        => array_map(array($this, 'format_node'), $node['children']),
		);
	}

	/**
	 * @param array $row
	 * @return array
	 */
	private function format_achievement_row(array $row): array
	{
		$timestamp = $row['achievements_completed'];
		if ($timestamp > 9999999999)
		{
			// Battle.net timestamps are in milliseconds.
			$timestamp = (int) ($timestamp / 1000);
		}

		return array(
			'TITLE'       => $row['title'],
			'DESCRIPTION' => $row['description'],
			'POINTS'      => $row['points'],
			'ICON'        => $row['icon'],
			'DATE'        => date('d/m/Y', $timestamp),
		);
	}
}
