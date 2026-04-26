<?php
/**
 * Battle.net WoW API PHP SDK
 *
 * @package   bbguildwow v2.0
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @author    Andreas Vandenberghe <sajaki@avathar.be>
 * @author    Chris Saylor
 * @author    Daniel Cannon <daniel@danielcannon.co.uk>
 * @copyright Copyright (c) 2011, 2015 Chris Saylor, Daniel Cannon, Andreas Vandenberghe
 * @link      https://develop.battle.net/
 */

namespace avathar\bbguildwow\api;

/**
 * Resource skeleton
 *
 * Uses OAuth 2.0 Client Credentials Grant for authentication.
 *
 * @package avathar\bbguildwow\api
 */
abstract class battlenet_resource
{
	/**
	 * List of region API base URLs
	 *
	 * @var array
	 */
	protected $api_url = array(
		'eu'  => 'https://eu.api.blizzard.com/',
		'us'  => 'https://us.api.blizzard.com/',
		'kr'  => 'https://kr.api.blizzard.com/',
		'tw'  => 'https://tw.api.blizzard.com/',
	);

	/**
	 * OAuth 2.0 token endpoints per region
	 *
	 * @var array
	 */
	protected $token_url = array(
		'eu'  => 'https://oauth.battle.net/token',
		'us'  => 'https://oauth.battle.net/token',
		'kr'  => 'https://oauth.battle.net/token',
		'tw'  => 'https://oauth.battle.net/token',
		'cn'  => 'https://oauth.battlenet.com.cn/token',
	);

	/**
	 * List of possible locales
	 *
	 * @var array
	 */
	protected $locales_allowed = array(
		'eu'  => array('en_GB', 'de_DE', 'es_ES', 'fr_FR', 'it_IT', 'pl_PL', 'pt_PT', 'ru_RU'),
		'us'  => array('en_US', 'es_MX', 'pt_BR'),
		'kr'  => array('ko_KR'),
		'tw'  => array('zh_TW'),
	);

	/** @var string */
	public $region;

	/** @var string */
	public $locale;

	/** @var string Client ID (stored as apikey in bb_games) */
	public $apikey;

	/** @var string Client Secret (stored as privkey in bb_games) */
	public $privkey;

	/** @var string Namespace type: 'static', 'dynamic', or 'profile' */
	public $namespace_type = 'dynamic';

	/** @var string Game edition: 'retail', 'classic_era', 'classic_prog', 'classic_ann' */
	public $edition = 'retail';

	/**
	 * Edition-to-namespace infix mapping.
	 * Retail has no infix; Classic editions insert between type and region.
	 */
	const EDITION_INFIXES = array(
		'retail'       => '',
		'classic_era'  => 'classic1x',
		'classic_prog' => 'classic',
		'classic_ann'  => 'classicann',
	);

	/** @var array */
	protected $methods_allowed;

	/** @var int */
	private $cacheTtl;

	/** @var \phpbb\cache\service */
	public $cache;

	/** @var string */
	protected $endpoint;

	/**
	 * @param \phpbb\cache\service $cache
	 * @param string               $region
	 * @param int                  $cacheTtl
	 */
	public function __construct(\phpbb\cache\service $cache, $region = 'us', $cacheTtl = 3600)
	{
		global $user;

		if (empty($this->methods_allowed))
		{
			trigger_error($user->lang['NO_METHODS']);
		}
		$this->region = $region;
		$this->cache = $cache;
		$this->cacheTtl = $cacheTtl;
	}

	/**
	 * Fetch an OAuth 2.0 access token using Client Credentials Grant.
	 *
	 * Caches the token using phpBB cache with key bbguild_wow_oauth_token_{region}.
	 *
	 * @param bool $force_refresh Force a new token even if cached
	 * @return string|false Access token or false on failure
	 */
	protected function fetch_oauth_token($force_refresh = false)
	{
		$cache_key = 'bbguild_wow_oauth_token_' . $this->region;

		if (!$force_refresh)
		{
			$cached = $this->cache->get($cache_key);
			if ($cached !== false)
			{
				return $cached;
			}
		}

		if (!isset($this->token_url[$this->region]))
		{
			return false;
		}

		$token_endpoint = $this->token_url[$this->region];
		$credentials = base64_encode($this->apikey . ':' . $this->privkey);

		$curl = curl_init($token_endpoint);
		if ($curl === false)
		{
			return false;
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
		curl_close($curl);

		if ($response === false || $http_code !== 200)
		{
			return false;
		}

		$data = json_decode($response, true);
		if (!isset($data['access_token']))
		{
			return false;
		}

		// Cache for slightly less than the actual expiry (default 86400s = 24h)
		$ttl = isset($data['expires_in']) ? (int) $data['expires_in'] - 300 : 82800;
		$this->cache->put($cache_key, $data['access_token'], $ttl);

		return $data['access_token'];
	}

	/**
	 * Build the Battlenet-Namespace header value.
	 *
	 * For retail: "profile-eu", "static-us"
	 * For Classic: "profile-classic-eu", "static-classic1x-us"
	 *
	 * @return string
	 */
	protected function get_namespace()
	{
		$infix = isset(self::EDITION_INFIXES[$this->edition]) ? self::EDITION_INFIXES[$this->edition] : '';
		if ($infix !== '')
		{
			return $this->namespace_type . '-' . $infix . '-' . $this->region;
		}
		return $this->namespace_type . '-' . $this->region;
	}

	/**
	 * Consumes the resource by method and returns the results of the request.
	 *
	 * Uses OAuth 2.0 Bearer token authentication and the Battlenet-Namespace header.
	 *
	 * @param  string $method Request method
	 * @param  array  $params Parameters
	 * @return array Request data
	 */
	public function consume($method, array $params)
	{
		global $user;

		if ($this->apikey == '' || $this->privkey == '')
		{
			trigger_error($user->lang['WOWAPI_KEY_MISSING']);
		}

		if (!isset($this->locales_allowed[$this->region]) || !in_array($this->locale, $this->locales_allowed[$this->region]))
		{
			if ($this->region != '' && isset($this->locales_allowed[$this->region]))
			{
				$this->locale = $this->locales_allowed[$this->region][0];
			}
			else
			{
				trigger_error(sprintf($user->lang['WOWAPI_LOCALE_NOTALLOWED'], (string) $this->locale));
			}
		}

		if (!in_array($method, $this->methods_allowed) && !in_array('*', $this->methods_allowed))
		{
			trigger_error($user->lang['WOWAPI_METH_NOTALLOWED']);
		}

		if (!isset($this->api_url[$this->region]))
		{
			trigger_error(sprintf($user->lang['WOWAPI_LOCALE_NOTALLOWED'], (string) $this->region));
		}

		$requestUri = $this->api_url[$this->region];
		$requestUri .= $this->endpoint . '/' . $method;
		$requestUri .= '?namespace=' . $this->get_namespace();
		$requestUri .= '&locale=' . $this->locale;

		if (isset($params['data']) && !empty($params['data']))
		{
			if (is_array($params['data']))
			{
				$requestUri .= '&' . http_build_query($params['data']);
			}
			else
			{
				$requestUri .= '&' . $params['data'];
			}
		}

		$cachesignature = 'bbguildwow_api_' . base64_encode($requestUri);

		if (!$data = $this->_getCachedResult($cachesignature))
		{
			$data = $this->_authenticatedRequest($requestUri);

			// On 401, invalidate token and retry once
			if (isset($data['response_headers']['http_code']) && $data['response_headers']['http_code'] == 401)
			{
				$data = $this->_authenticatedRequest($requestUri, true);
			}

			// Only cache successful responses (2xx), not errors
			if ($data !== false)
			{
				$http_code = isset($data['response_headers']['http_code']) ? (int) $data['response_headers']['http_code'] : 0;
				if ($http_code >= 200 && $http_code < 300)
				{
					$this->cache->put($cachesignature, $data, $this->cacheTtl);
				}
			}
		}

		// Store the request URL in the response for debugging
		$data['request_url'] = $requestUri;

		return $data;
	}

	/**
	 * Make an authenticated API request with OAuth 2.0 Bearer token.
	 *
	 * @param string $url           Full request URL
	 * @param bool   $force_refresh Force token refresh
	 * @return array|false Response data or false on failure
	 */
	protected function _authenticatedRequest($url, $force_refresh = false)
	{
		$token = $this->fetch_oauth_token($force_refresh);
		if ($token === false)
		{
			global $user;
			trigger_error($user->lang['WOWAPI_TOKEN_FAILED']);
		}

		$curl = curl_init($url);
		if ($curl === false)
		{
			return false;
		}

		curl_setopt_array($curl, array(
			CURLOPT_HTTPHEADER     => array(
				'Authorization: Bearer ' . $token,
				'Battlenet-Namespace: ' . $this->get_namespace(),
			),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 60,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_USERAGENT      => 'bbGuild/2.0 (phpBB)',
		));

		$response = curl_exec($curl);
		$headers = curl_getinfo($curl);
		curl_close($curl);

		$data = array(
			'response'         => '',
			'response_headers' => (array) $headers,
			'error'            => '',
		);

		if ($response !== false && $response !== '')
		{
			$decoded = json_decode($response, true);
			if (json_last_error() === JSON_ERROR_NONE)
			{
				$data['response'] = $decoded;

				// Check for API error responses
				if (isset($decoded['code']) && isset($decoded['detail']))
				{
					$data['error'] = $decoded['detail'];
				}
			}
			else
			{
				$data['response'] = $response;
			}
		}

		return $data;
	}

	/**
	 * @param string $cachesignature
	 * @return bool|mixed
	 */
	protected function _getCachedResult($cachesignature)
	{
		if (!$this->cache->get($cachesignature))
		{
			return false;
		}
		return $this->cache->get($cachesignature);
	}
}
