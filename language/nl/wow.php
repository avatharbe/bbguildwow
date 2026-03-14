<?php
/**
 * bbguild_wow language file [Dutch]
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
	'WOWAPIKEY' => 'Client-ID',
	'WOWAPIKEY_EXPLAIN' => 'Maak een API-client aan op <a href="https://develop.battle.net/access/clients">develop.battle.net/access/clients</a> om je Client-ID te verkrijgen.',
	'WOWPRIVKEY' => 'Client-secret',
	'WOWPRIVKEY_EXPLAIN' => 'Het client-secret van je Battle.net API-client. Vereist voor API-toegang.',
	'WOWAPILOCALE' => 'Taalinstelling',
	'WOWAPILOCALE_EXPLAIN' => 'De Battle.net API biedt gelokaliseerde teksten via de locale parameter. De beschikbare taalinstellingen variëren per regio.',
	'WOWAPI_LOCALE_NOTALLOWED' => 'Ongeldige locale %s: kies een van de volgende afhankelijk van je WoW-regio: en_GB, en_US, de_DE, es_ES, fr_FR, it_IT, pt_PT, pt_BR, of ru_RU',
	'WOWAPI_KEY_MISSING' => 'Maak een API-client aan op <a href="https://develop.battle.net/access/clients">develop.battle.net</a> en vul je Client-ID en Client-secret in.',
	'WOWAPI_TOKEN_FAILED' => 'Kan geen OAuth-toegangstoken verkrijgen van Battle.net. Controleer je Client-ID en Client-secret.',
	'WOWAPI_METH_NOTALLOWED' => 'Methode niet toegestaan.',
	'WOWAPI_REGION_NOTALLOWED' => 'Regio niet toegestaan.',
	'WOWAPI_API_NOTIMPLEMENTED' => 'API niet toegestaan.',
	'WOWAPI_NO_REALMS' => 'Geen realm opgegeven.',
	'WOWAPI_NO_GUILD' => 'Gildnaam niet opgegeven.',
	'WOWAPI_INVALID_FIELD' => 'Ongeldig veld aangevraagd: %s',
	'WOWAPI_NO_CHARACTER' => 'Personagenaam niet opgegeven.',
	'CHARACTERAPICALL' => 'Spelers bijwerken via Character API',
	'CALL_BATTLENET_CHAR_API' => 'Roep de Battle.net Character API aan voor deze gilde. Schakelt over naar inactief als lastModified langer dan 90 dagen geleden was, heractiveert als minder dan 90 dagen en de deactivatiestatus \'API\' was.',
	'ARM_SHOWACH' => 'Prestatiepunten tonen',
	'ARM_SHOWACH_EXPLAIN' => 'Prestatiepunttotalen tonen in de gildelijst.',
	'ARM_ACHIEV_HIDE_EMPTY' => 'Lege prestatiecategorieën verbergen',
	'ARM_ACHIEV_HIDE_EMPTY_EXPLAIN' => 'Verberg prestatiecategorieën zonder voortgang in het portaal. Gebruikers kunnen dit in de frontend schakelen.',

	// Portal module names
	'BBGUILD_PORTAL_ACHIEVEMENTS' => 'Prestaties',
	'BBGUILD_PORTAL_GUILD_NEWS'   => 'Gilde nieuws',

	// Achievements module
	'ACHIEV_PROGRESS_OVERVIEW' => 'Voortgangsoverzicht',
	'ACHIEV_TOTAL_COMPLETED'   => 'Totaal voltooid',
	'ACHIEV_RECENTLY_EARNED'   => 'Recent behaald',
	'ACHIEV_POINTS_TOTAL'      => 'Prestatiepunten',
	'ACHIEV_COMPLETED_LABEL'   => 'achievements completed',

	// Achievement browser
	'ACHIEV_BACK'              => 'Prestaties',
	'ACHIEV_NO_CATEGORIES'     => 'Nog geen prestatiecategorieën gesynchroniseerd. Synchroniseer categorieën via ACP.',
	'ACHIEV_NOT_COMPLETED'     => 'Niet voltooid',
	'ACHIEV_CRITERIA'          => 'Criteria',
	'ACHIEV_FEATS_OF_STRENGTH' => 'Feats of Strength',
	'ACHIEV_SYNC_CATEGORIES'   => 'Categorieën synchroniseren',
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
	'ACHIEV_SHOW_EMPTY'        => 'Alle categorieën tonen',
	'ACHIEV_HIDE_EMPTY'        => 'Lege categorieën verbergen',
));
