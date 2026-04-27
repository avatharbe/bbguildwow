<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * WoW spec catalog tests — issue #331 Phase 4
 */

namespace avathar\bbguildwow\tests\game;

use avathar\bbguildwow\game\wow_provider;
use PHPUnit\Framework\TestCase;

class wow_provider_spec_catalog_test extends TestCase
{
	public function test_catalog_covers_all_thirteen_classes(): void
	{
		$catalog = wow_provider::spec_catalog();
		$this->assertSame(range(1, 13), array_keys($catalog));
	}

	public function test_total_spec_count_is_thirty_nine(): void
	{
		$catalog = wow_provider::spec_catalog();
		$total = 0;
		foreach ($catalog as $specs)
		{
			$total += count($specs);
		}
		// 11 classes × 3 specs + Druid (4) + Demon Hunter (2) = 33+4+2 = 39
		$this->assertSame(39, $total);
	}

	public function test_druid_has_four_specs_and_demon_hunter_has_two(): void
	{
		$catalog = wow_provider::spec_catalog();
		$this->assertCount(4, $catalog[11], 'Druid');
		$this->assertCount(2, $catalog[12], 'Demon Hunter');
	}

	public function test_every_spec_has_required_fields(): void
	{
		foreach (wow_provider::spec_catalog() as $class_id => $specs)
		{
			foreach ($specs as $spec)
			{
				$this->assertArrayHasKey('spec_name',  $spec);
				$this->assertArrayHasKey('role_id',    $spec);
				$this->assertArrayHasKey('spec_icon',  $spec);
				$this->assertArrayHasKey('spec_order', $spec);
				$this->assertNotEmpty($spec['spec_name'], "class $class_id");
				$this->assertNotEmpty($spec['spec_icon'], "class $class_id / {$spec['spec_name']}");
				$this->assertContains($spec['role_id'], [0, 1, 2], "class $class_id / {$spec['spec_name']}");
				$this->assertGreaterThan(0, $spec['spec_order'], "class $class_id / {$spec['spec_name']}");
			}
		}
	}

	public function test_spec_orders_are_unique_per_class(): void
	{
		foreach (wow_provider::spec_catalog() as $class_id => $specs)
		{
			$orders = array_column($specs, 'spec_order');
			$this->assertSame(count($orders), count(array_unique($orders)), "duplicate spec_order in class $class_id");
		}
	}

	public function test_spec_names_are_unique_per_class(): void
	{
		foreach (wow_provider::spec_catalog() as $class_id => $specs)
		{
			$names = array_column($specs, 'spec_name');
			$this->assertSame(count($names), count(array_unique($names)), "duplicate spec_name in class $class_id");
		}
	}

	public function test_known_role_assignments(): void
	{
		$catalog = wow_provider::spec_catalog();
		// Mage class 8: all DPS
		foreach ($catalog[8] as $spec)
		{
			$this->assertSame(0, $spec['role_id'], "Mage / {$spec['spec_name']} expected DPS");
		}
		// Holy/Disc Priest are healers
		$priest_by_name = array_column($catalog[5], null, 'spec_name');
		$this->assertSame(1, $priest_by_name['Holy']['role_id']);
		$this->assertSame(1, $priest_by_name['Discipline']['role_id']);
		$this->assertSame(0, $priest_by_name['Shadow']['role_id']);
		// Blood DK is the tank
		$dk_by_name = array_column($catalog[6], null, 'spec_name');
		$this->assertSame(2, $dk_by_name['Blood']['role_id']);
	}

	public function test_translations_cover_supported_locales(): void
	{
		$translations = wow_provider::spec_translations();
		$this->assertEqualsCanonicalizing(['de', 'fr', 'it', 'es_x_tu'], array_keys($translations));
	}

	public function test_each_locale_has_entries_for_every_unique_canonical_name(): void
	{
		$catalog = wow_provider::spec_catalog();
		$canonical = [];
		foreach ($catalog as $specs)
		{
			foreach ($specs as $spec)
			{
				$canonical[$spec['spec_name']] = true;
			}
		}
		$canonical_names = array_keys($canonical);

		foreach (wow_provider::spec_translations() as $locale => $map)
		{
			foreach ($canonical_names as $name)
			{
				$this->assertArrayHasKey(
					$name,
					$map,
					"Locale '$locale' missing translation for canonical name '$name'"
				);
				$this->assertNotEmpty($map[$name], "Locale '$locale' / '$name' is empty");
			}
		}
	}

	public function test_known_translations(): void
	{
		$translations = wow_provider::spec_translations();
		// Spot-check a few well-known mappings.
		$this->assertSame('Frost',     $translations['de']['Frost']);
		$this->assertSame('Givre',     $translations['fr']['Frost']);
		$this->assertSame('Gelo',      $translations['it']['Frost']);
		$this->assertSame('Escarcha',  $translations['es_x_tu']['Frost']);
		$this->assertSame('Heilig',    $translations['de']['Holy']);
		$this->assertSame('Sacré',     $translations['fr']['Holy']);
		$this->assertSame('Sacro',     $translations['it']['Holy']);
		$this->assertSame('Sagrado',   $translations['es_x_tu']['Holy']);
	}
}
