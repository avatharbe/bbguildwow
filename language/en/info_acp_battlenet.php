<?php
/**
 * bbguildwow ACP language file for Battle.net API (EN)
 *
 * @package   phpBB Extension - bbguildwow
 * @copyright 2018 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge(
	$lang, array(
	'ACP_WOW_BATTLENET'            => 'Battle.net API',
	'ACP_WOW_BATTLENET_EXPLAIN'    => 'Manage the Battle.net OAuth 2.0 API connection for World of Warcraft. API credentials are configured in <em>Games &gt; Edit World of Warcraft</em>.',

	'WOW_BNET_CREDENTIALS'         => 'API Credentials',
	'WOW_BNET_NOT_SET'             => 'Not configured',
	'WOW_BNET_EDIT_CREDENTIALS'    => 'Edit WoW Game Settings',

	'WOW_BNET_TEST_CONNECTION'     => 'Test Connection',
	'WOW_BNET_TEST_EXPLAIN'        => 'Test the OAuth 2.0 token exchange with Battle.net to verify your Client ID and Client Secret are valid.',
	'WOW_BNET_TEST_REGION'         => 'Region',
	'WOW_BNET_TEST_NOW'            => 'Test Connection',
	'WOW_BNET_STATUS_OK'           => 'Connection Successful',
	'WOW_BNET_STATUS_FAIL'         => 'Connection Failed',
	'WOW_BNET_TEST_SUCCESS'        => 'Successfully obtained OAuth token for region %s. Token expires in %s hours.',
	'WOW_BNET_NO_CREDENTIALS'      => 'Client ID and Client Secret must be configured before testing.',
	'WOW_BNET_CURL_FAILED'         => 'Could not initialize cURL.',
	'WOW_BNET_CURL_ERROR'          => 'cURL error: %s',
	'WOW_BNET_AUTH_FAILED'         => 'Authentication failed (HTTP %d): %s',
	'WOW_BNET_NO_TOKEN'            => 'Battle.net responded but did not provide an access token.',

	'WOW_BNET_TOKEN_CACHE'         => 'Token Cache',
	'WOW_BNET_TOKEN_CACHE_EXPLAIN' => 'OAuth tokens are cached per region to avoid unnecessary token requests. Tokens typically expire after 24 hours.',
	'WOW_BNET_REGION'              => 'Region',
	'WOW_BNET_TOKEN_STATUS'        => 'Status',
	'WOW_BNET_TOKEN_PREVIEW'       => 'Token',
	'WOW_BNET_CACHED'              => 'Cached',
	'WOW_BNET_NOT_CACHED'          => 'No token',
	'WOW_BNET_CLEAR_CACHE'         => 'Clear All Cached Tokens',
	'WOW_BNET_CACHE_CLEARED'       => 'All cached OAuth tokens have been cleared.',

	'WOW_SYNC_SCHEDULE'            => 'Scheduled Guild Sync',
	'WOW_SYNC_SCHEDULE_EXPLAIN'    => 'Periodically sync each WoW guild\'s roster (members, faction, emblem) from the Battle.net API via phpBB cron. Character profiles (item level, specialization) and the guild activity feed are synced separately and are not part of this schedule.',
	'WOW_SYNC_ENABLED'             => 'Enable scheduled sync',
	'WOW_SYNC_INTERVAL'            => 'Sync interval',
	'WOW_SYNC_INTERVAL_1H'         => 'Every hour',
	'WOW_SYNC_INTERVAL_3H'         => 'Every 3 hours',
	'WOW_SYNC_INTERVAL_6H'         => 'Every 6 hours',
	'WOW_SYNC_INTERVAL_12H'        => 'Every 12 hours',
	'WOW_SYNC_INTERVAL_24H'        => 'Every 24 hours',
	'WOW_SYNC_SAVE_SETTINGS'       => 'Save Settings',
	'WOW_SYNC_SETTINGS_SAVED'      => 'Sync settings have been saved.',
	'WOW_SYNC_LAST_RUN'            => 'Last sync',
	'WOW_SYNC_NEVER'               => 'Never run',
	'WOW_SYNC_LAST_RESULT'         => 'Last result',
	'WOW_SYNC_NOW'                 => 'Sync Now',
	'WOW_SYNC_NOW_DONE'            => 'Guild roster sync has been run.',
));
