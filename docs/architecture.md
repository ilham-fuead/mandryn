# Mandryn Architecture Map

A class-by-class map of the library. `docs/UniversalPaging.md` documents the
paging behaviour in depth; `docs/MCP.md` documents the MCP tool layer.

## Modern DB layer — `Mandryn\db\` (`src/db/`)

| Class | Role |
|---|---|
| `Query` | SQL string builder for CREATE/SELECT/INSERT/UPDATE/DELETE. Emits `SQL_STRING` (escaped) or `PREPARE_STATEMENT` (named placeholders) forms. Guards against unscoped writes and unescaped values. |
| `DBQueryi` | PDO-based executor for `Query`. Note: hardcodes port 3306 and prints PDO errors to stdout — do not use where stdout is a protocol channel. |
| `MagicObject` | Lightweight dynamic object: on-the-fly properties, row hydration (`fromArray`/`fromRow`), column-name mapping, JSON serialisation. Pairs with `DBQueryi::getCustomSqlObjects()` and `Query`. |
| `constant/QueryType` | String constants: `CREATE`, `SELECT`, `INSERT`, `UPDATE`, `DELETE`. |
| `constant/SqlStringType` | `SQL_STRING` (1) and `PREPARE_STATEMENT` (2). |
| `constant/DataType` | Integer constants: `NULL`(0), `INT`(1), `STR`(2), `LOB`(3). |
| `constant/ConditionType` | String operators: `=`, `!=`, `>`, `>=`, `<`, `<=`, `IS NULL`, `IS NOT NULL`, ` LIKE `. |
| `constant/AppenderOperator` | `NONE_OPR`, `AND_OPR`, `OR_OPR`. |
| `constant/EngineType` | MySQL storage engines (`MyISAM`, `InnoDB`, …). |

## HTTP layer — `Mandryn\http\` (`src/http/`)

| Class | Role |
|---|---|
| `HttpUrl` | URL plus query-parameter array, with status/data slots used by `ConcurrentRequest`. |
| `MagicInput` | Modern, decoupled input validator: validates GET/POST/JSON/header inputs against a definition, removes undefined keys. Extends `MagicObject`. |
| `ConcurrentRequest` | Guzzle `Pool` wrapper for parallel GET requests. Exposes neither status codes nor timeouts, so it is a library feature rather than the basis of the MCP fetch tool. |

## MCP layer — `Mandryn\Mcp\` (`src/mcp/`)

| Class | Role |
|---|---|
| `Server` | Newline-delimited JSON-RPC 2.0 stdio loop (`initialize`, `ping`, `tools/list`, `tools/call`). |
| `ToolRegistry` | name → `Tool` map. |
| `Tool` | Interface implemented by every tool: `name()`, `description()`, `inputSchema()`, `call()`. |
| `SqlGuard` | Strips comments, enforces single-statement, classifies `read`/`write`, gates writes behind `--allow-writes`. |
| `Connection\DbConfig` | Value object: host, port, user, password, name. |
| `Connection\DbConnection` | Interface — the DB-free test seam. |
| `Connection\MysqliDbConnection` | Real implementation (mysqli + prepared statements). |
| `Connection\UnconfiguredDbConnection` | Fails with an actionable message when no DB config was supplied. |
| `Tools\QueryTool` | `mandryn_query` — read/write-gated SQL with row cap. |
| `Tools\PageTool` | `mandryn_page` — UniversalPaging MANUAL mode, always read-only. |
| `Tools\SchemaTool` | `mandryn_schema` — table/column introspection. |
| `Tools\FetchTool` | `mandryn_fetch` — bounded HTTP GET via `HttpUrl` + Guzzle. |

## Legacy layer — global namespace (`src/mandryn_v1/`)

| Class | Role |
|---|---|
| `UniversalPaging` (+ `PagingInfo`, `IPagingType`) | Abstract recordset pager; AUTO_VIRTUAL and MANUAL modes, optional memory table and blind mode. |
| `DBQuery`, `DB`, `ConnectionDetail` | mysqli wrapper. `DBQuery` caps concurrent instances at 3 and `exit()`s on failed queries in DEV mode. |
| `DBCommand`, `DBQueryEZ`, `SQLQuery`, `SQLQueryES` | Older-generation DB convenience wrappers. |
| `MagicInput`, `MagicObject`, `Field`, `FilterDefinition`, `TextLiteral`, `Utilities`, `MixedDataTypeContainer` | Request input, validation, and array/object helpers. |
| `Pagination`, `Sorting`, `Searching` | List control state. |
| `Login`, `User` | Authentication and user loading. |
| `Checkbox`, `Radio`, `RadioCheckBox`, `Select` | HTML form controls. |

These legacy classes autoload into the **global namespace** (the historical
convention); all new code must be namespaced under `Mandryn\*`.
