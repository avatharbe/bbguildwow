<?php
/**
 * bbGuild WoW Extension — squashed migration for 2.0.0-b2
 *
 * Combines all schema, data-seeding, and module registration
 * from the former basics/ and v200b1/ migrations into one file.
 *
 * @package   avathar\bbguild_wow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild_wow\migrations\v200b2;

class release_2_0_0_b2 extends \phpbb\db\migration\container_aware_migration
{
	public static function depends_on()
	{
		return [
			'\phpbb\db\migration\data\v320\v320',
			'\avathar\bbguild\migrations\basics\schema',
		];
	}

	/* ------------------------------------------------------------------ */
	/*  effectively_installed                                              */
	/* ------------------------------------------------------------------ */

	public function effectively_installed()
	{
		return isset($this->config['bbguild_wow_version'])
			&& version_compare($this->config['bbguild_wow_version'], '2.0.0-b2', '>=');
	}

	/* ------------------------------------------------------------------ */
	/*  SCHEMA                                                             */
	/* ------------------------------------------------------------------ */

	public function update_schema()
	{
		return [
			'add_tables' => [
				/* Achievement tables */
				$this->table_prefix . 'bb_achievement' => [
					'COLUMNS' => [
						'id'              => ['UINT', 0],
						'game_id'         => ['VCHAR:10', ''],
						'title'           => ['VCHAR_UNI:255', ''],
						'points'          => ['UINT', 0],
						'description'     => ['VCHAR_UNI:255', ''],
						'icon'            => ['VCHAR_UNI:255', ''],
						'factionid'       => ['BOOL', 0],
						'reward'          => ['VCHAR_UNI:255', ''],
						'category_id'     => ['UINT', 0],
					],
					'PRIMARY_KEY' => 'id',
					'KEYS' => [
						'idx_category' => ['INDEX', ['category_id']],
					],
				],
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
				$this->table_prefix . 'bb_achievement_criteria' => [
					'COLUMNS' => [
						'criteria_id'   => ['UINT', 0],
						'description'   => ['VCHAR_UNI:255', ''],
						'orderIndex'    => ['UINT', 0],
						'max'           => ['TIMESTAMP', 0],
					],
					'PRIMARY_KEY' => 'criteria_id',
				],
				$this->table_prefix . 'bb_achievement_rewards' => [
					'COLUMNS' => [
						'rewards_item_id' => ['UINT', 0],
						'description'     => ['VCHAR_UNI:255', ''],
						'itemlevel'       => ['UINT', 0],
						'quality'         => ['UINT', 0],
						'icon'            => ['VCHAR:255', ''],
					],
					'PRIMARY_KEY' => 'rewards_item_id',
				],
				$this->table_prefix . 'bb_relations_table' => [
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
				$this->table_prefix . 'bb_achievement_track' => [
					'COLUMNS' => [
						'guild_id'               => ['USINT', 0],
						'player_id'              => ['UINT', 0],
						'achievement_id'         => ['UINT', 0],
						'achievements_completed' => ['BINT', 0],
					],
					'PRIMARY_KEY' => ['guild_id', 'player_id', 'achievement_id'],
				],
				$this->table_prefix . 'bb_criteria_track' => [
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
				/* WoW-specific guild fields */
				$this->table_prefix . 'bb_guild_wow' => [
					'COLUMNS' => [
						'guild_id'           => ['USINT', 0],
						'battlegroup'        => ['VCHAR:255', ''],
						'level'              => ['UINT', 0],
						'achievementpoints'  => ['UINT', 0],
						'guildarmoryurl'     => ['VCHAR:255', ''],
					],
					'PRIMARY_KEY' => ['guild_id'],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_tables' => [
				$this->table_prefix . 'bb_achievement',
				$this->table_prefix . 'bb_achievement_category',
				$this->table_prefix . 'bb_achievement_criteria',
				$this->table_prefix . 'bb_achievement_rewards',
				$this->table_prefix . 'bb_relations_table',
				$this->table_prefix . 'bb_achievement_track',
				$this->table_prefix . 'bb_criteria_track',
				$this->table_prefix . 'bb_guild_wow',
			],
		];
	}

	/* ------------------------------------------------------------------ */
	/*  DATA — config, game seeding, ACP modules, version stamp            */
	/* ------------------------------------------------------------------ */

	public function update_data()
	{
		return [
			['config.add', ['bbguild_show_achiev', 0]],
			['config.add', ['bbguild_achiev_hide_empty', 1]],
			['custom', [[$this, 'seed_game_data']]],
			['module.add', ['acp', 'ACP_BBGUILD_PLAYER', [
				'module_basename' => '\avathar\bbguild_wow\acp\achievement_module',
				'modes'           => ['addachievement', 'listachievements'],
			]]],
			['module.add', ['acp', 'ACP_BBGUILD_MAINPAGE', [
				'module_basename' => '\avathar\bbguild_wow\acp\battlenet_module',
				'modes'           => ['battlenet'],
			]]],
			['custom', [[$this, 'set_version']]],
		];
	}

	public function revert_data()
	{
		return [
			['config.remove', ['bbguild_wow_version']],
			['module.remove', ['acp', 'ACP_BBGUILD_MAINPAGE', [
				'module_basename' => '\avathar\bbguild_wow\acp\battlenet_module',
			]]],
			['module.remove', ['acp', 'ACP_BBGUILD_PLAYER', [
				'module_basename' => '\avathar\bbguild_wow\acp\achievement_module',
			]]],
			['config.remove', ['bbguild_show_achiev']],
			['config.remove', ['bbguild_achiev_hide_empty']],
			['custom', [[$this, 'remove_game_data']]],
			['custom', [[$this, 'remove_wow_players_and_guilds']]],
		];
	}

	/* ------------------------------------------------------------------ */
	/*  Helpers                                                            */
	/* ------------------------------------------------------------------ */

	public function set_version()
	{
		$this->config->set('bbguild_wow_version', '2.0.0-b2');
	}

	public function seed_game_data()
	{
		$installer = $this->get_installer();
		$installer->install($this->get_table_names(), 'wow', 'World of Warcraft', '', '', 'us');
	}

	public function remove_game_data()
	{
		$installer = $this->get_installer();
		$installer->uninstall($this->get_table_names(), 'wow', 'World of Warcraft');
	}

	public function remove_wow_players_and_guilds()
	{
		$players_table   = $this->table_prefix . 'bb_players';
		$guild_table     = $this->table_prefix . 'bb_guild';
		$ranks_table     = $this->table_prefix . 'bb_ranks';
		$guild_wow_table = $this->table_prefix . 'bb_guild_wow';

		// Delete WoW players
		$this->db->sql_query("DELETE FROM $players_table WHERE game_id = 'wow'");

		// Get WoW guild IDs (guilds that have no remaining players from other games)
		$sql = "SELECT g.id FROM $guild_table g
			WHERE g.id > 0
			AND g.game_id = 'wow'
			AND NOT EXISTS (
				SELECT 1 FROM $players_table p
				WHERE p.player_guild_id = g.id AND p.game_id <> 'wow'
			)";
		$result = $this->db->sql_query($sql);
		$guild_ids = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$guild_ids[] = (int) $row['id'];
		}
		$this->db->sql_freeresult($result);

		if (!empty($guild_ids))
		{
			$this->db->sql_query('DELETE FROM ' . $ranks_table .
				' WHERE ' . $this->db->sql_in_set('guild_id', $guild_ids));
			$this->db->sql_query('DELETE FROM ' . $guild_table .
				' WHERE ' . $this->db->sql_in_set('id', $guild_ids));
		}

		// Clean up guild_wow table
		if ($this->db_tools->sql_table_exists($guild_wow_table))
		{
			$this->db->sql_query("DELETE FROM $guild_wow_table");
		}

		// Clean up downloaded portraits
		$upload_path = $this->config['upload_path'];
		$portrait_dir = $this->phpbb_root_path . $upload_path . '/bbguild_wow/';
		if (is_dir($portrait_dir))
		{
			$files = glob($portrait_dir . 'portraits/*.jpg');
			if ($files)
			{
				array_map('unlink', $files);
			}
			@rmdir($portrait_dir . 'portraits');
			@rmdir($portrait_dir);
		}
	}

	private function get_installer()
	{
		return new \avathar\bbguild_wow\game\wow_installer(
			$this->container->get('dbal.conn'),
			$this->container->get('cache.driver'),
			$this->container->get('config'),
			$this->container->get('user')
		);
	}

	private function get_table_names()
	{
		return [
			'bb_games_table'     => $this->table_prefix . 'bb_games',
			'bb_factions_table'  => $this->table_prefix . 'bb_factions',
			'bb_classes_table'   => $this->table_prefix . 'bb_classes',
			'bb_races_table'     => $this->table_prefix . 'bb_races',
			'bb_gameroles_table' => $this->table_prefix . 'bb_gameroles',
			'bb_language_table'  => $this->table_prefix . 'bb_language',
			'bb_players_table'   => $this->table_prefix . 'bb_players',
		];
	}
}
