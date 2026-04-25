<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguild_wow\tests\system;

use PHPUnit\Framework\TestCase;

class ext_test extends TestCase
{
	/** @var \PHPUnit\Framework\MockObject\MockObject|\Symfony\Component\DependencyInjection\ContainerInterface */
	protected $container;

	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\finder */
	protected $extension_finder;

	/** @var \PHPUnit\Framework\MockObject\MockObject|\phpbb\db\migrator */
	protected $migrator;

	protected function setUp(): void
	{
		parent::setUp();

		// Mock ext.manager that reports bbguild core as enabled
		$ext_manager = $this->createMock(\phpbb\extension\manager::class);
		$ext_manager->method('is_enabled')
			->with('avathar/bbguild')
			->willReturn(true);

		$this->container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
		$this->container->method('get')
			->with('ext.manager')
			->willReturn($ext_manager);

		$this->extension_finder = $this->getMockBuilder(\phpbb\finder::class)
			->disableOriginalConstructor()
			->getMock();

		$this->migrator = $this->getMockBuilder(\phpbb\db\migrator::class)
			->disableOriginalConstructor()
			->getMock();
	}

	public function test_ext_is_enableable(): void
	{
		$ext = new \avathar\bbguild_wow\ext(
			$this->container,
			$this->extension_finder,
			$this->migrator,
			'avathar/bbguild_wow',
			''
		);

		$this->assertTrue($ext->is_enableable());
	}

	public function test_ext_requires_bbguild_core(): void
	{
		// Override: bbguild core NOT enabled
		$ext_manager = $this->createMock(\phpbb\extension\manager::class);
		$ext_manager->method('is_enabled')
			->with('avathar/bbguild')
			->willReturn(false);

		$container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
		$container->method('get')
			->with('ext.manager')
			->willReturn($ext_manager);

		$ext = new \avathar\bbguild_wow\ext(
			$container,
			$this->extension_finder,
			$this->migrator,
			'avathar/bbguild_wow',
			''
		);

		$result = $ext->is_enableable();
		$this->assertIsArray($result);
		$this->assertStringContainsString('bbGuild core', $result[0]);
	}
}
