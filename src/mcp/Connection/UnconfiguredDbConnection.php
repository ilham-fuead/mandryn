<?php

namespace Mandryn\Mcp\Connection;

use Mandryn\Mcp\DbException;

/**
 * Stand-in connection used when the server starts without DB configuration.
 * Every call fails with an actionable message so the agent (and the human
 * reading the transcript) knows exactly what to set.
 */
final class UnconfiguredDbConnection implements DbConnection
{
    public function queryRows(string $sql, array $params = []): array
    {
        throw $this->notConfigured();
    }

    public function queryScalar(string $sql, array $params = []): mixed
    {
        throw $this->notConfigured();
    }

    public function execute(string $sql, array $params = []): int
    {
        throw $this->notConfigured();
    }

    public function escapeLiteral(string $value): string
    {
        throw $this->notConfigured();
    }

    public function getConfig(): DbConfig
    {
        throw $this->notConfigured();
    }

    private function notConfigured(): DbException
    {
        return new DbException(
            'database not configured: set MANDYRN_DB_HOST and MANDYRN_DB_NAME '
            . '(plus MANDYRN_DB_USER/MANDYRN_DB_PASS/MANDYRN_DB_PORT as needed) '
            . 'or start the server with --config <file.json>'
        );
    }
}
