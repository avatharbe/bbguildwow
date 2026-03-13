<?php
/**
 * bbguild_wow language file [English]
 *
 * @package   phpBB Extension - bbguild_wow
 * @copyright 2009 bbguild
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
	'WOWAPI' => 'WoW Armory',
	'WOWAPIKEY' => 'Client ID',
	'WOWAPIKEY_EXPLAIN' => 'Create an API client at <a href="https://develop.battle.net/access/clients">develop.battle.net/access/clients</a> to obtain your Client ID.',
	'WOWPRIVKEY' => 'Client Secret',
	'WOWPRIVKEY_EXPLAIN' => 'The Client Secret from your Battle.net API client. Required for API access.',
	'WOWAPILOCALE' => 'Locale',
	'WOWAPILOCALE_EXPLAIN' => 'Battle.net API resources provide localized strings using the locale query string parameter. The available locales supported vary from region to region and align with those supported on the community sites.',
	'WOWAPI_LOCALE_NOTALLOWED' => 'Illegal locale %s: choose one depending on your WoW region: en_GB, en_US, de_DE, es_ES, fr_FR, it_IT, pt_PT, pt_BR, or ru_RU',
	'WOWAPI_KEY_MISSING' => 'Please create an API client at <a href="https://develop.battle.net/access/clients">develop.battle.net</a> and enter your Client ID and Client Secret.',
	'WOWAPI_TOKEN_FAILED' => 'Failed to obtain OAuth access token from Battle.net. Please verify your Client ID and Client Secret.',
	'WOWAPI_METH_NOTALLOWED' => 'Method not allowed.',
	'WOWAPI_REGION_NOTALLOWED' => 'Region not allowed.',
	'WOWAPI_API_NOTIMPLEMENTED' => 'API not allowed.',
	'WOWAPI_NO_REALMS' => 'No realm specified.',
	'WOWAPI_NO_GUILD' => 'Guild name not specified.',
	'WOWAPI_INVALID_FIELD' => 'Invalid field requested: %s',
	'WOWAPI_NO_CHARACTER' => 'Character name not specified.',
	'CHARACTERAPICALL' => 'Update Players from Character API',
	'CALL_BATTLENET_CHAR_API' => 'Call Battle.net Character API for this Guild. Toggles to inactive if lastModified flag was > 90 days ago, reactivates if < 90 and character deactivation status was \'API\'.',
	'ARM_SHOWACH' => 'Show Achievement Points',
	'ARM_SHOWACH_EXPLAIN' => 'Display achievement point totals on the guild roster.',

	// Portal module names
	'BBGUILD_PORTAL_ACHIEVEMENTS' => 'Achievements',
	'BBGUILD_PORTAL_GUILD_NEWS'   => 'Guild News',

	// Achievements module
	'ACHIEV_PROGRESS_OVERVIEW' => 'Progress Overview',
	'ACHIEV_TOTAL_COMPLETED'   => 'Total Completed',
	'ACHIEV_RECENTLY_EARNED'   => 'Recently Earned',
	'ACHIEV_POINTS_TOTAL'      => 'Achievement Points',
	'ACHIEV_COMPLETED_LABEL'   => 'achievements completed',

	// Achievement browser
	'ACHIEV_BACK'              => 'Achievements',
	'ACHIEV_NO_CATEGORIES'     => 'No achievement categories synced yet. Sync categories via ACP.',
	'ACHIEV_NOT_COMPLETED'     => 'Not completed',
	'ACHIEV_CRITERIA'          => 'Criteria',
	'ACHIEV_FEATS_OF_STRENGTH' => 'Feats of Strength',
	'ACHIEV_SYNC_CATEGORIES'   => 'Sync Categories',
));
