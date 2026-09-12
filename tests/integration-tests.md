# Integration tests — bbguildwow

Integration tests sit between unit and functional: they exercise
multiple components together (DB, cache, and sometimes HTTP) but skip
functional tests' Goutte-driven HTTP routing — they call model/API
code directly. Broader than unit tests (real DB, real extension
install), narrower than functional tests (no page rendering, no route
dispatch).

**Correction to the description above (2026-09):** `\phpbb_database_test_case`
turned out not to fit — all implemented tests actually extend
`\phpbb_functional_test_case` (directly, or via `mock_battlenet_test_case`
below), because that's what gives a real DB connection, a real installed
extension, and working `get_db()`/`get_cache_driver()`/`get_extension_manager()`
helpers in this test framework. "Skips HTTP routing" still holds — no
test here drives a route through Goutte — but the full phpBB
application bootstrap (board install, extension enable) does run.

CI status: implemented (not yet wired into the CI matrix — same follow-up
as the rest of this file's "not yet implemented" note applied to the
suite as a whole).

## Conventions

- File naming: `tests/integration/<feature>_test.php`
- Class extends `mock_battlenet_test_case` (`tests/integration/mock_battlenet_test_case.php`)
  when the test needs to intercept a Battle.net API call, or plain
  `\phpbb_functional_test_case` directly when it doesn't (e.g.
  `roster_sync_test.php` drives `sync_guild_members()` with
  already-fetched data — no HTTP involved at all).
- Group tag: `@group integration` in the class docblock
- HTTP interception: **not** `Symfony\Component\HttpClient\MockHttpClient`
  — `api/battlenet_resource.php` makes its requests with raw `curl_exec()`,
  with no injectable HTTP client to mock. `mock_battlenet_test_case`
  instead starts a real local `php -S` server (driven by a JSON control
  file) and tests point production code at it via the `create_battlenet()`
  seam (`game/wow_api.php` / `model/achievement.php`, extracted 2026-09):
  subclass the test's model class, override `create_battlenet()` to
  return a `battlenet` facade wrapping a mock-server-pointed resource.
  See `tests/integration/sync_portraits_test.php` for the pattern.
- No DI container is reachable in-process: `phpbb_functional_test_case`'s
  own HTTP-driven request handling happens in a separate PHP process, so
  `$this->get_container()` (used in earlier drafts of these tests) does
  not exist and none of the app's real services are available here.
  Construct dependencies directly instead — table names via
  `self::$config['table_prefix'] . '<suffix>'`, and
  `get_db()`/`get_cache_driver()`/`get_extension_manager()` (protected
  helpers already on the base class) cover most of the rest. A
  `\phpbb\user` needed only for the specific `$user->lang[...]` keys a
  constructor reads can be a `disableOriginalConstructor()` PHPUnit mock
  with `__get()` stubbed — it's a normal, overridable method on
  `\phpbb\user` (see `phpbb/user.php`), not true PHP magic dispatch.
- `phpbb_functional_test_case` does not reset DB state between test
  methods or between test classes in the same suite run. Give fixtures
  in different files (or different methods needing distinct rows) their
  own identity — e.g. a distinct `player_guild_id` per file when the
  production query being tested scopes by guild (as `sync_portraits_test.php`,
  `sync_specs_test.php`, and `equipment_sync_test.php` each do), not just
  a distinct realm/name, since an unscoped query would otherwise pick up
  another file's rows too.

## Implemented

### `roster_sync_test.php`

Drives `wow_api::sync_guild_members()` directly with fabricated member
data (no HTTP mock needed — this method takes already-fetched data).
Covers: new-member insert with class/race mapping, in-place update on a
re-appearing same-name-same-realm character, soft-delete-old +
insert-new on a character rename (see below — NOT an in-place update),
soft-delete on departure.

**Correction to the original suggestion below:** a Battle.net name
change does not update the existing row. `update_wow_roster()` keys
players by `name-realm_slug`; a rename produces a different key, so the
old key is soft-deleted and the new key is inserted fresh. The test
locks down this actual behavior.

### `equipment_sync_test.php`

Mocks `/profile/wow/character/{realm}/{name}/equipment` via the mock
server. Covers first-sync insert and resync replace/remove-on-unequip
(delete-then-reinsert wipes the prior loadout rather than merging).

### `achievement_sync_test.php`

Mocks the category index/detail and guild achievement/detail endpoints.
Covers category hierarchy insert, `setAchievements()` → `syncCategories()`
category_id backfill, and re-run idempotency for both.

**Scope cut:** `insert_achievement()`, `insert_criteria()`,
`collect_criteria()`, and `flatten_criteria()` are dead code — grepped
the whole codebase, nothing calls them. Only `syncCategories()` and
`setAchievements()` (via `fetch_achievement_detail_from()` /
`update_achievement_detail()`) are reachable from any caller, so those
are the only paths tested. Same treatment as tests #5/#6 below.

### `sync_portraits_test.php` / `sync_specs_test.php`

Added beyond the original suggested list — these two sync methods
(along with `sync_equipment()` above) had no test seam at all before
`create_battlenet()` was extracted in game/wow_api.php (see the plan
this file's checklist came from). `sync_portraits()`'s remote-image
download path can't be exercised deterministically without network;
the test asserts the code's own documented fallback (raw URL stored)
instead — see that test file's docblock.

## Original suggestions (superseded by "Implemented" above)

### 1. `oauth_token_lifecycle_test.php`

Mock Battle.net OAuth endpoint. Run through:
- First call → mock returns `{access_token, expires_in: 3600}`
- Assert token cached at `bbguild_wow_oauth_token_eu`
- Second call within TTL → cache hit, no HTTP
- Advance time past TTL → cache miss, refetch, new token cached
- Mock returns 401 on resource call → triggers refresh, retries once

Catches: OAuth flow regressions that unit tests miss because they only
test individual hops in isolation.

### 2. `roster_sync_test.php`

Mock the Battle.net guild roster endpoint with a fixture (e.g. 10
characters with mixed classes/races). Run the sync command:
- Assert `bb_players` rows inserted with correct `class_id` mapping
  (Battle.net's `playable_class.id` → bbguild's `class_id`)
- Assert race/faction mapping correct
- Re-run sync with one character renamed: existing row updated, no
  duplicate inserted
- Re-run sync with one character missing from API: row marked inactive,
  not deleted (preserves DKP history)

Catches: ID-mapping drift after Battle.net schema changes, soft-delete
regressions.

### 3. `equipment_sync_test.php`

Mock `/profile/wow/character/{realm}/{name}/equipment`. For each
equipped item:
- Assert `bb_player_equipment` row inserted with `slot_type`,
  `item_id`, `item_quality`
- Re-run with same character → existing rows updated, not duplicated
- Re-run with character that has unequipped a slot → that row removed

### 4. `achievement_sync_test.php`

This is the most complex sync. Mock the achievement category endpoint,
the achievement list endpoint, and the achievement criteria endpoint.
Assert:
- All categories inserted in correct hierarchy (`parent_category` chain)
- Achievements inserted with FK to `bb_achievement_category`
- Criteria inserted with FK to `bb_achievement`
- Re-run is idempotent (no duplicates)
- Achievement reward (item or title) mapped correctly

Catches: sync ordering bugs (criteria inserted before parent
achievement → FK violation), missing transactional wrap.

### 5. `player_detail_data_aggregation_test.php` — DROPPED

Premise doesn't match the code: there is no aggregator service.
`event\listener::on_player_detail_display()` is a plain procedural
event listener — direct SQL queries against `bb_players` and
`bb_player_equipment`, then template var assignment. Nothing to unit-
test in isolation without either extracting a real aggregator service
(a production refactor, not a test-writing task) or duplicating the
listener's SQL in the test itself, which wouldn't test anything real.

### 6. `cache_invalidation_test.php` — DROPPED

Premise doesn't match the code: the only `cache->destroy()` call
anywhere in this extension is for the OAuth token
(`bbguild_wow_oauth_token_{region}`, in `acp/battlenet_module.php`).
There is no `bbguild_wow_player_<id>` cache entry — that key doesn't
exist. Renaming a character in ACP doesn't cache-invalidate anything
today because nothing per-player is cached.

## Why integration tests are valuable here

The WoW plugin's code is mostly glue between Battle.net's API and
bbguild's data model. Unit tests verify the parsers; functional tests
verify the rendered pages; **integration tests verify the glue**. The
sync paths in particular have a lot of state (cache + DB + HTTP) and
benefit hugely from end-to-end coverage that can run in seconds, not
minutes.

## Notes for other plugins

Most integration tests above are WoW-specific (require an external
API). For non-API plugins, the only relevant integration tests are
fixture-loading correctness (test #4 minus the HTTP mocks — load seed
data into DB, assert structure). Those plugins get more value from
unit + functional tests instead.
