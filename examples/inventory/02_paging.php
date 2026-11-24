<?php
declare(strict_types=1);

/**
 * 02 - UniversalPaging: AUTO_VIRTUAL (walks every page) + MANUAL (single page).
 *
 * Usage: php examples/inventory/02_paging.php
 */

require __DIR__ . '/bootstrap.php';

class InventoryPager extends UniversalPaging
{
    /** @var int number of pages rendered */
    public $pageCount = 0;

    protected function performTaskOnEachPage($DBQueryObj, $startRowIndex, $lastRowIndex)
    {
        $this->pageCount++;

        $result = $DBQueryObj->getQueryResult();
        $first  = null;
        $last   = null;

        if ($result !== false) {
            $first = mysqli_fetch_assoc($result);
            while ($row = mysqli_fetch_assoc($result)) {
                $last = $row;
            }
        }

        out(sprintf(
            '  page %2d : rows %4d..%-4d | first %s | last %s',
            $this->pageCount,
            $startRowIndex + 1,
            $lastRowIndex + 1,
            $first ? $first['sku'] : '-',
            $last ? $last['sku'] : '-'
        ));
    }

    protected function handleTaskOnNoPaging()
    {
        out('  no records to page');
    }
}

out('=== AUTO_VIRTUAL : walks every page (100 rows/page) ===');

$db = new DBQuery(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$pager = new InventoryPager($db);
$pager->setSQLStatement('SELECT item_id, sku FROM inventory_items ORDER BY item_id');
$pager->setPagingProperty(IPagingType::AUTO_VIRTUAL, 100);
$pager->setPagingDelay(0); // avoid the default 1s sleep between pages
$pager->startPaging(1);

$info = $pager->getPagingInfo();
out(sprintf(
    '  TOTAL : %d rows over %d pages (%d page callbacks)',
    $info->totalRow,
    $info->totalPage,
    $pager->pageCount
));

out();
out('=== MANUAL : single page only (page 7, caller supplies totals) ===');

$db2 = new DBQuery(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$pager2 = new InventoryPager($db2);
$pager2->setSQLStatement('SELECT item_id, sku FROM inventory_items ORDER BY item_id');
$pager2->setPagingProperty(IPagingType::MANUAL, 100);

// MANUAL mode: the caller owns the totals - no COUNT(*) is ever run.
$pi = new PagingInfo();
$pi->totalRow = 1000;
$pi->totalPage = 10;
$pager2->setPageProperty($pi);

$pager2->startPaging(7);
out(sprintf('  MANUAL page 7 rendered (%d page callback)', $pager2->pageCount));
