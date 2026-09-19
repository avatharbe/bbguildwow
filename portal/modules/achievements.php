<?php
/**
 * WoW Achievements portal module.
 *
 * Displays a 3-level achievement browser: category cards with progress rings,
 * AJAX-loaded achievement list, and achievement detail modal.
 *
 * @package   avathar\bbguildwow
 * @copyright 2018 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\portal\modules;

use avathar\bbguild\portal\modules\module_base;
use avathar\bbguildwow\model\achievement;
use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\db\driver\driver_interface;
use phpbb\template\template;

class achievements extends module_base
{
	protected int $columns = 21; // top + center + bottom
	protected string $name = 'BBGUILD_PORTAL_ACHIEVEMENTS';
	protected string $image_src = '';
	protected $language = array('vendor' => 'avathar/bbguildwow', 'file' => 'wow');

	/** @var config */
	protected config $config;

	/** @var driver_interface */
	protected driver_interface $db;

	/** @var template */
	protected template $template;

	/** @var helper */
	protected helper $helper;

	/** @var string */
	protected string $achievement_table;

	/** @var string */
	protected string $achievement_track_table;

	/** @var string */
	protected string $guild_wow_table;

	/** @var achievement */
	protected achievement $achievement_model;

	public function __construct(
		config $config,
		driver_interface $db,
		template $template,
		helper $helper,
		string $achievement_table,
		string $achievement_track_table,
		string $guild_wow_table,
		achievement $achievement_model
	)
	{
		$this->config = $config;
		$this->db = $db;
		$this->template = $template;
		$this->helper = $helper;
		$this->achievement_table = $achievement_table;
		$this->achievement_track_table = $achievement_track_table;
		$this->guild_wow_table = $guild_wow_table;
		$this->achievement_model = $achievement_model;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_template_center(int $module_id)
	{
		// Load guild achievement points from Blizzard API (authoritative)
		$sql = 'SELECT achievementpoints FROM ' . $this->guild_wow_table .
			' WHERE guild_id = ' . (int) $this->guild_id;
		$result = $this->db->sql_query($sql);
		$achiev_points = (int) ($this->db->sql_fetchfield('achievementpoints') ?? 0);
		$this->db->sql_freeresult($result);

		$this->template->assign_var('ACHIEV_POINTS', $achiev_points);

		// Load category progress for the card grid
		$categories = $this->achievement_model->getCategoryProgress((int) $this->guild_id);

		$hide_empty = (int) $this->config['bbguild_achiev_hide_empty'];

		$sum_completed = 0;
		$sum_total = 0;

		if (!empty($categories))
		{
			foreach ($categories as $cat)
			{
				$percent = ($cat['total_count'] > 0) ? round($cat['completed_count'] / $cat['total_count'] * 100) : 0;
				$this->template->assign_block_vars('achievement_categories', array(
					'ID'              => $cat['id'],
					'NAME'            => $cat['name'],
					'TOTAL_POINTS'    => $cat['total_points'],
					'EARNED_POINTS'   => $cat['earned_points'],
					'PERCENT'         => $percent,
					'COMPLETED_COUNT' => $cat['completed_count'],
					'TOTAL_COUNT'     => $cat['total_count'],
					'S_EMPTY'         => ($cat['completed_count'] == 0),
				));
				if (!$hide_empty || $cat['completed_count'] > 0)
				{
					$sum_completed += $cat['completed_count'];
					$sum_total += $cat['total_count'];
				}
			}
		}

		$this->template->assign_vars(array(
			'ACHIEV_COMPLETED'   => $sum_completed,
			'ACHIEV_TOTAL_COUNT' => $sum_total,
		));

		// Assign AJAX route base URLs for JS — use helper->route() for correct paths on any installation
		$achiev_list_url = $this->helper->route('avathar_bbguildwow_achiev_list', array('guild_id' => (int) $this->guild_id, 'category_id' => 0));
		$achiev_detail_url = $this->helper->route('avathar_bbguildwow_achiev_detail', array('guild_id' => (int) $this->guild_id, 'achievement_id' => 0));
		// Strip the trailing placeholder "0" so JS can append the real ID
		$achiev_list_base = substr($achiev_list_url, 0, strrpos($achiev_list_url, '0'));
		$achiev_detail_base = substr($achiev_detail_url, 0, strrpos($achiev_detail_url, '0'));

		$this->template->assign_vars(array(
			'ACHIEV_GUILD_ID'         => (int) $this->guild_id,
			'U_ACHIEV_LIST_BASE'      => $achiev_list_base,
			'U_ACHIEV_DETAIL_BASE'    => $achiev_detail_base,
			'S_ACHIEV_HAS_CATEGORIES' => !empty($categories),
			'S_ACHIEV_HIDE_EMPTY'     => $hide_empty,
		));

		// Fetch recently earned achievements
		$this->load_recent_achievements();

		return '@avathar_bbguildwow/portal/modules/achievements_center.html';
	}

	/**
	 * Load overall achievement progress for this guild.
	 *
	 * Uses achievementpoints from guild_wow table (Blizzard's authoritative
	 * guild-level total) for points display. The achievement detail table
	 * stores character-level point values which differ from guild-level.
	 */
	protected function load_overall_progress(): void
	{
		// Completed count — only achievements with a completion timestamp
		$sql = 'SELECT COUNT(at.achievement_id) AS completed_count
			FROM ' . $this->achievement_track_table . ' at
			INNER JOIN ' . $this->achievement_table . ' a
				ON a.id = at.achievement_id
			WHERE at.guild_id = ' . (int) $this->guild_id . '
				AND a.game_id = \'wow\'
				AND at.achievements_completed > 0';
		$result = $this->db->sql_query($sql);
		$completed = (int) ($this->db->sql_fetchfield('completed_count') ?? 0);
		$this->db->sql_freeresult($result);

		// Total achievements in catalog
		$sql = 'SELECT COUNT(*) AS total_count
			FROM ' . $this->achievement_table . '
			WHERE game_id = \'wow\'';
		$result = $this->db->sql_query($sql);
		$total_count = (int) ($this->db->sql_fetchfield('total_count') ?? 0);
		$this->db->sql_freeresult($result);

		// Guild-level achievement points from Blizzard API (authoritative)
		$sql = 'SELECT achievementpoints FROM ' . $this->guild_wow_table .
			' WHERE guild_id = ' . (int) $this->guild_id;
		$result = $this->db->sql_query($sql);
		$achiev_points = (int) ($this->db->sql_fetchfield('achievementpoints') ?? 0);
		$this->db->sql_freeresult($result);

		$this->template->assign_vars(array(
			'ACHIEV_COMPLETED'   => $completed,
			'ACHIEV_TOTAL_COUNT' => $total_count,
			'ACHIEV_POINTS'      => $achiev_points,
		));
	}

	/**
	 * Load recently earned achievements (last 5).
	 */
	protected function load_recent_achievements(): void
	{
		$sql = 'SELECT a.id, a.title, a.description, a.points, a.icon,
				at.achievements_completed
			FROM ' . $this->achievement_track_table . ' at
			INNER JOIN ' . $this->achievement_table . ' a
				ON a.id = at.achievement_id
			WHERE at.guild_id = ' . (int) $this->guild_id . '
				AND a.game_id = \'wow\'
				AND at.achievements_completed > 0
				AND a.title <> \'\'
			ORDER BY at.achievements_completed DESC';
		$result = $this->db->sql_query_limit($sql, 5);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$timestamp = (int) $row['achievements_completed'];
			// Battle.net timestamps are in milliseconds
			if ($timestamp > 9999999999)
			{
				$timestamp = (int) ($timestamp / 1000);
			}

			$this->template->assign_block_vars('recent_achievements', array(
				'ID'          => (int) $row['id'],
				'TITLE'       => $row['title'],
				'DESCRIPTION' => $row['description'],
				'POINTS'      => (int) $row['points'],
				'ICON'        => $row['icon'],
				'DATE'        => date('d/m/Y', $timestamp),
			));
		}
		$this->db->sql_freeresult($result);
	}
}
