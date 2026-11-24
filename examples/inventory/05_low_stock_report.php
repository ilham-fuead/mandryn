<?php
declare(strict_types=1);

/**
 * 05 - DBQuery: low-stock report (LOW_STOCK / OUT_OF_STOCK) + status summary.
 *
 * Usage: php examples/inventory/05_low_stock_report.php
 */

require __DIR__ . '/bootstrap.php';

$db = new DBQuery(DB_HOST, DB_USER, DB_PASS, DB_NAME);

out('=== Items needing attention (worst 15 first) ===');

$db->setSQL_Statement(
    "SELECT i.sku, i.item_name, c.category_name, w.city, " .
    "i.quantity_on_hand, i.reorder_level, i.status " .
    'FROM inventory_items i ' .
    'INNER JOIN categories c ON c.category_id = i.category_id ' .
    'INNER JOIN warehouses w ON w.warehouse_id = i.warehouse_id ' .
    "WHERE i.status IN ('LOW_STOCK', 'OUT_OF_STOCK') " .
    'ORDER BY i.quantity_on_hand ASC, i.item_id ASC ' .
    'LIMIT 15'
);
$db->runSQL_Query();

if ($db->isHavingRecordRow()) {
    foreach ($db->yieldRow() as $row) {
        out(sprintf(
            '%-10s | %-26s | %-20s | %-12s | qty=%3d (reorder %d) | %s',
            $row['sku'],
            $row['item_name'],
            $row['category_name'],
            $row['city'],
            $row['quantity_on_hand'],
            $row['reorder_level'],
            $row['status']
        ));
    }
} else {
    out('No low-stock items.');
}

out();
out('=== Status summary ===');
$db->setSQL_Statement(
    'SELECT status, COUNT(*) AS cnt FROM inventory_items GROUP BY status ORDER BY status'
);
$db->runSQL_Query();
while ($row = $db->fetchRow()) {
    out(sprintf('%-14s %4d', $row['status'], $row['cnt']));
}
