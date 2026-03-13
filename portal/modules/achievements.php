<?php
/**
 * WoW Achievements portal module.
 *
 * Displays a 3-level achievement browser: category cards with progress rings,
 * AJAX-loaded achievement list, and achievement detail modal.
 *
 * @package   avathar\bbguild_wow
 * @copyright 2018 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild_wow\portal\modules;

use avathar\bbguild\portal\modules\module_base;
use avathar\bbguild_wow\model\achievement;
use phpbb\config\config;
use phpbb\db\driver\driver_interface;
use phpbb\template\template;

class achievements extends module_base
{
	protected int $columns = 21; // top + center + bottom
	protected string $name = 'BBGUILD_PORTAL_ACHIEVEMENTS';
	protected string $image_src = '';
	protected $language = array('vendor' => 'avathar/bbguild_wow', 'file' => 'wow');

	/** @var config */
	protected config $config;

	/** @var driver_interface */
	protected driver_interface $db;

	/** @var template */
	protected template $template;

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
		string $achievement_table,
		string $achievement_track_table,
		string $guild_wow_table,
		achievement $achievement_model
	)
	{
		$this->config = $config;
		$this->db = $db;
		$this->template = $template;
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
		// Respect ACP "Show Achievements" toggle
		if (empty($this->config['bbguild_show_achiev']))
		{
			return null;
		}

		// Check if this guild has achievement points
		$sql = 'SELECT achievementpoints FROM ' . $this->guild_wow_table .
			' WHERE guild_id = ' . (int) $this->guild_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row || empty($row['achievementpoints']))
		{
			return null;
		}

		$this->template->assign_var('ACHIEV_POINTS', (int) $row['achievementpoints']);

		// Load overall progress
		$this->load_overall_progress();

		// Load category progress for the card grid
		$categories = $this->achievement_model->getCategoryProgress((int) $this->guild_id);

		if (!empty($categories))
		{
			foreach ($categories as $cat)
			{
				$percent = ($cat['total_points'] > 0) ? round($cat['earned_points'] / $cat['total_points'] * 100) : 0;
				$this->template->assign_block_vars('achievement_categories', array(
					'ID'              => $cat['id'],
					'NAME'            => $cat['name'],
					'TOTAL_POINTS'    => $cat['total_points'],
					'EARNED_POINTS'   => $cat['earned_points'],
					'PERCENT'         => $percent,
					'COMPLETED_COUNT' => $cat['completed_count'],
					'TOTAL_COUNT'     => $cat['total_count'],
				));
			}
		}

		// Assign AJAX route base URLs for JS
		// Use generate_board_url() to build absolute paths that work regardless of current page URL
		$board_url = generate_board_url();
		$this->template->assign_vars(array(
			'ACHIEV_GUILD_ID'         => (int) $this->guild_id,
			'U_ACHIEV_LIST_BASE'      => $board_url . '/app.php/bbguild_wow/achievements/list/' . (int) $this->guild_id . '/',
			'U_ACHIEV_DETAIL_BASE'    => $board_url . '/app.php/bbguild_wow/achievements/detail/' . (int) $this->guild_id . '/',
			'S_ACHIEV_HAS_CATEGORIES' => !empty($categories),
		));

		// Fetch recently earned achievements
		$this->load_recent_achievements();

		return '@avathar_bbguild_wow/portal/modules/achievements_center.html';
	}

	/**
	 * Load overall achievement progress for this guild.
	 */
	protected function load_overall_progress(): void
	{
		$sql = 'SELECT COUNT(at.achievement_id) AS completed_count,
				SUM(a.points) AS earned_points
			FROM ' . $this->achievement_track_table . ' at
			INNER JOIN ' . $this->achievement_table . ' a
				ON a.id = at.achievement_id
			WHERE at.guild_id = ' . (int) $this->guild_id . '
				AND a.game_id = \'wow\'';
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$completed = (int) ($row['completed_count'] ?? 0);
		$earned = (int) ($row['earned_points'] ?? 0);

		$this->template->assign_vars(array(
			'ACHIEV_COMPLETED' => $completed,
			'ACHIEV_EARNED'    => $earned,
		));
	}

	/**
	 * Load recently earned achievements (last 5).
	 */
	protected function load_recent_achievements(): void
	{
		$sql = 'SELECT a.title, a.description, a.points, a.icon,
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
