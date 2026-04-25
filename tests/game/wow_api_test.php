<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguild_wow\tests\game;

use PHPUnit\Framework\TestCase;
use avathar\bbguild_wow\game\wow_api;

class wow_api_test extends TestCase
{
	/** @var wow_api */
	protected $api;

	protected function setUp(): void
	{
		parent::setUp();

		$cache = $this->createMock(\phpbb\cache\service::class);
		$db = $this->createMock(\phpbb\db\driver\driver_interface::class);

		$this->api = new wow_api($cache, $db, 'phpbb_guild_wow', 'phpbb_players', 'phpbb_ranks');
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
}
