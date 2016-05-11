<?php
namespace Mandryn\db;
class Query {

    public $queryType;
    public $tableName;
    private $tableEngineType;
    private $tableStructureSQL;
    public $returnFields;
    public $updateFields;
    public $conditionFields;

    public function __construct($IQueryType) {
        $this->queryType = $IQueryType;
        $this->updateFields = [];
        $this->conditionFields = [];
    }

    public function setTable($tableName) {
        $this->tableName = $tableName;
    }

    public function setCreateTableEngineType($IEngineType) {
        $this->tableEngineType = $IEngineType;
    }

    public function setCreateTableStructureSQL($sql) {
        $this->tableStructureSQL = $sql;
    }

    public function setUpdateField($fieldName, $value, $IDataType) {
        $this->updateFields[] = array($fieldName, $value, $IDataType);
    }

    public function setConditionField($fieldName, $IConditionType, $value, $IDataType, $IAppenderOperator) {
        $this->conditionFields[] = array($fieldName, $IConditionType, $value, $IDataType, $IAppenderOperator);
    }

    public function getQueryString() {
        $sqlStatement = '';

        if ($this->queryType === IQueryType::CREATE) {
            $sqlStatement = $this->getCreateSQL();
        }

        if ($this->queryType === IQueryType::UPDATE) {
            $sqlStatement = $this->getUpdateSQL();
        }

        if ($this->queryType === IQueryType::DELETE) {
            $sqlStatement=  $this->getDeleteSQL();
        }

        return $sqlStatement;
    }

    private function getCreateSQL() {
        $sqlStatement = "CREATE TABLE {$this->tableName} ENGINE={$this->tableEngineType} ";
        $sqlStatement.=$this->tableStructureSQL;
        return $sqlStatement;
    }

    private function getUpdateSQL() {
        $sqlStatement = "UPDATE {$this->tableName} ";

        $updateFieldsArray = [];

        foreach ($this->updateFields as $fld) {
            if ($fld[2] === IDataType::INT) {
                $updateFieldsArray[] = "{$fld[0]}={$fld[1]}";
            } else {
                $updateFieldsArray[] = "{$fld[0]}='{$fld[1]}'";
            }
        }

        $sqlStatement.="SET " . implode(',', $updateFieldsArray) . ' ';

        $conditionFieldsArray = [];

        foreach ($this->conditionFields as $fld) {

            $appender = ($fld[4] === IAppenderOperator::NONE_OPR) ? '' : ($fld[4] . ' ');

            if ($fld[3] === IDataType::INT) {
                $conditionFieldsArray[] = "{$appender}{$fld[0]}{$fld[1]}{$fld[2]}";
            } else {
                $conditionFieldsArray[] = "{$appender}{$fld[0]}{$fld[1]}'{$fld[2]}'";
            }
        }

        $sqlStatement.="WHERE " . implode(' ', $conditionFieldsArray);
        
        return $sqlStatement;
    }

    private function getDeleteSQL() {
        $sqlStatement = "DELETE FROM {$this->tableName} ";

        $conditionFieldsArray = [];

        foreach ($this->conditionFields as $fld) {

            $appender = ($fld[4] === IAppenderOperator::NONE_OPR) ? '' : ($fld[4] . ' ');

            if ($fld[3] === IDataType::INT) {
                $conditionFieldsArray[] = "{$appender}{$fld[0]}{$fld[1]}{$fld[2]}";
            } else {
                $conditionFieldsArray[] = "{$appender}{$fld[0]}{$fld[1]}'{$fld[2]}'";
            }
        }

        $sqlStatement.="WHERE " . implode(' ', $conditionFieldsArray);
        
        return $sqlStatement;
    }

}

interface IConditionType{
    const EQUAL='=';
    const NOT_EQUAL='!=';
    const GREATER_THAN='>';
    const GREATER_THAN_OR_EQUAL='>=';
    const LESS_THAN='<';
    const LESS_THAN_OR_EQUAL='<=';
    const LIKE=' LIKE ';
    
}

interface IEngineType {

    const MyISAM = 'MyISAM';
    const InnoDB = 'InnoDB';
    const BerkeleyDB = 'BerkeleyDB';
    const BLACKHOLE = 'BLACKHOLE';
    const MEMORY = 'MEMORY';

}

interface IDataType {

    const TINYINT = 1;
    const SMALLINT = 2;
    const INT = 3;
    const FLOAT = 4;
    const DOUBLE = 5;
    const TIMESTAMP = 7;
    const BIGINT = 8;
    const MEDIUMINT = 9;
    const DATE = 10;
    const TIME = 11;
    const DATETIME = 12;
    const YEAR = 13;
    const BIT = 16;
    const BLOB = 255;
    const VARCHAR = 253;
    const CHAR = 254;
    const DECIMAL = 246;

}

interface IQueryType {

    const CREATE = 'CREATE';
    const SELECT = 'SELECT';
    const UPDATE = 'UPDATE';
    const DELETE = 'DELETE';

}

interface IAppenderOperator {

    const NONE_OPR = 'NONE';
    const AND_OPR = 'AND';
    const OR_OPR = 'OR';

}
