# ADR 0001 — Bound parameters in UniversalPaging

- **Status:** Accepted (design)
- **Date:** 2026-11-26
- **Context:** `UniversalPaging` has no bound-parameter support. Callers (and
  the MCP `mandryn_page` tool) interpolate `?` placeholders via
  `mysqli_real_escape_string` before handing SQL to paging. This is the
  single biggest security footgun in the legacy layer and blocks using paging
  in long-running/agent contexts safely.

## Decision

1. **Backwards-compatible API.** Add an optional params array to
   `setSQLStatement(string $sql, array $params = [])`. Existing callers that
   pass only SQL keep working unchanged. Params are positional `?` only;
   named placeholders remain unsupported (consistent with `mandryn_page`).

2. **Escaping at the connection, not in paging.** `UniversalPaging` stores the
   params and escapes them lazily using the `mysqli` link it already holds
   (`mysqli_real_escape_string`), producing a fully-interpolated SQL string
   before any query runs. No prepared statements are introduced in this
   iteration — the legacy `DBQuery`/`mysqli` stack has no prepared-statement
   support and rewriting it is out of scope.

3. **Single source of truth.** `PageTool::interpolateParams()` is refactored
   to delegate to the new `UniversalPaging` helper so escaping logic lives in
   one place. The MCP tool continues to validate param counts and reject
   named placeholders before reaching paging.

4. **Future work.** A true prepared-statement path requires migrating
   `DBQuery` to `mysqli_prepare`/`mysqli_stmt_*`. That is tracked as a
   separate ADR (0002) and is not part of this change.

## Consequences

- Positive: callers can pass params instead of interpolating by hand; one
  escaping implementation; no breaking API change.
- Negative: still string-interpolated (escaped, not bound) — SQL injection
  risk reduced but not eliminated; complex types (arrays, objects) remain
  unsupported.
- Neutral: `AGENTS.md` "Known traps" entry updated to reflect the new
  optional params support.
