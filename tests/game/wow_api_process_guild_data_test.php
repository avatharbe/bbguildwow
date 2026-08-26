<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguildwow\tests\game;

use PHPUnit\Framework\TestCase;
use avathar\bbguildwow\game\wow_api;

/**
 * Covers wow_api::process_guild_data(), the transform between Battle.net's
 * guild profile response and the shape save_guild_extension() and
 * portrait_controller::do_sync_roster() consume.
 *
 * It is a pure function of its input — no db, cache, globals or HTTP — so it
 * is exercised directly with fixture arrays rather than through the sync
 * route. The one exception is the guild crest, which delegates to
 * create_emblem() and writes to the filesystem; crest handling is therefore
 * out of scope here and covered by the absent-crest case only.
 */
class wow_api_process_guild_data_test extends TestCase
{
	/** @var wow_api */
	protected $api;

	protected function setUp(): void
	{
		parent::setUp();

		$cache = $this->createMock(\phpbb\cache\service::class);
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);
		$filesystem = new \phpbb\filesystem\filesystem();

		$this->api = new wow_api($cache, $db, 'phpbb_guild_wow', 'phpbb_players', 'phpbb_ranks', $filesystem);
	}

	// ── achievement points ─────────────────────────────────

	public function test_achievement_points_extracted(): void
	{
		$result = $this->api->process_guild_data(array('achievement_points' => 4310), array());

		$this->assertSame(4310, $result['achievementpoints']);
	}

	public function test_achievement_points_default_to_zero_when_absent(): void
	{
		$result = $this->api->process_guild_data(array(), array());

		$this->assertSame(0, $result['achievementpoints']);
	}

	// ── fields the modern API no longer provides ───────────

	public function test_level_and_battlegroup_are_always_neutral(): void
	{
		$result = $this->api->process_guild_data(array('level' => 25, 'battlegroup' => 'Vindication'), array());

		$this->assertSame(0, $result['level'], 'guild level was removed from the modern API and must report 0');
		$this->assertSame('', $result['battlegroup'], 'battlegroup was removed from the modern API and must report empty');
	}

	// ── faction ────────────────────────────────────────────

	public function test_alliance_faction_mapped(): void
	{
		$result = $this->api->process_guild_data(array('faction' => array('type' => 'ALLIANCE')), array());

		$this->assertSame(1, $result['faction']);
		$this->assertSame('Alliance', $result['faction_name']);
	}

	public function test_horde_faction_mapped(): void
	{
		$result = $this->api->process_guild_data(array('faction' => array('type' => 'HORDE')), array());

		$this->assertSame(2, $result['faction']);
		$this->assertSame('Horde', $result['faction_name']);
	}

	public function absent_faction_data(): array
	{
		return array(
			'no faction key'      => array(array()),
			'faction not an array'=> array(array('faction' => 'HORDE')),
			'no type key'         => array(array('faction' => array())),
			'unrecognised type'   => array(array('faction' => array('type' => 'NEUTRAL'))),
		);
	}

	/**
	 * Regression guard for #29: an incomplete guild response must not report a
	 * faction at all, so update_guild_battleNet() preserves the faction stored
	 * in the ACP instead of silently resetting every guild to Horde.
	 *
	 * @dataProvider absent_faction_data
	 */
	public function test_faction_omitted_when_api_does_not_provide_one(array $raw): void
	{
		$result = $this->api->process_guild_data($raw, array());

		$this->assertArrayNotHasKey('faction', $result, 'faction must be absent, not defaulted');
		$this->assertArrayNotHasKey('faction_name', $result);
	}

	// ── armory URL ─────────────────────────────────────────

	public function test_armory_url_built_for_retail(): void
	{
		$result = $this->api->process_guild_data(array(
			'name'         => 'Knights of Avathar',
			'_region'      => 'eu',
			'_realm_slug'  => 'area-52',
		), array());

		$this->assertSame(
			'https://worldofwarcraft.blizzard.com/en-eu/guild/eu/area-52/knights-of-avathar',
			$result['guildarmoryurl']
		);
	}

	public function test_realm_slug_derived_from_realm_name_when_not_supplied(): void
	{
		$result = $this->api->process_guild_data(array(
			'name'    => 'My Guild',
			'_region' => 'eu',
			'_realm'  => "Mal'Ganis",
		), array());

		$this->assertStringContainsString('/eu/malganis/my-guild', $result['guildarmoryurl']);
	}

	public function test_armory_url_empty_for_classic(): void
	{
		$result = $this->api->process_guild_data(array(
			'name'        => 'My Guild',
			'_region'     => 'eu',
			'_realm_slug' => 'area-52',
			'_edition'    => 'classic',
		), array());

		$this->assertSame('', $result['guildarmoryurl'], 'Classic has no official armory');
	}

	public function test_armory_url_empty_without_a_guild_name(): void
	{
		$result = $this->api->process_guild_data(array('_region' => 'eu', '_realm_slug' => 'area-52'), array());

		$this->assertSame('', $result['guildarmoryurl']);
	}

	// ── emblem ─────────────────────────────────────────────

	public function test_emblem_path_empty_without_a_crest(): void
	{
		$result = $this->api->process_guild_data(array('name' => 'My Guild'), array());

		$this->assertSame('', $result['emblempath']);
	}

	// ── members ────────────────────────────────────────────

	public function test_members_passed_through_with_count(): void
	{
		$members = array(
			array('character' => array('name' => 'Sajaki'), 'rank' => 0),
			array('character' => array('name' => 'Jeeves'), 'rank' => 4),
		);

		$result = $this->api->process_guild_data(array('members' => $members), array('members'));

		$this->assertSame($members, $result['members']);
		$this->assertSame(2, $result['playercount']);
	}

	public function test_members_default_to_empty_with_zero_count(): void
	{
		$result = $this->api->process_guild_data(array(), array('members'));

		$this->assertSame(array(), $result['members']);
		$this->assertSame(0, $result['playercount']);
	}
}
