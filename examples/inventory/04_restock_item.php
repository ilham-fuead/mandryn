<?php
declare(strict_types=1);

/**
 * 04 - DBCommand: UPDATE (restock) with a WHERE condition.
 *
 * Usage:
 *   php examples/inventory/04_restock_item.php              (SKU-00001 + 50 units)
 *   php examples/inventory/04_restock_item.php SKU-10002 60
 *
 * Note: this example changes data - run it against a development database.
 */

require __DIR__ . '/bootstrap.php';

$sku      = (string) ($argv[1] ?? 'SKU-00001');
$extraQty = (int) ($argv[2] ?? 50);

$db         = new DBQuery(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$skuEscaped = $db->escape_string($sku);

// 1. Read the current state.
$db->setSQL_Statement(
    "SELECT item_id, sku, quantity_on_hand, reorder_level, status " .
    "FROM inventory_items WHERE sku = '$skuEscaped'"
);
$db->runSQL_Query();

if (!$db->isHavingRecordRow()) {
    out("SKU not found: $sku");
    exit(1);
}

$before = $db->fetchRow();
out(sprintf(
    'Before : %s | qty=%d | reorder=%d | %s',
    $before['sku'],
    $before['quantity_on_hand'],
    $before['reorder_level'],
    $before['status']
));

// 2. Compute the new values and build the UPDATE with DBCommand.
$newQty    = (int) $before['quantity_on_hand'] + $extraQty;
$newStatus = $newQty === 0
    ? 'OUT_OF_STOCK'
    : ($newQty <= (int) $before['reorder_level'] ? 'LOW_STOCK' : 'ACTIVE');

$cmd = new DBCommand($db);
$cmd->setUPDATEtoTable('inventory_items');
$cmd->addUPDATEcolumn('quantity_on_hand', $newQty, IFieldType::INTEGER_TYPE);
$cmd->addUPDATEcolumn('last_restocked_at', date('Y-m-d H:i:s'), IFieldType::DATETIME_TYPE);
$cmd->addUPDATEcolumn('status', $newStatus, IFieldType::STRING_TYPE);
$cmd->addConditionStatement(
    'sku',
    $skuEscaped,
    IFieldType::STRING_TYPE,
    IConditionOperator::NONE,
    IComparisonType::EQUAL_TO
);
$cmd->executeQueryCommand();

if ($cmd->getExecutionStatus() === true) {
    out('Update OK. Rows affected: ' . $cmd->getAffectedRowCount());
    out('SQL: ' . $cmd->getSQLstring());
} else {
    out('Update failed: ' . $cmd->getErrno() . ' - ' . $cmd->getError());
    exit(1);
}

// 3. Read the row back.
$db->setSQL_Statement(
    "SELECT quantity_on_hand, status, last_restocked_at " .
    "FROM inventory_items WHERE sku = '$skuEscaped'"
);
$db->runSQL_Query();
$after = $db->fetchRow();
out(sprintf(
    'After  : qty=%d | %s | last restock %s',
    $after['quantity_on_hand'],
    $after['status'],
    $after['last_restocked_at']
));
