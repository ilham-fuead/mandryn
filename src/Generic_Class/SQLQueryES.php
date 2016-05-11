<?php
/**
 * SQLQuery with string for SQL sanitizer (Escaped String(Es) Imposed for Enhanced Security(ES))
 *
 * @author Mohd Ilhammuddin Bin Mohd Fuead
 * Date created: 17/07/2012
 */

class SQLQueryES extends SQLQuery {
    private $dbLink;

    public function  __construct($dbLink) {
        $this->dbLink=$dbLink;
        parent::__construct();
    }

    public function addReturnField($fieldName){
        $cleanedFieldName=mysqli_real_escape_string($this->dbLink,$fieldName);
        parent::addReturnField($cleanedFieldName);
    }

    public function cleanString($str){
        return mysqli_real_escape_string($this->dbLink,$str);
    }
}
?>
