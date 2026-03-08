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
	'WOWAPIKEY' => 'API-sleutel',
	'WOWAPIKEY_EXPLAIN' => 'Registreer op dev.battle.net om een sleutel te verkrijgen (<a href="https://dev.battle.net/apps/mykeys">https://dev.battle.net/apps/mykeys</a>)',
	'WOWPRIVKEY' => 'Geheime Mashery-sleutel',
	'WOWPRIVKEY_EXPLAIN' => '(<i>de geheime sleutel is momenteel niet nodig voor bbGuild</i>)',
	'WOWAPILOCALE' => 'Taalinstelling',
	'WOWAPILOCALE_EXPLAIN' => 'De Battle.NET API biedt gelokaliseerde teksten via de locale parameter. De beschikbare taalinstellingen variëren per regio.',
	'WOWAPI_LOCALE_NOTALLOWED' => 'Ongeldige locale %s : kies een van de volgende afhankelijk van je WoW-regio : en_GB, en_US, de_DE, es_ES, fr_FR, it_IT, pt_PT, pt_BR, of ru_RU',
	'WOWAPI_KEY_MISSING' => 'Vraag een Mashery-account aan op https://dev.battle.net/ en verkrijg een API-sleutel.',
	'WOWAPI_METH_NOTALLOWED' => 'Methode niet toegestaan.',
	'WOWAPI_REGION_NOTALLOWED' => 'Regio niet toegestaan.',
	'WOWAPI_API_NOTIMPLEMENTED' => 'API niet toegestaan.',
	'WOWAPI_NO_REALMS' => 'Geen realm opgegeven.',
	'WOWAPI_NO_GUILD' => 'Gildnaam niet opgegeven.',
	'WOWAPI_INVALID_FIELD' => 'Ongeldig veld aangevraagd: %s',
	'WOWAPI_NO_CHARACTER' => 'Personagenaam niet opgegeven.',
	'CHARACTERAPICALL' => 'Spelers bijwerken via Character API',
	'CALL_BATTLENET_CHAR_API' => 'Roep de Battle.NET Character API aan voor deze gilde. Schakelt over naar inactief als lastModified langer dan 90 dagen geleden was, heractiveerd als minder dan 90 dagen en de deactivatiestatus \'API\' was.',
	'ARM_SHOWACH' => 'Prestatiepunten tonen',
	'ARM_SHOWACH_EXPLAIN' => 'Prestatiepunttotalen tonen in de gildelijst.',
));
