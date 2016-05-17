<?php

interface IFieldType {
    const STRING_TYPE="string";
    const INTEGER_TYPE="integer";
    const FLOAT_TYPE="float";
    const DATETIME_TYPE="datetime";
    const UNKNOWN="unknown";
}

interface IConditionOperator {
    const NONE="";
    const AND_OPERATOR="AND";
    const OR_OPERATOR="OR";
}

interface IColumnSortOrder {
    const ASC="ASC";
    const DESC="DESC";
}

class SQLQuery implements IFieldType, IConditionOperator, IColumnSortOrder {

    private $sqlStatement;
    private $tableName;
    private $tableName2;
    private $firstTableKeyName;
    private $secondTableKeyName;
    private $returnFields;
    private $insertFieldsName;
    private $insertFields;
    private $updateFields;
    private $conditionFields;
    private $sortFieldName;
    private $sortOrder;
    private $sqlType;

    public function __construct() {
        $this->sqlStatement = "";
    }

    public function setSELECTQuery($tableName) {
        $this->clearSQLSegment();
        $this->sqlType = "select";
        $this->tableName = $tableName;
    }

    public function setSELECT_INNER_JOIN_Query($firstTableName, $firstTableKeyName, $secondTableName, $secondTableKeyName) {
        $this->clearSQLSegment();
        $this->sqlType = "select inner join";
        $this->tableName = $firstTableName;
        $this->firstTableKeyName = $firstTableKeyName;
        $this->tableName2 = $secondTableName;
        $this->secondTableKeyName = $secondTableKeyName;
    }

    public function setUPDATEQuery($tableName) {
        $this->clearSQLSegment();
        $this->sqlType = "update";
        $this->tableName = $tableName;
    }

    public function setDELETEQuery($tableName) {
        $this->clearSQLSegment();
        $this->sqlType = "delete";
        $this->tableName = $tableName;
    }

    public function setINSERTQuery($tableName) {
        $this->clearSQLSegment();
        $this->sqlType = "insert";
        $this->tableName = $tableName;
    }

    public function addReturnField($fieldName) {
        if (!isset($this->returnFields) || $this->returnFields == "") {
            $this->returnFields = $fieldName;
        } else {
            $this->returnFields.="," . $fieldName;
        }
    }

    /**
     *
     * Method untuk menambah SELECT colum dari database.
     * @param string $fieldNames Senarai fieldName perlu dipisah menggunakan ','
     * <code>
     * addReturnFields(fieldName1,fieldName2,n..);
     * </code>
     */
    public function addReturnFields($fieldNames) {
        $totalFieldName = func_num_args();
        for ($i = 0; $i < $totalFieldName; $i++) {
            $this->addReturnField(func_get_arg($i));
        }
    }

    private function addInsertFieldName($fieldName) {
        if (!isset($this->insertFieldsName) || $this->insertFieldsName == "") {
            $this->insertFieldsName = $fieldName;
        } else {
            $this->insertFieldsName.="," . $fieldName;
        }
    }

    public function addUpdateField($fieldName, $fieldValue, $IFieldType) {
        $fieldEncloser = $this->getFieldEncloser($IFieldType);

        if (!isset($this->updateFields) || $this->updateFields == "") {
            $this->updateFields = $fieldName . "=" . $fieldEncloser . $fieldValue . $fieldEncloser;
        } else {
            $this->updateFields.="," . $fieldName . "=" . $fieldEncloser . $fieldValue . $fieldEncloser;
        }
    }

    public function addInsertField($fieldName, $fieldValue, $IFieldType) {
        $fieldEncloser = $this->getFieldEncloser($IFieldType);
        $this->addInsertFieldName($fieldName);

        if (!isset($this->insertFields) || $this->insertFields == "") {
            $this->insertFields = $fieldEncloser . $fieldValue . $fieldEncloser;
        } else {
            $this->insertFields.="," . $fieldEncloser . $fieldValue . $fieldEncloser;
        }
    }

    /**
     *
     * Method to add condition on query. First and single only condition
     * should have option NONE for conditionOperator.
     * <code>
     * addConditionField('name','mr framework',IFieldType::STRING_TYPE, IConditionOperator::NONE);
     * </code>
     */
    public function addConditionField($fieldName, $fieldValue, $IFieldType, $IConditionOperator) {
        $fieldEncloser = $this->getFieldEncloser($IFieldType);

        if (!isset($this->conditionFields) || $this->conditionFields == "") {
            $this->conditionFields = $fieldName . "=" . $fieldEncloser . $fieldValue . $fieldEncloser;
        } else {
            $this->conditionFields.=" $IConditionOperator " . $fieldName . "=" . $fieldEncloser . $fieldValue . $fieldEncloser;
        }
    }
    
    public function addNotEqualConditionField($fieldName, $fieldValue, $IFieldType, $IConditionOperator) {
        $fieldEncloser = $this->getFieldEncloser($IFieldType);

        if (!isset($this->conditionFields) || $this->conditionFields == "") {
            $this->conditionFields = $fieldName . "<>" . $fieldEncloser . $fieldValue . $fieldEncloser;
        } else {
            $this->conditionFields.=" $IConditionOperator " . $fieldName . "<>" . $fieldEncloser . $fieldValue . $fieldEncloser;
        }
    }
    
    public function add_MoreThan_ConditionField($fieldName, $fieldValue, $IFieldType, $IConditionOperator) {
        $fieldEncloser = $this->getFieldEncloser($IFieldType);

        if (!isset($this->conditionFields) || $this->conditionFields == "") {
            $this->conditionFields = $fieldName . ">" . $fieldEncloser . $fieldValue . $fieldEncloser;
        } else {
            $this->conditionFields.=" $IConditionOperator " . $fieldName . ">" . $fieldEncloser . $fieldValue . $fieldEncloser;
        }
    }
    
    public function add_LessThan_ConditionField($fieldName, $fieldValue, $IFieldType, $IConditionOperator) {
        $fieldEncloser = $this->getFieldEncloser($IFieldType);

        if (!isset($this->conditionFields) || $this->conditionFields == "") {
            $this->conditionFields = $fieldName . "<" . $fieldEncloser . $fieldValue . $fieldEncloser;
        } else {
            $this->conditionFields.=" $IConditionOperator " . $fieldName . "<" . $fieldEncloser . $fieldValue . $fieldEncloser;
        }
    }
    
    public function add_MoreThanOrEqual_ConditionField($fieldName, $fieldValue, $IFieldType, $IConditionOperator) {
        $fieldEncloser = $this->getFieldEncloser($IFieldType);

        if (!isset($this->conditionFields) || $this->conditionFields == "") {
            $this->conditionFields = $fieldName . ">=" . $fieldEncloser . $fieldValue . $fieldEncloser;
        } else {
            $this->conditionFields.=" $IConditionOperator " . $fieldName . ">=" . $fieldEncloser . $fieldValue . $fieldEncloser;
        }
    }
    
    public function add_LessThanOrEqual_ConditionField($fieldName, $fieldValue, $IFieldType, $IConditionOperator) {
        $fieldEncloser = $this->getFieldEncloser($IFieldType);

        if (!isset($this->conditionFields) || $this->conditionFields == "") {
            $this->conditionFields = $fieldName . "<=" . $fieldEncloser . $fieldValue . $fieldEncloser;
        } else {
            $this->conditionFields.=" $IConditionOperator " . $fieldName . "<=" . $fieldEncloser . $fieldValue . $fieldEncloser;
        }
    }

    public function addIsNullConditionField($fieldName, $IFieldType, $IConditionOperator) {
        $fieldEncloser = $this->getFieldEncloser($IFieldType);

        if (!isset($this->conditionFields) || $this->conditionFields == "") {
            $this->conditionFields = $fieldName . " IS NULL";
        } else {
            $this->conditionFields.=" $IConditionOperator " . $fieldName . " IS NULL";
        }
    }
    
    public function addIsNotNullConditionField($fieldName, $IFieldType, $IConditionOperator) {
        $fieldEncloser = $this->getFieldEncloser($IFieldType);

        if (!isset($this->conditionFields) || $this->conditionFields == "") {
            $this->conditionFields = $fieldName . " IS NOT NULL";
        } else {
            $this->conditionFields.=" $IConditionOperator " . $fieldName . " IS NOT NULL";
        }
    }

    public function addLikeConditionField($fieldName, $fieldValue, $IConditionOperator) {
        $fieldEncloser = $this->getFieldEncloser(IFieldType::STRING_TYPE);

        if (!isset($this->conditionFields) || $this->conditionFields == "") {
            $this->conditionFields = $fieldName . " LIKE " . $fieldEncloser . $fieldValue . $fieldEncloser;
        } else {
            $this->conditionFields.=" $IConditionOperator " . $fieldName . " LIKE " . $fieldEncloser . $fieldValue . $fieldEncloser;
        }
    }

    public function addInConditionField($fieldName, $fieldValue, $IFieldType, $IConditionOperator) {
        $fieldEncloser = $this->getFieldEncloser($IFieldType);

        if (!isset($this->conditionFields) || $this->conditionFields == "") {
            $this->conditionFields = $fieldName . " IN (" . $fieldEncloser . str_replace(",", "','", $fieldValue) . $fieldEncloser . ")";
        } else {
            $this->conditionFields.=" $IConditionOperator " . $fieldName . " IN (" . $fieldEncloser . str_replace(",", "','", $fieldValue) . $fieldEncloser . ")";
        }
    }
    
    public function addSortOrder($fieldName, $ISortOrder) {
        $this->sortFieldName = $fieldName;
        $this->sortOrder = $ISortOrder;
    }

    private function isWithCondition() {
        if (!isset($this->conditionFields) || $this->conditionFields == "") {
            return FALSE;
        }
        return TRUE;
    }
    
    private function isWithSortOrder(){
        if(isset($this->sortFieldName) && $this->sortFieldName!=""){
            if(isset($this->sortOrder) && $this->sortOrder!=""){
                return TRUE;
            }else                
                return FALSE;
        }else
            return FALSE;
    }

    private function getFieldEncloser($IFieldType) {
        if ($IFieldType == "string" || $IFieldType == "datetime" || $IFieldType == "unknown") {
            return "'";
        } elseif ($IFieldType == "integer" || $IFieldType == "float") {
            return "";
        } 
    }

    private function generateQuery() {
        if ($this->sqlType == "select") {
            $this->generateReturnFields();
            $this->sqlStatement.=" FROM $this->tableName";
            if ($this->isWithCondition())
                $this->generateConditionFields();
            if($this->isWithSortOrder())
                $this->generateSortOrder ();
        }elseif ($this->sqlType == "select inner join") {
            $this->generateReturnFields();
            $this->sqlStatement.=" FROM $this->tableName INNER JOIN $this->tableName2";
            $this->sqlStatement.=" ON $this->tableName" . "." . $this->firstTableKeyName . " = $this->tableName2" . "." . $this->secondTableKeyName;
            if ($this->isWithCondition())
                $this->generateConditionFields();
        }elseif ($this->sqlType == "update") {
            $this->sqlStatement = "UPDATE $this->tableName";
            $this->generateUpdateFields();
            if ($this->isWithCondition()) {
                $this->generateConditionFields();
            } else {
                $this->clearSQLSegment();
                throw new Exception("Dangerous SQL Statenment. Updating without condition. Aborted!");
                //trigger_error("Dangerous SQL Statenment. Updating without condition. Aborted!",E_USER_ERROR);
            }
        } elseif ($this->sqlType == "delete") {
            $this->sqlStatement = "DELETE FROM $this->tableName";
            if ($this->isWithCondition()) {
                $this->generateConditionFields();
            } else {
                $this->clearSQLSegment();
                throw new Exception("Dangerous SQL Statement. Deletion without condition. Aborted!");
                //trigger_error("Dangerous SQL Statenment. Deletion without condition. Aborted!",E_USER_ERROR);
            }
        } elseif ($this->sqlType == "insert") {
            $this->sqlStatement = "INSERT INTO $this->tableName";
            $this->sqlStatement.="(" . $this->insertFieldsName . ") ";
            $this->sqlStatement.="VALUES(" . $this->insertFields . ")";
        }
    }

    private function generateReturnFields() {
        if (isset($this->returnFields) && $this->returnFields != "" && strlen($this->returnFields) > 0) {
            $this->sqlStatement = "SELECT $this->returnFields";
        } else {
            $this->sqlStatement = "SELECT *";
        }
    }

    private function generateUpdateFields() {
        if (isset($this->updateFields) || $this->updateFields != "") {
            $this->sqlStatement .= " SET $this->updateFields";
        }
    }

    private function generateConditionFields() {
        if (isset($this->conditionFields) || $this->conditionFields != "") {
            $this->sqlStatement .= " WHERE $this->conditionFields";
        }
    }

    private function generateSortOrder() {
        $this->sqlStatement .= " ORDER BY $this->sortFieldName $this->sortOrder";
    }

    private function clearSQLSegment() {
        $this->sqlStatement = "";
        $this->returnFields = "";
        $this->updateFields = "";
        $this->insertFields = "";
        $this->insertFieldsName = "";
        $this->conditionFields = "";
    }

    public function getSQLQuery() {
        try {
            $this->generateQuery();
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        return $this->sqlStatement;
    }

}

//$testsql=new SQLQuery();
//$testsql->setSELECT_INNER_JOIN_Query("firstTableName", "firstTableKeyName", "secondTableName", "secondTableKeyName");
//$testsql->addConditionField('fieldName', 'fieldValue', IFieldType::STRING_TYPE, IConditionOperator::NONE);
//$testsql->addConditionField('fieldName2', 'fieldValue2', IFieldType::FLOAT_TYPE, IConditionOperator::OR_OPERATOR);
//echo $testsql->getSQLQuery();
/*
 * Version: 1.6 [ Modified Date: 10 Sept 2012 ]
 * Updated By: Mohd Rizuwan bin Sa'ar @ Idris
 * Remarks:
 * Enhancement 1)Fixed addInConditionField method that support specific filtering by seperated commas.
 *               SQL SELECT IN.
 * 
 * 
 * Version: 1.5 [ Modified Date: 12 Apr 2014 ]
 * Updated By: Mohd Ilhammuddin Bin Mohd Fuead
 * Remarks:
 * Enhancement 1)Added constant UNKNOWN for field type to handle [unknown] | [generic] | [not yet implemented] 
 *               MYSQL field. 
 * 
 * Version: 1.4 [ Modified Date: 10 Sept 2012 ]
 * Updated By: Fernandez Christie Jassil
 * Remarks:
 * Enhancement 1)Added addInConditionField method that support filtering
 *               SQL SELECT IN.
 *
 * Version: 1.3 [ Modified Date: 5 Jan 2012 ]
 * Updated By: Fernandez Christie Jassil
 * Remarks:
 * Enhancement 1)Added addIsNullConditionField method that support
 *               IS NULL condition field.
 *
 * Version: 1.2 [ Modified Date: 13 May 2011 ]
 * Updated By: Mohd Ilhammuddin Bin Mohd Fuead
 * Remarks:
 * Enhancement 1)Added addReturnFields method that support
 *               unlimited fieldname argument.
 *
 * Version: 1.1 [ Modified Date: 8 May 2011 ]
 * Updated By: Mohd Ilhammuddin Bin Mohd Fuead
 * Remarks:
 * Enhancement 1)Added LIKE condition field support.
 *             2)Added checking for preventing unintended dangerous SQL query
 *               for UPDATE and DELETE operation without condition specified.
 *
 * Version: 1.0 [ Released Date: 5 May 2011 ]
 * Developer: Mohd Ilhammuddin Bin Mohd Fuead
 * Description/Remarks:
 * A Utility class for constructing valid SQL statement to
 * be consume by other main classes. This version support all
 * standard SQL Command.
 *
 */
?>
