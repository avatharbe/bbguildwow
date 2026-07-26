-- ============================================================================
-- bbGuild WoW Plugin - Database Cleanup Script
-- ============================================================================--
-- WARNING: This script DROPS ALL WoW plugin tables and removes all
--          World of Warcraft game data from the database.
--          It is intended for development/testing purposes only.
--          Back up your database before running this script!
--
-- Assumptions:
--   - phpBB table prefix is "phpbb_"
--   - bbGuild core tables exist
--
-- After running this script, disable and re-enable the extension in
-- phpBB ACP to re-run migrations and restore default data.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. Drop WoW plugin tables (child tables first)
-- ----------------------------------------------------------------------------

DROP TABLE IF EXISTS phpbb_bb_criteria_track;
DROP TABLE IF EXISTS phpbb_bb_achievement_track;
DROP TABLE IF EXISTS phpbb_bb_relations_table;
DROP TABLE IF EXISTS phpbb_bb_achievement_rewards;
DROP TABLE IF EXISTS phpbb_bb_achievement_criteria;
DROP TABLE IF EXISTS phpbb_bb_achievement;
DROP TABLE IF EXISTS phpbb_bb_guild_wow;

-- ----------------------------------------------------------------------------
-- 2. Remove WoW game data from core tables
-- ----------------------------------------------------------------------------

DELETE FROM phpbb_bb_language WHERE game_id = 'wow';
DELETE FROM phpbb_bb_classes WHERE game_id = 'wow';
DELETE FROM phpbb_bb_races WHERE game_id = 'wow';
DELETE FROM phpbb_bb_factions WHERE game_id = 'wow';
DELETE FROM phpbb_bb_gameroles WHERE game_id = 'wow';
DELETE FROM phpbb_bb_players WHERE player_game_id = 'wow';
DELETE FROM phpbb_bb_games WHERE game_id = 'wow';

-- ----------------------------------------------------------------------------
-- 3. phpBB config entries
-- ----------------------------------------------------------------------------

DELETE FROM phpbb_config WHERE config_name = 'bbguild_show_achiev';

-- ----------------------------------------------------------------------------
-- 4. phpBB ACP modules
-- ----------------------------------------------------------------------------

DELETE FROM phpbb_modules WHERE module_basename LIKE '%bbguild_wow%';

-- ----------------------------------------------------------------------------
-- 5. phpBB extension registration
-- ----------------------------------------------------------------------------

DELETE FROM phpbb_ext WHERE ext_name = 'avathar/bbguild_wow';

-- ----------------------------------------------------------------------------
-- 6. phpBB migration tracking
-- ----------------------------------------------------------------------------

DELETE FROM phpbb_migrations WHERE migration_name LIKE '%avathar\\bbguild_wow%';

-- ============================================================================
-- Done. Now purge the phpBB cache and re-enable the extension from ACP.
-- ============================================================================
