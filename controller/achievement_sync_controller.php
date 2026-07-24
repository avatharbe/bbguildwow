<?php
/**
 * Achievement sync AJAX controller
 *
 * Provides AJAX endpoints for syncing achievement categories and
 * guild achievements from the Battle.net API. Called repeatedly
 * by JS until all data is fetched (batch pattern).
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\controller;

use avathar\bbguildwow\model\achievement;
use avathar\bbguild\model\admin\log;
use avathar\bbguild\model\games\game;
use avathar\bbguild\model\player\guilds;
use phpbb\auth\auth;
use phpbb\db\driver\driver_interface;
use Symfony\Component\HttpFoundation\JsonResponse;

class achievement_sync_controller
{
	/** @var achievement */
	protected $achievement;

	/** @var driver_interface */
	protected $db;

	/** @var log */
	protected $bbguildlog;

	/** @var auth */
	protected $auth;

	/** @var string */
	protected $guild_table;

	/** @var string */
	protected $games_table;

	/** @var string */
	protected $achievement_table;

	public function __construct(
		achievement $achievement,
		driver_interface $db,
		log $bbguildlog,
		auth $auth,
		string $guild_table,
		string $games_table,
		string $achievement_table
	)
	{
		$this->achievement = $achievement;
		$this->db = $db;
		$this->bbguildlog = $bbguildlog;
		$this->auth = $auth;
		$this->guild_table = $guild_table;
		$this->games_table = $games_table;
		$this->achievement_table = $achievement_table;
	}

	/**
	 * Reject the request unless the current user holds the bbGuild ACP
	 * permission. These endpoints only ever get *linked* from the ACP
	 * Edit Guild page, but the route itself has no auth of its own, so
	 * every action must check explicitly instead of relying on the link
	 * being hidden.
	 *
	 * @return JsonResponse|null Null if authorized, an error response otherwise.
	 */
	private function check_auth(): ?JsonResponse
	{
		if (!$this->auth->acl_get('a_bbguild'))
		{
			return new JsonResponse(array('error' => 'Insufficient permissions.', 'done' => true), 403);
		}
		return null;
	}

	/**
	 * Sync achievement categories from Battle.net API.
	 *
	 * @param int $guild_id
	 * @return JsonResponse
	 */
	public function sync_categories($guild_id)
	{
		if ($auth_error = $this->check_auth())
		{
			return $auth_error;
		}

		$guild_id = (int) $guild_id;

		$game = $this->load_game($guild_id);
		if ($game instanceof JsonResponse)
		{
			return $game;
		}

		$guild = $this->load_guild($guild_id);
		if ($guild instanceof JsonResponse)
		{
			return $guild;
		}

		$this->achievement->setGame($game, 0);
		$this->achievement->setGuildId($guild_id);
		$this->achievement->setEdition($guild->getGameEdition());

		try
		{
			$sync_result = $this->achievement->syncCategories($game);
		}
		catch (\Exception $e)
		{
			return new JsonResponse(array('error' => $e->getMessage(), 'done' => true), 500);
		}

		// Count achievements still without a category
		$sql = 'SELECT COUNT(*) AS remaining FROM ' . $this->achievement_table .
			" WHERE game_id = 'wow' AND category_id = 0";
		$result = $this->db->sql_query($sql);
		$remaining = (int) $this->db->sql_fetchfield('remaining');
		$this->db->sql_freeresult($result);

		$sql = 'SELECT COUNT(*) AS total FROM ' . $this->achievement_table .
			" WHERE game_id = 'wow'";
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		$is_done = $remaining === 0;

		// Only log on final batch to avoid flooding
		if ($is_done || !$sync_result['success'])
		{
			$this->bbguildlog->log_insert(array(
				'log_type'   => $sync_result['success'] ? 'L_ACTION_SPECS_SYNCED' : 'L_ERROR_SPECS_SYNCED',
				'log_result' => $sync_result['success'] ? 'L_SUCCESS' : 'L_ERROR',
				'log_action' => [$guild->getName(), 'Categories: ' . $sync_result['message']],
			));
		}

		return new JsonResponse(array(
			'done'      => $is_done,
			'fetched'   => $sync_result['count'],
			'total'     => $total,
			'remaining' => $remaining,
			'message'   => $sync_result['message'],
		));
	}

	/**
	 * Sync guild achievements from Battle.net API.
	 *
	 * @param int $guild_id
	 * @return JsonResponse
	 */
	public function sync_achievements($guild_id)
	{
		if ($auth_error = $this->check_auth())
		{
			return $auth_error;
		}

		$guild_id = (int) $guild_id;

		$game = $this->load_game($guild_id);
		if ($game instanceof JsonResponse)
		{
			return $game;
		}

		$guild = $this->load_guild($guild_id);
		if ($guild instanceof JsonResponse)
		{
			return $guild;
		}

		$this->achievement->setGame($game, 0);
		$this->achievement->setGuildId($guild_id);
		$this->achievement->setEdition($guild->getGameEdition());

		try
		{
			$sync_result = $this->achievement->setAchievements($guild, $game);
		}
		catch (\Exception $e)
		{
			return new JsonResponse(array('error' => $e->getMessage(), 'done' => true), 500);
		}

		// Count achievements still needing details
		$sql = 'SELECT COUNT(*) AS remaining FROM ' . $this->achievement_table .
			" WHERE game_id = 'wow' AND icon = '' AND points = 0";
		$result = $this->db->sql_query($sql);
		$remaining = (int) $this->db->sql_fetchfield('remaining');
		$this->db->sql_freeresult($result);

		$sql = 'SELECT COUNT(*) AS total FROM ' . $this->achievement_table .
			" WHERE game_id = 'wow'";
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		$is_done = $remaining === 0;

		// Only log on final batch to avoid flooding
		if ($is_done || !$sync_result['success'])
		{
			$this->bbguildlog->log_insert(array(
				'log_type'   => $sync_result['success'] ? 'L_ACTION_SPECS_SYNCED' : 'L_ERROR_SPECS_SYNCED',
				'log_result' => $sync_result['success'] ? 'L_SUCCESS' : 'L_ERROR',
				'log_action' => [$guild->getName(), 'Achievements: ' . $sync_result['message']],
			));
		}

		return new JsonResponse(array(
			'done'      => $is_done,
			'fetched'   => $sync_result['count'],
			'total'     => $total,
			'remaining' => $remaining,
			'message'   => $sync_result['message'],
		));
	}

	/**
	 * Load the WoW game object with API credentials.
	 *
	 * @param int $guild_id
	 * @return game|JsonResponse
	 */
	private function load_game($guild_id)
	{
		global $phpbb_container;

		$sql = 'SELECT game_id FROM ' . $this->guild_table . ' WHERE id = ' . (int) $guild_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row || $row['game_id'] !== 'wow')
		{
			return new JsonResponse(array('error' => 'Guild not found or not a WoW guild', 'done' => true), 400);
		}

		try
		{
			$user = $phpbb_container->get('user');
			$user->add_lang_ext('avathar/bbguild', 'admin');

			$game = new game(
				$phpbb_container->get('dbal.conn'),
				$phpbb_container->get('cache.driver'),
				$phpbb_container->get('config'),
				$user,
				$phpbb_container->get('ext.manager'),
				$phpbb_container->getParameter('avathar.bbguild.tables.bb_classes'),
				$phpbb_container->getParameter('avathar.bbguild.tables.bb_races'),
				$phpbb_container->getParameter('avathar.bbguild.tables.bb_language'),
				$phpbb_container->getParameter('avathar.bbguild.tables.bb_factions'),
				$phpbb_container->getParameter('avathar.bbguild.tables.bb_games')
			);
			$game->game_id = 'wow';
			$game->get_game();
			return $game;
		}
		catch (\Exception $e)
		{
			return new JsonResponse(array('error' => 'Could not load game: ' . $e->getMessage(), 'done' => true), 500);
		}
	}

	/**
	 * Load the guild object.
	 *
	 * @param int $guild_id
	 * @return guilds|JsonResponse
	 */
	private function load_guild($guild_id)
	{
		global $phpbb_container;

		try
		{
			$user = $phpbb_container->get('user');
			$guild = new guilds(
				$phpbb_container->get('dbal.conn'),
				$user,
				$phpbb_container->get('config'),
				$phpbb_container->get('cache.driver'),
				$phpbb_container->get('avathar.bbguild.log'),
				$phpbb_container->getParameter('avathar.bbguild.tables.bb_players'),
				$phpbb_container->getParameter('avathar.bbguild.tables.bb_ranks'),
				$phpbb_container->getParameter('avathar.bbguild.tables.bb_classes'),
				$phpbb_container->getParameter('avathar.bbguild.tables.bb_races'),
				$phpbb_container->getParameter('avathar.bbguild.tables.bb_language'),
				$phpbb_container->getParameter('avathar.bbguild.tables.bb_guild'),
				$phpbb_container->getParameter('avathar.bbguild.tables.bb_factions'),
				(int) $guild_id
			);
			$guild->get_guild();
			return $guild;
		}
		catch (\Exception $e)
		{
			return new JsonResponse(array('error' => 'Could not load guild: ' . $e->getMessage(), 'done' => true), 500);
		}
	}
}
