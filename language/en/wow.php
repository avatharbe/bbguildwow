<?php
/**
 * bbguildwow language file [English]
 *
 * @package   phpBB Extension - bbguildwow
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
	'WOW_PHASE_PLAYER_ACHIEVEMENTS' => 'Character Achievements',
	'WOW_GUILD_SYNC_EXPLAIN'   => 'Syncs guild data from the Battle.net API in 3 phases:<br />'
		. '1. <strong>Roster</strong> — Guild roster (<code>/data/wow/guild/{realm}/{name}/roster</code>)<br />'
		. '2. <strong>Specializations</strong> — per character (<code>/profile/wow/character/{realm}/{name}/specializations</code>)<br />'
		. '3. <strong>Portraits</strong> — per character (<code>/profile/wow/character/{realm}/{name}/character-media</code>)<br />'
		. 'Large guilds may take a few minutes.',
	'WOW_SYNC_ACHIEVEMENTS_LABEL'   => 'Sync Achievements',
	'WOW_SYNC_ACHIEVEMENTS_EXPLAIN' => 'Syncs achievement data from the Battle.net API in 3 phases:<br />'
		. '1. <strong>Achievement Categories</strong> — Category index + per-category detail (<code>/data/wow/achievement-category/index</code>)<br />'
		. '2. <strong>Achievements</strong> — Guild achievements + per-achievement detail (<code>/data/wow/guild/{realm}/{name}/achievements</code>)<br />'
		. '3. <strong>Character Achievements</strong> — per character, for the Achievements player-detail tab (<code>/profile/wow/character/{realm}/{name}/achievements</code>)<br />'
		. 'Detail fetching is time-limited per batch; large guilds may take multiple clicks.',
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
	'WOW_AVG_ILVL'             => 'Average Item Level',
	'WOW_STATS'                => 'Character Stats',
	'WOW_STAT_GROUP_BASE'      => 'Base',
	'WOW_STAT_GROUP_MELEE'     => 'Melee',
	'WOW_STAT_GROUP_SPELL'     => 'Spell',
	'WOW_LOADING'              => 'Loading…',
	'WOW_LOADING_ERROR'        => 'Error loading stats.',
	'WOW_PROFESSIONS'          => 'Professions',
	'WOW_MYTHIC_PLUS'          => 'Mythic+',
	'WOW_MPLUS_RATING'         => 'M+ Rating',
	'WOW_DUNGEON'              => 'Dungeon',
	'WOW_KEY_LEVEL'            => 'Key',
	'WOW_TIME'                 => 'Time',
	'WOW_PVP'                  => 'PvP',
	'WOW_HONOR_LEVEL'          => 'Honor Level',
	'WOW_PVP_NO_DATA'          => 'No PvP data available.',
	'WOW_TALENTS'              => 'Talents',
	'WOW_TALENTS_CLASS'        => 'Class Talents',
	'WOW_TALENTS_SPEC'         => 'Specialization Talents',
	'WOW_TALENTS_HERO'         => 'Hero Talents',
	'WOW_TALENTS_NO_DATA'      => 'No talent data available.',
	'WOW_RAID_PROGRESSION'     => 'Raid Progression',
	'WOW_TAB_COMING_SOON'      => 'Coming soon.',
	'WOW_ACHIEVEMENTS'         => 'Achievements',
	'WOW_ACHIEVEMENTS_EARNED'  => 'achievements earned',
	'WOW_ACHIEVEMENTS_NO_DATA' => 'No achievements earned yet.',

	// is_enableable() error messages
	'BBGUILDWOW_PHP_VERSION_FAIL'		=> 'This extension requires PHP %1$s or higher. You are running PHP %2$s.',
	'BBGUILDWOW_PHPBB_VERSION_FAIL'		=> 'This extension requires phpBB %1$s or higher. You are running phpBB %2$s.',
	'BBGUILDWOW_REQUIRES_BBGUILD'		=> 'This extension requires the bbGuild core extension (avathar/bbguild) to be enabled first.',
	'BBGUILDWOW_REQUIRES_BBGUILD_VERSION'	=> 'This extension requires bbGuild core (avathar/bbguild) version %1$s or newer. Installed version: %2$s.',

	// Sync AJAX controller messages (portrait_controller, achievement_controller, achievement_sync_controller)
	'WOW_SYNC_INSUFFICIENT_PERMISSIONS' => 'Insufficient permissions.',
	'WOW_SYNC_GUILD_NOT_WOW'            => 'Guild not found or not a WoW guild.',
	'WOW_SYNC_CREDENTIALS_MISSING'      => 'API credentials not configured.',
	'WOW_SYNC_API_ERROR'                => 'API error %d',
	'WOW_SYNC_EMPTY_RESPONSE'           => 'Empty response',
	'WOW_SYNC_DETAIL_WITH_URL'          => '%1$s (URL: %2$s)',
	'WOW_SYNC_ROSTER_RESULT'            => 'Roster synced: %d members.',
	'WOW_SYNC_SPECS_UP_TO_DATE'         => 'All specs are up to date.',
	'WOW_SYNC_PORTRAITS_UP_TO_DATE'     => 'All portraits are up to date.',
	'WOW_SYNC_EQUIPMENT_UP_TO_DATE'     => 'All equipment is up to date.',
	'WOW_SYNC_GAME_LOAD_FAILED'         => 'Could not load game: %s',
	'WOW_SYNC_GUILD_LOAD_FAILED'        => 'Could not load guild: %s',
	'WOW_ACHIEV_CATEGORY_NOT_FOUND'     => 'Category not found',
	'WOW_ACHIEV_NOT_FOUND'              => 'Achievement not found',
	'WOW_SYNC_LOG_CATEGORIES'           => 'Categories: %s',
	'WOW_SYNC_LOG_ACHIEVEMENTS'         => 'Achievements: %s',
	'WOW_SYNC_LOG_MEMBERS_COUNT'        => '%d members',

	// game/wow_api.php sync result messages (keep in sync with wow_api::LANG_FALLBACK)
	'WOW_API_PORTRAITS_UP_TO_DATE' => 'All player portraits are up to date.',
	'WOW_API_PORTRAITS_FETCHED'    => 'Fetched %d portraits.',
	'WOW_API_SPECS_UP_TO_DATE'     => 'All player specs are up to date.',
	'WOW_API_SPECS_FETCHED'        => 'Fetched %d specs.',
	'WOW_API_EQUIPMENT_UP_TO_DATE' => 'All player equipment is up to date.',
	'WOW_API_EQUIPMENT_FETCHED'    => 'Fetched equipment for %d players.',
	'WOW_API_ACHIEVEMENTS_UP_TO_DATE' => 'All player achievements are up to date.',
	'WOW_API_ACHIEVEMENTS_FETCHED'    => 'Fetched achievements for %d players.',
	'WOW_API_BATCH_FAILED'         => ' %d failed [%s].',
	'WOW_API_BATCH_REMAINING'      => ' %d remaining.',
	'WOW_API_ERR_404'              => '404 Not Found',
	'WOW_API_ERR_403'              => '403 Forbidden',
	'WOW_API_ERR_500'              => '500 Server Error',
	'WOW_API_ERR_502'              => '502 Bad Gateway',
	'WOW_API_ERR_503'              => '503 Service Unavailable',
	'WOW_API_ERR_504'              => '504 Gateway Timeout',
	'WOW_API_ERR_NO_AVATAR'        => 'No avatar data',
	'WOW_API_ERR_NO_SPEC'          => 'No spec data',
	'WOW_API_ERR_UNKNOWN'          => 'Unknown error',
	'WOW_API_ERR_HTTP_CODE'        => 'HTTP %s',
	'WOW_API_FACTION_ALLIANCE'     => 'Alliance',
	'WOW_API_FACTION_HORDE'        => 'Horde',
	'WOW_API_DEFAULT_RANK_NAME'    => 'Rank%d',

	// model/achievement.php sync result messages (keep in sync with achievement::LANG_FALLBACK)
	'WOW_ACH_ARMORY_DISABLED_GAME'    => 'Armory is not enabled for this game. Enable it in ACP Game settings.',
	'WOW_ACH_ARMORY_DISABLED_GUILD'   => 'Armory is not enabled for this guild. Enable it in ACP Guild settings.',
	'WOW_ACH_CREDENTIALS_MISSING'     => 'Battle.net API credentials not configured. Set Client ID and Secret in ACP Game settings.',
	'WOW_ACH_GUILD_SLUG_EMPTY'        => 'Guild realm or name is empty (realm="%s", name="%s"). Check guild settings.',
	'WOW_ACH_API_ERROR_DETAIL'        => 'API error %d: %s',
	'WOW_ACH_UNKNOWN_SHORT'           => 'Unknown',
	'WOW_ACH_EMPTY_RESPONSE_HTTP'     => 'Empty response (HTTP %s)',
	'WOW_ACH_GUILD_NOT_FOUND_DETAIL'  => '%s. Could not find guild "%s" on realm "%s" (region: %s). Request URL: %s',
	'WOW_ACH_API_EMPTY_RESPONSE'      => 'Achievements API returned empty response (HTTP %s). %s URL: %s',
	'WOW_ACH_UNKNOWN_ERROR'           => 'Unknown error',
	'WOW_ACH_API_ERROR'               => 'Achievements API error %d: %s. URL: %s',
	'WOW_ACH_NO_ACHIEVEMENTS_ARRAY'   => 'API response has no achievements array. Response keys: %s',
	'WOW_ACH_SYNCED_RESULT'           => 'Synced %d achievements, fetched details for %d.',
	'WOW_ACH_REMAINING_DETAILS'       => ' %d achievements still need details — click "Load from API" again to fetch more.',
	'WOW_ACH_CAT_ARMORY_DISABLED'     => 'Armory is not enabled for this game.',
	'WOW_ACH_CAT_CREDENTIALS_MISSING' => 'Battle.net API credentials not configured.',
	'WOW_ACH_CAT_API_ERROR'           => 'Category index API error: %s',
	'WOW_ACH_CAT_SYNCED_RESULT'       => 'Synced %d categories, inserted %d new achievements, mapped %d.',
	'WOW_ACH_CAT_REMAINING'           => ' %d achievements still need category mapping — click "Sync Categories" again.',

	// controller/asset_controller.php
	'WOW_ASSET_NOT_FOUND' => 'Not found',

	// api/battlenet_resource.php — referenced but previously undefined (#37 follow-up audit)
	'NO_METHODS' => 'No HTTP methods configured for this API resource.',

	// game/wow_provider.php (keep in sync with wow_provider::LANG_FALLBACK)
	'WOW_PROVIDER_GAME_NAME'     => 'World of Warcraft',
	'WOW_PROVIDER_SPEC_LABEL'    => 'Specialization',
	'WOW_PROVIDER_ARMOR_CLOTH'   => 'Cloth',
	'WOW_PROVIDER_ARMOR_LEATHER' => 'Leather',
	'WOW_PROVIDER_ARMOR_MAIL'    => 'Mail',
	'WOW_PROVIDER_ARMOR_PLATE'   => 'Plate',
));
