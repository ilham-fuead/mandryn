# mandryn

**Matured & Agile Norms and DRY Approach Indefinitely (n<sup>th</sup>)** — a PHP component library for database access, recordset paging, request input handling, and form controls.

[![tests](https://github.com/ilham-fuead/mandryn/actions/workflows/tests.yml/badge.svg)](https://github.com/ilham-fuead/mandryn/actions/workflows/tests.yml)
[![phpstan](https://img.shields.io/badge/phpstan-level%206%20%2B%203-blue.svg)](https://phpstan.org)
[![license](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![php](https://img.shields.io/badge/php-%3E%3D8.3.6-777bb4.svg)](composer.json)
[![packagist](https://img.shields.io/packagist/v/mandryn/mandryn.svg)](https://packagist.org/packages/mandryn/mandryn)

## What problems it solves

- **Paging large recordsets** without writing `LIMIT`/`COUNT(*)` boilerplate — `UniversalPaging` handles page maths, slicing, and optional in-memory caching.
- **Building SQL safely** with a typed query builder (`Mandryn\db\Query`) instead of string concatenation.
- **Running DB queries** through `DBQuery`/`DBQueryi` (mysqli + PDO) with prepared-statement row hydration.
- **Validating request input** with `MagicInput`/`MagicObject` (rules, defaults, JSON serialisation).
- **Parallel HTTP requests** via `ConcurrentRequest` (Guzzle pool).
- **Exposing DB/paging to AI agents** through the `bin/mandryn-mcp` MCP stdio server.

## Requirements

- PHP **8.3.6** or newer, with the `mysqli` and `mysqlnd` extensions
- MySQL or MariaDB for the database and paging components

## Installation

```bash
composer require mandryn/mandryn:^6.0
```

## Modules at a glance

| Module | Namespace | Key classes | Purpose |
|---|---|---|---|
| Database (modern) | `Mandryn\db` | `Query`, `DBQueryi` | SQL string building, low-level mysqli/PDO access, prepared-statement hydration |
| Constants | `Mandryn\db\constant` | `QueryType`, `DataType`, `ConditionType`, `AppenderOperator`, `SqlStringType`, `EngineType` | Type-safe constants for the query builders |
| HTTP | `Mandryn\http` | `ConcurrentRequest`, `HttpUrl`, `MagicInput` | Parallel HTTP requests, URL helpers, decoupled input validation |
| MCP | `Mandryn\Mcp` | `Server`, `ToolRegistry`, `SqlGuard`, `Tools\*`, `Connection\*` | MCP stdio server exposing DB/paging/fetch as agent tools |
| Database (legacy) | *(global)* | `DBQuery`, `DBCommand`, `SQLQuery`, `DBQueryEZ`, `SQLQueryES` | Older-generation database convenience wrappers |
| Paging (legacy) | *(global)* | `UniversalPaging`, `PagingInfo`, `IPagingType`, `Pagination`, `Sorting`, `Searching` | Recordset paging and list controls |
| Input/helpers (legacy) | *(global)* | `MagicInput`, `MagicObject`, `Field`, `FilterDefinition`, `TextLiteral`, `Utilities` | Request input, validation, array/object helpers |
| Auth (legacy) | *(global)* | `Login`, `User` | Authentication and user loading |
| Forms (legacy) | *(global)* | `Checkbox`, `Radio`, `RadioCheckBox`, `Select` | HTML form controls |

Classes under `src/mandryn_v1/` are autoloaded into the **global namespace** (historical convention); everything under `src/db/`, `src/http/`, and `src/mcp/` is namespaced. See [docs/architecture.md](docs/architecture.md) for the full class map.

## Quick start

### 1. Build SQL with the query builder

```php
use Mandryn\db\Query;
use Mandryn\db\constant\QueryType;

$q = new Query();
$q->setTable('users');
$q->addColumn('id, name, email');
$q->addCondition('status', QueryType::EQUAL, 'active');
$sql = $q->getQueryString();
```

### 2. Page a recordset

```php
require __DIR__ . '/vendor/autoload.php';

use Mandryn\db\DBQuery;
use Mandryn\mandryn_v1\UniversalPaging;
use Mandryn\mandryn_v1\IPagingType;

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
$pager->setSQLStatement('SELECT id, name FROM users ORDER BY id', []);
$pager->setPagingProperty(IPagingType::AUTO_VIRTUAL, 10);
$pager->startPaging(1);
```

### 3. Parallel HTTP requests

```php
use Mandryn\http\ConcurrentRequest;

$cr = new ConcurrentRequest();
$cr->addRequest('https://api.example.com/a');
$cr->addRequest('https://api.example.com/b');
$results = $cr->execute();
```

### 4. MCP server (for AI agents)

`bin/mandryn-mcp` exposes Mandryn's database, paging, and HTTP capabilities as MCP tools:

| Tool | Purpose |
|---|---|
| `mandryn_schema` | Introspect tables and columns |
| `mandryn_query` | Run a single SQL statement (read-only unless `--allow-writes`) |
| `mandryn_page` | Page a SELECT via `UniversalPaging` |
| `mandryn_fetch` | Fetch an http/https URL |

Read-only by default; write statements require `--allow-writes`. See [docs/MCP.md](docs/MCP.md) for configuration and client setup.

## Paging in one page

`UniversalPaging` is an abstract class: extend it, implement two hooks, and it handles the page maths, `LIMIT` slicing, and optional temp-table caching.

### Paging modes at a glance

| Mode | Counts rows? | Shape | Use it for |
|---|---|---|---|
| `AUTO_VIRTUAL` | Yes | Walks every page in one call | Batch jobs, CLI exports |
| `MANUAL` | No (caller supplies totals) | One page per call | Web endpoints |
| `MANUAL` + blind | No | One page per call, stops on an empty result | Large append-only tables, audit trails |
| `AUTO_VIRTUAL` + memory table | Yes | Temp `MEMORY` table | Repeated reads of the same set |

Blind mode is the one to reach for when the data set is large and nobody has rights to tune the database: there is no `COUNT(*)`, and each page is a bounded `LIMIT` the existing index already serves.

Full details, caveats, and the complete API surface live in **[docs/UniversalPaging.md](docs/UniversalPaging.md)**. A runnable JSON endpoint lives in [`examples/paging-manual.php`](examples/paging-manual.php), with a Vue 3 pager in [`examples/UserPager.vue`](examples/UserPager.vue).

## Running the tests

```bash
composer install
composer test      # PHPUnit suite (DB-free)
composer analyse   # PHPStan: level 6 (modern) + level 3 (legacy)
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