<?php
/**
 * Battle.net WoW Achievement Category API
 *
 * Uses the Game Data API endpoints:
 * - Achievement Category Index: GET /data/wow/achievement-category/index
 * - Achievement Category Detail: GET /data/wow/achievement-category/{id}
 *
 * @package   bbguild_wow v2.0
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @author    Andreas Vandenberghe <sajaki@avathar.be>
 * @link      https://develop.battle.net/
 */

namespace avathar\bbguild_wow\api;

/**
 * Achievement Category resource (Game Data API, static namespace).
 *
 * @package avathar\bbguild_wow\api
 */
class battlenet_achievement_category extends battlenet_resource
{
	/** @var array */
	protected $methods_allowed = array('*');

	/** @var string */
	protected $endpoint = 'data/wow/achievement-category';

	/**
	 * Fetch the achievement category index (all root + child categories).
	 *
	 * @return array
	 */
	public function getCategoryIndex(): array
	{
		return $this->consume('index', array());
	}

	/**
	 * Fetch detail for a single achievement category (includes achievement list).
	 *
	 * @param int $id Category ID
	 * @return array
	 */
	public function getCategoryDetail(int $id): array
	{
		return $this->consume((string) $id, array());
	}
}
