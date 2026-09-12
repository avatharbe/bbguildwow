<?php
/**
 * bbguildwow acp language file for achievement (EN)
 *
 * @package   phpBB Extension - bbguildwow
 * @copyright 2009 bbguild
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @author    sajaki
 * @link      http://www.avathar.be/bbdkp
 * @version   2.0
 */

/**
 * @ignore
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

// Create the lang array if it does not already exist
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// Merge the following language entries into the lang array
$lang = array_merge(
	$lang, array(
	'ACP_ADDACHIEV'            => 'Game Achievements',
	'ACP_LISTACHIEV'           => 'Achievement List',
	'ACP_MM_EDITACHI'          => 'Edit Achievement',

	'ACHIEV_ADDED'             => 'Achievement added successfully.',
	'ACHIEV_UPDATED'           => 'Achievement updated successfully.',
	'ACHIEV_DELETED'           => 'Achievement(s) deleted successfully.',
	'ACHIEV_FOOTCOUNT'         => '%d achievements found.',
	'CONFIRM_DELETE_ACHIEVEMENT' => 'Are you sure you want to delete achievement "%s"?',
	'WARNING_NOACHIEVEMENTS'   => 'No achievements found. Use the API button or add achievements manually.',

	'ADD_ACHIEVEMENT'          => 'Add Achievement',
	'ADD_ACHIEVEMENT_MANUAL'   => 'Add Manual',
	'ADD_ACHIEVEMENT_API'      => 'Load from API',
	'UPDATE_ACHIEVEMENT'       => 'Update Achievement',
	'DELETE_ACHIEVEMENT'       => 'Delete Achievement',
	'ACHIEVEMENT_ADD_EXPLAIN'  => 'Add or edit achievement details. You can populate achievements automatically via the Battle.net API.',
	'ACHI_ID'                  => 'Achievement ID',
	'ACHI_TITLE'               => 'Title',
	'ACHI_DESC'                => 'Description',
	'ACHI_POINTS'              => 'Points',

	'FV_REQUIRED_TITLE'        => 'Please enter a title.',
	'FV_REQUIRED_DESCRIPTION'  => 'Please enter a description.',
	'FV_REQUIRED_ID'           => 'Please enter an achievement ID.',

	'RETURN_ACHIEVLIST'        => 'Return to Index',
	'ACP_GUILD_OPTION_NONE'    => '(None)',
	)
);
