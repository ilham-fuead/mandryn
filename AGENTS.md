# AGENTS.md

Guidance for AI coding agents (Cursor, Codex, Claude Code, MiniMax Code)
working on this repository. Read this **before** editing any code.

## What this repo is

`mandryn/mandryn` — a PHP component library (PHP >= 8.3.6, MIT) providing
database access, recordset paging (`UniversalPaging`), HTTP helpers, and form
controls. MySQL/MariaDB via `mysqli` + `mysqlnd`. It also ships an MCP stdio
server (`bin/mandryn-mcp`) that exposes the DB/paging/fetch capabilities as
tools for AI agents.

## Quick start (this machine)

```powershell
# Toolchain (no `php`/`composer` on PATH on Windows)
$php = 'D:\laragon\bin\php\php-8.4.24-nts-Win32-vs17-x64\php.exe'
& $php D:\laragon\bin\composer\composer.phar install
& $php D:\laragon\bin\composer\composer.phar test
& $php D:\laragon\bin\composer\composer.phar analyse
```

PowerShell 5.1: no `&&`/`||`; chain with `;` and check `$LASTEXITCODE`.
Preserve file line endings (CRLF/LF) when editing.

## Commands

```bash
composer install          # install dependencies
composer test             # PHPUnit suite (DB-free)
composer analyse          # PHPStan: level 6 (modern) + baselined legacy
```

The real PHP on this machine: `D:\laragon\bin\php\php-8.4.24-nts-Win32-vs17-x64\php.exe`
(no `php` on PATH). Composer: `D:\laragon\bin\composer\composer.phar`, run as
`php D:\laragon\bin\composer\composer.phar ...`.

## Architecture map

| Path | Layer | Autoload |
|---|---|---|
| `src/db/` | Modern namespaced DB layer: `Query` (SQL builder), `DBQueryi` (PDO executor), `constant/` (type-safe constants) | PSR-4 `Mandryn\db\` |
| `src/http/` | Modern HTTP: `ConcurrentRequest` (Guzzle pool), `HttpUrl` | PSR-4 `Mandryn\http\` |
| `src/mcp/` | MCP tool layer: JSON-RPC `Server`, `ToolRegistry`, `SqlGuard`, `Tools/*`, `Connection/*` | PSR-4 `Mandryn\Mcp\` |
| `src/mandryn_v1/` | Legacy layer: `UniversalPaging`, `DBQuery`, `DBCommand`, form controls, `Login`/`User` | classmap, **global namespace** |
| `bin/mandryn-mcp` | MCP stdio server entry point | composer `bin` |

Docs: `docs/architecture.md` (class map), `docs/UniversalPaging.md` (paging
manual), `docs/MCP.md` (MCP guide). Specs/plans: `docs/superpowers/`.

## Invariants (never break these)

1. **Tests stay DB-free.** `composer test` must pass with no MySQL server.
   Tests use deterministic fakes (see `tests/Mcp/FakeDbConnection.php`).
   The only exception is `tests/Mcp/UniversalPagingIntegrationTest.php`,
   which skips unless `MANDYRN_TEST_DB_HOST` is set.
2. **PHP >= 8.3.6.** No syntax or features newer than 8.3; no implicit
   nullable parameter types (deprecated in 8.4).
3. **No new classes in the global namespace.** New code goes under
   `Mandryn\*` namespaces. `src/mandryn_v1` is frozen legacy; only fix bugs
   there.
4. **SQL values never interpolated raw.** Modern `Query` builder or
   prepared statements; `SqlGuard` classifies statements and gates writes.
5. **Behaviour changes ship with a test**, and `composer test` +
   `composer analyse` stay green.

## Testing discipline

- Add/modify the test **first** for bug fixes (TDD) where practical.
- Use deterministic fakes (`tests/Mcp/FakeDbConnection.php`); never hit a live
  DB in unit tests.
- The integration test is the only DB-touching test and is opt-in via env var.
- A failing test or analysis error blocks the PR.

## Security invariants

- **No secrets** in code or commits; use env/config; never log tokens/keys.
- **SQL injection:** never interpolate values; builder or prepared statements.
- **MCP stdout is the protocol channel** — never echo/var_dump/print in
  `src/mcp/`. Errors become `isError` tool results or exceptions.
- **`SqlGuard`** is the single gate for read vs write; add keywords there, not
  in tools.
- **Writes require `--allow-writes`**; default is read-only.

## Known traps

- **`DBQuery::runSQL_Query()` calls `exit()` on failed queries** (DEV mode).
  Anything that runs arbitrary SQL against a live DB must NOT use the legacy
  `DBQuery` error path in a long-running process — the MCP layer validates
  SQL on its own connection before touching `UniversalPaging`.
- **`DBQuery::MAX_TOTAL_INSTANCE = 3`** — a hard cap on simultaneous legacy
  connections; keep usage short-lived.
- **`Mandryn\db\DBQueryi` prints PDO errors to stdout** and hardcodes port
  3306. Never use it where stdout is a protocol channel (the MCP server).
- **MCP server stdout is the protocol.** In `src/mcp/`, never print/echo/
  var_dump. Errors become `isError` tool results or exceptions.
- **`UniversalPaging` has no bound-parameter support.** `mandryn_page`
  escapes params with `mysqli_real_escape_string` instead; positional `?`
  only, no named placeholders, no CTEs, no own LIMIT in the SQL.
- **Implicit nullable params** are deprecated in 8.4 — always declare types.
- **PowerShell 5.1** has no `&&`/`||`; use `;` and check `$LASTEXITCODE`.

## Working on the MCP layer

- Tools implement `Mandryn\Mcp\Tool`; wire new tools in `bin/mandryn-mcp`.
- DB access goes through `Mandryn\Mcp\Connection\DbConnection` (seam for
  DB-free tests); the real implementation is `MysqliDbConnection`.
- `SqlGuard::assertAllowed()` returns `read`|`write` and enforces the
  `--allow-writes` gate. Add new read/write keywords there, not in tools.
- MCP wire format: newline-delimited JSON-RPC 2.0, results wrapped in
  `{"content":[{"type":"text","text":"<json>"}]}`, errors as
  `isError: true`. Objects must encode as `{}` not `[]`.

## Release / contribution flow

- Branch from `master`: `feat/<slug>`, `fix/<slug>`, `chore/<slug>`.
- Commit messages: Conventional Commits (`feat(scope): summary`).
- Keep tests + analysis green; open a PR (see `CONTRIBUTING.md`).
- Update `CHANGELOG.md` for notable changes.
- Never force-push `master`; never commit secrets; never `--no-verify`.

## When in doubt

- Search the codebase (`grep`/`glob`) before assuming a symbol doesn't exist.
- Read the relevant file before editing.
- Run `composer test` + `composer analyse` before declaring done.
- Ask before irreversible/public actions (publishing, deleting tags, force-push).
- Significant decisions → `docs/adr/NNNN-<slug>.md`.
