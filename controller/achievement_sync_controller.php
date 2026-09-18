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
use avathar\bbguildwow\game\wow_api;
use avathar\bbguild\model\admin\log;
use avathar\bbguild\model\games\game;
use avathar\bbguild\model\player\guilds;
use phpbb\auth\auth;
use phpbb\db\driver\driver_interface;
use phpbb\language\language;
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

	/** @var language */
	protected $language;

	/** @var string */
	protected $guild_table;

	/** @var string */
	protected $games_table;

	/** @var string */
	protected $achievement_table;

	/** @var wow_api */
	protected $wow_api;

	/** @var string */
	protected $players_table;

	/** @var string */
	protected $achievement_track_table;

	public function __construct(
		achievement $achievement,
		driver_interface $db,
		log $bbguildlog,
		auth $auth,
		language $language,
		string $guild_table,
		string $games_table,
		string $achievement_table,
		wow_api $wow_api,
		string $players_table,
		string $achievement_track_table
	)
	{
		$this->achievement = $achievement;
		$this->db = $db;
		$this->bbguildlog = $bbguildlog;
		$this->auth = $auth;
		$this->language = $language;
		$this->guild_table = $guild_table;
		$this->games_table = $games_table;
		$this->achievement_table = $achievement_table;
		$this->wow_api = $wow_api;
		$this->players_table = $players_table;
		$this->achievement_track_table = $achievement_track_table;
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
		$this->language->add_lang('wow', 'avathar/bbguildwow');

		if (!$this->auth->acl_get('a_bbguild'))
		{
			return new JsonResponse(array('error' => $this->language->lang('WOW_SYNC_INSUFFICIENT_PERMISSIONS'), 'done' => true), 403);
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
				'log_action' => [$guild->getName(), $this->language->lang('WOW_SYNC_LOG_CATEGORIES', $sync_result['message'])],
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

		// Count achievements still needing details. icon = '' alone, not
		// "AND points = 0" -- points comes back set on the very first
		// successful detail fetch regardless of whether icon extraction
		// worked, so pairing it with points=0 would report $is_done as
		// soon as every row has SOME detail, cutting the JS polling loop
		// short with thousands of rows still missing just their icon.
		$sql = 'SELECT COUNT(*) AS remaining FROM ' . $this->achievement_table .
			" WHERE game_id = 'wow' AND icon = ''";
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
				'log_action' => [$guild->getName(), $this->language->lang('WOW_SYNC_LOG_ACHIEVEMENTS', $sync_result['message'])],
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
	 * Sync per-character achievements for this guild's roster
	 * (bbguildwow#44's sync side). Time-budgeted per request like
	 * wow_api::sync_specs()/sync_equipment() -- called repeatedly by JS
	 * until 'remaining' reaches 0, since a large guild's characters won't
	 * all fit in one request's time budget.
	 *
	 * Backfill semantics, not ongoing freshness: a character already
	 * counted here (has at least one bb_achievement_track row) won't be
	 * re-picked-up by a later click of this same action to catch newly-
	 * earned achievements -- see wow_api::sync_achievements()'s docblock.
	 *
	 * @param int $guild_id
	 * @return JsonResponse
	 */
	public function sync_player_achievements($guild_id)
	{
		if ($auth_error = $this->check_auth())
		{
			return $auth_error;
		}

		$guild_id = (int) $guild_id;

		$sql = 'SELECT apikey, privkey, apilocale, region FROM ' . $this->games_table .
			" WHERE game_id = 'wow'";
		$result = $this->db->sql_query($sql);
		$game_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$game_row || empty($game_row['apikey']))
		{
			return new JsonResponse(array('error' => $this->language->lang('WOW_SYNC_CREDENTIALS_MISSING'), 'done' => true), 400);
		}

		$sql = 'SELECT name, region, game_edition FROM ' . $this->guild_table . ' WHERE id = ' . $guild_id;
		$result = $this->db->sql_query($sql);
		$guild_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$guild_name = $guild_row ? $guild_row['name'] : '(unknown)';
		$region = (!empty($guild_row['region'])) ? $guild_row['region'] : $game_row['region'];
		$edition = (!empty($guild_row['game_edition'])) ? $guild_row['game_edition'] : 'retail';

		$sql = 'SELECT COUNT(*) AS total FROM ' . $this->players_table .
			' WHERE player_guild_id = ' . $guild_id .
			" AND game_id = 'wow' AND player_status = 1";
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		$sql = 'SELECT COUNT(*) AS remaining FROM ' . $this->players_table . ' p
			WHERE p.player_guild_id = ' . $guild_id . "
				AND p.game_id = 'wow' AND p.player_status = 1
				AND NOT EXISTS (
					SELECT 1 FROM " . $this->achievement_track_table . ' ac WHERE ac.player_id = p.player_id
				)';
		$result = $this->db->sql_query($sql);
		$remaining_before = (int) $this->db->sql_fetchfield('remaining');
		$this->db->sql_freeresult($result);

		if ($remaining_before === 0)
		{
			return new JsonResponse(array(
				'done' => true, 'fetched' => 0, 'total' => $total, 'remaining' => 0,
				'message' => $this->language->lang('WOW_API_ACHIEVEMENTS_UP_TO_DATE'),
			));
		}

		$sync_result = $this->wow_api->sync_achievements(
			$guild_id, $region,
			$game_row['apikey'], $game_row['apilocale'], $game_row['privkey'], $edition
		);

		$result = $this->db->sql_query($sql); // same NOT EXISTS query, re-run after the batch
		$remaining_after = (int) $this->db->sql_fetchfield('remaining');
		$this->db->sql_freeresult($result);

		$is_done = $remaining_after === 0;

		if ($is_done)
		{
			$this->bbguildlog->log_insert(array(
				'log_type'   => 'L_ACTION_SPECS_SYNCED',
				'log_result' => 'L_SUCCESS',
				'log_action' => [$guild_name, $this->language->lang('WOW_SYNC_LOG_ACHIEVEMENTS', $sync_result['message'])],
			));
		}

		return new JsonResponse(array(
			'done'      => $is_done,
			'fetched'   => $sync_result['count'],
			'total'     => $total,
			'remaining' => $remaining_after,
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
			return new JsonResponse(array('error' => $this->language->lang('WOW_SYNC_GUILD_NOT_WOW'), 'done' => true), 400);
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
			return new JsonResponse(array('error' => $this->language->lang('WOW_SYNC_GAME_LOAD_FAILED', $e->getMessage()), 'done' => true), 500);
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
			return new JsonResponse(array('error' => $this->language->lang('WOW_SYNC_GUILD_LOAD_FAILED', $e->getMessage()), 'done' => true), 500);
		}
	}
}
