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
	/** Avatar driver name a linked user's avatar is set to (#369). */
	const AVATAR_DRIVER_NAME = 'avatar.driver.bbguildwow_character';

	/** @var wow_api */
	private $wow_api;

	/** @var \phpbb\db\driver\driver_interface */
	private $db;

	/** @var \FastImageSize\FastImageSize */
	private $imagesize;

	/** @var string */
	private $bb_players_table;

	/**
	 * Constructor.
	 *
	 * @param wow_api                           $wow_api
	 * @param \phpbb\db\driver\driver_interface $db
	 * @param \FastImageSize\FastImageSize      $imagesize
	 * @param string                            $bb_players_table
	 */
	public function __construct(wow_api $wow_api, \phpbb\db\driver\driver_interface $db, \FastImageSize\FastImageSize $imagesize, string $bb_players_table)
	{
		$this->wow_api = $wow_api;
		$this->db = $db;
		$this->imagesize = $imagesize;
		$this->bb_players_table = $bb_players_table;
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
		$success = $this->wow_api->sync_character($player_row);

		$phpbb_user_id = (int) ($player_row['phpbb_user_id'] ?? 0);
		if ($success && $phpbb_user_id > 0)
		{
			$this->sync_avatar((int) $player_row['player_id'], $phpbb_user_id);
		}

		return $success;
	}

	/**
	 * Sets the linked forum user's avatar to the character's Battle.net
	 * render, if they don't already have an avatar of their own (#369).
	 *
	 * @param int $player_id
	 * @param int $phpbb_user_id
	 */
	private function sync_avatar(int $player_id, int $phpbb_user_id): void
	{
		$sql = 'SELECT player_render_url, player_portrait_url FROM ' . $this->bb_players_table .
			' WHERE player_id = ' . $player_id;
		$result = $this->db->sql_query($sql);
		$player_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$player_row)
		{
			return;
		}

		$image_url = $this->usable_image_url($player_row['player_render_url']);
		if ($image_url === '')
		{
			$image_url = $this->usable_image_url($player_row['player_portrait_url']);
		}

		if ($image_url === '')
		{
			return;
		}

		$sql = 'SELECT user_avatar_type FROM ' . USERS_TABLE .
			' WHERE user_id = ' . $phpbb_user_id;
		$result = $this->db->sql_query($sql);
		$user_row = $this->db->sql_fetchrow($result);
		$this->db->sql_freeresult($result);

		if (!$user_row || !empty($user_row['user_avatar_type']))
		{
			return;
		}

		$dimensions = $this->imagesize->getImageSize($image_url);
		if (!is_array($dimensions) || empty($dimensions['width']) || empty($dimensions['height']))
		{
			return;
		}

		$data = array(
			'user_avatar_type'   => self::AVATAR_DRIVER_NAME,
			'user_avatar'        => $image_url,
			'user_avatar_width'  => (int) $dimensions['width'],
			'user_avatar_height' => (int) $dimensions['height'],
		);

		$sql = 'UPDATE ' . USERS_TABLE . ' SET ' . $this->db->sql_build_array('UPDATE', $data) .
			' WHERE user_id = ' . $phpbb_user_id;
		$this->db->sql_query($sql);
	}

	/**
	 * @param mixed $url
	 * @return string The URL if it looks like a real image URL, else ''.
	 */
	private function usable_image_url($url): string
	{
		$url = (string) $url;

		return ($url !== '' && $url !== 'N/A') ? $url : '';
	}
}
