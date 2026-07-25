<?php
/**
 * bbGuild - World of Warcraft — 2.0.0-rc2 ACP restructure migration
 *
 * Moves the "BattleNet API" ACP module (battlenet_module) out of the bbGuild
 * "General Settings" category (ACP_BBGUILD_MAINPAGE) into the new "Game
 * settings" category (ACP_BBGUILD_GAMESETTINGS) created by bbguild core rc4.
 *
 * depends_on the core rc4 migration so the target category already exists
 * before this migration re-parents the module.
 *
 * Canonical version lives in ext::BBGUILDWOW_VERSION; not in phpbb_config.
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\migrations\v200rc2;

class release_2_0_0_rc2 extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return [
			'\avathar\bbguildwow\migrations\v200b4\release_2_0_0_b4',
			'\avathar\bbguild\migrations\v200rc4\release_2_0_0_rc4',
		];
	}

	public function effectively_installed()
	{
		// battlenet_module already parented under the Game settings category?
		$sql = 'SELECT m.module_id
			FROM ' . $this->table_prefix . 'modules m
			JOIN ' . $this->table_prefix . "modules p ON p.module_id = m.parent_id
			WHERE m.module_class = 'acp'
				AND m.module_basename = '\\avathar\\bbguildwow\\acp\\battlenet_module'
				AND p.module_langname = 'ACP_BBGUILD_GAMESETTINGS'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (bool) $row;
	}

	public function update_data()
	{
		return [
			['module.remove', ['acp', 'ACP_BBGUILD_MAINPAGE', [
				'module_basename' => '\avathar\bbguildwow\acp\battlenet_module',
			]]],
			['module.add', ['acp', 'ACP_BBGUILD_GAMESETTINGS', [
				'module_basename' => '\avathar\bbguildwow\acp\battlenet_module',
				'modes'           => ['battlenet'],
			]]],
		];
	}

	// No revert_data(): phpBB's migrator auto-reverses update_data() on uninstall
	// (battlenet_module removed from Game settings and re-added to General
	// Settings). An explicit revert that repeated those steps ran them twice —
	// "A module already exists: ACP_WOW_BATTLENET" — because migrator::revert()
	// merges reverse_update_data(update_data) WITH revert_data().
}
