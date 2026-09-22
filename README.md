# bbGuild - World of Warcraft

bbGuild WoW is the World of Warcraft plugin for bbGuild, a guild management extension for phpBB with roots in bbDKP and EQdkp. It brings WoW guild management into your forum, connecting to Blizzard’s Battle.net API to synchronize guild rosters, character profiles, specializations, equipment, and portraits. With support for Retail and Classic editions, plus an integrated achievement browser and guild activity feed, it helps your community keep track of its characters and progress in one place.

## Version 
- **2.1.1** (requires bbGuild core >= 2.1.0)
[![Tests](https://github.com/avatharbe/bbguildwow/actions/workflows/tests.yml/badge.svg)](https://github.com/avatharbe/bbguildwow/actions/workflows/tests.yml)

**Documentation:** [avatharbe.github.io/bbguildwow](https://avatharbe.github.io/bbguildwow/)

## Features

- **WoW Classes** - All 14 playable classes (Warrior, Paladin, Hunter, Rogue, Priest, Death Knight, Shaman, Mage, Warlock, Monk, Druid, Demon Hunter, Evoker) with color codes and level ranges
- **WoW Races** - 15 playable races across Alliance and Horde factions, including Pandaren
- **WoW Factions** - Alliance and Horde with faction-based guild styling
- **WoW Classic Support** - Retail, Classic Era, Classic Progression, and Classic Anniversary editions with edition-aware API namespaces
- **Battle.net API** - OAuth 2.0 guild roster sync, character profiles, specializations, armory links, and portrait images
- **Achievement Browser** - 3-level drill-down: category cards with SVG progress rings, achievement lists, and detail modals via AJAX
- **Gear Tooltips** - Equipped items render through bbTips' item-anchor builder, with captured enchant/gem/bonus IDs so tooltips reflect a character's actual itemization, not just the base item
- **Per-Character Sync** - Implements bbGuild core's character-sync scheduler contract, so a background cron keeps specs/equipment/portraits fresh incrementally alongside the existing manual full-sync buttons
- **Guild News** - Activity feed portal module showing recent loots, achievement completions, and (via the Battle.net Guild Activity API) roster/guild events
- **Localization** - Class and race names in English, French, German, Italian, Spanish, Dutch, and Polish

## Requirements

- phpBB >= 3.3.0
- PHP >= 8.1.0
- PHP cURL extension
- **bbGuild core** (`avathar/bbguild`) must be installed and enabled

## Installation

1. Ensure bbGuild core (`avathar/bbguild`) is installed and enabled.
2. Download the latest release of `bbguildwow`.
3. Copy the `bbguildwow` folder to `/ext/avathar/bbguildwow/`.
4. Navigate in the ACP to `Customise -> Manage extensions`.
5. Look for `bbGuild - World of Warcraft` under Disabled Extensions and click `Enable`.
6. Go to ACP > bbGuild > Games and install the **World of Warcraft** game.

See the [Installation Guide](https://avatharbe.github.io/bbguildwow/INSTALL/) for detailed setup instructions including Battle.net API configuration.

## Uninstall

1. Navigate in the ACP to `Customise -> Extension Management -> Extensions`.
2. Find `bbGuild - World of Warcraft` under Enabled Extensions and click `Disable`.
3. To permanently uninstall, click `Delete Data` and then delete the `/ext/avathar/bbguildwow` folder.

**Note:** Disabling the extension does not delete existing guild or player data from the database. Your roster and player records remain intact in bbGuild core. Only the WoW installer, API integration, and game-specific images become unavailable.

## Battle.net API

This extension integrates with the Blizzard Battle.net API for:
- Automatic guild member synchronization with AJAX batch processing, plus an incremental per-character background sync (specs, equipment, portraits) via bbGuild core's cron scheduler
- Character profile data (level, class, race, specialization, achievements)
- Character equipment detail (enchants, gems, bonus IDs, set pieces) for accurate bbTips tooltips
- Character portraits via Character Media API (batch sync)
- Guild emblem generation (stored in phpBB's `files/` directory)
- Guild activity feed (roster/guild events surfaced as guild news)
- Achievement category and progress synchronization
- Edition-aware API namespaces for WoW Classic support

The API client uses OAuth 2.0 Client Credentials Grant with the modern `api.blizzard.com` endpoints and supports all five regions (US, EU, KR, TW, SEA). See the [Battle.net API Reference](https://avatharbe.github.io/bbguildwow/BATTLENET_API/) for details.

## Documentation

Full documentation site: **[avatharbe.github.io/bbguildwow](https://avatharbe.github.io/bbguildwow/)**

- [Installation Guide](https://avatharbe.github.io/bbguildwow/INSTALL/) - Step-by-step setup
- [Battle.net API Reference](https://avatharbe.github.io/bbguildwow/BATTLENET_API/) - API integration details and known issues
- [FAQ](https://avatharbe.github.io/bbguildwow/FAQ/) - Frequently asked questions
- [Changelog](https://avatharbe.github.io/bbguildwow/CHANGELOG/) - Version history
- [Architecture](https://avatharbe.github.io/bbguildwow/ARCHITECTURE/) - How the plugin system works

## For Developers

This extension serves as the reference implementation for bbGuild game plugins. If you want to create a plugin for another game, see the [Architecture](https://avatharbe.github.io/bbguildwow/ARCHITECTURE/) page for the plugin contract and structure.

## License

[GNU General Public License v2](http://opensource.org/licenses/gpl-2.0.php)

## Links

- [bbGuild Core](https://github.com/avatharbe/bbguild)
- [Support Forum](https://www.avathar.be/forum)
- [Issue Tracker](https://github.com/avatharbe/bbguildwow/issues)
