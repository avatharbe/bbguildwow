<?php
/**
 * bbGuild WoW Extension — flag guild-only achievement categories
 *
 * Battle.net's achievement-category tree has two disjoint namespaces:
 * character-scope (`categories`/`root_categories` in the Category Index)
 * and guild-scope (`guild_categories`/`character_categories`, is_guild_category
 * true on the Category Detail response). The player-detail achievements tab
 * (bbguildwow#44) only mirrors the character-scope tree, matching Blizzard's
 * own character armory page, which never shows a "Guild" branch — so the
 * category sync needs a way to tell the two namespaces apart without relying
 * on id ranges.
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\migrations\v210b1;

class add_achievement_category_guild_flag extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\avathar\bbguildwow\migrations\v210b1\add_news_source'];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'bb_achievement_category' => [
					'is_guild_category' => ['BOOL', 0],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_columns' => [
				$this->table_prefix . 'bb_achievement_category' => ['is_guild_category'],
			],
		];
	}
}
