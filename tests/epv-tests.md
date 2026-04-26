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
