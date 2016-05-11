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

    public function __construct($QueryType) {
        $this->queryType = $QueryType;
        $this->updateFields = [];
        $this->conditionFields = [];
    }

    public function setTable($tableName) {
        $this->tableName = $tableName;
    }

    public function setCreateTableEngineType($EngineType) {
        $this->tableEngineType = $EngineType;
    }

    public function setCreateTableStructureSQL($sql) {
        $this->tableStructureSQL = $sql;
    }

    public function setUpdateField($fieldName, $value, $DataType) {
        $this->updateFields[] = array($fieldName, $value, $DataType);
    }

    public function setConditionField($fieldName, $ConditionType, $value, $DataType, $AppenderOperator) {
        $this->conditionFields[] = array($fieldName, $ConditionType, $value, $DataType, $AppenderOperator);
    }

    public function getQueryString() {
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

    private function getUpdateSQL() {
        $sqlStatement = "UPDATE {$this->tableName} ";

        $updateFieldsArray = [];

        foreach ($this->updateFields as $fld) {
            if ($fld[2] === \Mandryn\db\constant\DataType::INT) {
                $updateFieldsArray[] = "{$fld[0]}={$fld[1]}";
            } else {
                $updateFieldsArray[] = "{$fld[0]}='{$fld[1]}'";
            }
        }

        $sqlStatement.="SET " . implode(',', $updateFieldsArray) . ' ';

        $conditionFieldsArray = [];

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

        $sqlStatement.="WHERE " . implode(' ', $conditionFieldsArray);

        return $sqlStatement;
    }

    private function getDeleteSQL() {
        $sqlStatement = "DELETE FROM {$this->tableName} ";

        $conditionFieldsArray = [];

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

        $sqlStatement.="WHERE " . implode(' ', $conditionFieldsArray);

        return $sqlStatement;
    }

}
