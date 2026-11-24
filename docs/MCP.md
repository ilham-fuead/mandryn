# Mandryn MCP Server

`bin/mandryn-mcp` exposes Mandryn's database, paging, and HTTP capabilities to
AI agents as MCP tools over stdio (JSON-RPC 2.0). It ships in the `mandryn`
package (`composer bin`), so consumers get `vendor/bin/mandryn-mcp`.

## Quick start

```bash
# From the repo:
MANDYRN_DB_HOST=127.0.0.1 MANDYRN_DB_NAME=mydb MANDYRN_DB_USER=root \
  php bin/mandryn-mcp
```

The server reads newline-delimited JSON-RPC messages from stdin and writes
responses to stdout. Nothing else ever writes to stdout.

## Configuration

Database credentials come from environment variables, optionally overlaid by a
JSON config file:

| Env var | Meaning | Default |
|---|---|---|
| `MANDYRN_DB_HOST` | MySQL/MariaDB host (or `host:port`) | — (required) |
| `MANDYRN_DB_PORT` | Port | `3306` |
| `MANDYRN_DB_USER` | Username | empty |
| `MANDYRN_DB_PASS` | Password | empty |
| `MANDYRN_DB_NAME` | Database name | — (required) |

`--config <file>` reads a JSON object `{"host": ..., "port": ..., "user": ...,
"pass": ..., "name": ...}` whose keys override the corresponding env vars.
Without `MANDYRN_DB_HOST` and `MANDYRN_DB_NAME`, the server still starts, but
the DB tools return a descriptive "database not configured" error.

## Tools

| Tool | Purpose | Write access |
|---|---|---|
| `mandryn_schema` | List base tables and columns (optionally one table) | never |
| `mandryn_query` | Run a single SQL statement (prepared, row cap) | gated by `--allow-writes` |
| `mandryn_page` | Page a SELECT via UniversalPaging MANUAL mode | never |
| `mandryn_fetch` | GET an http/https URL (body capped at 64 KB) | n/a |

### Safety model

- **Read-only by default.** `INSERT`/`UPDATE`/`DELETE`/`DDL` are refused
  unless the server is started with `--allow-writes`. `mandryn_page` is always
  read-only regardless of the flag.
- **Single statement only.** `SqlGuard` rejects multi-statement SQL and
  strips leading comments before classifying.
- **Values via placeholders.** `mandryn_query` binds `?` placeholders as a
  prepared statement. `mandryn_page` escapes positional `?` params with
  `mysqli_real_escape_string` (UniversalPaging has no bound-parameter
  support); it rejects named placeholders, CTEs, and any SQL that already has
  a `LIMIT`.

### `mandryn_page` notes

- Caller supplies `page` (1-based) and `page_size` (1..200). The total row
  count is computed with a wrapped `COUNT(*)`; the actual page fetch goes
  through `UniversalPaging` in MANUAL mode.
- Because the legacy `DBQuery` error path calls `exit()`, the SQL is
  pre-validated on the MCP connection before paging runs. A malformed
  statement returns a tool error instead of terminating the server.

## Connecting an MCP client

The server is a stdio process. Point your client at `php` with
`bin/mandryn-mcp` (or `vendor/bin/mandryn-mcp` when installed as a dependency).
Use absolute paths — `php` is often absent from the PATH of desktop apps.

```json
{
  "mcpServers": {
    "mandryn": {
      "command": "php",
      "args": ["D:/laragon/bin/php/php-8.4.24-nts-Win32-vs17-x64/php.exe",
               "D:/laragon/www/mandryn/bin/mandryn-mcp"],
      "env": {
        "MANDYRN_DB_HOST": "127.0.0.1",
        "MANDYRN_DB_NAME": "mydb",
        "MANDYRN_DB_USER": "root",
        "MANDYRN_DB_PASS": ""
      }
    }
  }
}
```

Where to put it:

- **MiniMax Code / Cursor:** the MCP settings file (MiniMax Code MCP panel;
  Cursor uses `.cursor/mcp.json`) accepts the same `mcpServers` shape.
- **Claude Desktop:** `claude_desktop_config.json` in the app config directory.

To allow write statements, add `"--allow-writes"` before the script path in
`args`.
