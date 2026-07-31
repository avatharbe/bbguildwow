<?php
/**
 * bbGuild WoW Extension — per-item stat rows (queryable)
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\migrations\v210b1;

class add_player_item_stat extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\avathar\bbguildwow\migrations\v210b1\add_equipment_detail'];
	}

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
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [$this->table_prefix . 'bb_player_item_stat'],
		];
	}
}
