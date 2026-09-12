<?php
/**
 * bbGuild WoW Extension — API-sourced guild activity feed entries
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\migrations\v210b1;

class add_news_source extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return [
			'\avathar\bbguildwow\migrations\v210b1\add_guild_sync_config',
			'\avathar\bbguild\migrations\v200b3\release_2_0_0_b3',
		];
	}

	public function update_schema()
	{
		return [
			'add_columns' => [
				$this->table_prefix . 'bb_news' => [
					'news_source'     => ['VCHAR:10', 'manual'],
					'news_source_key' => ['VCHAR:64', ''],
				],
			],
			'add_index' => [
				$this->table_prefix . 'bb_news' => [
					'news_source_key' => ['news_source_key'],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'drop_keys' => [
				$this->table_prefix . 'bb_news' => ['news_source_key'],
			],
			'drop_columns' => [
				$this->table_prefix . 'bb_news' => ['news_source', 'news_source_key'],
			],
		];
	}
}
