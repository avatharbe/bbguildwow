<?php
/**
 * bbGuild WoW Battle.net API — thrown on SDK-level failures
 *
 * @package   bbguildwow v2.0
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\api;

/**
 * Replaces trigger_error() in the Battle.net SDK layer. This code is
 * reachable from JSON/AJAX sync controllers, which need a catchable
 * failure to convert into a JsonResponse — trigger_error() isn't
 * catchable and would render phpBB's HTML error page instead.
 *
 * @package avathar\bbguildwow\api
 */
class battlenet_api_exception extends \RuntimeException
{
}
