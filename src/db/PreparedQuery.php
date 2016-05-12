<?php

namespace Mandryn\db;

class PreparedQuery extends Query {

    public function __construct($QueryType) {
        parent::__construct($QueryType);
    }

    public function getPreparedQueryString() {
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

    protected function getUpdateSQL() {
        $sqlStatement = "UPDATE {$this->tableName} ";

        $updateFieldsArray = [];

        foreach ($this->updateFields as $fld) {
            $updateFieldsArray[] = "{$fld[0]} = :{$fld[0]}";
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
    
    protected function getConditionFieldsArray(){
        $conditionFieldsArray = [];
        
        foreach ($this->conditionFields as $fld) {

            $appender = ($fld[4] === \Mandryn\db\constant\AppenderOperator::NONE_OPR) ? '' : ($fld[4] . ' ');
            
            if ($fld[1] === \Mandryn\db\constant\ConditionType::IS_NULL || $fld[1] === \Mandryn\db\constant\ConditionType::IS_NOT_NULL) {
                $conditionFieldsArray[] = "{$appender}{$fld[0]} {$fld[1]}";
            } else {
                //0 - $fieldName, 1 - $ConditionType, 2 - $value, 3 - $DataType, 4 - $AppenderOperator
                $conditionFieldsArray[] = "{$appender}{$fld[0]} {$fld[1]} :{$fld[0]}";
            }
        }
        return $conditionFieldsArray;
    }

}
