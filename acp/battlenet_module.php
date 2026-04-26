<?php
/**
 * Battle.net API ACP module
 *
 * Provides a dashboard for managing Battle.net OAuth 2.0 credentials,
 * testing the API connection, and managing the token cache.
 *
 * @package   bbguildwow v2.0
 * @copyright 2018 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\acp;

use avathar\bbguild\model\games\game;

/**
 * Class battlenet_module
 *
 * @package avathar\bbguildwow\acp
 */
class battlenet_module
{
	/** @var string */
	public $tpl_name;

	/** @var string */
	public $page_title;

	/** @var string */
	public $u_action;

	/**
	 * @param int    $id
	 * @param string $mode
	 */
	public function main($id, $mode)
	{
		global $user, $db, $template, $request, $phpbb_container, $auth;

		$form_key = 'avathar/bbguildwow_battlenet';
		add_form_key($form_key);

		$this->tpl_name = 'acp_battlenet';
		$this->page_title = $user->lang['ACP_WOW_BATTLENET'];

		if (!$auth->acl_get('a_bbguild'))
		{
			trigger_error($user->lang['NOAUTH_A_PLAYERS_MAN'], E_USER_WARNING);
		}

		$template->assign_vars(array(
			'S_BBGUILD' => true,
		));

		$cache = $phpbb_container->get('cache');

		// Load WoW game record to get credentials
		$game = $this->get_game($db, $phpbb_container);

		$client_id = '';
		$client_secret = '';
		$api_locale = '';
		if ($game)
		{
			$client_id = (string) $game->getApikey();
			$client_secret = (string) $game->get_privkey();
			$api_locale = (string) $game->get_apilocale();
		}

		$has_credentials = ($client_id !== '' && $client_secret !== '');

		// Handle form submissions
		$action = $request->variable('action', '');

		if ($request->is_set_post('submit'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
			}

			if ($action === 'clear_cache')
			{
				$regions = array('us', 'eu', 'kr', 'tw');
				foreach ($regions as $region)
				{
					$cache->destroy('bbguild_wow_oauth_token_' . $region);
				}
				trigger_error($user->lang['WOW_BNET_CACHE_CLEARED'] . adm_back_link($this->u_action));
			}
		}

		// Test connection if requested
		$test_result = '';
		$test_status = '';
		$test_token_preview = '';
		if ($request->is_set_post('test_connection'))
		{
			if (!check_form_key($form_key))
			{
				trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
			}

			if (!$has_credentials)
			{
				$test_status = 'error';
				$test_result = $user->lang['WOW_BNET_NO_CREDENTIALS'];
			}
			else
			{
				$test_region = $request->variable('test_region', 'eu');
				$result = $this->test_oauth_token($client_id, $client_secret, $test_region);
				$test_status = $result['success'] ? 'success' : 'error';
				$test_result = $result['message'];
				$test_token_preview = $result['token_preview'];
			}
		}

		// Check cached tokens for each region
		$regions = array('us', 'eu', 'kr', 'tw');
		foreach ($regions as $region)
		{
			$cached_token = $cache->get('bbguild_wow_oauth_token_' . $region);
			$template->assign_block_vars('region_row', array(
				'REGION'     => strtoupper($region),
				'REGION_KEY' => $region,
				'HAS_TOKEN'  => ($cached_token !== false),
				'TOKEN_PREVIEW' => ($cached_token !== false) ? substr($cached_token, 0, 8) . '...' : '',
			));
		}

		// Mask the client secret for display
		$masked_secret = '';
		if ($client_secret !== '')
		{
			$masked_secret = substr($client_secret, 0, 4) . str_repeat('*', max(0, strlen($client_secret) - 8)) . substr($client_secret, -4);
		}

		$template->assign_vars(array(
			'L_TITLE'           => $user->lang['ACP_WOW_BATTLENET'],
			'L_EXPLAIN'         => $user->lang['ACP_WOW_BATTLENET_EXPLAIN'],
			'CLIENT_ID'         => $client_id,
			'CLIENT_SECRET_MASKED' => $masked_secret,
			'API_LOCALE'        => $api_locale,
			'HAS_CREDENTIALS'   => $has_credentials,
			'S_TEST_RESULT'     => ($test_result !== ''),
			'TEST_STATUS'       => $test_status,
			'TEST_RESULT'       => $test_result,
			'TEST_TOKEN_PREVIEW' => $test_token_preview,
			'U_ACTION'          => $this->u_action,
			'U_EDIT_GAME'       => $this->get_edit_game_url($db),
		));
	}

	/**
	 * Test OAuth token retrieval against Battle.net
	 *
	 * @param string $client_id
	 * @param string $client_secret
	 * @param string $region
	 * @return array ['success' => bool, 'message' => string, 'token_preview' => string]
	 */
	private function test_oauth_token($client_id, $client_secret, $region)
	{
		global $user;

		$token_urls = array(
			'eu' => 'https://oauth.battle.net/token',
			'us' => 'https://oauth.battle.net/token',
			'kr' => 'https://oauth.battle.net/token',
			'tw' => 'https://oauth.battle.net/token',
		);

		if (!isset($token_urls[$region]))
		{
			return array(
				'success' => false,
				'message' => sprintf($user->lang['WOWAPI_REGION_NOTALLOWED']),
				'token_preview' => '',
			);
		}

		$credentials = base64_encode($client_id . ':' . $client_secret);
		$curl = curl_init($token_urls[$region]);

		if ($curl === false)
		{
			return array(
				'success' => false,
				'message' => $user->lang['WOW_BNET_CURL_FAILED'],
				'token_preview' => '',
			);
		}

		curl_setopt_array($curl, array(
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
			CURLOPT_HTTPHEADER     => array(
				'Authorization: Basic ' . $credentials,
				'Content-Type: application/x-www-form-urlencoded',
			),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_FOLLOWLOCATION => true,
		));

		$response = curl_exec($curl);
		$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$curl_error = curl_error($curl);
		curl_close($curl);

		if ($response === false)
		{
			return array(
				'success' => false,
				'message' => sprintf($user->lang['WOW_BNET_CURL_ERROR'], $curl_error),
				'token_preview' => '',
			);
		}

		if ($http_code !== 200)
		{
			$error_data = json_decode($response, true);
			$error_detail = isset($error_data['error_description']) ? $error_data['error_description'] : (isset($error_data['error']) ? $error_data['error'] : 'HTTP ' . $http_code);

			return array(
				'success' => false,
				'message' => sprintf($user->lang['WOW_BNET_AUTH_FAILED'], $http_code, $error_detail),
				'token_preview' => '',
			);
		}

		$data = json_decode($response, true);
		if (!isset($data['access_token']))
		{
			return array(
				'success' => false,
				'message' => $user->lang['WOW_BNET_NO_TOKEN'],
				'token_preview' => '',
			);
		}

		$expires_in = isset($data['expires_in']) ? (int) $data['expires_in'] : 0;
		$hours = round($expires_in / 3600, 1);

		// Cache the token so it appears in the Token Cache table
		global $phpbb_container;
		$cache = $phpbb_container->get('cache');
		$cache->put('bbguild_wow_oauth_token_' . $region, $data['access_token'], $expires_in);

		return array(
			'success' => true,
			'message' => sprintf($user->lang['WOW_BNET_TEST_SUCCESS'], strtoupper($region), $hours),
			'token_preview' => substr($data['access_token'], 0, 12) . '...',
		);
	}

	/**
	 * Load WoW game record from database
	 *
	 * @param \phpbb\db\driver\driver_interface                        $db
	 * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
	 * @return game|null
	 */
	private function get_game($db, $container)
	{
		try
		{
			$game = new game(
				$db,
				$container->get('cache.driver'),
				$container->get('config'),
				$container->get('user'),
				$container->get('ext.manager'),
				$container->getParameter('avathar.bbguild.tables.bb_classes'),
				$container->getParameter('avathar.bbguild.tables.bb_races'),
				$container->getParameter('avathar.bbguild.tables.bb_language'),
				$container->getParameter('avathar.bbguild.tables.bb_factions'),
				$container->getParameter('avathar.bbguild.tables.bb_games')
			);
			$game->game_id = 'wow';
			$game->get_game();
			return $game;
		}
		catch (\Exception $e)
		{
			return null;
		}
	}

	/**
	 * Build URL to the game edit page for WoW
	 *
	 * @param \phpbb\db\driver\driver_interface $db
	 * @return string
	 */
	private function get_edit_game_url($db)
	{
		global $phpbb_admin_path, $phpEx;
		return append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-game_module&amp;mode=editgames&amp;action=editgames&amp;game_id=wow');
	}
}
