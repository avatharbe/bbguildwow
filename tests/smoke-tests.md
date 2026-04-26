# Smoke tests — bbguildwow

Smoke tests are the shallowest possible "does it run at all" checks.
They differ from functional tests by NOT asserting on response content:
they just verify that nothing throws or 5xx's. Cheap to write, fast to
run, catch the worst class of regressions (PHP fatals, missing
services, broken autoload).

CI status: not yet implemented. Will run alongside functional tests
once enabled (no `@group functional` exclusion needed; smoke tests
are typically tagged `@group smoke`).

## Conventions

- File naming: `tests/functional/smoke_<feature>_test.php` (smoke tests
  do need a booted phpBB, so they live under `tests/functional/`)
- Class extends `\phpbb_functional_test_case`
- Group tag: `@group smoke` in the class docblock
- No content assertions — only `assertContainsLang`, status code
  assertions, or the absence of 5xx

## Suggested tests

### 1. `smoke_extension_lifecycle_test.php`

- Enable bbguild core, then enable bbguildwow
- Disable bbguildwow
- Re-enable bbguildwow

Assert: no PHP errors raised, extension state in `phpbb_ext` table
flips correctly. This single test catches 90% of "I broke something"
regressions: missing migrations, broken services.yml, autoload misses,
listener subscription errors.

### 2. `smoke_services_resolve_test.php`

For every service ID in `config/services.yml`, fetch it from the
container:

```php
foreach ($yaml['services'] as $id => $_) {
    $this->get_extension_manager()->get_finder()
        ->get_container()->get($id);
}
```

Assert: each `get()` call succeeds without throwing. Catches typos in
service definitions, missing class files, circular dependency loops.

### 3. `smoke_acp_modules_load_test.php`

Login as admin. For each ACP module declared in migrations
(achievement_module, battlenet_module, …), GET its URL. Assert HTTP
status is 200 (or 302 for redirect, never 5xx).

Catches: module `main()` method throwing on load, missing language
keys causing fatals, broken template paths.

### 4. `smoke_routes_no_500_test.php`

Issue an unauthenticated GET to every public route declared in
`config/routing.yml`. Assert each returns 200, 302, 401, or 403 — never
5xx. Authz checks belong in functional tests; this is just "doesn't
crash".

Catches: type errors in controller signatures, container parameter
references that no longer exist, missing template files.

### 5. `smoke_migration_idempotency_test.php`

Run the bbguildwow migration twice in a fresh DB (the second run via
`effectively_installed()`). Assert: second run is a no-op, no SQL
errors, no duplicate-key violations.

Catches: migrations that mistakenly recreate tables on re-run, missing
`effectively_installed()` guards.

## Why smoke tests matter

Functional tests verify behavior; smoke tests verify the lights turn
on. A passing functional suite means features work as designed; a
passing smoke suite means *nothing is on fire*. They're cheap insurance
against the kind of regression that's so basic it doesn't show up in
focused test cases.

## Notes for other plugins

All five tests above generalise unchanged. Substitute the plugin's
service IDs, ACP modules, and routes. For plugins without ACP modules
or routes, drop tests #3 and #4 (or assert that the plugin defines
none, as a guardrail against accidentally adding one without coverage).
