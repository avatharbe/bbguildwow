<?php
/**
 * bbGuild WoW Extension — 2.1.0 migration
 *
 * Adds item enchant/gem/bonus/set columns, the bb_player_item_stat table,
 * scheduled roster sync config, bb_news source/source_key columns,
 * bb_achievement_category.is_guild_category, widens
 * bb_achievement.description to TEXT_UNI, and adds an index on
 * bb_achievement_track.
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
			'\avathar\bbguildwow\migrations\v200\release_2_0_0',
			'\avathar\bbguild\migrations\v200\release_2_0_0',
		];
	}

	/* ------------------------------------------------------------------ */
	/*  effectively_installed                                              */
	/* ------------------------------------------------------------------ */

	public function effectively_installed()
	{
		// Version lives in ext::BBGUILDWOW_VERSION, not phpbb_config; check
		// for the player-scoped index on bb_achievement_track instead.
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
