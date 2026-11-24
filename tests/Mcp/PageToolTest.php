<?php

namespace Tests\Mcp;

use Mandryn\Mcp\GuardException;
use Mandryn\Mcp\Tools\PageTool;
use PHPUnit\Framework\TestCase;

final class PageToolTest extends TestCase
{
    public function testPageReturnsRowsAndTotals(): void
    {
        $connection = new FakeDbConnection(scalarValue: 7);
        $factoryCalls = [];
        $tool = new PageTool($connection, function (array $connectionDetail, string $sql, int $page, int $pageSize) use (&$factoryCalls): array {
            $factoryCalls[] = [$connectionDetail, $sql, $page, $pageSize];

            return [['id' => 1]];
        });

        $result = $tool->call(['sql' => 'SELECT id FROM users ORDER BY id', 'page' => 1, 'page_size' => 10]);

        self::assertSame(7, $result['total']);
        self::assertSame(1, $result['total_pages']);
        self::assertSame(1, $result['page']);
        self::assertSame(10, $result['page_size']);
        self::assertSame([['id' => 1]], $result['rows']);

        self::assertCount(1, $factoryCalls);
        self::assertSame('fake-host', $factoryCalls[0][0]['host']);
        self::assertSame('SELECT id FROM users ORDER BY id', $factoryCalls[0][1]);
        self::assertSame(1, $factoryCalls[0][2]);
        self::assertSame(10, $factoryCalls[0][3]);

        // count ran on the wrapped statement
        self::assertSame(
            'SELECT COUNT(*) AS total_rows FROM ( SELECT id FROM users ORDER BY id ) AS _mandryn_count',
            $connection->calls[0]['sql'],
        );
    }

    public function testRequestedPageIsClampedToTotalPages(): void
    {
        $connection = new FakeDbConnection(scalarValue: 25);
        $factoryCalls = [];
        $tool = new PageTool($connection, function (array $connectionDetail, string $sql, int $page, int $pageSize) use (&$factoryCalls): array {
            $factoryCalls[] = $page;

            return [];
        });

        $result = $tool->call(['sql' => 'SELECT id FROM users', 'page' => 9, 'page_size' => 10]);

        self::assertSame(3, $result['total_pages']);
        self::assertSame(3, $result['page']);
        self::assertSame([3], $factoryCalls);
    }

    public function testWriteStatementIsRejected(): void
    {
        $tool = new PageTool(new FakeDbConnection(), null);

        $this->expectException(GuardException::class);
        $this->expectExceptionMessage('mandryn_page only supports read statements');
        $tool->call(['sql' => 'DELETE FROM users', 'page' => 1]);
    }

    public function testSqlWithLimitIsRejected(): void
    {
        $tool = new PageTool(new FakeDbConnection(), null);

        $this->expectException(GuardException::class);
        $this->expectExceptionMessage('LIMIT');
        $tool->call(['sql' => 'SELECT id FROM users LIMIT 5', 'page' => 1]);
    }

    public function testCteIsRejected(): void
    {
        $tool = new PageTool(new FakeDbConnection(), null);

        $this->expectException(GuardException::class);
        $this->expectExceptionMessage('CTEs are not supported');
        $tool->call(['sql' => 'WITH c AS (SELECT 1 AS id) SELECT * FROM c', 'page' => 1]);
    }

    public function testNamedParamsAreRejected(): void
    {
        $tool = new PageTool(new FakeDbConnection(), null);

        $this->expectException(GuardException::class);
        $this->expectExceptionMessage('named parameters');
        $tool->call(['sql' => 'SELECT * FROM t WHERE a = :name', 'params' => ['x'], 'page' => 1]);
    }

    public function testParamsAreEscapedIntoTheSql(): void
    {
        $connection = new FakeDbConnection(scalarValue: 3);
        $factoryCalls = [];
        $tool = new PageTool($connection, function (array $connectionDetail, string $sql, int $page, int $pageSize) use (&$factoryCalls): array {
            $factoryCalls[] = $sql;

            return [];
        });

        $tool->call(['sql' => "SELECT * FROM t WHERE name = ? AND active = ?", 'params' => ["O'Brien", 1], 'page' => 1]);

        self::assertSame("SELECT * FROM t WHERE name = 'O\\'Brien' AND active = 1", $factoryCalls[0]);
    }

    public function testParamCountMismatchIsRejected(): void
    {
        $tool = new PageTool(new FakeDbConnection(), null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('mismatch');
        $tool->call(['sql' => 'SELECT * FROM t WHERE a = ? AND b = ?', 'params' => [1], 'page' => 1]);
    }

    public function testZeroTotalShortCircuitsWithoutPager(): void
    {
        $connection = new FakeDbConnection(scalarValue: 0);
        $factoryCalled = false;
        $tool = new PageTool($connection, function (array $connectionDetail, string $sql, int $page, int $pageSize) use (&$factoryCalled): array {
            $factoryCalled = true;

            return [];
        });

        $result = $tool->call(['sql' => 'SELECT id FROM users', 'page' => 1]);

        self::assertSame(0, $result['total']);
        self::assertSame(0, $result['total_pages']);
        self::assertSame([], $result['rows']);
        self::assertFalse($factoryCalled);
    }

    public function testTrailingSemicolonIsStripped(): void
    {
        $connection = new FakeDbConnection(scalarValue: 1);
        $factoryCalls = [];
        $tool = new PageTool($connection, function (array $connectionDetail, string $sql, int $page, int $pageSize) use (&$factoryCalls): array {
            $factoryCalls[] = $sql;

            return [];
        });

        $tool->call(['sql' => 'SELECT id FROM users;', 'page' => 1]);

        self::assertSame('SELECT id FROM users', $factoryCalls[0]);
    }

    public function testSchemaShape(): void
    {
        $tool = new PageTool(new FakeDbConnection(), null);

        self::assertSame('mandryn_page', $tool->name());
        $schema = $tool->inputSchema();
        self::assertSame('object', $schema['type']);
        self::assertArrayHasKey('sql', $schema['properties']);
        self::assertArrayHasKey('params', $schema['properties']);
        self::assertArrayHasKey('page', $schema['properties']);
        self::assertArrayHasKey('page_size', $schema['properties']);
    }
}
