<?php
/**
 * Battle.net WoW Achievement Media API
 *
 * Achievement detail (battlenet_achievement::getAchievementDetail()) only
 * links to its icon via `media.key.href` -- it never embeds the asset
 * inline (confirmed live against the real API). The actual icon URL comes
 * from this separate endpoint:
 * - GET /data/wow/media/achievement/{achievementId}
 *
 * @package   bbguildwow v2.0
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @author    Andreas Vandenberghe <sajaki@avathar.be>
 * @link      https://develop.battle.net/
 */

namespace avathar\bbguildwow\api;

/**
 * Achievement media resource (Game Data API, static namespace).
 *
 * @package avathar\bbguildwow\api
 */
class battlenet_achievement_media extends battlenet_resource
{
	/** @var array */
	protected $methods_allowed = array('*');

	/** @var string */
	protected $endpoint = 'data/wow/media/achievement';

	/**
	 * Fetch an achievement's media assets (icon) by ID.
	 *
	 * @param int $id Achievement ID
	 * @return array
	 */
	public function getAchievementMedia(int $id): array
	{
		return $this->consume((string) $id, array());
	}
}
