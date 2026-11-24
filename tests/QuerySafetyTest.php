<?php

namespace Tests;

use InvalidArgumentException;
use Mandryn\db\Query;
use Mandryn\db\constant\AppenderOperator;
use Mandryn\db\constant\ConditionType;
use Mandryn\db\constant\DataType;
use Mandryn\db\constant\QueryType;
use Mandryn\db\constant\SqlStringType;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Safety coverage for the SQL_STRING path of the query builder.
 *
 * SQL_STRING interpolates values into the statement, so it is the mode where a
 * builder can hand an injectable string to the database. These tests pin the
 * three defences: values are escaped, declared INT fields carry only integers,
 * and an unscoped UPDATE/DELETE cannot be built by accident.
 */
final class QuerySafetyTest extends TestCase
{
    private function escaper(): callable
    {
        return static function ($value) {
            return str_replace("'", "''", $value);
        };
    }

    public function testStringValueIsEscapedBeforeInterpolation(): void
    {
        $query = new Query(QueryType::INSERT);
        $query->setTable('users');
        $query->setValueEscaper($this->escaper());
        $query->setInsertField('name', "x'); DROP TABLE users; --", DataType::STR);

        $sql = $query->getQueryString(SqlStringType::SQL_STRING);

        $this->assertSame(
            "INSERT INTO users (name) VALUES ('x''); DROP TABLE users; --')",
            $sql,
            'The quote must be neutralised so the payload stays inside the literal.'
        );
    }

    public function testSQLStringModeRefusesToEmitAnUnescapedStringValue(): void
    {
        $query = new Query(QueryType::INSERT);
        $query->setTable('users');
        $query->setInsertField('name', 'John Doe', DataType::STR);

        $this->expectException(RuntimeException::class);

        $query->getQueryString(SqlStringType::SQL_STRING);
    }

    public function testIntFieldRejectsNonNumericValue(): void
    {
        $query = new Query(QueryType::INSERT);
        $query->setTable('users');
        $query->setInsertField('age', '1); DROP TABLE users; --', DataType::INT);

        $this->expectException(InvalidArgumentException::class);

        $query->getQueryString(SqlStringType::SQL_STRING);
    }

    public function testIntFieldAcceptsNumericString(): void
    {
        $query = new Query(QueryType::INSERT);
        $query->setTable('users');
        $query->setInsertField('age', '30', DataType::INT);

        $this->assertSame(
            'INSERT INTO users (age) VALUES (30)',
            $query->getQueryString(SqlStringType::SQL_STRING)
        );
    }

    public function testDeleteWithoutConditionsIsRefused(): void
    {
        $query = new Query(QueryType::DELETE);
        $query->setTable('users');

        $this->expectException(RuntimeException::class);

        $query->getQueryString(SqlStringType::SQL_STRING);
    }

    public function testUpdateWithoutConditionsIsRefused(): void
    {
        $query = new Query(QueryType::UPDATE);
        $query->setTable('users');
        $query->setValueEscaper($this->escaper());
        $query->setUpdateField('status', 'inactive', DataType::STR);

        $this->expectException(RuntimeException::class);

        $query->getQueryString(SqlStringType::SQL_STRING);
    }

    public function testUnscopedWriteIsAllowedWhenExplicitlyOptedIn(): void
    {
        $query = new Query(QueryType::DELETE);
        $query->setTable('users');
        $query->allowUnscopedWrite(true);

        $this->assertSame('DELETE FROM users', $query->getQueryString(SqlStringType::SQL_STRING));
    }

    public function testSelectWithoutConditionsHasNoDanglingWhere(): void
    {
        $query = new Query(QueryType::SELECT);
        $query->setTable('users');
        $query->setSelectField('id');

        $this->assertSame(
            'SELECT id FROM users',
            $query->getQueryString(SqlStringType::SQL_STRING)
        );
    }

    public function testSetSelectFieldAcceptsSeveralArguments(): void
    {
        $query = new Query(QueryType::SELECT);
        $query->setTable('users');
        $query->setSelectField('id', 'name', 'email');

        $this->assertSame(
            'SELECT id,name,email FROM users',
            $query->getQueryString(SqlStringType::SQL_STRING)
        );
    }

    public function testInvalidTableNameIsRejected(): void
    {
        $query = new Query(QueryType::SELECT);

        $this->expectException(InvalidArgumentException::class);

        $query->setTable('users; DROP TABLE pengguna');
    }

    public function testHostileInsertFieldNameIsRejected(): void
    {
        $query = new Query(QueryType::INSERT);
        $query->setTable('users');
        $query->setValueEscaper($this->escaper());
        $query->setInsertField("name) VALUES ('x'); --", 'value', DataType::STR);

        $this->expectException(InvalidArgumentException::class);

        $query->getQueryString(SqlStringType::SQL_STRING);
    }

    public function testExpressionSelectFieldIsStillAllowed(): void
    {
        $query = new Query(QueryType::SELECT);
        $query->setTable('users');
        $query->setSelectField('COUNT(*) AS total');

        $this->assertSame(
            'SELECT COUNT(*) AS total FROM users',
            $query->getQueryString(SqlStringType::SQL_STRING)
        );
    }

    public function testPreparedStatementModeNeedsNoEscaper(): void
    {
        $query = new Query(QueryType::INSERT);
        $query->setTable('users');
        $query->setInsertField('name', "x'); DROP TABLE users; --", DataType::STR);

        $this->assertSame(
            'INSERT INTO users (name) VALUES (:name)',
            $query->getQueryString(SqlStringType::PREPARE_STATEMENT)
        );
    }

    public function testEscapedStringValueInCondition(): void
    {
        $query = new Query(QueryType::SELECT);
        $query->setTable('users');
        $query->setValueEscaper($this->escaper());
        $query->setSelectField('id');
        $query->setConditionField('name', ConditionType::EQUAL, "O'Brien", DataType::STR, AppenderOperator::NONE_OPR);

        $this->assertSame(
            "SELECT id FROM users WHERE name='O''Brien'",
            $query->getQueryString(SqlStringType::SQL_STRING)
        );
    }
}
