<?php

/**
 * Handle inputs from GET, POST & Raw JSON and copy as object properties
 *
 * Magicly copy and sanitize inputs
 *
 * @category   Utility, Security
 * @package    Mandryn/Mandryn
 * @author     Mohd Ilhammuddin Bin Mohd Fuead <ilham.fuead@gmail.com>
 * @copyright  2017-2025 The Mandryn Team
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: 1.4.2
 * @since      Class available since Release 2.1.0
 */
class MagicInput extends MagicObject {

    private $inputDefinition;
    private $nonCompliedInputList;
    private $isDefinitionExist;
    private $removeNonDefineInput;
    private $DBQueryObj;
    private $sanitizationForDB;

    public function __construct() {
        $this->inputDefinition = [];
        $this->nonCompliedInputList = [];
        $this->isDefinitionExist = false;
        $this->removeNonDefineInput = true;
        $this->sanitizationForDB = false;
        parent::__construct();
    }

    public function sanitizeInputsForDBoperations(DBQuery $DBQueryObj) {
        $this->DBQueryObj = $DBQueryObj;
        $this->sanitizationForDB = true;
    }

    private function addInputDefinition($inputName, $inputType, $requiredStatus = false, $integrationAlias = '') {
        $this->inputDefinition[] = ['name' => $inputName, 'type' => $inputType, 'required' => $requiredStatus, 'alias' => $integrationAlias];
    }
    
    /**
     * Use INPUT DEFINITION - to establish acceptable input trait
     * 
     * @param array $InputsDefinition
     * 
     * Definition is in Array format - [string inputName, string inputType, boolean requiredStatus, string inputAlias] 
     * Notes: 
     * 
     *    i. inputType(string) to denote input datatype/format as:
     *       [i] Integer
     *       [f] Float
     *       [n] Numeric(integer/float) 
     *       [d] Date(yyyy-mm-dd)
     *       [dt] Datetime(yyyy-mm-dd HH:mm:ss)
     *       [s] String
     *       [e] E-mail
     *       [h] Request Header
     *       [u] Unknown
     *      
     *   ii. requiredStatus is use to denote input is mandatory
     *
     *  iii. inputAlias(string) is use for input mapping in other component/object/array
     *       If no alias given, inputName will be used for mapping
     * 
     * @param boolean $removeNonDefineInput
     * 
     * Remove all input without definition if this parameter set tu true(default)
     * 
     */
    public function setInputsDefinition(array $InputsDefinition, $removeNonDefineInput = true) {
        $this->isDefinitionExist = true;
        $this->removeNonDefineInput = $removeNonDefineInput;
        foreach ($InputsDefinition as $def) {
            $this->addInputDefinition($def[0], $def[1], $def[2], $def[3]);
        }
    }

    private function logNonCompliedInput($inputName, $errorMsg) {
        $this->nonCompliedInputList[] = ['name' => $inputName, 'error' => $errorMsg];
    }

    public function getNonCompliedInputList() {
        return $this->nonCompliedInputList;
    }

    private function deleteInputWithoutDefinition() {
        $validInputList = [];

        foreach ($this->inputDefinition as $def) {

            if($def['type'] == 'h' || $def['type'] == 'hs') {
                $def['name']=strtoupper(str_replace('-', '_', $def['name']));
            }

            $validInputList[] = $def['name'];
        }

        foreach ($this->property as $key => $val) {
            if (!in_array($key, $validInputList)) {
                unset($this->{$key});
            }
        }

        unset($validInputList);
    }

    private function sanitizeForDB() {
        $validInputList = [];

        foreach ($this->inputDefinition as $def) {

            if($def['type'] == 'h' || $def['type'] == 'hs') {
                $def['name']=strtoupper(str_replace('-', '_', $def['name']));
            }

            $validInputList[] = $def['name'];
        }

        foreach ($this->property as $key => $val) {
            if (in_array($key, $validInputList)) {
                $this->{$key}=mysqli_real_escape_string($this->DBQueryObj->getLink(), $val);
            }
        }        
    }

    private function applyInputDefinition() {
        /** TODO: Reset non-complied input list * */
        $this->nonCompliedInputList = [];

        /** TODO: Loop each input definition * */
        foreach ($this->inputDefinition as $def) {

            $inputValue = null;

            /** TODO: Change input definition for request header to reflect actual header item read by PHP 
             * with tranformation to Uppercase & changed from - to _ * */
            if($def['type'] == 'h' || $def['type'] == 'hs') {
                $def['name']=strtoupper(str_replace('-', '_', $def['name']));
            }

            /** TODO: Check current definition with actual input item * */
            if (array_key_exists($def['name'], parent::toArray())) {
                $inputValue = parent::toArray()[$def['name']];
                if (($inputValue == null || trim($inputValue) == '') && $inputValue != '0') {
                    $this->logNonCompliedInput($def['name'], 'Empty or null' . $inputValue);
                    continue;
                }
            } else {
                if ($def['required'] == true) {
                    $this->logNonCompliedInput($def['name'], 'Required');
                }

                continue;
            }

            /** TODO: Check current value for correct datatype * */
            $this->inputTypeChecker($def['name'], $inputValue, $def['type']);
        }

        /** TODO: If removeNonDefineInput is true, remove all input without definition * */
        if ($this->removeNonDefineInput) {
            $this->deleteInputWithoutDefinition();
        }

        /** TODO: Sanitize inputs for database operations * */
        if ($this->sanitizationForDB) {
            $this->sanitizeForDB();
        }

    }

    private function inputTypeChecker($inputName, $inputValue, $inputType = '') {
        
        switch ($inputType) {
            case 'i':
                $this->numericTypeChecker($inputName, $inputValue, 'Not an integer', 'i');
                break;
            case 'f':
                $this->numericTypeChecker($inputName, $inputValue, 'Not a float', 'f');
                break;
            case 'n':
                $this->numericTypeChecker($inputName, $inputValue, 'Not a number', 'n');
                break;
            case 'e':
                if (!filter_var($inputValue, FILTER_VALIDATE_EMAIL)) {
                    $this->logNonCompliedInput($inputName, 'Invalid e-mail');
                }
                break;
            case 'd':
                $format = 'Y-m-d';
                $this->datetimeTypeChecker($inputName, $inputValue, 'Invalid date', $format);
                break;
            case 'dt':
                $format = 'Y-m-d H:i:s';
                $this->datetimeTypeChecker($inputName, $inputValue, 'Invalid datetime', $format);
                break;
            case 'hs':
                $this->safeHeaderValueChecker($inputName, $inputValue, 'Unsecured header value');
                break;
            case 'h':
            case 'u':
            case 's':
                break;
        }
    }

    private function safeHeaderValueChecker($inputName, $inputValue, $errMsg) {
        
        if (!ctype_alnum(str_replace(['-','_','.',',',':','/','@'],'',$inputValue))) {
            $this->logNonCompliedInput($inputName, $errMsg);
        }
    }    

    private function datetimeTypeChecker($inputName, $inputValue, $errMsg, $format = '') {
        $d = DateTime::createFromFormat($format, $inputValue);
        if (!($d && $d->format($format) == $inputValue)) {
            $this->logNonCompliedInput($inputName, $errMsg);
        }
    }

    private function numericTypeChecker($inputName, $inputValue, $errMsg, $type = '') {

        if (is_numeric($inputValue)) {
            /** Force type juggle before type checking * */
            $inputValue += 0;

            switch ($type) {
                case 'i':
                    if (!is_int($inputValue)) {
                        $this->logNonCompliedInput($inputName, $errMsg);
                    }
                    break;
                case 'f':
                    if (!is_float($inputValue)) {
                        $this->logNonCompliedInput($inputName, $errMsg);
                    }
                    break;
            }
        } else {
            $this->logNonCompliedInput($inputName, $errMsg);
        }
    }

    public function isInputsComplied() {
        $this->applyInputDefinition();
        if (count($this->nonCompliedInputList) > 0) {
            return false;
        } else {
            return true;
        }
    }

    public function getInputsDefinition() {
        return $this->inputDefinition;
    }

    public function getJsonString() {
        if ($this->isDefinitionExist) {
            if ($this->isInputsComplied()) {
                return parent::getJsonString();
            } else {
                return '[]';
            }
        } else {
            return parent::getJsonString();
        }
    }

    public function toArray() {
        if ($this->isDefinitionExist) {
            if ($this->isInputsComplied()) {
                /** TODO:Instead of deleting non define input, return input with definition */
                return parent::toArray();
            }
        } else {
            return parent::toArray();
        }
    }

    /**
     *
     * @param boolean $apply_sanitize sanitize input before assign to object. Default to true.
     */
    public function copy_GET_properties($apply_sanitize = true) {
        $GET_array = [];
        
        if($this->is_non_empty_array($_GET)){
            $GET_array = $apply_sanitize ? filter_input_array(INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS) : $_GET;
        }        
        
        $this->copyArrayProperties($GET_array, true);
    }

    /**
     *
     * @param boolean $apply_sanitize sanitize input before assign to object. Default to true.
     */
    public function copy_POST_properties($apply_sanitize = true) {
        $POST_array = [];
        
        if($this->is_non_empty_array($_POST)){
            $POST_array = $apply_sanitize ? filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS) : $_POST;
        }
        
        $this->copyArrayProperties($POST_array, true);
    }

    /**
     *
     * @param boolean $apply_sanitize sanitize input before assign to object. Default to true.
     */
    public function copy_RAW_JSON_properties($apply_sanitize = true) {
        $request = file_get_contents('php://input');

        /* 2nd parameter supply true to convert request as input array, false as input object */
        $input = json_decode($request, true);

        if ($apply_sanitize && is_array($input)) {
            $input = filter_var_array($input, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }

        if (is_array($input)) {
            $this->copyArrayProperties($input);
        }
    }

    public function copy_request_header_properties($apply_sanitize = true): void
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if ($this->startsWith($key, 'HTTP_')) {
                
                // Remove the "HTTP_" prefix and format the header name properly, request hyphen converted to underscore
                $headerName = substr($key, 5);
                $headers[$headerName] = $value;

            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5','AUTHORIZATION'], true)) {
                // Handle special cases without "HTTP_" prefix
                $headerName = $key;
                $headers[$headerName] = $value;
            }
        }
       

        if (is_array($headers)) {

            if($apply_sanitize){
                $headers = filter_var_array($headers, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            }            
        
            $this->copyArrayProperties($headers);
        }
    }

    private function startsWith(string $haystack, string $needle): bool
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }
    
    private function is_non_empty_array($array){
        if(is_array($array)){
            return count($array)>0?true:false;
        }else{
            return false;
        }
    }

}
