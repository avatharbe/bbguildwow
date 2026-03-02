<?php
/**
 * bbGuild WoW plugin - Achievement schema migration
 *
 * @package   avathar\bbguild_wow
 * @copyright 2018 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild_wow\migrations\basics;

class schema extends \phpbb\db\migration\migration
{
	protected $achievement_table;
	protected $achievement_track_table;
	protected $achievement_criteria_table;
	protected $achievement_rewards_table;
	protected $bb_relations_table;
	protected $criteria_track_table;

	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v320\v320'];
	}

	public function effectively_installed()
	{
		return $this->db_tools->sql_table_exists($this->table_prefix . 'bb_achievement');
	}

	public function update_schema()
	{
		$this->GetTablenames();

		return [
			'add_tables' => [
				/* 1 - achievements */
				$this->achievement_table => [
					'COLUMNS' => [
						'id'              => ['UINT', 0],
						'game_id'         => ['VCHAR:10', ''],
						'title'           => ['VCHAR_UNI:255', ''],
						'points'          => ['UINT', 0],
						'description'     => ['VCHAR_UNI:255', ''],
						'icon'            => ['VCHAR_UNI:255', ''],
						'factionid'       => ['BOOL', 0],
						'reward'          => ['VCHAR_UNI:255', ''],
					],
					'PRIMARY_KEY' => 'id',
				],
				/* 2 - achievement criteria */
				$this->achievement_criteria_table => [
					'COLUMNS' => [
						'criteria_id'   => ['UINT', 0],
						'description'   => ['VCHAR_UNI:255', ''],
						'orderIndex'    => ['UINT', 0],
						'max'           => ['TIMESTAMP', 0],
					],
					'PRIMARY_KEY' => 'criteria_id',
				],
				/* 3 - achievement rewards */
				$this->achievement_rewards_table => [
					'COLUMNS' => [
						'rewards_item_id' => ['UINT', 0],
						'description'     => ['VCHAR_UNI:255', ''],
						'itemlevel'       => ['UINT', 0],
						'quality'         => ['UINT', 0],
						'icon'            => ['VCHAR:255', ''],
					],
					'PRIMARY_KEY' => 'rewards_item_id',
				],
				/* 4 - achievement/criteria/rewards relations */
				$this->bb_relations_table => [
					'COLUMNS' => [
						'id'           => ['UINT', null, 'auto_increment'],
						'attribute_id' => ['VCHAR:3', ''],
						'rel_attr_id'  => ['VCHAR:3', ''],
						'att_value'    => ['UINT', 0],
						'rel_value'    => ['UINT', 0],
					],
					'PRIMARY_KEY' => 'id',
					'KEYS'        => ['UQ01' => ['UNIQUE', ['attribute_id', 'rel_attr_id', 'att_value', 'rel_value']]],
				],
				/* 5 - achievement tracking */
				$this->achievement_track_table => [
					'COLUMNS' => [
						'guild_id'               => ['USINT', 0],
						'player_id'              => ['UINT', 0],
						'achievement_id'         => ['UINT', 0],
						'achievements_completed' => ['BINT', 0],
					],
					'PRIMARY_KEY' => ['guild_id', 'player_id', 'achievement_id'],
				],
				/* 6 - criteria tracking */
				$this->criteria_track_table => [
					'COLUMNS' => [
						'guild_id'           => ['USINT', 0],
						'player_id'          => ['UINT', 0],
						'criteria_id'        => ['UINT', 0],
						'criteria_quantity'   => ['BINT', 0],
						'criteria_created'    => ['BINT', 0],
						'criteria_timestamp'  => ['BINT', 0],
					],
					'PRIMARY_KEY' => ['guild_id', 'player_id', 'criteria_id'],
				],
			],
		];
	}

	public function revert_schema()
	{
		$this->GetTablenames();

		return [
			'drop_tables' => [
				$this->achievement_table,
				$this->achievement_criteria_table,
				$this->achievement_rewards_table,
				$this->bb_relations_table,
				$this->achievement_track_table,
				$this->criteria_track_table,
			],
		];
	}

	private function GetTablenames()
	{
		$this->achievement_table          = $this->table_prefix . 'bb_achievement';
		$this->achievement_track_table    = $this->table_prefix . 'bb_achievement_track';
		$this->achievement_criteria_table = $this->table_prefix . 'bb_achievement_criteria';
		$this->achievement_rewards_table  = $this->table_prefix . 'bb_achievement_rewards';
		$this->bb_relations_table         = $this->table_prefix . 'bb_relations_table';
		$this->criteria_track_table       = $this->table_prefix . 'bb_criteria_track';
	}
}
