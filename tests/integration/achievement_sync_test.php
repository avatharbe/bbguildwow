<?php
/**
 * @package bbGuild WoW Extension
 * @copyright (c) 2026 avathar.be
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace avathar\bbguildwow\tests\integration;

use avathar\bbguild\model\games\game;
use avathar\bbguild\model\player\guilds;
use avathar\bbguildwow\api\battlenet;
use avathar\bbguildwow\api\battlenet_achievement;
use avathar\bbguildwow\api\battlenet_achievement_category;
use avathar\bbguildwow\api\battlenet_guild;
use avathar\bbguildwow\model\achievement;

class mock_battlenet_guild_for_achievements extends battlenet_guild
{
	public function __construct(\phpbb\cache\service $cache, string $base_url, string $region = 'us', int $cache_ttl = 3600)
	{
		parent::__construct($cache, $region, $cache_ttl);
		$this->api_url = array($region => $base_url);
		$this->token_url = array($region => $base_url . 'token');
		$this->apikey = 'test_client_id';
		$this->privkey = 'test_client_secret';
		$this->locale = 'en_US';
	}
}

class mock_battlenet_achievement_for_achievements extends battlenet_achievement
{
	public function __construct(\phpbb\cache\service $cache, string $base_url, string $region = 'us', int $cache_ttl = 3600)
	{
		parent::__construct($cache, $region, $cache_ttl);
		$this->api_url = array($region => $base_url);
		$this->token_url = array($region => $base_url . 'token');
		$this->apikey = 'test_client_id';
		$this->privkey = 'test_client_secret';
		$this->locale = 'en_US';
	}
}

class mock_battlenet_achievement_category_for_achievements extends battlenet_achievement_category
{
	public function __construct(\phpbb\cache\service $cache, string $base_url, string $region = 'us', int $cache_ttl = 3600)
	{
		parent::__construct($cache, $region, $cache_ttl);
		$this->api_url = array($region => $base_url);
		$this->token_url = array($region => $base_url . 'token');
		$this->apikey = 'test_client_id';
		$this->privkey = 'test_client_secret';
		$this->locale = 'en_US';
	}
}

/**
 * battlenet facade with the three resource slots this test needs pre-set to
 * their mock counterparts — achievement's create_battlenet() override
 * returns one of these regardless of which $api type was asked for, since
 * a single test only ever needs one resource live per call.
 */
class mock_battlenet_facade_for_achievements extends battlenet
{
	public function __construct($guild = null, $achievement_resource = null, $achievement_category = null)
	{
		$this->guild = $guild;
		$this->achievement = $achievement_resource;
		$this->achievement_category = $achievement_category;
	}
}

class achievement_with_mock_api extends achievement
{
	public $mock_guild;
	public $mock_achievement;
	public $mock_achievement_category;

	protected function create_battlenet(string $api, string $region, string $apikey, string $locale, string $privkey, string $ext_path = '', int $cache_ttl = 3600, string $edition = 'retail'): battlenet
	{
		return new mock_battlenet_facade_for_achievements($this->mock_guild, $this->mock_achievement, $this->mock_achievement_category);
	}
}

/**
 * @group integration
 */
class achievement_sync_test extends mock_battlenet_test_case
{
	private function get_table_prefix(): string
	{
		return self::$config['table_prefix'];
	}

	static protected function setup_extensions()
	{
		return array('avathar/bbguild', 'avathar/bbguildwow');
	}

	/** @var int */
	private $guild_row_id;

	protected function setUp(): void
	{
		parent::setUp();

		$db = $this->get_db();

		// bbguildwow's own migration seeds a 'wow' row into bb_games as part
		// of enabling the extension (setup_extensions() above triggers that
		// install) — an INSERT here would collide with bb_games.game_id's
		// UNIQUE index. UPDATE the auto-seeded row instead.
		$db->sql_query('UPDATE ' . $this->get_table_prefix() . 'bb_games SET ' . $db->sql_build_array('UPDATE', array(
			'region'         => 'us',
			'armory_enabled' => 1,
			'apikey'         => 'test_client_id',
			'apilocale'      => 'en_US',
			'privkey'        => 'test_client_secret',
		)) . " WHERE game_id = 'wow'");

		// bb_guild.id is NOT an autoincrement column (unlike bb_games/bb_players)
		// — guilds::create_guild() itself allocates ids via MAX(id)+1 (see
		// model/player/guilds.php), so mirror that here rather than relying
		// on sql_nextid(), which would return the DEFAULT '0' every time and
		// collide across this file's two test methods.
		$max_id_result = $db->sql_query('SELECT MAX(id) AS id FROM ' . $this->get_table_prefix() . 'bb_guild');
		$max_id_row = $db->sql_fetchrow($max_id_result);
		$db->sql_freeresult($max_id_result);
		$this->guild_row_id = (int) $max_id_row['id'] + 1;

		$db->sql_query('INSERT INTO ' . $this->get_table_prefix() . 'bb_guild ' . $db->sql_build_array('INSERT', array(
			'id'             => $this->guild_row_id,
			'name'           => 'My Guild',
			'realm'          => 'Area 52',
			'region'         => 'us',
			'game_id'        => 'wow',
			'game_edition'   => 'retail',
			'armory_enabled' => 1,
		)));

		// game::__construct() and battlenet_guild::getGuild()/getAchievements()
		// (on an empty-slug path only) read $user->lang[...] directly. Neither
		// a real \phpbb\user nor global $user is bootstrapped in-process here
		// (this test drives production code directly, not through the board
		// under test's own HTTP-handled request). __get() is a normal,
		// overridable method on \phpbb\user (not true PHP magic dispatch —
		// see phpbb/user.php), so a disableOriginalConstructor() mock with
		// __get() stubbed satisfies the \phpbb\user type hint without
		// needing a real language-loading stack.
		$GLOBALS['user'] = $this->make_stub_user();
	}

	protected function tearDown(): void
	{
		unset($GLOBALS['user']);
		parent::tearDown();
	}

	private function make_stub_user(): \phpbb\user
	{
		$lang = array(
			// Read unconditionally by game::__construct().
			'REGIONEU' => 'Europe',
			'REGIONKR' => 'Korea',
			'REGIONSEA' => 'SEA',
			'REGIONTW' => 'Taiwan',
			'REGIONUS' => 'United States',
			// Read unconditionally by guilds::get_guild() (called from its
			// constructor whenever $guild_id > 0, which load_guild() always
			// passes).
			'CLOSED' => 'Closed',
			'OPEN'   => 'Open',
		);

		$user = $this->getMockBuilder(\phpbb\user::class)
			->disableOriginalConstructor()
			->getMock();
		$user->method('__get')->willReturnCallback(function ($name) use ($lang) {
			return $name === 'lang' ? $lang : null;
		});

		return $user;
	}

	private function load_game(): game
	{
		$g = new game(
			$this->get_db(),
			$this->get_cache_driver(),
			new \phpbb\config\config(array()),
			$GLOBALS['user'],
			$this->get_extension_manager(),
			$this->get_table_prefix() . 'bb_classes',
			$this->get_table_prefix() . 'bb_races',
			$this->get_table_prefix() . 'bb_language',
			$this->get_table_prefix() . 'bb_factions',
			$this->get_table_prefix() . 'bb_games'
		);
		$g->game_id = 'wow';
		$g->get_game();

		return $g;
	}

	private function load_guild(): guilds
	{
		$log = new \avathar\bbguild\model\admin\log(
			$this->get_table_prefix() . 'bb_logs',
			$this->get_db(),
			$GLOBALS['user']
		);

		return new guilds(
			$this->get_db(),
			$GLOBALS['user'],
			new \phpbb\config\config(array()),
			$this->get_cache_driver(),
			$log,
			$this->get_table_prefix() . 'bb_players',
			$this->get_table_prefix() . 'bb_ranks',
			$this->get_table_prefix() . 'bb_classes',
			$this->get_table_prefix() . 'bb_races',
			$this->get_table_prefix() . 'bb_language',
			$this->get_table_prefix() . 'bb_guild',
			$this->get_table_prefix() . 'bb_factions',
			$this->guild_row_id
		);
	}

	/**
	 * achievement::__construct() takes \avathar\bbguild\model\admin\util,
	 * but neither syncCategories() nor setAchievements() ever reads
	 * $this->util (only get_tracked_achievements() does, which this test
	 * doesn't exercise) — a never-invoked stand-in is sufficient.
	 */
	private function make_inert_util(): \avathar\bbguild\model\admin\util
	{
		return new \avathar\bbguild\model\admin\util(
			$this->getMockBuilder(\phpbb\request\request::class)
				->disableOriginalConstructor()
				->getMock()
		);
	}

	/** @return achievement_with_mock_api */
	private function load_achievement()
	{
		return new achievement_with_mock_api(
			$this->get_db(),
			$this->make_stateful_cache(),
			$this->make_inert_util(),
			$this->get_table_prefix() . 'bb_achievement_track',
			$this->get_table_prefix() . 'bb_achievement',
			$this->get_table_prefix() . 'bb_achievement_rewards',
			$this->get_table_prefix() . 'bb_criteria_track',
			$this->get_table_prefix() . 'bb_achievement_criteria',
			$this->get_table_prefix() . 'bb_relations_table',
			$this->get_table_prefix() . 'bb_guild_wow',
			$this->get_table_prefix() . 'bb_achievement_category'
		);
	}

	public function test_sync_categories_inserts_root_and_child_hierarchy(): void
	{
		$cache = $this->make_stateful_cache();
		$this->configure_mock_routes(array(
			'/token' => array(array('status' => 200, 'body' => array('access_token' => 'tok', 'expires_in' => 3600))),
			'/data/wow/achievement-category/index' => array(
				array('status' => 200, 'body' => array('root_categories' => array(
					array('id' => 1, 'name' => 'General', 'subcategories' => array(
						array('id' => 2, 'name' => 'Leveling'),
					)),
				))),
			),
			'/data/wow/achievement-category/2' => array(
				array('status' => 200, 'body' => array('achievements' => array())),
			),
		));

		$model = $this->load_achievement();
		$model->mock_achievement_category = new mock_battlenet_achievement_category_for_achievements($cache, self::base_url(), 'us');
		$model->setEdition('retail');

		$result = $model->syncCategories($this->load_game());

		$this->assertTrue($result['success']);

		$db = $this->get_db();
		$table = $this->get_table_prefix() . 'bb_achievement_category';
		$sql_result = $db->sql_query("SELECT id, parent_id, name FROM $table WHERE game_id = 'wow' ORDER BY id");
		$rows = array();
		while ($row = $db->sql_fetchrow($sql_result))
		{
			$rows[] = array('id' => (int) $row['id'], 'parent_id' => (int) $row['parent_id'], 'name' => $row['name']);
		}
		$db->sql_freeresult($sql_result);

		$this->assertSame(array(
			array('id' => 1, 'parent_id' => 0, 'name' => 'General'),
			array('id' => 2, 'parent_id' => 1, 'name' => 'Leveling'),
		), $rows);
	}

	public function test_set_achievements_then_sync_categories_backfills_category_id(): void
	{
		$cache = $this->make_stateful_cache();
		$achievement_table = $this->get_table_prefix() . 'bb_achievement';
		$track_table = $this->get_table_prefix() . 'bb_achievement_track';

		// setAchievements() first — stubs the achievement with category_id=0,
		// exactly as the real workflow does before a user has run category sync.
		$this->configure_mock_routes(array(
			'/token' => array(array('status' => 200, 'body' => array('access_token' => 'tok', 'expires_in' => 3600))),
			'/data/wow/guild/area-52/my-guild' => array(array('status' => 200, 'body' => array('name' => 'My Guild'))),
			'/data/wow/guild/area-52/my-guild/achievements' => array(
				array('status' => 200, 'body' => array(
					'total_points' => 500,
					'achievements' => array(
						array('achievement' => array('id' => 42, 'name' => 'Level 10'), 'completed_timestamp' => 1700000000),
					),
				)),
			),
			'/data/wow/achievement/42' => array(
				array('status' => 200, 'body' => array('id' => 42, 'name' => 'Level 10', 'points' => 10, 'description' => 'Reach level 10.')),
			),
		));

		$model = $this->load_achievement();
		$model->mock_guild = new mock_battlenet_guild_for_achievements($cache, self::base_url(), 'us');
		$model->mock_achievement = new mock_battlenet_achievement_for_achievements($cache, self::base_url(), 'us');
		$model->setGame($this->load_game());
		$model->setEdition('retail');

		$guild = $this->load_guild();

		$result = $model->setAchievements($guild, $this->load_game());
		$this->assertTrue($result['success']);

		$db = $this->get_db();
		$sql_result = $db->sql_query("SELECT title, points, category_id FROM $achievement_table WHERE id = 42");
		$row = $db->sql_fetchrow($sql_result);
		$db->sql_freeresult($sql_result);
		$this->assertSame('Level 10', $row['title']);
		$this->assertSame(10, (int) $row['points']);
		$this->assertSame(0, (int) $row['category_id']);

		// Now sync categories — the leaf category detail response includes
		// achievement 42, which should get its category_id backfilled.
		$this->configure_mock_routes(array(
			'/token' => array(array('status' => 200, 'body' => array('access_token' => 'tok', 'expires_in' => 3600))),
			'/data/wow/achievement-category/index' => array(
				array('status' => 200, 'body' => array('root_categories' => array(array('id' => 5, 'name' => 'Leveling')))),
			),
			'/data/wow/achievement-category/5' => array(
				array('status' => 200, 'body' => array('achievements' => array(array('id' => 42, 'name' => 'Level 10')))),
			),
		));
		$model->mock_achievement_category = new mock_battlenet_achievement_category_for_achievements($cache, self::base_url(), 'us');
		$cat_result = $model->syncCategories($this->load_game());
		$this->assertTrue($cat_result['success']);

		$sql_result = $db->sql_query("SELECT category_id FROM $achievement_table WHERE id = 42");
		$this->assertSame(5, (int) $db->sql_fetchfield('category_id'));
		$db->sql_freeresult($sql_result);

		// Re-run setAchievements(): tracking rows must not duplicate.
		$model2 = $this->load_achievement();
		$model2->mock_guild = new mock_battlenet_guild_for_achievements($cache, self::base_url(), 'us');
		$model2->mock_achievement = new mock_battlenet_achievement_for_achievements($cache, self::base_url(), 'us');
		$model2->setGame($this->load_game());
		$model2->setEdition('retail');
		$model2->setAchievements($guild, $this->load_game());

		$sql_result = $db->sql_query("SELECT COUNT(*) AS cnt FROM $track_table WHERE achievement_id = 42");
		$this->assertSame(1, (int) $db->sql_fetchfield('cnt'), 're-running must not duplicate the tracking row');
		$db->sql_freeresult($sql_result);
	}
}
