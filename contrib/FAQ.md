# Frequently Asked Questions

## General

### What is bbGuild WoW?

It is a game plugin for bbGuild that adds World of Warcraft support. It provides WoW-specific classes, races, factions, and optional Battle.net API integration for automatic guild roster synchronization.

### Do I need this extension to use bbGuild?

No. bbGuild core works without any game plugins. However, without a game plugin installed, you won't have any game-specific classes, races, or factions available. You can still create guilds and manage players manually using the "Custom" game type in core.

### Can I use bbGuild WoW alongside other game plugins?

Yes. bbGuild's plugin system supports multiple game plugins simultaneously. Each guild is associated with one game, but your forum can host guilds from different games at the same time.

---

## Installation

### The extension won't enable — nothing happens

Verify that bbGuild core (`avathar/bbguild`) is enabled first. The WoW extension checks for core and refuses to enable without it.

### I enabled the extension but WoW doesn't appear in the games list

After enabling the extension, you need to clear the phpBB cache (ACP > General > Purge the cache). The game registry discovers plugins at container compilation time.

### I disabled the extension — is my data lost?

No. Disabling or even uninstalling the WoW extension does not touch existing guild or player data. All roster information, player records, ranks, and achievements remain in the bbGuild database tables. You simply lose the ability to:
- Install WoW as a new game
- Use Battle.net API features
- See WoW-specific images (if they are moved to this extension in the future)

Your existing WoW guilds and players continue to display normally in bbGuild core.

---

## Battle.net API

### Do I need a Battle.net API key?

No, it is optional. Without an API key you can still:
- Manually add and manage players
- Use the guild roster
- Assign classes, races, and ranks manually

With an API key you additionally get:
- Automatic guild member import
- Character portraits from Blizzard
- Armory profile links
- Level, class, and race data pulled automatically

### Where do I get an API key?

Register at [https://develop.battle.net/](https://develop.battle.net/) with your Blizzard account. Create an API client to obtain a Client ID and Client Secret.

### The API calls are failing — what's wrong?

The most common causes:
1. **Invalid or expired API key** — Verify your credentials at develop.battle.net
2. **Wrong region** — Make sure your guild's region matches where it actually exists
3. **Wrong locale** — The locale must be valid for your region (e.g. `en_GB` for EU, `en_US` for US)
4. **API credentials** — Make sure both Client ID and Client Secret are entered correctly. See [BATTLENET_API.md](BATTLENET_API.md) for details.

### I get a 403 error from the API

This means your API key is being rejected. Possible causes:
- The key is invalid or revoked
- Your Blizzard account doesn't have API access
- You've exceeded the rate limit

bbGuild will automatically mark the guild's armory as disabled when it receives a 403. Fix your API credentials and re-enable armory in the guild settings.

### Character portraits are not loading

Portrait URLs are generated using Blizzard's CDN:
```
https://render.worldofwarcraft.com/character/{region}/{thumbnail}
```

If portraits are broken, check that the thumbnail path in the API response is valid.

---

## Data & Content

### What classes are included?

All 13 WoW classes as of Legion (patch 7.x):

| ID | Class | Armor | Color |
|----|-------|-------|-------|
| 1 | Warrior | Plate | #c69b6d |
| 2 | Paladin | Plate | #f48cba |
| 3 | Hunter | Mail | #aad372 |
| 4 | Rogue | Leather | #fff468 |
| 5 | Priest | Cloth | #f0ebe0 |
| 6 | Death Knight | Plate | #c41e3b |
| 7 | Shaman | Mail | #2359ff |
| 8 | Mage | Cloth | #68ccef |
| 9 | Warlock | Cloth | #9382c9 |
| 10 | Monk | Leather | #00ffba |
| 11 | Druid | Leather | #ff7c0a |
| 12 | Demon Hunter | Leather | #A330C3 |

**Note:** Evoker (class 13, added in Dragonflight) is not yet included. It will be added in a future update.

### What races are included?

15 races across both factions:

**Alliance:** Human, Dwarf, Night Elf, Gnome, Draenei, Worgen, Pandaren (Alliance)

**Horde:** Orc, Undead, Tauren, Troll, Blood Elf, Goblin, Pandaren (Horde)

**Note:** Allied races (Void Elf, Lightforged Draenei, Dark Iron Dwarf, Kul Tiran, Mechagnome, Nightborne, Highmountain Tauren, Mag'har Orc, Zandalari Troll, Vulpera, Dracthyr) are not yet included. They will be added in a future update.

### What languages are class/race names available in?

English (en), French (fr), German (de), and Italian (it). The language used depends on the `bbguild_lang` configuration setting in bbGuild core.

### Can I add custom classes or races?

Not through this extension — the installer provides the standard WoW classes and races. However, bbGuild core supports custom games where you can define arbitrary classes and races via the ACP.

---

## For Developers

### How does the plugin system work?

See [ARCHITECTURE.md](ARCHITECTURE.md) for the full technical details. In short:
1. This extension registers a **service** tagged with `bbguild.game_provider`
2. bbGuild core's **game registry** discovers all tagged services at container compilation
3. Core delegates game-specific operations (install, API calls, image paths) to the provider

### Can I use this as a template for another game?

Yes, that's the intent. To create a plugin for another game:
1. Copy this extension's structure
2. Implement `game_provider_interface` with your game's data
3. Create an installer extending `abstract_game_install`
4. Optionally implement `game_api_interface` if your game has an external API
5. Tag your provider service as `bbguild.game_provider` in `services.yml`
