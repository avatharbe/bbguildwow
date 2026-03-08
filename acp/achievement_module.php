<?php
/**
 * achievement acp file
 *
 * @package   bbguild_wow v2.0
 * @copyright 2018 avathar.be
 * @author    Sajaki
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild_wow\acp;

use avathar\bbguild\model\player\guilds;
use avathar\bbguild_wow\model\achievement;
use avathar\bbguild\model\games\game;
use avathar\bbguild\model\games\rpg\faction;

/**
 * This class manages achievement info
 * @todo finish this module
 * @package avathar\bbguild_wow\acp
 */
class achievement_module
{
	/**
	 * trigger link
	 *
	 * @var string
	 */
	public $link = '';

	protected $phpbb_container;
	/**
	 * @var \phpbb\request\request
	 **/
	protected $request;
	/**
	 * @var \phpbb\template\template
	 **/
	protected $template;
	/**
	 * @var \phpbb\user
	 **/
	protected $user;
	/**
	 * @var \phpbb\db\driver\driver_interface
	 */
	protected $db;

	public $id;
	public $mode;
	public $auth;

	public $achievement;

	/**
	 * @type guilds
	 */
	private $guild;

	/**
	 * @type game
	 */
	private $game;

	/**
	 * @type string
	 */
	private $moduleurl;


	/**
	 * @param $id
	 * @param $mode
	 */
	public function main($id, $mode)
	{
		global $user, $db, $template, $phpbb_admin_path, $phpEx;
		global $request, $phpbb_container, $auth;

		$this->id = $id;
		$this->mode = $mode;
		$this->request=$request;
		$this->template=$template;
		$this->user=$user;
		$this->db=$db;
		$this->phpbb_container = $phpbb_container;
		$this->auth=$auth;


		$form_key = 'avathar/bbguild_wow';
		add_form_key($form_key);
		$this->tpl_name   = 'acp_' . $mode;

		if (! $this->auth->acl_get('a_bbguild'))
		{
			trigger_error($user->lang['NOAUTH_A_PLAYERS_MAN']);
		}

		//css trigger
		$this->template->assign_vars(
			array (
				'S_BBGUILD' => true,
			)
		);

		$this->moduleurl = 'i=-avathar-bbguild_wow-acp-achievement_module&amp;';

		// Get achievement service from container
		$this->achievement = $phpbb_container->get('avathar.bbguild_wow.achievement');

		switch ($mode)
		{
			/**
			 * List achievement for this guild
			 */
			case 'listachievements':
				$this->link = '<br /><a href="' . append_sid("{$phpbb_admin_path}index.$phpEx", $this->moduleurl . 'mode=listachievements') . '"><h3>Return to Index</h3></a>';
				$this->guild = $this->GetGuild();

				// add achievement button redirect
				$achievaddmanual = $this->request->is_set_post('achievaddmanual');
				$achievaddapi    = $this->request->is_set_post('achievaddapi');
				$achievdelete    = $this->request->is_set_post('delete');

				if ($achievaddmanual)
				{
					$a = $this->request->variable('achievement_guild_id', $this->request->variable('hidden_guildid', 0));
					redirect(append_sid("{$phpbb_admin_path}index.$phpEx", $this->moduleurl . 'mode=addachievement&amp;guild_id=' . $a));
				}
				if ($achievaddapi)
				{
					$a = $this->request->variable('achievement_guild_id', $this->request->variable('hidden_guildid', 0));
					$this->LoadAPIGuildachievements($this->guild);

				}
				if ($achievdelete)
				{
					$this->achievement_batch_delete($this->guild);
				}

				// pageloading
				$this->BuildTemplateListAchievements($this->guild );
				break;

			/**
			 * add achievement manually
			 */
			case 'addachievement' :
				$this->link = '<br /><a href="' . append_sid("{$phpbb_admin_path}index.$phpEx", $this->moduleurl . 'mode=listachievements') . '"><h3>' . $this->user->lang['RETURN_PLAYERLIST'] . '</h3></a>';

				$add = $this->request->is_set_post('add');
				$update = $this->request->is_set_post('update');
				$delete = $this->request->variable('delete', '')  != '' ? true : false;

				$this->guild = $this->GetGuild();

				if ($add || $update)
				{
					if (! check_form_key('avathar/bbguild_wow'))
					{
						trigger_error($this->user->lang['FORM_INVALID'] . adm_back_link($this->u_action));
					}
				}

				if ($add)
				{
					$this->Addachievement();
				}

				if ($update)
				{
					$this->UpdateAchievement();
				}

				if ($delete)
				{
					if (confirm_box(true))
					{
						$deleteachi = $this->DeleteAchievement();
					}
					else
					{
						$this->achievement->setGame($this->game, 0);
						$this->achievement->id = $this->request->variable('achievement_id', 0);
						$this->achievement->get_achievement();
						$s_hidden_fields = build_hidden_fields(
							array(
								'delete' => true ,
								'del_achievement_id' => $this->achievement->id)
						);

						confirm_box(false, sprintf($this->user->lang['CONFIRM_DELETE_ACHIEVEMENT'], $this->achievement->getDescription()), $s_hidden_fields);
					}
				}

				$this->BuildTemplateAddEditAchievements($this->request->variable('achievement_id', 0));
				break;
		}
	}

	/**
	 * List achievements
	 *
	 * @param \avathar\bbguild\model\player\guilds $Guild
	 */
	private function BuildTemplateListAchievements(guilds $Guild)
	{
		global $phpbb_admin_path, $phpEx;

		// configure achievement service for this game
		$this->achievement->setGame($this->game, 0);

		// fill popup and set selected to default selection
		$guildlist = $Guild->guildlist(0);
		foreach ($guildlist as $g)
		{
			$this->template->assign_block_vars(
				'guild_row', array(
					'VALUE'    => $g['id'],
					'SELECTED' => ($g['id'] == $Guild->getGuildid()) ? ' selected="selected"' : '',
					'OPTION'   => (!empty($g['name'])) ? $g['name'] : '(None)')
			);
		}

		$start    = $this->request->variable('start', 0, false);
		$GuildAchievements = $this->achievement->get_tracked_achievements($start, $Guild->guildid, 0);
		$footcount_text   = sprintf($this->user->lang['ACHIEV_FOOTCOUNT'], $GuildAchievements[2]);

		$modulename = 'i=-avathar-bbguild_wow-acp-achievement_module&amp;mode=list_achievements';
		$pagination = $this->phpbb_container->get('pagination');
		$pagination_url = append_sid(
			"{$phpbb_admin_path}index.$phpEx",
			$modulename . '&amp;o=' . $GuildAchievements[1]['uri']['current'] .
			'&amp;' . URI_GUILD . '=' . $Guild->getGuildid());
		$pagination->generate_template_pagination($pagination_url, 'pagination', 'start', $GuildAchievements[2], 15, $start, true);

		foreach ($GuildAchievements[0] as $id => $achievement)
		{
			$this->template->assign_block_vars(
				'players_row', array(
					'GUILD'    => $achievement['guild_id'],
					'PLAYER'    => $achievement['player_id'],
					'GAME'    => $achievement['game_id'],
					'TITLE'    => $achievement['title'],
					'POINTS'    => $achievement['points'],
					'DESCRIPTION'    => $achievement['description'],
					'ICON'    => $achievement['icon'],
					'FACTION'    => $achievement['factionId'],
					'CID'    => $achievement['criteria']['criteria_id'],
					'CDESCR'    => $achievement['criteria']['criteriadescription'],
					'CORDER'    => $achievement['criteria']['criteriaorder'],
					'CMAX'    => $achievement['criteria']['criteriamax'],
					'CQUANT'    => $achievement['criteria']['criteria_quantity'],
					'CTIMESTAMP'    => $achievement['criteria']['criteria_timestamp'],
					'CCREATED'    => $achievement['criteria']['criteria_created'],
					'REWARDS'    => $achievement['criteria']['criteria_timestamp'],
					'REWARDSITEM'    => $achievement['rewardItems']['rewards_item_id'],
					'REWARDDESCR'    => $achievement['rewardItems']['rewardsdescription'],
					'ITEMLEVEL'    => $achievement['rewardItems']['itemlevel'],
					'COMPLETED'    => $achievement['achievements_completed'],
					'O_NAME' => append_sid("{$phpbb_admin_path}index.$phpEx", $modulename . '&amp;o=' .
						$GuildAchievements[1]['uri'][0] . '&amp;' . URI_GUILD . '=' . $Guild->getGuildid()
					),
					'PAGE_NUMBER'           => $pagination->on_page($GuildAchievements[2], 15, $start),
			));
		}

		$this->template->assign_vars(
			array(
				'LISTACHI_FOOTCOUNT'    => $footcount_text,
				'L_TITLE'               => $this->user->lang['ACP_LISTACHIEV'],
				'GUILD_EMBLEM'          => $this->guild->getEmblempath(),
				'GUILD_NAME'            => $this->guild->getName(),
				'U_VIEW_GUILD'          => append_sid("{$phpbb_admin_path}index.$phpEx", 'i=-avathar-bbguild-acp-guild_module&amp;mode=editguild&amp;action=editguild&amp;' . URI_GUILD . '=' . $this->guild->getGuildid()),
			)
		);

		$this->page_title = $this->user->lang['ACP_LISTACHIEV'];

	}

	/***
	 * get a guild from pulldown
	 *
	 * @return \avathar\bbguild\model\player\guilds
	 */
	private function GetGuild()
	{
		$Guild     = new guilds(
			$this->db,
			$this->user,
			$this->phpbb_container->get('config'),
			$this->phpbb_container->get('cache.driver'),
			$this->phpbb_container->get('avathar.bbguild.log'),
			$this->phpbb_container->getParameter('avathar.bbguild.tables.bb_players'),
			$this->phpbb_container->getParameter('avathar.bbguild.tables.bb_ranks'),
			$this->phpbb_container->getParameter('avathar.bbguild.tables.bb_classes'),
			$this->phpbb_container->getParameter('avathar.bbguild.tables.bb_races'),
			$this->phpbb_container->getParameter('avathar.bbguild.tables.bb_language'),
			$this->phpbb_container->getParameter('avathar.bbguild.tables.bb_guild'),
			$this->phpbb_container->getParameter('avathar.bbguild.tables.bb_factions')
		);
		$guildlist = $Guild->guildlist(1);
		// guild dropdown query
		$getguild_dropdown = $this->request->is_set_post('achievement_guild_id');
		if ($getguild_dropdown)
		{
			// user selected dropdown - get guildid
			$Guild->setGuildid($this->request->variable('achievement_guild_id', 0));
		}
		if ($Guild->guildid == 0)
		{
			if (count((array) $guildlist) === 0)
			{
				trigger_error('ERROR_NOGUILD', E_USER_WARNING);
			}
			if (count((array) $guildlist) === 1)
			{
				//if there is only one then take this one
				$Guild->setGuildid($guildlist[0]['id']);
				$Guild->setName($guildlist[0]['name']);
				if ($Guild->getGuildid() === 0 && $Guild->getName() === 'Guildless')
				{
					trigger_error('ERROR_NOGUILD', E_USER_WARNING);
				}
			} else
			{
				//
				foreach ($guildlist as $g)
				{
					$Guild->setGuildid($g['id']);
					break;
				}
			}
		}
		$Guild->get_guild();
		$this->game = new game(
			$this->db,
			$this->phpbb_container->get('cache.driver'),
			$this->phpbb_container->get('config'),
			$this->user,
			$this->phpbb_container->get('ext.manager'),
			$this->phpbb_container->getParameter('avathar.bbguild.tables.bb_classes'),
			$this->phpbb_container->getParameter('avathar.bbguild.tables.bb_races'),
			$this->phpbb_container->getParameter('avathar.bbguild.tables.bb_language'),
			$this->phpbb_container->getParameter('avathar.bbguild.tables.bb_factions'),
			$this->phpbb_container->getParameter('avathar.bbguild.tables.bb_games')
		);
		$this->game->game_id = $Guild->getGameId();
		$this->game->get_game();

		return $Guild;
	}

	/**
	 * prepare form for adding achievement
	 * @param $achievement_id
	 *
	 */
	private function BuildTemplateAddEditAchievements($achievement_id)
	{
		$this->achievement->setGame($this->game, $achievement_id);
		if ($achievement_id > 0)
		{
			$this->achievement->get_achievement();
		}

		// Game dropdown
		if (isset ($this->games))
		{
			foreach ($this->games as $gameid => $gamename)
			{
				$this->template->assign_block_vars(
					'game_row', array(
						'VALUE'    => $gameid,
						'SELECTED' => ($this->game->game_id == $gameid) ? ' selected="selected"' : '',
						'OPTION'   => $gamename)
				);
			}
		}

		// faction  dropdown
		$listfactions = new faction(
			$this->db,
			$this->phpbb_container->get('cache.driver'),
			$this->user,
			$this->game->game_id,
			$this->phpbb_container->getParameter('avathar.bbguild.tables.bb_factions'),
			$this->phpbb_container->getParameter('avathar.bbguild.tables.bb_races')
		);
		$fa = $listfactions->get_factions();
		foreach ($fa as $faction_id => $faction)
		{
			$this->template->assign_block_vars(
				'faction_row', array (
					'ID' => $faction['f_index'],
					'SELECTED' => ( $faction_id == isset($this->achievement) ? $this->achievement->getFactionId() : 0) ? ' selected="selected"' : '',
					'VALUE' => $faction['faction_id'],
					'NAME' => $faction['faction_name'],
				)
			);
		}

		$this->template->assign_vars(
			array(
				'S_ADD'    => $achievement_id == 0 ? true :false,
				'ID'    => $achievement_id,
				'POINTS'    => $achievement_id,
				'MSG_TITLE_EMPTY'           => $this->user->lang['FV_REQUIRED_TITLE'],
				'MSG_DESCRIPTION_EMPTY'  => $this->user->lang['FV_REQUIRED_DESCRIPTION'],
				'MSG_ID_EMPTY'           => $this->user->lang['FV_REQUIRED_ID'],
			)
		);
	}

	/**
	 *
	 * execute add achievement
	 */
	private function Addachievement()
	{

	}

	/**
	 * execute update achievement
	 */
	private function UpdateAchievement()
	{

	}

	/**
	 * execute delete achievement
	 */
	private function DeleteAchievement()
	{

	}

	/**
	 *
	 * function to batch delete achievements, called from listing
	 * @param \avathar\bbguild\model\player\guilds $Guild
	 *
	 */
	private function achievement_batch_delete(guilds $Guild)
	{

	}

	/**
	 * load guild achievements from API
	 * @param \avathar\bbguild\model\player\guilds $Guild
	 */
	private function LoadAPIGuildachievements(guilds $Guild)
	{
		//call achievement API
		$this->achievement->setGame($this->game, 0);
		$this->achievement->setGuildId($this->guild->guildid);
		$this->achievement->setAchievements($Guild, $this->game);
	}
}
