<?php
/**
 * bbguild_wow language file [Spanish]
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
	'WOWAPIKEY' => 'Clave API',
	'WOWAPIKEY_EXPLAIN' => 'Regístrate en dev.battle.net para obtener la clave (<a href="https://dev.battle.net/apps/mykeys">https://dev.battle.net/apps/mykeys</a>)',
	'WOWPRIVKEY' => 'Clave secreta Mashery',
	'WOWPRIVKEY_EXPLAIN' => '(<i>la clave secreta no es necesaria actualmente para bbGuild</i>)',
	'WOWAPILOCALE' => 'Idioma',
	'WOWAPILOCALE_EXPLAIN' => 'Los recursos de la API de Battle.NET proporcionan cadenas localizadas usando el parámetro locale. Los locales disponibles varían según la región.',
	'WOWAPI_LOCALE_NOTALLOWED' => 'Locale ilegal %s : elige uno según tu región de WoW : en_GB, en_US, de_DE, es_ES, fr_FR, it_IT, pt_PT, pt_BR, o ru_RU',
	'WOWAPI_KEY_MISSING' => 'Por favor solicita una cuenta Mashery en https://dev.battle.net/ y obtén una clave API.',
	'WOWAPI_METH_NOTALLOWED' => 'Método no permitido.',
	'WOWAPI_REGION_NOTALLOWED' => 'Región no permitida.',
	'WOWAPI_API_NOTIMPLEMENTED' => 'API no permitida.',
	'WOWAPI_NO_REALMS' => 'No se especificó ningún reino.',
	'WOWAPI_NO_GUILD' => 'No se especificó el nombre de la hermandad.',
	'WOWAPI_INVALID_FIELD' => 'Campo solicitado no válido: %s',
	'WOWAPI_NO_CHARACTER' => 'No se especificó el nombre del personaje.',
	'CHARACTERAPICALL' => 'Actualizar jugadores desde la API de personajes',
	'CALL_BATTLENET_CHAR_API' => 'Llamar a la API de personajes de Battle.NET para esta hermandad. Desactiva si lastModified fue hace más de 90 días, reactiva si es menos de 90 y el estado de desactivación del personaje fue \'API\'.',
	'ARM_SHOWACH' => 'Mostrar puntos de logro',
	'ARM_SHOWACH_EXPLAIN' => 'Mostrar los totales de puntos de logro en la lista de la hermandad.',
));
