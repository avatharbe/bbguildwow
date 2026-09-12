<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguildwow\tests\avatar;

use PHPUnit\Framework\TestCase;
use avathar\bbguildwow\avatar\character_avatar_driver;

class character_avatar_driver_test extends TestCase
{
	private function make_driver(): character_avatar_driver
	{
		$config = $this->createMock(\phpbb\config\config::class);
		$imagesize = $this->createMock(\FastImageSize\FastImageSize::class);
		$path_helper = $this->getMockBuilder(\phpbb\path_helper::class)
			->disableOriginalConstructor()
			->getMock();
		$cache = $this->createMock(\phpbb\cache\driver\driver_interface::class);

		$driver = new character_avatar_driver($config, $imagesize, '/board/', 'php', $path_helper, $cache);
		$driver->set_name('avatar.driver.bbguildwow_character');

		return $driver;
	}

	public function test_get_data_returns_src_width_height_from_row(): void
	{
		$driver = $this->make_driver();

		$row = array(
			'avatar'        => 'https://render.worldofwarcraft.com/character/eu/1/2-avatar.jpg',
			'avatar_width'  => 200,
			'avatar_height' => 200,
		);

		$this->assertSame(array(
			'src'    => 'https://render.worldofwarcraft.com/character/eu/1/2-avatar.jpg',
			'width'  => 200,
			'height' => 200,
		), $driver->get_data($row));
	}

	public function test_prepare_form_returns_false(): void
	{
		$driver = $this->make_driver();

		$request = $this->createMock(\phpbb\request\request::class);
		$template = $this->createMock(\phpbb\template\template::class);
		$user = $this->getMockBuilder(\phpbb\user::class)
			->disableOriginalConstructor()
			->getMock();
		$error = array();

		$this->assertFalse($driver->prepare_form($request, $template, $user, array(), $error));
	}

	public function test_get_name_returns_set_name(): void
	{
		$driver = $this->make_driver();

		$this->assertSame('avatar.driver.bbguildwow_character', $driver->get_name());
	}
}
