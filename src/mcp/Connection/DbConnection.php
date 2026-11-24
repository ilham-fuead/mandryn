<?php

namespace Mandryn\Mcp\Connection;

use Mandryn\Mcp\DbException;

interface DbConnection
{
    /**
     * Run a result-set query (SELECT/SHOW/DESCRIBE/EXPLAIN) and return all rows.
     *
     * @param list<mixed> $params positional parameters for `?` placeholders
     * @return list<array<string, mixed>>
     *
     * @throws DbException on SQL or connection failure
     */
    public function queryRows(string $sql, array $params = []): array;

    /**
     * Run a query and return the first cell of the first row, or null.
     *
     * @param list<mixed> $params
     *
     * @throws DbException on SQL or connection failure
     */
    public function queryScalar(string $sql, array $params = []): mixed;

    /**
     * Run a non-result statement (INSERT/UPDATE/DELETE, or DDL when no
     * parameters are given). Returns the number of affected rows.
     *
     * @param list<mixed> $params
     *
     * @throws DbException on SQL or connection failure
     */
    public function execute(string $sql, array $params = []): int;

    /**
     * Escape a value for safe literal interpolation into SQL. Only used by
     * mandryn_page, which must interpolate because UniversalPaging has no
     * bound-parameter support.
     *
     * @throws DbException on connection failure
     */
    public function escapeLiteral(string $value): string;

    public function getConfig(): DbConfig;
}
