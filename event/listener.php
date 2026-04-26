<?php
/**
 * bbGuild WoW plugin - Event listener
 *
 * Provides WoW-specific template variables (achievement points, armory URL)
 * for the bbGuild sidebar when the current guild is a WoW guild.
 * Also handles ACP template events for game/guild/player editing.
 *
 * @package   avathar\bbguild_wow
 * @copyright 2018 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild_wow\event;

use phpbb\config\config;
use phpbb\controller\helper;
use phpbb\db\driver\driver_interface;
use phpbb\request\request;
use phpbb\template\template;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var config */
	private $config;

	/** @var template */
	private $template;

	/** @var driver_interface */
	private $db;

	/** @var request */
	private $request;

	/** @var helper */
	private $helper;

	/** @var string */
	private $guild_wow_table;

	/** @var string */
	private $bb_players_table;

	/**
	 * @param config           $config
	 * @param template         $template
	 * @param driver_interface $db
	 * @param request          $request
	 * @param helper           $helper
	 * @param string           $guild_wow_table
	 * @param string           $bb_players_table
	 */
	public function __construct(config $config, template $template, driver_interface $db, request $request, helper $helper, $guild_wow_table, $bb_players_table)
	{
		$this->config = $config;
		$this->template = $template;
		$this->db = $db;
		$this->request = $request;
		$this->helper = $helper;
		$this->guild_wow_table = $guild_wow_table;
		$this->bb_players_table = $bb_players_table;
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return array(
			'core.user_setup'                           => 'load_language_on_setup',
			'core.page_header_after'                    => 'add_wow_guild_vars',
			'avathar.bbguild.acp_editgames_display'     => 'on_editgames_display',
			'avathar.bbguild.acp_editgames_submit'      => 'on_editgames_submit',
			'avathar.bbguild.acp_addguild_display'      => 'on_editguild_display',
			'avathar.bbguild.acp_addguild_submit'       => 'on_editguild_submit',
			'avathar.bbguild.acp_editguild_display'     => 'on_editguild_display',
			'avathar.bbguild.acp_editguild_submit'      => 'on_editguild_submit',
			'avathar.bbguild.acp_listplayers_display'   => 'on_listplayers_display',
			'avathar.bbguild.acp_config_display'        => 'on_config_display',
			'avathar.bbguild.acp_config_submit'         => 'on_config_submit',
			'avathar.bbguild.player_detail_display'     => 'on_player_detail_display',
		);
	}

	/**
	 * Load WoW plugin language file during user setup.
	 *
	 * @param \phpbb\event\data $event
	 */
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'avathar/bbguild_wow',
			'lang_set' => 'wow',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	/**
	 * When the current page is a bbGuild page with a WoW guild,
	 * load WoW-specific guild data and assign template variables.
	 *
	 * @param \phpbb\event\data $event
	 */
	public function add_wow_guild_vars($event)
	{
		// Only act when bbGuild has set GAME_ID and GUILD_ID template vars
		$tpldata = $this->template->retrieve_vars(array('GAME_ID', 'GUILD_ID'));

		$game_id = isset($tpldata['GAME_ID']) ? $tpldata['GAME_ID'] : '';
		$guild_id = isset($tpldata['GUILD_ID']) ? (int) $tpldata['GUILD_ID'] : 0;

		if ($game_id !== 'wow' || $guild_id <= 0)
		{
			return;
		}

		$sql = 'SELECT achievementpoints, guildarmoryurl
				FROM ' . $this->guild_wow_table . '
				WHERE guild_id = ' . $guild_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($row)
		{
			$this->template->assign_vars(array(
				'ACHIEV'              => $row['achievementpoints'],
				'ARMORY'              => $row['guildarmoryurl'],
				'ARMORY_URL'          => $row['guildarmoryurl'],
			));
		}
	}

	/**
	 * Populate WoW API credential fields on the edit game page.
	 *
	 * @param \phpbb\event\data $event
	 */
	public function on_editgames_display($event)
	{
		$game_id = $event['game_id'];
		if ($game_id !== 'wow')
		{
			return;
		}

		$editgame = $event['editgame'];

		// Populate apilocale dropdown
		$region = (string) $editgame->getRegion();
		if ($region === '')
		{
			$region = 'us';
		}

		$apilocales = $editgame->getApilocales($region);
		$current_locale = (string) $editgame->get_apilocale();

		if (is_array($apilocales))
		{
			foreach ($apilocales as $locale)
			{
				$this->template->assign_block_vars('apilocale_row', array(
					'VALUE'    => $locale,
					'SELECTED' => ($current_locale == $locale) ? ' selected="selected"' : '',
					'OPTION'   => $locale,
				));
			}
		}

		$this->template->assign_vars(array(
			'APIKEY'  => (string) $editgame->getApikey(),
			'PRIVKEY' => (string) $editgame->get_privkey(),
		));
	}

	/**
	 * Read WoW API credentials from the form and set them on the game object.
	 *
	 * @param \phpbb\event\data $event
	 */
	public function on_editgames_submit($event)
	{
		$game_id = $event['game_id'];
		if ($game_id !== 'wow')
		{
			return;
		}

		$editgame = $event['editgame'];
		$editgame->setApikey($this->request->variable('apikey', '', true));
		$editgame->set_privkey($this->request->variable('privkey', '', true));
		$editgame->set_apilocale($this->request->variable('apilocale', '', true));
	}

	/**
	 * Populate ARMORY_URL on the edit guild page for WoW guilds.
	 *
	 * @param \phpbb\event\data $event
	 */
	public function on_editguild_display($event)
	{
		$game_id = $event['game_id'];
		if ($game_id !== 'wow')
		{
			return;
		}

		$updateguild = $event['updateguild'];

		// Assign edition dropdown template vars (needed for both add and edit)
		$current_edition = $updateguild->getGameEdition();
		$editions = array(
			'retail'       => 'WOW_EDITION_RETAIL',
			'classic_era'  => 'WOW_EDITION_CLASSIC_ERA',
			'classic_prog' => 'WOW_EDITION_CLASSIC_PROG',
			'classic_ann'  => 'WOW_EDITION_CLASSIC_ANN',
		);
		foreach ($editions as $value => $lang_key)
		{
			$this->template->assign_block_vars('wow_edition_row', array(
				'VALUE'    => $value,
				'SELECTED' => ($current_edition === $value) ? ' selected="selected"' : '',
				'OPTION'   => $lang_key,
			));
		}
		$this->template->assign_vars(array(
			'GAME_EDITION' => $current_edition,
		));

		$guild_id = (int) $updateguild->getGuildid();

		if ($guild_id <= 0)
		{
			return;
		}

		$sql = 'SELECT guildarmoryurl
				FROM ' . $this->guild_wow_table . '
				WHERE guild_id = ' . $guild_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if ($row)
		{
			$this->template->assign_vars(array(
				'ARMORY_URL'        => $row['guildarmoryurl'],
				'U_ROSTER_SYNC'     => $this->helper->route('avathar_bbguild_wow_sync_roster', array('guild_id' => $guild_id)),
				'U_SPEC_SYNC'       => $this->helper->route('avathar_bbguild_wow_sync_specs', array('guild_id' => $guild_id)),
				'U_PORTRAIT_SYNC'   => $this->helper->route('avathar_bbguild_wow_sync_portraits', array('guild_id' => $guild_id)),
				'U_CATEGORY_SYNC'   => $this->helper->route('avathar_bbguild_wow_sync_categories', array('guild_id' => $guild_id)),
				'U_ACHIEV_SYNC'     => $this->helper->route('avathar_bbguild_wow_sync_achievements', array('guild_id' => $guild_id)),
				'U_EQUIPMENT_SYNC'  => $this->helper->route('avathar_bbguild_wow_sync_equipment', array('guild_id' => $guild_id)),
			));
		}
	}

	/**
	 * Handle game edition on guild update submit for WoW guilds.
	 *
	 * @param \phpbb\event\data $event
	 */
	public function on_editguild_submit($event)
	{
		$game_id = $event['game_id'];
		if ($game_id !== 'wow')
		{
			return;
		}

		$updateguild = $event['updateguild'];
		$updateguild->setGameEdition($this->request->variable('game_edition', 'retail'));
	}

	/**
	 * Set HAS_API flag on the list players page for WoW guilds.
	 * (This is handled via the core event dispatch — the core already
	 * checks has_api() via game_registry, so no WoW-specific action needed.)
	 *
	 * @param \phpbb\event\data $event
	 */
	public function on_listplayers_display($event)
	{
		// The core already sets HAS_API from game_registry->has_api().
		// This handler is available for future WoW-specific player list vars.
	}

	/**
	 * Inject the "Show Achievement Points" checkbox into the bbGuild config page.
	 */
	public function on_config_display()
	{
		$this->template->assign_vars(array(
			'F_SHOWACHIEV'        => (int) $this->config['bbguild_show_achiev'],
			'F_ACHIEV_HIDE_EMPTY' => (int) $this->config['bbguild_achiev_hide_empty'],
		));
	}

	/**
	 * Display WoW-specific data on the player detail page.
	 * Currently shows the active specialization.
	 *
	 * @param \phpbb\event\data $event
	 */
	public function on_player_detail_display($event)
	{
		global $phpbb_container;

		$player_id = (int) $event['player_id'];

		$sql = 'SELECT game_id, player_spec, player_render_url
			FROM ' . $this->bb_players_table . '
			WHERE player_id = ' . $player_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row || $row['game_id'] !== 'wow')
		{
			return;
		}

		$spec = $row['player_spec'];
		if (empty($spec) || $spec === 'N/A')
		{
			$spec = '';
		}

		// Load equipment
		$equipment_table = $phpbb_container->getParameter('avathar.bbguild_wow.tables.bb_player_equipment');
		$sql = 'SELECT slot_type, item_id, item_name, item_level, quality, icon_url
			FROM ' . $equipment_table . '
			WHERE player_id = ' . $player_id . '
			ORDER BY slot_type';
		$result = $this->db->sql_query($sql);

		$equipment = array();
		$total_ilvl = 0;
		$item_count = 0;
		while ($eq_row = $this->db->sql_fetchrow($result))
		{
			$equipment[$eq_row['slot_type']] = $eq_row;
			if ((int) $eq_row['item_level'] > 0)
			{
				$total_ilvl += (int) $eq_row['item_level'];
				$item_count++;
			}
		}
		$this->db->sql_freeresult($result);

		$avg_ilvl = ($item_count > 0) ? round($total_ilvl / $item_count) : 0;

		// Assign equipment block vars in slot order
		$slot_order = array(
			'HEAD', 'NECK', 'SHOULDER', 'BACK', 'CHEST', 'WRIST',
			'HANDS', 'WAIST', 'LEGS', 'FEET',
			'FINGER_1', 'FINGER_2', 'TRINKET_1', 'TRINKET_2',
			'MAIN_HAND', 'OFF_HAND',
		);

		foreach ($slot_order as $slot)
		{
			if (isset($equipment[$slot]))
			{
				$eq = $equipment[$slot];
				$this->template->assign_block_vars('wow_equipment', array(
					'SLOT'       => $slot,
					'SLOT_LABEL' => str_replace('_', ' ', ucwords(strtolower($slot), '_')),
					'ITEM_ID'    => (int) $eq['item_id'],
					'ITEM_NAME'  => $eq['item_name'],
					'ITEM_LEVEL' => (int) $eq['item_level'],
					'QUALITY'    => $eq['quality'],
					'ICON_URL'   => $eq['icon_url'],
					'S_HAS_ITEM' => true,
				));
			}
			else
			{
				$this->template->assign_block_vars('wow_equipment', array(
					'SLOT'       => $slot,
					'SLOT_LABEL' => str_replace('_', ' ', ucwords(strtolower($slot), '_')),
					'ITEM_ID'    => 0,
					'ITEM_NAME'  => '',
					'ITEM_LEVEL' => 0,
					'QUALITY'    => '',
					'ICON_URL'   => '',
					'S_HAS_ITEM' => false,
				));
			}
		}

		// Fetch stats and professions on demand via API (cached 1h by API layer)
		$wow_api = $phpbb_container->get('avathar.bbguild_wow.api');

		$sql = 'SELECT player_name, player_realm, player_region, g.game_edition
			FROM ' . $this->bb_players_table . ' p
			INNER JOIN ' . $this->guild_wow_table . ' gw ON 1=0
			LEFT JOIN %s g ON g.id = p.player_guild_id
			WHERE p.player_id = ' . $player_id;
		// Simpler: just get player + guild edition
		$sql = 'SELECT p.player_name, p.player_realm, p.player_region, g.game_edition
			FROM ' . $this->bb_players_table . ' p
			LEFT JOIN ' . $phpbb_container->getParameter('avathar.bbguild.tables.bb_guild') . ' g ON g.id = p.player_guild_id
			WHERE p.player_id = ' . $player_id;
		$result = $this->db->sql_query($sql);
		$player_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		$stats_data = array();
		$professions_data = array();

		if ($player_row)
		{
			$p_name = $player_row['player_name'];
			$p_realm = $player_row['player_realm'];
			$p_region = $player_row['player_region'];
			$p_edition = !empty($player_row['game_edition']) ? $player_row['game_edition'] : 'retail';

			// Character stats
			$raw_stats = $wow_api->fetch_character_stats($p_name, $p_realm, $p_region, $p_edition);
			if ($raw_stats)
			{
				$stat_keys = array(
					'strength', 'agility', 'intellect', 'stamina',
					'armor', 'versatility',
				);
				foreach ($stat_keys as $key)
				{
					if (isset($raw_stats[$key]))
					{
						$value = is_array($raw_stats[$key]) ? ($raw_stats[$key]['effective'] ?? $raw_stats[$key]['value'] ?? 0) : $raw_stats[$key];
						$stats_data[$key] = (int) $value;
					}
				}
				// Rating stats (percentage display)
				$rating_keys = array(
					'melee_crit' => 'crit',
					'melee_haste' => 'haste',
					'mastery' => 'mastery',
					'versatility_damage_done_bonus' => 'versatility',
				);
				foreach ($rating_keys as $api_key => $display_key)
				{
					if (isset($raw_stats[$api_key]))
					{
						$val = is_array($raw_stats[$api_key]) ? ($raw_stats[$api_key]['value'] ?? 0) : $raw_stats[$api_key];
						$stats_data[$display_key . '_pct'] = round((float) $val, 2);
					}
				}
			}

			// Professions
			$raw_prof = $wow_api->fetch_character_professions($p_name, $p_realm, $p_region, $p_edition);
			if ($raw_prof && isset($raw_prof['primaries']))
			{
				foreach ($raw_prof['primaries'] as $prof)
				{
					$skill_points = 0;
					$max_points = 0;
					if (isset($prof['tiers']) && !empty($prof['tiers']))
					{
						// Use the last (current expansion) tier
						$last_tier = end($prof['tiers']);
						$skill_points = $last_tier['skill_points'] ?? 0;
						$max_points = $last_tier['max_skill_points'] ?? 0;
					}
					$professions_data[] = array(
						'name'   => $prof['profession']['name'] ?? '',
						'skill'  => $skill_points,
						'max'    => $max_points,
					);
				}
			}
			if ($raw_prof && isset($raw_prof['secondaries']))
			{
				foreach ($raw_prof['secondaries'] as $prof)
				{
					$skill_points = 0;
					$max_points = 0;
					if (isset($prof['tiers']) && !empty($prof['tiers']))
					{
						$last_tier = end($prof['tiers']);
						$skill_points = $last_tier['skill_points'] ?? 0;
						$max_points = $last_tier['max_skill_points'] ?? 0;
					}
					if ($skill_points > 0)
					{
						$professions_data[] = array(
							'name'   => $prof['profession']['name'] ?? '',
							'skill'  => $skill_points,
							'max'    => $max_points,
						);
					}
				}
			}
		}

		// Assign stats block vars
		foreach ($stats_data as $stat_name => $stat_value)
		{
			$this->template->assign_block_vars('wow_stats', array(
				'NAME'  => str_replace('_', ' ', ucfirst($stat_name)),
				'KEY'   => $stat_name,
				'VALUE' => $stat_value,
				'S_PCT' => (strpos($stat_name, '_pct') !== false),
			));
		}

		// Assign professions block vars
		foreach ($professions_data as $prof)
		{
			$this->template->assign_block_vars('wow_professions', array(
				'NAME'  => $prof['name'],
				'SKILL' => $prof['skill'],
				'MAX'   => $prof['max'],
			));
		}

		// Mythic Keystone profile
		$mplus_rating = 0;
		$mplus_color = '';
		$mplus_runs = array();
		if ($player_row && $p_edition === 'retail')
		{
			$raw_mplus = $wow_api->fetch_mythic_keystone_profile($p_name, $p_realm, $p_region, $p_edition);
			if ($raw_mplus)
			{
				if (isset($raw_mplus['current_mythic_rating']))
				{
					$rating = $raw_mplus['current_mythic_rating'];
					$mplus_rating = round((float) ($rating['rating'] ?? 0));
					if (isset($rating['color']))
					{
						$c = $rating['color'];
						$mplus_color = sprintf('#%02x%02x%02x', $c['r'] ?? 0, $c['g'] ?? 0, $c['b'] ?? 0);
					}
				}
				if (isset($raw_mplus['current_period']['best_runs']))
				{
					foreach ($raw_mplus['current_period']['best_runs'] as $run)
					{
						$upgrades = $run['keystone_upgrades'] ?? 0;
						$stars = '';
						for ($i = 0; $i < $upgrades; $i++)
						{
							$stars .= '+';
						}

						$duration_ms = $run['duration'] ?? 0;
						$minutes = floor($duration_ms / 60000);
						$seconds = floor(($duration_ms % 60000) / 1000);

						$this->template->assign_block_vars('wow_mplus_runs', array(
							'DUNGEON'  => $run['dungeon']['name'] ?? '',
							'LEVEL'    => (int) ($run['keystone_level'] ?? 0),
							'TIME'     => sprintf('%d:%02d', $minutes, $seconds),
							'UPGRADES' => $stars,
							'S_TIMED'  => $upgrades > 0,
						));
						$mplus_runs[] = true;
					}
				}
			}
		}

		// PvP summary
		$pvp_data = array();
		if ($player_row && $p_edition === 'retail')
		{
			$raw_pvp = $wow_api->fetch_pvp_summary($p_name, $p_realm, $p_region, $p_edition);
			if ($raw_pvp)
			{
				if (isset($raw_pvp['honor_level']))
				{
					$pvp_data['honor_level'] = (int) $raw_pvp['honor_level'];
				}
				if (isset($raw_pvp['pvp_map_statistics']))
				{
					// Not always present — skip silently
				}
			}

			// Try to get bracket ratings (2v2, 3v3, rbg) from the brackets endpoint
			// The pvp-summary doesn't always include ratings; they're separate endpoints
			// For now, just show honor level if available
		}

		$this->template->assign_vars(array(
			'WOW_PLAYER_SPEC'       => $spec,
			'WOW_AVG_ILVL'          => $avg_ilvl,
			'WOW_MPLUS_RATING'      => $mplus_rating,
			'WOW_MPLUS_COLOR'       => $mplus_color,
			'WOW_PVP_HONOR_LEVEL'   => $pvp_data['honor_level'] ?? 0,
			'S_WOW_PLAYER'          => true,
			'S_WOW_HAS_EQUIPMENT'   => !empty($equipment),
			'S_WOW_HAS_STATS'       => !empty($stats_data),
			'S_WOW_HAS_PROFESSIONS' => !empty($professions_data),
			'S_WOW_HAS_MPLUS'       => $mplus_rating > 0 || !empty($mplus_runs),
			'S_WOW_HAS_PVP'         => !empty($pvp_data),
			'WOW_PLAYER_RENDER'     => isset($row['player_render_url']) && !empty($row['player_render_url']) && $row['player_render_url'] !== 'N/A' ? $row['player_render_url'] : '',
			'S_WOW_HAS_RENDER'      => isset($row['player_render_url']) && !empty($row['player_render_url']) && $row['player_render_url'] !== 'N/A',
		));
	}

	/**
	 * Save the achievement settings from the bbGuild config page.
	 */
	public function on_config_submit()
	{
		$this->config->set('bbguild_show_achiev', $this->request->variable('bbguild_show_achiev', 0), true);
		$this->config->set('bbguild_achiev_hide_empty', $this->request->variable('bbguild_achiev_hide_empty', 0), true);
	}

}
