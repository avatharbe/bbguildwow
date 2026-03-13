<?php
/**
 * bbGuild WoW Battle.net API
 *
 * @package   bbguild_wow v2.0
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @author    Andreas Vandenberghe <sajaki@avathar.be>
 * @author    Chris Saylor
 * @author    Daniel Cannon <daniel@danielcannon.co.uk>
 * @copyright Copyright (c) 2011, 2015 Chris Saylor, Daniel Cannon, Andreas Vandenberghe
 * @link      https://develop.battle.net/
 */

namespace avathar\bbguild_wow\api;

/**
 * Battle.net WoW API PHP SDK
 *
 * Factory class that creates the appropriate resource instance.
 *
 * @package avathar\bbguild_wow\api
 */
class battlenet
{
	/** @var \phpbb\cache\service */
	public $cache;

	/** @var array */
	protected $region = array('us', 'eu', 'kr', 'tw');

	/** @var array */
	protected $api = array('guild', 'realm', 'character', 'achievement', 'achievement-category', 'playable-data');

	/** @var battlenet_realm */
	public $Realm;

	/** @var battlenet_guild */
	public $guild;

	/** @var battlenet_achievement */
	public $achievement;

	/** @var battlenet_character */
	public $character;

	/** @var battlenet_achievement_category */
	public $achievement_category;

	/** @var battlenet_static_data */
	public $static_data;

	/** @var string */
	public $locale;

	/** @var string */
	public $apikey;

	/** @var int */
	private $cacheTtl;

	/**
	 * Namespace types per resource.
	 * @see https://develop.battle.net/documentation/guides/using-oauth
	 */
	private $namespace_types = array(
		'realm'          => 'dynamic',
		'guild'          => 'profile',
		'character'      => 'profile',
		'achievement'            => 'static',
		'achievement-category'   => 'static',
		'playable-data'          => 'static',
	);

	/**
	 * @param string               $API
	 * @param string               $region
	 * @param string               $apikey    Client ID
	 * @param string               $locale
	 * @param string               $privkey   Client Secret
	 * @param string               $ext_path
	 * @param \phpbb\cache\service $cache
	 * @param int                  $cacheTtl
	 */
	public function __construct($API, $region, $apikey, $locale, $privkey, $ext_path, \phpbb\cache\service $cache, $cacheTtl = 3600)
	{
		global $user;

		if (!in_array($API, $this->api))
		{
			trigger_error($user->lang['WOWAPI_API_NOTIMPLEMENTED']);
		}

		if (!in_array($region, $this->region))
		{
			trigger_error($user->lang['WOWAPI_REGION_NOTALLOWED']);
		}

		$this->api      = $API;
		$this->region   = $region;
		$this->ext_path = $ext_path;
		$this->cache    = $cache;
		$this->cacheTtl = $cacheTtl;

		$namespace_type = isset($this->namespace_types[$this->api]) ? $this->namespace_types[$this->api] : 'dynamic';

		switch ($this->api)
		{
			case 'realm':
				$this->Realm = new battlenet_realm($this->cache, $region, $this->cacheTtl);
				$this->Realm->apikey = $apikey;
				$this->Realm->locale = $locale;
				$this->Realm->privkey = $privkey;
				$this->Realm->namespace_type = $namespace_type;
				break;
			case 'guild':
				$this->guild = new battlenet_guild($this->cache, $region, $this->cacheTtl);
				$this->guild->apikey = $apikey;
				$this->guild->locale = $locale;
				$this->guild->privkey = $privkey;
				$this->guild->namespace_type = $namespace_type;
				break;
			case 'character':
				$this->character = new battlenet_character($this->cache, $region, $this->cacheTtl);
				$this->character->apikey = $apikey;
				$this->character->locale = $locale;
				$this->character->privkey = $privkey;
				$this->character->namespace_type = $namespace_type;
				break;
			case 'achievement':
				$this->achievement = new battlenet_achievement($this->cache, $region, $this->cacheTtl);
				$this->achievement->apikey = $apikey;
				$this->achievement->locale = $locale;
				$this->achievement->privkey = $privkey;
				$this->achievement->namespace_type = $namespace_type;
				break;
			case 'achievement-category':
				$this->achievement_category = new battlenet_achievement_category($this->cache, $region, $this->cacheTtl);
				$this->achievement_category->apikey = $apikey;
				$this->achievement_category->locale = $locale;
				$this->achievement_category->privkey = $privkey;
				$this->achievement_category->namespace_type = $namespace_type;
				break;
			case 'playable-data':
				$this->static_data = new battlenet_static_data($this->cache, $region, $this->cacheTtl);
				$this->static_data->apikey = $apikey;
				$this->static_data->locale = $locale;
				$this->static_data->privkey = $privkey;
				$this->static_data->namespace_type = $namespace_type;
				break;
		}
	}
}
