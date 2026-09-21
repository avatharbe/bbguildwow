<?php
/**
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * Talents player-detail tab (bbguildwow#45, follow-up to the #375
 * placeholder). Real content: the active loadout's selected class/spec/
 * hero talents, via wow_api::fetch_character_talents() -- which wraps
 * the same /specializations endpoint sync_one_specs() already calls
 * every sync, just exposing the rest of the response (talent loadouts)
 * instead of only active_specialization.name. No new Battle.net
 * endpoint needed; see #45 for how this was confirmed against a live
 * response before implementing.
 *
 * Scope: the character's currently-active spec's currently-active
 * loadout only (a character can have several saved loadouts per spec;
 * Blizzard marks exactly one is_active per spec). Not covered: viewing
 * other saved loadouts, the talent tree's visual layout/connections
 * (this is a flat list, not a tree render), and the
 * talent_loadout_code export string (shareable with WoW's in-game
 * import, not rendered here).
 */

namespace avathar\bbguildwow\portal\player_detail_tabs;

use avathar\bbguild\portal\player_detail_tab_interface;
use avathar\bbguildwow\game\wow_api;
use phpbb\db\driver\driver_interface;
use phpbb\template\template;

class talents_tab implements player_detail_tab_interface
{
	/** @var wow_api */
	protected $wow_api;

	/** @var driver_interface */
	protected $db;

	/** @var template */
	protected $template;

	/** @var string */
	protected $players_table;

	/** @var string */
	protected $guild_table;

	public function __construct(
		wow_api $wow_api,
		driver_interface $db,
		template $template,
		string $players_table,
		string $guild_table
	)
	{
		$this->wow_api = $wow_api;
		$this->db = $db;
		$this->template = $template;
		$this->players_table = $players_table;
		$this->guild_table = $guild_table;
	}

	public function get_tab_name(): string
	{
		return 'WOW_TALENTS';
	}

	public function get_tab_slug(): string
	{
		return 'talents';
	}

	public function get_tab_order(): int
	{
		return 10;
	}

	public function is_available(int $player_id, string $game_id): bool
	{
		return $game_id === 'wow';
	}

	public function render(int $player_id): ?string
	{
		$sql = 'SELECT p.player_name, p.player_realm, p.player_region, g.game_edition
			FROM ' . $this->players_table . ' p
			LEFT JOIN ' . $this->guild_table . ' g ON g.id = p.player_guild_id
			WHERE p.player_id = ' . (int) $player_id;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$row)
		{
			return null;
		}

		$edition = !empty($row['game_edition']) ? $row['game_edition'] : 'retail';

		$loadout = null;
		$spec_name = '';
		try
		{
			$data = $this->wow_api->fetch_character_talents($row['player_name'], $row['player_realm'], $row['player_region'], $edition);
			if ($data !== false)
			{
				$spec_name = $data['active_specialization']['name'] ?? '';
				$loadout = $this->find_active_loadout($data);
			}
		}
		catch (\Throwable $e)
		{
			$loadout = null;
		}

		$this->template->assign_vars(array(
			'WOW_TALENTS_SPEC_NAME' => $spec_name,
			'WOW_TALENTS_HAS_DATA'  => $loadout !== null,
		));

		if ($loadout !== null)
		{
			// Same bbTips Wowhead-style tooltip as equipment (event/listener.php)
			// rather than a plain title="" attribute, which renders as the
			// browser's own unstyled tooltip instead of bbTips' popup.
			global $phpbb_container;
			$bbtips_wow = null;
			if (isset($phpbb_container) && $phpbb_container->has('avathar.bbtips.linker'))
			{
				$bbtips_wow = $phpbb_container->get('avathar.bbtips.linker')->wow();
			}

			$this->assign_talent_rows('class_talent_row', $loadout['selected_class_talents'] ?? array(), $bbtips_wow);
			$this->assign_talent_rows('spec_talent_row', $loadout['selected_spec_talents'] ?? array(), $bbtips_wow);
			$this->assign_talent_rows('hero_talent_row', $loadout['selected_hero_talents'] ?? array(), $bbtips_wow);
		}

		return '@avathar_bbguildwow/portal/talents_tab.html';
	}

	/**
	 * Find the given specializations response's currently-active spec,
	 * then that spec's currently-active loadout. Blizzard marks exactly
	 * one loadout per spec as is_active, and separately reports which
	 * spec is the character's overall active one.
	 *
	 * @param array $data Parsed getCharacterSpecializations() response
	 * @return array|null The active loadout, or null if not found
	 */
	private function find_active_loadout(array $data): ?array
	{
		$active_spec_id = $data['active_specialization']['id'] ?? null;
		if ($active_spec_id === null)
		{
			return null;
		}

		foreach ($data['specializations'] ?? array() as $spec)
		{
			if (($spec['specialization']['id'] ?? null) !== $active_spec_id)
			{
				continue;
			}

			foreach ($spec['loadouts'] ?? array() as $loadout)
			{
				if (!empty($loadout['is_active']))
				{
					return $loadout;
				}
			}
		}

		return null;
	}

	/**
	 * Assign one talent-list block. Entries with no resolvable name
	 * (seen in live responses -- some talent ids have no 'tooltip' at
	 * all) are skipped rather than shown as a bare numeric id.
	 *
	 * @param string     $block      Template block name
	 * @param array      $talents    e.g. loadout['selected_class_talents']
	 * @param mixed|null $bbtips_wow avathar\bbtips\provider\wow_provider, or null if bbTips isn't installed
	 */
	private function assign_talent_rows(string $block, array $talents, $bbtips_wow): void
	{
		foreach ($talents as $talent)
		{
			$name = $talent['tooltip']['talent']['name'] ?? null;
			if ($name === null)
			{
				continue;
			}

			$rank = (int) ($talent['rank'] ?? 1);
			$spell_id = (int) ($talent['tooltip']['spell_tooltip']['spell']['id'] ?? 0);

			$link = '';
			if ($bbtips_wow !== null && $spell_id > 0)
			{
				// Leading U+00A0 x2 (not the "&nbsp;" entity -- build_link()
				// runs this through htmlspecialchars(), which would escape
				// the entity into literal text) puts a gap between
				// bbTips' auto-inserted icon and the talent name, matching
				// the equipment slots' explicit icon+nbsp+name markup. One
				// nbsp alone was too narrow next to the icon's own width
				// (bbguildwow#379-adjacent feedback) to read as a real gap.
				$link = $bbtips_wow->build_link('spell', $spell_id, array('text' => "\u{00A0}\u{00A0}" . $name));
			}

			$this->template->assign_block_vars($block, array(
				'NAME' => $name,
				'RANK' => $rank,
				'S_MULTI_RANK' => $rank > 1,
				'SPELL_ID' => $spell_id,
				'LINK' => $link,
			));
		}
	}
}
