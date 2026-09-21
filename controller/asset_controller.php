<?php
/**
 * Asset file-serving controller
 *
 * Serves emblem and portrait images stored in phpBB's files/ directory,
 * which is protected by a deny-all .htaccess. This controller reads
 * files from disk and streams them via BinaryFileResponse.
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\controller;

use phpbb\db\driver\driver_interface;
use phpbb\language\language;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class asset_controller
{
	/** @var driver_interface */
	protected $db;

	/** @var language */
	protected $language;

	/** @var string */
	protected $root_path;

	/** @var string */
	protected $guild_table;

	/** @var string */
	protected $players_table;

	public function __construct(
		driver_interface $db,
		language $language,
		string $root_path,
		string $guild_table,
		string $players_table
	)
	{
		$this->db = $db;
		$this->language = $language;
		$this->root_path = $root_path;
		$this->guild_table = $guild_table;
		$this->players_table = $players_table;
	}

	/**
	 * Serve a guild emblem image.
	 *
	 * @param int $guild_id
	 * @return Response
	 */
	public function serve_emblem($guild_id)
	{
		$guild_id = (int) $guild_id;

		$sql = 'SELECT emblemurl FROM ' . $this->guild_table . ' WHERE id = ' . $guild_id;
		$result = $this->db->sql_query($sql);
		$emblemurl = $this->db->sql_fetchfield('emblemurl');
		$this->db->sql_freeresult($result);

		if (empty($emblemurl) || strpos($emblemurl, 'bbguildwow/emblems/') === false)
		{
			return new Response($this->lang_not_found(), 404);
		}

		return $this->serve_file($emblemurl, 'image/png');
	}

	/**
	 * Serve a player portrait image.
	 *
	 * @param int $player_id
	 * @return Response
	 */
	public function serve_portrait($player_id)
	{
		$player_id = (int) $player_id;

		$sql = 'SELECT player_portrait_url FROM ' . $this->players_table . ' WHERE player_id = ' . $player_id;
		$result = $this->db->sql_query($sql);
		$portrait_url = $this->db->sql_fetchfield('player_portrait_url');
		$this->db->sql_freeresult($result);

		if (empty($portrait_url) || strpos($portrait_url, 'bbguildwow/portraits/') === false)
		{
			return new Response($this->lang_not_found(), 404);
		}

		// Determine content type from extension
		$ext = strtolower(pathinfo($portrait_url, PATHINFO_EXTENSION));
		$content_type = ($ext === 'png') ? 'image/png' : 'image/jpeg';

		return $this->serve_file($portrait_url, $content_type);
	}

	/**
	 * Serve a player's full-body character render image.
	 *
	 * @param int $player_id
	 * @return Response
	 */
	public function serve_render($player_id)
	{
		$player_id = (int) $player_id;

		$sql = 'SELECT player_render_url FROM ' . $this->players_table . ' WHERE player_id = ' . $player_id;
		$result = $this->db->sql_query($sql);
		$render_url = $this->db->sql_fetchfield('player_render_url');
		$this->db->sql_freeresult($result);

		if (empty($render_url) || strpos($render_url, 'bbguildwow/renders/') === false)
		{
			return new Response($this->lang_not_found(), 404);
		}

		$ext = strtolower(pathinfo($render_url, PATHINFO_EXTENSION));
		$content_type = ($ext === 'png') ? 'image/png' : 'image/jpeg';

		return $this->serve_file($render_url, $content_type);
	}

	/**
	 * @return string
	 */
	private function lang_not_found(): string
	{
		$this->language->add_lang('wow', 'avathar/bbguildwow');
		return $this->language->lang('WOW_ASSET_NOT_FOUND');
	}

	/**
	 * Read a file from disk and return a BinaryFileResponse.
	 *
	 * @param string $relative_path Path relative to phpBB root (e.g. files/bbguildwow/emblems/foo.png)
	 * @param string $content_type  MIME type
	 * @return Response
	 */
	private function serve_file(string $relative_path, string $content_type): Response
	{
		$absolute_path = $this->root_path . $relative_path;

		// Path traversal guard: resolved path must stay under files/bbguildwow/
		$real_path = realpath($absolute_path);
		$allowed_base = realpath($this->root_path . 'files/bbguildwow');

		if ($real_path === false || $allowed_base === false || strpos($real_path, $allowed_base) !== 0)
		{
			return new Response($this->lang_not_found(), 404);
		}

		$response = new BinaryFileResponse($real_path);
		$response->headers->set('Content-Type', $content_type);
		$response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE);
		$response->setPublic();
		$response->setMaxAge(86400);
		$response->setAutoLastModified();

		return $response;
	}
}
