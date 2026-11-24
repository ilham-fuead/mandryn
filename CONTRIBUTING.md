# Contributing to Mandryn

Thanks for contributing. This file covers both human and AI-agent
contributors; agents should also read `AGENTS.md` at the repo root.

## Setup

```bash
composer install   # installs dependencies
composer test      # runs the PHPUnit suite (no database required)
composer analyse   # runs PHPStan (level 6 on modern code, baselined legacy)
```

## Flow

1. Fork the repository and branch from `master`. Use a short descriptive
   branch name (`feat/...`, `fix/...`, `chore/...`).
2. Make your change. Add or update a test for every behavioural change — the
   test suite is deliberately DB-free, so write tests against deterministic
   fakes rather than a live MySQL server.
3. Run `composer test` and `composer analyse`; both must be green.
4. Open a pull request describing the change and its motivation.

## Conventions

- **Namespaces:** new code lives under `Mandryn\*`. The `src/mandryn_v1`
  directory is frozen legacy code autoloaded into the global namespace; only
  fix bugs there, do not add features or new classes.
- **SQL safety:** never interpolate user input into SQL. Use the `Query`
  builder or prepared statements. In the MCP layer, classify every statement
  through `Mandryn\Mcp\SqlGuard`.
- **PHP version:** target PHP 8.3.6+. Do not use implicit nullable parameter
  types (deprecated in 8.4) or features newer than 8.3.
- **MCP stdout discipline:** `bin/mandryn-mcp` writes the MCP protocol to
  stdout. No code on that path may print, echo, or var_dump.

## Reporting security issues

Please report security issues privately to the maintainer rather than in a
public issue.
