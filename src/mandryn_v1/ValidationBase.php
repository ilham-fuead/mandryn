<?php

interface IInputFormat {

    const MYKAD = "/^\d{12,12}$/";
    const POSKOD = "/^\d{5,5}$/";
    const USERID = "/^[^'|\s]{1}[A-Za-z0-9]{5,15}$/";
    const PASSWORD = "/^[a-zA-Z1-9]{6,15}$/";
    const EMEL = "/^([a-zA-Z0-9])+([\.a-zA-Z0-9_-]*[a-zA-Z]+)*@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)*\.([a-zA-Z]{2,6})$/";

}

final class ValidationBase implements IInputFormat {

    private $fieldFormats;
    private $errorList;

    public function __construct() {
        $this->resetErrorList();
        //$this->initFieldFormats();
    }

    private function initFieldFormats() {
        //Add more formats here
        $this->fieldFormats[] = array("mykad" => "/^\d{12,12}$/");
        $this->fieldFormats[] = array("poskod" => "/^\d{5,5}$/");
    }

    private function resetErrorList() {
        $this->errorList = array();
        $this->fieldFormats = array();
    }

    public function addErrorList($field, $errorMessage) {
        $this->errorList[] = array("field" => $field, "errorMessage" => $errorMessage);
    }

    public function getErrorList() {
        return $this->errorList;
    }

    public function isError() {
        return (sizeof($this->errorList) > 0) ? true : false;
    }

    public function isEmpty($fieldValue) {
        return (!isset($fieldValue) || trim($fieldValue) == '') ? true : false;
    }

    public function isNumber($fieldValue) {
        return is_numeric($fieldValue);
    }

    public function isDate($dateField) { //semakTarikh( $medanTarikh )
        list( $day, $month, $year ) = preg_split('/[\/\.-]/', $dateField);
        $validDate = checkdate($month, $day, $year); //$tarikhSah
        if ($validDate == TRUE)
            return TRUE;
        else
            return FALSE;
    }

    private function isFormat($format, $fieldValue) {
        $doMatch = false;
        foreach ($this->fieldFormats as $frm) {
            if (array_key_exists($format, $frm)) {
                $doMatch = true;
                $result = preg_match($frm[$format], $fieldValue);
                //echo $frm[$format] . " result: " . $result;
            }
        }
        return ($doMatch && $result == 1) ? true : false;
    }

    public function isInFormat($IInputFormat, $fieldValue) {
        return (preg_match($IInputFormat, $fieldValue) == 1) ? true : false;
    }

    public function gotError() {
        return (count($this->errorList) > 0) ? TRUE : FALSE;
    }

    public function getErrorTransacCode() {
        $transacCode = "";
        $errStr="";
        if ($this->gotError()) {
            foreach ($this->getErrorList() as $errPair) {
                $errStr.=$errPair["field"];
                $errStr.="=";
                $errStr.=$errPair["errorMessage"];
                $errStr.="&";
            }
            $transacCode = "9|" . substr($errStr, 0, (strlen($errStr) - 1));
        }
        return $transacCode;
    }

}

?>
