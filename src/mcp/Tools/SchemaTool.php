<?php

namespace Mandryn\Mcp\Tools;

use Mandryn\Mcp\Connection\DbConnection;
use Mandryn\Mcp\Tool;

final class SchemaTool implements Tool
{
    private const TABLES_SQL_WITH_FILTER = "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'";

    private const COLUMNS_SQL_WITH_FILTER = 'SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()';

    public function __construct(private readonly DbConnection $connection)
    {
    }

    public function name(): string
    {
        return 'mandryn_schema';
    }

    public function description(): string
    {
        return 'Introspect the connected database: list base tables and their columns '
            . '(name, type, nullability, key, extra). Optionally filter to a single table. '
            . 'Read-only.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'table' => [
                    'type' => 'string',
                    'description' => 'Optional plain table name (letters, digits, underscore) to limit the introspection to one table.',
                    'pattern' => '^[A-Za-z0-9_]+$',
                ],
            ],
        ];
    }

    public function call(array $arguments): array
    {
        $table = $arguments['table'] ?? null;
        if ($table !== null) {
            if (!is_string($table) || preg_match('/^[A-Za-z0-9_]+$/', $table) !== 1) {
                throw new \InvalidArgumentException('argument "table" must be a plain table name (letters, digits, underscore)');
            }
        }

        $params = $table === null ? [] : [$table];
        $filter = $table === null ? '' : ' AND TABLE_NAME = ?';

        $database = $this->connection->queryScalar('SELECT DATABASE()');

        $tablesSql = self::TABLES_SQL_WITH_FILTER . $filter . ' ORDER BY TABLE_NAME';
        $columnsSql = self::COLUMNS_SQL_WITH_FILTER . $filter . ' ORDER BY TABLE_NAME, ORDINAL_POSITION';

        $tableRows = $this->connection->queryRows($tablesSql, $params);
        $columnRows = $this->connection->queryRows($columnsSql, $params);

        /** @var array<string, array{name: string, columns: list<array<string, mixed>>}> $tables */
        $tables = [];
        foreach ($tableRows as $row) {
            $name = (string) $row['TABLE_NAME'];
            $tables[$name] = ['name' => $name, 'columns' => []];
        }

        foreach ($columnRows as $row) {
            $name = (string) $row['TABLE_NAME'];
            // Ignore columns that belong to tables outside the base-table
            // listing (e.g. views) rather than silently promoting them.
            if (!isset($tables[$name])) {
                continue;
            }

            $tables[$name]['columns'][] = [
                'name' => (string) $row['COLUMN_NAME'],
                'type' => (string) $row['COLUMN_TYPE'],
                'nullable' => $row['IS_NULLABLE'] === 'YES',
                'key' => $row['COLUMN_KEY'] !== '' ? (string) $row['COLUMN_KEY'] : null,
                'extra' => $row['EXTRA'] !== '' ? (string) $row['EXTRA'] : null,
            ];
        }

        return [
            'database' => $database,
            'total_tables' => count($tables),
            'tables' => array_values($tables),
        ];
    }
}
