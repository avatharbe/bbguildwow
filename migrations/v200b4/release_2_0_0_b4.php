<?php
/**
 * bbGuild WoW Extension — 2.0.0-b4 migration
 *
 * Cleans up the legacy `bbguild_wow_version` phpbb_config row left over
 * from the old version-string tracking removed in this release (matches
 * avatharbe/bbguild's own #353 refactor: version now lives solely in
 * ext::BBGUILDWOW_VERSION). Nothing reads this row anymore, so removal
 * is a one-way cleanup — no revert_data() to restore it.
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\migrations\v200b4;

class release_2_0_0_b4 extends \phpbb\db\migration\container_aware_migration
{
	public static function depends_on()
	{
		return ['\avathar\bbguildwow\migrations\v200b3\release_2_0_0_b3'];
	}

	public function effectively_installed()
	{
		return !isset($this->config['bbguild_wow_version']);
	}

	public function update_data()
	{
		return [
			['config.remove', ['bbguild_wow_version']],
		];
	}
}
