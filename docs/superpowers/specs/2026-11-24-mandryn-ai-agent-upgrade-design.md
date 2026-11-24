# Mandryn AI-Agent Upgrade — Design

- **Date:** 2026-11-24
- **Branch:** `mandryn-dev`
- **Status:** approved by maintainer (Mohd Ilhammuddin Mohd Fuead)

## Goal

Make `mandryn/mandryn` maximally useful for AI agent development, in two phases:

1. **Phase 1 — repo hygiene:** make the repository easy for AI coding agents to
   work *on* (understand, extend, verify).
2. **Phase 2 — agent-usable tool layer:** let AI agents *use* Mandryn's database
   and paging capabilities through an MCP (Model Context Protocol) stdio server.

Decisions made with the maintainer:

- Direction: **both phases, in this order** (hygiene first, tool layer second).
- Tool layer transport: **MCP stdio (JSON-RPC 2.0)**.
- Data access posture: **read-only by default; writes require an explicit
  `--allow-writes` flag**.
- Everything lands **in this repo** (no separate package).

## Phase 1 — Repo hygiene

### 1.1 `AGENTS.md` (repo root)

A compact guide consumed by AI coding agents (Cursor, Codex, Claude Code,
MiniMax Code). Contents:

- Build/test commands: `composer install`, `composer test`,
  `vendor/bin/phpstan`.
- Architecture map: `src/db` + `src/http` = modern, namespaced (PSR-4);
  `src/mandryn_v1` = legacy, global-namespace, classmap-autoloaded.
- Invariants agents must preserve:
  1. Tests stay DB-free (deterministic fakes; never require a live MySQL).
  2. PHP >= 8.3.6.
  3. No new classes in the global namespace; new code goes into namespaced
     `Mandryn\*` namespaces.
  4. Every SQL value goes through the query builder / escaper — no string
     interpolation of user input into SQL.
  5. New behavioural code ships with a test; `composer test` must stay green.
- Known traps (see "Runtime traps" below).

### 1.2 PHPStan + CI gate

- Add `phpstan/phpstan` as a dev dependency.
- `phpstan.neon.dist`:
  - `level: 6` for `src/db/` and `src/http/` (and, once added, `src/mcp/`).
  - Legacy `src/mandryn_v1/`: `level: 0` plus a generated baseline so the gate
    is green on day one; tighten later.
- CI (`tests.yml`): add a `static-analysis` job running `composer validate
  --strict` and `vendor/bin/phpstan analyse --no-progress`.

### 1.3 `CONTRIBUTING.md`

Fork/branch/test/PR flow from the README, plus the agent-facing conventions
from `AGENTS.md`.

### 1.4 `docs/architecture.md`

Short class-by-class map of both layers: what each class does, which are
modern vs legacy, which are deprecated-but-kept, and where the paging modes
live. No rewrite of existing docs (`docs/UniversalPaging.md` stays).

## Phase 2 — MCP tool layer

### 2.1 Layout

```
bin/mandryn-mcp                  # executable entry (shebang), lists in composer "bin"
src/mcp/Server.php               # JSON-RPC 2.0 stdio loop
src/mcp/ToolRegistry.php         # tool name -> handler
src/mcp/Connection/
    DbConnection.php             # interface (adapter seam, DB-free tests)
    MysqliDbConnection.php       # mysqli + prepared statements impl
src/mcp/Tools/
    QueryTool.php                # mandryn_query
    PageTool.php                 # mandryn_page  (wraps UniversalPaging)
    SchemaTool.php               # mandryn_schema
    FetchTool.php                # mandryn_fetch  (wraps ConcurrentRequest)
src/mcp/SqlGuard.php             # statement classification + write gating
tests/Mcp/...                    # DB-free tests
docs/MCP.md                      # user/agent connection guide
```

Autoload: add `"Mandryn\\Mcp\\": "src/mcp/"` to `composer.json` PSR-4.
Composer `bin` gains `bin/mandryn-mcp`.

### 2.2 Protocol

Newline-delimited JSON-RPC 2.0 over stdin/stdout. Supported methods:
`initialize`, `notifications/initialized` (no reply), `tools/list`,
`tools/call`, `ping`. Unknown methods → JSON-RPC error. All tool results come
back as `{ "content": [ { "type": "text", "text": "<json>" } ] }`.

### 2.3 Tools

| Tool | Args | Returns | Read-only? |
|---|---|---|---|
| `mandryn_schema` | `{}` | tables + columns (SHOW TABLES + information_schema.COLUMNS) | always |
| `mandryn_query` | `sql`, `params[]`, `limit?` | rows (prepared statement) | SELECT/SHOW/DESCRIBE/EXPLAIN only unless `--allow-writes` |
| `mandryn_page` | `sql`, `params[]`, `page`, `page_size` | page rows + totals (UniversalPaging MANUAL mode) | SELECT only, always |
| `mandryn_fetch` | `url`, `params{}` | status, capped body (Mandryn `HttpUrl` + Guzzle client with timeout; `ConcurrentRequest` exposes neither status codes nor timeouts, so it stays a library feature for batch use) | n/a (HTTP) |

### 2.4 Safety model

- `SqlGuard` strips leading comments/whitespace, extracts the first keyword,
  rejects multi-statement SQL (`;` beyond a single trailing one), and
  classifies statements as READ or WRITE. WRITE tools are refused with a clear
  error unless the server was started with `--allow-writes`.
- `mandryn_page` is always read-only (no write flag changes that).
- Values travel via prepared statements (`mandryn_query`, `mandryn_schema`) or
  via `mysqli_real_escape_string` interpolation (`mandryn_page` — because
  `UniversalPaging` has no bound-parameter support).
- `mandryn_fetch`: http/https only, response body capped at 64 KB, per-request
  timeout, no redirect to file:// etc.
- Credentials come from env vars (`MANDYRN_DB_HOST|PORT|USER|PASS|NAME`) or a
  `--config <file>` JSON; never echoed in tool output. Server never logs SQL
  values to stdout (stdout is the protocol channel).
- Optional row caps: `mandryn_query` accepts a `limit` arg (default 100, max
  1000) appended when the SQL has no LIMIT.

### 2.5 Runtime traps (discovered while reading the code, handled in design)

1. **Legacy `DBQuery` calls `exit()` on failed queries in DEV mode** — would
   kill the MCP server process. Mitigation: `mandryn_page` pre-validates the
   SQL (prepared count query) on `MysqliDbConnection` before handing it to
   `UniversalPaging`; a malformed statement therefore returns a tool error
   instead of dying. Documented residual risk: an exotic server-side failure
   between validation and execution can still terminate the process; the MCP
   client can restart it.
2. **`DBQuery::MAX_TOTAL_INSTANCE = 3`** — MANUAL-mode paging with
   caller-supplied totals creates at most 1–2 short-lived instances; verified
   safe. The MCP layer does not use `DBQuery` elsewhere.
3. **`Mandryn\db\DBQueryi` prints PDO errors to stdout and hardcodes port
   3306** — stdout corruption would break the JSON-RPC stream. Mitigation:
   the MCP layer uses its own `MysqliDbConnection` (consistent with the
   library's mysqli orientation) instead of `DBQueryi`. `mandryn_fetch`
   builds URLs with Mandryn `HttpUrl` and performs the request with a
   dedicated Guzzle client (timeout + status code + body cap); it does not
   use `ConcurrentRequest`, which exposes neither status codes nor timeouts.

### 2.6 Testing

- All MCP tests stay DB-free via the `DbConnection` seam (same discipline as
  the existing `QueryTest` fake escaper).
- `SqlGuardTest`: classification, multi-statement rejection, comment-stripping
  bypass attempts.
- `ServerTest`: JSON-RPC framing — initialize handshake, `tools/list` shape,
  `tools/call` happy path, error responses, notification (no-id) handling.
- `PageToolTest`: unit-tests request validation and count-query assembly with
  a fake `DbConnection`. The `UniversalPaging` MANUAL integration is covered
  by a smoke test that runs only when `MANDYRN_TEST_DB_HOST` is set, so CI
  stays DB-free. (UniversalPaging itself constructs its own `DBQuery`
  instances, so it cannot be faked DB-free.)
- `FetchToolTest`: Guzzle `MockHandler` (no network).
- CI: run on PHP 8.3 + 8.4 as today.

## Success criteria

1. `composer test` green, DB-free, on PHP 8.3 and 8.4 (CI).
2. PHPStan level 6 clean for `src/db`, `src/http`, `src/mcp`; legacy layer
   baselined at level 0.
3. `bin/mandryn-mcp` handshakes with a standard MCP client and serves the four
   tools; `mandryn_query`/`mandryn_schema` verified against a live MySQL in a
   manual smoke test; write statements refused without `--allow-writes`.
4. `docs/MCP.md` contains copy-paste connection configs for MiniMax Code,
   Cursor, and Claude Desktop.
5. Everything committed on branch `mandryn-dev`; master untouched.

## Out of scope (deferred)

- Migrating `src/mandryn_v1` to namespaces / adding type declarations.
- Rector, code style fixer, API doc generation.
- Writes transaction batching, `mandryn_import`, SQL explain tool (EXPLAIN is
  reachable via `mandryn_query` today).
- Auth on the MCP transport itself (stdio is local; DB credentials bound the
  blast radius).
