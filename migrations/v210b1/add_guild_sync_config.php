<?php
/**
 * bbGuild WoW Extension — scheduled guild roster sync config
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\migrations\v210b1;

class add_guild_sync_config extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\avathar\bbguildwow\migrations\v210b1\add_player_item_stat'];
	}

	public function update_data()
	{
		return [
			['config.add', ['bbguild_wow_sync_enabled', 0]],
			['config.add', ['bbguild_wow_sync_interval', 21600]],
			['config.add', ['bbguild_wow_last_sync', 0]],
			['config.add', ['bbguild_wow_last_sync_result', '']],
		];
	}
}
