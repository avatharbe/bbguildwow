<?php
/**
 * bbGuild WoW Extension
 *
 * @package   bbguildwow v2.0
 * @copyright 2018 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow;

use phpbb\extension\base;

/**
 * Class ext
 *
 * @package avathar\bbguildwow
 */
class ext extends base
{
	const MIN_PHP_VERSION = '8.1.0';
	const MIN_PHPBB_VERSION = '3.3.0';

	/**
	 * Check whether or not the extension can be enabled.
	 * Requires PHP 8.1+, phpBB 3.3+, and bbGuild core extension to be enabled.
	 *
	 * @return bool|array True if enableable, or an array of error messages otherwise
	 */
	public function is_enableable()
	{
		$errors = [];

		$user = $this->container->get('user');
		$user->add_lang_ext('avathar/bbguildwow', 'wow');

		if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '<'))
		{
			$errors[] = $user->lang('BBGUILDWOW_PHP_VERSION_FAIL', self::MIN_PHP_VERSION, PHP_VERSION);
		}

		if (phpbb_version_compare(PHPBB_VERSION, self::MIN_PHPBB_VERSION, '<'))
		{
			$errors[] = $user->lang('BBGUILDWOW_PHPBB_VERSION_FAIL', self::MIN_PHPBB_VERSION, PHPBB_VERSION);
		}

		$ext_manager = $this->container->get('ext.manager');
		if (!$ext_manager->is_enabled('avathar/bbguild'))
		{
			$errors[] = $user->lang('BBGUILDWOW_REQUIRES_BBGUILD');
		}

		return empty($errors) ? true : $errors;
	}
}
