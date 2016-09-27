<?php
/**
 *
 * @author Mohd Ilhammuddin Bin Mohd Fuead
 *
 */
class TextLiteral {

    private $lateral;
    private $DBQueryObj;

    public function __construct(DBQuery $DBQueryObj) {
        $this->lateral = array();
        $this->DBQueryObj = $DBQueryObj;
    }

    private function lateralBuilder($tableName, $columnCodeName, $columnCodeValue, $columnLateral) {
        $lateral = '';
        $SQLQueryObj = new SQLQuery();
        $SQLQueryObj->setSELECTQuery($tableName);
        $SQLQueryObj->addReturnField($columnLateral);

        $SQLQueryObj->addConditionField($columnCodeName, $columnCodeValue, IFieldType::STRING_TYPE, IConditionOperator::NONE);
        $this->DBQueryObj->setSQL_Statement($SQLQueryObj->getSQLQuery());
        $this->DBQueryObj->runSQL_Query();

        if ($this->DBQueryObj->isHavingRecordRow()) {
            $row = mysqli_fetch_assoc($this->DBQueryObj->getQueryResult());
            $lateral = $row[$columnLateral];
        }

        return $lateral;
    }

    private function lateralBuilderCustomSQL($columnLateral, $sql) {
        $lateral = '';

        $this->DBQueryObj->setSQL_Statement($sql);
        $this->DBQueryObj->runSQL_Query();

        if ($this->DBQueryObj->isHavingRecordRow()) {
            $row = mysqli_fetch_assoc($this->DBQueryObj->getQueryResult());
            $lateral = $row[$columnLateral];
        }
        return $lateral;
    }

    public function __get($name) {
        return $this->lateral[$name];
    }

    public function __set($name, $value) {
        $this->lateral[$name]=$value;
    }

    public function addTextLateral($lateralName,$tableName, $inFieldName, $inFieldValue, $outFieldName){
        $this->lateral[$lateralName]=$this->lateralBuilder($tableName, $inFieldName, $inFieldValue, $outFieldName);
    }

    public function addTextLateralSQL($lateralName,$sql,$outFieldName){
        $this->lateral[$lateralName]=$this->lateralBuilderCustomSQL($outFieldName,$sql);
    }

    public function __destruct() {
        unset($this->DBQueryObj);
        unset($this->lateral);
    }

}