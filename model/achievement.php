<?php
/**
 * This file holds the Achievement API class
 *
 * @package   bbguild_wow v2.0
 * @copyright 2018 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace avathar\bbguild_wow\model;

use avathar\bbguild_wow\api\battlenet;
use avathar\bbguild\model\games\game;
use avathar\bbguild\model\player\guilds;

/**
 * This provides data about an individual achievement.
 *
 * @package avathar\bbguild_wow\model
 */
class achievement
{
	public $bb_achievement_track_table;
	public $bb_achievement_table;
	public $bb_achievement_rewards_table;
	public $bb_criteria_track_table;
	public $bb_achievement_criteria_table;
	public $bb_relations_table;

	/**
	 * achievement id
	 * bb_achievement
	 * @var int
	 */
	public $id;

	/**
	 * game id
	 * bb_achievement
	 * @var string
	 */
	public $game_id;

	/**
	 * title of achievement
	 * bb_achievement
	 * @var string
	 */
	protected $title;

	/**
	 * points
	 * bb_achievement
	 * @var int
	 */
	protected $points;

	/**
	 * long description
	 * bb_achievement
	 * @var string
	 */
	protected $description;

	/**
	 * icon
	 *
	 * @var string
	 */
	protected $icon;

	/**
	 * faction ID
	 * bb_achievement
	 * @var int
	 */
	protected $factionId;

	/**
	 * guild if its a guild achievement
	 * bb_achievement
	 * @var string
	 */
	protected $guild_id;

	/**
	 * player_id if its an individual achievement
	 * bb_achievement
	 * @var string
	 */
	protected $player_id;

	/**
	 * oneline description of rewards attached to this achievement.
	 *
	 * @var string
	 */
	protected $reward;

	/***************************************/

	/**
	 * criteria
	 * bb_achievement_criteria
	 * @var array
	 */
	protected $criteria;

	/**
	 * reward
	 * bb_achievement_rewards
	 * @var array
	 */
	protected $rewardItems;

	/**
	 * date of achievement completion.
	 * bb_achievement_track
	 * @type double
	 */
	protected $achievements_completed;

	/***************************************/

	/**
	 * @type game
	 */
	private $game;

	/***************************************/

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 * @return achievement
	 */
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getGameId()
	{
		return $this->game_id;
	}

	/**
	 * @param string $game_id
	 * @return achievement
	 */
	public function setGameId($game_id)
	{
		$this->game_id = $game_id;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $title
	 * @return achievement
	 */
	public function setTitle($title)
	{
		$this->title = $title;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getPoints()
	{
		return $this->points;
	}

	/**
	 * @param int $points
	 * @return achievement
	 */
	public function setPoints($points)
	{
		$this->points = $points;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * @param string $description
	 * @return achievement
	 */
	public function setDescription($description)
	{
		$this->description = $description;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getIcon()
	{
		return $this->icon;
	}

	/**
	 * @param string $icon
	 * @return achievement
	 */
	public function setIcon($icon)
	{
		$this->icon = $icon;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getFactionId()
	{
		return $this->factionId;
	}

	/**
	 * @param int $factionId
	 * @return achievement
	 */
	public function setFactionId($factionId)
	{
		$this->factionId = $factionId;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getGuildId()
	{
		return $this->guild_id;
	}

	/**
	 * @param string $guild_id
	 */
	public function setGuildId($guild_id)
	{
		$this->guild_id = $guild_id;
	}

	/**
	 * @return string
	 */
	public function getPlayerId()
	{
		return $this->player_id;
	}

	/**
	 * @param string $player_id
	 */
	public function setPlayerId($player_id)
	{
		$this->player_id = $player_id;
	}

	/**
	 * @return string
	 */
	public function getReward()
	{
		return $this->reward;
	}

	/**
	 * @param string $reward
	 */
	public function setReward($reward)
	{
		$this->reward = $reward;
	}

	/*****************************************************/

	/**
	 * @return array
	 */
	public function getRewardItems()
	{
		return $this->rewardItems;
	}

	/**
	 * @param array $rewardItems
	 * @return achievement
	 */
	public function setRewardItems($rewardItems)
	{
		$this->rewardItems = $rewardItems;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getCriteria()
	{
		return $this->criteria;
	}

	/**
	 * @param array $criteria
	 * @return achievement
	 */
	public function setCriteria($criteria)
	{
		$this->criteria = $criteria;
		return $this;
	}

	/**
	 * achievement constructor.
	 *
	 * Table names are injected via the service container.
	 * Use setGame() and setId() to configure game/achievement context.
	 *
	 * @param string $bb_achievement_track_table
	 * @param string $bb_achievement_table
	 * @param string $bb_achievement_rewards_table
	 * @param string $bb_criteria_track_table
	 * @param string $bb_achievement_criteria_table
	 * @param string $bb_relations_table
	 */
	public function __construct($bb_achievement_track_table, $bb_achievement_table,
		$bb_achievement_rewards_table, $bb_criteria_track_table, $bb_achievement_criteria_table,
		$bb_relations_table)
	{
		$this->bb_achievement_track_table = $bb_achievement_track_table;
		$this->bb_achievement_table = $bb_achievement_table;
		$this->bb_achievement_rewards_table = $bb_achievement_rewards_table;
		$this->bb_criteria_track_table = $bb_criteria_track_table;
		$this->bb_achievement_criteria_table = $bb_achievement_criteria_table;
		$this->bb_relations_table = $bb_relations_table;
	}

	/**
	 * Set the game context for this achievement instance.
	 *
	 * @param game $game
	 * @param int  $id
	 */
	public function setGame(game $game, $id = 0)
	{
		$this->game = $game;
		$this->game_id = $game->game_id;
		$this->id = $id;
	}

	/**
	 * get achievement (no track info)
	 * @return int
	 */
	public function get_achievement()
	{
		global $db;
		$i=0;

		$sql_array = array (
			'SELECT' => '
			a.id   AS achievement_id,
			a.game_id,
			a.title,
			a.points,
			a.description,
			a.icon,
			a.factionid,
			a.reward,
			ac.achievements_completed,
			r2.rel_value      AS rewards_item_id ,
			w.description     AS rewardsdescription,
			w.rewards_item_id AS rewards_item_id,
			w.itemlevel       AS itemlevel,
			w.quality         AS quality,
			r1.rel_value      AS criteria_id,
			c.description     AS criteriadescription,
			c.orderindex      AS criteriaorder,
			c.max             AS criteriamax,
			ct.criteria_quantity,
			ct.criteria_timestamp,
			ct.criteria_created ',
			'FROM' => array (
				$this->bb_achievement_table => 'a',
				$this->bb_achievement_track_table => 'ac',
			),
			'LEFT_JOIN' => array(
				array(
					'FROM'  => array($this->bb_relations_table => 'r2'),
					'ON'    => "  a.id = r2.att_value AND r2.attribute_id = 'ACH' AND r2.rel_attr_id = 'REW' " ,
				),
				array(
					'FROM'  => array($this->bb_achievement_rewards_table => 'w'),
					'ON'    => " w.rewards_item_id = r2.rel_value " ,
				),
				array(
					'FROM'  => array($this->bb_relations_table => 'r1'),
					'ON'    => " a.id = r1.att_value AND r1.attribute_id = 'ACH' AND r1.rel_attr_id = 'CRI' " ,
				),
				array(
					'FROM'  => array($this->bb_achievement_criteria_table => 'c'),
					'ON'    => " c.criteria_id = r1.rel_value " ,
				),
				array(
					'FROM'  => array($this->bb_criteria_track_table => 'ct'),
					'ON'    => " ct.criteria_id = c.criteria_id AND ( ct.guild_id = 1 OR ct.player_id = 0) " ,
				)),
			'WHERE' =>  '1=1 AND a.id = ' . (int) $this->id . " AND a.game_id = '". $this->game_id . "'" ,
		);

		$sql = $db->sql_build_query('SELECT', $sql_array);

		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$i=1;
			$this->title        = $row['title'];
			$this->points       = $row['points'];
			$this->description  = $row['description'];
			$this->icon         = $row['icon'];
			$this->factionId    = $row['factionid'];
			$this->reward       = $row['reward'];
			$this->criteria     = array( $row['criteria_id'], $row['criteria'], $row['criteriaorder'], $row['criteriamax']);
			$this->rewardItems  = array( $row['rewards_item_id'], $row['rewards'], $row['rewardorder'], $row['rewardmax']);
		}
		$db->sql_freeresult($result);
		return $i;
	}

	/**
	 * get tracked achievements from local database
	 *
	 * @param     $start
	 * @param     $guild_id
	 * @param int $player_id
	 * @return array
	 */
	public function get_tracked_achievements($start, $guild_id, $player_id = 0)
	{

		global $db;

		$sql_array = array (
			'SELECT' => '
			a.id   AS achievement_id,
			a.game_id,
			a.title,
			a.points,
			a.description,
			a.icon,
			a.factionid,
			a.reward,
			ac.achievements_completed,
			r2.rel_value      AS rewards_item_id ,
			w.description     AS rewardsdescription,
			w.rewards_item_id AS rewards_item_id,
			w.itemlevel       AS itemlevel,
			w.quality         AS quality,
			r1.rel_value      AS criteria_id,
			c.description     AS criteriadescription,
			c.orderindex      AS criteriaorder,
			c.max             AS criteriamax,
			ct.criteria_quantity,
			ct.criteria_timestamp,
			ct.criteria_created ',
			'FROM' => array (
				$this->bb_achievement_table => 'a',
				$this->bb_achievement_track_table => 'ac',
				),
			'LEFT_JOIN' => array(
				array(
					'FROM'  => array($this->bb_relations_table => 'r2'),
					'ON'    => "  a.id = r2.att_value AND r2.attribute_id = 'ACH' AND r2.rel_attr_id = 'REW' " ,
				),
				array(
					'FROM'  => array($this->bb_achievement_rewards_table => 'w'),
					'ON'    => " w.rewards_item_id = r2.rel_value " ,
				),
				array(
					'FROM'  => array($this->bb_relations_table => 'r1'),
					'ON'    => " a.id = r1.att_value AND r1.attribute_id = 'ACH' AND r1.rel_attr_id = 'CRI' " ,
				),
				array(
					'FROM'  => array($this->bb_achievement_criteria_table => 'c'),
					'ON'    => " c.criteria_id = r1.rel_value " ,
				),
				array(
					'FROM'  => array($this->bb_criteria_track_table => 'ct'),
					'ON'    => " ct.criteria_id = c.criteria_id AND ( ct.guild_id = 1 OR ct.player_id = 0) " ,
				)),
			'WHERE' =>  '1=1 AND (ac.guild_id = ' . $guild_id .' OR ac.player_id= ' . $player_id . ") AND a.game_id = '". $this->game_id . "'" ,
		);

		$sort_order = array(
			0 => array('a.id', 'a.id desc'),
			1 => array('a.title', 'a.title desc'),
			2 => array('a.description', 'a.description desc'),
			3 => array('a.points', 'a.points desc'),
			4 => array('ac.achievements_completed', 'ac.achievements_completed desc'),
		);

		$current_order   = $this->switch_order($sort_order);
		$sql_array['ORDER_BY']  = $current_order['sql'];
		$sql = $db->sql_build_query('SELECT', $sql_array);
		$result = $db->sql_query($sql);
		$dataset = $db->sql_fetchrowset($result);
		$achievcount = count($dataset);

		if ($start> 0)
		{
			$result = $db->sql_query_limit($sql, 15, $start);
			$dataset = $db->sql_fetchrowset($result);
		}
		$db->sql_freeresult($result);

		$achievements = array();

		foreach ($dataset as $row)
		{
			$achievements[] = array(
				'guild_id'                 => $guild_id,
				'player_id'                => $player_id,
				'game_id'                  => $this->game_id,
				'title'                    => $row['title'],
				'points'                   => $row['points'],
				'description'              => $row['description'],
				'icon'                     => $row['icon'],
				'factionId'                => $row['factionid'],
				'reward'                   => $row['reward'],
				'criteria'                 => array(
					'criteria_id' => $row['criteria_id'],
					'criteriadescription' =>	$row['criteriadescription'],
					'criteriaorder' =>	$row['criteriaorder'],
					'criteriamax' =>	$row['criteriamax'],
					'criteria_quantity' =>	$row['criteria_quantity'],
					'criteria_timestamp' =>	$row['criteria_timestamp'],
					'criteria_created' =>	$row['criteria_created']),
				'rewardItems'  => array(
					'rewards_item_id' =>	$row['rewards_item_id'],
					'rewardsdescription' =>	$row['rewardsdescription'],
					'itemlevel' =>	$row['itemlevel'],
					'quality'   => $row['quality']
				),
				'achievements_completed'   => $row['achievements_completed']
			);
		}

		return array($achievements, $current_order, $achievcount);

	}


	/**
	 * call API and set set achievment tracked for one guild
	 *
	 * @param \avathar\bbguild\model\player\guilds $Guild
	 * @param \avathar\bbguild\model\games\game    $game
	 */
	public function setAchievements(guilds $Guild, game $game)
	{
		global $db;

		$achievement = array();

		/**** achievement track *****/
		$sql = 'DELETE FROM ' . $this->bb_achievement_track_table . ' WHERE guild_id = ' . $Guild->guildid;
		$db->sql_query($sql);
		/**** criteria track *****/
		$sql = 'DELETE FROM ' . $this->bb_criteria_track_table . ' WHERE guild_id = ' . $Guild->guildid;
		$db->sql_query($sql);
		/**** achievement  *****/
		$sql = 'DELETE FROM ' . $this->bb_achievement_table . ' WHERE 1 <> 1 ';
		$db->sql_query($sql);
		/**** achievement rewards *****/
		$sql = 'DELETE FROM ' . $this->bb_achievement_rewards_table . ' WHERE 1 = 1 ';
		$db->sql_query($sql);
		/**** achievement criteria *****/
		$sql = 'DELETE FROM ' . $this->bb_achievement_criteria_table . ' WHERE  1 = 1 ';
		$db->sql_query($sql);
		/**** relations *****/
		$sql = 'DELETE FROM ' . $this->bb_relations_table . ' WHERE 1 = 1' ;
		$db->sql_query($sql);

		//call Guild API for achievements endpoint
		$data = (array) $Guild->Call_Guild_API(array('achievements'), $game);

		foreach ($data['achievements']['achievementsCompleted'] as $id => $achi)
		{
			$achievement[$id]['id'] = $achi;
		}
		foreach ($data['achievements']['achievementsCompletedTimestamp'] as $id => $achiTimeStamp)
		{
			$achievement[$id]['timestamp'] = $achiTimeStamp;
		}

		$sql_ary = array();

		foreach ($achievement as $id => $achi)
		{
			$sql_ary[] = array(
				'guild_id' => $Guild->guildid,
				'player_id' => 0,
				'achievement_id' => $achi['id'],
				'achievements_completed' => $achi['timestamp'],
			);
		}
		$db->sql_multi_insert($this->bb_achievement_track_table, $sql_ary);

		$criteria = array();
		foreach ($data['achievements']['criteria'] as $id => $criteria_id)
		{
			$criteria[$id]['criteria_id'] = $criteria_id;
			$criteria[$id]['criteriaQuantity'] = $data['achievements']['criteriaQuantity'][$id];
			$criteria[$id]['criteriaTimestamp'] = $data['achievements']['criteriaTimestamp'][$id];
			$criteria[$id]['criteriaCreated'] = $data['achievements']['criteriaCreated'][$id];
		}

		$sql_ary = array();
		foreach ($criteria as $id => $crit)
		{
			$sql_ary[] = array(
				'guild_id' => $Guild->guildid,
				'player_id' => 0,
				'criteria_id' => $crit['criteria_id'],
				'criteria_quantity' => $crit['criteriaQuantity'],
				'criteria_timestamp' => $crit['criteriaTimestamp'],
				'criteria_created' => $crit['criteriaCreated'],
			);
		}
		/**** loop tracked achievements that arent already in achievement table *****/
		$sql_array = array (
			'SELECT' => ' t.achievement_id ',
			'FROM' => array (
				$this->bb_achievement_track_table => 't',
			),
			'LEFT_JOIN' => array(
				array(
					'FROM'  => array($this->bb_achievement_table => 's'),
					'ON'    => ' s.id = t.achievement_id ',
				)),
			'WHERE' =>  ' t.guild_id = ' . $Guild->guildid . ' AND s.id is NULL ',
		);
		$sql = $db->sql_build_query('SELECT', $sql_array);
		$result = $db->sql_query($sql);

		$achievement_ids = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$achievement_ids[] = $row['achievement_id'];
		}
		$db->sql_freeresult($result);

		foreach ($achievement_ids as $id => $achievement_id)
		{
			$this->id = $achievement_id;
			$achievementdata = $this->Call_Achievement_API($Guild);
			if ($achievementdata)
			{
				$this->insert_achievement($achievementdata);
				$this->insert_rewardItems($achievementdata);
				$this->insert_criteria($achievementdata);
			}
		}
	}

	/**
	 * call achievement endpoint
	 *
	 * @param \avathar\bbguild\model\player\guilds $Guild
	 * @return array|bool
	 */
	private function Call_Achievement_API(guilds $Guild)
	{
		global $cache;

		// game and guild have to be armory enabled...
		if (! $this->game->getArmoryEnabled() || !$Guild->isArmoryEnabled() )
		{
			return false;
		}

		// new instance of battlenet class
		$api  = new battlenet('achievement',$this->game->getRegion(), $this->game->getApikey(),
			$this->game->get_apilocale(), $this->game->get_privkey(), $this->ext_path, $cache);
		$data = $api->achievement->getAchievementDetail($this->id);
		$data = $data['response'];
		unset($api);
		if (!isset($data))
		{
			return false;
		}

		//if we get error code
		if (isset($data['code']))
		{
			return false;
		}

		if (isset($data['status']))
		{
			return false;
		}

		return $data;
	}

	/**
	 * Insert an achievement into local database
	 *
	 * @param array $data
	 */
	private function insert_achievement(array $data)
	{
		global $db;
		$this->id = isset($data['id']) ? $data['id'] : 0;
		$this->game_id = $this->game->game_id;
		$this->title = isset($data['title']) ? $data['title']: '';
		$this->points = isset($data['points']) ? $data['points']: 0;
		$this->description = isset($data['description']) ? $data['description']: '';
		$this->icon = isset($data['icon']) ? $data['icon']: '';
		$this->factionId = isset($data['factionId']) ? $data['factionId']: '';
		$this->reward = isset($data['reward']) ? $data['reward']: '';

		$sql_ary = array(
			'id'            => $this->id,
			'game_id'       => $this->game_id,
			'title'         => $this->title,
			'points'        => $this->points,
			'description'   => $this->description,
			'factionid'     => $this->factionId,
			'icon'          => $this->icon,
			'reward'        => $this->reward
		);

		$db->sql_query('INSERT INTO ' . $this->bb_achievement_table . ' ' . $db->sql_build_array('INSERT', $sql_ary));
	}

	/**
	 * insert achievement rewardItems array into database
	 *
	 * @param array $data
	 */
	private function insert_rewardItems(array $data)
	{
		$sql_ary1 = array();
		$sql_ary2 = array();
		global $db;
		$this->rewardItems = is_array($data['rewardItems']) ? $data['rewardItems'] : '' ;
		foreach ($this->rewardItems as $id => $rewardItems)
		{
			$sql_ary1[] = array(
				'rewards_item_id'   => $rewardItems['id'],
				'description'       => $rewardItems['name'],
				'itemlevel'         => $rewardItems['itemLevel'],
				'icon'              => $rewardItems['icon'],
				'quality'           => $rewardItems['quality'],
			);
			$db->sql_query('DELETE FROM ' . $this->bb_achievement_rewards_table . ' WHERE rewards_item_id =  ' . $rewardItems['id']);

			$sql_ary2[] = array(
				'attribute_id'  => 'ACH',
				'rel_attr_id'   => 'REW',
				'att_value'     => $data['id'],
				'rel_value'     => $rewardItems['id'],
			);

			$db->sql_query('DELETE FROM ' . $this->bb_relations_table . " WHERE attribute_id =  'ACH' and rel_attr_id = 'REW' and att_value= '" . $data['id'] . "' and rel_value = '".  $rewardItems['id'] ."' " );
		}

		if (count($sql_ary1) > 0)
		{
			$db->sql_multi_insert($this->bb_achievement_rewards_table, $sql_ary1);
		}

		if (count($sql_ary2) > 0)
		{
			$db->sql_multi_insert($this->bb_relations_table, $sql_ary2);
		}

	}

	/**
	 * insert achievement criteria array into database
	 *
	 * @param array $data
	 */
	private function insert_criteria(array $data)
	{
		global $db;
		$sql_ary3 = array();
		$sql_ary4 = array();
		$this->criteria = is_array($data['criteria']) ? $data['criteria'] : '' ;
		foreach ($this->criteria as $id => $criterium)
		{
			$sql_ary3[] = array(
				'criteria_id'   => $criterium['id'],
				'description'   => $criterium['description'],
				'orderindex'    => $criterium['orderIndex'],
				'max'           => $criterium['max'],
			);

			$db->sql_query('DELETE FROM ' . $this->bb_achievement_criteria_table . ' WHERE criteria_id =  ' . $criterium['id']);

			$sql_ary4[] = array(
				'attribute_id'  => 'ACH',
				'rel_attr_id'   => 'CRI',
				'att_value'     => $data['id'],
				'rel_value'     => $criterium['id'],
			);

			$db->sql_query('DELETE FROM ' . $this->bb_relations_table . " WHERE attribute_id =  'ACH' and rel_attr_id = 'CRI' and att_value= '" . $data['id'] . "' and rel_value = '".  $criterium['id'] ."' " );
		}

		if (count($sql_ary3) > 0)
		{
			$db->sql_multi_insert($this->bb_achievement_criteria_table, $sql_ary3);
		}

		if (count($sql_ary4) > 0)
		{
			$db->sql_multi_insert($this->bb_relations_table, $sql_ary4);
		}
	}
}
