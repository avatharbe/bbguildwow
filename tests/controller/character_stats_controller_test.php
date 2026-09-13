<?php
namespace avathar\bbguildwow\tests\controller;

use avathar\bbguildwow\controller\character_stats_controller;
use PHPUnit\Framework\TestCase;

class character_stats_controller_test extends TestCase
{
	private function make_controller($db_row, $wow_api_overrides = array())
	{
		$wow_api = $this->getMockBuilder('avathar\bbguildwow\game\wow_api')
			->disableOriginalConstructor()
			->onlyMethods(array('fetch_character_stats', 'fetch_character_professions', 'fetch_mythic_keystone_profile', 'fetch_pvp_summary'))
			->getMock();

		$wow_api->method('fetch_character_stats')->willReturn($wow_api_overrides['stats'] ?? false);
		$wow_api->method('fetch_character_professions')->willReturn($wow_api_overrides['professions'] ?? false);
		$wow_api->method('fetch_mythic_keystone_profile')->willReturn($wow_api_overrides['mplus'] ?? false);
		$wow_api->method('fetch_pvp_summary')->willReturn($wow_api_overrides['pvp'] ?? false);

		$db = $this->createMock('phpbb\db\driver\driver_interface');
		$db->method('sql_query')->willReturn('resource');
		$db->method('sql_fetchrow')->willReturn($db_row);
		$db->method('sql_freeresult')->willReturn(true);

		$auth = $this->createMock('phpbb\auth\auth');
		$auth->method('acl_get')->willReturn(true);

		$language = $this->createMock('phpbb\language\language');
		$language->method('lang')->willReturn('Insufficient permissions.');

		return new character_stats_controller($auth, $wow_api, $db, 'phpbb_bb_players', 'phpbb_bb_guild', $language);
	}

	public function test_returns_404_for_unknown_player(): void
	{
		$controller = $this->make_controller(false);
		$response = $controller->stats(999);

		$this->assertSame(404, $response->getStatusCode());
	}

	public function test_returns_403_when_not_authorized(): void
	{
		$wow_api = $this->getMockBuilder('avathar\bbguildwow\game\wow_api')
			->disableOriginalConstructor()
			->onlyMethods(array('fetch_character_stats', 'fetch_character_professions', 'fetch_mythic_keystone_profile', 'fetch_pvp_summary'))
			->getMock();

		$db = $this->createMock('phpbb\db\driver\driver_interface');
		$db->method('sql_query')->willReturn('resource');
		$db->method('sql_fetchrow')->willReturn(false);
		$db->method('sql_freeresult')->willReturn(true);

		$auth = $this->createMock('phpbb\auth\auth');
		$auth->method('acl_get')->willReturn(false);

		$language = $this->createMock('phpbb\language\language');
		$language->method('lang')->willReturn('Insufficient permissions.');

		$controller = new character_stats_controller($auth, $wow_api, $db, 'phpbb_bb_players', 'phpbb_bb_guild', $language);
		$response = $controller->stats(1);
		$data = json_decode($response->getContent(), true);

		$this->assertSame(403, $response->getStatusCode());
		$this->assertArrayHasKey('error', $data);
	}

	public function test_returns_404_for_non_wow_player(): void
	{
		$controller = $this->make_controller(array(
			'game_id' => 'ffxiv', 'player_name' => 'X', 'player_realm' => 'Y', 'player_region' => 'us', 'game_edition' => 'retail',
		));
		$response = $controller->stats(1);

		$this->assertSame(404, $response->getStatusCode());
	}

	public function test_maps_stats_professions_mplus_pvp_for_retail_player(): void
	{
		$controller = $this->make_controller(
			array('game_id' => 'wow', 'player_name' => 'Sajaki', 'player_realm' => 'silvermoon', 'player_region' => 'eu', 'game_edition' => 'retail'),
			array(
				'stats' => array('strength' => 123, 'versatility' => 45, 'melee_crit' => array('value' => 15.71), 'mastery' => array('value' => 22.5), 'versatility_damage_done_bonus' => array('value' => 8.3)),
				'professions' => array('primaries' => array(
					array('profession' => array('name' => 'Blacksmithing'), 'tiers' => array(array('skill_points' => 300, 'max_skill_points' => 300))),
				)),
				'mplus' => array(
					'current_mythic_rating' => array('rating' => 1500.4, 'color' => array('r' => 255, 'g' => 128, 'b' => 0)),
					'current_period' => array('best_runs' => array(
						array('dungeon' => array('name' => "Atal'Dazar"), 'keystone_level' => 15, 'duration' => 1450000, 'keystone_upgrades' => 2),
					)),
				),
				'pvp' => array('honor_level' => 40),
			)
		);

		$response = $controller->stats(1);
		$data = json_decode($response->getContent(), true);

		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame(123, $data['stats'][0]['value']);
		$this->assertSame('strength', $data['stats'][0]['key']);
		$this->assertSame('base', $data['stats'][0]['group']);
		$this->assertSame('versatility', $data['stats'][1]['key']);
		$this->assertFalse($data['stats'][1]['pct']);
		$this->assertSame('base', $data['stats'][1]['group']);
		$this->assertTrue($data['stats'][2]['pct']);
		$this->assertSame('melee', $data['stats'][2]['group']);
		$this->assertTrue($data['stats'][3]['pct']);
		$this->assertSame('spell', $data['stats'][3]['group']);
		$this->assertSame(22.5, $data['stats'][3]['value']);
		$this->assertTrue($data['stats'][4]['pct']);
		$this->assertSame('spell', $data['stats'][4]['group']);
		$this->assertSame(8.3, $data['stats'][4]['value']);
		$this->assertSame('Blacksmithing', $data['professions'][0]['name']);
		$this->assertSame(1500, $data['mplus']['rating']);
		$this->assertSame('#ff8000', $data['mplus']['color']);
		$this->assertSame("Atal'Dazar", $data['mplus']['runs'][0]['dungeon']);
		$this->assertTrue($data['mplus']['runs'][0]['timed']);
		$this->assertSame(40, $data['pvp']['honor_level']);
	}

	public function test_mplus_and_pvp_are_null_for_non_retail_edition(): void
	{
		$controller = $this->make_controller(
			array('game_id' => 'wow', 'player_name' => 'X', 'player_realm' => 'Y', 'player_region' => 'us', 'game_edition' => 'classic'),
			array('stats' => array('strength' => 50))
		);

		$response = $controller->stats(1);
		$data = json_decode($response->getContent(), true);

		$this->assertNull($data['mplus']);
		$this->assertNull($data['pvp']);
	}
}
