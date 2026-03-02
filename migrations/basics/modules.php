<?php
/**
 * bbGuild WoW plugin - Achievement ACP module migration
 *
 * @package   avathar\bbguild_wow
 * @copyright 2018 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild_wow\migrations\basics;

class modules extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\avathar\bbguild_wow\migrations\basics\schema'];
	}

	public function effectively_installed()
	{
		$sql = 'SELECT COUNT(module_id) AS cnt
			FROM ' . MODULES_TABLE . "
			WHERE module_basename = '" . $this->db->sql_escape('\avathar\bbguild_wow\acp\achievement_module') . "'
				AND module_class = 'acp'";
		$result = $this->db->sql_query($sql);
		$count = (int) $this->db->sql_fetchfield('cnt');
		$this->db->sql_freeresult($result);

		return $count > 0;
	}

	public function update_data()
	{
		return [
			['module.add', ['acp', 'ACP_BBGUILD_PLAYER', [
				'module_basename' => '\avathar\bbguild_wow\acp\achievement_module',
				'modes'           => ['addachievement', 'listachievements'],
			]]],
		];
	}

	public function revert_data()
	{
		return [
			['module.remove', ['acp', 'ACP_BBGUILD_PLAYER', [
				'module_basename' => '\avathar\bbguild_wow\acp\achievement_module',
			]]],
		];
	}
}
