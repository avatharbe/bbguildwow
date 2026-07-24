<?php
/**
 * bbGuild WoW Extension — router for the local Battle.net API mock server
 *
 * Started via `php -S 127.0.0.1:<port> mock_battlenet_server.php` from
 * mock_battlenet_test_case.php. Serves canned JSON responses driven by
 * a control file the test writes before each request it makes, and
 * records a per-path call count the test can assert on afterward.
 *
 * Control file shape:
 * {
 *   "routes": {
 *     "/token": [{"status": 200, "body": {...}}],
 *     "/data/wow/guild/area-52/my-guild": [
 *       {"status": 401, "body": {...}},
 *       {"status": 200, "body": {...}}
 *     ]
 *   },
 *   "calls": {"/token": 1, "/data/wow/guild/area-52/my-guild": 2}
 * }
 *
 * Each path's responses are consumed in order; once exhausted, the
 * last entry repeats for any further calls to that path.
 *
 * @package   bbguildwow v2.0
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

$control_file = getenv('BBGUILDWOW_MOCK_CONTROL_FILE');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path === '/__health')
{
	http_response_code(200);
	echo 'ok';
	exit;
}

$control = array('routes' => array(), 'calls' => array());
if (file_exists($control_file))
{
	$decoded = json_decode(file_get_contents($control_file), true);
	if (is_array($decoded))
	{
		$control = $decoded;
	}
}

$call_index = $control['calls'][$path] ?? 0;
$control['calls'][$path] = $call_index + 1;
file_put_contents($control_file, json_encode($control));

$responses = $control['routes'][$path] ?? null;
if ($responses === null)
{
	http_response_code(404);
	header('Content-Type: application/json');
	echo json_encode(array('error' => 'no mock route configured for ' . $path));
	exit;
}

$response = $responses[min($call_index, count($responses) - 1)];

http_response_code($response['status']);
header('Content-Type: application/json');
echo json_encode($response['body']);
