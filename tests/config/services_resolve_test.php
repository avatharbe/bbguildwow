<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguildwow\tests\config;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Smoke check for config/services.yml: every declared class exists and
 * autoloads. Catches typos in class names/paths that a full container
 * build would also catch, but cheaply and without needing a booted
 * board — the DI container itself is only ever assembled inside a real
 * phpBB request, not inside a PHPUnit process, so this is deliberately
 * a static check rather than an actual container resolve.
 */
class services_resolve_test extends TestCase
{
	public function service_class_data(): array
	{
		$path = __DIR__ . '/../../config/services.yml';
		$parsed = Yaml::parseFile($path);

		$cases = array();
		foreach ($parsed['services'] as $id => $definition)
		{
			if (!isset($definition['class']))
			{
				continue;
			}
			$cases[$id] = array($id, $definition['class']);
		}

		return $cases;
	}

	/**
	 * @dataProvider service_class_data
	 */
	public function test_service_class_exists(string $service_id, string $class): void
	{
		$this->assertTrue(
			class_exists($class),
			"Service '$service_id' declares class '$class', which does not exist or does not autoload."
		);
	}

	public function test_services_yaml_declares_at_least_one_service(): void
	{
		$this->assertNotEmpty($this->service_class_data());
	}
}
