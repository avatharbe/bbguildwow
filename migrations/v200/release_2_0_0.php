<?php
/**
 * bbGuild WoW Extension — squashed migration for the complete 2.0.x line
 *
 * Consolidates every migration that shipped under 2.0.0-b2 through
 * 2.0.0-rc2 into a single class:
 *
 *   - v200b2\release_2_0_0_b2          (achievement/guild schema, game seed, ACP modules)
 *   - v200b3\add_player_equipment      (bb_player_equipment table)
 *   - v200b3\add_player_render_url     (bb_players.player_render_url column)
 *   - v200b3\seed_specializations      (backfill bb_specializations for existing installs, #331)
 *   - v200b3\seed_spec_translations    (backfill bb_language spec translations, #26)
 *   - v200b3\release_2_0_0_b3          (checkpoint only, no schema/data of its own)
 *   - v200b4\release_2_0_0_b4          (drop legacy bbguild_wow_version config row)
 *   - v200rc2\release_2_0_0_rc2        (re-parent battlenet_module under Game settings)
 *
 * The original chain first parented the "BattleNet API" ACP module under
 * ACP_BBGUILD_MAINPAGE and later moved it to ACP_BBGUILD_GAMESETTINGS once
 * bbguild core created that category (core's rc4). Since this squashed
 * migration depends on bbguild core's fully-squashed 2.0.x migration
 * (which already includes rc4's effects), ACP_BBGUILD_GAMESETTINGS exists
 * before this migration's update_data() runs, so the module is registered
 * there directly — same end state, without the intermediate detour.
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\migrations\v200;

use avathar\bbguildwow\game\wow_provider;

class release_2_0_0 extends \phpbb\db\migration\container_aware_migration
{
	public static function depends_on()
	{
		return [
			'\phpbb\db\migration\data\v320\v320',
			// bbguild core's own squashed 2.0.x migration. As of writing this
			// migration, bbguild core had NOT yet been squashed; this name is
			// the expected result of that parallel task. Verify/correct if
			// bbguild core landed under a different class name.
			'\avathar\bbguild\migrations\v200\release_2_0_0',
		];
	}

	/* ------------------------------------------------------------------ */
	/*  effectively_installed                                              */
	/* ------------------------------------------------------------------ */

	public function effectively_installed()
	{
		// Version lives in ext::BBGUILDWOW_VERSION, not phpbb_config; check
		// for the last effect of the chain instead — battlenet_module
		// parented under the Game settings category (the former rc2 step,
		// applied directly here — see class docblock).
		$sql = 'SELECT m.module_id
			FROM ' . $this->table_prefix . 'modules m
			JOIN ' . $this->table_prefix . "modules p ON p.module_id = m.parent_id
			WHERE m.module_class = 'acp'
				AND m.module_basename = '\\avathar\\bbguildwow\\acp\\battlenet_module'
				AND p.module_langname = 'ACP_BBGUILD_GAMESETTINGS'";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		return (bool) $row;
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
				/* Player equipment */
				$this->table_prefix . 'bb_player_equipment' => [
					'COLUMNS' => [
						'player_id'   => ['UINT', 0],
						'slot_type'   => ['VCHAR:30', ''],
						'item_id'     => ['UINT', 0],
						'item_name'   => ['VCHAR_UNI:255', ''],
						'item_level'  => ['USINT', 0],
						'quality'     => ['VCHAR:20', ''],
						'icon_url'    => ['VCHAR:255', ''],
						'last_update' => ['TIMESTAMP', 0],
					],
					'PRIMARY_KEY' => ['player_id', 'slot_type'],
					'KEYS' => [
						'pid' => ['INDEX', ['player_id']],
					],
				],
			],
			'add_columns' => [
				$this->table_prefix . 'bb_players' => [
					'player_render_url' => ['VCHAR', ''],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'bb_players' => [
					'player_render_url',
				],
			],
			'drop_tables' => [
				$this->table_prefix . 'bb_player_equipment',
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
	/*  DATA — config, game seeding, ACP modules                          */
	/* ------------------------------------------------------------------ */

	public function update_data()
	{
		return [
			['config.add', ['bbguild_show_achiev', 0]],
			['config.add', ['bbguild_achiev_hide_empty', 1]],
			['custom', [[$this, 'seed_game_data']]],
			['module.add', ['acp', 'ACP_BBGUILD_PLAYER', [
				'module_basename' => '\avathar\bbguildwow\acp\achievement_module',
				'modes'           => ['addachievement', 'listachievements'],
			]]],
			['module.add', ['acp', 'ACP_BBGUILD_GAMESETTINGS', [
				'module_basename' => '\avathar\bbguildwow\acp\battlenet_module',
				'modes'           => ['battlenet'],
			]]],
			// #331: backfill bb_specializations for installs that enabled
			// the WoW game before the spec catalog shipped. Must run after
			// seed_game_data so bb_games already has the 'wow' row.
			['custom', [[$this, 'seed_wow_specializations']]],
			// #26: locale display names for the specs just seeded above.
			['custom', [[$this, 'seed_wow_spec_translations']]],
			// Former b4: legacy version-string config row superseded by
			// ext::BBGUILDWOW_VERSION; one-way cleanup, nothing restores it.
			['config.remove', ['bbguild_wow_version']],
		];
	}

	public function revert_data()
	{
		return [
			['module.remove', ['acp', 'ACP_BBGUILD_GAMESETTINGS', [
				'module_basename' => '\avathar\bbguildwow\acp\battlenet_module',
			]]],
			['module.remove', ['acp', 'ACP_BBGUILD_PLAYER', [
				'module_basename' => '\avathar\bbguildwow\acp\achievement_module',
			]]],
			['config.remove', ['bbguild_show_achiev']],
			['config.remove', ['bbguild_achiev_hide_empty']],
			['custom', [[$this, 'remove_wow_spec_translations']]],
			['custom', [[$this, 'remove_wow_specializations']]],
			['custom', [[$this, 'remove_game_data']]],
			['custom', [[$this, 'remove_wow_players_and_guilds']]],
		];
	}

	/* ------------------------------------------------------------------ */
	/*  Helpers — game seeding                                             */
	/* ------------------------------------------------------------------ */

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
		$filesystem = $this->container->get('filesystem');
		$upload_path = $this->config['upload_path'];
		$portrait_dir = $this->phpbb_root_path . $upload_path . '/bbguildwow/';
		if ($filesystem->exists($portrait_dir))
		{
			$files = glob($portrait_dir . 'portraits/*.jpg');
			if ($files)
			{
				$filesystem->remove($files);
			}

			// Only remove the directories if they're now empty — renders/
			// and emblems/ live alongside portraits/ under the same
			// bbguildwow/ parent, so this must never recurse.
			if ($filesystem->exists($portrait_dir . 'portraits') && !glob($portrait_dir . 'portraits/*'))
			{
				$filesystem->remove($portrait_dir . 'portraits');
			}
			if (!glob($portrait_dir . '*'))
			{
				$filesystem->remove($portrait_dir);
			}
		}
	}

	private function get_installer()
	{
		return new \avathar\bbguildwow\game\wow_installer(
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

	/* ------------------------------------------------------------------ */
	/*  Helpers — specialization backfill (#331) and translations (#26)   */
	/* ------------------------------------------------------------------ */

	public function seed_wow_specializations()
	{
		// Skip seeding if the WoW game isn't installed in bb_games.
		// (Plugin can be enabled without the game being installed yet —
		// in that case, install_specs() will run when the game is added.)
		$games_table = $this->table_prefix . 'bb_games';
		$sql = 'SELECT 1 FROM ' . $games_table . " WHERE game_id = 'wow' LIMIT 1";
		$result = $this->db->sql_query($sql);
		$exists = (bool) $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		if (!$exists)
		{
			return;
		}

		$specs_table = $this->table_prefix . 'bb_specializations';
		$rows = [];
		foreach (wow_provider::spec_catalog() as $class_id => $specs)
		{
			foreach ($specs as $spec)
			{
				$rows[] = [
					'game_id'    => 'wow',
					'class_id'   => (int) $class_id,
					'role_id'    => (int) $spec['role_id'],
					'spec_name'  => (string) $spec['spec_name'],
					'spec_icon'  => (string) $spec['spec_icon'],
					'spec_order' => (int) $spec['spec_order'],
				];
			}
		}
		if ($rows)
		{
			$this->db->sql_multi_insert($specs_table, $rows);
		}
	}

	public function remove_wow_specializations()
	{
		$specs_table = $this->table_prefix . 'bb_specializations';
		$this->db->sql_query('DELETE FROM ' . $specs_table . " WHERE game_id = 'wow'");
	}

	public function seed_wow_spec_translations()
	{
		$specs_table = $this->table_prefix . 'bb_specializations';
		$lang_table  = $this->table_prefix . 'bb_language';

		// Build (class_id|spec_name) → spec_id lookup from existing rows.
		$sql = 'SELECT spec_id, class_id, spec_name FROM ' . $specs_table
			. " WHERE game_id = 'wow'";
		$result = $this->db->sql_query($sql);
		$by_key = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$by_key[(int) $row['class_id'] . '|' . $row['spec_name']] = (int) $row['spec_id'];
		}
		$this->db->sql_freeresult($result);

		if (!$by_key)
		{
			return; // No specs seeded; nothing to translate.
		}

		$translations = wow_provider::spec_translations();
		$rows = [];
		foreach (wow_provider::spec_catalog() as $class_id => $specs)
		{
			foreach ($specs as $spec)
			{
				$key = (int) $class_id . '|' . $spec['spec_name'];
				if (!isset($by_key[$key]))
				{
					continue;
				}
				$spec_id = $by_key[$key];

				foreach ($translations as $locale => $map)
				{
					if (!isset($map[$spec['spec_name']]))
					{
						continue;
					}
					$rows[] = [
						'game_id'      => 'wow',
						'attribute_id' => $spec_id,
						'language'     => $locale,
						'attribute'    => 'spec',
						'name'         => (string) $map[$spec['spec_name']],
						'name_short'   => (string) $map[$spec['spec_name']],
					];
				}
			}
		}
		if ($rows)
		{
			$this->db->sql_multi_insert($lang_table, $rows);
		}
	}

	public function remove_wow_spec_translations()
	{
		$lang_table = $this->table_prefix . 'bb_language';
		$this->db->sql_query('DELETE FROM ' . $lang_table . " WHERE attribute = 'spec' AND game_id = 'wow'");
	}
}
