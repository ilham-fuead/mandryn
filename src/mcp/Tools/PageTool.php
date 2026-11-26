<?php

namespace Mandryn\Mcp\Tools;

use Mandryn\Mcp\Connection\DbConnection;
use Mandryn\Mcp\GuardException;
use Mandryn\Mcp\SqlGuard;
use Mandryn\Mcp\Tool;

final class PageTool implements Tool
{
    public const MAX_PAGE_SIZE = 200;
    public const DEFAULT_PAGE_SIZE = 25;

    /**
     * @var null|callable(array{host: string, user: string, password: string, name: string}, string, int, int, int, int): list<array<string, mixed>>
     */
    private $pagerFactory;

    /**
     * @param null|callable(array{host: string, user: string, password: string, name: string}, string, int, int, int, int): list<array<string, mixed>> $pagerFactory
     *        Test seam: overrides the UniversalPaging-based page fetch. Defaults to null = real UniversalPaging MANUAL mode.
     */
    public function __construct(
        private readonly DbConnection $connection,
        ?callable $pagerFactory = null,
    ) {
        $this->pagerFactory = $pagerFactory;
    }

    public function name(): string
    {
        return 'mandryn_page';
    }

    public function description(): string
    {
        return 'Page through the rows of a SELECT using a prepared statement (LIMIT/OFFSET). '
            . 'Returns one page plus totals (total row count comes from a wrapped COUNT(*) query). '
            . 'Always read-only, even under --allow-writes. Column values are returned with their '
            . 'native PDO types (not stringified). Positional ? parameters are interpolated '
            . '(escaped) since UniversalPaging has no bound-parameter support; named '
            . 'placeholders are not supported. The SQL must not contain its own LIMIT, and '
            . 'CTEs are not supported.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'sql' => [
                    'type' => 'string',
                    'description' => 'A single read-only statement (SELECT/SHOW/DESCRIBE/EXPLAIN) without LIMIT.',
                ],
                'params' => [
                    'type' => 'array',
                    'description' => 'Positional values for ? placeholders, in order. Escaped, never bound.',
                    'items' => ['type' => ['string', 'number', 'integer', 'boolean', 'null']],
                    'default' => [],
                ],
                'page' => [
                    'type' => 'integer',
                    'description' => '1-based page number. Clamped to the available pages.',
                    'minimum' => 1,
                    'default' => 1,
                ],
                'page_size' => [
                    'type' => 'integer',
                    'description' => 'Rows per page (1..200, default 25).',
                    'minimum' => 1,
                    'maximum' => self::MAX_PAGE_SIZE,
                    'default' => self::DEFAULT_PAGE_SIZE,
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

        $page = $arguments['page'] ?? 1;
        if (!is_int($page) && !(is_string($page) && ctype_digit($page) && (int) $page >= 1)) {
            throw new \InvalidArgumentException('argument "page" must be an integer >= 1');
        }
        $page = (int) $page;

        $pageSize = $arguments['page_size'] ?? self::DEFAULT_PAGE_SIZE;
        if (!is_int($pageSize) && !(is_string($pageSize) && ctype_digit($pageSize))) {
            throw new \InvalidArgumentException('argument "page_size" must be an integer between 1 and ' . self::MAX_PAGE_SIZE);
        }
        $pageSize = max(1, min((int) $pageSize, self::MAX_PAGE_SIZE));

        // mandryn_page is always read-only, independent of the server flag.
        $classification = (new SqlGuard(true))->assertAllowed($sql);
        if ($classification !== SqlGuard::READ) {
            throw new GuardException('mandryn_page only supports read statements (SELECT/SHOW/DESCRIBE/EXPLAIN); writes are never allowed through this tool');
        }

        $sql = trim($sql);
        $sql = rtrim($sql, ';');

        if (preg_match('/\blimit\b/i', $sql) === 1) {
            throw new GuardException('mandryn_page appends its own LIMIT; remove LIMIT from the SQL');
        }

        $firstWord = strtoupper(strtok($sql, " \t\n\r("));
        if ($firstWord === 'WITH') {
            throw new GuardException('CTEs are not supported by mandryn_page; rewrite the statement without WITH');
        }

        if (preg_match('/:[A-Za-z_][A-Za-z0-9_]*/', $sql) === 1) {
            throw new GuardException('named parameters are not supported by mandryn_page; use positional ? placeholders');
        }

        $placeholderCount = substr_count($sql, '?');
        if ($placeholderCount !== count($params)) {
            throw new \InvalidArgumentException(
                'param count mismatch: the SQL has ' . $placeholderCount . ' placeholders but ' . count($params) . ' params were given'
            );
        }

        if ($params !== []) {
            $sql = $this->interpolateParams($sql, $params);
        }

        $countSql = 'SELECT COUNT(*) AS total_rows FROM ( ' . $sql . ' ) AS _mandryn_count';
        $total = (int) $this->connection->queryScalar($countSql);

        if ($total === 0) {
            return [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => 0,
                'total_pages' => 0,
                'rows' => [],
            ];
        }

        $totalPages = max(1, (int) ceil($total / $pageSize));
        $page = max(1, min($page, $totalPages));

        if ($this->pagerFactory !== null) {
            $config = $this->connection->getConfig();
            $factory = $this->pagerFactory;
            $rows = $factory([
                'host' => $config->hostForMysqli(),
                'user' => $config->user,
                'password' => $config->password,
                'name' => $config->name,
            ], $sql, $page, $pageSize, $total, $totalPages);
        } else {
            $offset = ($page - 1) * $pageSize;
            $rows = $this->connection->queryRows($sql . ' LIMIT ' . $offset . ', ' . $pageSize);
        }

        return [
            'page' => $page,
            'page_size' => $pageSize,
            'total' => $total,
            'total_pages' => $totalPages,
            'rows' => $rows,
        ];
    }

    /**
     * @param list<mixed> $params
     */
    private function interpolateParams(string $sql, array $params): string
    {
        $offset = 0;

        foreach ($params as $param) {
            $position = strpos($sql, '?', $offset);
            if ($position === false) {
                throw new \InvalidArgumentException('param count mismatch while interpolating values');
            }

            if ($param === null) {
                $literal = 'NULL';
            } elseif (is_int($param) || is_float($param)) {
                $literal = (string) $param;
            } elseif (is_string($param) || is_bool($param)) {
                $literal = "'" . $this->connection->escapeLiteral(is_bool($param) ? ($param ? '1' : '0') : $param) . "'";
            } else {
                throw new \InvalidArgumentException('unsupported param type: ' . get_debug_type($param));
            }

            $sql = substr_replace($sql, $literal, $position, 1);
            $offset = $position + strlen($literal);
        }

        return $sql;
    }
}

