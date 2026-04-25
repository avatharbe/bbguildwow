<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguild_wow\tests\game;

use PHPUnit\Framework\TestCase;
use avathar\bbguild_wow\game\wow_provider;

class wow_provider_test extends TestCase
{
	/** @var wow_provider */
	protected $provider;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $installer;

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $api;

	protected function setUp(): void
	{
		parent::setUp();

		$this->installer = $this->getMockBuilder(\avathar\bbguild_wow\game\wow_installer::class)
			->disableOriginalConstructor()
			->getMock();

		$this->api = $this->getMockBuilder(\avathar\bbguild_wow\game\wow_api::class)
			->disableOriginalConstructor()
			->getMock();

		$ext_manager = $this->createMock(\phpbb\extension\manager::class);
		$ext_manager->method('get_extension_path')
			->with('avathar/bbguild_wow', true)
			->willReturn('/ext/avathar/bbguild_wow/');

		$this->provider = new wow_provider($this->installer, $this->api, $ext_manager);
	}

	public function test_get_game_id(): void
	{
		$this->assertSame('wow', $this->provider->get_game_id());
	}

	public function test_get_game_name(): void
	{
		$this->assertSame('World of Warcraft', $this->provider->get_game_name());
	}

	public function test_get_installer(): void
	{
		$this->assertSame($this->installer, $this->provider->get_installer());
	}

	public function test_has_api(): void
	{
		$this->assertTrue($this->provider->has_api());
	}

	public function test_get_api(): void
	{
		$this->assertSame($this->api, $this->provider->get_api());
	}

	public function test_get_boss_base_url(): void
	{
		$this->assertStringContainsString('wowhead.com', $this->provider->get_boss_base_url());
		$this->assertStringContainsString('%s', $this->provider->get_boss_base_url());
	}

	public function test_get_zone_base_url(): void
	{
		$this->assertStringContainsString('wowhead.com', $this->provider->get_zone_base_url());
		$this->assertStringContainsString('%s', $this->provider->get_zone_base_url());
	}

	public function test_get_images_path(): void
	{
		$this->assertSame('/ext/avathar/bbguild_wow/images/', $this->provider->get_images_path());
	}

	public function test_get_regions(): void
	{
		$regions = $this->provider->get_regions();
		$this->assertCount(5, $regions);
		$this->assertArrayHasKey('us', $regions);
		$this->assertArrayHasKey('eu', $regions);
		$this->assertArrayHasKey('kr', $regions);
		$this->assertArrayHasKey('tw', $regions);
		$this->assertArrayHasKey('sea', $regions);
	}

	public function test_get_api_locales(): void
	{
		$locales = $this->provider->get_api_locales();
		$this->assertCount(5, $locales);
		$this->assertCount(8, $locales['eu']);
		$this->assertCount(3, $locales['us']);
		$this->assertCount(1, $locales['kr']);
		$this->assertCount(1, $locales['tw']);
		$this->assertCount(1, $locales['sea']);
		$this->assertContains('en_GB', $locales['eu']);
		$this->assertContains('de_DE', $locales['eu']);
	}

	public function test_get_armor_types(): void
	{
		$armor = $this->provider->get_armor_types();
		$this->assertCount(4, $armor);
		$this->assertArrayHasKey('CLOTH', $armor);
		$this->assertArrayHasKey('LEATHER', $armor);
		$this->assertArrayHasKey('MAIL', $armor);
		$this->assertArrayHasKey('PLATE', $armor);
	}
}
