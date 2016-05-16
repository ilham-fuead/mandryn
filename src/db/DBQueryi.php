<?php
namespace Mandryn\db;

class DBQueryi {

    private $pdoObj;
    private $statementObj;
    private $preparedQueryObj;
    private $paramFields;

    public function __construct($host, $username, $password, $database_name) {
        $dsn = "mysql:host={$host};dbname={$database_name};port=3306;charset=utf8";
        $this->pdoObj = new \PDO($dsn, $username, $password);
        $this->pdoObj->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function setQuery(\Mandryn\db\Query $preparedQueryObj) {
        $this->preparedQueryObj = $preparedQueryObj;
        $this->initStatement();
        $this->bindParameters();
    }

    private function initStatement() {
        $this->statementObj = $this->pdoObj->prepare($this->preparedQueryObj->getQueryString(\Mandryn\db\constant\SqlStringType::PREPARE_STATEMENT));
    }

    private function bindParameters() {
        $this->paramFields = [];

        if ($this->preparedQueryObj->queryType === \Mandryn\db\constant\QueryType::UPDATE) {
            foreach ($this->preparedQueryObj->updateFields as $fld) {

                /*  [ 0 - $fieldName ] , [ 1 - $value ] , [ 2 - $DataType ]  */

                $this->paramFields[$fld[0]] = $fld[1];

                if ($fld[2] === \Mandryn\db\constant\DataType::INT) {
                    $this->statementObj->bindParam(":{$fld[0]}", $this->paramFields[$fld[0]], \PDO::PARAM_INT);
                } else {
                    $this->statementObj->bindParam(":{$fld[0]}", $this->paramFields[$fld[0]]);
                }
            }
        }
        
        foreach ($this->preparedQueryObj->conditionFields as $fld) {

            /*  [ 0 - $fieldName ] , [ 1 - $ConditionType ] , [ 2 - $value ] , [ 3 - $DataType ] , [ 4 - $AppenderOperator ]  */

            $this->paramFields[$fld[0]] = $fld[2];

            if ($fld[3] === \Mandryn\db\constant\DataType::INT) {
                $this->statementObj->bindParam(":{$fld[0]}", $this->paramFields[$fld[0]], \PDO::PARAM_INT);
            } else {
                $this->statementObj->bindParam(":{$fld[0]}", $this->paramFields[$fld[0]]);
            }
        }
    }

    public function setFieldValue($fieldName, $fieldValue) {
        if(array_key_exists($fieldName, $this->paramFields)){
            $this->paramFields[$fieldName]=$fieldValue;
        }
    }

    public function execute() {
        try {
            return $this->statementObj->execute();
        } catch (PDOException $e) {
            print $e->getMessage();
        }
    }
    
    public function closeConnection(){
        $this->pdoObj=null;
    }

    public function __destruct() {
        $this->pdoObj=null;
    }
    
    public static function query($sqlWithPlaceholder,$placeholderNamesValuesArray){
        $statement=$this->pdoObj->prepare($sqlWithPlaceholder);
        
        foreach ($placeholderNamesValuesArray as $placeholder => $value) {
            $statement->bindValue($placeholder, $value);
        }
        
        $statement->execute();
        
        while ($row=$statement->fetch(\PDO::FETCH_ASSOC)!==false){
            yield $row;
        }
        
    }

}