<?php
/**
 * WoW Installer
 *
 * Installs World of Warcraft factions, classes, races, and roles.
 * Extends the abstract_game_install from bbGuild core.
 *
 * @package   bbguildwow v2.0
 * @copyright 2018 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\game;

use avathar\bbguild\model\games\abstract_game_install;

/**
 * Class wow_installer
 *
 * @package avathar\bbguildwow\game
 */
class wow_installer extends abstract_game_install
{
	/**
	 * @inheritdoc
	 */
	protected function has_api_support(): bool
	{
		return true;
	}

	/**
	 * Installs WoW factions (Alliance, Horde)
	 */
	protected function install_factions()
	{
		$this->db->sql_query('DELETE FROM ' . $this->table('bb_factions_table') . " WHERE game_id = '" . $this->db->sql_escape($this->game_id) . "'");
		$sql_ary = array();
		$sql_ary[] = array('game_id' => $this->game_id, 'faction_id' => 1, 'faction_name' => 'Alliance');
		$sql_ary[] = array('game_id' => $this->game_id, 'faction_id' => 2, 'faction_name' => 'Horde');
		$this->db->sql_multi_insert($this->table('bb_factions_table'), $sql_ary);
	}

	/**
	 * Installs WoW classes with translations (en, fr, de, it)
	 */
	protected function install_classes()
	{

		// classes (note class 10 does not exist)
		$this->db->sql_query('DELETE FROM ' . $this->table('bb_classes_table') . " WHERE game_id = '" . $this->db->sql_escape($this->game_id) . "'");
		$sql_ary = array();
		$sql_ary[] = array('game_id' => $this->game_id, 'class_id' => 0,  'class_armor_type' => 'PLATE',   'class_min_level' => 1, 'class_max_level' => 90, 'colorcode' => '#999',    'imagename' => 'wow_unknown');
		$sql_ary[] = array('game_id' => $this->game_id, 'class_id' => 1,  'class_armor_type' => 'PLATE',   'class_min_level' => 1, 'class_max_level' => 90, 'colorcode' => '#c69b6d', 'imagename' => 'wow_warrior');
		$sql_ary[] = array('game_id' => $this->game_id, 'class_id' => 4,  'class_armor_type' => 'LEATHER', 'class_min_level' => 1, 'class_max_level' => 90, 'colorcode' => '#fff468', 'imagename' => 'wow_rogue');
		$sql_ary[] = array('game_id' => $this->game_id, 'class_id' => 3,  'class_armor_type' => 'MAIL',    'class_min_level' => 1, 'class_max_level' => 90, 'colorcode' => '#aad372', 'imagename' => 'wow_hunter');
		$sql_ary[] = array('game_id' => $this->game_id, 'class_id' => 2,  'class_armor_type' => 'PLATE',   'class_min_level' => 1, 'class_max_level' => 90, 'colorcode' => '#f48cba', 'imagename' => 'wow_paladin');
		$sql_ary[] = array('game_id' => $this->game_id, 'class_id' => 7,  'class_armor_type' => 'MAIL',    'class_min_level' => 1, 'class_max_level' => 90, 'colorcode' => '#2359ff', 'imagename' => 'wow_shaman');
		$sql_ary[] = array('game_id' => $this->game_id, 'class_id' => 11, 'class_armor_type' => 'LEATHER', 'class_min_level' => 1, 'class_max_level' => 90, 'colorcode' => '#ff7c0a', 'imagename' => 'wow_druid');
		$sql_ary[] = array('game_id' => $this->game_id, 'class_id' => 9,  'class_armor_type' => 'CLOTH',   'class_min_level' => 1, 'class_max_level' => 90, 'colorcode' => '#9382c9', 'imagename' => 'wow_warlock');
		$sql_ary[] = array('game_id' => $this->game_id, 'class_id' => 8,  'class_armor_type' => 'CLOTH',   'class_min_level' => 1, 'class_max_level' => 90, 'colorcode' => '#68ccef', 'imagename' => 'wow_mage');
		$sql_ary[] = array('game_id' => $this->game_id, 'class_id' => 5,  'class_armor_type' => 'CLOTH',   'class_min_level' => 1, 'class_max_level' => 90, 'colorcode' => '#f0ebe0', 'imagename' => 'wow_priest');
		$sql_ary[] = array('game_id' => $this->game_id, 'class_id' => 6,  'class_armor_type' => 'PLATE',   'class_min_level' => 8, 'class_max_level' => 90, 'colorcode' => '#c41e3b', 'imagename' => 'wow_death_knight');
		$sql_ary[] = array('game_id' => $this->game_id, 'class_id' => 10, 'class_armor_type' => 'LEATHER', 'class_min_level' => 1, 'class_max_level' => 90, 'colorcode' => '#00ffba', 'imagename' => 'wow_monk');
		$sql_ary[] = array('game_id' => $this->game_id, 'class_id' => 12, 'class_armor_type' => 'LEATHER', 'class_min_level' => 8, 'class_max_level' => 90, 'colorcode' => '#A330C3', 'imagename' => 'wow_demon_hunter');
		$sql_ary[] = array('game_id' => $this->game_id, 'class_id' => 13, 'class_armor_type' => 'MAIL',    'class_min_level' => 1, 'class_max_level' => 90, 'colorcode' => '#33937F', 'imagename' => 'wow_evoker');
		$this->db->sql_multi_insert($this->table('bb_classes_table'), $sql_ary);
		unset($sql_ary);

		// class names in multiple languages
		$this->db->sql_query('DELETE FROM ' . $this->table('bb_language_table') . " WHERE game_id = '" . $this->db->sql_escape($this->game_id) . "' AND attribute='class' ");

		$sql_ary = array();
		// en
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 0,  'language' => 'en', 'attribute' => 'class', 'name' => 'Unknown',      'name_short' => 'Unknown');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 1,  'language' => 'en', 'attribute' => 'class', 'name' => 'Warrior',      'name_short' => 'Warrior');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 4,  'language' => 'en', 'attribute' => 'class', 'name' => 'Rogue',        'name_short' => 'Rogue');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 3,  'language' => 'en', 'attribute' => 'class', 'name' => 'Hunter',       'name_short' => 'Hunter');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 2,  'language' => 'en', 'attribute' => 'class', 'name' => 'Paladin',      'name_short' => 'Paladin');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 7,  'language' => 'en', 'attribute' => 'class', 'name' => 'Shaman',       'name_short' => 'Shaman');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 11, 'language' => 'en', 'attribute' => 'class', 'name' => 'Druid',        'name_short' => 'Druid');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 9,  'language' => 'en', 'attribute' => 'class', 'name' => 'Warlock',      'name_short' => 'Warlock');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 8,  'language' => 'en', 'attribute' => 'class', 'name' => 'Mage',         'name_short' => 'Mage');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 5,  'language' => 'en', 'attribute' => 'class', 'name' => 'Priest',       'name_short' => 'Priest');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 6,  'language' => 'en', 'attribute' => 'class', 'name' => 'Death Knight', 'name_short' => 'Death Knight');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 10, 'language' => 'en', 'attribute' => 'class', 'name' => 'Monk',         'name_short' => 'Monk');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 12, 'language' => 'en', 'attribute' => 'class', 'name' => 'Demon Hunter', 'name_short' => 'Demon Hunter');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 13, 'language' => 'en', 'attribute' => 'class', 'name' => 'Evoker',       'name_short' => 'Evoker');

		// fr
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 0,  'language' => 'fr', 'attribute' => 'class', 'name' => 'Unknown',               'name_short' => 'Unknown');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 1,  'language' => 'fr', 'attribute' => 'class', 'name' => 'Warrior',               'name_short' => 'Warrior');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 4,  'language' => 'fr', 'attribute' => 'class', 'name' => 'Voleur',                'name_short' => 'Voleur');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 3,  'language' => 'fr', 'attribute' => 'class', 'name' => 'Chasseur',              'name_short' => 'Chasseur');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 2,  'language' => 'fr', 'attribute' => 'class', 'name' => 'Paladin',               'name_short' => 'Paladin');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 7,  'language' => 'fr', 'attribute' => 'class', 'name' => 'Chaman',                'name_short' => 'Chaman');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 11, 'language' => 'fr', 'attribute' => 'class', 'name' => 'Druide',                'name_short' => 'Druide');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 9,  'language' => 'fr', 'attribute' => 'class', 'name' => 'Démoniste',             'name_short' => 'Démoniste');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 8,  'language' => 'fr', 'attribute' => 'class', 'name' => 'Mage',                  'name_short' => 'Mage');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 5,  'language' => 'fr', 'attribute' => 'class', 'name' => 'Prêtre',                'name_short' => 'Prêtre');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 6,  'language' => 'fr', 'attribute' => 'class', 'name' => 'Chevalier de la Mort',  'name_short' => 'Chevalier de la Mort');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 10, 'language' => 'fr', 'attribute' => 'class', 'name' => 'Moine',                 'name_short' => 'Moine');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 12, 'language' => 'fr', 'attribute' => 'class', 'name' => 'Chasseur de démons',    'name_short' => 'Chasseur de démons');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 13, 'language' => 'fr', 'attribute' => 'class', 'name' => 'Évocateur',              'name_short' => 'Évocateur');

		// de
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 0,  'language' => 'de', 'attribute' => 'class', 'name' => 'Unbekannt',      'name_short' => 'Unbekannt');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 1,  'language' => 'de', 'attribute' => 'class', 'name' => 'Krieger',        'name_short' => 'Krieger');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 4,  'language' => 'de', 'attribute' => 'class', 'name' => 'Schurke',        'name_short' => 'Schurke');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 3,  'language' => 'de', 'attribute' => 'class', 'name' => 'Jäger',          'name_short' => 'Jäger');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 2,  'language' => 'de', 'attribute' => 'class', 'name' => 'Paladin',        'name_short' => 'Paladin');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 7,  'language' => 'de', 'attribute' => 'class', 'name' => 'Schamane',       'name_short' => 'Schamane');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 11, 'language' => 'de', 'attribute' => 'class', 'name' => 'Druide',         'name_short' => 'Druide');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 9,  'language' => 'de', 'attribute' => 'class', 'name' => 'Hexenmeister',   'name_short' => 'Hexenmeister');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 8,  'language' => 'de', 'attribute' => 'class', 'name' => 'Magier',         'name_short' => 'Magier');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 5,  'language' => 'de', 'attribute' => 'class', 'name' => 'Priester',       'name_short' => 'Priester');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 6,  'language' => 'de', 'attribute' => 'class', 'name' => 'Todesritter',    'name_short' => 'Todesritter');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 10, 'language' => 'de', 'attribute' => 'class', 'name' => 'Mönch',          'name_short' => 'Mönch');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 12, 'language' => 'de', 'attribute' => 'class', 'name' => 'Dämonenjäger',   'name_short' => 'Dämonenjäger');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 13, 'language' => 'de', 'attribute' => 'class', 'name' => 'Rufer',          'name_short' => 'Rufer');

		// it
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 0,  'language' => 'it', 'attribute' => 'class', 'name' => 'Sconosciuto',            'name_short' => 'Sconosciuto');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 1,  'language' => 'it', 'attribute' => 'class', 'name' => 'Guerriero',              'name_short' => 'Guerriero');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 4,  'language' => 'it', 'attribute' => 'class', 'name' => 'Ladro',                  'name_short' => 'Ladro');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 3,  'language' => 'it', 'attribute' => 'class', 'name' => 'Cacciatore',             'name_short' => 'Cacciatore');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 2,  'language' => 'it', 'attribute' => 'class', 'name' => 'Paladino',               'name_short' => 'Paladino');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 7,  'language' => 'it', 'attribute' => 'class', 'name' => 'Sciamano',               'name_short' => 'Sciamano');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 11, 'language' => 'it', 'attribute' => 'class', 'name' => 'Druido',                 'name_short' => 'Druido');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 9,  'language' => 'it', 'attribute' => 'class', 'name' => 'Stregone',               'name_short' => 'Stregone');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 8,  'language' => 'it', 'attribute' => 'class', 'name' => 'Mago',                   'name_short' => 'Mago');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 5,  'language' => 'it', 'attribute' => 'class', 'name' => 'Sacerdote',              'name_short' => 'Sacerdote');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 6,  'language' => 'it', 'attribute' => 'class', 'name' => 'Cavaliere della Morte',  'name_short' => 'Cavaliere della Morte');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 10, 'language' => 'it', 'attribute' => 'class', 'name' => 'Monaco',                 'name_short' => 'Monaco');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 12, 'language' => 'it', 'attribute' => 'class', 'name' => 'Cacciatore di Demoni',   'name_short' => 'Cacciatore di Demoni');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 13, 'language' => 'it', 'attribute' => 'class', 'name' => 'Evocatore',              'name_short' => 'Evocatore');

		$this->db->sql_multi_insert($this->table('bb_language_table'), $sql_ary);
	}

	/**
	 * Installs WoW races with translations (en, fr, de, it)
	 */
	protected function install_races()
	{
		$this->db->sql_query('DELETE FROM ' . $this->table('bb_races_table') . " WHERE game_id = '" . $this->db->sql_escape($this->game_id) . "' ");
		$sql_ary = array();
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 0,  'race_faction_id' => 0, 'image_female' => ' ',                  'image_male' => ' ');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 1,  'race_faction_id' => 1, 'image_female' => 'wow_human_female',    'image_male' => 'wow_human_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 2,  'race_faction_id' => 2, 'image_female' => 'wow_orc_female',      'image_male' => 'wow_orc_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 3,  'race_faction_id' => 1, 'image_female' => 'wow_dwarf_female',    'image_male' => 'wow_dwarf_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 4,  'race_faction_id' => 1, 'image_female' => 'wow_nightelf_female', 'image_male' => 'wow_nightelf_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 5,  'race_faction_id' => 2, 'image_female' => 'wow_scourge_female',  'image_male' => 'wow_scourge_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 6,  'race_faction_id' => 2, 'image_female' => 'wow_tauren_female',   'image_male' => 'wow_tauren_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 7,  'race_faction_id' => 1, 'image_female' => 'wow_gnome_female',    'image_male' => 'wow_gnome_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 8,  'race_faction_id' => 2, 'image_female' => 'wow_troll_female',    'image_male' => 'wow_troll_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 9,  'race_faction_id' => 2, 'image_female' => 'wow_goblin_female',   'image_male' => 'wow_goblin_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 10, 'race_faction_id' => 2, 'image_female' => 'wow_bloodelf_female', 'image_male' => 'wow_bloodelf_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 11, 'race_faction_id' => 1, 'image_female' => 'wow_draenei_female',  'image_male' => 'wow_draenei_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 22, 'race_faction_id' => 1, 'image_female' => 'wow_worgen_female',   'image_male' => 'wow_worgen_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 25, 'race_faction_id' => 1, 'image_female' => 'wow_pandaren_female', 'image_male' => 'wow_pandaren_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 26, 'race_faction_id' => 2, 'image_female' => 'wow_pandaren_female',           'image_male' => 'wow_pandaren_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 27, 'race_faction_id' => 2, 'image_female' => 'wow_nightborne_female',          'image_male' => 'wow_nightborne_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 28, 'race_faction_id' => 2, 'image_female' => 'wow_highmountaintauren_female',  'image_male' => 'wow_highmountaintauren_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 29, 'race_faction_id' => 1, 'image_female' => 'wow_voidelf_female',             'image_male' => 'wow_voidelf_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 30, 'race_faction_id' => 1, 'image_female' => 'wow_lightforgeddraenei_female',  'image_male' => 'wow_lightforgeddraenei_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 31, 'race_faction_id' => 2, 'image_female' => 'wow_zandalari_female',           'image_male' => 'wow_zandalari_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 32, 'race_faction_id' => 1, 'image_female' => 'wow_kultiran_female',            'image_male' => 'wow_kultiran_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 34, 'race_faction_id' => 1, 'image_female' => 'wow_darkirondwarf_female',       'image_male' => 'wow_darkirondwarf_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 35, 'race_faction_id' => 2, 'image_female' => 'wow_vulpera_female',             'image_male' => 'wow_vulpera_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 36, 'race_faction_id' => 2, 'image_female' => 'wow_magharorc_female',           'image_male' => 'wow_magharorc_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 37, 'race_faction_id' => 1, 'image_female' => 'wow_mechagnome_female',          'image_male' => 'wow_mechagnome_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 52, 'race_faction_id' => 1, 'image_female' => 'wow_dracthyr_female',            'image_male' => 'wow_dracthyr_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 70, 'race_faction_id' => 2, 'image_female' => 'wow_dracthyr_female',            'image_male' => 'wow_dracthyr_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 84, 'race_faction_id' => 2, 'image_female' => 'wow_earthen_female',             'image_male' => 'wow_earthen_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 85, 'race_faction_id' => 1, 'image_female' => 'wow_earthen_female',             'image_male' => 'wow_earthen_male');
		$sql_ary[] = array('game_id' => $this->game_id, 'race_id' => 86, 'race_faction_id' => 0, 'image_female' => 'wow_haranir_female',             'image_male' => 'wow_haranir_male');
		$this->db->sql_multi_insert($this->table('bb_races_table'), $sql_ary);
		unset($sql_ary);

		// race names
		$this->db->sql_query('DELETE FROM ' . $this->table('bb_language_table') . " WHERE game_id = '" . $this->db->sql_escape($this->game_id) . "' AND attribute = 'race' ");

		$sql_ary = array();
		// en
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 0,  'language' => 'en', 'attribute' => 'race', 'name' => 'Unknown',           'name_short' => 'Unknown');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 1,  'language' => 'en', 'attribute' => 'race', 'name' => 'Human',              'name_short' => 'Human');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 2,  'language' => 'en', 'attribute' => 'race', 'name' => 'Orc',                'name_short' => 'Orc');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 3,  'language' => 'en', 'attribute' => 'race', 'name' => 'Dwarf',              'name_short' => 'Dwarf');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 4,  'language' => 'en', 'attribute' => 'race', 'name' => 'Night Elf',          'name_short' => 'Night Elf');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 5,  'language' => 'en', 'attribute' => 'race', 'name' => 'Undead',             'name_short' => 'Undead');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 6,  'language' => 'en', 'attribute' => 'race', 'name' => 'Tauren',             'name_short' => 'Tauren');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 7,  'language' => 'en', 'attribute' => 'race', 'name' => 'Gnome',              'name_short' => 'Gnome');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 8,  'language' => 'en', 'attribute' => 'race', 'name' => 'Troll',              'name_short' => 'Troll');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 10, 'language' => 'en', 'attribute' => 'race', 'name' => 'Blood Elf',          'name_short' => 'Blood Elf');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 11, 'language' => 'en', 'attribute' => 'race', 'name' => 'Draenei',            'name_short' => 'Draenei');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 9,  'language' => 'en', 'attribute' => 'race', 'name' => 'Goblin',             'name_short' => 'Goblin');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 22, 'language' => 'en', 'attribute' => 'race', 'name' => 'Worgen',             'name_short' => 'Worgen');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 25, 'language' => 'en', 'attribute' => 'race', 'name' => 'Pandaren Alliance',  'name_short' => 'Pandaren Alliance');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 26, 'language' => 'en', 'attribute' => 'race', 'name' => 'Pandaren Horde',     'name_short' => 'Pandaren Horde');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 27, 'language' => 'en', 'attribute' => 'race', 'name' => 'Nightborne',         'name_short' => 'Nightborne');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 28, 'language' => 'en', 'attribute' => 'race', 'name' => 'Highmountain Tauren','name_short' => 'Highmountain Tauren');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 29, 'language' => 'en', 'attribute' => 'race', 'name' => 'Void Elf',           'name_short' => 'Void Elf');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 30, 'language' => 'en', 'attribute' => 'race', 'name' => 'Lightforged Draenei','name_short' => 'Lightforged Draenei');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 31, 'language' => 'en', 'attribute' => 'race', 'name' => 'Zandalari Troll',    'name_short' => 'Zandalari Troll');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 32, 'language' => 'en', 'attribute' => 'race', 'name' => 'Kul Tiran',          'name_short' => 'Kul Tiran');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 34, 'language' => 'en', 'attribute' => 'race', 'name' => 'Dark Iron Dwarf',    'name_short' => 'Dark Iron Dwarf');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 35, 'language' => 'en', 'attribute' => 'race', 'name' => 'Vulpera',            'name_short' => 'Vulpera');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 36, 'language' => 'en', 'attribute' => 'race', 'name' => 'Mag\'har Orc',       'name_short' => 'Mag\'har Orc');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 37, 'language' => 'en', 'attribute' => 'race', 'name' => 'Mechagnome',         'name_short' => 'Mechagnome');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 52, 'language' => 'en', 'attribute' => 'race', 'name' => 'Dracthyr',           'name_short' => 'Dracthyr');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 70, 'language' => 'en', 'attribute' => 'race', 'name' => 'Dracthyr',           'name_short' => 'Dracthyr');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 84, 'language' => 'en', 'attribute' => 'race', 'name' => 'Earthen',            'name_short' => 'Earthen');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 85, 'language' => 'en', 'attribute' => 'race', 'name' => 'Earthen',            'name_short' => 'Earthen');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 86, 'language' => 'en', 'attribute' => 'race', 'name' => 'Haranir',            'name_short' => 'Haranir');

		// fr
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 0,  'language' => 'fr', 'attribute' => 'race', 'name' => 'Inconnu',            'name_short' => 'Inconnu');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 1,  'language' => 'fr', 'attribute' => 'race', 'name' => 'Humain',             'name_short' => 'Humain');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 2,  'language' => 'fr', 'attribute' => 'race', 'name' => 'Orc',                'name_short' => 'Orc');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 3,  'language' => 'fr', 'attribute' => 'race', 'name' => 'Nain',               'name_short' => 'Nain');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 4,  'language' => 'fr', 'attribute' => 'race', 'name' => 'Elfe de la Nuit',    'name_short' => 'Elfe de la Nuit');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 5,  'language' => 'fr', 'attribute' => 'race', 'name' => 'Mort-Vivant',        'name_short' => 'Mort-Vivant');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 6,  'language' => 'fr', 'attribute' => 'race', 'name' => 'Tauren',             'name_short' => 'Tauren');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 7,  'language' => 'fr', 'attribute' => 'race', 'name' => 'Gnome',              'name_short' => 'Gnome');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 8,  'language' => 'fr', 'attribute' => 'race', 'name' => 'Troll',              'name_short' => 'Troll');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 10, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Elfe de Sang',       'name_short' => 'Elfe de Sang');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 11, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Draeneï',            'name_short' => 'Draeneï');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 9,  'language' => 'fr', 'attribute' => 'race', 'name' => 'Goblin',             'name_short' => 'Goblin');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 22, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Worgen',             'name_short' => 'Worgen');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 25, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Pandaren Alliance',  'name_short' => 'Pandaren Alliance');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 26, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Pandaren Horde',     'name_short' => 'Pandaren Horde');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 27, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Sacrenuit',          'name_short' => 'Sacrenuit');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 28, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Tauren de Haut-Roc', 'name_short' => 'Tauren de Haut-Roc');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 29, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Elfe du Vide',       'name_short' => 'Elfe du Vide');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 30, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Draeneï sancteforge','name_short' => 'Draeneï sancteforge');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 31, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Troll zandalari',    'name_short' => 'Troll zandalari');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 32, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Kultirassien',       'name_short' => 'Kultirassien');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 34, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Nain sombrefer',     'name_short' => 'Nain sombrefer');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 35, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Vulpérin',           'name_short' => 'Vulpérin');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 36, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Orc mag\'har',       'name_short' => 'Orc mag\'har');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 37, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Mécagnome',          'name_short' => 'Mécagnome');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 52, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Dracthyr',           'name_short' => 'Dracthyr');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 70, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Dracthyr',           'name_short' => 'Dracthyr');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 84, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Terrestre',          'name_short' => 'Terrestre');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 85, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Terrestre',          'name_short' => 'Terrestre');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 86, 'language' => 'fr', 'attribute' => 'race', 'name' => 'Haranir',            'name_short' => 'Haranir');

		// de
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 0,  'language' => 'de', 'attribute' => 'race', 'name' => 'Unbekannt',          'name_short' => 'Unbekannt');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 1,  'language' => 'de', 'attribute' => 'race', 'name' => 'Mensch',             'name_short' => 'Mensch');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 2,  'language' => 'de', 'attribute' => 'race', 'name' => 'Orc',                'name_short' => 'Orc');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 3,  'language' => 'de', 'attribute' => 'race', 'name' => 'Zwerg',              'name_short' => 'Zwerg');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 4,  'language' => 'de', 'attribute' => 'race', 'name' => 'Nachtelf',           'name_short' => 'Nachtelf');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 5,  'language' => 'de', 'attribute' => 'race', 'name' => 'Untoter',            'name_short' => 'Untoter');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 6,  'language' => 'de', 'attribute' => 'race', 'name' => 'Tauren',             'name_short' => 'Tauren');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 7,  'language' => 'de', 'attribute' => 'race', 'name' => 'Gnome',              'name_short' => 'Gnome');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 8,  'language' => 'de', 'attribute' => 'race', 'name' => 'Troll',              'name_short' => 'Troll');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 10, 'language' => 'de', 'attribute' => 'race', 'name' => 'Blutelf',            'name_short' => 'Blutelf');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 11, 'language' => 'de', 'attribute' => 'race', 'name' => 'Draenei',            'name_short' => 'Draenei');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 9,  'language' => 'de', 'attribute' => 'race', 'name' => 'Goblin',             'name_short' => 'Goblin');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 22, 'language' => 'de', 'attribute' => 'race', 'name' => 'Worgen',             'name_short' => 'Worgen');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 25, 'language' => 'de', 'attribute' => 'race', 'name' => 'Pandaren Alliance',  'name_short' => 'Pandaren Alliance');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 26, 'language' => 'de', 'attribute' => 'race', 'name' => 'Pandaren Horde',     'name_short' => 'Pandaren Horde');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 27, 'language' => 'de', 'attribute' => 'race', 'name' => 'Nachtgeborener',     'name_short' => 'Nachtgeborener');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 28, 'language' => 'de', 'attribute' => 'race', 'name' => 'Hochbergtauren',     'name_short' => 'Hochbergtauren');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 29, 'language' => 'de', 'attribute' => 'race', 'name' => 'Leerenelf',          'name_short' => 'Leerenelf');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 30, 'language' => 'de', 'attribute' => 'race', 'name' => 'Lichtgeschmiedeter Draenei', 'name_short' => 'Lichtgeschmiedeter Draenei');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 31, 'language' => 'de', 'attribute' => 'race', 'name' => 'Zandalari-Troll',    'name_short' => 'Zandalari-Troll');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 32, 'language' => 'de', 'attribute' => 'race', 'name' => 'Kul Tiraner',        'name_short' => 'Kul Tiraner');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 34, 'language' => 'de', 'attribute' => 'race', 'name' => 'Dunkeleisenzwerg',   'name_short' => 'Dunkeleisenzwerg');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 35, 'language' => 'de', 'attribute' => 'race', 'name' => 'Vulpera',            'name_short' => 'Vulpera');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 36, 'language' => 'de', 'attribute' => 'race', 'name' => 'Mag\'har-Orc',       'name_short' => 'Mag\'har-Orc');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 37, 'language' => 'de', 'attribute' => 'race', 'name' => 'Mechagnome',         'name_short' => 'Mechagnome');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 52, 'language' => 'de', 'attribute' => 'race', 'name' => 'Dracthyr',           'name_short' => 'Dracthyr');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 70, 'language' => 'de', 'attribute' => 'race', 'name' => 'Dracthyr',           'name_short' => 'Dracthyr');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 84, 'language' => 'de', 'attribute' => 'race', 'name' => 'Irdener',            'name_short' => 'Irdener');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 85, 'language' => 'de', 'attribute' => 'race', 'name' => 'Irdener',            'name_short' => 'Irdener');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 86, 'language' => 'de', 'attribute' => 'race', 'name' => 'Haranir',            'name_short' => 'Haranir');

		// it
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 0,  'language' => 'it', 'attribute' => 'race', 'name' => 'Sconosciuto',        'name_short' => 'Sconosciuto');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 1,  'language' => 'it', 'attribute' => 'race', 'name' => 'Umani',              'name_short' => 'Umani');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 2,  'language' => 'it', 'attribute' => 'race', 'name' => 'Orchi',              'name_short' => 'Orchi');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 3,  'language' => 'it', 'attribute' => 'race', 'name' => 'Nani',               'name_short' => 'Nani');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 4,  'language' => 'it', 'attribute' => 'race', 'name' => 'Elfi della Notte',   'name_short' => 'Elfi della Notte');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 5,  'language' => 'it', 'attribute' => 'race', 'name' => 'Non Morti',          'name_short' => 'Non Morti');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 6,  'language' => 'it', 'attribute' => 'race', 'name' => 'Tauren',             'name_short' => 'Tauren');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 7,  'language' => 'it', 'attribute' => 'race', 'name' => 'Gnomi',              'name_short' => 'Gnomi');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 8,  'language' => 'it', 'attribute' => 'race', 'name' => 'Troll',              'name_short' => 'Troll');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 10, 'language' => 'it', 'attribute' => 'race', 'name' => 'Elfi del Sangue',    'name_short' => 'Elfi del Sangue');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 11, 'language' => 'it', 'attribute' => 'race', 'name' => 'Draenei',            'name_short' => 'Draenei');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 9,  'language' => 'it', 'attribute' => 'race', 'name' => 'Goblin',             'name_short' => 'Goblin');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 22, 'language' => 'it', 'attribute' => 'race', 'name' => 'Worgen',             'name_short' => 'Worgen');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 25, 'language' => 'it', 'attribute' => 'race', 'name' => 'Pandaren Alleanza',  'name_short' => 'Pandaren Alleanza');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 26, 'language' => 'it', 'attribute' => 'race', 'name' => 'Pandaren Orda',      'name_short' => 'Pandaren Orda');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 27, 'language' => 'it', 'attribute' => 'race', 'name' => 'Nobile Oscuro',      'name_short' => 'Nobile Oscuro');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 28, 'language' => 'it', 'attribute' => 'race', 'name' => 'Tauren di Alto Monte','name_short' => 'Tauren di Alto Monte');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 29, 'language' => 'it', 'attribute' => 'race', 'name' => 'Elfo del Vuoto',     'name_short' => 'Elfo del Vuoto');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 30, 'language' => 'it', 'attribute' => 'race', 'name' => 'Draenei Forgialuce', 'name_short' => 'Draenei Forgialuce');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 31, 'language' => 'it', 'attribute' => 'race', 'name' => 'Troll Zandalari',    'name_short' => 'Troll Zandalari');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 32, 'language' => 'it', 'attribute' => 'race', 'name' => 'Kul Tirano',         'name_short' => 'Kul Tirano');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 34, 'language' => 'it', 'attribute' => 'race', 'name' => 'Nano Ferroscuro',    'name_short' => 'Nano Ferroscuro');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 35, 'language' => 'it', 'attribute' => 'race', 'name' => 'Vulpera',            'name_short' => 'Vulpera');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 36, 'language' => 'it', 'attribute' => 'race', 'name' => 'Orco Mag\'har',      'name_short' => 'Orco Mag\'har');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 37, 'language' => 'it', 'attribute' => 'race', 'name' => 'Mechagnomo',         'name_short' => 'Mechagnomo');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 52, 'language' => 'it', 'attribute' => 'race', 'name' => 'Dracthyr',           'name_short' => 'Dracthyr');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 70, 'language' => 'it', 'attribute' => 'race', 'name' => 'Dracthyr',           'name_short' => 'Dracthyr');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 84, 'language' => 'it', 'attribute' => 'race', 'name' => 'Terrigeno',          'name_short' => 'Terrigeno');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 85, 'language' => 'it', 'attribute' => 'race', 'name' => 'Terrigeno',          'name_short' => 'Terrigeno');
		$sql_ary[] = array('game_id' => $this->game_id, 'attribute_id' => 86, 'language' => 'it', 'attribute' => 'race', 'name' => 'Haranir',            'name_short' => 'Haranir');

		$this->db->sql_multi_insert($this->table('bb_language_table'), $sql_ary);
	}

	/**
	 * Installs WoW specializations (issue #331).
	 *
	 * Skipped on installs that haven't run core migration v200b4 yet
	 * (table_names lacks bb_specializations_table).
	 */
	protected function install_specs(): void
	{
		if (!isset($this->table_names['bb_specializations_table']))
		{
			return;
		}

		$rows = [];
		foreach (wow_provider::spec_catalog() as $class_id => $specs)
		{
			foreach ($specs as $spec)
			{
				$rows[] = [
					'game_id'    => $this->game_id,
					'class_id'   => (int) $class_id,
					'role_id'    => (int) $spec['role_id'],
					'spec_name'  => (string) $spec['spec_name'],
					'spec_icon'  => (string) $spec['spec_icon'],
					'spec_order' => (int) $spec['spec_order'],
				];
			}
		}
		if (!$rows)
		{
			return;
		}
		$this->db->sql_multi_insert($this->table('bb_specializations_table'), $rows);

		// Seed translations into bb_language. Match by canonical (game_id,
		// class_id, spec_name) since sql_multi_insert doesn't return IDs.
		$this->install_spec_translations();
	}

	/**
	 * Insert bb_language rows for each spec_id × locale where Blizzard
	 * provides an official translation. Locales without a translation
	 * fall back to the canonical spec_name on the bb_specializations row.
	 */
	private function install_spec_translations(): void
	{
		if (!isset($this->table_names['bb_language_table']))
		{
			return;
		}

		$specs_table = $this->table('bb_specializations_table');
		$lang_table  = $this->table('bb_language_table');

		// Map (class_id|spec_name) → spec_id by reading back the rows we
		// just inserted. spec_name uniquely identifies a spec within a class.
		$sql = 'SELECT spec_id, class_id, spec_name FROM ' . $specs_table
			. " WHERE game_id = '" . $this->db->sql_escape($this->game_id) . "'";
		$result = $this->db->sql_query($sql);
		$by_key = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$by_key[(int) $row['class_id'] . '|' . $row['spec_name']] = (int) $row['spec_id'];
		}
		$this->db->sql_freeresult($result);

		$translations = wow_provider::spec_translations();
		$lang_rows = [];
		foreach (wow_provider::spec_catalog() as $class_id => $specs)
		{
			foreach ($specs as $spec)
			{
				$key = (int) $class_id . '|' . $spec['spec_name'];
				if (!isset($by_key[$key]))
				{
					continue;
				}
				$spec_id = $by_key[$key];

				foreach ($translations as $locale => $map)
				{
					if (!isset($map[$spec['spec_name']]))
					{
						continue;
					}
					$lang_rows[] = [
						'game_id'      => $this->game_id,
						'attribute_id' => $spec_id,
						'language'     => $locale,
						'attribute'    => 'spec',
						'name'         => (string) $map[$spec['spec_name']],
						'name_short'   => (string) $map[$spec['spec_name']],
					];
				}
			}
		}
		if ($lang_rows)
		{
			$this->db->sql_multi_insert($lang_table, $lang_rows);
		}
	}
}
