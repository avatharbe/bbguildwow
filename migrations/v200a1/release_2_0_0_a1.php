<?php
/**
 *
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Release 2.0.0-a1 version stamp
 */

namespace avathar\bbguild_wow\migrations\v200a1;

class release_2_0_0_a1 extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return [
			'\avathar\bbguild_wow\migrations\basics\data',
			'\avathar\bbguild_wow\migrations\basics\modules',
		];
	}

	public function effectively_installed()
	{
		return isset($this->config['bbguild_wow_version'])
			&& version_compare($this->config['bbguild_wow_version'], '2.0.0-a1', '>=');
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'set_version']]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['bbguild_wow_version']],
		];
	}

	public function set_version()
	{
		$this->config->set('bbguild_wow_version', '2.0.0-a1');
	}
}
