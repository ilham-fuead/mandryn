<?php
declare(strict_types=1);

/**
 * 03 - MagicInput + DBCommand: validate a JSON payload, then INSERT a new item.
 *
 * Usage:
 *   php examples/inventory/03_add_item.php                 (embedded demo payload)
 *   php examples/inventory/03_add_item.php item.json       (payload from file)
 *
 * Under a real HTTP server you would replace the $_POST injection below with
 * copy_POST_properties() (form POST) or copy_RAW_JSON_properties() (JSON body).
 */

require __DIR__ . '/bootstrap.php';

$jsonFile = $argv[1] ?? null;

if ($jsonFile === null) {
    $json = '{"sku":"SKU-20001","item_name":"Demo Widget (mandryn example)",' .
        '"category_id":1,"warehouse_id":1,"quantity_on_hand":25,' .
        '"reorder_level":10,"unit_cost":9.99,"unit_price":19.99}';
} else {
    $json = file_get_contents($jsonFile);
}

$payload = json_decode($json, true);
if (!is_array($payload)) {
    out('Invalid JSON payload.');
    exit(1);
}

// 1. Define what input is acceptable: [name, type, required, alias]
$input = new MagicInput();
$input->setInputsDefinition([
    ['sku',              's', true,  ''],
    ['item_name',        's', true,  ''],
    ['category_id',      'i', true,  ''],
    ['warehouse_id',     'i', true,  ''],
    ['quantity_on_hand', 'i', false, ''],
    ['reorder_level',    'i', false, ''],
    ['unit_cost',        'f', true,  ''],
    ['unit_price',       'f', true,  ''],
    ['status',           's', false, ''],
]);

// 2. Feed the payload in and let MagicInput copy it as object properties.
$_POST = $payload;
$input->copy_POST_properties(false);

// 3. Compliance check against the definition.
if (!$input->isInputsComplied()) {
    out('Input not compliant. Details:');
    foreach ($input->getNonCompliedInputList() as $item) {
        out(sprintf('  - %s : %s', $item['name'], $item['error']));
    }
    exit(1);
}
out('Input compliant.');

// 4. Build and run the INSERT with DBCommand, consuming the MagicInput object.
$db  = new DBQuery(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$cmd = new DBCommand($db);

$cmd->setINSERTintoTable('inventory_items');
$cmd->importInputColumns($input);
$cmd->executeQueryCommand();

if ($cmd->getExecutionStatus() === true) {
    out('Insert OK. New item_id: ' . $cmd->getInsertID());
    out('SQL executed: ' . $cmd->getSQLstring());

    // 5. Read the row back to prove it landed.
    $db->setSQL_Statement(
        'SELECT item_id, sku, item_name, quantity_on_hand, status ' .
        'FROM inventory_items WHERE item_id = ' . (int) $cmd->getInsertID()
    );
    $db->runSQL_Query();
    $row = $db->isHavingRecordRow() ? $db->fetchRow() : null;
    if ($row) {
        out(sprintf(
            'Read-back: %s | %s | qty=%d | %s',
            $row['sku'],
            $row['item_name'],
            $row['quantity_on_hand'],
            $row['status']
        ));
    }
} else {
    out('Insert failed: ' . $cmd->getErrno() . ' - ' . $cmd->getError());
    exit(1);
}
