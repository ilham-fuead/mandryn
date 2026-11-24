# mandryn

**Matured & Agile Norms and DRY Approach Indefinitely (n<sup>th</sup>)** — a PHP component library for database access, recordset paging, request input handling, and form controls.

[![tests](https://github.com/ilham-fuead/mandryn/actions/workflows/tests.yml/badge.svg)](https://github.com/ilham-fuead/mandryn/actions/workflows/tests.yml)
[![license](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![php](https://img.shields.io/badge/php-%3E%3D8.3.6-777bb4.svg)](composer.json)

## Requirements

- PHP **8.3.6** or newer, with the `mysqli` and `mysqlnd` extensions
- MySQL or MariaDB for the database and paging components

## Installation

```bash
composer require mandryn/mandryn:^6.0
```

## What's inside

| Namespace | Classes | Purpose |
|---|---|---|
| `Mandryn\db` | `Query`, `DBQueryi`, `MagicObject` | SQL string building, PDO-backed executor (prepared statements), dynamic row object |
| `Mandryn\db\constant` | `QueryType`, `SqlStringType`, `DataType`, `ConditionType`, `AppenderOperator`, `EngineType` | Type-safe constants for the query builders |
| `Mandryn\http` | `ConcurrentRequest`, `HttpUrl`, `MagicInput` | Parallel HTTP requests, URL helpers, decoupled input validator |
| `Mandryn\Mcp` | `Server`, `ToolRegistry`, `SqlGuard`, `Tools\*`, `Connection\*` | MCP stdio server exposing DB/paging/fetch as agent tools |
| *(global)* | `DBQuery`, `DBCommand`, `SQLQuery`, `DBQueryEZ`, `SQLQueryES` | Older-generation database convenience wrappers |
| *(global)* | `UniversalPaging`, `PagingInfo`, `IPagingType`, `Pagination`, `Sorting`, `Searching` | Recordset paging and list controls |
| *(global)* | `MagicInput`, `MagicObject`, `Field`, `FilterDefinition`, `TextLiteral`, `Utilities` | Request input, validation, and array/object helpers |
| *(global)* | `Login`, `User` | Authentication and user loading |
| *(global)* | `Checkbox`, `Radio`, `RadioCheckBox`, `Select` | HTML form controls |

Classes under `src/mandryn_v1/` are autoloaded into the **global namespace** (that is the historical convention this library ships with); everything under `src/db/`, `src/http/`, and `src/mcp/` is namespaced. The MCP server runs from `bin/mandryn-mcp`. See [docs/architecture.md](docs/architecture.md) for the full class map.

## Paging in one page

`UniversalPaging` is an abstract class: extend it, implement two hooks, and it handles the page maths, `LIMIT` slicing, and optional temp-table caching.

```php
require __DIR__ . '/vendor/autoload.php';

class UserPager extends UniversalPaging
{
    protected function performTaskOnEachPage($DBQueryObj, $startRowIndex, $lastRowIndex)
    {
        while ($row = mysqli_fetch_assoc($DBQueryObj->getQueryResult())) {
            echo $row['name'], "\n";
        }
    }

    protected function handleTaskOnNoPaging()
    {
        echo "No records.\n";
    }
}

$pager = new UserPager(new DBQuery('localhost', 'user', 'pass', 'mydb'));
$pager->setSQLStatement('SELECT id, name FROM users ORDER BY id');
$pager->setPagingProperty(IPagingType::AUTO_VIRTUAL, 10);
$pager->startPaging(1);
```

### Paging modes at a glance

| Mode | Counts rows? | Shape | Use it for |
|---|---|---|---|
| `AUTO_VIRTUAL` | Yes | Walks every page in one call | Batch jobs, CLI exports |
| `MANUAL` | No (caller supplies totals) | One page per call | Web endpoints |
| `MANUAL` + blind | No | One page per call, stops on an empty result | Large append-only tables, audit trails |
| `AUTO_VIRTUAL` + memory table | Yes | Temp `MEMORY` table | Repeated reads of the same set |

Blind mode is the one to reach for when the data set is large and nobody has rights to tune the database: there is no `COUNT(*)`, and each page is a bounded `LIMIT` the existing index already serves.

Full details, caveats, and the complete API surface live in **[docs/UniversalPaging.md](docs/UniversalPaging.md)**. A runnable JSON endpoint lives in [`examples/paging-manual.php`](examples/paging-manual.php), with a Vue 3 pager in [`examples/UserPager.vue`](examples/UserPager.vue).

## MCP server (for AI agents)

`bin/mandryn-mcp` exposes Mandryn's database, paging, and HTTP capabilities as
MCP tools for AI agents (MiniMax Code, Cursor, Claude Desktop, etc.):

| Tool | Purpose |
|---|---|
| `mandryn_schema` | Introspect tables and columns |
| `mandryn_query` | Run a single SQL statement (read-only unless `--allow-writes`) |
| `mandryn_page` | Page a SELECT via `UniversalPaging` |
| `mandryn_fetch` | Fetch an http/https URL |

Read-only by default; write statements require `--allow-writes`. See
**[docs/MCP.md](docs/MCP.md)** for configuration and client setup.

## Running the tests

```bash
composer install
composer test      # PHPUnit suite (DB-free)
composer analyse   # PHPStan: level 6 (modern) + baselined legacy
```

The suite is configured in `phpunit.xml.dist`; copy it to `phpunit.xml` if you need a local override. CI runs the same commands on PHP 8.3 and 8.4.

## Versioning

This library follows [Semantic Versioning](https://semver.org/). Notable changes are recorded in [CHANGELOG.md](CHANGELOG.md).

## Contributing

1. Fork the repository and branch from `master`.
2. Keep `composer test` green — add a test for any behavioural change.
3. Match the surrounding code style and keep lines length-consistent within a file.
4. Open a pull request describing the change and its motivation.

Please report security issues privately to the maintainer rather than in a public issue.

## License

Released under the [MIT License](LICENSE). Copyright (c) 2011-2026 Mandryn Team.

## Support

For enterprise training, consultation, or project proposals:

- e-mail: <ilham.fuead@gmail.com>
- mobile: 60133820757

Documentation wiki: <https://github.com/ilham-fuead/mandryn/wiki>
