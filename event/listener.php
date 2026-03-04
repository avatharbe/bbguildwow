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

use phpbb\db\driver\driver_interface;
use phpbb\request\request;
use phpbb\template\template;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	/** @var template */
	private $template;

	/** @var driver_interface */
	private $db;

	/** @var request */
	private $request;

	/** @var string */
	private $guild_wow_table;

	/**
	 * @param template         $template
	 * @param driver_interface $db
	 * @param request          $request
	 * @param string           $guild_wow_table
	 */
	public function __construct(template $template, driver_interface $db, request $request, $guild_wow_table)
	{
		$this->template = $template;
		$this->db = $db;
		$this->request = $request;
		$this->guild_wow_table = $guild_wow_table;
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
			'avathar.bbguild.acp_editguild_display'     => 'on_editguild_display',
			'avathar.bbguild.acp_listplayers_display'   => 'on_listplayers_display',
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
				'S_DISPLAY_ACHIEVEMENTS' => !empty($row['achievementpoints']),
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
				'ARMORY_URL' => $row['guildarmoryurl'],
			));
		}
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
}
