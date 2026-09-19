<?php
/**
 * bbGuild WoW Extension — 2.1.0-b1 release checkpoint
 *
 * Converges the seven feature migrations landed under this milestone
 * (add_equipment_detail -> add_player_item_stat -> add_guild_sync_config ->
 * add_news_source -> add_achievement_category_guild_flag ->
 * widen_achievement_description -> add_achievement_track_player_index,
 * already a single linear chain) behind one canonically-named
 * dependency, matching every prior milestone's convention (v200b2, v200b3,
 * v200b4, v200rc2) — so later work depends on the readable milestone name
 * instead of picking whichever feature-specific migration happens to be the
 * current tip. Carries no schema/data change of its own.
 *
 * Canonical version lives in ext::BBGUILDWOW_VERSION; not in phpbb_config.
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\migrations\v210b1;

class release_2_1_0_b1 extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\avathar\bbguildwow\migrations\v210b1\add_achievement_track_player_index'];
	}
}
