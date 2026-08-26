# EPV — bbguildwow

EPV (Extension Pre-Validator) is run by the phpBB extension test framework
on every push. It enforces extension-DB-style policies before submission:
file structure, license content, namespace / event docblock conventions,
banned function calls, image properties, etc.

CI status: **passing**. Configured in `.github/workflows/tests.yml` →
`basic-checks` job. The framework runs `vendor/bin/EPV.php` against the
extension dir.

## What EPV checks

These are the rules we have already encountered and adapted for. There
is no test file to write for EPV — keeping CI green is the goal.

### Composer

- `composer.json` `name` field must NOT contain underscores. We use
  `avathar/bbguildwow` (no separator). Hyphens are allowed by EPV but
  break PHP's namespace rules; underscores break EPV. No-separator is
  the only intersection.
- `name`, `type`, `description`, `license` must all be present.

### Directory structure

- `license.txt` must be present at the extension root and >99% similar
  to GPL-2.0.
- `composer.json` must be at the same path level as the namespace
  declared in `name`.
- The "Packaging structure doesn't meet the extension DB policies" Error
  is non-fatal (Error level, not Fatal); EPV exits 1 only on Fatals.

### Event docblocks

For every `$dispatcher->trigger_event(...)` call, the docblock above the
`$vars = array(...)` line must contain, in this order:

```php
/**
 * Description (required).
 *
 * @event vendor.name.event_name
 * @var type varname Description
 * @var type varname Description
 * @since version  (must come AFTER all @var lines)
 */
```

EPV scans upward from the dispatch line; if it sees `*/` then `@var`
before finding `@since`, it throws Fatal `Found '@var' information
after '@since'`. Practical implication: always put `@since` last.

### Event names

- Each `@event` name must be globally unique within the extension.
  We've split `acp_editguild_*` into `acp_addguild_*` + `acp_editguild_*`
  for this reason.
- Names must start with `vendor.namespace.` (lowercase, dots). Mismatches
  vs the composer name produce a Notice (non-fatal).

### Service names

- Service IDs in `config/services.yml` must start with the vendor
  namespace (e.g. `avathar.bbguildwow.foo`). Mismatch is a Warning.
- Reserved prefixes: `phpbb.`, `core.` (Fatal if used).

### Banned PHP functions

EPV flags these in extension code:

| Function | EPV level | Fix |
|---|---|---|
| `unserialize` | Error | use `json_decode(..., true)` for plugin-local data |
| `eval`, `exec`, `shell_exec`, `system`, `passthru` | Fatal/Error | rewrite |
| `die`, `htmlspecialchars`, `addslashes` | Error | use phpBB helpers |
| `mysql_*`, `mysqli_*`, `pg_*` etc. | Error | use `$db->sql_*` (DBAL) |
| `var_dump`, `print_r`, `printf` | Error | template system |
| `include_once`, `require_once` | Warning | autoload instead |

### SQL queries — known false positives

`epv_test_validate_sql_queries.php` matches the regex
`/WHERE[^;\$]+[=<>]+[^;]+("|') \. \$/mU` and warns unless the same line
contains one of its whitelisted keywords: `sql_in_set`, `sql_escape`,
`sql_bit_and`, `get_visibility_sql`, `get_sql_where`,
`get_forums_visibility_sql`, `ORDER BY`, `ORDER_BY`.

**`(int)` is not on that whitelist.** Every correctly-cast integer query in
this extension therefore warns. Triaged 26/08/2026 — all 34 sites are false
positives; each interpolated value is an explicit `(int)` cast or an
`int`-typed parameter:

| File | Sites | Guard |
|---|---|---|
| `controller/achievement_controller.php` | 4 | `(int)` cast at method entry (`:82`, `:97-98`, `:183-184`) |
| `controller/asset_controller.php` | 2 | `(int)` cast at entry (`:56`, `:79`) |
| `controller/portrait_controller.php` | 14 | all inside `do_sync_*(int $guild_id)` private methods |
| `event/listener.php` | 5 | `(int)` casts at `:114`, `:235`, `:314` |
| `game/wow_api.php` | 6 | `int $guild_id` params, `(int)` at `:843`, `:1125` (both equipment and stat DELETEs share the `:843` cast) |
| `model/achievement.php` | 3 | `(int)` at `:1001`, `:1103`; `int $achievement_id` param |

Defence in depth: every route in `config/routing.yml` constrains its id
parameter to `\d+`, so a non-numeric value never reaches a controller.

**Do not rewrite these to silence EPV.** Wrapping already-safe integers in
`sql_escape()` would add a pointless string cast and make the intent less
clear, not more. The warnings are expected output; re-triage only if the
count changes.

One cosmetic wart worth knowing about: `model/achievement.php:1117` quotes
an int into a string comparison (`att_value = '" . $achievement_id . "'`).
Not injectable, but it forces a string comparison against a numeric column.

A 34th warning existed until 26/08/2026 in `event/listener.php`, from a dead
`$sql` assignment that was overwritten four lines later (an abandoned draft
with `INNER JOIN ... ON 1=0` and an unformatted `%s`). It was deleted rather
than suppressed.

### Languages

- Each language file must be valid PHP returning an array.
- Missing keys across languages produce Warnings (non-fatal but visible).

### Images

- All bundled PNGs/JPEGs must have ICC profiles stripped (the framework
  also has a separate `Check image ICC profiles` step that enforces this).

## When to revisit

EPV's rules evolve. Re-read the EPV `master` branch source after every
phpBB minor release and adjust if new fatals are introduced. The
relevant validators live in
`https://github.com/phpbb/epv/tree/master/src/Tests/Tests`.
