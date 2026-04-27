<?php
/**
 * WoW Game Provider
 *
 * Registers World of Warcraft as a game plugin with bbGuild core.
 *
 * @package   bbguildwow v2.0
 * @copyright 2018 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\game;

use avathar\bbguild\model\games\game_provider_interface;
use avathar\bbguild\model\games\specialization_provider_interface;

/**
 * Class wow_provider
 *
 * @package avathar\bbguildwow\game
 */
class wow_provider implements game_provider_interface, specialization_provider_interface
{
	/** @var wow_installer */
	private $installer;

	/** @var wow_api */
	private $api;

	/** @var \phpbb\extension\manager */
	private $ext_manager;

	/**
	 * @param wow_installer             $installer
	 * @param wow_api                   $api
	 * @param \phpbb\extension\manager  $ext_manager
	 */
	public function __construct(wow_installer $installer, wow_api $api, \phpbb\extension\manager $ext_manager)
	{
		$this->installer = $installer;
		$this->api = $api;
		$this->ext_manager = $ext_manager;
	}

	/**
	 * @inheritdoc
	 */
	public function get_game_id(): string
	{
		return 'wow';
	}

	/**
	 * @inheritdoc
	 */
	public function get_game_name(): string
	{
		return 'World of Warcraft';
	}

	/**
	 * @inheritdoc
	 */
	public function get_installer(): \avathar\bbguild\model\games\game_install_interface
	{
		return $this->installer;
	}

	/**
	 * @inheritdoc
	 */
	public function get_boss_base_url(): string
	{
		return 'http://www.wowhead.com/?npc=%s';
	}

	/**
	 * @inheritdoc
	 */
	public function get_zone_base_url(): string
	{
		return 'http://www.wowhead.com/?zone=%s';
	}

	/**
	 * @inheritdoc
	 */
	public function get_images_path(): string
	{
		return $this->ext_manager->get_extension_path('avathar/bbguildwow', true) . 'images/';
	}

	/**
	 * @inheritdoc
	 */
	public function has_api(): bool
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function get_api(): ?\avathar\bbguild\model\games\game_api_interface
	{
		return $this->api;
	}

	/**
	 * @inheritdoc
	 */
	public function get_regions(): array
	{
		return array(
			'us'  => 'US',
			'eu'  => 'EU',
			'kr'  => 'KR',
			'tw'  => 'TW',
			'sea' => 'SEA',
		);
	}

	/**
	 * @inheritdoc
	 */
	public function get_api_locales(): array
	{
		return array(
			'eu'  => array('en_GB', 'de_DE', 'es_ES', 'fr_FR', 'it_IT', 'pl_PL', 'pt_PT', 'ru_RU'),
			'us'  => array('en_US', 'es_MX', 'pt_BR'),
			'kr'  => array('ko_KR'),
			'tw'  => array('zh_TW'),
			'sea' => array('en_US'),
		);
	}

	/**
	 * @inheritdoc
	 */
	public function get_armor_types(): array
	{
		return array(
			'CLOTH'   => 'Cloth',
			'LEATHER' => 'Leather',
			'MAIL'    => 'Mail',
			'PLATE'   => 'Plate',
		);
	}

	/**
	 * Specialization catalog for WoW (issue #331).
	 *
	 * Static so the installer can read the same source of truth without
	 * needing to construct a provider (provider depends on api + ext_manager).
	 *
	 * Class IDs match Battle.net's playable_class IDs (1=Warrior … 13=Evoker)
	 * so the data lines up with character profile responses.
	 * Role IDs use the bbguild abstract role seed: 0=DPS, 1=Healer, 2=Tank.
	 *
	 * @return array<int, list<array{spec_name:string,role_id:int,spec_icon:string,spec_order:int}>>
	 */
	public static function spec_catalog(): array
	{
		// Role constants for readability.
		$dps    = 0;
		$healer = 1;
		$tank   = 2;

		return [
			// 1 — Warrior
			1 => [
				['spec_name' => 'Arms',       'role_id' => $dps,    'spec_icon' => 'warrior_arms',       'spec_order' => 1],
				['spec_name' => 'Fury',       'role_id' => $dps,    'spec_icon' => 'warrior_fury',       'spec_order' => 2],
				['spec_name' => 'Protection', 'role_id' => $tank,   'spec_icon' => 'warrior_protection', 'spec_order' => 3],
			],
			// 2 — Paladin
			2 => [
				['spec_name' => 'Holy',        'role_id' => $healer, 'spec_icon' => 'paladin_holy',        'spec_order' => 1],
				['spec_name' => 'Protection',  'role_id' => $tank,   'spec_icon' => 'paladin_protection',  'spec_order' => 2],
				['spec_name' => 'Retribution', 'role_id' => $dps,    'spec_icon' => 'paladin_retribution', 'spec_order' => 3],
			],
			// 3 — Hunter
			3 => [
				['spec_name' => 'Beast Mastery', 'role_id' => $dps, 'spec_icon' => 'hunter_beast_mastery', 'spec_order' => 1],
				['spec_name' => 'Marksmanship',  'role_id' => $dps, 'spec_icon' => 'hunter_marksmanship',  'spec_order' => 2],
				['spec_name' => 'Survival',      'role_id' => $dps, 'spec_icon' => 'hunter_survival',      'spec_order' => 3],
			],
			// 4 — Rogue
			4 => [
				['spec_name' => 'Assassination', 'role_id' => $dps, 'spec_icon' => 'rogue_assassination', 'spec_order' => 1],
				['spec_name' => 'Outlaw',        'role_id' => $dps, 'spec_icon' => 'rogue_outlaw',        'spec_order' => 2],
				['spec_name' => 'Subtlety',      'role_id' => $dps, 'spec_icon' => 'rogue_subtlety',      'spec_order' => 3],
			],
			// 5 — Priest
			5 => [
				['spec_name' => 'Discipline', 'role_id' => $healer, 'spec_icon' => 'priest_discipline', 'spec_order' => 1],
				['spec_name' => 'Holy',       'role_id' => $healer, 'spec_icon' => 'priest_holy',       'spec_order' => 2],
				['spec_name' => 'Shadow',     'role_id' => $dps,    'spec_icon' => 'priest_shadow',     'spec_order' => 3],
			],
			// 6 — Death Knight
			6 => [
				['spec_name' => 'Blood',  'role_id' => $tank, 'spec_icon' => 'deathknight_blood',  'spec_order' => 1],
				['spec_name' => 'Frost',  'role_id' => $dps,  'spec_icon' => 'deathknight_frost',  'spec_order' => 2],
				['spec_name' => 'Unholy', 'role_id' => $dps,  'spec_icon' => 'deathknight_unholy', 'spec_order' => 3],
			],
			// 7 — Shaman
			7 => [
				['spec_name' => 'Elemental',   'role_id' => $dps,    'spec_icon' => 'shaman_elemental',   'spec_order' => 1],
				['spec_name' => 'Enhancement', 'role_id' => $dps,    'spec_icon' => 'shaman_enhancement', 'spec_order' => 2],
				['spec_name' => 'Restoration', 'role_id' => $healer, 'spec_icon' => 'shaman_restoration', 'spec_order' => 3],
			],
			// 8 — Mage
			8 => [
				['spec_name' => 'Arcane', 'role_id' => $dps, 'spec_icon' => 'mage_arcane', 'spec_order' => 1],
				['spec_name' => 'Fire',   'role_id' => $dps, 'spec_icon' => 'mage_fire',   'spec_order' => 2],
				['spec_name' => 'Frost',  'role_id' => $dps, 'spec_icon' => 'mage_frost',  'spec_order' => 3],
			],
			// 9 — Warlock
			9 => [
				['spec_name' => 'Affliction',  'role_id' => $dps, 'spec_icon' => 'warlock_affliction',  'spec_order' => 1],
				['spec_name' => 'Demonology',  'role_id' => $dps, 'spec_icon' => 'warlock_demonology',  'spec_order' => 2],
				['spec_name' => 'Destruction', 'role_id' => $dps, 'spec_icon' => 'warlock_destruction', 'spec_order' => 3],
			],
			// 10 — Monk
			10 => [
				['spec_name' => 'Brewmaster', 'role_id' => $tank,   'spec_icon' => 'monk_brewmaster', 'spec_order' => 1],
				['spec_name' => 'Mistweaver', 'role_id' => $healer, 'spec_icon' => 'monk_mistweaver', 'spec_order' => 2],
				['spec_name' => 'Windwalker', 'role_id' => $dps,    'spec_icon' => 'monk_windwalker', 'spec_order' => 3],
			],
			// 11 — Druid
			11 => [
				['spec_name' => 'Balance',     'role_id' => $dps,    'spec_icon' => 'druid_balance',     'spec_order' => 1],
				['spec_name' => 'Feral',       'role_id' => $dps,    'spec_icon' => 'druid_feral',       'spec_order' => 2],
				['spec_name' => 'Guardian',    'role_id' => $tank,   'spec_icon' => 'druid_guardian',    'spec_order' => 3],
				['spec_name' => 'Restoration', 'role_id' => $healer, 'spec_icon' => 'druid_restoration', 'spec_order' => 4],
			],
			// 12 — Demon Hunter
			12 => [
				['spec_name' => 'Havoc',     'role_id' => $dps,  'spec_icon' => 'demonhunter_havoc',     'spec_order' => 1],
				['spec_name' => 'Vengeance', 'role_id' => $tank, 'spec_icon' => 'demonhunter_vengeance', 'spec_order' => 2],
			],
			// 13 — Evoker
			13 => [
				['spec_name' => 'Devastation',  'role_id' => $dps,    'spec_icon' => 'evoker_devastation',  'spec_order' => 1],
				['spec_name' => 'Preservation', 'role_id' => $healer, 'spec_icon' => 'evoker_preservation', 'spec_order' => 2],
				['spec_name' => 'Augmentation', 'role_id' => $dps,    'spec_icon' => 'evoker_augmentation', 'spec_order' => 3],
			],
		];
	}

	public function get_spec_label(): string
	{
		return 'Specialization';
	}

	/**
	 * Interface implementation: delegates to the static catalog.
	 *
	 * @return array<int, list<array{spec_name:string,role_id:int,spec_icon:string,spec_order:int}>>
	 */
	public function get_specializations(): array
	{
		return self::spec_catalog();
	}

	/**
	 * Per-locale display names for each canonical (English) spec name.
	 *
	 * Sourced from Battle.net's `playable-specialization/{id}` endpoint
	 * `name` object, which Blizzard maintains per official locale. We only
	 * include locales that Blizzard officially supports for WoW: de, fr,
	 * it, es. For locales without an official translation (pl, nl), the
	 * canonical English `spec_name` on the `bb_specializations` row acts
	 * as the fallback.
	 *
	 * Translation by canonical English name is safe here because every
	 * duplicate English name (Frost, Holy, Protection, Restoration)
	 * resolves to the same translation across classes in every locale.
	 *
	 * The phpBB convention `es_x_tu` maps to Battle.net's `es_ES`.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function spec_translations(): array
	{
		return [
			'de' => [
				'Arms' => 'Waffen',
				'Fury' => 'Furor',
				'Protection' => 'Schutz',
				'Holy' => 'Heilig',
				'Retribution' => 'Vergeltung',
				'Beast Mastery' => 'Tierherrschaft',
				'Marksmanship' => 'Treffsicherheit',
				'Survival' => 'Überleben',
				'Assassination' => 'Meucheln',
				'Outlaw' => 'Gesetzlosigkeit',
				'Subtlety' => 'Täuschung',
				'Discipline' => 'Disziplin',
				'Shadow' => 'Schatten',
				'Blood' => 'Blut',
				'Frost' => 'Frost',
				'Unholy' => 'Unheilig',
				'Elemental' => 'Elementar',
				'Enhancement' => 'Verstärkung',
				'Restoration' => 'Wiederherstellung',
				'Arcane' => 'Arkan',
				'Fire' => 'Feuer',
				'Affliction' => 'Gebrechen',
				'Demonology' => 'Dämonologie',
				'Destruction' => 'Zerstörung',
				'Brewmaster' => 'Braumeister',
				'Mistweaver' => 'Nebelwirker',
				'Windwalker' => 'Windläufer',
				'Balance' => 'Gleichgewicht',
				'Feral' => 'Wildheit',
				'Guardian' => 'Wächter',
				'Havoc' => 'Verwüstung',
				'Vengeance' => 'Rachsucht',
				'Devastation' => 'Verheerung',
				'Preservation' => 'Bewahrung',
				'Augmentation' => 'Verstärkung',
			],
			'fr' => [
				'Arms' => 'Armes',
				'Fury' => 'Fureur',
				'Protection' => 'Protection',
				'Holy' => 'Sacré',
				'Retribution' => 'Vindicte',
				'Beast Mastery' => 'Maîtrise des bêtes',
				'Marksmanship' => 'Précision',
				'Survival' => 'Survie',
				'Assassination' => 'Assassinat',
				'Outlaw' => 'Hors-la-loi',
				'Subtlety' => 'Finesse',
				'Discipline' => 'Discipline',
				'Shadow' => 'Ombre',
				'Blood' => 'Sang',
				'Frost' => 'Givre',
				'Unholy' => 'Impie',
				'Elemental' => 'Élémentaire',
				'Enhancement' => 'Amélioration',
				'Restoration' => 'Restauration',
				'Arcane' => 'Arcanes',
				'Fire' => 'Feu',
				'Affliction' => 'Affliction',
				'Demonology' => 'Démonologie',
				'Destruction' => 'Destruction',
				'Brewmaster' => 'Maître brasseur',
				'Mistweaver' => 'Tisse-brume',
				'Windwalker' => 'Marche-vent',
				'Balance' => 'Équilibre',
				'Feral' => 'Farouche',
				'Guardian' => 'Gardien',
				'Havoc' => 'Dévastation',
				'Vengeance' => 'Vengeance',
				'Devastation' => 'Dévastation',
				'Preservation' => 'Préservation',
				'Augmentation' => 'Augmentation',
			],
			'it' => [
				'Arms' => 'Armi',
				'Fury' => 'Furia',
				'Protection' => 'Protezione',
				'Holy' => 'Sacro',
				'Retribution' => 'Castigo',
				'Beast Mastery' => 'Affinità Animale',
				'Marksmanship' => 'Precisione di Tiro',
				'Survival' => 'Sopravvivenza',
				'Assassination' => 'Assassinio',
				'Outlaw' => 'Fuorilegge',
				'Subtlety' => 'Scaltrezza',
				'Discipline' => 'Disciplina',
				'Shadow' => 'Ombra',
				'Blood' => 'Sangue',
				'Frost' => 'Gelo',
				'Unholy' => 'Empietà',
				'Elemental' => 'Elementale',
				'Enhancement' => 'Potenziamento',
				'Restoration' => 'Rigenerazione',
				'Arcane' => 'Arcano',
				'Fire' => 'Fuoco',
				'Affliction' => 'Afflizione',
				'Demonology' => 'Demonologia',
				'Destruction' => 'Distruzione',
				'Brewmaster' => 'Mastro Birraio',
				'Mistweaver' => 'Misticismo',
				'Windwalker' => 'Impeto',
				'Balance' => 'Equilibrio',
				'Feral' => 'Aggressore Ferino',
				'Guardian' => 'Guardiano Ferino',
				'Havoc' => 'Rovina',
				'Vengeance' => 'Vendetta',
				'Devastation' => 'Devastazione',
				'Preservation' => 'Conservazione',
				'Augmentation' => 'Fortificazione',
			],
			'es_x_tu' => [
				'Arms' => 'Armas',
				'Fury' => 'Furia',
				'Protection' => 'Protección',
				'Holy' => 'Sagrado',
				'Retribution' => 'Reprensión',
				'Beast Mastery' => 'Bestias',
				'Marksmanship' => 'Puntería',
				'Survival' => 'Supervivencia',
				'Assassination' => 'Asesinato',
				'Outlaw' => 'Forajido',
				'Subtlety' => 'Sutileza',
				'Discipline' => 'Disciplina',
				'Shadow' => 'Sombra',
				'Blood' => 'Sangre',
				'Frost' => 'Escarcha',
				'Unholy' => 'Profano',
				'Elemental' => 'Elemental',
				'Enhancement' => 'Mejora',
				'Restoration' => 'Restauración',
				'Arcane' => 'Arcano',
				'Fire' => 'Fuego',
				'Affliction' => 'Aflicción',
				'Demonology' => 'Demonología',
				'Destruction' => 'Destrucción',
				'Brewmaster' => 'Maestro cervecero',
				'Mistweaver' => 'Tejedor de niebla',
				'Windwalker' => 'Viajero del viento',
				'Balance' => 'Equilibrio',
				'Feral' => 'Feral',
				'Guardian' => 'Guardián',
				'Havoc' => 'Devastación',
				'Vengeance' => 'Venganza',
				'Devastation' => 'Devastación',
				'Preservation' => 'Preservación',
				'Augmentation' => 'Aumento',
			],
		];
	}
}
