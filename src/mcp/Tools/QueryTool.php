<?php

namespace Mandryn\Mcp\Tools;

use Mandryn\Mcp\Connection\DbConnection;
use Mandryn\Mcp\SqlGuard;
use Mandryn\Mcp\Tool;

final class QueryTool implements Tool
{
    private const DEFAULT_LIMIT = 100;
    private const MAX_LIMIT = 1000;

    public function __construct(
        private readonly DbConnection $connection,
        private readonly SqlGuard $guard,
    ) {
    }

    public function name(): string
    {
        return 'mandryn_query';
    }

    public function description(): string
    {
        return 'Run a single SQL statement against the configured MySQL database. '
            . 'Read statements (SELECT/SHOW/DESCRIBE/EXPLAIN) return rows; a LIMIT is appended '
            . 'automatically when the statement has none (default 100, max 1000). '
            . 'Write statements (INSERT/UPDATE/DELETE/DDL) are refused unless the server was '
            . 'started with --allow-writes; they then return the affected row count. '
            . 'Values must travel via ? placeholders plus the params argument - never '
            . 'interpolated into the SQL.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'sql' => [
                    'type' => 'string',
                    'description' => 'The SQL statement to run (single statement only).',
                ],
                'params' => [
                    'type' => 'array',
                    'description' => 'Positional values bound to ? placeholders in order.',
                    'items' => ['type' => ['string', 'number', 'integer', 'boolean', 'null']],
                    'default' => [],
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Row cap for read statements (1..1000, default 100). Ignored when the SQL already has a LIMIT.',
                    'minimum' => 1,
                    'maximum' => self::MAX_LIMIT,
                    'default' => self::DEFAULT_LIMIT,
                ],
            ],
            'required' => ['sql'],
        ];
    }

    public function call(array $arguments): array
    {
        $sql = $arguments['sql'] ?? null;
        if (!is_string($sql) || trim($sql) === '') {
            throw new \InvalidArgumentException('missing required argument "sql" (non-empty string)');
        }

        $params = $arguments['params'] ?? [];
        if (!is_array($params)) {
            throw new \InvalidArgumentException('argument "params" must be an array');
        }
        $params = array_values($params);

        $limit = $arguments['limit'] ?? self::DEFAULT_LIMIT;
        if (!is_int($limit) && !(is_string($limit) && ctype_digit($limit))) {
            throw new \InvalidArgumentException('argument "limit" must be an integer between 1 and ' . self::MAX_LIMIT);
        }
        $limit = max(1, min((int) $limit, self::MAX_LIMIT));

        $classification = $this->guard->assertAllowed($sql);

        $sql = trim($sql);
        $sql = rtrim($sql, ';');

        if ($classification === SqlGuard::READ) {
            $appended = false;
            if (preg_match('/\blimit\s+\d+(\s*,\s*\d+)?\s*$/i', $sql) !== 1) {
                $sql .= ' LIMIT ' . $limit;
                $appended = true;
            }

            $rows = $this->connection->queryRows($sql, $params);

            return [
                'rows' => $rows,
                'count' => count($rows),
                'truncated' => $appended && count($rows) === $limit,
            ];
        }

        $affected = $this->connection->execute($sql, $params);

        return ['affected_rows' => $affected];
    }
}
