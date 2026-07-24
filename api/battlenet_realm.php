<?php
/**
 * Battle.net WoW Realm API
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
 * Realm resource.
 *
 * @package avathar\bbguildwow\api
 */
class battlenet_realm extends battlenet_resource
{
	/** @var array */
	protected $methods_allowed = array('status');

	/** @var string */
	protected $endpoint = 'realm';

	/**
	 * Get status results for all realms.
	 *
	 * @return array
	 */
	public function getAllRealmStatus()
	{
		return $this->consume('status', array());
	}

	/**
	 * Get status results for specified realm(s).
	 *
	 * @param  array $realms
	 * @return mixed
	 */
	public function getRealmStatus(array $realms)
	{
		global $user;
		$data = array();

		if (count($realms) == 0)
		{
			throw new battlenet_api_exception($user->lang['WOWAPI_NO_REALMS']);
		}
		else
		{
			$realm_str = 'realms=';
			foreach ($realms as $key => $realm)
			{
				$realm_str .= ($key == 0 ? '' : ',') . rawurlencode($realm);
			}
			$data = $this->consume(
				'status', array(
					'data' => $realm_str,
				)
			);
		}
		return $data;
	}
}
