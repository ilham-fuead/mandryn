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
    private $sqlStringType;

    public function __construct($QueryType) {
        $this->queryType = $QueryType;
        $this->updateFields = [];
        $this->conditionFields = [];
    }

    public function setTable($tableName) {
        $this->tableName = $tableName;
    }

    final public function setCreateTableEngineType($EngineType) {
        $this->tableEngineType = $EngineType;
    }

    final public function setCreateTableStructureSQL($sql) {
        $this->tableStructureSQL = $sql;
    }

    public function setUpdateField($fieldName, $value, $DataType) {
        $this->updateFields[] = array($fieldName, $value, $DataType);
    }

    public function setConditionField($fieldName, $ConditionType, $value, $DataType, $AppenderOperator) {
        $this->conditionFields[] = array($fieldName, $ConditionType, $value, $DataType, $AppenderOperator);
    }

    public function getQueryString($SqlStringType = \Mandryn\db\constant\SqlStringType::SQL_STRING) {
        $this->sqlStringType = $SqlStringType;

        $sqlStatement = '';

        if ($this->queryType === \Mandryn\db\constant\QueryType::CREATE) {
            $sqlStatement = $this->getCreateSQL();
        }

        if ($this->queryType === \Mandryn\db\constant\QueryType::UPDATE) {
            $sqlStatement = $this->getUpdateSQL();
        }

        if ($this->queryType === \Mandryn\db\constant\QueryType::DELETE) {
            $sqlStatement = $this->getDeleteSQL();
        }

        return $sqlStatement;
    }

    private function getCreateSQL() {
        $sqlStatement = "CREATE TABLE {$this->tableName} ENGINE={$this->tableEngineType} ";
        $sqlStatement.=$this->tableStructureSQL;
        return $sqlStatement;
    }

    protected function getUpdateSQL() {
        $sqlStatement = "UPDATE {$this->tableName} ";

        $updateFieldsArray = [];

        if ($this->sqlStringType === \Mandryn\db\constant\SqlStringType::SQL_STRING) {
            foreach ($this->updateFields as $fld) {
                if ($fld[2] === \Mandryn\db\constant\DataType::INT) {
                    $updateFieldsArray[] = "{$fld[0]}={$fld[1]}";
                } else {
                    $updateFieldsArray[] = "{$fld[0]}='{$fld[1]}'";
                }
            }
        } elseif ($this->sqlStringType === \Mandryn\db\constant\SqlStringType::PREPARE_STATEMENT) {
            foreach ($this->updateFields as $fld) {
                $updateFieldsArray[] = "{$fld[0]} = :{$fld[0]}";
            }
        }

        $sqlStatement.="SET " . implode(',', $updateFieldsArray) . ' ';

        $conditionFieldsArray = $this->getConditionFieldsArray();

        $sqlStatement.="WHERE " . implode(' ', $conditionFieldsArray);

        return $sqlStatement;
    }

    protected function getDeleteSQL() {
        $sqlStatement = "DELETE FROM {$this->tableName} ";

        $conditionFieldsArray = $this->getConditionFieldsArray();

        $sqlStatement.="WHERE " . implode(' ', $conditionFieldsArray);

        return $sqlStatement;
    }

    protected function getConditionFieldsArray() {
        $conditionFieldsArray = [];
        if ($this->sqlStringType === \Mandryn\db\constant\SqlStringType::SQL_STRING) {
            foreach ($this->conditionFields as $fld) {

                $appender = ($fld[4] === \Mandryn\db\constant\AppenderOperator::NONE_OPR) ? '' : ($fld[4] . ' ');

                if ($fld[1] === \Mandryn\db\constant\ConditionType::IS_NULL || $fld[1] === \Mandryn\db\constant\ConditionType::IS_NOT_NULL) {
                    $conditionFieldsArray[] = "{$appender}{$fld[0]} {$fld[1]}";
                } else {
                    if ($fld[3] === \Mandryn\db\constant\DataType::INT) {
                        $conditionFieldsArray[] = "{$appender}{$fld[0]}{$fld[1]}{$fld[2]}";
                    } else {
                        $conditionFieldsArray[] = "{$appender}{$fld[0]}{$fld[1]}'{$fld[2]}'";
                    }
                }
            }
        } elseif ($this->sqlStringType === \Mandryn\db\constant\SqlStringType::PREPARE_STATEMENT) {
            foreach ($this->conditionFields as $fld) {

                $appender = ($fld[4] === \Mandryn\db\constant\AppenderOperator::NONE_OPR) ? '' : ($fld[4] . ' ');

                if ($fld[1] === \Mandryn\db\constant\ConditionType::IS_NULL || $fld[1] === \Mandryn\db\constant\ConditionType::IS_NOT_NULL) {
                    $conditionFieldsArray[] = "{$appender}{$fld[0]} {$fld[1]}";
                } else {
                    $conditionFieldsArray[] = "{$appender}{$fld[0]} {$fld[1]} :{$fld[0]}";
                }
            }
        }
        return $conditionFieldsArray;
    }

}
