<?php

namespace Mandryn\Mcp\Connection;

use Mandryn\Mcp\DbException;

final class MysqliDbConnection implements DbConnection
{
    private ?\mysqli $link = null;

    public function __construct(private readonly DbConfig $config)
    {
    }

    public function queryRows(string $sql, array $params = []): array
    {
        $stmt = $this->prepare($sql);
        $this->bindValues($stmt, $params);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new DbException('Query failed: ' . $error);
        }

        $result = $stmt->get_result();
        if ($result === false) {
            $error = $stmt->error;
            $stmt->close();
            throw new DbException('Query returned no result set: ' . $error);
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        $stmt->close();

        return $rows;
    }

    public function queryScalar(string $sql, array $params = []): mixed
    {
        $stmt = $this->prepare($sql);
        $this->bindValues($stmt, $params);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new DbException('Query failed: ' . $error);
        }

        $result = $stmt->get_result();
        if ($result === false) {
            $error = $stmt->error;
            $stmt->close();
            throw new DbException('Query returned no result set: ' . $error);
        }

        $row = $result->fetch_array(MYSQLI_NUM);
        $result->free();
        $stmt->close();

        if ($row === null || $row === false) {
            return null;
        }

        return $row[0];
    }

    public function execute(string $sql, array $params = []): int
    {
        if ($params === []) {
            $ok = $this->link()->query($sql);
            if ($ok === false) {
                throw new DbException('Command failed: ' . $this->link()->error);
            }
            if ($ok === true) {
                return $this->link()->affected_rows;
            }
            $ok->free();

            return $this->link()->affected_rows;
        }

        $stmt = $this->prepare($sql);
        $this->bindValues($stmt, $params);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new DbException('Command failed: ' . $error);
        }

        $affected = $stmt->affected_rows;
        $stmt->close();

        return $affected;
    }

    public function escapeLiteral(string $value): string
    {
        return $this->link()->real_escape_string($value);
    }

    public function getConfig(): DbConfig
    {
        return $this->config;
    }

    private function link(): \mysqli
    {
        if ($this->link !== null) {
            return $this->link;
        }

        mysqli_report(MYSQLI_REPORT_OFF);
        $link = @mysqli_connect(
            $this->config->host,
            $this->config->user,
            $this->config->password,
            $this->config->name,
            $this->config->port,
        );

        if ($link === false) {
            throw new DbException('Connection failed: ' . mysqli_connect_error());
        }

        $link->set_charset('utf8mb4');
        $this->link = $link;

        return $link;
    }

    private function prepare(string $sql): \mysqli_stmt
    {
        $stmt = $this->link()->prepare($sql);
        if ($stmt === false) {
            throw new DbException('Prepare failed: ' . $this->link()->error);
        }

        return $stmt;
    }

    /**
     * @param list<mixed> $params
     */
    private function bindValues(\mysqli_stmt $stmt, array $params): void
    {
        if ($params === []) {
            return;
        }

        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param) || $param === null) {
                $types .= 's';
            } else {
                throw new \InvalidArgumentException(
                    'Unsupported parameter type: ' . get_debug_type($param) . '. Use scalar or null values only.'
                );
            }
        }

        if (!$stmt->bind_param($types, ...$params)) {
            throw new DbException('Bind failed: ' . $stmt->error);
        }
    }
}
