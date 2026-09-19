<?php
/**
 * bbGuild WoW Extension — index bb_achievement_track for player-scoped lookups
 *
 * bb_achievement_track's only index is its own PRIMARY KEY
 * (guild_id, player_id, achievement_id) -- since player_id isn't the
 * leading column, MySQL can't use it for a player-only lookup, so every
 * player-scoped query (the achievements tab's own reads, and the
 * guild-batch player-achievement sync's own dedup check) does a full
 * table scan of the WHOLE table across every guild on the board, not
 * just the one being queried. Confirmed live via EXPLAIN: type=ALL,
 * key=(none), scanning all 13517 rows for a single player's lookup.
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\migrations\v210b1;

class add_achievement_track_player_index extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\avathar\bbguildwow\migrations\v210b1\widen_achievement_description'];
	}

	public function update_schema()
	{
		return [
			'add_index' => [
				$this->table_prefix . 'bb_achievement_track' => [
					'idx_player' => ['player_id'],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_keys' => [
				$this->table_prefix . 'bb_achievement_track' => ['idx_player'],
			],
		];
	}
}
