<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguildwow\tests\integration;

/**
 * Shared harness for integration tests that need a real curl round trip
 * against the Battle.net SDK (api/battlenet_resource.php and its
 * subclasses use raw curl_exec(), not an injectable HTTP client, so
 * there is nothing to swap for a mock at the PHP level). Starts a real
 * local PHP built-in server for the duration of the test class, driven
 * by a JSON control file that individual tests configure per scenario.
 *
 * Extends phpbb_functional_test_case (not phpbb_database_test_case, the
 * base class tests/integration-tests.md originally suggested) so it
 * reuses the exact DB access and extension-setup mechanism the
 * functional/smoke test suites already use — phpbb_database_test_case
 * pulls in a separate dbunit/XML-fixture stack unused anywhere else in
 * this codebase.
 */
abstract class mock_battlenet_test_case extends \phpbb_functional_test_case
{
	/** @var resource|null */
	private static $server_process;

	/** @var int */
	protected static $mock_server_port = 18098;

	/** @var string */
	private static $control_file;

	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();

		self::$control_file = sys_get_temp_dir() . '/bbguildwow_mock_battlenet_control.json';
		self::start_mock_server();
	}

	public static function tearDownAfterClass(): void
	{
		self::stop_mock_server();
		parent::tearDownAfterClass();
	}

	private static function start_mock_server(): void
	{
		file_put_contents(self::$control_file, json_encode(array('routes' => array(), 'calls' => array())));
		putenv('BBGUILDWOW_MOCK_CONTROL_FILE=' . self::$control_file);

		$router = __DIR__ . '/mock_battlenet_server.php';
		$cmd = sprintf(
			'%s -S 127.0.0.1:%d %s',
			escapeshellarg(PHP_BINARY),
			self::$mock_server_port,
			escapeshellarg($router)
		);

		$descriptors = array(1 => array('pipe', 'w'), 2 => array('pipe', 'w'));
		self::$server_process = proc_open($cmd, $descriptors, $pipes);

		if (!is_resource(self::$server_process))
		{
			self::fail('Could not start the mock Battle.net server.');
		}

		$deadline = microtime(true) + 5;
		while (microtime(true) < $deadline)
		{
			$ch = curl_init(self::base_url() . '__health');
			curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 1));
			$result = curl_exec($ch);
			curl_close($ch);

			if ($result === 'ok')
			{
				return;
			}

			usleep(100000);
		}

		self::fail('Mock Battle.net server did not become ready in time.');
	}

	private static function stop_mock_server(): void
	{
		if (is_resource(self::$server_process))
		{
			proc_terminate(self::$server_process);
			proc_close(self::$server_process);
		}
	}

	protected static function base_url(): string
	{
		return 'http://127.0.0.1:' . self::$mock_server_port . '/';
	}

	/**
	 * Configure the mock server's responses and reset call counters.
	 *
	 * @param array $routes Path => list of {status, body} responses,
	 *                       consumed in order per path (see
	 *                       mock_battlenet_server.php's docblock).
	 */
	protected function configure_mock_routes(array $routes): void
	{
		file_put_contents(self::$control_file, json_encode(array(
			'routes' => $routes,
			'calls'  => array(),
		)));
	}

	protected function mock_call_count(string $path): int
	{
		$control = json_decode(file_get_contents(self::$control_file), true);

		return $control['calls'][$path] ?? 0;
	}

	/**
	 * A real, stateful (in-memory) stand-in for \phpbb\cache\service.
	 * get()/put()/destroy() are magic-proxied to the driver on the real
	 * class (see the addMethods() note in tests/api/battlenet_resource_test.php),
	 * so PHPUnit's mock builder + a captured-by-reference array gives a
	 * genuine cache round trip without needing to wire up a real driver,
	 * config, db, and event dispatcher.
	 *
	 * @return \phpbb\cache\service|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected function make_stateful_cache()
	{
		$cache = $this->getMockBuilder(\phpbb\cache\service::class)
			->disableOriginalConstructor()
			->addMethods(array('get', 'put', 'destroy'))
			->getMock();

		$store = array();

		$cache->method('get')->willReturnCallback(function ($key) use (&$store) {
			return $store[$key] ?? false;
		});
		$cache->method('put')->willReturnCallback(function ($key, $value, $ttl = 0) use (&$store) {
			$store[$key] = $value;
		});
		$cache->method('destroy')->willReturnCallback(function ($key) use (&$store) {
			unset($store[$key]);
		});

		return $cache;
	}
}
