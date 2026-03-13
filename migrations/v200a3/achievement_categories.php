<?php
/**
 * bbGuild WoW plugin - Achievement categories migration
 *
 * @package   avathar\bbguild_wow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild_wow\migrations\v200a3;

class achievement_categories extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\avathar\bbguild_wow\migrations\v200a2\battlenet_module'];
	}

	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'bb_achievement_category');
	}

	public function update_schema()
	{
		return [
			'add_tables' => [
				$this->table_prefix . 'bb_achievement_category' => [
					'COLUMNS' => [
						'id'            => ['UINT', 0],
						'game_id'       => ['VCHAR:10', ''],
						'parent_id'     => ['UINT', 0],
						'name'          => ['VCHAR_UNI:255', ''],
						'display_order' => ['USINT', 0],
					],
					'PRIMARY_KEY' => 'id',
					'KEYS' => [
						'idx_parent' => ['INDEX', ['parent_id']],
					],
				],
			],
			'add_columns' => [
				$this->table_prefix . 'bb_achievement' => [
					'category_id' => ['UINT', 0, 'after' => 'reward'],
				],
			],
			'add_index' => [
				$this->table_prefix . 'bb_achievement' => [
					'idx_category' => ['category_id'],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . 'bb_achievement_category',
			],
			'drop_keys' => [
				$this->table_prefix . 'bb_achievement' => [
					'idx_category',
				],
			],
			'drop_columns' => [
				$this->table_prefix . 'bb_achievement' => [
					'category_id',
				],
			],
		];
	}
}
