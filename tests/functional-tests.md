# Functional tests — bbguildwow

Functional tests in phpBB extensions extend `\phpbb_functional_test_case`,
boot a real phpBB install, and exercise routes via Goutte. They verify
end-to-end behavior that unit tests can't cover (autoload wiring, DB
migrations, ACP module routing, listeners firing on real events).

CI status: currently excluded with `--exclude-group functional` in
`.github/workflows/tests.yml` until the suite below is implemented.

## Conventions

- File naming: `tests/functional/<feature>_test.php`
- Class extends `\phpbb_functional_test_case`
- `protected static $fixtures = [];` if a SQL fixture is needed
- Group tag: every functional test should declare `@group functional`
  (in the class docblock) so it can be opted in/out per CI job.
- Use `$this->add_lang_ext('avathar/bbguildwow', 'wow')` in `setUp()`
  before asserting on translated strings.

**Testability note:** any future functional/integration test that needs
to intercept a Battle.net API call made from inside `wow_api` or
`model\achievement` can use the `create_battlenet()` seam (extracted
2026-09) instead of hitting the network — see
`tests/integration/sync_portraits_test.php` for the pattern (subclass +
override `create_battlenet()` to return a `battlenet` facade built
around a mock-server-pointed resource).

## Suggested tests

The following tests cover the most valuable seams between bbguild core,
the WoW plugin, and the Battle.net integration. They are listed in
roughly the order they should be implemented (each one builds on the
previous setup).

### 1. `extension_enable_test.php`

Enable bbguild core, then enable bbguildwow on top. Assert:
- `wow` row present in `bb_games`
- bbguildwow's classes (Warrior, Druid, …) seeded in `bb_classes` for
  `game_id='wow'`
- ACP module `-avathar-bbguildwow-acp-battlenet_module` visible in the
  ACP module tree
- `config['bbguild_wow_version']` equals the value in `composer.json`

Catches: migration regressions, services.yml misconfig, missing tables.

### 2. `game_registry_test.php`

After enabling, request the bbguild core game registry service via
`$this->get_db_test_case_helpers()` (or by HTTP-loading an ACP page that
lists games). Assert World of Warcraft appears with `has_api() === true`.

Catches: `bbguild.game_provider` tag missing in services.yml, broken
provider class.

### 3. `guild_view_renders_test.php`

Insert a guild fixture with `game_id='wow'` and one player. GET
`/guild/{guild_id}` as an authenticated user. Assert:
- Response is 200
- Roster portal module rendered the player row
- Class image `<img>` `src` resolves under `ext/avathar/bbguildwow/images/classes/`

Catches: guild_context wiring, image path resolution, listener
`add_wow_guild_vars` not firing.

### 4. `player_detail_test.php`

GET `/guild/{guild_id}/player/{player_id}` for a WoW character. Assert
the WoW-specific blocks rendered by `on_player_detail_display`:
- Equipment section present (or empty-state when no API data)
- Active spec block present
- M+ score / PvP block present
- Achievement teaser links to category browser

Catches: event listener subscription, template inclusion order, missing
template variables.

### 5. `acp_battlenet_config_test.php`

Login as admin, GET ACP route for `acp_battlenet_module`. Assert:
- HTTP 200
- Form contains `client_id`, `client_secret`, `region` inputs
- Submitting bogus credentials returns the same page with an error
  flash, not a 500

Catches: ACP module class autoload, form CSRF integration, error
handling around OAuth fetch.

### 6. `sync_routes_authz_test.php`

For each sync route (`sync-roster`, `sync-portraits`, `sync-specs`,
`sync-equipment`, `sync-achievements`, `sync-categories`), assert:
- Anonymous request returns 401 or 403
- Authenticated non-admin returns 403
- Admin request returns 200 with a JSON body containing `status`

Run with HTTP fixtures stubbed (no real Battle.net call). This is the
single most valuable functional test — it locks down authz on every
endpoint a plugin maintainer is likely to add to.

### 7. `achievement_browser_test.php`

GET `/bbguildwow/achievements/categories/`, assert grid of category
cards renders. Click into a category, assert achievement list renders.
Click into an achievement, assert the detail modal HTML contains the
achievement name and reward.

Catches: 3-level drill-down routing, JSON fixtures for category /
achievement / criteria tables, locale-aware string rendering.

### 8. `disable_keeps_core_test.php`

Enable bbguild core + bbguildwow, create a non-WoW guild (e.g. EQ2),
disable bbguildwow. Assert:
- `/guild/{eq2_guild_id}` still renders 200
- bbguild core's ACP game list still loads (i.e. plugin disable doesn't
  cascade-break core)

Catches: shared service definitions accidentally moved into the plugin,
event listeners that throw when the plugin is gone.

## Notes for other plugins

For non-API plugins (`bbguildeq2`, `bbguildgw2`, `bbguildlotro`,
`bbguildffxiv`, `bbguildffxi`, `bbguildlineage2`, `bbguildswtor`,
`bbguildeq`), drop tests #5-7 (no Battle.net API equivalent). Tests
#1-4 and #8 apply unchanged — substitute the plugin's `game_id` and
class/race seed data.

For all plugins, `disable_keeps_core_test` is the most important
guardrail because it's the failure mode that's hardest to spot in
manual testing.
