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
 * Paginated (15 per page, matching get_tracked_achievements()'s own
 * $per_page) via phpBB's standard pagination service — same
 * request-'start'-param + generate_template_pagination() pattern
 * acp/achievement_module.php's guild-level achievement list already
 * uses, just against a Symfony route instead of an ACP index.php link.
 *
 * Display-only: does not touch the guild-level achievement browser
 * (portal\modules\achievements) or its sync logic.
 */

namespace avathar\bbguildwow\portal\player_detail_tabs;

use avathar\bbguild\portal\player_detail_tab_interface;
use avathar\bbguildwow\model\achievement;
use phpbb\controller\helper;
use phpbb\db\driver\driver_interface;
use phpbb\pagination;
use phpbb\request\request;
use phpbb\template\template;

class achievements_tab implements player_detail_tab_interface
{
	/** Matches get_tracked_achievements()'s own hardcoded $per_page. */
	const PER_PAGE = 15;

	/** @var achievement */
	protected $achievement_model;

	/** @var template */
	protected $template;

	/** @var driver_interface */
	protected $db;

	/** @var request */
	protected $request;

	/** @var pagination */
	protected $pagination;

	/** @var helper */
	protected $helper;

	/** @var string */
	protected $players_table;

	public function __construct(
		achievement $achievement_model,
		template $template,
		driver_interface $db,
		request $request,
		pagination $pagination,
		helper $helper,
		string $players_table
	)
	{
		$this->achievement_model = $achievement_model;
		$this->template = $template;
		$this->db = $db;
		$this->request = $request;
		$this->pagination = $pagination;
		$this->helper = $helper;
		$this->players_table = $players_table;
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

		$start = $this->request->variable('start', 0);

		// get_tracked_achievements() serves both the guild-level browser's
		// list view and this per-character view — passing guild_id=0 with
		// a non-zero player_id switches it to the player_id filter branch.
		// completed_only=true (bbguildwow#44 follow-up) excludes
		// still-in-progress tracked rows at the SQL level, so $total below
		// is an accurate page count for what this tab actually displays.
		[$tracked, , $total] = $this->achievement_model->get_tracked_achievements($start, 0, $player_id, true);

		// Page-local newest-first ordering (get_tracked_achievements()'s
		// own default sort is by achievement id, not completion date).
		// This only orders within the current page, not across the whole
		// result set — a real cross-page date sort would need passing a
		// sort-order override through to switch_order(), left as a
		// follow-up rather than part of this pagination pass.
		usort($tracked, fn (array $a, array $b) => (int) $b['achievements_completed'] <=> (int) $a['achievements_completed']);

		foreach ($tracked as $row)
		{
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
			'WOW_ACHIEVEMENT_COUNT'  => $total,
			'WOW_ACHIEVEMENT_POINTS' => $this->achievement_model->get_player_completed_points($player_id),
		));

		$guild_id = $this->get_guild_id($player_id);
		if ($guild_id !== null)
		{
			$pagination_url = $this->helper->route('avathar_bbguild_player', array(
				'guild_id'  => $guild_id,
				'player_id' => $player_id,
				'tab_slug'  => $this->get_tab_slug(),
			));
			$this->pagination->generate_template_pagination($pagination_url, 'pagination', 'start', $total, self::PER_PAGE, $start);
		}

		return '@avathar_bbguildwow/portal/achievements_tab.html';
	}

	/**
	 * @param int $player_id
	 * @return int|null
	 */
	private function get_guild_id(int $player_id): ?int
	{
		$sql = 'SELECT player_guild_id FROM ' . $this->players_table . ' WHERE player_id = ' . $player_id;
		$result = $this->db->sql_query($sql);
		$guild_id = $this->db->sql_fetchfield('player_guild_id');
		$this->db->sql_freeresult($result);

		return $guild_id !== false ? (int) $guild_id : null;
	}
}
