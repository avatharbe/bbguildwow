# bbGuild WoW - Extension Analysis

## Overview

**bbguildwow** is the World of Warcraft game plugin for **bbGuild** (phpBB 3.3+ guild management). It's the plugin among the bbGuild family that goes beyond static game data — it talks to Blizzard's Battle.net API directly for guild roster sync, character profiles/equipment/portraits, achievements, and (as of #10) the guild activity feed.

- **Author:** Andreas Vandenberghe (Sajaki)
- **Version:** 2.1.0 (requires bbGuild core >= 2.1.0 — see `ext::BBGUILDWOW_VERSION`, `ext::MIN_BBGUILD_VERSION`)
- **License:** GPL-2.0-only
- **Repository:** https://github.com/avatharbe/bbguildwow

## Project Status

bbGuild core hit **2.1.0 stable** 2026-09-22. bbguildwow's 2.1.0 work (milestone: guild page overhaul, tracking [bbguild#303](https://github.com/avatharbe/bbguild/issues/303)) shipped alongside it — see `contrib/CHANGELOG.md`'s `2.1.0` section for the full list, and [bbguild/CLAUDE.md](https://github.com/avatharbe/bbguild/blob/main/CLAUDE.md)'s roadmap section for the full family-wide 2.1.0/2.2.0/2.3.0 plan.

Recently shipped (2026-09-12):
- **#361/#362** — bbGuild core's per-character `character_sync` cron + `sync/character_sync_handler.php` implementing its `character_sync_interface`, delegating to `wow_api::sync_character()`.
- **#11** — Scheduled guild roster sync via phpBB cron (`cron/task/sync_guild.php`).
- **#10** — Guild activity feed sync (Battle.net Guild Activity API → `bb_news`).
- **#363** — Gear tooltips via bbTips, with captured enchant/gem/bonus IDs.

## Architecture

See **`contrib/ARCHITECTURE.md`** for the full game-plugin contract (how bbGuild core discovers this plugin via tagged services) and **`contrib/BATTLENET_API.md`** for Battle.net API details — note the latter's core Guild/Character/Realm/Achievement sections describe the **older Community API shape** (fields=, `side: 0/1`) and haven't been reconciled with the actual OAuth 2.0 / Game Data API implementation in `api/battlenet_resource.php`; only the Guild Activity section added alongside this file reflects the current API generation. Worth a full pass if anyone relies on that doc for the older endpoints.

### Directory Structure

```
bbguildwow/
├── acp/                 # ACP modules: Battle.net API dashboard, achievement admin
├── api/                 # Battle.net API SDK (battlenet_resource base + guild/character/realm/achievement resources)
├── controller/          # AJAX sync controllers (roster/specs/portraits/equipment, achievements) + asset serving
├── cron/task/           # Scheduled sync: sync_guild.php (roster + activity feed, #11/#10)
├── event/               # phpBB event listener (player-detail display, config page, etc.)
├── game/                # wow_provider (game_provider_interface), wow_installer, wow_api (game_api_interface)
├── migrations/          # v200/release_2_0_0.php + v210/release_2_1_0.php (squashed, #383) — each writes its milestone's full end state directly
├── model/                # Achievement model
├── portal/modules/      # Portal blocks: achievements, guild_news (activity feed display)
├── styles/               # Templates, CSS
├── sync/                 # character_sync_handler.php — bbguild core's per-character cron contract (#362)
├── language/             # en, fr, de, it, nl, es_x_tu, pl
├── contrib/              # ARCHITECTURE.md, BATTLENET_API.md, FAQ.md, INSTALL.md, CHANGELOG.md — published as a docs site
├── docs/                 # docs/superpowers/ only — internal design-history plans/specs, not user-facing
└── tests/                # api/, cron/, game/, config/, system/, integration/ (functional, needs real DB), functional/
```

### Database Tables

Own tables (`bb_guild_wow`, achievement tables `bb_achievement*`, `bb_criteria_track`, `bb_relations_table`, `bb_player_equipment`, `bb_player_item_stat`) plus columns added to bbGuild core's shared tables via cross-extension `depends_on` migrations — notably `bb_news.news_source`/`news_source_key` (#10) for distinguishing API-sourced activity-feed entries from manual posts and deduplicating repeated cron fetches.

### Cron

`cron\task\sync_guild` (config-gated, `bbguild_wow_sync_enabled`/`_sync_interval`, ACP toggle on the Battle.net API page) syncs every WoW guild's roster and activity feed on a schedule, independently of each other and independently per guild (one guild's/one concern's failure doesn't block the rest). Character-profile refresh (ilvl/spec) is deliberately **not** part of this cron — that's bbGuild core's separate per-character `character_sync` cron via `sync/character_sync_handler.php`, to avoid double-syncing the same data on two schedules.

## Requirements

- phpBB >= 3.3.0
- PHP >= 8.1.0
- PHP cURL extension
- bbGuild core (`avathar/bbguild`) >= 2.0.0, installed and enabled

## Testing

Unit-style tests (mocked phpBB classes) run via the shared local harness at `~/.local/share/phpbb-ext-test-harness` — point its `phpunit.xml` at this extension's `tests/` dir (excluding `tests/integration` and `tests/functional`, which need a real DB / full phpBB checkout — CI only). As of 2026-09-12: 168 unit tests passing.

## Completed Fixes (recent)

See `contrib/CHANGELOG.md` for the full history. Highlights from the 2.1.0 line:
- **#10** — Guild activity feed sync; dedup via a structural hash of the raw activity entry (not the derived display text), so it's correct independent of whether the Battle.net response's exact type-string/field mapping is fully understood.
- **#11** — Scheduled roster sync cron, reusing the existing AJAX sync path's `wow_api` methods rather than duplicating orchestration logic.
- **#35** — Roster sync never deactivated characters who left the guild (`player_status` only ever set on insert).
- **#37** — Hardcoded English strings across `wow_api`/`achievement`/AJAX controllers replaced with phpBB language keys + `LANG_FALLBACK` for unit-test callers that construct classes directly (no DI container).
- **#32** — Multibyte guild/character names (e.g. "Bête Noire") 404'd on Battle.net sync; request URLs now percent-encode each path segment.
