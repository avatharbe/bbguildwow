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
	'WOWAPIKEY' => 'Client-ID',
	'WOWAPIKEY_EXPLAIN' => 'Erstelle einen API-Client auf <a href="https://develop.battle.net/access/clients">develop.battle.net/access/clients</a> um deine Client-ID zu erhalten.',
	'WOWPRIVKEY' => 'Client-Secret',
	'WOWPRIVKEY_EXPLAIN' => 'Das Client-Secret deines Battle.net API-Clients. Erforderlich für den API-Zugriff.',
	'WOWAPILOCALE' => 'Locale',
	'WOWAPILOCALE_EXPLAIN' => 'Wähle: en_GB, en_US, de_DE, es_ES, fr_FR, it_IT, pt_PT, pt_BR oder ru_RU',
	'WOWAPI_KEY_MISSING' => 'Bitte erstelle einen API-Client auf <a href="https://develop.battle.net/access/clients">develop.battle.net</a> und gib deine Client-ID und dein Client-Secret ein.',
	'WOWAPI_TOKEN_FAILED' => 'OAuth-Zugriffstoken von Battle.net konnte nicht abgerufen werden. Bitte überprüfe Client-ID und Client-Secret.',
	'WOWAPI_METH_NOTALLOWED' => 'Methode nicht erlaubt.',
	'WOWAPI_REGION_NOTALLOWED' => 'Region nicht erlaubt.',
	'WOWAPI_API_NOTIMPLEMENTED' => 'API nicht erlaubt.',
	'WOWAPI_NO_REALMS' => 'Es wurde kein Realm eingetragen.',
	'WOWAPI_NO_GUILD' => 'Es wurde kein Gildenname eingetragen.',
	'WOWAPI_INVALID_FIELD' => 'Ungültige Feldanfrage: %s',
	'WOWAPI_NO_CHARACTER' => 'Es wurde kein Charaktername eingetragen.',
	'CHARACTERAPICALL' => 'Charaktere vom Armory aktualisieren',
	'CALL_BATTLENET_CHAR_API' => 'Anruf Battle.net Character API für 50 am ältesten bearbeitete WoW Characters. Deaktivierung folgt, wenn der Charakter über 180 Tage nicht aktualisiert wurde im Armory.',
	'ARM_SHOWACH' => 'Erfolgspunkte anzeigen',
	'ARM_SHOWACH_EXPLAIN' => 'Erfolgspunkttotale in der Gildenliste anzeigen.',

	// Portal module names
	'BBGUILD_PORTAL_ACHIEVEMENTS' => 'Erfolge',
	'BBGUILD_PORTAL_GUILD_NEWS'   => 'Gildenneuigkeiten',

	// Achievements module
	'ACHIEV_PROGRESS_OVERVIEW' => 'Fortschrittsübersicht',
	'ACHIEV_TOTAL_COMPLETED'   => 'Gesamt abgeschlossen',
	'ACHIEV_RECENTLY_EARNED'   => 'Kürzlich verdient',
	'ACHIEV_POINTS_TOTAL'      => 'Erfolgspunkte',
	'ACHIEV_COMPLETED_LABEL'   => 'Erfolge abgeschlossen',

	// Achievement browser
	'ACHIEV_BACK'              => 'Erfolge',
	'ACHIEV_NO_CATEGORIES'     => 'Noch keine Erfolgskategorien synchronisiert. Kategorien im ACP synchronisieren.',
	'ACHIEV_NOT_COMPLETED'     => 'Nicht abgeschlossen',
	'ACHIEV_CRITERIA'          => 'Kriterien',
	'ACHIEV_FEATS_OF_STRENGTH' => 'Heldentaten',
	'ACHIEV_SYNC_CATEGORIES'   => 'Kategorien synchronisieren',
));
