<?php

namespace Tests\Mcp;

use Mandryn\Mcp\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;

final class SchemaToolTest extends TestCase
{
    private const TABLES_SQL = "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME";

    private const COLUMNS_SQL = 'SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME, ORDINAL_POSITION';

    private const TABLES_SQL_FILTERED = "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME = ? ORDER BY TABLE_NAME";

    private const COLUMNS_SQL_FILTERED = 'SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY TABLE_NAME, ORDINAL_POSITION';

    public function testFullSchemaIsGroupedByTable(): void
    {
        $connection = new FakeDbConnection(
            rowsBySql: [
                self::TABLES_SQL => [
                    ['TABLE_NAME' => 'orders'],
                    ['TABLE_NAME' => 'users'],
                ],
                self::COLUMNS_SQL => [
                    ['TABLE_NAME' => 'users', 'COLUMN_NAME' => 'id', 'COLUMN_TYPE' => 'int unsigned', 'IS_NULLABLE' => 'NO', 'COLUMN_KEY' => 'PRI', 'EXTRA' => 'auto_increment'],
                    ['TABLE_NAME' => 'users', 'COLUMN_NAME' => 'name', 'COLUMN_TYPE' => 'varchar(120)', 'IS_NULLABLE' => 'YES', 'COLUMN_KEY' => '', 'EXTRA' => ''],
                    ['TABLE_NAME' => 'orders', 'COLUMN_NAME' => 'user_id', 'COLUMN_TYPE' => 'int', 'IS_NULLABLE' => 'NO', 'COLUMN_KEY' => 'MUL', 'EXTRA' => ''],
                    ['TABLE_NAME' => 'v_archive', 'COLUMN_NAME' => 'x', 'COLUMN_TYPE' => 'int', 'IS_NULLABLE' => 'NO', 'COLUMN_KEY' => '', 'EXTRA' => ''],
                ],
            ],
            scalarValue: 'mydb',
        );
        $tool = new SchemaTool($connection);

        $result = $tool->call([]);

        self::assertSame('mydb', $result['database']);
        self::assertSame(2, $result['total_tables']);
        self::assertCount(2, $result['tables']);

        self::assertSame('orders', $result['tables'][0]['name']);
        self::assertSame('users', $result['tables'][1]['name']);

        $userColumns = $result['tables'][1]['columns'];
        self::assertCount(2, $userColumns);
        self::assertSame('id', $userColumns[0]['name']);
        self::assertSame('int unsigned', $userColumns[0]['type']);
        self::assertFalse($userColumns[0]['nullable']);
        self::assertSame('PRI', $userColumns[0]['key']);
        self::assertSame('auto_increment', $userColumns[0]['extra']);
        self::assertTrue($userColumns[1]['nullable']);
        self::assertNull($userColumns[1]['key']);
        self::assertNull($userColumns[1]['extra']);

        // the view's columns must not leak into the base-table listing
        self::assertSame('orders', $result['tables'][0]['name']);
        self::assertCount(1, $result['tables'][0]['columns']);
    }

    public function testTableFilterForwardsParam(): void
    {
        $connection = new FakeDbConnection(
            rowsBySql: [
                self::TABLES_SQL_FILTERED => [['TABLE_NAME' => 'users']],
                self::COLUMNS_SQL_FILTERED => [
                    ['TABLE_NAME' => 'users', 'COLUMN_NAME' => 'id', 'COLUMN_TYPE' => 'int', 'IS_NULLABLE' => 'NO', 'COLUMN_KEY' => 'PRI', 'EXTRA' => ''],
                ],
            ],
            scalarValue: 'mydb',
        );
        $tool = new SchemaTool($connection);

        $result = $tool->call(['table' => 'users']);

        self::assertSame(1, $result['total_tables']);
        self::assertSame('SELECT DATABASE()', $connection->calls[0]['sql']);
        self::assertSame(['users'], $connection->calls[1]['params']);
        self::assertSame(['users'], $connection->calls[2]['params']);
    }

    public function testInvalidTableNameIsRejected(): void
    {
        $tool = new SchemaTool(new FakeDbConnection());

        $this->expectException(\InvalidArgumentException::class);
        $tool->call(['table' => 'users; DROP TABLE users']);
    }

    public function testEmptyDatabaseYieldsEmptyTables(): void
    {
        $connection = new FakeDbConnection(scalarValue: 'empty_db');
        $tool = new SchemaTool($connection);

        $result = $tool->call([]);

        self::assertSame('empty_db', $result['database']);
        self::assertSame(0, $result['total_tables']);
        self::assertSame([], $result['tables']);
    }

    public function testSchemaShape(): void
    {
        $tool = new SchemaTool(new FakeDbConnection());

        self::assertSame('mandryn_schema', $tool->name());
        self::assertSame('object', $tool->inputSchema()['type']);
        self::assertArrayHasKey('table', $tool->inputSchema()['properties']);
    }
}
