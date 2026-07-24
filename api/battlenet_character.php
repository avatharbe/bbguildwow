<?php
/**
 * Battle.net WoW Character API
 *
 * Uses the Profile API endpoint:
 * - Character profile: GET /profile/wow/character/{realmSlug}/{characterName}
 *
 * @package   bbguildwow v2.0
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @author    Andreas Vandenberghe <sajaki@avathar.be>
 * @author    Chris Saylor
 * @author    Daniel Cannon <daniel@danielcannon.co.uk>
 * @copyright Copyright (c) 2011, 2015 Chris Saylor, Daniel Cannon, Andreas Vandenberghe
 * @link      https://develop.battle.net/
 */

namespace avathar\bbguildwow\api;

/**
 * Character resource.
 *
 * @package avathar\bbguildwow\api
 */
class battlenet_character extends battlenet_resource
{
	/** @var array */
	protected $methods_allowed = array('*');

	/** @var string */
	protected $endpoint = 'profile/wow/character';

	/**
	 * Fetch character profile summary.
	 *
	 * @param string $realm_slug     Lowercase hyphenated realm slug
	 * @param string $character_name Lowercase character name
	 * @return array
	 */
	public function getCharacter(string $realm_slug, string $character_name): array
	{
		global $user;

		if ($character_name === '')
		{
			throw new battlenet_api_exception($user->lang['WOWAPI_NO_CHARACTER']);
		}

		if ($realm_slug === '')
		{
			throw new battlenet_api_exception($user->lang['WOWAPI_NO_REALMS']);
		}

		return $this->consume($realm_slug . '/' . $this->normalize_name($character_name), array());
	}

	/**
	 * Fetch character media (avatar, inset, main render).
	 *
	 * @param string $realm_slug     Lowercase hyphenated realm slug
	 * @param string $character_name Lowercase character name
	 * @return array
	 */
	public function getCharacterMedia(string $realm_slug, string $character_name): array
	{
		return $this->consume($realm_slug . '/' . $this->normalize_name($character_name) . '/character-media', array());
	}

	/**
	 * Fetch character specializations (active spec + all specs).
	 *
	 * @param string $realm_slug     Lowercase hyphenated realm slug
	 * @param string $character_name Lowercase character name
	 * @return array
	 */
	public function getCharacterSpecializations(string $realm_slug, string $character_name): array
	{
		return $this->consume($realm_slug . '/' . $this->normalize_name($character_name) . '/specializations', array());
	}

	/**
	 * Fetch character equipment (gear in all slots).
	 *
	 * @param string $realm_slug     Lowercase hyphenated realm slug
	 * @param string $character_name Lowercase character name
	 * @return array
	 */
	public function getCharacterEquipment(string $realm_slug, string $character_name): array
	{
		return $this->consume($realm_slug . '/' . $this->normalize_name($character_name) . '/equipment', array());
	}

	/**
	 * Fetch character statistics (primary and secondary stats).
	 *
	 * @param string $realm_slug     Lowercase hyphenated realm slug
	 * @param string $character_name Lowercase character name
	 * @return array
	 */
	public function getCharacterStatistics(string $realm_slug, string $character_name): array
	{
		return $this->consume($realm_slug . '/' . $this->normalize_name($character_name) . '/statistics', array());
	}

	/**
	 * Fetch character professions.
	 *
	 * @param string $realm_slug     Lowercase hyphenated realm slug
	 * @param string $character_name Lowercase character name
	 * @return array
	 */
	public function getCharacterProfessions(string $realm_slug, string $character_name): array
	{
		return $this->consume($realm_slug . '/' . $this->normalize_name($character_name) . '/professions', array());
	}

	/**
	 * Fetch character Mythic Keystone profile (M+ rating and best runs).
	 *
	 * @param string $realm_slug     Lowercase hyphenated realm slug
	 * @param string $character_name Lowercase character name
	 * @return array
	 */
	public function getCharacterMythicKeystoneProfile(string $realm_slug, string $character_name): array
	{
		return $this->consume($realm_slug . '/' . $this->normalize_name($character_name) . '/mythic-keystone-profile', array());
	}

	/**
	 * Fetch character PvP summary (honor level, arena/RBG ratings).
	 *
	 * @param string $realm_slug     Lowercase hyphenated realm slug
	 * @param string $character_name Lowercase character name
	 * @return array
	 */
	public function getCharacterPvPSummary(string $realm_slug, string $character_name): array
	{
		return $this->consume($realm_slug . '/' . $this->normalize_name($character_name) . '/pvp-summary', array());
	}

	/**
	 * Normalize a character name for the Battle.net API.
	 *
	 * The API expects lowercase names with proper Unicode handling.
	 * PHP's strtolower() only handles ASCII; mb_strtolower() is needed
	 * for characters like É→é, Å→å, Ð→ð, Cyrillic, etc.
	 *
	 * @param string $name Character name
	 * @return string Lowercased name safe for API URLs
	 */
	private function normalize_name(string $name): string
	{
		return mb_strtolower(trim($name), 'UTF-8');
	}
}
