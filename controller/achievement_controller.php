<?php
/**
 * Achievement AJAX controller
 *
 * Provides JSON endpoints for the 3-level achievement browser:
 * - categories: per-root-category progress
 * - achievement_list: achievements in a category
 * - achievement_detail: single achievement with criteria
 *
 * @package   avathar\bbguild_wow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild_wow\controller;

use avathar\bbguild_wow\model\achievement;
use phpbb\db\driver\driver_interface;
use phpbb\request\request;
use Symfony\Component\HttpFoundation\JsonResponse;

class achievement_controller
{
	/** @var achievement */
	protected $achievement_model;

	/** @var driver_interface */
	protected $db;

	/** @var request */
	protected $request;

	/** @var string */
	protected $achievement_table;

	/** @var string */
	protected $achievement_track_table;

	/** @var string */
	protected $achievement_category_table;

	/** @var string */
	protected $relations_table;

	/** @var string */
	protected $achievement_criteria_table;

	/** @var string */
	protected $criteria_track_table;

	public function __construct(
		achievement $achievement_model,
		driver_interface $db,
		request $request,
		string $achievement_table,
		string $achievement_track_table,
		string $achievement_category_table,
		string $relations_table,
		string $achievement_criteria_table,
		string $criteria_track_table
	)
	{
		$this->achievement_model = $achievement_model;
		$this->db = $db;
		$this->request = $request;
		$this->achievement_table = $achievement_table;
		$this->achievement_track_table = $achievement_track_table;
		$this->achievement_category_table = $achievement_category_table;
		$this->relations_table = $relations_table;
		$this->achievement_criteria_table = $achievement_criteria_table;
		$this->criteria_track_table = $criteria_track_table;
	}

	/**
	 * Return per-root-category achievement progress as JSON.
	 *
	 * @param int $guild_id
	 * @return JsonResponse
	 */
	public function categories($guild_id)
	{
		$guild_id = (int) $guild_id;
		$categories = $this->achievement_model->getCategoryProgress($guild_id);

		return new JsonResponse($categories);
	}

	/**
	 * Return achievements in a category (+ children) with completion status.
	 *
	 * @param int $guild_id
	 * @param int $category_id
	 * @return JsonResponse
	 */
	public function achievement_list($guild_id, $category_id)
	{
		$guild_id = (int) $guild_id;
		$category_id = (int) $category_id;
		$db = $this->db;

		// Get category name
		$sql = 'SELECT name FROM ' . $this->achievement_category_table .
			' WHERE id = ' . $category_id;
		$result = $db->sql_query($sql);
		$category_name = $db->sql_fetchfield('name');
		$db->sql_freeresult($result);

		if ($category_name === false)
		{
			return new JsonResponse(array('error' => 'Category not found'), 404);
		}

		// Get all category IDs (this category + its children)
		$category_ids = array($category_id);
		$sql = 'SELECT id FROM ' . $this->achievement_category_table .
			' WHERE parent_id = ' . $category_id;
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$category_ids[] = (int) $row['id'];
		}
		$db->sql_freeresult($result);

		// Fetch achievements in these categories
		$sql = 'SELECT a.id, a.title, a.description, a.points, a.icon,
				at.achievements_completed
			FROM ' . $this->achievement_table . ' a
			LEFT JOIN ' . $this->achievement_track_table . ' at
				ON at.achievement_id = a.id AND at.guild_id = ' . $guild_id . '
			WHERE ' . $db->sql_in_set('a.category_id', $category_ids) . '
				AND a.game_id = \'wow\'
			ORDER BY a.title';
		$result = $db->sql_query($sql);

		$achievements = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$completed = (int) $row['achievements_completed'];
			$completed_date = '';
			if ($completed > 0)
			{
				if ($completed > 9999999999)
				{
					$completed = (int) ($completed / 1000);
				}
				$completed_date = date('d/m/Y', $completed);
			}

			$achievements[] = array(
				'id'             => (int) $row['id'],
				'title'          => $row['title'],
				'description'    => $row['description'],
				'points'         => (int) $row['points'],
				'icon'           => $row['icon'],
				'completed'      => $completed > 0,
				'completed_date' => $completed_date,
			);
		}
		$db->sql_freeresult($result);

		return new JsonResponse(array(
			'category_name' => $category_name,
			'achievements'  => $achievements,
		));
	}

	/**
	 * Return a single achievement with criteria progress.
	 *
	 * @param int $guild_id
	 * @param int $achievement_id
	 * @return JsonResponse
	 */
	public function achievement_detail($guild_id, $achievement_id)
	{
		$guild_id = (int) $guild_id;
		$achievement_id = (int) $achievement_id;
		$db = $this->db;

		// Get achievement base data + track
		$sql = 'SELECT a.id, a.title, a.description, a.points, a.icon, a.reward,
				at.achievements_completed
			FROM ' . $this->achievement_table . ' a
			LEFT JOIN ' . $this->achievement_track_table . ' at
				ON at.achievement_id = a.id AND at.guild_id = ' . $guild_id . '
			WHERE a.id = ' . $achievement_id . '
				AND a.game_id = \'wow\'';
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$row)
		{
			return new JsonResponse(array('error' => 'Achievement not found'), 404);
		}

		$completed = (int) $row['achievements_completed'];
		$completed_date = '';
		if ($completed > 0)
		{
			if ($completed > 9999999999)
			{
				$completed = (int) ($completed / 1000);
			}
			$completed_date = date('d/m/Y', $completed);
		}

		// Get criteria via relations table
		$sql = 'SELECT c.criteria_id, c.description, c.max,
				ct.criteria_quantity
			FROM ' . $this->relations_table . ' r
			INNER JOIN ' . $this->achievement_criteria_table . ' c
				ON c.criteria_id = r.rel_value
			LEFT JOIN ' . $this->criteria_track_table . ' ct
				ON ct.criteria_id = c.criteria_id AND ct.guild_id = ' . $guild_id . '
			WHERE r.attribute_id = \'ACH\' AND r.rel_attr_id = \'CRI\'
				AND r.att_value = ' . $achievement_id . '
			ORDER BY c.orderindex';
		$result = $db->sql_query($sql);

		$criteria = array();
		while ($crow = $db->sql_fetchrow($result))
		{
			$criteria[] = array(
				'description' => $crow['description'],
				'max'         => (int) $crow['max'],
				'quantity'    => (int) $crow['criteria_quantity'],
			);
		}
		$db->sql_freeresult($result);

		return new JsonResponse(array(
			'id'             => (int) $row['id'],
			'title'          => $row['title'],
			'description'    => $row['description'],
			'points'         => (int) $row['points'],
			'icon'           => $row['icon'],
			'reward'         => $row['reward'],
			'completed'      => $completed > 0,
			'completed_date' => $completed_date,
			'criteria'       => $criteria,
		));
	}
}
