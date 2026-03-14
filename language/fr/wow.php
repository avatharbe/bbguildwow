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
	'WOWAPIKEY' => 'Client ID',
	'WOWAPIKEY_EXPLAIN' => 'Créez un client API sur <a href="https://develop.battle.net/access/clients">develop.battle.net/access/clients</a> pour obtenir votre Client ID.',
	'WOWPRIVKEY' => 'Client Secret',
	'WOWPRIVKEY_EXPLAIN' => 'Le Client Secret de votre client API Battle.net. Requis pour l\'accès à l\'API.',
	'WOWAPILOCALE' => 'Locale',
	'WOWAPILOCALE_EXPLAIN' => 'Choisissez : en_GB, en_US, de_DE, es_ES, fr_FR, it_IT, pt_PT, pt_BR ou ru_RU',
	'WOWAPI_KEY_MISSING' => 'Veuillez créer un client API sur <a href="https://develop.battle.net/access/clients">develop.battle.net</a> et entrer votre Client ID et Client Secret.',
	'WOWAPI_TOKEN_FAILED' => 'Impossible d\'obtenir le jeton d\'accès OAuth de Battle.net. Veuillez vérifier votre Client ID et Client Secret.',
	'WOWAPI_METH_NOTALLOWED' => 'Méthode non admise.',
	'WOWAPI_REGION_NOTALLOWED' => 'Région non admise.',
	'WOWAPI_API_NOTIMPLEMENTED' => 'API non admise.',
	'WOWAPI_NO_REALMS' => 'Royaume non spécifié.',
	'WOWAPI_NO_GUILD' => 'Guilde non spécifiée.',
	'WOWAPI_INVALID_FIELD' => 'Champ invalide : %s',
	'WOWAPI_NO_CHARACTER' => 'Nom du personnage non spécifié.',
	'CHARACTERAPICALL' => 'Mise à jour des membres depuis l\'API',
	'CALL_BATTLENET_CHAR_API' => 'Appel Battle.net Character API pour 50 membres WoW ayant la mise à jour la plus ancienne. Désactivation si la dernière activité sur l\'armurerie remonte à 180 jours.',
	'ARM_SHOWACH' => 'Montrer les points des Hauts Faits ?',
	'ARM_SHOWACH_EXPLAIN' => 'Afficher les totaux de points de haut fait dans la liste de guilde.',
	'ARM_ACHIEV_HIDE_EMPTY' => 'Masquer les cat&eacute;gories vides',
	'ARM_ACHIEV_HIDE_EMPTY_EXPLAIN' => 'Masquer les cat&eacute;gories de hauts faits sans progression dans le portail. Les utilisateurs peuvent basculer depuis le frontend.',

	// Portal module names
	'BBGUILD_PORTAL_ACHIEVEMENTS' => 'Hauts Faits',
	'BBGUILD_PORTAL_GUILD_NEWS'   => 'Nouvelles de la guilde',

	// Achievements module
	'ACHIEV_PROGRESS_OVERVIEW' => 'Aperçu de la progression',
	'ACHIEV_TOTAL_COMPLETED'   => 'Total accompli',
	'ACHIEV_RECENTLY_EARNED'   => 'Obtenus récemment',
	'ACHIEV_POINTS_TOTAL'      => 'Points de hauts faits',
	'ACHIEV_COMPLETED_LABEL'   => 'hauts faits accomplis',

	// Achievement browser
	'ACHIEV_BACK'              => 'Hauts Faits',
	'ACHIEV_NO_CATEGORIES'     => 'Aucune cat&eacute;gorie de hauts faits synchronis&eacute;e. Synchronisez les cat&eacute;gories via l\'ACP.',
	'ACHIEV_NOT_COMPLETED'     => 'Non accompli',
	'ACHIEV_CRITERIA'          => 'Crit&egrave;res',
	'ACHIEV_FEATS_OF_STRENGTH' => 'Exploits',
	'ACHIEV_SYNC_CATEGORIES'   => 'Synchroniser les cat&eacute;gories',
	'ACHIEV_SHOW_EMPTY'        => 'Afficher toutes les cat&eacute;gories',
	'ACHIEV_HIDE_EMPTY'        => 'Masquer les cat&eacute;gories vides',
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
