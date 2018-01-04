<?php

/**
 * Handle inputs from GET, POST & Raw JSON and copy as object properties
 *
 * Magicly copy and sanitize inputs
 *
 * @category   Utility, Security
 * @package    Mandryn/Mandryn
 * @author     Mohd Ilhammuddin Bin Mohd Fuead <ilham.fuead@gmail.com>
 * @copyright  2017-2022 The Mandryn Team
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: 1.2.0
 * @since      Class available since Release 2.1.0
 */
class MagicInput extends MagicObject {

    private $inputDefinition;
    private $nonCompliedInputList;
    private $isDefinitionExist;
    private $removeNonDefineInput;

    public function __construct() {
        $this->inputDefinition = [];
        $this->nonCompliedInputList = [];
        $this->isDefinitionExist = false;
        $this->removeNonDefineInput = true;
        parent::__construct();
    }

    private function addInputDefinition($inputName, $inputType, $requiredStatus = false, $integrationAlias = '') {
        $this->inputDefinition[] = ['name' => $inputName, 'type' => $inputType, 'required' => $requiredStatus, 'alias' => $integrationAlias];
    }

    /**
     * 
     * @param array $InputsDefinition child array format - each input definition is in array format  [ string inputName, string inputType, boolean requiredStatus, string integrationAlias]
     * 
     * integrationAlias is for mapping with other object/entity/collection
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
            $validInputList[] = $def['name'];
        }

        foreach ($this->property as $key => $val) {
            if (!in_array($key, $validInputList)) {
                unset($this->{$key});
            }
        }

        unset($validInputList);
    }

    private function applyInputDefinition() {
        /** TODO: Reset non-complied input list * */
        $this->nonCompliedInputList = [];

        /** TODO: Loop each input definition * */
        foreach ($this->inputDefinition as $def) {

            $inputValue = null;

            /** TODO: Check current definition with actual input item * */
            if (array_key_exists($def['name'], parent::toArray())) {
                $inputValue = parent::toArray()[$def['name']];
            } else {
                if ($def['required'] == true) {
                    $this->logNonCompliedInput($def['name'], 'Input is required');
                }

                continue;
            }

            /** TODO: Check current value for correct datatype * */
            switch ($def['type']) {
                case 'i':
                    if (is_numeric($inputValue)) {
                        /** Force type juggle before type checking * */
                        $inputValue += 0;
                        if (!filter_var($inputValue, FILTER_VALIDATE_INT)) {
                            $this->logNonCompliedInput($def['name'], 'Input must be an integer');
                        }
                    } else {
                        $this->logNonCompliedInput($def['name'], 'Input must be an integer');
                    }
                    break;
                case 'f':
                    if (is_numeric($inputValue)) {
                        /** Force type juggle before type checking * */
                        $inputValue += 0;
                        if (!is_float($inputValue)) {
                            $this->logNonCompliedInput($def['name'], 'Input must be a float');
                        }
                    } else {
                        $this->logNonCompliedInput($def['name'], 'Input must be a float');
                    }
                    break;
                case 'n':
                    if (!is_numeric($inputValue)) {
                        $this->logNonCompliedInput($def['name'], 'Input must be a number');
                    }
                    break;
                case 'e':
                    if (!filter_var($inputValue, FILTER_VALIDATE_EMAIL)) {
                        $this->logNonCompliedInput($def['name'], 'Input must be an email');
                    }
                    break;
                case 'd':
                    $format = 'Y-m-d';
                    $d = DateTime::createFromFormat($format, $inputValue);
                    if (!($d && $d->format($format) == $inputValue)) {
                        $this->logNonCompliedInput($def['name'], 'Input must be a valid date');
                    }
                    break;
                case 'dt':
                    $format = 'Y-m-d H:i:s';
                    $d = DateTime::createFromFormat($format, $inputValue);
                    if (!($d && $d->format($format) == $inputValue)) {
                        $this->logNonCompliedInput($def['name'], 'Input must be a valid datetime');
                    }
                    break;
                case 'u':
                    /** TODO: Skip checking for unknown type * */
                case 's':
                    /** TODO: Skip checking for string type * */
                    break;
            }
        }

        if ($this->removeNonDefineInput) {
            $this->deleteInputWithoutDefinition();
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

        $GET_array = $apply_sanitize ? filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING) : $_GET;

        $this->copyArrayProperties($GET_array, true);
    }

    /**
     *
     * @param boolean $apply_sanitize sanitize input before assign to object. Default to true.
     */
    public function copy_POST_properties($apply_sanitize = true) {

        $POST_array = $apply_sanitize ? filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING) : $_POST;

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
            $input = filter_var_array($input, FILTER_SANITIZE_STRING);
        }

        if (is_array($input)) {
            $this->copyArrayProperties($input);
        }
    }

}
