<?php

namespace Tests\Mcp;

use Mandryn\Mcp\GuardException;
use Mandryn\Mcp\SqlGuard;
use Mandryn\Mcp\Tools\QueryTool;
use PHPUnit\Framework\TestCase;

final class QueryToolTest extends TestCase
{
    public function testReadQueryReturnsRows(): void
    {
        $connection = new FakeDbConnection([
            'SELECT id, name FROM users LIMIT 100' => [['id' => 1, 'name' => 'Ilham']],
        ]);
        $tool = new QueryTool($connection, new SqlGuard());

        $result = $tool->call(['sql' => 'SELECT id, name FROM users']);

        self::assertSame([['id' => 1, 'name' => 'Ilham']], $result['rows']);
        self::assertSame(1, $result['count']);
        self::assertFalse($result['truncated']);
    }

    public function testLimitIsAutoAppended(): void
    {
        $connection = new FakeDbConnection();
        $tool = new QueryTool($connection, new SqlGuard());

        $tool->call(['sql' => 'SELECT 1']);

        self::assertSame('SELECT 1 LIMIT 100', $connection->calls[0]['sql']);
    }

    public function testExistingLimitIsNotDuplicated(): void
    {
        $connection = new FakeDbConnection();
        $tool = new QueryTool($connection, new SqlGuard());

        $tool->call(['sql' => 'SELECT 1 LIMIT 5']);

        self::assertSame('SELECT 1 LIMIT 5', $connection->calls[0]['sql']);
    }

    public function testLimitArgumentIsRespectedAndCapped(): void
    {
        $connection = new FakeDbConnection();
        $tool = new QueryTool($connection, new SqlGuard());

        $tool->call(['sql' => 'SELECT 1', 'limit' => 25]);
        self::assertSame('SELECT 1 LIMIT 25', $connection->calls[0]['sql']);

        $tool->call(['sql' => 'SELECT 1', 'limit' => 99999]);
        self::assertSame('SELECT 1 LIMIT 1000', $connection->calls[1]['sql']);
    }

    public function testParamsAreForwardedPositionally(): void
    {
        $connection = new FakeDbConnection();
        $tool = new QueryTool($connection, new SqlGuard());

        $tool->call(['sql' => 'SELECT * FROM t WHERE a = ? AND b = ?', 'params' => [1, 'x']]);

        self::assertSame([1, 'x'], $connection->calls[0]['params']);
    }

    public function testWriteIsBlockedByDefault(): void
    {
        $connection = new FakeDbConnection();
        $tool = new QueryTool($connection, new SqlGuard());

        $this->expectException(GuardException::class);
        $tool->call(['sql' => 'DELETE FROM users']);
    }

    public function testWriteRunsWithAllowWrites(): void
    {
        $connection = new FakeDbConnection(affectedRows: 3);
        $tool = new QueryTool($connection, new SqlGuard(true));

        $result = $tool->call(['sql' => 'DELETE FROM users WHERE id = ?', 'params' => [7]]);

        self::assertSame(['affected_rows' => 3], $result);
        self::assertSame('DELETE FROM users WHERE id = ?', $connection->calls[0]['sql']);
    }

    public function testMultiStatementIsRejected(): void
    {
        $connection = new FakeDbConnection();
        $tool = new QueryTool($connection, new SqlGuard());

        $this->expectException(GuardException::class);
        $tool->call(['sql' => 'SELECT 1; DROP TABLE users']);
    }

    public function testTrailingSemicolonIsStrippedBeforeExecution(): void
    {
        $connection = new FakeDbConnection();
        $tool = new QueryTool($connection, new SqlGuard());

        $tool->call(['sql' => 'SHOW TABLES;']);

        self::assertSame('SHOW TABLES LIMIT 100', $connection->calls[0]['sql']);
    }

    public function testMissingSqlArgumentThrows(): void
    {
        $tool = new QueryTool(new FakeDbConnection(), new SqlGuard());

        $this->expectException(\InvalidArgumentException::class);
        $tool->call([]);
    }

    public function testInvalidLimitThrows(): void
    {
        $tool = new QueryTool(new FakeDbConnection(), new SqlGuard());

        $this->expectException(\InvalidArgumentException::class);
        $tool->call(['sql' => 'SELECT 1', 'limit' => 'many']);
    }

    public function testTruncatedFlagWhenRowsHitTheLimit(): void
    {
        $rows = [];
        for ($i = 0; $i < 100; $i++) {
            $rows[] = ['id' => $i];
        }
        $connection = new FakeDbConnection(['SELECT * FROM t LIMIT 100' => $rows]);
        $tool = new QueryTool($connection, new SqlGuard());

        $result = $tool->call(['sql' => 'SELECT * FROM t']);

        self::assertTrue($result['truncated']);
    }

    public function testSchemaShape(): void
    {
        $tool = new QueryTool(new FakeDbConnection(), new SqlGuard());

        self::assertSame('mandryn_query', $tool->name());
        self::assertSame('object', $tool->inputSchema()['type']);
        self::assertArrayHasKey('sql', $tool->inputSchema()['properties']);
        self::assertArrayHasKey('params', $tool->inputSchema()['properties']);
        self::assertArrayHasKey('limit', $tool->inputSchema()['properties']);
    }
}
