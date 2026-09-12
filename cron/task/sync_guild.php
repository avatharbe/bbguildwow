<?php
/**
 * bbGuild WoW Battle.net API — scheduled guild roster sync
 *
 * @package   bbguildwow v2.0
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\cron\task;

use avathar\bbguild\model\admin\log;
use avathar\bbguildwow\game\wow_api;

/**
 * Periodically syncs the roster (bb_players) of every WoW guild from the
 * Battle.net API. Character profile refresh (ilvl/spec) and the guild
 * activity feed are intentionally out of scope here: profile refresh is
 * owned by bbguild core's character_sync cron via the WoW
 * character_sync_interface handler (avoids double-syncing on two
 * schedules), and activity feed sync has no fetch path yet (bbguildwow#10).
 */
class sync_guild extends \phpbb\cron\task\base
{
	/** Fallback sync interval in seconds (6 hours) when unset or invalid. */
	const DEFAULT_INTERVAL = 21600;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var log */
	protected $bbguild_log;

	/** @var wow_api */
	protected $wow_api;

	/** @var string */
	protected $guild_table;

	/** @var string */
	protected $games_table;

	public function __construct(
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		log $bbguild_log,
		wow_api $wow_api,
		string $guild_table,
		string $games_table
	)
	{
		$this->config = $config;
		$this->db = $db;
		$this->bbguild_log = $bbguild_log;
		$this->wow_api = $wow_api;
		$this->guild_table = $guild_table;
		$this->games_table = $games_table;
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_runnable()
	{
		return !empty($this->config['bbguild_wow_sync_enabled']);
	}

	/**
	 * {@inheritdoc}
	 */
	public function should_run()
	{
		$interval = (int) $this->config['bbguild_wow_sync_interval'];
		if ($interval <= 0)
		{
			$interval = self::DEFAULT_INTERVAL;
		}

		return (time() - (int) $this->config['bbguild_wow_last_sync']) > $interval;
	}

	/**
	 * {@inheritdoc}
	 */
	public function run()
	{
		$game_row = $this->get_game_row();

		if (!$game_row || empty($game_row['apikey']) || empty($game_row['privkey']))
		{
			$this->bbguild_log->log_insert(array(
				'log_type'   => 'L_ERROR_ROSTER_SYNCED',
				'log_result' => 'L_ERROR',
				'log_action' => array('(unknown)', 'Battle.net credentials not configured.'),
			));
			$this->finish_run('Battle.net credentials not configured.');
			return;
		}

		$synced = 0;
		$failed = 0;
		foreach ($this->get_wow_guilds() as $guild_row)
		{
			if ($this->sync_guild_roster($guild_row, $game_row))
			{
				$synced++;
			}
			else
			{
				$failed++;
			}
		}

		$summary = $synced . ' guild(s) synced';
		if ($failed > 0)
		{
			$summary .= ', ' . $failed . ' failed';
		}
		$this->finish_run($summary);
	}

	/**
	 * @param string $result_summary
	 */
	private function finish_run(string $result_summary): void
	{
		$this->config->set('bbguild_wow_last_sync', time());
		$this->config->set('bbguild_wow_last_sync_result', $result_summary);
	}

	/**
	 * @return array|null
	 */
	private function get_game_row(): ?array
	{
		$sql = 'SELECT apikey, privkey, apilocale, region FROM ' . $this->games_table . " WHERE game_id = 'wow'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return $row ?: null;
	}

	/**
	 * @return array[]
	 */
	private function get_wow_guilds(): array
	{
		$sql = 'SELECT id, name, realm, region, min_armory, game_edition FROM ' . $this->guild_table . " WHERE game_id = 'wow'";
		$result = $this->db->sql_query($sql);

		$guilds = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$guilds[] = $row;
		}
		$this->db->sql_freeresult($result);

		return $guilds;
	}

	/**
	 * Fetch + sync roster for a single guild. Failures (API errors or
	 * exceptions) are logged and swallowed here so one bad guild can't
	 * abort the rest of the cron run.
	 *
	 * @param array $guild_row
	 * @param array $game_row
	 * @return bool True if the guild's roster synced without error.
	 */
	private function sync_guild_roster(array $guild_row, array $game_row): bool
	{
		$guild_id = (int) $guild_row['id'];
		$region = !empty($guild_row['region']) ? $guild_row['region'] : $game_row['region'];
		$edition = !empty($guild_row['game_edition']) ? $guild_row['game_edition'] : 'retail';

		try
		{
			$params = array('members');
			$params['edition'] = $edition;
			$data = $this->wow_api->fetch_guild_data($guild_row['name'], $guild_row['realm'], $region, $params);

			if (!is_array($data) || isset($data['code']))
			{
				$detail = isset($data['code']) ? 'API error ' . $data['code'] : 'Empty response from Battle.net.';
				$this->bbguild_log->log_insert(array(
					'log_type'   => 'L_ERROR_ARMORY_DOWN',
					'log_result' => 'L_ERROR',
					'log_action' => array($guild_row['name'] . '-' . $guild_row['realm'] . ': ' . $detail),
				));
				return false;
			}

			$processed = $this->wow_api->process_guild_data($data, array('members'));
			$this->wow_api->save_guild_extension($guild_id, $processed);

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

			$member_count = 0;
			if (isset($data['members']))
			{
				$min_level = isset($guild_row['min_armory']) ? (int) $guild_row['min_armory'] : 0;
				$this->wow_api->sync_guild_members($data['members'], $guild_id, $region, $min_level);
				$member_count = count($data['members']);
			}

			$this->bbguild_log->log_insert(array(
				'log_type'   => 'L_ACTION_ROSTER_SYNCED',
				'log_action' => array($guild_row['name'], $member_count . ' members synced.'),
			));

			return true;
		}
		catch (\Throwable $e)
		{
			$this->bbguild_log->log_insert(array(
				'log_type'   => 'L_ERROR_ROSTER_SYNCED',
				'log_result' => 'L_ERROR',
				'log_action' => array($guild_row['name'], $e->getMessage()),
			));

			return false;
		}
	}
}
