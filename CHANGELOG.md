# Changelog

## 2.0.0-b2 15/03/2026
  - [NEW] WoW Classic support (#15) — edition-aware API namespaces for Retail, Classic Era, Classic Progression, and Classic Anniversary
  - [NEW] AJAX-batched API sync with separate roster and achievement sync buttons in guild ACP
  - [NEW] API sync logging with per-player error tracking
  - [FIX] Unicode character names in Battle.net API calls
  - [FIX] Infinite retry loop for 404 characters during spec/portrait sync
  - [CHG] Guild emblems stored in phpBB `files/` directory instead of extension directory
  - [CHG] WoW class max level updated from 80 to 90
  - [CHG] Squashed migrations into single `v200b2` release migration

## 2.0.0-b1 14/03/2026
  - [NEW] 3-level achievement browser: category cards with SVG progress rings, AJAX achievement list, and detail modal (#13)
  - [NEW] Achievement category sync from Battle.net API
  - [NEW] Guild news portal module — activity feed with recent loots and achievement completions

## 2.0.0-a1 02/03/2026
  - [NEW] Initial release as standalone phpBB extension, extracted from bbGuild core
  - [NEW] WoW game provider, installer, and API integration via tagged services
  - [NEW] Battle.net OAuth 2.0 API — guild roster sync, character profiles, portraits, and armory links
  - [NEW] ACP modules for achievement management and Battle.net API credentials
  - [NEW] 14 playable classes, 15 races, 2 factions with multilingual support (EN, DE, FR, IT, ES, NL, PL)
