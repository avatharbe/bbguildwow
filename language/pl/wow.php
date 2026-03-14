<?php
/**
 * bbguild_wow language file [Polish]
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
	'WOWAPIKEY_EXPLAIN' => 'Utwórz klienta API na <a href="https://develop.battle.net/access/clients">develop.battle.net/access/clients</a> aby uzyskać Client ID.',
	'WOWPRIVKEY' => 'Client Secret',
	'WOWPRIVKEY_EXPLAIN' => 'Client Secret z Twojego klienta API Battle.net. Wymagany do dostępu do API.',
	'WOWAPILOCALE' => 'Język',
	'WOWAPILOCALE_EXPLAIN' => 'Zasoby API Battle.net dostarczają zlokalizowane ciągi znaków za pomocą parametru locale. Dostępne ustawienia lokalne różnią się w zależności od regionu.',
	'WOWAPI_LOCALE_NOTALLOWED' => 'Niedozwolony locale %s: wybierz jeden w zależności od regionu WoW: en_GB, en_US, de_DE, es_ES, fr_FR, it_IT, pt_PT, pt_BR lub ru_RU',
	'WOWAPI_KEY_MISSING' => 'Utwórz klienta API na <a href="https://develop.battle.net/access/clients">develop.battle.net</a> i wprowadź swój Client ID oraz Client Secret.',
	'WOWAPI_TOKEN_FAILED' => 'Nie udało się uzyskać tokenu dostępu OAuth z Battle.net. Sprawdź swój Client ID i Client Secret.',
	'WOWAPI_METH_NOTALLOWED' => 'Metoda niedozwolona.',
	'WOWAPI_REGION_NOTALLOWED' => 'Region niedozwolony.',
	'WOWAPI_API_NOTIMPLEMENTED' => 'API niedozwolone.',
	'WOWAPI_NO_REALMS' => 'Nie podano królestwa.',
	'WOWAPI_NO_GUILD' => 'Nie podano nazwy gildii.',
	'WOWAPI_INVALID_FIELD' => 'Żądane pole jest nieprawidłowe: %s',
	'WOWAPI_NO_CHARACTER' => 'Nie podano nazwy postaci.',
	'CHARACTERAPICALL' => 'Aktualizuj graczy z API postaci',
	'CALL_BATTLENET_CHAR_API' => 'Wywołaj API postaci Battle.net dla tej gildii. Przełącza na nieaktywny jeśli lastModified było ponad 90 dni temu, reaktywuje jeśli poniżej 90 dni i status dezaktywacji postaci to \'API\'.',
	'ARM_SHOWACH' => 'Pokaż punkty osiągnięć',
	'ARM_SHOWACH_EXPLAIN' => 'Wyświetlaj sumy punktów osiągnięć na liście gildii.',
	'ARM_ACHIEV_HIDE_EMPTY' => 'Ukryj puste kategorie osiągnięć',
	'ARM_ACHIEV_HIDE_EMPTY_EXPLAIN' => 'Ukryj kategorie osiągnięć bez postępu w portalu. Użytkownicy mogą przełączać to z poziomu interfejsu.',

	// Portal module names
	'BBGUILD_PORTAL_ACHIEVEMENTS' => 'Osiągnięcia',
	'BBGUILD_PORTAL_GUILD_NEWS'   => 'Wiadomości gildii',

	// Achievements module
	'ACHIEV_PROGRESS_OVERVIEW' => 'Przegląd postępów',
	'ACHIEV_TOTAL_COMPLETED'   => 'Ukończone łącznie',
	'ACHIEV_RECENTLY_EARNED'   => 'Ostatnio zdobyte',
	'ACHIEV_POINTS_TOTAL'      => 'Punkty osiągnięć',
	'ACHIEV_COMPLETED_LABEL'   => 'achievements completed',

	// Achievement browser
	'ACHIEV_BACK'              => 'Osiągnięcia',
	'ACHIEV_NO_CATEGORIES'     => 'Brak zsynchronizowanych kategorii osiągnięć. Zsynchronizuj kategorie przez ACP.',
	'ACHIEV_NOT_COMPLETED'     => 'Nie ukończono',
	'ACHIEV_CRITERIA'          => 'Kryteria',
	'ACHIEV_FEATS_OF_STRENGTH' => 'Feats of Strength',
	'ACHIEV_SYNC_CATEGORIES'   => 'Synchronizuj kategorie',
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
	'ACHIEV_SHOW_EMPTY'        => 'Pokaż wszystkie kategorie',
	'ACHIEV_HIDE_EMPTY'        => 'Ukryj puste kategorie',
));
