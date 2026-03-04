<?php
/**
 * bbguild_wow language file [Italian]
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
	'WOWAPIKEY' => 'chiave API',
	'WOWAPIKEY_EXPLAIN' => 'Si prega di registrarsi per dev.battle.net per ottenere la chiave (https://dev.battle.net/apps/mykeys)',
	'WOWPRIVKEY' => 'Secret key',
	'WOWPRIVKEY_EXPLAIN' => 'your Secret Mashery key',
	'WOWAPILOCALE' => 'Locale',
	'WOWAPILOCALE_EXPLAIN' => 'en_GB, en_US, de_DE, es_ES, fr_FR, it_IT, pt_PT, pt_BR, or ru_RU',
	'WOWAPI_METH_NOTALLOWED' => 'Method not allowed.',
	'WOWAPI_REGION_NOTALLOWED' => 'Region not allowed.',
	'WOWAPI_API_NOTIMPLEMENTED' => 'API not allowed.',
	'WOWAPI_NO_REALMS' => 'Nessun Reame specificato.',
	'WOWAPI_NO_GUILD' => 'Il nome della gilda non è stato specificato.',
	'WOWAPI_INVALID_FIELD' => 'Campo richiesto non valido : %s',
	'WOWAPI_NO_CHARACTER' => 'Il nome del personaggio non è stato specificato.',
	'CHARACTERAPICALL' => 'Aggiorna membri tramite Character API',
	'CALL_BATTLENET_CHAR_API' => "Chiamare Battle.NET API di caratteri per questa gilda. alterna a inattivo se lastModified bandiera era > 90 giorni fa, riattiva se < 90 e il carattere disattivazione dello stato era 'API'.",
));
