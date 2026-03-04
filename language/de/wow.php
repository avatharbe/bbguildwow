<?php
/**
 * bbguild_wow language file [German]
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
	'WOWAPIKEY' => 'API Schlüssel',
	'WOWAPIKEY_EXPLAIN' => 'bitte registrieren auf dev.battle.net (https://dev.battle.net/apps/mykeys)',
	'WOWPRIVKEY' => 'Privatschlüssel',
	'WOWPRIVKEY_EXPLAIN' => 'Mashery key',
	'WOWAPILOCALE' => 'Locale',
	'WOWAPILOCALE_EXPLAIN' => 'choose : en_GB, en_US, de_DE, es_ES, fr_FR, it_IT, pt_PT, pt_BR, or ru_RU',
	'WOWAPI_METH_NOTALLOWED' => 'Method nicht erlaubt.',
	'WOWAPI_REGION_NOTALLOWED' => 'Region nicht erlaubt.',
	'WOWAPI_API_NOTIMPLEMENTED' => 'API nicht erlaubt.',
	'WOWAPI_NO_REALMS' => 'Es wurde kein Realm eingetragen.',
	'WOWAPI_NO_GUILD' => 'es wurde kein Gildenname eingetragen.',
	'WOWAPI_INVALID_FIELD' => 'Ungültige Feldanfrage : %s',
	'WOWAPI_NO_CHARACTER' => 'Es wurde kein Charaktername eingetragen.',
	'CHARACTERAPICALL' => 'Charaktere vom Armory aktualisieren',
	'CALL_BATTLENET_CHAR_API' => 'Anruf Battle.NET Character API für 50 am ältesten bearbeitete WoW characters. Deaktivierung folgt indem das Charakter über 180 tage nicht aktualisiert wurde im Armory.',
));
