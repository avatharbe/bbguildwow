# Integration tests — bbguildwow

Integration tests sit between unit and functional: they exercise
multiple components together (DB, cache, HTTP client) but DO NOT boot
the full phpBB request stack. Faster than functional tests, broader
than unit tests. Best fit for verifying API integration paths without
the overhead of full HTTP routing.

CI status: not yet implemented. Will run as a separate matrix in CI
(needs DB but not phpBB web server). Tagged `@group integration` for
selective execution.

## Conventions

- File naming: `tests/integration/<feature>_test.php`
- Class extends `\phpbb_database_test_case` (gives DB fixtures, no HTTP)
- Group tag: `@group integration` in the class docblock
- HTTP client: use a `Symfony\Component\HttpClient\MockHttpClient` so
  no real Battle.net traffic during tests

## Suggested tests

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
