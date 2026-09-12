<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguildwow\tests\cron;

use PHPUnit\Framework\TestCase;

class sync_guild_test extends TestCase
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $db;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $log;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $wow_api;

	protected function get_task(array $config_data = array())
	{
		$defaults = array(
			'bbguild_wow_sync_enabled'     => 0,
			'bbguild_wow_sync_interval'    => 21600,
			'bbguild_wow_last_sync'        => 0,
			'bbguild_wow_last_sync_result' => '',
		);

		$this->config = new \phpbb\config\config(array_merge($defaults, $config_data));
		$this->db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$this->log = $this->getMockBuilder(\avathar\bbguild\model\admin\log::class)
			->disableOriginalConstructor()
			->getMock();
		$this->wow_api = $this->getMockBuilder(\avathar\bbguildwow\game\wow_api::class)
			->disableOriginalConstructor()
			->getMock();

		$this->db->method('sql_query')->willReturn(true);
		$this->db->method('sql_freeresult')->willReturn(null);
		$this->db->method('sql_build_array')->willReturn("'dummy'");

		return new \avathar\bbguildwow\cron\task\sync_guild(
			$this->config,
			$this->db,
			$this->log,
			$this->wow_api,
			'phpbb_bb_guild',
			'phpbb_bb_games'
		);
	}

	private function make_game_row(array $overrides = array()): array
	{
		return array_merge(array(
			'apikey'    => 'client-id',
			'privkey'   => 'client-secret',
			'apilocale' => 'en_US',
			'region'    => 'us',
		), $overrides);
	}

	private function make_guild_row(array $overrides = array()): array
	{
		return array_merge(array(
			'id'           => 5,
			'name'         => 'Test Guild',
			'realm'        => 'Area 52',
			'region'       => '',
			'min_armory'   => 0,
			'game_edition' => '',
		), $overrides);
	}

	public function test_is_runnable_false_when_disabled()
	{
		$task = $this->get_task(array('bbguild_wow_sync_enabled' => 0));

		$this->assertFalse($task->is_runnable());
	}

	public function test_is_runnable_true_when_enabled()
	{
		$task = $this->get_task(array('bbguild_wow_sync_enabled' => 1));

		$this->assertTrue($task->is_runnable());
	}

	public function test_should_run_true_when_never_synced()
	{
		$task = $this->get_task(array('bbguild_wow_last_sync' => 0));

		$this->assertTrue($task->should_run());
	}

	public function test_should_run_true_after_interval_elapsed()
	{
		$task = $this->get_task(array(
			'bbguild_wow_sync_interval' => 3600,
			'bbguild_wow_last_sync'     => time() - 3601,
		));

		$this->assertTrue($task->should_run());
	}

	public function test_should_run_false_within_interval()
	{
		$task = $this->get_task(array(
			'bbguild_wow_sync_interval' => 3600,
			'bbguild_wow_last_sync'     => time() - 60,
		));

		$this->assertFalse($task->should_run());
	}

	public function test_should_run_uses_default_interval_when_zero()
	{
		$task = $this->get_task(array(
			'bbguild_wow_sync_interval' => 0,
			'bbguild_wow_last_sync'     => time() - 100,
		));

		// 100s ago is within the 21600s (6h) default, so no run yet.
		$this->assertFalse($task->should_run());
	}

	public function test_run_skips_and_logs_when_credentials_missing()
	{
		$task = $this->get_task();

		$this->db->method('sql_fetchrow')->willReturn($this->make_game_row(array('apikey' => '')));

		$this->wow_api->expects($this->never())->method('fetch_guild_data');

		$this->log->expects($this->once())
			->method('log_insert')
			->with($this->callback(function ($values) {
				return $values['log_type'] === 'L_ERROR_ROSTER_SYNCED' && $values['log_result'] === 'L_ERROR';
			}));

		$task->run();

		$this->assertGreaterThan(0, (int) $this->config['bbguild_wow_last_sync']);
	}

	public function test_run_syncs_single_guild_success()
	{
		$task = $this->get_task();

		$game_row = $this->make_game_row();
		$guild_row = $this->make_guild_row();

		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			$game_row,
			$guild_row,
			false
		);

		$member_data = array(array('character' => array('name' => 'Player1')));

		$this->wow_api->expects($this->once())
			->method('fetch_guild_data')
			->with('Test Guild', 'Area 52', 'us', $this->anything())
			->willReturn(array('members' => $member_data));

		$this->wow_api->expects($this->once())
			->method('process_guild_data')
			->willReturn(array('faction' => 2, 'playercount' => 1));

		$this->wow_api->expects($this->once())
			->method('save_guild_extension')
			->with(5, $this->anything());

		$this->wow_api->expects($this->once())
			->method('sync_guild_members')
			->with($member_data, 5, 'us', 0);

		$this->log->expects($this->once())
			->method('log_insert')
			->with($this->callback(function ($values) {
				return $values['log_type'] === 'L_ACTION_ROSTER_SYNCED';
			}));

		$task->run();

		$this->assertGreaterThan(0, (int) $this->config['bbguild_wow_last_sync']);
	}

	public function test_run_logs_error_and_continues_when_guild_api_errors()
	{
		$task = $this->get_task();

		$game_row = $this->make_game_row();
		$guild_row = $this->make_guild_row();

		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			$game_row,
			$guild_row,
			false
		);

		$this->wow_api->expects($this->once())
			->method('fetch_guild_data')
			->willReturn(array('code' => 404, 'detail' => 'Not found'));

		$this->wow_api->expects($this->never())->method('sync_guild_members');

		$this->log->expects($this->once())
			->method('log_insert')
			->with($this->callback(function ($values) {
				return $values['log_type'] === 'L_ERROR_ARMORY_DOWN';
			}));

		$task->run();

		$this->assertGreaterThan(0, (int) $this->config['bbguild_wow_last_sync']);
	}

	public function test_run_isolates_exception_per_guild_and_continues()
	{
		$task = $this->get_task();

		$game_row = $this->make_game_row();
		$guild_row_1 = $this->make_guild_row(array('id' => 5, 'name' => 'Guild One'));
		$guild_row_2 = $this->make_guild_row(array('id' => 6, 'name' => 'Guild Two'));

		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			$game_row,
			$guild_row_1,
			$guild_row_2,
			false
		);

		$member_data = array(array('character' => array('name' => 'Player1')));

		$fetch_call_count = 0;
		$this->wow_api->expects($this->exactly(2))
			->method('fetch_guild_data')
			->willReturnCallback(function () use (&$fetch_call_count, $member_data) {
				$fetch_call_count++;
				if ($fetch_call_count === 1)
				{
					throw new \avathar\bbguildwow\api\battlenet_api_exception('boom');
				}
				return array('members' => $member_data);
			});

		$this->wow_api->method('process_guild_data')->willReturn(array());

		$this->wow_api->expects($this->once())
			->method('sync_guild_members')
			->with($member_data, 6, 'us', 0);

		$log_types = array();
		$this->log->expects($this->exactly(2))
			->method('log_insert')
			->willReturnCallback(function ($values) use (&$log_types) {
				$log_types[] = $values['log_type'];
				return true;
			});

		$task->run();

		$this->assertContains('L_ERROR_ROSTER_SYNCED', $log_types);
		$this->assertContains('L_ACTION_ROSTER_SYNCED', $log_types);
	}

	public function test_run_records_last_sync_result_summary_on_success()
	{
		$task = $this->get_task();

		$game_row = $this->make_game_row();
		$guild_row = $this->make_guild_row();

		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			$game_row,
			$guild_row,
			false
		);

		$this->wow_api->method('fetch_guild_data')->willReturn(array('members' => array()));
		$this->wow_api->method('process_guild_data')->willReturn(array());

		$task->run();

		$this->assertStringContainsString('1 guild', (string) $this->config['bbguild_wow_last_sync_result']);
	}

	public function test_run_records_last_sync_result_when_credentials_missing()
	{
		$task = $this->get_task();

		$this->db->method('sql_fetchrow')->willReturn($this->make_game_row(array('apikey' => '')));

		$task->run();

		$this->assertStringContainsString('credentials', (string) $this->config['bbguild_wow_last_sync_result']);
	}
}
