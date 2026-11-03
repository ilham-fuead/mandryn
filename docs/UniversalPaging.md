# UniversalPaging — Usage Manual

Abstract paginator for MySQL recordsets. Part of `mandryn/mandryn` (PHP >= 8.3.6).

## Overview

`UniversalPaging` (in `src/mandryn_v1/UniversalPaging.php`) drives per-page processing of a SQL result set. You **extend** it and implement two abstract methods; the base class handles row counting, page math, `LIMIT` slicing, optional in-memory temp tables, and recursive page traversal.

Two paging modes:
- **AUTO_VIRTUAL** — counts total rows/pages, then recurses through every page automatically.
- **MANUAL** — caller supplies the current page; no auto-count (caller must `setPageProperty()`).

## Class hierarchy

```
IPagingType (interface)   -> AUTO_VIRTUAL=1, MANUAL=2
PagingInfo (class)        -> pagingType,totalRow,totalRowPerPaging,totalPage,memTableName,lastPage
UniversalPaging (abstract) -> implements IPagingType
```

## Constructor

```php
public function __construct(DBQuery $DBQueryObj)
```
Stores the connection detail from `$DBQueryObj->getConnectionDetail()`. `$DBQueryObj` is unset after.

## Required overrides (abstract)

```php
abstract protected function performTaskOnEachPage(DBQuery $DBQueryObj, int $startRowIndex, int $lastRowIndex): void;
abstract protected function handleTaskOnNoPaging(): void;
```

## Public API

| Method | Purpose |
|---|---|
| `setSQLStatement(string $sql)` | Base SELECT without LIMIT. |
| `setPagingProperty(IPagingType, rowPerPage=0, useMemTable=false, blindMode=false)` | Configure mode, page size, memory table, blind mode. |
| `setPagingType(IPagingType)` | Override paging type only. |
| `setRowPerPage(int)` | Override rows per page. |
| `setUseTmpMemEng(bool $use=true)` | Enable in-memory MEMORY-engine temp table. |
| `setPageProperty(PagingInfo $obj)` | (MANUAL mode) inject totalRow/totalPage/memTableName. |
| `setPagingDelay(int $seconds=0)` | Delay between pages (auto mode). |
| `getPagingInfo(): PagingInfo` | Triggers init (auto mode) and returns info. |
| `getPagingInfoObj(): PagingInfo` | Returns info without init. |
| `startPaging(int $currentPage)` | Drives rendering. |
| `getLastPageStatus(): int` | 1 when last page reached (blind mode). |
| `storeAdditionalDataToArray(mixed $data)` | Append to mixed-data container. |
| `getAdditionalDataArray()` / `getAdditionalDataArrayByIndex($i)` / `setAdditionalDataArrayByIndex($i,$v)` | Access stored data. |

## Usage — AUTO_VIRTUAL (batch/CLI)

```php
require __DIR__ . '/vendor/autoload.php';

use Mandryn\db\DBQuery;
use Mandryn\mandryn_v1\UniversalPaging;
use Mandryn\mandryn_v1\IPagingType;

class UserPager extends UniversalPaging
{
    protected function performTaskOnEachPage($DBQueryObj, $startRowIndex, $lastRowIndex)
    {
        while ($row = mysqli_fetch_assoc($DBQueryObj->getQueryResult())) {
            echo $row['IDpengguna'] . ' - ' . $row['nama'] . "\n";
        }
    }

    protected function handleTaskOnNoPaging()
    {
        echo "No records.\n";
    }
}

$db = new DBQuery('localhost', 'user', 'pass', 'mydb');
$pager = new UserPager($db);
$pager->setSQLStatement('SELECT IDpengguna, nama FROM pengguna');
$pager->setPagingProperty(IPagingType::AUTO_VIRTUAL, 10);
$pager->setPagingDelay(0);
$pager->startPaging(1);

$info = $pager->getPagingInfo();
echo "Total rows: {$info->totalRow}, pages: {$info->totalPage}\n";
```

## Usage — MANUAL (HTTP pagination)

```php
$page = (int) ($_GET['page'] ?? 1);
$pager = new UserPager($db);
$pager->setSQLStatement('SELECT IDpengguna, nama FROM pengguna');
$pager->setPagingProperty(IPagingType::MANUAL, 10);

// Caller supplies totals (or run COUNT(*) first)
$info = new \PagingInfo();
$info->totalRow = 1234;
$info->totalPage = (int) ceil(1234 / 10);
$pager->setPageProperty($info);

$pager->startPaging($page);
```

## Modes

- **AUTO_VIRTUAL**: runs `COUNT`-equivalent via `mysqli_num_rows` on full result, computes pages, then recursively renders each page with `LIMIT start,n`. Delays between pages via `sleep`/`usleep`.
- **MANUAL**: renders a single page from `$currentPage`; no counting. Caller manages totals.
- **Blind mode** (`useBlindMode=true` in `setPagingProperty`): skips counting, pages until an empty result (cap `BLIND_MAX_PAGES = 100`).
- **Memory table** (`useMemTable=true` or `setUseTmpMemEng(true)`): AUTO_VIRTUAL mode only. Builds a `MEMORY`-engine temp table from your SQL for faster re-reads. Requires `CREATE TABLE` + `SET GLOBAL` privileges.
- **Blind mode** (`useBlindMode=true`): can pair with MANUAL mode. Pages until an empty result; no upfront row count. Cap `BLIND_MAX_PAGES = 100`.

## SQA notes

- `totalRowPerPaging <= 0` defaults to 1 (no division-by-zero).
- Query-result `false` is guarded (no `mysqli_num_rows` warning).
- `executeTableLevelCommand()` returns `bool` instead of throwing.
- Memory-table name uses `random_bytes(4)` (no `rand()` collision).
- `initPageProperty()` is idempotent (no double DB round-trip).
- No debug `echo` in production paths.

## Caveats

- MySQL only (`LIMIT a,b` syntax + `ENGINE=MEMORY`).
- Blocks the request for the entire dataset in AUTO_VIRTUAL — avoid on public web endpoints.
- SQL is concatenated with `LIMIT` unsanitized — trust/sanitize upstream.
- No built-in prev/next UI helpers.
- Abstract class requires a subclass; cannot instantiate directly.
- `mysqli` + `mysqlnd` required.

## Version

1.0 (released 2011-08-17), maintained in `mandryn/mandryn`.
