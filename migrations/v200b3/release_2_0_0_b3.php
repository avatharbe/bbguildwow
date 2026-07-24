<?php
/**
 * bbGuild WoW Extension — finalizing migration for 2.0.0-b3
 *
 * Pulls in all v200b3 sibling migrations (player equipment table,
 * player render URL column, specialization seed, spec translation
 * seed).
 *
 * Canonical version lives in ext::BBGUILDWOW_VERSION; not in phpbb_config.
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\migrations\v200b3;

class release_2_0_0_b3 extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return [
			'\avathar\bbguildwow\migrations\v200b3\add_player_equipment',
			'\avathar\bbguildwow\migrations\v200b3\add_player_render_url',
			'\avathar\bbguildwow\migrations\v200b3\seed_specializations',
			'\avathar\bbguildwow\migrations\v200b3\seed_spec_translations',
		];
	}

	public function effectively_installed()
	{
		// Version lives in ext::BBGUILDWOW_VERSION, not phpbb_config; check
		// a concrete artifact created by this migration's own dependency
		// chain instead.
		return $this->db_tools->sql_table_exists($this->table_prefix . 'bb_player_equipment');
	}
}
