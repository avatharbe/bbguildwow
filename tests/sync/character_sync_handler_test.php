<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguildwow\tests\sync;

use PHPUnit\Framework\TestCase;
use avathar\bbguildwow\sync\character_sync_handler;

class character_sync_handler_test extends TestCase
{
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $wow_api;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $db;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $imagesize;

	/** @var array Captured UPDATE payloads (data passed to sql_build_array('UPDATE', $data)) */
	protected $update_calls;

	protected function get_handler()
	{
		$this->wow_api = $this->getMockBuilder(\avathar\bbguildwow\game\wow_api::class)
			->disableOriginalConstructor()
			->getMock();
		$this->db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$this->imagesize = $this->createMock(\FastImageSize\FastImageSize::class);

		$this->update_calls = array();
		$this->db->method('sql_build_array')->willReturnCallback(function ($mode, $data) {
			$this->update_calls[] = array('mode' => $mode, 'data' => $data);
			return "SET 'dummy'";
		});
		$this->db->method('sql_query')->willReturn(true);
		$this->db->method('sql_freeresult')->willReturn(null);

		return new character_sync_handler($this->wow_api, $this->db, $this->imagesize, 'phpbb_bb_players');
	}

	private function player_row(array $overrides = array()): array
	{
		return array_merge(array(
			'player_id'      => 1,
			'phpbb_user_id'  => 0,
		), $overrides);
	}

	public function test_get_game_id_returns_wow(): void
	{
		$handler = $this->get_handler();

		$this->assertSame('wow', $handler->get_game_id());
	}

	public function test_sync_character_delegates_player_row_to_wow_api(): void
	{
		$handler = $this->get_handler();
		$player_row = $this->player_row(array('phpbb_user_id' => 0));

		$this->wow_api->expects($this->once())
			->method('sync_character')
			->with($player_row)
			->willReturn(true);

		$this->assertTrue($handler->sync_character($player_row));
	}

	public function test_returns_false_when_wow_api_sync_fails(): void
	{
		$handler = $this->get_handler();
		$this->wow_api->method('sync_character')->willReturn(false);

		$this->db->expects($this->never())->method('sql_fetchrow');

		$result = $handler->sync_character($this->player_row(array('phpbb_user_id' => 5)));

		$this->assertFalse($result);
	}

	public function test_returns_true_and_skips_avatar_when_character_unclaimed(): void
	{
		$handler = $this->get_handler();
		$this->wow_api->method('sync_character')->willReturn(true);

		$this->db->expects($this->never())->method('sql_fetchrow');

		$result = $handler->sync_character($this->player_row(array('phpbb_user_id' => 0)));

		$this->assertTrue($result);
	}

	public function test_skips_avatar_when_user_already_has_one(): void
	{
		$handler = $this->get_handler();
		$this->wow_api->method('sync_character')->willReturn(true);

		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			array('player_render_url' => 'https://render.example/char.jpg', 'player_portrait_url' => ''),
			array('user_avatar_type' => 'avatar.driver.upload')
		);

		$handler->sync_character($this->player_row(array('phpbb_user_id' => 5)));

		$this->assertCount(0, $this->update_calls);
	}

	public function test_sets_avatar_from_render_url_when_eligible(): void
	{
		$handler = $this->get_handler();
		$this->wow_api->method('sync_character')->willReturn(true);

		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			array('player_render_url' => 'https://render.example/char.jpg', 'player_portrait_url' => ''),
			array('user_avatar_type' => '')
		);

		$this->imagesize->method('getImageSize')->willReturn(array('width' => 200, 'height' => 200));

		$handler->sync_character($this->player_row(array('phpbb_user_id' => 5)));

		$this->assertCount(1, $this->update_calls);
		$this->assertSame('UPDATE', $this->update_calls[0]['mode']);
		$data = $this->update_calls[0]['data'];
		$this->assertSame('https://render.example/char.jpg', $data['user_avatar']);
		$this->assertSame(200, $data['user_avatar_width']);
		$this->assertSame(200, $data['user_avatar_height']);
		$this->assertSame(character_sync_handler::AVATAR_DRIVER_NAME, $data['user_avatar_type']);
	}

	public function test_falls_back_to_portrait_url_when_render_missing(): void
	{
		$handler = $this->get_handler();
		$this->wow_api->method('sync_character')->willReturn(true);

		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			array('player_render_url' => 'N/A', 'player_portrait_url' => 'https://portrait.example/char.jpg'),
			array('user_avatar_type' => '')
		);

		$this->imagesize->method('getImageSize')->willReturn(array('width' => 100, 'height' => 100));

		$handler->sync_character($this->player_row(array('phpbb_user_id' => 5)));

		$this->assertCount(1, $this->update_calls);
		$this->assertSame('https://portrait.example/char.jpg', $this->update_calls[0]['data']['user_avatar']);
	}

	public function test_skips_avatar_when_no_image_available(): void
	{
		$handler = $this->get_handler();
		$this->wow_api->method('sync_character')->willReturn(true);

		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			array('player_render_url' => '', 'player_portrait_url' => 'N/A'),
			array('user_avatar_type' => '')
		);

		$handler->sync_character($this->player_row(array('phpbb_user_id' => 5)));

		$this->assertCount(0, $this->update_calls);
	}

	public function test_returns_wow_api_result(): void
	{
		$handler = $this->get_handler();
		$this->wow_api->method('sync_character')->willReturn(true);

		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(
			array('player_render_url' => '', 'player_portrait_url' => ''),
			array('user_avatar_type' => '')
		);

		$this->assertTrue($handler->sync_character($this->player_row(array('phpbb_user_id' => 5))));
	}
}
