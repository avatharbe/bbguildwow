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
	'WOWAPIKEY' => 'API key',
	'WOWAPIKEY_EXPLAIN' => 'register on dev.battle.net to obtain key (<a href="https://dev.battle.net/apps/mykeys">https://dev.battle.net/apps/mykeys</a>)',
	'WOWPRIVKEY' => 'Secret Mashery key',
	'WOWPRIVKEY_EXPLAIN' => '(<i>the secret key is not currently needed by bbGuild</i>)',
	'WOWAPILOCALE' => 'Locale',
	'WOWAPILOCALE_EXPLAIN' => 'Battle.NET API resources provide localized strings using the locale query string parameter. The available Locales supported vary from region to region and align with those supported on the community sites.',
	'WOWAPI_LOCALE_NOTALLOWED' => 'illegal Locale %s : choose one of depending on your WoW Region : en_GB, en_US, de_DE, es_ES, fr_FR, it_IT, pt_PT, pt_BR, or ru_RU',
	'WOWAPI_KEY_MISSING' => 'Please request a Mashery Account at https://dev.battle.net/ and get an API key.',
	'WOWAPI_METH_NOTALLOWED' => 'Method not allowed.',
	'WOWAPI_REGION_NOTALLOWED' => 'Region not allowed.',
	'WOWAPI_API_NOTIMPLEMENTED' => 'API not allowed.',
	'WOWAPI_NO_REALMS' => 'No realm specified.',
	'WOWAPI_NO_GUILD' => 'Guildname name not specified.',
	'WOWAPI_INVALID_FIELD' => 'Invalid field requested : %s',
	'WOWAPI_NO_CHARACTER' => 'Character name not specified.',
	'CHARACTERAPICALL' => 'Update Players from Character API',
	'CALL_BATTLENET_CHAR_API' => 'Call Battle.NET Character API for this Guild. toggles to inactive if lastModified flag was > 90 days ago, reactivates if < 90 and character deactivation status was \'API\'.',
));
