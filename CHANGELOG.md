# Changelog

## 2.0.0-b3 28/04/2026
  - [NEW] Specialization catalog (#26 / bbguild#331) — 39 specs across 13 Battle.net classes
    - `wow_provider` implements `specialization_provider_interface`; static `spec_catalog()` is the single source of truth
    - `wow_installer::install_specs()` seeds specs and translation rows on fresh install
    - Backfill migrations for existing installs (`seed_specializations`, `seed_spec_translations`)
  - [NEW] Specialization icons — 39 PNGs in `images/spec_icons/` sourced from Battle.net's `playable-specialization/{id}/media` endpoint
  - [NEW] Specialization translations for de / fr / it / es_x_tu, sourced from Battle.net's official `name` object. Polish and Dutch fall back to canonical English (Blizzard doesn't officially translate WoW into those locales).
  - [CHG] Soft-requires `avathar/bbguild >= 2.0.0-b4` for the specialization plumbing
  - [CHG] Repo + composer name dropped to no-separator form (`avatharbe/bbguildwow`, `avathar/bbguildwow`); PHP namespace is `avathar\bbguildwow`. DB-stored config keys (`bbguild_wow_version`, `bbguild_wow_oauth_token_*`) and cache keys preserved with the original underscore form.
  - [CHG] Unit-test workflow now checks out bbguild core alongside bbguildwow so tests that instantiate plugin classes (which implement core interfaces) can resolve them on CI

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
