# Mandryn AI-Agent Upgrade Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add repo hygiene (AGENTS.md, PHPStan, CI gate) and an MCP stdio server exposing Mandryn's DB/paging/fetch capabilities as read-only-by-default tools.

**Architecture:** Phase 1 = documentation + static analysis gate. Phase 2 = new `Mandryn\Mcp` PSR-4 namespace: a JSON-RPC 2.0 stdio `Server` dispatching to four tools behind a `Tool` interface; DB access goes through a `DbConnection` seam (mysqli impl) so every unit test stays DB-free; `mandryn_page` reuses `UniversalPaging` MANUAL mode via an injectable pager factory.

**Tech Stack:** PHP >= 8.3.6, mysqli/mysqlnd, PHPUnit 10.5, PHPStan 2.x (dev), Guzzle 7/8, symfony/process (existing).

**Spec:** `docs/superpowers/specs/2026-11-24-mandryn-ai-agent-upgrade-design.md`

## Global Constraints

- PHP >= 8.3.6 (composer.json floor, do not lower).
- Test suite MUST stay DB-free: no live MySQL needed for `composer test`; the only exception is `UniversalPagingIntegrationTest`, which skips unless `MANDYRN_TEST_DB_HOST` is set.
- New code MUST be namespaced (`Mandryn\Mcp\*`); never add classes to the global namespace.
- stdout of `bin/mandryn-mcp` is the MCP protocol channel: nothing else may ever write to stdout (no print/echo/var_dump in MCP code paths).
- SQL values: prepared statements everywhere except `mandryn_page`, where params are `mysqli_real_escape_string`-escaped (UniversalPaging has no bound-param support) — documented in tool descriptions.
- CI runs PHP 8.3 and 8.4 (existing matrix).
- Commit per task on branch `mandryn-dev`; master untouched until review.

---

## File Structure (locked)

**Create:**

| File | Responsibility |
|---|---|
| `AGENTS.md` | agent guide: commands, architecture map, invariants, traps |
| `CONTRIBUTING.md` | human+agent contribution flow |
| `docs/architecture.md` | class-by-class map of both layers |
| `docs/MCP.md` | MCP connection guide (MiniMax Code / Cursor / Claude Desktop) |
| `phpstan.neon.dist` | level 6, paths `src/db`, `src/http`, `src/mcp` |
| `phpstan.legacy.neon` | level 0, path `src/mandryn_v1`, includes baseline |
| `phpstan-legacy-baseline.neon` | generated baseline (committed) |
| `bin/mandryn-mcp` | executable entry: argv/env parsing, wiring, `Server::run()` |
| `src/mcp/Tool.php` | interface: `name()`, `description()`, `inputSchema()`, `call()` |
| `src/mcp/ToolRegistry.php` | name → Tool map, `all(): array`, `get(string): ?Tool` |
| `src/mcp/Server.php` | JSON-RPC 2.0 stdio loop |
| `src/mcp/SqlGuard.php` | statement classification + write gating |
| `src/mcp/GuardException.php` | `\RuntimeException` subclass |
| `src/mcp/DbException.php` | `\RuntimeException` subclass |
| `src/mcp/Connection/DbConfig.php` | value object: host, port, user, password, name |
| `src/mcp/Connection/DbConnection.php` | interface (the DB-free seam) |
| `src/mcp/Connection/MysqliDbConnection.php` | lazy mysqli + prepared statements |
| `src/mcp/Tools/QueryTool.php` | `mandryn_query` |
| `src/mcp/Tools/PageTool.php` | `mandryn_page` |
| `src/mcp/Tools/SchemaTool.php` | `mandryn_schema` |
| `src/mcp/Tools/FetchTool.php` | `mandryn_fetch` |
| `tests/Mcp/FakeDbConnection.php` | canned-result seam impl for tests |
| `tests/Mcp/SqlGuardTest.php` | guard unit tests |
| `tests/Mcp/ServerTest.php` | JSON-RPC framing tests (stub tools) |
| `tests/Mcp/QueryToolTest.php` | query tool tests (fake connection) |
| `tests/Mcp/SchemaToolTest.php` | schema tool tests (fake connection) |
| `tests/Mcp/PageToolTest.php` | page tool tests (fake connection + pager factory) |
| `tests/Mcp/FetchToolTest.php` | fetch tool tests (Guzzle MockHandler) |
| `tests/Mcp/UniversalPagingIntegrationTest.php` | env-gated live-DB test of the default pager factory |

**Modify:**

| File | Change |
|---|---|
| `composer.json` | PSR-4 `"Mandryn\\Mcp\\": "src/mcp/"`, `"bin": ["bin/mandryn-mcp"]`, require-dev phpstan/phpstan, script `"analyse"` |
| `composer.lock` | via `composer require --dev` |
| `.github/workflows/tests.yml` | add `static-analysis` job |

---

## Task 1: Composer + PHPStan scaffolding

**Files:**
- Modify: `composer.json` (autoload, bin, scripts, require-dev)
- Create: `phpstan.neon.dist`, `phpstan.legacy.neon`, `phpstan-legacy-baseline.neon`
- Modify: `.github/workflows/tests.yml`

**Interfaces:**
- Produces: composer script `analyse` = `phpstan analyse -c phpstan.neon.dist --no-progress && phpstan analyse -c phpstan.legacy.neon --no-progress`; phpstan binary at `vendor/bin/phpstan`; `Mandryn\Mcp\` autoload active.

- [ ] **Step 1: Add phpstan + autoload/bin wiring**

Run: `php D:\laragon\bin\composer\composer.phar require --dev phpstan/phpstan:^2.0`
Then edit `composer.json`: add to `autoload.psr-4`: `"Mandryn\\Mcp\\": "src/mcp/"`; add `"bin": ["bin/mandryn-mcp"]`; scripts: `"analyse": "phpstan analyse -c phpstan.neon.dist --no-progress && phpstan analyse -c phpstan.legacy.neon --no-progress"`.
Run: `php D:\laragon\bin\composer\composer.phar dump-autoload`

- [ ] **Step 2: Write `phpstan.neon.dist`**

```neon
parameters:
    level: 6
    paths:
        - src/db
        - src/http
        - src/mcp
    tmpDir: .phpstan-cache
```

- [ ] **Step 3: Write `phpstan.legacy.neon`**

```neon
includes:
    - phpstan-legacy-baseline.neon
parameters:
    level: 0
    paths:
        - src/mandryn_v1
    tmpDir: .phpstan-cache
```

- [ ] **Step 4: Generate the legacy baseline**

Run: `vendor/bin/phpstan analyse -c phpstan.legacy.neon --generate-baseline`
Expected: creates `phpstan-legacy-baseline.neon` with include lines in `phpstan.legacy.neon`; exit 0 afterwards on re-run.

- [ ] **Step 5: Fix modern-dir findings or accept**

Run: `vendor/bin/phpstan analyse -c phpstan.neon.dist --no-progress`
Expected initially: some findings in `src/db`, `src/http`. Fix trivial ones (missing return types, unused code) in the files themselves. Do NOT weaken the config below level 6. If a finding is a genuine false positive, add a targeted `ignoreErrors` entry with a comment.

- [ ] **Step 6: Add CI job**

In `.github/workflows/tests.yml`, add after the test job:

```yaml
  static-analysis:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          coverage: none
      - run: composer install --prefer-dist --no-progress
      - run: composer validate --strict
      - run: composer analyse
```

(Adjust step names/keys to match the existing workflow file's style — read it first.)

- [ ] **Step 7: Verify + commit**

Run: `composer validate --strict` and `composer analyse`.
Commit: `git add composer.json composer.lock phpstan.neon.dist phpstan.legacy.neon phpstan-legacy-baseline.neon .github/workflows/tests.yml` (+ any src fixes) then `git commit -m "build: phpstan gates (level 6 modern, baseline legacy) + CI static-analysis job"`.

---

## Task 2: `DbConnection` seam

**Files:**
- Create: `src/mcp/Connection/DbConfig.php`, `src/mcp/Connection/DbConnection.php`, `src/mcp/Connection/MysqliDbConnection.php`, `src/mcp/DbException.php`
- Test: `tests/Mcp/FakeDbConnection.php`

**Interfaces:**
- Produces (exact signatures, later tasks depend on these):

```php
namespace Mandryn\Mcp\Connection;

final class DbConfig
{
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly string $user,
        public readonly string $password,
        public readonly string $name,
    ) {}
    public function hostForMysqli(): string; // "host" or "host:port" when port != 3306
}

interface DbConnection
{
    /** @param list<mixed> $params positional params for `?` placeholders */
    /** @return list<array<string, mixed>> */
    public function queryRows(string $sql, array $params = []): array;
    public function queryScalar(string $sql, array $params = []): mixed; // first col of first row, or null
    /** @return int affected rows */
    public function execute(string $sql, array $params = []): int;
    public function escapeLiteral(string $value): string;
    public function getConfig(): DbConfig;
}
```

`MysqliDbConnection`: lazy `mysqli_connect` on first use; `queryRows` via `mysqli_prepare`/`bind_param` (type `s` for strings, `i` for int/float-castable); `queryScalar` = first cell of `queryRows`-style single-row query; `execute` = prepared when `$params` non-empty, else `mysqli_query` (DDL path); errors become `DbException` with `mysqli_error()` message — never printed. `escapeLiteral` = `mysqli_real_escape_string`. Config injected via constructor; env fallback handled by `bin/mandryn-mcp`, not here.

- [ ] **Step 1: Write `DbConfig` + `FakeDbConnection` test double**

`DbConfig` as above. `tests/Mcp/FakeDbConnection.php`:

```php
namespace Tests\Mcp;

use Mandryn\Mcp\Connection\DbConfig;
use Mandryn\Mcp\Connection\DbConnection;

final class FakeDbConnection implements DbConnection
{
    /** @var list<array{sql: string, params: array}> */
    public array $calls = [];

    public function __construct(
        private array $rowsBySql = [],
        private mixed $scalarBySql = null,
        private int $affectedRows = 0,
    ) {}

    public function queryRows(string $sql, array $params = []): array
    {
        $this->calls[] = ['sql' => $sql, 'params' => $params];
        return $this->rowsBySql[$sql] ?? [];
    }

    public function queryScalar(string $sql, array $params = []): mixed
    {
        $this->calls[] = ['sql' => $sql, 'params' => $params];
        return $this->scalarBySql;
    }

    public function execute(string $sql, array $params = []): int
    {
        $this->calls[] = ['sql' => $sql, 'params' => $params];
        return $this->affectedRows;
    }

    public function escapeLiteral(string $value): string
    {
        return addslashes($value);
    }

    public function getConfig(): DbConfig
    {
        return new DbConfig('fake-host', 3306, 'fake-user', 'fake-pass', 'fake_db');
    }
}
```

- [ ] **Step 2: Write `MysqliDbConnection`**

Implementation per the interface contract above. No unit test at this stage (it needs a live DB); it is exercised by Task 7's env-gated integration test.

- [ ] **Step 3: Smoke check + commit**

Run: `php -l` each new file; `composer test` still green.
Commit: `git add src/mcp/Connection src/mcp/DbException.php tests/Mcp/FakeDbConnection.php` → `git commit -m "feat(mcp): DbConnection seam + mysqli implementation"`.

---

## Task 3: `SqlGuard`

**Files:**
- Create: `src/mcp/SqlGuard.php`, `src/mcp/GuardException.php`
- Test: `tests/Mcp/SqlGuardTest.php`

**Interfaces:**
- Consumes: none.
- Produces:

```php
namespace Mandryn\Mcp;

final class SqlGuard
{
    public function __construct(private bool $allowWrites = false) {}
    /** @throws GuardException */
    public function assertAllowed(string $sql): string; // returns 'read'|'write' (lowercase)
}
```

Rules (all inside `assertAllowed`, in order):
1. Strip leading whitespace and leading comments: repeat while the head matches `/* ... */`, `-- ...\n`, or `# ...\n`.
2. Empty remainder → `GuardException('empty SQL statement')`.
3. Statement count: allow at most one trailing `;`. Walk the string tracking `'`/`"`/backtick quoting and `\` escapes; a `;` outside quotes with any non-whitespace after it → `GuardException('multiple SQL statements are not allowed')`.
4. First word outside quotes, uppercased: READ set = `SELECT, SHOW, DESCRIBE, DESC, EXPLAIN, WITH`; anything else = write.
5. Write + `allowWrites === false` → `GuardException('write statement "<word>" blocked: restart with --allow-writes to permit writes')`.
6. Return `'read'` or `'write'`.

- [ ] **Step 1: Write the failing tests**

`tests/Mcp/SqlGuardTest.php` — cover: plain SELECT passes as read; trailing `;` ok; `select /* inline */ 1` passes; leading `-- c\nSELECT 1` passes; leading `# c\nSELECT 1` passes; `SELECT 1; DROP TABLE x` throws (multi-statement); `SELECT ';'` passes (semicolon in literal); `SELECT ";"` passes; `` SELECT `;` `` passes; DELETE/INSERT/UPDATE/CREATE/DROP/ALTER/TRUNCATE blocked without flag and classified write with flag; `WITH cte AS (...) SELECT` = read; `WITH cte AS (...) DELETE` = write; empty/whitespace-only throws.

- [ ] **Step 2: Run tests — expect failures**

Run: `vendor/bin/phpunit tests/Mcp/SqlGuardTest.php`
Expected: FAIL (class missing).

- [ ] **Step 3: Implement `SqlGuard` + `GuardException`**

- [ ] **Step 4: Run tests — expect pass**

- [ ] **Step 5: Commit**

`git add src/mcp/SqlGuard.php src/mcp/GuardException.php tests/Mcp/SqlGuardTest.php` → `git commit -m "feat(mcp): SqlGuard read/write classification and write gating"`.

---

## Task 4: JSON-RPC `Server` + `ToolRegistry`

**Files:**
- Create: `src/mcp/Tool.php`, `src/mcp/ToolRegistry.php`, `src/mcp/Server.php`
- Test: `tests/Mcp/ServerTest.php`

**Interfaces:**
- Consumes: none.
- Produces:

```php
namespace Mandryn\Mcp;

interface Tool
{
    public function name(): string;
    public function description(): string;
    /** @return array<string, mixed> JSON Schema for the arguments object */
    public function inputSchema(): array;
    /** @return array<string, mixed> result payload (JSON-encoded by Server) */
    /** @throws \Throwable on tool errors */
    public function call(array $arguments): array;
}

final class ToolRegistry
{
    /** @param list<Tool> $tools */
    public function __construct(array $tools);
    public function get(string $name): ?Tool;
    /** @return list<Tool> */
    public function all(): array;
}

final class Server
{
    public function __construct(
        ToolRegistry $registry,
        string $name = 'mandryn',
        string $version = '0.1.0',
        /** @var resource */ private $input = null,   // default STDIN
        /** @var resource */ private $output = null,  // default STDOUT
    ) {}
    public function run(): void;
}
```

`run()` loop: `fgets($input)`; decode JSON; skip empty lines. `id` absent → notification (handle `notifications/initialized` and `notifications/cancelled` as no-ops, reply to nothing). Responses: `{"jsonrpc":"2.0","id":<id>,"result":{...}}` or `{"jsonrpc":"2.0","id":<id>,"error":{"code":-32601,"message":"Method not found"}}` (invalid JSON → id null, code -32700). Methods: `initialize` → `{protocolVersion:"2024-11-05",capabilities:{tools:{}},serverInfo:{name,version}}`; `ping` → `{}`; `tools/list` → `{tools:[{name,description,inputSchema}]}`; `tools/call` → run `registry.get(params.name)`; missing tool → error result content; tool `\Throwable` → `result:{content:[{type:"text",text:json_encode(['error'=>$e->getMessage()])}],isError:true}`; success → `result:{content:[{type:"text",text:json_encode($tool->call($arguments), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)}]}`. Every response written as one line + `fflush`. Unknown tools also return `isError:true` with text error.

- [ ] **Step 1: Write the failing tests**

`tests/Mcp/ServerTest.php` — use `php://memory` streams, a stub tool (fixed schema, echoes args) plus a failing tool (throws). Assert: initialize result; `tools/list` contains the stub name/schema; `tools/call` success text decodes to the echoed args; `tools/call` with unknown tool → `isError:true`; `tools/call` on throwing tool → `isError:true` with message; notification (`no id`) produces no output line; unknown method → `-32601`; invalid JSON line → `-32700`.

- [ ] **Step 2: Run — expect failures**

- [ ] **Step 3: Implement `Tool`, `ToolRegistry`, `Server`**

- [ ] **Step 4: Run — expect pass**

- [ ] **Step 5: Commit**

`git add src/mcp/Tool.php src/mcp/ToolRegistry.php src/mcp/Server.php tests/Mcp/ServerTest.php` → `git commit -m "feat(mcp): JSON-RPC 2.0 stdio server and tool registry"`.

---

## Task 5: `mandryn_query` (`QueryTool`)

**Files:**
- Create: `src/mcp/Tools/QueryTool.php`
- Test: `tests/Mcp/QueryToolTest.php`

**Interfaces:**
- Consumes: `SqlGuard`, `DbConnection` (Task 2), `GuardException`.
- Produces: tool name `mandryn_query`, schema: `{sql: string (required), params: array (optional, default []), limit: integer (optional, 1..1000, default 100)}`; result: `{rows: [...], count: int, truncated: bool}` for reads, `{affected_rows: int}` for writes.

Flow: `$this->guard->assertAllowed($sql)`; if `read` and SQL has no trailing `LIMIT` (regex `/\blimit\s+\d+(\s*,\s*\d+)?\s*;?\s*$/i` after trimming) → append ` LIMIT <limit>`; `$conn->queryRows(...)`; `truncated = count($rows) === $limit`. If `write`: `$conn->execute($sql, $params)` → affected rows.

- [ ] **Step 1: Write failing tests** (`tests/Mcp/QueryToolTest.php`)

Fake connection with `rowsBySql` keyed by exact SQL. Cases: read passes with rows + count; limit auto-appended when absent; limit not appended when SQL already ends with LIMIT; limit arg respected (1000 cap enforced); write blocked by default → `GuardException`; write allowed when guard constructed with `allowWrites: true` → `affected_rows` returned; multi-statement → `GuardException`.

- [ ] **Step 2-4: TDD cycle**

- [ ] **Step 5: Commit**

`git add src/mcp/Tools/QueryTool.php tests/Mcp/QueryToolTest.php` → `git commit -m "feat(mcp): mandryn_query tool (read-only gated, row cap)"`.

---

## Task 6: `mandryn_schema` (`SchemaTool`)

**Files:**
- Create: `src/mcp/Tools/SchemaTool.php`
- Test: `tests/Mcp/SchemaToolTest.php`

**Interfaces:**
- Consumes: `DbConnection`.
- Produces: tool name `mandryn_schema`; schema: `{table: string (optional, ^[A-Za-z0-9_]+$)}`; result: `{database: string, total_tables: int, tables: [{name, columns: [{name, type, nullable, key, extra}]}]}`.

Queries (all prepared with the same optional param):
```sql
SELECT DATABASE()
SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA
  FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME, ORDINAL_POSITION
```
With `table` arg: add ` AND TABLE_NAME = ?` to the last two. Group columns by table.

- [ ] **Step 1: Write failing tests** — fake connection keyed by exact SQL strings; assert grouping, column shape, `total_tables`, `database` value; invalid `table` name (e.g. `x; DROP`) → `GuardException`/`\InvalidArgumentException`; empty DB → empty tables array.
- [ ] **Step 2-4: TDD cycle**
- [ ] **Step 5: Commit** → `git commit -m "feat(mcp): mandryn_schema tool (table/column introspection)"`.

---

## Task 7: `mandryn_page` (`PageTool`)

**Files:**
- Create: `src/mcp/Tools/PageTool.php`
- Test: `tests/Mcp/PageToolTest.php`, `tests/Mcp/UniversalPagingIntegrationTest.php`

**Interfaces:**
- Consumes: `DbConnection` (count via `queryScalar`, `escapeLiteral`, `getConfig`), `SqlGuard`, `DbConfig::hostForMysqli()`.
- Produces: tool name `mandryn_page`; schema: `{sql (required), params: array (optional, positional `?` only), page: integer (>=1, default 1), page_size: integer (1..200, default 25)}`; result: `{page, page_size, total, total_pages, rows: [...]}`.

Flow:
1. Guard with `allowWrites: false` ALWAYS (page tool is never writable, even under `--allow-writes`).
2. Trim trailing `;`. Reject SQL containing `LIMIT` (case-insensitive regex `\blimit\b`) → `GuardException('mandryn_page appends its own LIMIT...')`. Reject `WITH`-leading CTEs (`GuardException('CTEs are not supported by mandryn_page')`). Reject `:` placeholders (`'named parameters are not supported; use positional ?'`).
3. Escape params: for each param in order, replace the first `?` with `$conn->escapeLiteral((string)$param)`; param count must match `?` count.
4. Totals: `$conn->queryScalar('SELECT COUNT(*) AS total_rows FROM ( ' . $sql . ' ) AS _mandryn_count')`; if `total === 0` return empty page immediately (never touch UniversalPaging).
5. Pager seam: constructor takes `?callable $pagerFactory = null`. Default factory (uses real `UniversalPaging`):
   - `$dbq = new \DBQuery($cfg->hostForMysqli(), $cfg->user, $cfg->password, $cfg->name);`
   - `$collector = new class extends \UniversalPaging {...}` collecting rows via `mysqli_fetch_assoc($dbq->getQueryResult())` inside `performTaskOnEachPage`, and setting `$collected = []` in `handleTaskOnNoPaging`.
   - `setSQLStatement($sql); setPagingProperty(\IPagingType::MANUAL, $pageSize, false, false);` build `\PagingInfo` with `totalRow/totalPage/lastPage=0/memTableName=''` → `setPageProperty(...)`; `startPaging($page)`.
   - Returns the collected rows array.
   Tests inject `fn($cfg, $sql, $page, $pageSize) => cannedRows`.
6. Compute `total_pages = max(1, (int)ceil(total / page_size))` when total > 0 else 0; clamp `page` into `[1, total_pages]` before calling the factory; return shape above.

- [ ] **Step 1: Write failing unit tests** (`PageToolTest`, fake connection, injected pager factory capturing args): totals come from count query; page/page_size clamped and passed to factory; write SQL rejected; SQL with LIMIT rejected; named params rejected; param escaping: assert the escaped SQL passed to factory contains the escaped literal (fake escaper `addslashes` → check `O\'Brien`-style transform); `total === 0` short-circuits without invoking factory.
- [ ] **Step 2: Write env-gated integration test** (`UniversalPagingIntegrationTest`): `setUp()` checks `getenv('MANDYRN_TEST_DB_HOST')` else `markTestSkipped`. Uses a table it creates itself (`CREATE TABLE IF NOT EXISTS mandryn_mcp_test (...)`; inserts ~7 rows; page_size 3; asserts page 1 has 3 rows, page 3 has 1 row, totals = 7). This exercises the default pager factory end-to-end.
- [ ] **Step 3-5: TDD cycle for the unit tests** (integration test is skipped locally unless env set — run it in Task 10's smoke pass).
- [ ] **Step 6: Commit** → `git commit -m "feat(mcp): mandryn_page tool wrapping UniversalPaging MANUAL mode"`.

---

## Task 8: `mandryn_fetch` (`FetchTool`)

**Files:**
- Create: `src/mcp/Tools/FetchTool.php`
- Test: `tests/Mcp/FetchToolTest.php`

**Interfaces:**
- Consumes: `Mandryn\http\HttpUrl` (URL + query building), Guzzle `ClientInterface` (injectable).
- Produces: tool name `mandryn_fetch`; schema: `{url: string (required), params: object (optional)}`; result: `{status, content_type, bytes, truncated, body}`.

Flow: build `new HttpUrl($url, (array)$params)`; scheme must be `http`/`https` (via `parse_url`) else `GuardException('only http/https URLs are allowed')`. Request: `$client->request('GET', $httpUrlObj->uri, ['query' => $httpUrlObj->parametersArray, 'timeout' => 10, 'allow_redirects' => true])`. Body: `(string)$response->getBody()` truncated to 65536 bytes (`truncated` flag). Headers: content-type only. Guzzle exceptions (`ClientException`, `ConnectException`, etc.) → `DbException`? No — rethrow as `\RuntimeException` with the message so Server marks the tool call errored.

- [ ] **Step 1: Write failing tests** with `GuzzleHttp\Handler\MockHandler` + `HandlerStack` injected into the tool: 200 with small body → status/content-type/body; large body → truncated flag + 64KB cap; `ftp://` and `file://` URLs rejected; non-2xx status still returned (not exception); connect error → `\RuntimeException`.
- [ ] **Step 2-4: TDD cycle**
- [ ] **Step 5: Commit** → `git commit -m "feat(mcp): mandryn_fetch tool (HttpUrl + bounded Guzzle GET)"`.

---

## Task 9: `bin/mandryn-mcp` entry point

**Files:**
- Create: `bin/mandryn-mcp`
- Modify: none.

**Interfaces:**
- Consumes: all previous tasks.

Content: shebang `#!/usr/bin/env php`; autoload via `$GLOBALS['_composer_autoload_path']` (composer bin proxy) with fallback to `dirname(__DIR__) . '/vendor/autoload.php'` (exits with a message to stderr if missing). CLI parsing (no libraries): `--allow-writes`, `--config <file>` (JSON with host/port/user/pass/name; missing keys fall back to env `MANDYRN_DB_HOST|PORT|USER|PASS|NAME`, port default 3306). Build `DbConfig` only if host+name present; else pass `null` to tools and have DB tools throw `DbException('database not configured: set MANDYRN_DB_* or pass --config')`. Wire `SqlGuard($allowWrites)`, `MysqliDbConnection` (when configured), four tools, `ToolRegistry`, `Server`; `$server->run();`. Nothing else writes to stdout.

- [ ] **Step 1: Write the file**
- [ ] **Step 2: Lint + static analysis** — `php -l bin/mandryn-mcp`; `composer analyse`.
- [ ] **Step 3: Manual protocol smoke test (no DB)** — pipe lines into it:
```
powershell: '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{}}' | php bin/mandryn-mcp
```
Expect the initialize result line. Then a `tools/list` call. Then a `mandryn_query` call with no DB config → `isError:true` with the config message. Nothing else on stdout.
- [ ] **Step 4: Commit** → `git commit -m "feat(mcp): bin/mandryn-mcp entry point (env/--config, --allow-writes)"`.

---

## Task 10: Phase 1 documentation

**Files:**
- Create: `AGENTS.md`, `CONTRIBUTING.md`, `docs/architecture.md`, `docs/MCP.md`

**Content contracts:**
- `AGENTS.md`: commands (`composer install`, `composer test`, `composer analyse`); architecture map (modern namespaced vs legacy global); the five invariants from the spec §1.1 verbatim; trap list from spec §2.5 (DBQuery exit, instance cap, DBQueryi stdout printing) with the rule "never print/echo from library code paths reachable by the MCP server".
- `CONTRIBUTING.md`: fork/branch/test/PR flow + agent conventions pointer.
- `docs/architecture.md`: one table per layer; each class, role, deprecated status.
- `docs/MCP.md`: env vars, `--config` example, tool reference table, `--allow-writes` warning, and copy-paste `mcpServers` snippets for MiniMax Code (config.yaml custom MCP / mcp.json), Cursor (`.cursor/mcp.json`), Claude Desktop (`claude_desktop_config.json`) using `"command": "php", "args": ["D:/laragon/.../bin/mandryn-mcp"]` placeholder-style absolute paths plus the packaged `vendor/bin/mandryn-mcp` variant.

- [ ] **Step 1: Write all four files**
- [ ] **Step 2: Commit** → `git commit -m "docs: AGENTS.md, CONTRIBUTING.md, architecture map, MCP guide"`.

---

## Task 11: Final verification + live DB smoke test

- [ ] **Step 1:** `composer test` — all green, DB-free (integration test skipped when env absent).
- [ ] **Step 2:** `composer analyse` — both configs clean.
- [ ] **Step 3:** Live smoke test against Laragon MariaDB (local): set `$env:MANDYRN_DB_HOST='127.0.0.1'; $env:MANDYRN_DB_USER='root'; $env:MANDYRN_DB_PASS=''; $env:MANDYRN_DB_NAME='<existing db>'`, run `php bin/mandryn-mcp` with an initialize + `mandryn_schema` + `mandryn_query` + a blocked `DELETE` + `--allow-writes` accepted write (on a scratch table), verifying each JSON response line.
- [ ] **Step 4:** With env set, run the env-gated integration test: `$env:MANDYRN_TEST_DB_HOST='127.0.0.1'; vendor/bin/phpunit tests/Mcp/UniversalPagingIntegrationTest.php`.
- [ ] **Step 5:** `git status` review; final commit for any fixes; push `mandryn-dev`:
```
git push -u origin mandryn-dev
```
