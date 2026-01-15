<?php

namespace Tests;

use Mandryn\db\Query;
use Mandryn\db\constant\QueryType;
use Mandryn\db\constant\SqlStringType;
use Mandryn\db\constant\DataType;
use Mandryn\db\constant\ConditionType;
use Mandryn\db\constant\AppenderOperator;
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    public function testGetQueryString_InsertPreparedStatement_CorrectSql()
    {
        $query = new Query(QueryType::INSERT);
        $query->setTable('users');
        $query->setInsertField('name', 'John Doe', DataType::STR);
        $query->setInsertField('email', 'john.doe@example.com', DataType::STR);

        $expectedSql = "INSERT INTO users (name,email) VALUES (:name,:email)";
        $actualSql = $query->getQueryString(SqlStringType::PREPARE_STATEMENT);

        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testGetQueryString_InsertSqlString_CorrectSql()
    {
        $query = new Query(QueryType::INSERT);
        $query->setTable('users');
        $query->setInsertField('name', 'John Doe', DataType::STR);
        $query->setInsertField('email', 'john.doe@example.com', DataType::STR);
        $query->setInsertField('age', 30, DataType::INT);

        $expectedSql = "INSERT INTO users (name,email,age) VALUES ('John Doe','john.doe@example.com',30)";
        $actualSql = $query->getQueryString(SqlStringType::SQL_STRING);

        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testGetQueryString_UpdateSqlString_CorrectSql()
    {
        $query = new Query(QueryType::UPDATE);
        $query->setTable('users');
        $query->setUpdateField('name', 'Jane Doe', DataType::STR);
        $query->setUpdateField('age', 31, DataType::INT);
        $query->setConditionField('id', ConditionType::EQUAL, 1, DataType::INT, AppenderOperator::NONE_OPR);

        $expectedSql = "UPDATE users SET name='Jane Doe',age=31 WHERE id=1";
        $actualSql = $query->getQueryString(SqlStringType::SQL_STRING);

        $this->assertEquals($expectedSql, $actualSql);
    }

    public function testGetQueryString_UpdatePreparedStatement_CorrectSql()
    {
        $query = new Query(QueryType::UPDATE);
        $query->setTable('users');
        $query->setUpdateField('name', 'Jane Doe', DataType::STR);
        $query->setUpdateField('age', 31, DataType::INT);
        $query->setConditionField('id', ConditionType::EQUAL, 1, DataType::INT, AppenderOperator::NONE_OPR);

        $expectedSql = "UPDATE users SET name = :name,age = :age WHERE id = :id";
        $actualSql = $query->getQueryString(SqlStringType::PREPARE_STATEMENT);

        $this->assertEquals($expectedSql, $actualSql);
    }
}
