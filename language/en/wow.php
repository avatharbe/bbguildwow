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
	'ARM_ACHIEV_HIDE_EMPTY' => 'Hide empty achievement categories',
	'ARM_ACHIEV_HIDE_EMPTY_EXPLAIN' => 'Hide achievement categories with no progress from the portal. Users can toggle this from the frontend.',

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
	'WOW_SYNC_PORTRAITS'       => 'Sync Portraits',
	'WOW_SYNC_PORTRAITS_EXPLAIN' => 'Fetch character portraits from the Battle.net Character Media API. Processes ~20 characters per click.',
	'WOW_SYNC_PROGRESS'        => 'Sync Progress',
	'WOW_PHASE_ROSTER'         => 'Roster',
	'WOW_PHASE_SPECS'          => 'Specializations',
	'WOW_PHASE_PORTRAITS'      => 'Portraits',
	'WOW_PHASE_CATEGORIES'     => 'Achievement Categories',
	'WOW_PHASE_ACHIEVEMENTS'   => 'Achievements',
	'WOW_GUILD_SYNC_EXPLAIN'   => 'Syncs guild data from the Battle.net API in 3 phases:<br />'
		. '1. <strong>Roster</strong> — Guild roster (<code>/data/wow/guild/{realm}/{name}/roster</code>)<br />'
		. '2. <strong>Specializations</strong> — per character (<code>/profile/wow/character/{realm}/{name}/specializations</code>)<br />'
		. '3. <strong>Portraits</strong> — per character (<code>/profile/wow/character/{realm}/{name}/character-media</code>)<br />'
		. 'Large guilds may take a few minutes.',
	'WOW_SYNC_ACHIEVEMENTS_LABEL'   => 'Sync Achievements',
	'WOW_SYNC_ACHIEVEMENTS_EXPLAIN' => 'Syncs achievement data from the Battle.net API in 2 phases:<br />'
		. '1. <strong>Achievement Categories</strong> — Category index + per-category detail (<code>/data/wow/achievement-category/index</code>)<br />'
		. '2. <strong>Achievements</strong> — Guild achievements + per-achievement detail (<code>/data/wow/guild/{realm}/{name}/achievements</code>)<br />'
		. 'Detail fetching is time-limited per batch.',
	'ACHIEV_SHOW_EMPTY'        => 'Show all categories',
	'ACHIEV_HIDE_EMPTY'        => 'Hide empty categories',

	// Game edition (Classic support)
	'WOW_EDITION'              => 'Game Edition',
	'WOW_EDITION_EXPLAIN'      => 'Select the WoW edition for this guild. This determines the API namespace used for data retrieval.',
	'WOW_EDITION_RETAIL'       => 'Retail',
	'WOW_EDITION_CLASSIC_ERA'  => 'Classic Era',
	'WOW_EDITION_CLASSIC_PROG' => 'Classic (Progression)',
	'WOW_EDITION_CLASSIC_ANN'  => 'Classic (Anniversary)',

	// Sync phases
	'WOW_PHASE_EQUIPMENT'      => 'Equipment',

	// Player detail page
	'WOW_CHARACTER_INFO'       => 'Character Info',
	'WOW_SPECIALIZATION'       => 'Specialization',
	'WOW_EQUIPMENT'            => 'Equipment',
	'WOW_AVG_ILVL'             => 'Average Item Level',
	'WOW_STATS'                => 'Character Stats',
	'WOW_PROFESSIONS'          => 'Professions',
));
