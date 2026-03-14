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
	'WOWAPIKEY' => 'Client ID',
	'WOWAPIKEY_EXPLAIN' => 'Crea un client API su <a href="https://develop.battle.net/access/clients">develop.battle.net/access/clients</a> per ottenere il tuo Client ID.',
	'WOWPRIVKEY' => 'Client Secret',
	'WOWPRIVKEY_EXPLAIN' => 'Il Client Secret del tuo client API Battle.net. Necessario per l\'accesso all\'API.',
	'WOWAPILOCALE' => 'Locale',
	'WOWAPILOCALE_EXPLAIN' => 'en_GB, en_US, de_DE, es_ES, fr_FR, it_IT, pt_PT, pt_BR o ru_RU',
	'WOWAPI_KEY_MISSING' => 'Crea un client API su <a href="https://develop.battle.net/access/clients">develop.battle.net</a> e inserisci il tuo Client ID e Client Secret.',
	'WOWAPI_TOKEN_FAILED' => 'Impossibile ottenere il token di accesso OAuth da Battle.net. Verifica il tuo Client ID e Client Secret.',
	'WOWAPI_METH_NOTALLOWED' => 'Metodo non consentito.',
	'WOWAPI_REGION_NOTALLOWED' => 'Regione non consentita.',
	'WOWAPI_API_NOTIMPLEMENTED' => 'API non consentita.',
	'WOWAPI_NO_REALMS' => 'Nessun reame specificato.',
	'WOWAPI_NO_GUILD' => 'Il nome della gilda non è stato specificato.',
	'WOWAPI_INVALID_FIELD' => 'Campo richiesto non valido: %s',
	'WOWAPI_NO_CHARACTER' => 'Il nome del personaggio non è stato specificato.',
	'CHARACTERAPICALL' => 'Aggiorna membri tramite Character API',
	'CALL_BATTLENET_CHAR_API' => "Chiamare Battle.net API di caratteri per questa gilda. Alterna a inattivo se lastModified era > 90 giorni fa, riattiva se < 90 e il carattere disattivazione dello stato era 'API'.",
	'ARM_SHOWACH' => 'Mostrare i Punti impresa',
	'ARM_SHOWACH_EXPLAIN' => 'Visualizzare i totali dei punti impresa nella lista dei membri della gilda.',
	'ARM_ACHIEV_HIDE_EMPTY' => 'Nascondere le categorie vuote',
	'ARM_ACHIEV_HIDE_EMPTY_EXPLAIN' => 'Nascondere le categorie di imprese senza progressi nel portale. Gli utenti possono attivare/disattivare dal frontend.',

	// Portal module names
	'BBGUILD_PORTAL_ACHIEVEMENTS' => 'Imprese',
	'BBGUILD_PORTAL_GUILD_NEWS'   => 'Notizie della gilda',

	// Achievements module
	'ACHIEV_PROGRESS_OVERVIEW' => 'Panoramica progressi',
	'ACHIEV_TOTAL_COMPLETED'   => 'Totale completati',
	'ACHIEV_RECENTLY_EARNED'   => 'Ottenuti di recente',
	'ACHIEV_POINTS_TOTAL'      => 'Punti impresa',
	'ACHIEV_COMPLETED_LABEL'   => 'imprese completate',

	// Achievement browser
	'ACHIEV_BACK'              => 'Imprese',
	'ACHIEV_NO_CATEGORIES'     => 'Nessuna categoria di imprese sincronizzata. Sincronizza le categorie tramite ACP.',
	'ACHIEV_NOT_COMPLETED'     => 'Non completato',
	'ACHIEV_CRITERIA'          => 'Criteri',
	'ACHIEV_FEATS_OF_STRENGTH' => 'Imprese epiche',
	'ACHIEV_SYNC_CATEGORIES'   => 'Sincronizza categorie',
	'ACHIEV_SHOW_EMPTY'        => 'Mostra tutte le categorie',
	'ACHIEV_HIDE_EMPTY'        => 'Nascondi categorie vuote',
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
));
