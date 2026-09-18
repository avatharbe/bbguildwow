<?php
/**
 * bbGuild WoW Extension — widen bb_achievement.description
 *
 * Live crash while backfilling icons (bbguildwow#44 follow-up): some
 * achievement descriptions from the Battle.net API exceed 255
 * characters (e.g. multi-boss raid achievements listing several boss
 * names), and MySQL strict mode turns that into a hard SQL error
 * ("Data too long for column 'description'") instead of a silent
 * truncation -- which crashed the whole request, not just that one
 * row, and would keep crashing on the same achievement every future
 * sync run since its icon/points never got a chance to be written.
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\migrations\v210b1;

class widen_achievement_description extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\avathar\bbguildwow\migrations\v210b1\add_achievement_category_guild_flag'];
	}

	public function update_schema()
	{
		return [
			'change_columns' => [
				$this->table_prefix . 'bb_achievement' => [
					'description' => ['TEXT_UNI', ''],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'change_columns' => [
				$this->table_prefix . 'bb_achievement' => [
					'description' => ['VCHAR_UNI:255', ''],
				],
			],
		];
	}
}
