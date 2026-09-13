<?php
/**
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 * PVP player-detail tab (#375). Real content: honor level, fetched via the
 * same wow_api::fetch_pvp_summary() call #364's async stats endpoint uses.
 * Bracket ratings (2v2/3v3/RBG) are #23 — a future addition to this same
 * tab, not part of this task.
 */

namespace avathar\bbguildwow\portal\player_detail_tabs;

use avathar\bbguild\portal\player_detail_tab_interface;
use avathar\bbguildwow\game\wow_api;
use phpbb\db\driver\driver_interface;
use phpbb\template\template;

class pvp_tab implements player_detail_tab_interface
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
		return 'WOW_PVP';
	}

	public function get_tab_slug(): string
	{
		return 'pvp';
	}

	public function get_tab_order(): int
	{
		return 30;
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
		$honor_level = 0;

		if ($edition === 'retail')
		{
			$raw_pvp = $this->wow_api->fetch_pvp_summary($row['player_name'], $row['player_realm'], $row['player_region'], $edition);
			if ($raw_pvp && isset($raw_pvp['honor_level']))
			{
				$honor_level = (int) $raw_pvp['honor_level'];
			}
		}

		$this->template->assign_vars(array(
			'WOW_PVP_HONOR_LEVEL' => $honor_level,
		));

		return '@avathar_bbguildwow/portal/pvp_tab.html';
	}
}
