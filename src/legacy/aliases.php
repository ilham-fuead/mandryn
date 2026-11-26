<?php
declare(strict_types=1);

/**
 * Backwards-compatible aliases exposing the frozen legacy classes under the
 * `Mandryn\legacy\*` namespace. The original global-namespace classes remain
 * the source of truth; these are thin aliases for consumers migrating to
 * namespaced imports.
 */

$legacyClasses = [
    'Checkbox', 'ConnectionDetail', 'DB', 'DBCommand', 'DBQuery', 'DBQueryEZ',
    'Field', 'FilterDefinition', 'MagicInput', 'MagicObject', 'MixedDataTypeContainer',
    'PagingInfo', 'Radio', 'Select', 'SQLQuery', 'SQLQueryES', 'TextLiteral', 'Utilities',
];

foreach ($legacyClasses as $name) {
    if (class_exists($name, true) && !class_exists('Mandryn\\legacy\\' . $name, false)) {
        class_alias($name, 'Mandryn\\legacy\\' . $name);
    }
}

$legacyInterfaces = [
    'IActionType', 'IAuthenticationAction', 'IColumnSortOrder', 'IComparisonType',
    'IConditionOperator', 'IFieldType', 'ILayoutFlow', 'IPagingType', 'IRedirectCode',
    'IRedirectType', 'IRightLevel', 'ISecurityLevel', 'ISortColumn', 'ISortingColumn',
    'ISortingOrder', 'ISortOrder', 'IWildcardPosition',
];

foreach ($legacyInterfaces as $name) {
    if (interface_exists($name, true) && !interface_exists('Mandryn\\legacy\\' . $name, false)) {
        class_alias($name, 'Mandryn\\legacy\\' . $name);
    }
}
