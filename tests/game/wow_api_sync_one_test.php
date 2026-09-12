<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguildwow\tests\game;

use PHPUnit\Framework\TestCase;
use avathar\bbguildwow\game\wow_api;
use avathar\bbguildwow\api\battlenet;
use avathar\bbguildwow\api\battlenet_character;

/**
 * Test subclass that returns a scripted consume() response instead of
 * making HTTP requests, matching tests/api/battlenet_character_test.php's
 * established wrapper pattern.
 */
class scripted_battlenet_character extends battlenet_character
{
	/** @var array */
	public $scripted_response = array('response' => array(), 'response_headers' => array('http_code' => 200));

	public function __construct()
	{
		// Skip parent's constructor entirely — consume() is overridden below
		// so none of battlenet_resource's HTTP/OAuth setup is needed.
	}

	public function consume($method, array $params): array
	{
		return $this->scripted_response;
	}
}

class wow_api_sync_one_test extends TestCase
{
	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\db\driver\driver_interface */
	private $db;

	/** @var wow_api */
	private $api;

	/** @var scripted_battlenet_character */
	private $character;

	/** @var battlenet */
	private $battlenet;

	protected function setUp(): void
	{
		parent::setUp();

		$cache = $this->createMock(\phpbb\cache\service::class);
		$this->db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$this->db->method('sql_escape')->willReturnCallback(function ($s) { return addslashes($s); });
		$this->db->method('sql_build_array')->willReturnCallback(function ($type, $data) {
			return '(' . implode(',', array_keys($data)) . ') VALUES (\'' . implode('\',\'', $data) . '\')';
		});
		$filesystem = new \phpbb\filesystem\filesystem();

		$this->api = new wow_api($cache, $this->db, 'phpbb_guild_wow', 'phpbb_players', 'phpbb_ranks', $filesystem);

		$this->character = new scripted_battlenet_character();
		$this->battlenet = $this->getMockBuilder(battlenet::class)
			->disableOriginalConstructor()
			->getMock();
		$this->battlenet->character = $this->character;
	}

	private function invoke_protected(string $method, array $args)
	{
		$reflection = new \ReflectionMethod(wow_api::class, $method);
		$reflection->setAccessible(true);
		return $reflection->invokeArgs($this->api, $args);
	}

	// ── sync_one_specs() ────────────────────────────────────

	public function test_sync_one_specs_success(): void
	{
		$this->character->scripted_response = array(
			'response' => array('active_specialization' => array('name' => 'Fury')),
			'response_headers' => array('http_code' => 200),
		);
		$captured_sql = '';
		$this->db->expects($this->once())->method('sql_query')
			->with($this->callback(function ($sql) use (&$captured_sql) { $captured_sql = $sql; return true; }));

		$player = array('player_id' => 42, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn');
		$result = $this->invoke_protected('sync_one_specs', array($player, $this->battlenet));

		$this->assertSame(array('success' => true, 'error_code' => null, 'stop_batch' => false), $result);
		$this->assertStringContainsString("player_spec = 'Fury'", $captured_sql);
		$this->assertStringContainsString('WHERE player_id = 42', $captured_sql);
	}

	public function test_sync_one_specs_404_marks_unavailable(): void
	{
		// Default $mark_unavailable (no 3rd argument) must still write the
		// sentinel — this is the guild-batch path's existing behaviour and
		// must not regress now that sync_character() (#362) needs to opt out.
		$this->character->scripted_response = array(
			'response' => array('code' => 404),
			'response_headers' => array('http_code' => 404),
		);
		$captured_sql = '';
		$this->db->expects($this->once())->method('sql_query')
			->with($this->callback(function ($sql) use (&$captured_sql) { $captured_sql = $sql; return true; }));

		$player = array('player_id' => 42, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn');
		$result = $this->invoke_protected('sync_one_specs', array($player, $this->battlenet));

		$this->assertSame(array('success' => false, 'error_code' => 404, 'stop_batch' => false), $result);
		$this->assertStringContainsString("player_spec = 'N/A'", $captured_sql);
	}

	public function test_sync_one_specs_404_with_mark_unavailable_false_skips_sentinel_write(): void
	{
		$this->character->scripted_response = array(
			'response' => array('code' => 404),
			'response_headers' => array('http_code' => 404),
		);
		$this->db->expects($this->never())->method('sql_query');

		$player = array('player_id' => 42, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn');
		$result = $this->invoke_protected('sync_one_specs', array($player, $this->battlenet, false));

		$this->assertSame(array('success' => false, 'error_code' => 404, 'stop_batch' => false), $result);
	}

	public function test_sync_one_specs_500_sets_stop_batch(): void
	{
		$this->character->scripted_response = array(
			'response' => null,
			'response_headers' => array('http_code' => 500),
		);
		$this->db->method('sql_query');

		$player = array('player_id' => 42, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn');
		$result = $this->invoke_protected('sync_one_specs', array($player, $this->battlenet));

		$this->assertFalse($result['success']);
		$this->assertTrue($result['stop_batch']);
	}

	// ── sync_one_equipment() ────────────────────────────────

	public function test_sync_one_equipment_success(): void
	{
		$this->character->scripted_response = array(
			'response' => array('equipped_items' => array()),
			'response_headers' => array('http_code' => 200),
		);
		$this->db->expects($this->exactly(2))->method('sql_query');

		$player = array('player_id' => 42, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn');
		$result = $this->invoke_protected('sync_one_equipment', array($player, $this->battlenet, 'bb_player_equipment', 'bb_player_item_stat'));

		$this->assertSame(array('success' => true, 'error_code' => null, 'stop_batch' => false), $result);
	}

	public function test_sync_one_equipment_missing_equipped_items_is_failure(): void
	{
		$this->character->scripted_response = array(
			'response' => array('some_other_field' => true),
			'response_headers' => array('http_code' => 200),
		);
		$this->db->method('sql_query');

		$player = array('player_id' => 42, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn');
		$result = $this->invoke_protected('sync_one_equipment', array($player, $this->battlenet, 'bb_player_equipment', 'bb_player_item_stat'));

		$this->assertFalse($result['success']);
	}

	// ── sync_one_portrait() ─────────────────────────────────

	public function test_sync_one_portrait_success_falls_back_to_remote_url_when_download_fails(): void
	{
		// download_portrait() uses file_get_contents() on the asset URL; a
		// bogus test URL fails gracefully (returns '') and the existing
		// code already falls back to storing the raw remote URL, so this
		// test needs no real network access.
		$this->character->scripted_response = array(
			'response' => array('assets' => array(array('key' => 'avatar', 'value' => 'http://example.invalid/a.jpg'))),
			'response_headers' => array('http_code' => 200),
		);
		$captured_sql = '';
		$this->db->expects($this->once())->method('sql_query')
			->with($this->callback(function ($sql) use (&$captured_sql) { $captured_sql = $sql; return true; }));

		$player = array('player_id' => 42, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn');
		$result = $this->invoke_protected('sync_one_portrait', array($player, $this->battlenet, '/tmp/portraits/', 'files/bbguildwow/portraits/', 'files', '/tmp/'));

		$this->assertSame(array('success' => true, 'error_code' => null, 'stop_batch' => false), $result);
		$this->assertStringContainsString("player_portrait_url = 'http://example.invalid/a.jpg'", $captured_sql);
	}

	public function test_sync_one_portrait_no_avatar_asset_is_failure(): void
	{
		$this->character->scripted_response = array(
			'response' => array('assets' => array()),
			'response_headers' => array('http_code' => 200),
		);

		$player = array('player_id' => 42, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn');
		$result = $this->invoke_protected('sync_one_portrait', array($player, $this->battlenet, '/tmp/portraits/', 'files/bbguildwow/portraits/', 'files', '/tmp/'));

		$this->assertSame(array('success' => false, 'error_code' => 'no_avatar', 'stop_batch' => false), $result);
	}

	public function test_sync_one_portrait_404_marks_unavailable(): void
	{
		// Default $mark_unavailable (no 7th argument) must write the
		// sentinel — matches sync_one_specs()'s default-preserved behaviour
		// for the existing guild-batch path (#362).
		$this->character->scripted_response = array(
			'response' => array('code' => 404),
			'response_headers' => array('http_code' => 404),
		);
		$captured_sql = '';
		$this->db->expects($this->once())->method('sql_query')
			->with($this->callback(function ($sql) use (&$captured_sql) { $captured_sql = $sql; return true; }));

		$player = array('player_id' => 42, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn');
		$result = $this->invoke_protected('sync_one_portrait', array($player, $this->battlenet, '/tmp/portraits/', 'files/bbguildwow/portraits/', 'files', '/tmp/'));

		$this->assertSame(array('success' => false, 'error_code' => 404, 'stop_batch' => false), $result);
		$this->assertStringContainsString("player_portrait_url = 'N/A'", $captured_sql);
	}

	public function test_sync_one_portrait_404_with_mark_unavailable_false_skips_sentinel_write(): void
	{
		$this->character->scripted_response = array(
			'response' => array('code' => 404),
			'response_headers' => array('http_code' => 404),
		);
		$this->db->expects($this->never())->method('sql_query');

		$player = array('player_id' => 42, 'player_name' => 'Sajaki', 'player_realm' => 'argent-dawn');
		$result = $this->invoke_protected('sync_one_portrait', array($player, $this->battlenet, '/tmp/portraits/', 'files/bbguildwow/portraits/', 'files', '/tmp/', false));

		$this->assertSame(array('success' => false, 'error_code' => 404, 'stop_batch' => false), $result);
	}
}
