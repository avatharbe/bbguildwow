<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguild_wow\tests\api;

use PHPUnit\Framework\TestCase;

/**
 * Test subclass that captures consume() calls instead of making HTTP requests.
 */
class battlenet_character_test_wrapper extends \avathar\bbguild_wow\api\battlenet_character
{
	/** @var string Last method passed to consume() */
	public $last_method = '';

	/** @var array Last params passed to consume() */
	public $last_params = array();

	/**
	 * Override constructor to skip parent's trigger_error check.
	 */
	public function __construct(\phpbb\cache\service $cache, $region = 'us', $cacheTtl = 3600)
	{
		$this->region = $region;
		$this->cache = $cache;
		// $cacheTtl is private in battlenet_resource; consume() is overridden so it's never used.
	}

	/**
	 * Intercept consume() to capture the method and params.
	 */
	public function consume($method, array $params): array
	{
		$this->last_method = $method;
		$this->last_params = $params;
		return array('response' => array(), 'response_headers' => array('http_code' => 200));
	}
}

class battlenet_character_test extends TestCase
{
	/** @var battlenet_character_test_wrapper */
	protected $character;

	protected function setUp(): void
	{
		parent::setUp();
		$cache = $this->createMock(\phpbb\cache\service::class);
		$this->character = new battlenet_character_test_wrapper($cache, 'eu');
	}

	// ── Endpoint URL construction ──────────────────────────

	public function endpoint_data(): array
	{
		return array(
			'getCharacter'                    => array('getCharacter',                    'argent-dawn', 'Sajaki',  'argent-dawn/sajaki'),
			'getCharacterMedia'               => array('getCharacterMedia',               'argent-dawn', 'Sajaki',  'argent-dawn/sajaki/character-media'),
			'getCharacterSpecializations'     => array('getCharacterSpecializations',     'argent-dawn', 'Sajaki',  'argent-dawn/sajaki/specializations'),
			'getCharacterEquipment'           => array('getCharacterEquipment',           'argent-dawn', 'Sajaki',  'argent-dawn/sajaki/equipment'),
			'getCharacterStatistics'          => array('getCharacterStatistics',          'argent-dawn', 'Sajaki',  'argent-dawn/sajaki/statistics'),
			'getCharacterProfessions'         => array('getCharacterProfessions',         'argent-dawn', 'Sajaki',  'argent-dawn/sajaki/professions'),
			'getCharacterMythicKeystoneProfile' => array('getCharacterMythicKeystoneProfile', 'argent-dawn', 'Sajaki', 'argent-dawn/sajaki/mythic-keystone-profile'),
			'getCharacterPvPSummary'          => array('getCharacterPvPSummary',          'argent-dawn', 'Sajaki',  'argent-dawn/sajaki/pvp-summary'),
		);
	}

	/**
	 * @dataProvider endpoint_data
	 */
	public function test_endpoint_url(string $method_name, string $realm, string $name, string $expected_method): void
	{
		$this->character->$method_name($realm, $name);
		$this->assertSame($expected_method, $this->character->last_method);
	}

	// ── Unicode normalization ──────────────────────────────

	public function test_unicode_name_lowered(): void
	{
		$this->character->getCharacter('argent-dawn', 'Éloïse');
		$this->assertSame('argent-dawn/éloïse', $this->character->last_method);
	}

	public function test_name_trimmed(): void
	{
		$this->character->getCharacter('argent-dawn', '  Sajaki  ');
		$this->assertSame('argent-dawn/sajaki', $this->character->last_method);
	}

	// ── Empty input triggers error ─────────────────────────

	public function test_empty_name_triggers_error(): void
	{
		// getCharacter calls trigger_error via global $user when name is empty.
		// We need $user->lang to be set for the trigger_error call.
		$GLOBALS['user'] = new \stdClass();
		$GLOBALS['user']->lang = array(
			'WOWAPI_NO_CHARACTER' => 'No character specified',
		);

		$this->expectError();
		$this->character->getCharacter('argent-dawn', '');
	}

	public function test_empty_realm_triggers_error(): void
	{
		$GLOBALS['user'] = new \stdClass();
		$GLOBALS['user']->lang = array(
			'WOWAPI_NO_REALMS' => 'No realm specified',
		);

		$this->expectError();
		$this->character->getCharacter('', 'Sajaki');
	}

	protected function tearDown(): void
	{
		unset($GLOBALS['user']);
		parent::tearDown();
	}
}
