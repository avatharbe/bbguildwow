<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguildwow\tests\game;

use PHPUnit\Framework\TestCase;
use avathar\bbguildwow\game\wow_api;

class wow_api_sync_guild_activity_test extends TestCase
{
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $db;

	/** @var array Captured INSERT payloads (as sql_build_array('INSERT', $data) input) */
	protected $inserted_rows;

	protected function setUp(): void
	{
		parent::setUp();

		global $phpbb_container;

		$container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
		$container->method('getParameter')->willReturnMap(array(
			array('avathar.bbguild.tables.bb_news', 'phpbb_bb_news'),
		));
		$phpbb_container = $container;

		$this->inserted_rows = array();
	}

	private function make_api(array $existing_key_rows = array()): wow_api
	{
		$this->inserted_rows = array();

		$cache = $this->createMock(\phpbb\cache\service::class);
		$this->db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$filesystem = new \phpbb\filesystem\filesystem();

		$this->db->method('sql_freeresult')->willReturn(null);
		$this->db->method('sql_escape')->willReturnCallback(fn ($v) => addslashes($v));

		$existing_key_rows[] = false; // terminate the existing-keys SELECT loop
		$this->db->method('sql_fetchrow')->willReturnOnConsecutiveCalls(...$existing_key_rows);

		$this->db->method('sql_build_array')->willReturnCallback(function ($mode, $data) {
			$this->inserted_rows[] = $data;
			return "('dummy')";
		});
		$this->db->method('sql_query')->willReturn(true);

		return new wow_api($cache, $this->db, 'phpbb_guild_wow', 'phpbb_players', 'phpbb_ranks', $filesystem);
	}

	private function activity(string $type, int $timestamp_ms, string $character = ''): array
	{
		$activity = array(
			'activity'  => array('type' => $type),
			'timestamp' => $timestamp_ms,
		);
		if ($character !== '')
		{
			$activity['character'] = array('name' => $character);
		}

		return $activity;
	}

	public function test_inserts_new_activity_and_returns_counts(): void
	{
		$api = $this->make_api();

		$result = $api->sync_guild_activity(5, array(
			$this->activity('CHARACTER_ACHIEVEMENT', 1700000000000, 'Sajaki'),
		));

		$this->assertSame(array('inserted' => 1, 'skipped' => 0, 'total' => 1), $result);
		$this->assertCount(1, $this->inserted_rows);
		$this->assertSame(5, $this->inserted_rows[0]['guild_id']);
		$this->assertSame('api', $this->inserted_rows[0]['news_source']);
	}

	public function test_skips_activity_already_present_by_source_key(): void
	{
		$api = $this->make_api();

		$activity = $this->activity('CHARACTER_ACHIEVEMENT', 1700000000000, 'Sajaki');

		// First sync: inserts and we capture the key that was generated.
		$api->sync_guild_activity(5, array($activity));
		$key = $this->inserted_rows[0]['news_source_key'];

		// Second sync: pretend that key is already in bb_news.
		$api2 = $this->make_api(array(array('news_source_key' => $key)));
		$result = $api2->sync_guild_activity(5, array($activity));

		$this->assertSame(array('inserted' => 0, 'skipped' => 1, 'total' => 1), $result);
	}

	public function test_different_guild_ids_produce_different_keys_for_same_activity(): void
	{
		$activity = $this->activity('CHARACTER_ACHIEVEMENT', 1700000000000, 'Sajaki');

		$api1 = $this->make_api();
		$api1->sync_guild_activity(5, array($activity));
		$key1 = $this->inserted_rows[0]['news_source_key'];

		$api2 = $this->make_api();
		$api2->sync_guild_activity(6, array($activity));
		$key2 = $this->inserted_rows[0]['news_source_key'];

		$this->assertNotSame($key1, $key2);
	}

	public function test_headline_includes_character_name_when_present(): void
	{
		$api = $this->make_api();

		$api->sync_guild_activity(5, array(
			$this->activity('CHARACTER_ACHIEVEMENT', 1700000000000, 'Sajaki'),
		));

		$this->assertStringContainsString('Sajaki', $this->inserted_rows[0]['news_headline']);
	}

	public function test_news_date_converted_from_millisecond_timestamp(): void
	{
		$api = $this->make_api();

		$api->sync_guild_activity(5, array(
			$this->activity('CHARACTER_ACHIEVEMENT', 1700000000000),
		));

		$this->assertSame(1700000000, $this->inserted_rows[0]['news_date']);
	}

	public function test_returns_zero_counts_for_empty_activity_list(): void
	{
		$api = $this->make_api();

		$result = $api->sync_guild_activity(5, array());

		$this->assertSame(array('inserted' => 0, 'skipped' => 0, 'total' => 0), $result);
		$this->assertCount(0, $this->inserted_rows);
	}
}
