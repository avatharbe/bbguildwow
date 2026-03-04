<?php
/**
 * bbguild_wow language file [French]
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
	'WOWAPIKEY' => 'clef API',
	'WOWAPIKEY_EXPLAIN' => 'registrez sur dev.battle.net pour obtenir la clef (https://dev.battle.net/apps/mykeys)',
	'WOWPRIVKEY' => 'Clef secrète',
	'WOWPRIVKEY_EXPLAIN' => 'Clef secrète Mashery',
	'WOWAPILOCALE' => 'Locale',
	'WOWAPILOCALE_EXPLAIN' => 'choose : en_GB, en_US, de_DE, es_ES, fr_FR, it_IT, pt_PT, pt_BR, or ru_RU',
	'WOWAPI_METH_NOTALLOWED' => 'Methode non admise.',
	'WOWAPI_REGION_NOTALLOWED' => 'Region non admise.',
	'WOWAPI_API_NOTIMPLEMENTED' => 'API non admise.',
	'WOWAPI_NO_REALMS' => 'royaume non specifié.',
	'WOWAPI_NO_GUILD' => 'guilde non specifié.',
	'WOWAPI_INVALID_FIELD' => 'champs invalide : %s',
	'WOWAPI_NO_CHARACTER' => 'Character name not specified.',
	'CHARACTERAPICALL' => 'mise à jour membres depuis API',
	'CALL_BATTLENET_CHAR_API' => 'Appel Battle.NET Character API pour 50 membres WoW ayant la mise à jour la plus ancienne. Désactivation si la dernière activité sur l\'armurerie remonte à 180 jours.',
));
