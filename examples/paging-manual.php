<?php
// examples/paging-manual.php — MANUAL + blind-mode paging endpoint (JSON)
require __DIR__ . '/../vendor/autoload.php';

use Mandryn\db\DBQuery;
use Mandryn\mandryn_v1\UniversalPaging;
use Mandryn\mandryn_v1\IPagingType;

class UserPager extends UniversalPaging
{
    public array $rows = [];
    protected function performTaskOnEachPage($DBQueryObj, $startRowIndex, $lastRowIndex): void
    {
        $this->rows = [];
        while ($r = mysqli_fetch_assoc($DBQueryObj->getQueryResult())) {
            $this->rows[] = $r;
        }
    }
    protected function handleTaskOnNoPaging(): void
    {
        $this->rows = [];
    }
}

header('Content-Type: application/json; charset=utf-8');

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = max(1, (int) ($_GET['per_page'] ?? 10));
$blind = filter_var($_GET['blind'] ?? false, FILTER_VALIDATE_BOOLEAN);

$db = new DBQuery('localhost', 'user', 'pass', 'mydb');

$pager = new UserPager($db);
$pager->setSQLStatement('SELECT IDpengguna, nama, emel FROM pengguna ORDER BY IDpengguna');
$pager->setPagingProperty(IPagingType::MANUAL, $perPage, false, $blind);
$pager->setPagingDelay(0);

// In blind mode we don't know totals; the Vue side infers "has next" from row count.
$pager->startPaging($page);

echo json_encode([
    'page'     => $page,
    'per_page' => $perPage,
    'blind'    => $blind,
    'rows'     => $pager->rows,
    'info'     => [
        'total_row'  => $pager->getPagingInfo()->totalRow,
        'total_page' => $pager->getPagingInfo()->totalPage,
        'last_page'  => $pager->getLastPageStatus(),
    ],
], JSON_UNESCAPED_UNICODE);
