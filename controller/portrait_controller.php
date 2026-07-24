<?php
/**
 * Portrait sync AJAX controller
 *
 * Processes a batch of character portrait fetches per request.
 * Designed to be called repeatedly by JS until all portraits are synced.
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\controller;

use avathar\bbguildwow\game\wow_api;
use avathar\bbguild\model\admin\log;
use phpbb\auth\auth;
use phpbb\db\driver\driver_interface;
use Symfony\Component\HttpFoundation\JsonResponse;

class portrait_controller
{
	/** @var wow_api */
	protected $wow_api;

	/** @var driver_interface */
	protected $db;

	/** @var log */
	protected $bbguildlog;

	/** @var auth */
	protected $auth;

	/** @var string */
	protected $players_table;

	/** @var string */
	protected $guild_table;

	/** @var string */
	protected $games_table;

	public function __construct(
		wow_api $wow_api,
		driver_interface $db,
		log $bbguildlog,
		auth $auth,
		string $players_table,
		string $guild_table,
		string $games_table
	)
	{
		$this->wow_api = $wow_api;
		$this->db = $db;
		$this->bbguildlog = $bbguildlog;
		$this->auth = $auth;
		$this->players_table = $players_table;
		$this->guild_table = $guild_table;
		$this->games_table = $games_table;
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
	 * Sync guild roster from Battle.net API and return result as JSON.
	 *
	 * @param int $guild_id
	 * @return JsonResponse
	 */
	public function sync_roster($guild_id)
	{
		if ($auth_error = $this->check_auth())
		{
			return $auth_error;
		}

		try
		{
			return $this->do_sync_roster((int) $guild_id);
		}
		catch (\Exception $e)
		{
			return new JsonResponse(array('success' => false, 'message' => $e->getMessage()), 500);
		}
	}

	/**
	 * @param int $guild_id
	 * @return JsonResponse
	 */
	private function do_sync_roster(int $guild_id): JsonResponse
	{
		// Get guild data
		$sql = 'SELECT name, realm, region, min_armory, game_id, game_edition FROM ' . $this->guild_table .
			' WHERE id = ' . $guild_id;
		$result = $this->db->sql_query($sql);
		$guild_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$guild_row || $guild_row['game_id'] !== 'wow')
		{
			$this->bbguildlog->log_insert(array(
				'log_type'   => 'L_ERROR_ROSTER_SYNCED',
				'log_result' => 'L_ERROR',
				'log_action' => ['(unknown)', 'Guild not found or not a WoW guild'],
			));
			return new JsonResponse(array('success' => false, 'message' => 'Guild not found or not a WoW guild.'));
		}

		// Get game API credentials
		$sql = 'SELECT apikey, privkey, apilocale, region FROM ' . $this->games_table .
			" WHERE game_id = 'wow'";
		$result = $this->db->sql_query($sql);
		$game_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$game_row || empty($game_row['apikey']))
		{
			$this->bbguildlog->log_insert(array(
				'log_type'   => 'L_ERROR_ROSTER_SYNCED',
				'log_result' => 'L_ERROR',
				'log_action' => [$guild_row['name'], 'API credentials not configured'],
			));
			return new JsonResponse(array('success' => false, 'message' => 'API credentials not configured.'));
		}

		$region = !empty($guild_row['region']) ? $guild_row['region'] : $game_row['region'];
		$edition = !empty($guild_row['game_edition']) ? $guild_row['game_edition'] : 'retail';

		// Fetch guild data + roster
		$params = array('members');
		$params['edition'] = $edition;
		$data = $this->wow_api->fetch_guild_data(
			$guild_row['name'],
			$guild_row['realm'],
			$region,
			$params
		);

		if (!is_array($data) || isset($data['code']))
		{
			$detail = isset($data['code']) ? sprintf('API error %d', $data['code']) : 'Empty response';
			// Capture the exact Battle.net request URL (region host, namespace and
			// guild slug) so a 404/region/slug mismatch is self-diagnosing.
			$request_url = (is_array($data) && isset($data['_request_url'])) ? (string) $data['_request_url'] : '';
			$log_detail = $guild_row['name'] . '-' . $guild_row['realm'] . ': ' . $detail;
			if ($request_url !== '')
			{
				$log_detail .= ' — ' . $request_url;
			}
			$this->bbguildlog->log_insert(array(
				'log_type'   => 'L_ERROR_ARMORY_DOWN',
				'log_result' => 'L_ERROR',
				'log_action' => [$log_detail],
			));
			return new JsonResponse(array('success' => false, 'message' => $request_url !== '' ? $detail . ' (URL: ' . $request_url . ')' : $detail));
		}

		// Process and save guild data
		$processed = $this->wow_api->process_guild_data($data, array('members'));
		$this->wow_api->save_guild_extension($guild_id, $processed);

		// Update core guild fields
		$update = array(
			'armoryresult' => 'OK',
			'faction'      => isset($processed['faction']) ? $processed['faction'] : 2,
		);
		if (isset($processed['emblempath']))
		{
			$update['emblemurl'] = $processed['emblempath'];
		}
		if (isset($processed['playercount']))
		{
			$update['players'] = $processed['playercount'];
		}
		$this->db->sql_query('UPDATE ' . $this->guild_table .
			' SET ' . $this->db->sql_build_array('UPDATE', $update) .
			' WHERE id = ' . $guild_id);

		// Sync members
		$member_count = 0;
		if (isset($data['members']))
		{
			$min_level = isset($guild_row['min_armory']) ? (int) $guild_row['min_armory'] : 0;
			$this->wow_api->sync_guild_members($data['members'], $guild_id, $region, $min_level);
			$member_count = count($data['members']);
		}

		$this->bbguildlog->log_insert(array(
			'log_type'   => 'L_ACTION_ROSTER_SYNCED',
			'log_action' => [$guild_row['name'], sprintf('%d members', $member_count)],
		));

		return new JsonResponse(array(
			'success'      => true,
			'message'      => sprintf('Roster synced: %d members.', $member_count),
			'member_count' => $member_count,
		));
	}

	/**
	 * Process a batch of specialization syncs and return progress as JSON.
	 *
	 * @param int $guild_id
	 * @return JsonResponse
	 */
	public function sync_specs($guild_id)
	{
		if ($auth_error = $this->check_auth())
		{
			return $auth_error;
		}

		try
		{
			return $this->do_sync_specs((int) $guild_id);
		}
		catch (\Exception $e)
		{
			return new JsonResponse(array('error' => $e->getMessage(), 'done' => true), 500);
		}
	}

	/**
	 * @param int $guild_id
	 * @return JsonResponse
	 */
	private function do_sync_specs(int $guild_id): JsonResponse
	{
		$sql = 'SELECT apikey, privkey, apilocale, region FROM ' . $this->games_table .
			" WHERE game_id = 'wow'";
		$result = $this->db->sql_query($sql);
		$game_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$game_row || empty($game_row['apikey']))
		{
			$this->bbguildlog->log_insert(array(
				'log_type'   => 'L_ERROR_SPECS_SYNCED',
				'log_result' => 'L_ERROR',
				'log_action' => ['(unknown)', 'API credentials not configured'],
			));
			return new JsonResponse(array('error' => 'API credentials not configured', 'done' => true), 400);
		}

		$sql = 'SELECT name, region, game_edition FROM ' . $this->guild_table . ' WHERE id = ' . $guild_id;
		$result = $this->db->sql_query($sql);
		$guild_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$guild_name = $guild_row ? $guild_row['name'] : '(unknown)';
		$region = (!empty($guild_row['region'])) ? $guild_row['region'] : $game_row['region'];
		$edition = (!empty($guild_row['game_edition'])) ? $guild_row['game_edition'] : 'retail';

		// Count total and remaining
		$sql = 'SELECT COUNT(*) AS total FROM ' . $this->players_table .
			' WHERE player_guild_id = ' . $guild_id .
			" AND game_id = 'wow' AND player_status = 1";
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		$sql = 'SELECT COUNT(*) AS remaining FROM ' . $this->players_table .
			' WHERE player_guild_id = ' . $guild_id .
			" AND game_id = 'wow' AND player_status = 1" .
			" AND (player_spec = '' OR player_spec IS NULL)";
		$result = $this->db->sql_query($sql);
		$remaining_before = (int) $this->db->sql_fetchfield('remaining');
		$this->db->sql_freeresult($result);

		if ($remaining_before === 0)
		{
			return new JsonResponse(array(
				'done' => true, 'fetched' => 0, 'total' => $total, 'remaining' => 0,
				'message' => 'All specs are up to date.',
			));
		}

		$sync_result = $this->wow_api->sync_specs(
			$guild_id, $region,
			$game_row['apikey'], $game_row['apilocale'], $game_row['privkey'], $edition
		);

		$sql = 'SELECT COUNT(*) AS remaining FROM ' . $this->players_table .
			' WHERE player_guild_id = ' . $guild_id .
			" AND game_id = 'wow' AND player_status = 1" .
			" AND (player_spec = '' OR player_spec IS NULL)";
		$result = $this->db->sql_query($sql);
		$remaining_after = (int) $this->db->sql_fetchfield('remaining');
		$this->db->sql_freeresult($result);

		$has_server_errors = $this->has_server_errors($sync_result);
		$is_done = $remaining_after === 0 || $has_server_errors;

		// Only log on final batch or server errors to avoid flooding
		if ($is_done || $has_server_errors)
		{
			$this->bbguildlog->log_insert(array(
				'log_type'   => ($sync_result['count'] > 0 && !$has_server_errors) ? 'L_ACTION_SPECS_SYNCED' : 'L_ERROR_SPECS_SYNCED',
				'log_result' => ($sync_result['count'] > 0 && !$has_server_errors) ? 'L_SUCCESS' : 'L_ERROR',
				'log_action' => [$guild_name, $sync_result['message']],
			));

			if ($has_server_errors)
			{
				$this->bbguildlog->log_insert(array(
					'log_type'   => 'L_ERROR_ARMORY_DOWN',
					'log_result' => 'L_ERROR',
					'log_action' => [$guild_name . ': specs sync interrupted by server error'],
				));
			}
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
	 * Process a batch of portrait syncs and return progress as JSON.
	 *
	 * @param int $guild_id
	 * @return JsonResponse
	 */
	public function sync($guild_id)
	{
		if ($auth_error = $this->check_auth())
		{
			return $auth_error;
		}

		try
		{
			return $this->do_sync_portraits((int) $guild_id);
		}
		catch (\Exception $e)
		{
			return new JsonResponse(array('error' => $e->getMessage(), 'done' => true), 500);
		}
	}

	/**
	 * @param int $guild_id
	 * @return JsonResponse
	 */
	private function do_sync_portraits(int $guild_id): JsonResponse
	{
		// Get game API credentials
		$sql = 'SELECT apikey, privkey, apilocale, region FROM ' . $this->games_table .
			" WHERE game_id = 'wow'";
		$result = $this->db->sql_query($sql);
		$game_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$game_row || empty($game_row['apikey']))
		{
			$this->bbguildlog->log_insert(array(
				'log_type'   => 'L_ERROR_PORTRAITS_SYNCED',
				'log_result' => 'L_ERROR',
				'log_action' => ['(unknown)', 'API credentials not configured'],
			));
			return new JsonResponse(array(
				'error' => 'API credentials not configured',
				'done' => true,
			), 400);
		}

		// Get guild name, region, and edition (fall back to game region)
		$sql = 'SELECT name, region, game_edition FROM ' . $this->guild_table .
			' WHERE id = ' . $guild_id;
		$result = $this->db->sql_query($sql);
		$guild_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$guild_name = $guild_row ? $guild_row['name'] : '(unknown)';
		$region = (!empty($guild_row['region'])) ? $guild_row['region'] : $game_row['region'];
		$edition = (!empty($guild_row['game_edition'])) ? $guild_row['game_edition'] : 'retail';

		// Count total and remaining
		$sql = 'SELECT COUNT(*) AS total FROM ' . $this->players_table .
			' WHERE player_guild_id = ' . $guild_id .
			" AND game_id = 'wow' AND player_status = 1";
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		$sql = 'SELECT COUNT(*) AS remaining FROM ' . $this->players_table .
			' WHERE player_guild_id = ' . $guild_id .
			" AND game_id = 'wow' AND player_status = 1" .
			" AND (player_portrait_url = '' OR player_portrait_url IS NULL OR player_portrait_url LIKE 'http%')";
		$result = $this->db->sql_query($sql);
		$remaining_before = (int) $this->db->sql_fetchfield('remaining');
		$this->db->sql_freeresult($result);

		if ($remaining_before === 0)
		{
			return new JsonResponse(array(
				'done'      => true,
				'fetched'   => 0,
				'failed'    => 0,
				'total'     => $total,
				'remaining' => 0,
				'message'   => 'All portraits are up to date.',
			));
		}

		// Run a batch
		$sync_result = $this->wow_api->sync_portraits(
			$guild_id,
			$region,
			$game_row['apikey'],
			$game_row['apilocale'],
			$game_row['privkey'],
			$edition
		);

		// Recount remaining
		$sql = 'SELECT COUNT(*) AS remaining FROM ' . $this->players_table .
			' WHERE player_guild_id = ' . $guild_id .
			" AND game_id = 'wow' AND player_status = 1" .
			" AND (player_portrait_url = '' OR player_portrait_url IS NULL OR player_portrait_url LIKE 'http%')";
		$result = $this->db->sql_query($sql);
		$remaining_after = (int) $this->db->sql_fetchfield('remaining');
		$this->db->sql_freeresult($result);

		$has_server_errors = $this->has_server_errors($sync_result);
		$is_done = $remaining_after === 0 || $has_server_errors;

		// Only log on final batch or server errors to avoid flooding
		if ($is_done || $has_server_errors)
		{
			$this->bbguildlog->log_insert(array(
				'log_type'   => ($sync_result['count'] > 0 && !$has_server_errors) ? 'L_ACTION_PORTRAITS_SYNCED' : 'L_ERROR_PORTRAITS_SYNCED',
				'log_result' => ($sync_result['count'] > 0 && !$has_server_errors) ? 'L_SUCCESS' : 'L_ERROR',
				'log_action' => [$guild_name, $sync_result['message']],
			));

			if ($has_server_errors)
			{
				$this->bbguildlog->log_insert(array(
					'log_type'   => 'L_ERROR_ARMORY_DOWN',
					'log_result' => 'L_ERROR',
					'log_action' => [$guild_name . ': portrait sync interrupted by server error'],
				));
			}
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
	 * Process a batch of equipment syncs and return progress as JSON.
	 *
	 * @param int $guild_id
	 * @return JsonResponse
	 */
	public function sync_equipment($guild_id)
	{
		if ($auth_error = $this->check_auth())
		{
			return $auth_error;
		}

		try
		{
			return $this->do_sync_equipment((int) $guild_id);
		}
		catch (\Exception $e)
		{
			return new JsonResponse(array('error' => $e->getMessage(), 'done' => true), 500);
		}
	}

	/**
	 * @param int $guild_id
	 * @return JsonResponse
	 */
	private function do_sync_equipment(int $guild_id): JsonResponse
	{
		global $phpbb_container;

		$sql = 'SELECT apikey, privkey, apilocale, region FROM ' . $this->games_table .
			" WHERE game_id = 'wow'";
		$result = $this->db->sql_query($sql);
		$game_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$game_row || empty($game_row['apikey']))
		{
			return new JsonResponse(array('error' => 'API credentials not configured', 'done' => true), 400);
		}

		$sql = 'SELECT name, region, game_edition FROM ' . $this->guild_table . ' WHERE id = ' . $guild_id;
		$result = $this->db->sql_query($sql);
		$guild_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$guild_name = $guild_row ? $guild_row['name'] : '(unknown)';
		$region = (!empty($guild_row['region'])) ? $guild_row['region'] : $game_row['region'];
		$edition = (!empty($guild_row['game_edition'])) ? $guild_row['game_edition'] : 'retail';

		$equipment_table = $phpbb_container->getParameter('avathar.bbguildwow.tables.bb_player_equipment');
		$stale_threshold = time() - 86400;

		// Count total and remaining
		$sql = 'SELECT COUNT(*) AS total FROM ' . $this->players_table .
			' WHERE player_guild_id = ' . $guild_id .
			" AND game_id = 'wow' AND player_status = 1";
		$result = $this->db->sql_query($sql);
		$total = (int) $this->db->sql_fetchfield('total');
		$this->db->sql_freeresult($result);

		$sql = 'SELECT COUNT(DISTINCT p.player_id) AS remaining FROM ' . $this->players_table . ' p
			LEFT JOIN ' . $equipment_table . ' e
				ON e.player_id = p.player_id AND e.slot_type = \'HEAD\'
			WHERE p.player_guild_id = ' . $guild_id . '
				AND p.game_id = \'wow\' AND p.player_status = 1
				AND (e.player_id IS NULL OR e.last_update < ' . $stale_threshold . ')';
		$result = $this->db->sql_query($sql);
		$remaining_before = (int) $this->db->sql_fetchfield('remaining');
		$this->db->sql_freeresult($result);

		if ($remaining_before === 0)
		{
			return new JsonResponse(array(
				'done' => true, 'fetched' => 0, 'total' => $total, 'remaining' => 0,
				'message' => 'All equipment is up to date.',
			));
		}

		$sync_result = $this->wow_api->sync_equipment(
			$guild_id, $region,
			$game_row['apikey'], $game_row['apilocale'], $game_row['privkey'], $edition
		);

		// Recount remaining
		$sql = 'SELECT COUNT(DISTINCT p.player_id) AS remaining FROM ' . $this->players_table . ' p
			LEFT JOIN ' . $equipment_table . ' e
				ON e.player_id = p.player_id AND e.slot_type = \'HEAD\'
			WHERE p.player_guild_id = ' . $guild_id . '
				AND p.game_id = \'wow\' AND p.player_status = 1
				AND (e.player_id IS NULL OR e.last_update < ' . $stale_threshold . ')';
		$result = $this->db->sql_query($sql);
		$remaining_after = (int) $this->db->sql_fetchfield('remaining');
		$this->db->sql_freeresult($result);

		$has_server_errors = $this->has_server_errors($sync_result);
		$is_done = $remaining_after === 0 || $has_server_errors;

		if ($is_done)
		{
			$this->bbguildlog->log_insert(array(
				'log_type'   => ($sync_result['count'] > 0 && !$has_server_errors) ? 'L_ACTION_EQUIPMENT_SYNCED' : 'L_ERROR_EQUIPMENT_SYNCED',
				'log_result' => ($sync_result['count'] > 0 && !$has_server_errors) ? 'L_SUCCESS' : 'L_ERROR',
				'log_action' => [$guild_name, $sync_result['message']],
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
	 * Check if a sync result contains server-side (5xx) errors.
	 *
	 * @param array $sync_result Result from wow_api sync methods
	 * @return bool
	 */
	private function has_server_errors(array $sync_result): bool
	{
		if (empty($sync_result['errors']))
		{
			return false;
		}
		foreach (array_keys($sync_result['errors']) as $code)
		{
			if (is_int($code) && $code >= 500)
			{
				return true;
			}
		}
		return false;
	}
}
