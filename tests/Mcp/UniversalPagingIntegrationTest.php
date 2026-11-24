<?php

namespace Tests\Mcp;

use Mandryn\Mcp\Connection\DbConfig;
use Mandryn\Mcp\Connection\MysqliDbConnection;
use Mandryn\Mcp\Tools\PageTool;
use PHPUnit\Framework\TestCase;

/**
 * Live-DB integration test for the default UniversalPaging-based pager
 * factory. Skipped unless MANDYRN_TEST_DB_HOST is set, keeping CI DB-free.
 */
final class UniversalPagingIntegrationTest extends TestCase
{
    public function testDefaultPagerFactoryPagesRealRows(): void
    {
        $host = getenv('MANDYRN_TEST_DB_HOST');
        if ($host === false || $host === '') {
            self::markTestSkipped('Set MANDYRN_TEST_DB_HOST (plus MANDYRN_TEST_DB_USER/PASS/NAME) to run the live paging test.');
        }

        $config = new DbConfig(
            $host,
            (int) (getenv('MANDYRN_TEST_DB_PORT') ?: 3306),
            (string) (getenv('MANDYRN_TEST_DB_USER') ?: 'root'),
            (string) (getenv('MANDYRN_TEST_DB_PASS') ?: ''),
            (string) (getenv('MANDYRN_TEST_DB_NAME') ?: ''),
        );

        $connection = new MysqliDbConnection($config);

        $connection->execute('CREATE TABLE IF NOT EXISTS mandryn_mcp_test (id INT AUTO_INCREMENT PRIMARY KEY, label VARCHAR(50))');
        $connection->execute('DELETE FROM mandryn_mcp_test');
        for ($i = 1; $i <= 7; $i++) {
            $connection->execute('INSERT INTO mandryn_mcp_test (label) VALUES (?)', ['row-' . $i]);
        }

        try {
            $tool = new PageTool($connection); // default pager factory

            $page1 = $tool->call(['sql' => 'SELECT id, label FROM mandryn_mcp_test ORDER BY id', 'page' => 1, 'page_size' => 3]);
            self::assertSame(7, $page1['total']);
            self::assertSame(3, $page1['total_pages']);
            self::assertCount(3, $page1['rows']);
            // UniversalPaging uses a non-prepared mysqli result, so values come back as strings.
            self::assertSame('1', $page1['rows'][0]['id']);

            $page3 = $tool->call(['sql' => 'SELECT id, label FROM mandryn_mcp_test ORDER BY id', 'page' => 3, 'page_size' => 3]);
            self::assertCount(1, $page3['rows']);
            self::assertSame('7', $page3['rows'][0]['id']);

            $beyond = $tool->call(['sql' => 'SELECT id, label FROM mandryn_mcp_test ORDER BY id', 'page' => 99, 'page_size' => 3]);
            self::assertSame(3, $beyond['page']);
        } finally {
            $connection->execute('DROP TABLE mandryn_mcp_test');
        }
    }
}
