<?php
declare(strict_types=1);

/**
 * 01 - DBQuery: recordset listing with JOIN + JSON output.
 *
 * Usage: php examples/inventory/01_list_items.php
 */

require __DIR__ . '/bootstrap.php';

$db = new DBQuery(DB_HOST, DB_USER, DB_PASS, DB_NAME);

out('=== DBQuery - joined listing (first 8 items) ===');

$db->setSQL_Statement(
    'SELECT i.sku, i.item_name, c.category_name, w.warehouse_name, ' .
    'i.quantity_on_hand, i.unit_price, i.status ' .
    'FROM inventory_items i ' .
    'INNER JOIN categories c ON c.category_id = i.category_id ' .
    'INNER JOIN warehouses w ON w.warehouse_id = i.warehouse_id ' .
    'ORDER BY i.item_id LIMIT 8'
);
$db->runSQL_Query();

if ($db->isHavingRecordRow()) {
    while ($row = $db->fetchRow()) {
        out(sprintf(
            '%s | %-26s | %-20s | %-24s | qty=%3d | price=%8.2f | %s',
            $row['sku'],
            $row['item_name'],
            $row['category_name'],
            $row['warehouse_name'],
            $row['quantity_on_hand'],
            $row['unit_price'],
            $row['status']
        ));
    }
} else {
    out('No rows returned.');
}

out();
out('Rows in recordset: ' . $db->getNumRows());

out();
out('=== DBQuery - getRowsInJSON (first 3 rows) ===');
$db->setSQL_Statement(
    'SELECT sku, item_name, status FROM inventory_items ORDER BY item_id LIMIT 3'
);
$db->runSQL_Query();
out($db->getRowsInJSON());
