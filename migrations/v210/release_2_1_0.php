<?php
/**
 * bbGuild WoW Extension — squashed migration for the complete 2.1.x (dev) line
 *
 * Consolidates every migration landed under 2.1.0-b1 into a single class,
 * in their true dependency order (confirmed by v210b1\release_2_1_0_b1's
 * own docblock):
 *
 *   - v210b1\add_equipment_detail                 (item enchant/gem/bonus/set columns)
 *   - v210b1\add_player_item_stat                 (bb_player_item_stat table)
 *   - v210b1\add_guild_sync_config                (scheduled roster sync config)
 *   - v210b1\add_news_source                      (bb_news source/source_key columns)
 *   - v210b1\add_achievement_category_guild_flag  (bb_achievement_category.is_guild_category)
 *   - v210b1\widen_achievement_description         (bb_achievement.description -> TEXT_UNI)
 *   - v210b1\add_achievement_track_player_index    (bb_achievement_track idx_player)
 *   - v210b1\release_2_1_0_b1                      (checkpoint only, no schema/data of its own)
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\migrations\v210;

class release_2_1_0 extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return [
			// This extension's own squashed 2.0.x migration (former first
			// dependency: v200b3\add_player_equipment, via add_equipment_detail).
			'\avathar\bbguildwow\migrations\v200\release_2_0_0',
			// bbguild core's squashed 2.0.x migration (former dependency:
			// v200b3\release_2_0_0_b3, via add_news_source). As of writing
			// this migration, bbguild core had NOT yet been squashed; this
			// name is the expected result of that parallel task. Verify/
			// correct if bbguild core landed under a different class name.
			// Transitively implied by the wow v200 dependency above too,
			// but listed explicitly to mirror add_news_source's original
			// direct dependency.
			'\avathar\bbguild\migrations\v200\release_2_0_0',
		];
	}

	/* ------------------------------------------------------------------ */
	/*  effectively_installed                                              */
	/* ------------------------------------------------------------------ */

	public function effectively_installed()
	{
		// Version lives in ext::BBGUILDWOW_VERSION, not phpbb_config; check
		// for the last schema effect of the chain instead — the player-scoped
		// index on bb_achievement_track added by the former
		// add_achievement_track_player_index migration.
		return $this->db_tools->sql_index_exists($this->table_prefix . 'bb_achievement_track', 'idx_player');
	}

	/* ------------------------------------------------------------------ */
	/*  SCHEMA                                                             */
	/* ------------------------------------------------------------------ */

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'bb_player_item_stat' => [
					'COLUMNS' => [
						'player_id'  => ['UINT', 0],
						'slot_type'  => ['VCHAR:30', ''],
						'stat_type'  => ['VCHAR:32', ''],
						'stat_value' => ['UINT', 0],
					],
					'PRIMARY_KEY' => ['player_id', 'slot_type', 'stat_type'],
					'KEYS' => [
						'pid' => ['INDEX', ['player_id']],
					],
				],
			],
			'add_columns' => [
				$this->table_prefix . 'bb_player_equipment' => [
					'enchant_id'   => ['UINT', 0],
					'gem_ids'      => ['VCHAR:255', ''],
					'bonus_ids'    => ['VCHAR:512', ''],
					'set_item_ids' => ['VCHAR:255', ''],
				],
				$this->table_prefix . 'bb_news' => [
					'news_source'     => ['VCHAR:10', 'manual'],
					'news_source_key' => ['VCHAR:64', ''],
				],
				$this->table_prefix . 'bb_achievement_category' => [
					'is_guild_category' => ['BOOL', 0],
				],
			],
			'change_columns' => [
				$this->table_prefix . 'bb_achievement' => [
					'description' => ['TEXT_UNI', ''],
				],
			],
			'add_index' => [
				$this->table_prefix . 'bb_news' => [
					'news_source_key' => ['news_source_key'],
				],
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
				$this->table_prefix . 'bb_news' => ['news_source_key'],
			],
			'change_columns' => [
				$this->table_prefix . 'bb_achievement' => [
					'description' => ['VCHAR_UNI:255', ''],
				],
			],
			'drop_columns' => [
				$this->table_prefix . 'bb_achievement_category' => ['is_guild_category'],
				$this->table_prefix . 'bb_news' => ['news_source', 'news_source_key'],
				$this->table_prefix . 'bb_player_equipment' => [
					'enchant_id', 'gem_ids', 'bonus_ids', 'set_item_ids',
				],
			],
			'drop_tables' => [
				$this->table_prefix . 'bb_player_item_stat',
			],
		];
	}

	/* ------------------------------------------------------------------ */
	/*  DATA — scheduled guild roster sync config                         */
	/* ------------------------------------------------------------------ */

	public function update_data()
	{
		return [
			['config.add', ['bbguild_wow_sync_enabled', 0]],
			['config.add', ['bbguild_wow_sync_interval', 21600]],
			['config.add', ['bbguild_wow_last_sync', 0]],
			['config.add', ['bbguild_wow_last_sync_result', '']],
		];
	}
}
