<?php
/**
 * bbGuild WoW Extension — ACP module load smoke test
 *
 * @package   bbguildwow v2.0
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

/**
 * Logs in as admin and requests every bbguildwow ACP module. Asserts
 * the response is never a 5xx — catches module main() throwing on
 * load, missing language keys causing fatals, broken template paths.
 * No content assertions: a controlled trigger_error() page (e.g. "no
 * guild selected") is a pass here, a PHP fatal is not.
 *
 * @group smoke
 */
class avathar_bbguildwow_smoke_acp_modules_load_test extends phpbb_functional_test_case
{
	static protected function setup_extensions()
	{
		return array('avathar/bbguild', 'avathar/bbguildwow');
	}

	/**
	 * @return string[] i=/mode= query strings for every bbguildwow ACP module.
	 */
	private function acp_module_queries(): array
	{
		return array(
			'i=-avathar-bbguildwow-acp-battlenet_module&mode=battlenet',
			'i=-avathar-bbguildwow-acp-achievement_module&mode=listachievements',
			'i=-avathar-bbguildwow-acp-achievement_module&mode=addachievement',
		);
	}

	public function test_acp_modules_load_without_server_error()
	{
		$this->login('admin');
		$this->admin_login();

		foreach ($this->acp_module_queries() as $query)
		{
			self::request('GET', 'adm/index.php?' . $query . '&sid=' . $this->sid, array(), false);
			$status = (int) self::$client->getResponse()->getStatus();
			$this->assertLessThan(500, $status, "ACP module query '$query' returned $status");
		}

		$this->logout();
	}
}
