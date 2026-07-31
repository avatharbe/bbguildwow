<?php
/**
 * bbGuild WoW Extension — enrich player equipment with full item detail
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\migrations\v210b1;

class add_equipment_detail extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\avathar\bbguildwow\migrations\v200b3\add_player_equipment'];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'bb_player_equipment' => [
					'enchant_id'   => ['UINT', 0],
					'gem_ids'      => ['VCHAR:255', ''],
					'bonus_ids'    => ['VCHAR:512', ''],
					'set_item_ids' => ['VCHAR:255', ''],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'bb_player_equipment' => [
					'enchant_id', 'gem_ids', 'bonus_ids', 'set_item_ids',
				],
			],
		];
	}
}
