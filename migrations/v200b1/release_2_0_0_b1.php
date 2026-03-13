<?php
/**
 *
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Release 2.0.0-b1 version stamp
 */

namespace avathar\bbguild_wow\migrations\v200b1;

class release_2_0_0_b1 extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return [
			'\avathar\bbguild_wow\migrations\v200a3\achievement_categories',
		];
	}

	public function effectively_installed()
	{
		return isset($this->config['bbguild_wow_version'])
			&& version_compare($this->config['bbguild_wow_version'], '2.0.0-b1', '>=');
	}

	public function update_data()
	{
		return [
			['config.update', ['bbguild_wow_version', '2.0.0-b1']],
		];
	}
}
