# Architecture — bbGuild Game Plugin System

## Overview

bbGuild uses a **tagged service** pattern to discover game plugins. Each game is a separate phpBB extension that registers itself with bbGuild core via the Symfony dependency injection container. This document describes how the WoW plugin works and how to build your own.

## How Plugin Discovery Works

```
┌──────────────────────────────────┐
│         phpBB DI Container       │
│                                  │
│  ┌───────────────────────────┐   │
│  │     game_registry         │   │
│  │  (tagged_iterator)        │   │
│  │                           │   │
│  │  ┌─────────────────────┐  │   │
│  │  │ wow_provider        │◄─┼───┼── avathar/bbguildwow
│  │  │ tag: bbguild.game   │  │   │
│  │  └─────────────────────┘  │   │
│  │  ┌─────────────────────┐  │   │
│  │  │ aion_provider       │◄─┼───┼── avathar/bbguild_aion (future)
│  │  │ tag: bbguild.game   │  │   │
│  │  └─────────────────────┘  │   │
│  │  ┌─────────────────────┐  │   │
│  │  │ gw2_provider        │◄─┼───┼── avathar/bbguild_gw2 (future)
│  │  │ tag: bbguild.game   │  │   │
│  │  └─────────────────────┘  │   │
│  └───────────────────────────┘   │
│                                  │
│         avathar/bbguild (core)   │
└──────────────────────────────────┘
```

1. Each game extension's `config/services.yml` tags its provider with `bbguild.game_provider`
2. bbGuild core's `game_registry` receives all tagged providers via `!tagged_iterator`
3. The registry indexes providers by `get_game_id()` for O(1) lookup
4. Core code asks the registry for a provider when it needs game-specific behavior

## Plugin Contract

### Required: `game_provider_interface`

Every game plugin **must** implement this interface from `avathar\bbguild\model\games`:

```php
interface game_provider_interface
{
    public function get_game_id(): string;        // e.g. 'wow'
    public function get_game_name(): string;       // e.g. 'World of Warcraft'
    public function get_installer(): game_install_interface;
    public function get_boss_base_url(): string;   // sprintf format with %s
    public function get_zone_base_url(): string;   // sprintf format with %s
    public function get_images_path(): string;     // absolute path to images
    public function has_api(): bool;               // true if external API exists
    public function get_api(): ?game_api_interface;
    public function get_regions(): array;          // region_code => name
    public function get_api_locales(): array;      // region_code => [locales]
}
```

### Required: `game_install_interface`

The installer populates the database with game-specific factions, classes, races, and roles:

```php
interface game_install_interface
{
    public function install(array $table_names, string $game_id, string $game_name,
                           string $boss_base_url, string $zone_base_url, string $region): void;
    public function uninstall(array $table_names, string $game_id, string $game_name): void;
}
```

**Recommended:** Extend `abstract_game_install` instead of implementing the interface directly. It provides:
- Transaction management (begin/commit)
- Game record insertion/deletion
- Role installation (DPS/Healer/Tank) with 4-language translations
- Cache invalidation
- The `table(string $key)` helper for accessing table names

You only need to implement:
```php
abstract protected function install_factions();
abstract protected function install_classes();
abstract protected function install_races();
```

And optionally override:
```php
protected function has_api_support(): bool  // default: false
protected function install_roles()          // default: DPS/Healer/Tank
```

### Optional: `game_api_interface`

Only implement this if your game has an external API:

```php
interface game_api_interface
{
    public function fetch_guild_data(string $guild_name, string $realm, string $region, array $params);
    public function process_guild_data(array $raw_data, array $params): array;
    public function fetch_character_data(string $name, string $realm, string $region);
    public function get_player_armory_url(string $name, string $realm, string $region): string;
    public function get_player_portrait_url(array $player_data): string;
    public function sync_guild_members(array $member_data, int $guild_id, string $region, int $min_level): void;
    public function requires_api_key(): bool;
}
```

## WoW Plugin Structure

```
ext/avathar/bbguildwow/
├── composer.json                    # Package metadata
├── ext.php                          # Checks bbGuild core is enabled
├── config/
│   └── services.yml                 # DI config: tags provider as bbguild.game_provider
├── game/
│   ├── wow_provider.php             # game_provider_interface implementation
│   ├── wow_installer.php            # Extends abstract_game_install
│   └── wow_api.php                  # game_api_interface implementation
├── api/
│   ├── battlenet.php                # API factory
│   ├── battlenet_resource.php       # Abstract base (URL building, caching, auth)
│   ├── battlenet_character.php      # Character API resource
│   ├── battlenet_guild.php          # Guild API resource — getGuild/getRoster/getAchievements/getActivity
│   ├── battlenet_realm.php          # Realm API resource
│   └── battlenet_achievement.php    # Achievement API resource
├── cron/task/
│   └── sync_guild.php               # Scheduled roster + activity feed sync (#11/#10)
├── sync/
│   └── character_sync_handler.php   # bbGuild core's per-character character_sync_interface (#362)
└── docs/
    └── ...
```

## Service Wiring

### `config/services.yml`

```yaml
services:
    avathar.bbguildwow.installer:
        class: avathar\bbguildwow\game\wow_installer

    avathar.bbguildwow.api:
        class: avathar\bbguildwow\game\wow_api
        arguments:
            - '@cache'

    avathar.bbguildwow.provider:
        class: avathar\bbguildwow\game\wow_provider
        arguments:
            - '@avathar.bbguildwow.installer'
            - '@avathar.bbguildwow.api'
            - '@ext.manager'
        tags:
            - { name: bbguild.game_provider }
```

The `bbguild.game_provider` tag is what makes bbGuild core discover this provider.

## Dual-Path Execution

During the transition period, bbGuild core supports both the new plugin path and the legacy hardcoded path:

```
Core receives a request (e.g. install game, fetch guild data)
    │
    ├── Check game_registry for provider
    │     ├── Found → delegate to provider (new path)
    │     └── Not found ↓
    │
    └── Fall back to legacy hardcoded code (old path)
```

This means:
- If `bbguildwow` is enabled → WoW operations go through the plugin
- If `bbguildwow` is disabled → WoW operations use the old hardcoded installer (if still present)
- Once all games are extracted and old code is removed (Phase 5), only the plugin path will exist

## Creating a New Game Plugin

### Minimal example (no API)

For a game without API integration (e.g. a tabletop RPG):

**`config/services.yml`:**
```yaml
services:
    avathar.bbguild_mygame.installer:
        class: avathar\bbguild_mygame\game\mygame_installer

    avathar.bbguild_mygame.provider:
        class: avathar\bbguild_mygame\game\mygame_provider
        arguments:
            - '@avathar.bbguild_mygame.installer'
            - '@ext.manager'
        tags:
            - { name: bbguild.game_provider }
```

**`game/mygame_provider.php`:**
```php
class mygame_provider implements game_provider_interface
{
    public function get_game_id(): string { return 'mygame'; }
    public function get_game_name(): string { return 'My Game'; }
    public function get_installer(): game_install_interface { return $this->installer; }
    public function get_boss_base_url(): string { return ''; }
    public function get_zone_base_url(): string { return ''; }
    public function get_images_path(): string { return $this->ext_manager->get_extension_path('avathar/bbguild_mygame', true) . 'images/'; }
    public function has_api(): bool { return false; }
    public function get_api(): ?game_api_interface { return null; }
    public function get_regions(): array { return []; }
    public function get_api_locales(): array { return []; }
}
```

**`game/mygame_installer.php`:**
```php
class mygame_installer extends abstract_game_install
{
    protected function install_factions() { /* insert factions */ }
    protected function install_classes() { /* insert classes + language entries */ }
    protected function install_races() { /* insert races + language entries */ }
}
```

### Table Names

The `$table_names` array passed to `install()` / `uninstall()` contains these keys:

| Key | Table |
|-----|-------|
| `bb_games_table` | Game records |
| `bb_factions_table` | Factions per game |
| `bb_classes_table` | Classes per game |
| `bb_races_table` | Races per game |
| `bb_language_table` | Localized names for classes, races, roles |
| `bb_gameroles_table` | Roles per game (DPS, Healer, Tank) |
| `bb_players_table` | Player records |
| `bb_guild_table` | Guild records |
| `bb_ranks_table` | Rank records |

Access them via `$this->table('bb_classes_table')` in your installer.

### Language Entries

When inserting class/race names, add entries to the language table for each supported language:

```php
$sql_ary[] = array(
    'game_id'      => $this->game_id,
    'attribute_id' => 1,              // matches class_id or race_id
    'language'     => 'en',           // language code
    'attribute'    => 'class',        // 'class', 'race', or 'role'
    'name'         => 'Warrior',      // display name
    'name_short'   => 'Warrior',      // short name (for compact views)
);
```
