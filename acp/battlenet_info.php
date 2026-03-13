<?php
/**
 * @package bbguild_wow v2.0
 * @copyright 2018 avathar.be
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguild_wow\acp;

/**
 * Info class for ACP Battle.net API module
 *
 * @package bbguild_wow
 */
class battlenet_info
{
	/**
	 * phpbb module function
	 */
	public function module()
	{
		return array(
			'filename' => '\avathar\bbguild_wow\acp\battlenet_module',
			'title'    => 'ACP_BBGUILD_MAINPAGE',
			'version'  => '2.0.0-b1',
			'modes'    => array(
				'battlenet' => array(
					'title'   => 'ACP_WOW_BATTLENET',
					'auth'    => 'ext_avathar/bbguild_wow && acl_a_board && acl_a_bbguild',
					'cat'     => array('ACP_BBGUILD_MAINPAGE'),
				),
			),
		);
	}
}
