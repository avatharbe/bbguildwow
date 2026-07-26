# bbGuild - World of Warcraft

**Current version:** 2.0.0-rc2 (release candidate)

World of Warcraft is where this project's own history starts — bbGuild began life over a decade ago as a WoW guild tool, and bbguildwow is still the one plugin that goes beyond static game data. It talks to Blizzard's Battle.net API directly: a raid officer clicks "sync" and the roster, character profiles, portraits, and armory links update themselves, instead of someone manually re-typing the roster after every Mythic+ night. All 14 classes and 15 races are covered across Retail and every Classic edition (Era, Progression, Anniversary), so guilds running classic servers get the same tooling as retail. This release adds a full specialization layer — a Frost Mage shows up as a Frost Mage, not just "Mage" — plus a 3-level achievement browser your members can drill into without leaving the forum.
[![Tests](https://github.com/avatharbe/bbguildwow/actions/workflows/tests.yml/badge.svg)](https://github.com/avatharbe/bbguildwow/actions/workflows/tests.yml)

## Features

- **WoW Classes** - All 14 playable classes (Warrior, Paladin, Hunter, Rogue, Priest, Death Knight, Shaman, Mage, Warlock, Monk, Druid, Demon Hunter, Evoker) with color codes and level ranges
- **WoW Races** - 15 playable races across Alliance and Horde factions, including Pandaren
- **WoW Factions** - Alliance and Horde with faction-based guild styling
- **WoW Classic Support** - Retail, Classic Era, Classic Progression, and Classic Anniversary editions with edition-aware API namespaces
- **Battle.net API** - OAuth 2.0 guild roster sync, character profiles, specializations, armory links, and portrait images
- **Achievement Browser** - 3-level drill-down: category cards with SVG progress rings, achievement lists, and detail modals via AJAX
- **Guild News** - Activity feed portal module showing recent loots and achievement completions
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

See [docs/INSTALL.md](docs/INSTALL.md) for detailed setup instructions including Battle.net API configuration.

## Uninstall

1. Navigate in the ACP to `Customise -> Extension Management -> Extensions`.
2. Find `bbGuild - World of Warcraft` under Enabled Extensions and click `Disable`.
3. To permanently uninstall, click `Delete Data` and then delete the `/ext/avathar/bbguildwow` folder.

**Note:** Disabling the extension does not delete existing guild or player data from the database. Your roster and player records remain intact in bbGuild core. Only the WoW installer, API integration, and game-specific images become unavailable.

## Battle.net API

This extension integrates with the Blizzard Battle.net API for:
- Automatic guild member synchronization with AJAX batch processing
- Character profile data (level, class, race, specialization, achievements)
- Character portraits via Character Media API (batch sync)
- Guild emblem generation (stored in phpBB's `files/` directory)
- Achievement category and progress synchronization
- Edition-aware API namespaces for WoW Classic support

The API client uses OAuth 2.0 Client Credentials Grant with the modern `api.blizzard.com` endpoints and supports all five regions (US, EU, KR, TW, SEA). See [docs/BATTLENET_API.md](docs/BATTLENET_API.md) for details.

## Documentation

- [Installation Guide](docs/INSTALL.md) - Step-by-step setup
- [Battle.net API Reference](docs/BATTLENET_API.md) - API integration details and known issues
- [FAQ](docs/FAQ.md) - Frequently asked questions
- [Changelog](CHANGELOG.md) - Version history
- [Architecture](docs/ARCHITECTURE.md) - How the plugin system works

## For Developers

This extension serves as the reference implementation for bbGuild game plugins. If you want to create a plugin for another game, see [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for the plugin contract and structure.

## License

[GNU General Public License v2](http://opensource.org/licenses/gpl-2.0.php)

## Links

- [bbGuild Core](https://github.com/avatharbe/bbguild)
- [Support Forum](https://www.avathar.be/forum)
- [Issue Tracker](https://github.com/avatharbe/bbguildwow/issues)
