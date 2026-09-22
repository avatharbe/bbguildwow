<?php
/**
 * bbGuild WoW Extension — widen bb_news.news_source_key
 *
 * Guild activity source keys are built as a prefix, guild id, timestamp,
 * Battle.net activity type, and an md5 hash (#387) — long activity type
 * strings such as CHARACTER_ACHIEVEMENT push that past the original
 * VARCHAR(64) set in release_2_1_0, causing "Data too long for column
 * 'news_source_key'" on sync. 191 stays index-prefix-safe under utf8mb4
 * (191 * 4 = 764 bytes, under the classic 767-byte limit) with generous
 * headroom over any realistic activity type string.
 *
 * @package   avathar\bbguildwow
 * @copyright 2026 avathar.be
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 */

namespace avathar\bbguildwow\migrations\v211;

class widen_news_source_key extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return [
			'\avathar\bbguildwow\migrations\v210\release_2_1_0',
		];
	}

	public function update_schema()
	{
		return [
			'change_columns' => [
				$this->table_prefix . 'bb_news' => [
					'news_source_key' => ['VCHAR:191', ''],
				],
			],
		];
	}

	public function revert_schema()
	{
		return [
			'change_columns' => [
				$this->table_prefix . 'bb_news' => [
					'news_source_key' => ['VCHAR:64', ''],
				],
			],
		];
	}
}
