<?php
/**
 * achievements module
 *
 * @package   bbguild_wow
 * @copyright 2018 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */
namespace avathar\bbguild_wow\views;

use avathar\bbguild_wow\model\achievement;
use avathar\bbguild\views\viewnavigation;
use avathar\bbguild\views\iviews;

/**
 * Class viewachievements
 *
 * @package avathar\bbguild_wow\views
 */
class viewachievements implements iviews
{
	private $navigation;
	public  $response;
	private $tpl;
	public $achievement;

	/**
	 * viewachievements constructor.
	 *
	 * @param \avathar\bbguild\views\viewnavigation $navigation
	 */
	public function __construct(viewnavigation $navigation)
	{
		$this->navigation = $navigation;
		$this->buildpage();
	}

	/**
	 *prepare the rendering
	 */
	public function buildpage()
	{
		global $template;
		$this->tpl = 'main.html';
		$achievements =  $this->navigation->guild->getGuildAchievements();
		foreach ($achievements as $achi)
		{
			$a = $achi;
			$achi['detail'] = new achievement($game, $achi[$i]['id']);
		}

		$i=0;

		$template->assign_vars(
			array(
			'EMBLEM'                =>  $this->navigation->guild->getEmblempath(),
			'GUILD_FACTION'         =>  $this->navigation->guild->getFactionname(),
			'S_DISPLAY_WOWACHIEVEMENTS'     => true,
			)
		);
		$title = $this->navigation->user->lang['WELCOME'];

		unset($newsarr);
		// fully rendered page source that will be output on the screen.
		$this->response = $this->navigation->helper->render($this->tpl, $title);

	}


}
