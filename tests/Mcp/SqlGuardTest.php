<?php

namespace Tests\Mcp;

use Mandryn\Mcp\GuardException;
use Mandryn\Mcp\SqlGuard;
use PHPUnit\Framework\TestCase;

final class SqlGuardTest extends TestCase
{
    public function testPlainSelectIsRead(): void
    {
        $guard = new SqlGuard();

        self::assertSame('read', $guard->assertAllowed('SELECT * FROM users'));
    }

    public function testTrailingSemicolonIsAllowed(): void
    {
        $guard = new SqlGuard();

        self::assertSame('read', $guard->assertAllowed('SELECT 1;'));
    }

    public function testTrailingSemicolonWithWhitespaceIsAllowed(): void
    {
        $guard = new SqlGuard();

        self::assertSame('read', $guard->assertAllowed("SHOW TABLES; \n\t"));
    }

    public function testLeadingLineCommentsAreStripped(): void
    {
        $guard = new SqlGuard();

        self::assertSame('read', $guard->assertAllowed("-- a comment\nSELECT 1"));
        self::assertSame('read', $guard->assertAllowed("# a comment\nSELECT 1"));
    }

    public function testLeadingBlockCommentIsStripped(): void
    {
        $guard = new SqlGuard();

        self::assertSame('read', $guard->assertAllowed("/* header */ SELECT 1"));
        self::assertSame('read', $guard->assertAllowed("/* multi\nline */\nSELECT 1"));
    }

    public function testMultipleLeadingCommentsAreStripped(): void
    {
        $guard = new SqlGuard();

        self::assertSame('read', $guard->assertAllowed("/* a */ -- b\n/* c */ SELECT 1"));
    }

    public function testMultiStatementIsRejected(): void
    {
        $guard = new SqlGuard();

        $this->expectException(GuardException::class);
        $this->expectExceptionMessage('multiple SQL statements');
        $guard->assertAllowed('SELECT 1; DROP TABLE users');
    }

    public function testSemicolonInsideSingleQuotedLiteralIsFine(): void
    {
        $guard = new SqlGuard();

        self::assertSame('read', $guard->assertAllowed("SELECT ';'"));
    }

    public function testSemicolonInsideDoubleQuotedLiteralIsFine(): void
    {
        $guard = new SqlGuard();

        self::assertSame('read', $guard->assertAllowed('SELECT ";"'));
    }

    public function testSemicolonInsideBacktickIdentifierIsFine(): void
    {
        $guard = new SqlGuard();

        self::assertSame('read', $guard->assertAllowed('SELECT `weird;name` FROM t'));
    }

    public function testEscapedQuoteInsideLiteralIsFine(): void
    {
        $guard = new SqlGuard();

        self::assertSame('read', $guard->assertAllowed("SELECT 'it\\'s; ok'"));
    }

    public function testWriteStatementsAreBlockedByDefault(): void
    {
        $guard = new SqlGuard();

        foreach (['DELETE', 'INSERT', 'UPDATE', 'CREATE', 'DROP', 'ALTER', 'TRUNCATE', 'REPLACE'] as $verb) {
            try {
                $guard->assertAllowed("{$verb} x");
                self::fail("Expected GuardException for {$verb}");
            } catch (GuardException $e) {
                self::assertStringContainsString('--allow-writes', $e->getMessage());
            }
        }

        self::assertTrue(true);
    }

    public function testWriteStatementsPassWithAllowWrites(): void
    {
        $guard = new SqlGuard(true);

        self::assertSame('write', $guard->assertAllowed('DELETE FROM users WHERE id = 1'));
        self::assertSame('write', $guard->assertAllowed('INSERT INTO t (a) VALUES (1)'));
    }

    public function testUnknownKeywordIsClassifiedAsWrite(): void
    {
        $guard = new SqlGuard(true);

        self::assertSame('write', $guard->assertAllowed('KILL 42'));
    }

    public function testWithCteSelectIsRead(): void
    {
        $guard = new SqlGuard();

        self::assertSame('read', $guard->assertAllowed('WITH cte AS (SELECT 1 AS n) SELECT * FROM cte'));
    }

    public function testWithCteDeleteIsWrite(): void
    {
        $guard = new SqlGuard(true);

        self::assertSame('write', $guard->assertAllowed('WITH cte AS (SELECT id FROM t) DELETE FROM t WHERE id IN (SELECT id FROM cte)'));
    }

    public function testWithCteDeleteIsBlockedByDefault(): void
    {
        $guard = new SqlGuard();

        $this->expectException(GuardException::class);
        $guard->assertAllowed('WITH cte AS (SELECT id FROM t) DELETE FROM t WHERE id IN (SELECT id FROM cte)');
    }

    public function testParenthesizedSelectIsRead(): void
    {
        $guard = new SqlGuard();

        self::assertSame('read', $guard->assertAllowed('(SELECT 1)'));
    }

    public function testEmptyStatementIsRejected(): void
    {
        $guard = new SqlGuard();

        $this->expectException(GuardException::class);
        $this->expectExceptionMessage('empty SQL statement');
        $guard->assertAllowed('   ');
    }

    public function testCommentOnlyStatementIsRejected(): void
    {
        $guard = new SqlGuard();

        $this->expectException(GuardException::class);
        $guard->assertAllowed('-- nothing here');
    }

    public function testUnterminatedBlockCommentIsRejected(): void
    {
        $guard = new SqlGuard();

        $this->expectException(GuardException::class);
        $guard->assertAllowed('/* never closed SELECT 1');
    }

    public function testDescribeAndExplainAreRead(): void
    {
        $guard = new SqlGuard();

        self::assertSame('read', $guard->assertAllowed('DESCRIBE users'));
        self::assertSame('read', $guard->assertAllowed('DESC users'));
        self::assertSame('read', $guard->assertAllowed('EXPLAIN SELECT * FROM users'));
        self::assertSame('read', $guard->assertAllowed('SHOW TABLES'));
    }
}
