# Changelog

All notable changes to `mandryn/mandryn` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Historical releases before `2.0.0` are summarised rather than itemised; the full
per-commit history is available via `git log`.

## [Unreleased]

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

[Unreleased]: https://github.com/ilham-fuead/mandryn/compare/v4.0.0...HEAD
[4.0.0]: https://github.com/ilham-fuead/mandryn/compare/v2.0.2...v4.0.0
[2.0.2]: https://github.com/ilham-fuead/mandryn/compare/v2.0.1...v2.0.2
[2.0.1]: https://github.com/ilham-fuead/mandryn/compare/v2.0.0...v2.0.1
[2.0.0]: https://github.com/ilham-fuead/mandryn/compare/v1.3.0...v2.0.0
[1.3.0]: https://github.com/ilham-fuead/mandryn/releases/tag/v1.3.0
