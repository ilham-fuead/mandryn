# Migration guide

## Legacy global-namespace classes

The classes under `src/mandryn_v1/` are autoloaded into the **global namespace**
for backwards compatibility. They remain supported but are considered frozen:
new code should use the namespaced equivalents under `Mandryn\*`.

| Legacy (global) | Modern (namespaced) | Notes |
|---|---|---|
| `DBQuery` / `DBCommand` | `Mandryn\db\DBQueryi` | PDO-backed, prepared statements, no `exit()` on error |
| `MagicObject` | `Mandryn\db\MagicObject` | Typed, bug-fixed, `fromArray`/`fromRow`, JSON serialisation |
| `MagicInput` | `Mandryn\http\MagicInput` | Decoupled, validation-only, extends `MagicObject` |
| `UniversalPaging` | (no direct replacement) | Still the only pager; now accepts optional positional params |
| Form controls (`Checkbox`, `Radio`, ...) | (no modern replacement yet) | Legacy only |

## Moving from `DBQuery` to `DBQueryi`

```php
// Legacy
$db = new DBQuery($host, $user, $pass, $name);
$db->setSQL_Statement('SELECT * FROM users WHERE id = 1');
$db->runSQL_Query();
$result = $db->getQueryResult();

// Modern
use Mandryn\db\DBQueryi;
use Mandryn\db\Query;

$q = new Query();
// ... build query ...
$db = new DBQueryi($host, $user, $pass, $name, 3306);
$db->setQuery($q);
foreach ($db->getCustomSqlRecordset() as $row) { ... }
```

## Passing parameters

- `UniversalPaging::setSQLStatement($sql, [$p1, $p2])` now accepts positional
  `?` params (escaped via the connection).
- `mandryn_page` (MCP) continues to use `?` placeholders with a `params`
  array; named placeholders are not supported.

## Notes

- `DBQuery::runSQL_Query()` no longer calls `exit()` (as of 6.0.4); it
  throws instead.
- `DBQueryi` no longer prints PDO errors to stdout (as of 6.0.4); it throws.
- The legacy layer is PHPStan level 3 clean.