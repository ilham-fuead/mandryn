<?php

namespace Tests\Mcp;

use Mandryn\Mcp\Connection\DbConfig;
use Mandryn\Mcp\Connection\DbConnection;

final class FakeDbConnection implements DbConnection
{
    /** @var list<array{sql: string, params: array<int, mixed>}> */
    public array $calls = [];

    /**
     * @param array<string, list<array<string, mixed>>> $rowsBySql
     * @param mixed $scalarValue value returned for any queryScalar() call
     */
    public function __construct(
        private array $rowsBySql = [],
        private mixed $scalarValue = null,
        private int $affectedRows = 0,
    ) {
    }

    public function queryRows(string $sql, array $params = []): array
    {
        $this->calls[] = ['sql' => $sql, 'params' => $params];

        return $this->rowsBySql[$sql] ?? [];
    }

    public function queryScalar(string $sql, array $params = []): mixed
    {
        $this->calls[] = ['sql' => $sql, 'params' => $params];

        return $this->scalarValue;
    }

    public function execute(string $sql, array $params = []): int
    {
        $this->calls[] = ['sql' => $sql, 'params' => $params];

        return $this->affectedRows;
    }

    public function escapeLiteral(string $value): string
    {
        return addslashes($value);
    }

    public function getConfig(): DbConfig
    {
        return new DbConfig('fake-host', 3306, 'fake-user', 'fake-pass', 'fake_db');
    }
}
