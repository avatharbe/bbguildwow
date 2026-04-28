<?php
/**
 * bbGuild WoW Extension — finalizing migration for 2.0.0-b3
 *
 * Pulls in all v200b3 sibling migrations (player equipment table,
 * player render URL column, specialization seed, spec translation
 * seed) and bumps `bbguild_wow_version` so future b3+ migrations
 * see it as installed.
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
		return isset($this->config['bbguild_wow_version'])
			&& version_compare($this->config['bbguild_wow_version'], '2.0.0-b3', '>=');
	}

	public function update_data()
	{
		return [
			['config.update', ['bbguild_wow_version', '2.0.0-b3']],
		];
	}

	public function revert_data()
	{
		return [
			['config.update', ['bbguild_wow_version', '2.0.0-b2']],
		];
	}
}
