# Security Policy

## Reporting a vulnerability

Please **do not** open a public issue for security problems. Email the
maintainer privately:

- ilham.fuead@gmail.com

Include a description, reproduction steps, and the affected version. You will
receive an acknowledgement within 7 days.

## Supported versions

| Version | Supported |
|---|---|
| 6.x | yes |
| 5.x | security fixes only |
| < 5 | no |

## Security notes

- `mandryn_page` (MCP) is always read-only, even under `--allow-writes`.
- Write statements require the `--allow-writes` flag on `bin/mandryn-mcp`.
- `SqlGuard` is the single gate for read vs write classification.
- Never interpolate SQL values raw — use the `Query` builder or prepared
  statements (`DBQueryi`).
