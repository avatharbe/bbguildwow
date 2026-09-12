<?php
/**
 *
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * Implements bbguild core's character_sync_interface for WoW, delegating
 * all actual sync orchestration to wow_api::sync_character() (#362).
 *
 */

namespace avathar\bbguildwow\sync;

use avathar\bbguild\model\games\character_sync_interface;
use avathar\bbguildwow\game\wow_api;

/**
 * Class character_sync_handler
 *
 * @package avathar\bbguildwow\sync
 */
class character_sync_handler implements character_sync_interface
{
	/** @var wow_api */
	private $wow_api;

	/**
	 * Constructor.
	 *
	 * @param wow_api $wow_api
	 */
	public function __construct(wow_api $wow_api)
	{
		$this->wow_api = $wow_api;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_game_id(): string
	{
		return 'wow';
	}

	/**
	 * {@inheritdoc}
	 */
	public function sync_character(array $player_row): bool
	{
		return $this->wow_api->sync_character($player_row);
	}
}
