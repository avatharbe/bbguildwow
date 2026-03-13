# Changelog

## 2.0.0-b1 14/03/2026
  - [NEW] 3-level achievement browser: category cards with SVG progress rings, AJAX achievement list, detail modal (#13)
  - [NEW] `bb_achievement_category` table — syncs Blizzard achievement categories (root + child)
  - [NEW] `category_id` column on `bb_achievement` for category mapping
  - [NEW] Battle.net Achievement Category API resource (`achievement-category` index + detail)
  - [NEW] AJAX controller with 3 JSON endpoints: categories, achievement_list, achievement_detail
  - [NEW] "Sync Categories" button in ACP achievement listing
  - [NEW] Language keys for achievement browser (EN, DE, FR, IT)
  - [CHG] Portal achievements module now shows category card grid instead of flat list

## 2.0.0-a1 02/03/2026
  - [NEW] Initial release as standalone phpBB extension
  - [NEW] Extracted from bbGuild core as part of the game plugin architecture
  - [NEW] Implements `game_provider_interface` — registers WoW with bbGuild via tagged services
  - [NEW] `wow_installer` extends `abstract_game_install` with clean array-based table names
  - [NEW] `wow_api` implements `game_api_interface` wrapping Battle.net SDK
  - [NEW] `wow_provider` supplies game metadata (regions, locales, URLs)
  - [NEW] Battle.net API classes copied to own namespace (`avathar\bbguild_wow\api`)
  - [FIX] `battlenet_resource` now properly extends `curl` base class
  - [CHG] All 6 Battle.net API classes migrated from `avathar\bbguild\model\api` namespace
  - [CHG] Installer uses `$this->table()` helper instead of direct property access
  - [CHG] `has_api_support()` returns true, so `armory_enabled` is set correctly on install
  - [NOTE] Battle.net API uses legacy endpoints (`api.battle.net`) with HMAC auth — OAuth 2.0 migration planned
  - [NOTE] Images remain in bbGuild core for now — will be moved in a future release
