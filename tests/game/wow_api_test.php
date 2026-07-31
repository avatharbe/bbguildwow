<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguildwow\tests\game;

use PHPUnit\Framework\TestCase;
use avathar\bbguildwow\game\wow_api;

class wow_api_test extends TestCase
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

	// ── to_slug() ──────────────────────────────────────────

	public function to_slug_data(): array
	{
		return array(
			'spaces to hyphens'  => array('Area 52', 'area-52'),
			'apostrophe strip'   => array("Mal'Ganis", 'malganis'),
			'multi word'         => array('Twisting Nether', 'twisting-nether'),
			'unicode accent'     => array("Pozzo dell'Eternità", 'pozzo-delleternità'),
			'empty string'       => array('', ''),
			'leading trailing'   => array('  spaced  ', 'spaced'),
			'multiple hyphens'   => array('A - B', 'a-b'),
		);
	}

	/**
	 * @dataProvider to_slug_data
	 */
	public function test_to_slug(string $input, string $expected): void
	{
		$this->assertSame($expected, $this->api->to_slug($input));
	}

	// ── error_label() (private — via reflection) ───────────

	public function error_label_data(): array
	{
		return array(
			'404'        => array(404, '404 Not Found'),
			'403'        => array(403, '403 Forbidden'),
			'500'        => array(500, '500 Server Error'),
			'503'        => array(503, '503 Service Unavailable'),
			'no_avatar'  => array('no_avatar', 'No avatar data'),
			'no_spec'    => array('no_spec', 'No spec data'),
			'unknown'    => array('unknown', 'Unknown error'),
			'unmapped'   => array(429, 'HTTP 429'),
		);
	}

	/**
	 * @dataProvider error_label_data
	 */
	public function test_error_label($code, string $expected): void
	{
		$method = new \ReflectionMethod(wow_api::class, 'error_label');
		$method->setAccessible(true);

		$this->assertSame($expected, $method->invoke($this->api, $code));
	}

	// ── get_player_armory_url() ────────────────────────────

	public function armory_url_data(): array
	{
		return array(
			'retail eu' => array(
				'Sajaki', 'Argent Dawn', 'eu', 'retail',
				'https://worldofwarcraft.blizzard.com/en-eu/character/eu/argent-dawn/sajaki',
			),
			'retail us' => array(
				'Thrall', 'Area 52', 'us', 'retail',
				'https://worldofwarcraft.blizzard.com/en-us/character/us/area-52/thrall',
			),
			'classic returns empty' => array(
				'Sajaki', 'Argent Dawn', 'eu', 'classic_era',
				'',
			),
			'classic_prog returns empty' => array(
				'Sajaki', 'Argent Dawn', 'eu', 'classic_prog',
				'',
			),
		);
	}

	/**
	 * @dataProvider armory_url_data
	 */
	public function test_get_player_armory_url(string $name, string $realm, string $region, string $edition, string $expected): void
	{
		$this->assertSame($expected, $this->api->get_player_armory_url($name, $realm, $region, $edition));
	}

	// ── get_player_portrait_url() ──────────────────────────

	public function test_get_player_portrait_url_with_url(): void
	{
		$data = array('player_portrait_url' => 'https://render.worldofwarcraft.com/character/avatar.jpg');
		$this->assertSame('https://render.worldofwarcraft.com/character/avatar.jpg', $this->api->get_player_portrait_url($data));
	}

	public function test_get_player_portrait_url_empty(): void
	{
		$this->assertSame('', $this->api->get_player_portrait_url(array()));
	}

	public function test_get_player_portrait_url_empty_string(): void
	{
		$data = array('player_portrait_url' => '');
		$this->assertSame('', $this->api->get_player_portrait_url($data));
	}

	// ── requires_api_key() ─────────────────────────────────

	public function test_requires_api_key(): void
	{
		$this->assertTrue($this->api->requires_api_key());
	}

	// ── parse_equipped_item() ──────────────────────────────

	private function sample_head_item(): array
	{
		return array(
			'slot'    => array('type' => 'HEAD'),
			'item'    => array('id' => 50468),
			'name'    => 'Sanctified Lightsworn Headpiece',
			'level'   => array('value' => 277),
			'quality' => array('type' => 'EPIC'),
			'media'   => array('id' => 12345),
			'enchantments' => array(
				array('enchantment_id' => 4800, 'enchantment_slot' => array('type' => 'PERMANENT')),
				array('enchantment_id' => 9999, 'enchantment_slot' => array('type' => 'TEMPORARY')),
			),
			'sockets' => array(
				array('item' => array('id' => 40133)),
				array('item' => array('id' => 40132)),
			),
			'bonus_list' => array(6652, 1487),
			'set' => array('items' => array(
				array('item' => array('id' => 50468)),
				array('item' => array('id' => 50469)),
			)),
			'stats' => array(
				array('type' => array('type' => 'STRENGTH'), 'value' => 120),
				array('type' => array('type' => 'CRIT_RATING'), 'value' => 45),
				array('type' => array('type' => 'AGILITY'), 'value' => 5, 'is_negated' => true),
			),
		);
	}

	public function test_parse_equipped_item_core_and_detail(): void
	{
		$out = $this->api->parse_equipped_item($this->sample_head_item());
		$this->assertSame('HEAD', $out['slot_type']);
		$eq = $out['equipment'];
		$this->assertSame(50468, $eq['item_id']);
		$this->assertSame(277, $eq['item_level']);
		$this->assertSame('EPIC', $eq['quality']);
		$this->assertSame(4800, $eq['enchant_id']);          // PERMANENT only
		$this->assertSame('40133:40132', $eq['gem_ids']);
		$this->assertSame('6652:1487', $eq['bonus_ids']);
		$this->assertSame('50468:50469', $eq['set_item_ids']);
		$this->assertStringContainsString('/icons/56/12345.jpg', $eq['icon_url']);
	}

	public function test_parse_equipped_item_stats_skip_negated(): void
	{
		$out = $this->api->parse_equipped_item($this->sample_head_item());
		$this->assertSame(
			array(
				array('stat_type' => 'STRENGTH', 'stat_value' => 120),
				array('stat_type' => 'CRIT_RATING', 'stat_value' => 45),
			),
			$out['stats']
		);
	}

	public function test_parse_equipped_item_minimal_defaults(): void
	{
		$out = $this->api->parse_equipped_item(array('slot' => array('type' => 'NECK'), 'item' => array('id' => 1)));
		$this->assertSame('NECK', $out['slot_type']);
		$this->assertSame(0, $out['equipment']['enchant_id']);
		$this->assertSame('', $out['equipment']['gem_ids']);
		$this->assertSame('', $out['equipment']['bonus_ids']);
		$this->assertSame('', $out['equipment']['set_item_ids']);
		$this->assertSame(array(), $out['stats']);
	}

	public function test_parse_equipped_item_no_slot_returns_empty_slot(): void
	{
		$out = $this->api->parse_equipped_item(array('item' => array('id' => 1)));
		$this->assertSame('', $out['slot_type']);
	}
}
