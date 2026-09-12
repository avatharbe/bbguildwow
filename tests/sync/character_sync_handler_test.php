<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguildwow\tests\sync;

use PHPUnit\Framework\TestCase;
use avathar\bbguildwow\sync\character_sync_handler;
use avathar\bbguildwow\game\wow_api;

class character_sync_handler_test extends TestCase
{
	public function test_get_game_id_returns_wow(): void
	{
		$wow_api = $this->getMockBuilder(wow_api::class)->disableOriginalConstructor()->getMock();
		$handler = new character_sync_handler($wow_api);

		$this->assertSame('wow', $handler->get_game_id());
	}

	public function test_sync_character_delegates_to_wow_api_and_returns_true(): void
	{
		$wow_api = $this->getMockBuilder(wow_api::class)->disableOriginalConstructor()->getMock();
		$player_row = array('player_id' => 1, 'player_name' => 'Sajaki');
		$wow_api->expects($this->once())->method('sync_character')->with($player_row)->willReturn(true);

		$handler = new character_sync_handler($wow_api);

		$this->assertTrue($handler->sync_character($player_row));
	}

	public function test_sync_character_delegates_to_wow_api_and_returns_false(): void
	{
		$wow_api = $this->getMockBuilder(wow_api::class)->disableOriginalConstructor()->getMock();
		$player_row = array('player_id' => 1, 'player_name' => 'Sajaki');
		$wow_api->expects($this->once())->method('sync_character')->with($player_row)->willReturn(false);

		$handler = new character_sync_handler($wow_api);

		$this->assertFalse($handler->sync_character($player_row));
	}
}
