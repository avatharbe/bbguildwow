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
	'WOWAPIKEY' => 'Klucz API',
	'WOWAPIKEY_EXPLAIN' => 'Zarejestruj się na dev.battle.net aby uzyskać klucz (<a href="https://dev.battle.net/apps/mykeys">https://dev.battle.net/apps/mykeys</a>)',
	'WOWPRIVKEY' => 'Tajny klucz Mashery',
	'WOWPRIVKEY_EXPLAIN' => '(<i>tajny klucz nie jest obecnie wymagany przez bbGuild</i>)',
	'WOWAPILOCALE' => 'Język',
	'WOWAPILOCALE_EXPLAIN' => 'Zasoby API Battle.NET dostarczają zlokalizowane ciągi znaków za pomocą parametru locale. Dostępne ustawienia lokalne różnią się w zależności od regionu.',
	'WOWAPI_LOCALE_NOTALLOWED' => 'Niedozwolony locale %s : wybierz jeden w zależności od regionu WoW : en_GB, en_US, de_DE, es_ES, fr_FR, it_IT, pt_PT, pt_BR lub ru_RU',
	'WOWAPI_KEY_MISSING' => 'Proszę zarejestrować konto Mashery na https://dev.battle.net/ i uzyskać klucz API.',
	'WOWAPI_METH_NOTALLOWED' => 'Metoda niedozwolona.',
	'WOWAPI_REGION_NOTALLOWED' => 'Region niedozwolony.',
	'WOWAPI_API_NOTIMPLEMENTED' => 'API niedozwolone.',
	'WOWAPI_NO_REALMS' => 'Nie podano królestwa.',
	'WOWAPI_NO_GUILD' => 'Nie podano nazwy gildii.',
	'WOWAPI_INVALID_FIELD' => 'Żądane pole jest nieprawidłowe: %s',
	'WOWAPI_NO_CHARACTER' => 'Nie podano nazwy postaci.',
	'CHARACTERAPICALL' => 'Aktualizuj graczy z API postaci',
	'CALL_BATTLENET_CHAR_API' => 'Wywołaj API postaci Battle.NET dla tej gildii. Przełącza na nieaktywny jeśli lastModified było ponad 90 dni temu, reaktywuje jeśli poniżej 90 dni i status dezaktywacji postaci to \'API\'.',
	'ARM_SHOWACH' => 'Pokaż punkty osiągnięć',
	'ARM_SHOWACH_EXPLAIN' => 'Wyświetlaj sumy punktów osiągnięć na liście gildii.',
));
