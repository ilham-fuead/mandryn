<?php

/**
 * Class to construct proper filter definition. 
 * Constructed definition can be obtain by calling method getDefinition 
 * that return filter definition is in an array form.
 *
 * @author Mohd Ilhammuddin Bin Mohd Fuead
 */
class FilterDefinition {

    private $inputsFilterDefinition;
    private $autoSanitize;

    const filterArray = [
        "int" => FILTER_VALIDATE_INT,
        "flo" => FILTER_VALIDATE_FLOAT,
        "str" => FILTER_SANITIZE_STRING
    ];

    public function __construct($autoSanitize = false) {
        $this->inputsFilterDefinition = [];
        $this->autoSanitize = $autoSanitize;
    }

    public function addFilter(array $input) {
        $this->setFilterDefinition($input["name"], self::filterArray[$input["type"]]);
    }

    public function addFilterByGroup(array $inputs) {
        foreach ($inputs as $input) {
            $this->addFilter($input);
        }
    }

    public function addManualFilter($inputName, $FILTER_VALIDATE_OR_SANITIZE) {
        $this->setFilterDefinition($inputName, $FILTER_VALIDATE_OR_SANITIZE);
    }

    private function setFilterDefinition($inputName, $FILTER_VALIDATE_OR_SANITIZE) {
        $this->inputsFilterDefinition[$inputName] = [];
        $this->inputsFilterDefinition[$inputName]["filter"] = $FILTER_VALIDATE_OR_SANITIZE;
    }

    public function deleteFilterDefinition($inputName) {
        unset($this->inputsFilterDefinition[$inputName]);
    }

    public function getDefinition() {
        return $this->inputsFilterDefinition;
    }

    public function addFilterOptions($inputName, $optionName, $optionValue) {
        if (array_key_exists($inputName, $this->inputsFilterDefinition)) {
            if (array_key_exists('options', $this->inputsFilterDefinition[$inputName])) {
                $this->inputsFilterDefinition[$inputName]["options"][$optionName] = $optionValue;
            } else {
                $this->inputsFilterDefinition[$inputName]["options"] = [];
                $this->inputsFilterDefinition[$inputName]["options"][$optionName] = $optionValue;
            }
        }
    }

    public function addFilterFlags($inputName, $flagName, $flagValue) {
        if (array_key_exists($inputName, $this->inputsFilterDefinition)) {
            if (array_key_exists('flags', $this->inputsFilterDefinition[$inputName])) {
                $this->inputsFilterDefinition[$inputName]["flags"][$flagName] = $flagValue;
            } else {
                $this->inputsFilterDefinition[$inputName]["flags"] = [];
                $this->inputsFilterDefinition[$inputName]["flags"][$flagName] = $flagValue;
            }
        }
    }
    
    public function __destruct() {
       unset($this->inputsFilterDefinition);
    }

}
