<?php
//include 'Field.php';
/**
 * Class Select
 * 
 * Utility class to render HTML dropdown field type.
 * 
 * @version 1.0
 * @category UI, HTML Input Generator
 * @author Mohd Ilhammuddin Bin Mohd Fuead <ilham.fuead@gmail.com>
 * @copyright [Closed Distribution] e-SILA 2011, OSS Developer Team, SD, BPM 01/05/2011 
 */

interface ISortColumn{
const COLUMN_VALUE=1;
const COLUMN_LABEL=2;
}

interface ISortOrder{
const ASC='ASC';
const DESC='DESC';
}

class Select extends Field {

    private $keyColumnName;
    private $valueColumnName;
    private $sortColumn;
    private $sortColumnOrder;
    private $sortStatus;
    private $select_options;
    private $select_selectedValue;
    private $dbQueryObj;
    private $sqlQueryObj;
    private $sql;

    /**
     * @param string $id ID for the html select field to generate
     * @param string $label Label for the html select field to generate
     */
    public function __construct($id, $label) {
        parent::__construct($id, $label);
        //$this->select_options = array();
        $this->resetOptions();
        $this->sqlQueryObj=new SQLQuery();
        $this->sortStatus=FALSE;
    }

    public function setDataSource(DBQuery $dbQueryObj) {
        $this->dbQueryObj = $dbQueryObj;
    }

    /**
     * @param string $tableName Name of the reference table
     * @param string $keyColumnName Name of column that store key or id value
     * @param string $valueColumnName Name of column that store description or label
     */
    public function setDataSourceTable($tableName, $keyColumnName, $valueColumnName) {        
        $this->sqlQueryObj->setSELECTQuery($tableName);
        $this->sqlQueryObj->addReturnField($keyColumnName);
        $this->sqlQueryObj->addReturnField($valueColumnName);
        $this->sql=$this->sqlQueryObj->getSQLQuery();
        $this->keyColumnName=$keyColumnName;
        $this->valueColumnName=$valueColumnName;
        $this->addOptionsFromDataSource($keyColumnName, $valueColumnName);
    }
    
    /**
     * To be use for advance column filtering.
     * Required to be followed by method initBindingSelectAdv to bind DB columns and options.
     * 
     * @param string $tableName Name of the reference table
     * @param string $keyColumnName Name of column that store key or id value
     * @param string $valueColumnName Name of column that store description or label 
     */
    public function setDataSourceTableAdv($tableName, $keyColumnName, $valueColumnName) {        
        $this->sqlQueryObj->setSELECTQuery($tableName);
        $this->sqlQueryObj->addReturnField($keyColumnName);
        $this->sqlQueryObj->addReturnField($valueColumnName);
        $this->sql=$this->sqlQueryObj->getSQLQuery();
        $this->keyColumnName=$keyColumnName;
        $this->valueColumnName=$valueColumnName;        
    }
    
    /**
     * To be use for advance column filtering.
     * Must be use with method setDataSourceTableAdv prior using this method.
     */
    public function setFilterColumnAdv($columnName,$columnValue, $IFieldType, $IConditionOperator){
        $this->sqlQueryObj->addConditionField($columnName, $columnValue, $IFieldType, $IConditionOperator);
        $this->sql=$this->sqlQueryObj->getSQLQuery();
    }
    
    /**
     * To be use for advance column filtering.
     * Must be use with method setDataSourceTableAdv prior using this method.
     */
    public function setFilterColumnNotEqualAdv($columnName,$columnValue, $IFieldType, $IConditionOperator){
        $this->sqlQueryObj->addNotEqualConditionField($columnName, $columnValue, $IFieldType, $IConditionOperator);
        $this->sql=$this->sqlQueryObj->getSQLQuery();
    }

    /**
     * To be use for advance column filtering.
     * Must be use with method setDataSourceTableAdv prior using this method.
     */
    public function setSelectInAdv($columnName,$columnValue, $IFieldType, $IConditionOperator){
        $this->sqlQueryObj->addInConditionField($columnName, $columnValue, $IFieldType, $IConditionOperator);
        $this->sql=$this->sqlQueryObj->getSQLQuery();
    }

    /**
     * To be use for advance column filtering.
     * Must be use with method setDataSourceTableAdv prior using this method.
     */
    public function setIsNullColumnAdv($columnName, $IFieldType, $IConditionOperator){
        $this->sqlQueryObj->addIsNullConditionField($columnName, $IFieldType, $IConditionOperator);
        $this->sql=$this->sqlQueryObj->getSQLQuery();
    }
    
    /**
     * To be use for advance column sorting.
     * Must be use with method setDataSourceTableAdv prior using this method.
     */
    public function setSortColumnAdv($ISortColumn,$ISortOrder){
        $this->sortColumn=$ISortColumn;
        $this->sortColumnOrder=$ISortOrder;
        $this->sortStatus=TRUE;
    }
    
    /**
     * To be use for advance column filtering.
     * Must be called after all others *Adv method call.
     */
    public function initBindingSelectAdv(){
        if($this->sortStatus==TRUE)
            $this->sql=$this->sqlQueryObj->getSQLQuery() . ' ORDER BY ' . $this->sortColumn . ' ' . $this->sortColumnOrder;
        else
            $this->sql=$this->sqlQueryObj->getSQLQuery();
        
        $this->addOptionsFromDataSource($this->keyColumnName, $this->valueColumnName);
        $this->sortStatus=FALSE;
    }


    /**
     *
     * @param string $sql Custom SQL statement
     * @param string $keyColumnName Name of column that store key or id value
     * @param string $valueColumnName Name of column that store description or label
     */
    public function setDataSourceSQL($sql,$keyColumnName, $valueColumnName){
        $this->sql=$sql;
        $this->addOptionsFromDataSource($keyColumnName, $valueColumnName);
    }
    
    private function addOptionsFromDataSource($keyColumnName, $valueColumnName){
        $this->dbQueryObj->setSQL_Statement($this->sql);
        $this->dbQueryObj->runSQL_Query();
        if (mysqli_num_rows($this->dbQueryObj->getQueryResult()) > 0) {
            while ($field = mysqli_fetch_assoc($this->dbQueryObj->getQueryResult())) {
                $this->select_options[] = array('value' => $field[$keyColumnName], 'text' => $field[$valueColumnName]);
            }
        }
    }

    public function addOption($value, $text) {
        $this->select_options[] = array('value' => $value, 'text' => $text);
    }

    private function resetOptions(){
        $this->select_options=array();
    }

    public function setSelectedValue($selectedValue) {
        $this->select_selectedValue = $selectedValue;
    }

    private function renderOptions() {
        $optionsHTML = '';
        if (sizeof($this->select_options) > 0) {
            foreach ($this->select_options as $option) {
                if ($option['value'] == $this->select_selectedValue) {
                    $optionsHTML .= "<option value='{$option['value']}' selected='selected'>{$option['text']}</option>\n";
                } else {
                    $optionsHTML .= "<option value='{$option['value']}'>{$option['text']}</option>\n";
                }
            }
        }
        return $optionsHTML;
    }

    public function renderField() {        
        $selectHTML = '';

        $selectHTML .= "<select id='$this->field_id' name='$this->field_name' ";
        $selectHTML .= $this->renderCss();
        $selectHTML .= $this->renderEvents();
        $selectHTML .= ">\n";
        $selectHTML .= $this->renderOptions();
        $selectHTML .= "</select>";

        return array('fieldHTML' => $selectHTML, 'label' => $this->field_label);
    }
    
    public function TestGetSQL(){
        echo $this->sql;
    }

}
?>