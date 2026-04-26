# Unit tests — bbguildwow

Unit tests run via PHPUnit against single classes with all collaborators
mocked. No DB, no HTTP, no filesystem. Fast (<1s per test typically).
They run in CI on every push across PHP 8.1-8.4.

CI status: **passing** in `.github/workflows/tests.yml` → `unit-tests`
job. The job checks out phpBB + bbguildwow + bbguild core (so that
plugin classes can resolve core interfaces) and runs phpunit with
`--exclude-group functional`.

## Existing tests

- `tests/system/ext_test.php` — `ext::is_enableable()` returns true when
  bbguild core is enabled.
- `tests/game/wow_provider_test.php` — provider implements
  `game_provider_interface`, returns expected armor types / regions.
- `tests/game/wow_installer_test.php` — class/race/faction seed arrays
  have unique IDs, no duplicates, valid armor type references.
- `tests/game/wow_api_test.php` — pure-function helpers (`to_slug`,
  `error_label`, response parsing edge cases).
- `tests/api/battlenet_resource_test.php` — OAuth token cache hit path,
  retry logic, non-2xx handling.
- `tests/api/battlenet_character_test.php` — character endpoint URL
  composition, response shape.

## Suggested additions

### 1. `tests/game/wow_api_validate_response_test.php`

`wow_api::validate_response()` parses Battle.net JSON. Test edge cases
returned by the live API:
- Empty body → returns `null`, doesn't throw
- Malformed JSON → returns `null`, logs warning
- Maintenance window response (HTML page, not JSON) → returns `null`
- Rate-limit 429 with `Retry-After` header → triggers backoff path

### 2. `tests/api/battlenet_resource_oauth_refresh_test.php`

Currently we test cache hit. Add:
- Cache miss + token fetch + token cache write
- Token expired (cache miss after TTL) refetches
- 401 from API triggers token refresh and retry once

### 3. `tests/migrations/data_integrity_test.php`

Load `migrations/basics/data.php`, walk every seed array, assert:
- `bb_classes`: each row has `class_id`, `class_name`, `armor_type_id`
  in {0..N}, `game_id='wow'`
- `bb_races`: each row has `faction_id` ∈ {1, 2} (Alliance / Horde)
- No `class_id` collision across rows
- All referenced `armor_type_id` values exist in
  `wow_provider::get_armor_types()`

### 4. `tests/portal/wow_module_render_test.php`

For each WoW-specific portal module (currently the achievement teaser
in WoW namespace, if any), test the render method with a mocked
template service. Assert `template->assign_*` calls happen with
expected variable names.

### 5. `tests/event/listener_subscription_test.php`

`getSubscribedEvents()` returns an array. Assert:
- Every key (event name) starts with `core.` or `avathar.bbguild.`
- Every value (handler method) exists on the listener class
- No two keys map to the same event name (would silently shadow)

### 6. `tests/asset_url_resolver_test.php`

The resolver picks between local file, plugin-served file, and
Battle.net CDN URL based on configuration and resource availability.
Test:
- Guild emblem path resolution falls through local → plugin route → CDN
- Player portrait path uses correct region prefix (eu/us/kr/tw)
- Missing image returns the WoW plugin's default placeholder, not core's

### 7. `tests/cache_key_test.php`

Cache keys must remain stable across deploys. Assert exact strings:
- `'bbguild_wow_oauth_token_eu'` for EU region
- `'bbguild_wow_playable_classes'` for class list
- These keys persist in phpBB's cache backend; renaming them orphans
  cached data.

## Notes for other plugins

For non-API plugins, drop tests #2 (no OAuth) and adapt #1 to the
plugin's own data sources (or skip entirely). Tests #3 and #5 apply
unchanged with the plugin's seed data and listener.
