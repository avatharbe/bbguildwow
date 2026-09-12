# Battle.net API Reference

## Overview

This extension integrates with the Blizzard Battle.net API to provide:

- **Guild roster sync** — Automatically import guild members with class, race, level, and rank data
- **Character profiles** — Fetch individual character data including talents, titles, and achievements
- **Armory links** — Generate links to a character's Blizzard profile page
- **Character portraits** — Display character render images from Blizzard's CDN
- **Guild emblems** — Generate guild emblem images from API-provided emblem data
- **Realm status** — Query realm availability

## Authentication

The API uses **OAuth 2.0 Client Credentials Grant**:

1. Client sends `POST https://oauth.battle.net/token` with `grant_type=client_credentials` and `Authorization: Basic base64({client_id}:{client_secret})`
2. Blizzard responds with `{ "access_token": "...", "expires_in": 86400 }`
3. All subsequent API requests include `Authorization: Bearer {access_token}` and `Battlenet-Namespace: {type}-{region}`

**Token caching:** Tokens are cached using phpBB's cache service with key `bbguildwow_oauth_token_{region}`. On 401 response, the cached token is invalidated and the request is retried once.

**CN region:** Uses `https://oauth.battlenet.com.cn/token` instead.

## API Endpoints

| Region | Base URL |
|--------|----------|
| US | `https://us.api.blizzard.com/` |
| EU | `https://eu.api.blizzard.com/` |
| KR | `https://kr.api.blizzard.com/` |
| TW | `https://tw.api.blizzard.com/` |

### Namespace Types

Every API request requires a `Battlenet-Namespace` header:

| Type | Used For | Example |
|------|----------|---------|
| `dynamic-{region}` | Realm status | `dynamic-eu` |
| `profile-{region}` | Guild and character data | `profile-us` |
| `static-{region}` | Achievement data | `static-kr` |

## API Architecture

### Class Hierarchy

```
battlenet_resource (abstract base — OAuth 2.0 + caching)
      ├── battlenet_guild
      ├── battlenet_character
      ├── battlenet_realm
      └── battlenet_achievement

battlenet (factory)
      └── creates guild/character/realm/achievement instances
```

### Request Flow

1. Caller creates `battlenet($type, $region, $apikey, $locale, $privkey, $ext_path, $cache)`
2. Factory instantiates the appropriate resource subclass and sets namespace type
3. Resource's method (e.g. `getGuild()`) calls `consume($method, $params)`
4. `consume()` builds the request URL, checks cache, obtains OAuth token, sends Bearer-authenticated request
5. On 401, token is refreshed and request retried once
6. Response is JSON-decoded and cached for the configured TTL (default 3600s)

### Caching

All API responses are cached using phpBB's cache service:
- Cache key: `bbguildwow_api_` + base64-encoded full request URL
- Default TTL: 3600 seconds (1 hour)
- Cache is stored in phpBB's configured cache backend (file, APCu, Redis, etc.)

OAuth tokens are cached separately:
- Cache key: `bbguildwow_oauth_token_{region}`
- TTL: token expiry minus 5 minutes (typically ~23.9 hours)

## Guild API

**Request:** `GET /{endpoint}/{realm}/{name}?fields={fields}&locale={locale}`

**Extra fields:** `members`, `achievements`, `news`

**Response structure (members):**
```json
{
  "name": "Guild Name",
  "realm": "Realm Name",
  "battlegroup": "Battlegroup",
  "level": 25,
  "side": 0,
  "achievementPoints": 1234,
  "emblem": {
    "icon": 126,
    "iconColor": "ff101517",
    "border": 0,
    "borderColor": "ff0f1415",
    "backgroundColor": "ffffffff"
  },
  "members": [
    {
      "character": {
        "name": "CharName",
        "realm": "Realm",
        "class": 1,
        "race": 1,
        "gender": 0,
        "level": 110,
        "achievementPoints": 5678,
        "thumbnail": "realm/12/34567890-avatar.jpg"
      },
      "rank": 0
    }
  ]
}
```

**Faction mapping:** `side: 0` = Alliance (bbGuild faction 1), `side: 1` = Horde (bbGuild faction 2)

## Character API

**Request:** `GET /{endpoint}/{realm}/{name}?fields={fields}&locale={locale}`

**Extra fields:** `achievements`, `appearance`, `feed`, `guild`, `hunterPets`, `items`, `mounts`, `pets`, `petSlots`, `professions`, `progression`, `pvp`, `reputation`, `stats`, `talents`, `titles`

**Default fields requested by bbGuild:** `guild`, `titles`, `talents`

**Portrait URL format:** `https://render.worldofwarcraft.com/character/{region}/{thumbnail}`

**Armory URL format:** `https://worldofwarcraft.blizzard.com/en-{region}/character/{region}/{realmSlug}/{name}`

## Realm API

**Request:** `GET /realm/status?realms={realm1,realm2}&locale={locale}`

## Achievement API

**Request:** `GET /achievement/{id}?locale={locale}`

## Guild Activity API (#10)

**Note:** unlike the Guild/Character/Realm/Achievement sections above (legacy
Community API shape), this section reflects the actual modern **Game Data
API** implementation — OAuth 2.0, `Battlenet-Namespace` header, `data/wow/`
path prefix — matching what `api/battlenet_resource.php`/`battlenet_guild.php`
actually send.

**Request:** `GET /data/wow/guild/{realmSlug}/{nameSlug}/activity?namespace=profile-{region}&locale={locale}` (`battlenet_guild::getActivity()`)

Returns recent guild activity: boss kills, achievements earned by members,
members joined/left, item loots — see `bbguildwow#10`'s original issue for
the intended categories.

**Sync path:** `wow_api::fetch_guild_activity()` calls this and returns the
raw `response` array; `wow_api::sync_guild_activity(int $guild_id, array
$activities)` maps entries into `bb_news` (`news_source = 'api'`), skipping
any whose `news_source_key` (a hash of the raw entry — guild id + timestamp +
`activity.type` + full-entry md5) is already present, so repeated cron runs
don't duplicate rows.

**Known gap:** the exact response shape — specifically the enumeration of
`activity.type` values and the nested field names beyond `activity.type` /
`timestamp` / `character.name` (e.g. what an achievement entry's detail
object looks like) — has not been verified against a live guild's actual API
response. `describe_activity_headline()` in `wow_api.php` therefore builds
news text generically (humanized type string + character name, if present)
rather than asserting specific per-type field mappings it can't confirm.
Dedup/storage correctness doesn't depend on getting that mapping right, but
the displayed text quality would improve once a real response — or a
`mock_battlenet_server.php`-style fixture — is available to verify against.

## Error Handling

The API returns structured error responses:
```json
{ "code": 401, "type": "BLZWEBAPI00000401", "detail": "Unauthorized" }
```

| Code | Meaning | bbGuild Behavior |
|------|---------|------------------|
| 200 | Success | Data processed normally |
| 401 | Unauthorized | Token refreshed and request retried once |
| 403 | Forbidden | Marks armory as disabled, logs error |
| 404 | Not Found | Returns KO result |
| 429 | Rate Limited | Returns error |
| 500+ | Server Error | Returns KO result, logs error |

## Configuration

### API Credentials

Set in ACP > bbGuild > Games > Edit World of Warcraft:

| Field | Description |
|-------|-------------|
| **Client ID** | Your Blizzard API client ID from [develop.battle.net](https://develop.battle.net/access/clients) |
| **Client Secret** | Your Blizzard API client secret |
| **Locale** | Determines the language of API responses (e.g. `en_GB`, `de_DE`) |

### Region

Set per guild in ACP > bbGuild > Guilds > Edit:

| Region | API Base URL |
|--------|-------------|
| US | `https://us.api.blizzard.com/` |
| EU | `https://eu.api.blizzard.com/` |
| KR | `https://kr.api.blizzard.com/` |
| TW | `https://tw.api.blizzard.com/` |

### Minimum Armory Level

Set per guild — only characters at or above this level will be imported during guild sync.

## Files

| File | Purpose |
|------|---------|
| `game/wow_api.php` | Implements `game_api_interface`, orchestrates API calls |
| `api/battlenet.php` | Factory — creates resource instances per API type |
| `api/battlenet_resource.php` | Abstract base — OAuth 2.0 auth, caching, HTTP requests |
| `api/battlenet_guild.php` | Guild resource — `getGuild()`, `getRoster()`, `getAchievements()`, `getActivity()` |
| `api/battlenet_character.php` | Character resource — `getCharacter()` |
| `api/battlenet_realm.php` | Realm resource — `getRealmStatus()` |
| `api/battlenet_achievement.php` | Achievement resource — `getAchievementDetail()` |
| `cron/task/sync_guild.php` | Scheduled roster + activity feed sync (#11/#10) |
