<?php
/**
 * bbGuild WoW Extension — seed spec translations into bb_language (#26)
 *
 * Adds locale-specific display names (de/fr/it/es_x_tu) for the WoW
 * specializations seeded by `seed_specializations`. Polish and Dutch
 * are intentionally not seeded — Blizzard doesn't officially translate
 * WoW into those locales — and fall back to the canonical English
 * spec_name on the bb_specializations row.
 *
 * Idempotent: skipped when any 'spec' attribute language row already
 * exists for game_id='wow'.
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\migrations\v200b3;

use avathar\bbguildwow\game\wow_provider;

class seed_spec_translations extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return [
			'\avathar\bbguildwow\migrations\v200b3\seed_specializations',
		];
	}

	public function effectively_installed()
	{
		$lang_table = $this->table_prefix . 'bb_language';
		$sql = 'SELECT 1 FROM ' . $lang_table
			. " WHERE attribute = 'spec' AND game_id = 'wow' LIMIT 1";
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);
		return (bool) $row;
	}

	public function update_data()
	{
		return [
			['custom', [[$this, 'seed_translations']]],
		];
	}

	public function revert_data()
	{
		return [
			['custom', [[$this, 'remove_translations']]],
		];
	}

	public function seed_translations()
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
			return; // No specs seeded yet; seed_specializations migration handles this case.
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

	public function remove_translations()
	{
		$lang_table = $this->table_prefix . 'bb_language';
		$this->db->sql_query('DELETE FROM ' . $lang_table . " WHERE attribute = 'spec' AND game_id = 'wow'");
	}
}
