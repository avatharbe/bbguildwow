<?php
/**
 * Battle.net WoW Guild API
 *
 * Uses the Game Data API endpoints:
 * - Guild data:   GET /data/wow/guild/{realmSlug}/{nameSlug}
 * - Guild roster:  GET /data/wow/guild/{realmSlug}/{nameSlug}/roster
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
 * Guild resource.
 *
 * @package avathar\bbguildwow\api
 */
class battlenet_guild extends battlenet_resource
{
	/** @var array */
	protected $methods_allowed = array('*');

	/** @var string */
	protected $endpoint = 'data/wow/guild';

	/**
	 * Fetch guild profile data.
	 *
	 * @param string $realm_slug Lowercase hyphenated realm slug
	 * @param string $name_slug  Lowercase hyphenated guild name slug
	 * @return array
	 */
	public function getGuild(string $realm_slug, string $name_slug): array
	{
		global $user;

		if ($name_slug === '')
		{
			throw new battlenet_api_exception($user->lang['WOWAPI_NO_GUILD']);
		}

		if ($realm_slug === '')
		{
			throw new battlenet_api_exception($user->lang['WOWAPI_NO_REALMS']);
		}

		return $this->consume($realm_slug . '/' . $name_slug, array());
	}

	/**
	 * Fetch guild achievements.
	 *
	 * @param string $realm_slug Lowercase hyphenated realm slug
	 * @param string $name_slug  Lowercase hyphenated guild name slug
	 * @return array
	 */
	public function getAchievements(string $realm_slug, string $name_slug): array
	{
		global $user;

		if ($name_slug === '')
		{
			throw new battlenet_api_exception($user->lang['WOWAPI_NO_GUILD']);
		}

		if ($realm_slug === '')
		{
			throw new battlenet_api_exception($user->lang['WOWAPI_NO_REALMS']);
		}

		return $this->consume($realm_slug . '/' . $name_slug . '/achievements', array());
	}

	/**
	 * Fetch guild roster.
	 *
	 * @param string $realm_slug Lowercase hyphenated realm slug
	 * @param string $name_slug  Lowercase hyphenated guild name slug
	 * @return array
	 */
	public function getRoster(string $realm_slug, string $name_slug): array
	{
		global $user;

		if ($name_slug === '')
		{
			throw new battlenet_api_exception($user->lang['WOWAPI_NO_GUILD']);
		}

		if ($realm_slug === '')
		{
			throw new battlenet_api_exception($user->lang['WOWAPI_NO_REALMS']);
		}

		return $this->consume($realm_slug . '/' . $name_slug . '/roster', array());
	}
}
