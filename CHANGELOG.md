# Changelog

All notable changes to `mandryn/mandryn` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Historical releases before `2.0.0` are summarised rather than itemised; the full
per-commit history is available via `git log`.

## [Unreleased]

## [6.0.3] - 2026-11-24

### Fixed

- `Mandryn\http\MagicInput::toArray()` no longer infinitely recurses when an
  input definition is set (`isInputsComplied()` -> `applyInputDefinition()` was
  calling back into `toArray()`). Validation now reads the raw property array
  via `parent::toArray()`.

## [6.0.2] - 2026-11-24

### Added

- Modern namespaced `Mandryn\db\MagicObject` (typed, bug-fixed, row hydration
  via `fromArray`/`fromRow`, column-name mapping, JSON serialisation) and
  `Mandryn\http\MagicInput` (decoupled, validation-only input validator
  extending `MagicObject`). `DBQueryi` gains `getCustomSqlObjects()` for
  prepared-statement row hydration. The legacy `MagicObject::__isset` bug
  (`isset($name)` instead of `array_key_exists`) is fixed in place.

### Changed

- Dropped the unused `symfony/process` dependency (zero references in `src/`),
  shrinking the install footprint.

### Fixed

- `DBQueryi::execute()` now catches `\PDOException` instead of the
  non-existent `Mandryn\db\PDOException`.

## [6.0.1] - 2026-11-24

### Fixed

- The `composer analyse` script chained two PHPStan runs with `&&`, which
  broke on CI (`sh: @php: not found`) because Composer only substitutes `@php`
  at the start of a command. It now uses Composer's array form so each PHPStan
  run resolves `@php` correctly and the `static-analysis` job passes.

## [6.0.0] - 2026-11-24

### Added

- New MCP stdio server (`bin/mandryn-mcp`, namespace `Mandryn\Mcp`) exposing
  `mandryn_query`, `mandryn_page`, `mandryn_schema`, and `mandryn_fetch` as
  tools for AI agents. Read-only by default; write statements require
  `--allow-writes`. `mandryn_page` reuses `UniversalPaging` MANUAL mode.
  Documented in `docs/MCP.md`; the DB-free test discipline is preserved via a
  `DbConnection` seam.

### Changed

- Added parameter, return, and property type declarations across the modern
  `Mandryn\db` and `Mandryn\http` layers (`Query`, `DBQueryi`, `HttpUrl`,
  `ConcurrentRequest`). This tightens the public API: callers that passed values
  of unexpected types (for example `null` or a non-string where a string was
  expected) now receive a `TypeError` instead of silent coercion. The legacy
  `src/mandryn_v1` layer is untouched.

- Widen the Guzzle constraint to `^7.8 || ^8.0` and resolve to Guzzle 8.6.0.
  `ConcurrentRequest` was exercised against Guzzle 8 with a local server to
  confirm the `Pool` path, concurrent fulfilment and query-string building all
  behave as before; existing consumers on Guzzle 7 are unaffected.
- README install instruction corrected to `mandryn/mandryn:^5.0`; the previous
  `dev-master` example no longer resolves now that the package requires stable
  releases.
- Dependency resolution is pinned to `minimum-stability: stable` with
  `prefer-stable: true`, replacing `dev`. Builds now install released versions
  instead of dev branches; the lock file has been regenerated
  (`guzzlehttp/guzzle` 7.17.0, `symfony/process` 6.4.49,
  `phpunit/phpunit` 10.5.67).
- Added `.github/dependabot.yml`: weekly Composer updates with patch and minor
  bumps grouped into one pull request, plus monthly GitHub Actions updates,
  replacing the previous untuned defaults.

### Fixed

- `Mandryn\db\DBQueryi::execute()` caught `PDOException` without a leading
  backslash, which resolved to the non-existent `Mandryn\db\PDOException`, so
  PDO failures were never caught and surfaced as fatal errors. The catch now
  targets `\PDOException`, so execution failures are reported and `execute()`
  returns `null` instead of crashing.

## [5.0.0] - 2026-11-24

The behaviour changes in this release are breaking because the previous
behaviour was unsafe: `SQL_STRING` statements could be built with unescaped
values, and an `UPDATE`/`DELETE` with no conditions produced invalid SQL.
Consumers that already escape their values before calling the builder, or that
use `PREPARE_STATEMENT` mode, are unaffected.

### Security

- **Breaking:** `Mandryn\db\Query` no longer interpolates unescaped string
  values in `SQL_STRING` mode. Call `setValueEscaper()` with the connection's
  `escape_string` routine; without one the builder refuses to emit the statement
  instead of emitting an injectable one. `PREPARE_STATEMENT` mode is unaffected
  and still needs no escaper.
- Fields declared as `DataType::INT` are validated as integers, so the type flag
  can no longer carry arbitrary SQL into the statement.
- Table and column names are validated before use.
- **Breaking:** `UPDATE` and `DELETE` without a `WHERE` clause are refused unless
  `allowUnscopedWrite(true)` is called explicitly.
- `Pagination::paging()` escapes everything it reflects into HTML and no longer
  creates global variables from request keys. This closes a reflected XSS and a
  variable-variable injection in the same block.

### Fixed

- Removed the duplicated authentication interface declarations (`IRedirectType`,
  `ISecurityLevel`, `IRightLevel`, `IActionType`, `IRedirectCode`) from
  `User.php`. `Login.php` is now the single declaration site, which clears the
  "Ambiguous class resolution" warning Composer printed on every autoload
  rebuild.
- The query builder no longer appends a dangling `WHERE` when a query has no
  conditions.
- `setSelectField()` accepts several field arguments again; a `$num`/`$nums` typo
  left the multi-argument form silently doing nothing.

## [4.0.0] - 2026-11-24

This release restarts the version line at 4.0.0 so the project leaves behind its
earlier inconsistent tags: a `2.1.0`–`2.7.0` series, a `3.0.0-rc.1` (2023) that
predates it, and the separate `v2.0.0`–`v2.0.2` line. The changes below are
backwards compatible; the major number marks a new baseline, not a breaking API
change. From here the series continues `4.0.x` → `4.1.0` → `5.0.0`.

### Added

- `docs/UniversalPaging.md` — usage manual covering both paging modes, blind
  mode, and the optional MEMORY-engine temp table.
- `examples/paging-manual.php` and `examples/UserPager.vue` — a MANUAL plus
  blind-mode JSON endpoint and its Vue 3 pager.
- `docs/manual-paging-flow.html` — visual walkthrough of the MANUAL paging flow.
- `phpunit.xml.dist` so the suite runs without arguments, plus a
  `composer test` script.
- GitHub Actions CI running the suite across PHP 8.3 and 8.4.
- `LICENSE`, `.gitattributes`, `.editorconfig`, and this changelog.

### Changed

- Clarified in the manual which options pair with which mode: the memory table
  is `AUTO_VIRTUAL`-only, while blind mode pairs with `MANUAL`.

### Fixed

- `getPagingInfo()` no longer falls through to a full-result recount after
  `setPageProperty()` has supplied the totals. Previously a MANUAL-mode
  endpoint that read its own paging info paid the expensive `COUNT(*)` it
  exists to avoid. The regression is covered by `tests/UniversalPagingTest.php`.
- Duplicate interface declarations (`IRedirectType` and friends) shared between
  `Login` and `User`.
- MANUAL paging now receives its totals through `setPageProperty()`.
- The "blind mode" section of the manual was documented twice; the duplicate
  bullet has been merged.

### Removed

- Local-only artefacts that were never part of the library (a nested
  third-party checkout and generated audio samples) are no longer in the
  working tree.

## [2.0.2] - 2026-11-03

### Fixed

- `UniversalPaging` SQA hardening completed: `random_bytes()` for temporary
  table names instead of `rand()`, removal of debug output from production
  paths, and an idempotent `initPageProperty()` that no longer double-round-trips
  to the database.

## [2.0.1] - 2026-11-03

### Fixed

- `UniversalPaging`: guard against division by zero when the page size is not a
  positive integer.
- `UniversalPaging`: `mysqli_num_rows()` is no longer called on a failed query
  result.

## [2.0.0] - 2026-11-03

### Changed

- **Breaking:** requires PHP >= 8.3.6.
- `symfony/process` bumped to `^6.4`, `guzzlehttp/guzzle` to `^7.8`.
- Deprecated and duplicated code removed; known CVEs resolved.

## [1.3.0] - 2026-09-01

### Security

- Fixed SQL injection paths in the query builders.
- Passwords are hashed with bcrypt instead of being stored in plain text.
- Session fixation hardening in the login flow.

## [2.7.0] - 2025-01-24

### Added

- `MagicInput` can integrate HTTP request headers.

## [2.6.0] - 2023-06-09

### Added

- Transaction support for `DBQuery`.

## [2.5.2] - 2022-03-22

### Fixed

- Input arrays are checked before sanitisation, so an empty array no longer
  returns `null`.

## [2.1.0] - 2017-05-08

### Added

- `MagicInput` class.

## [1.0.0] - 2011-08-17

### Added

- Initial release of `UniversalPaging`, the abstract recordset paginator.

[Unreleased]: https://github.com/ilham-fuead/mandryn/compare/v5.0.0...HEAD
[5.0.0]: https://github.com/ilham-fuead/mandryn/compare/v4.0.0...v5.0.0
[4.0.0]: https://github.com/ilham-fuead/mandryn/compare/v2.0.2...v4.0.0
[2.0.2]: https://github.com/ilham-fuead/mandryn/compare/v2.0.1...v2.0.2
[2.0.1]: https://github.com/ilham-fuead/mandryn/compare/v2.0.0...v2.0.1
[2.0.0]: https://github.com/ilham-fuead/mandryn/compare/v1.3.0...v2.0.0
[1.3.0]: https://github.com/ilham-fuead/mandryn/releases/tag/v1.3.0
