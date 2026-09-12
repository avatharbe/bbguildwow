<?php
/**
 * bbGuild WoW Extension — character-render avatar driver
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\avatar;

/**
 * Displays a claimed WoW character's Battle.net render as the linked
 * forum user's avatar. System-managed only (set automatically by
 * character_sync_handler when the user has no avatar of their own) —
 * not offered as a manual choice in UCP, hence prepare_form() always
 * declines rather than rendering a form.
 */
class character_avatar_driver extends \phpbb\avatar\driver\driver
{
	/**
	 * {@inheritdoc}
	 */
	public function get_data($row)
	{
		return array(
			'src'    => $row['avatar'],
			'width'  => $row['avatar_width'],
			'height' => $row['avatar_height'],
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function prepare_form($request, $template, $user, $row, &$error)
	{
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function process_form($request, $template, $user, $row, &$error)
	{
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_template_name()
	{
		return '';
	}
}
