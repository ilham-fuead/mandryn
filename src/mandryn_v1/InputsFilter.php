<?php

class InputsFilter {

    private $inputsFilterDefinition;
    private $inputs;
    private $autoSanitize;

    const filterArray = [
        "int" => FILTER_VALIDATE_INT,
        "flo" => FILTER_VALIDATE_FLOAT,
        "str" => FILTER_SANITIZE_STRING
    ];

    public function __construct($autoSanitize = false) {
        $this->inputs = [];
        $this->inputsFilterDefinition = [];
        $this->autoSanitize = $autoSanitize;
    }

    public function setSingleInput(array $input, $FILTER_VALIDATE_OR_SANITIZE = "") {
        $this->inputs[$input["name"]] = $input["value"];
        if ($FILTER_VALIDATE_OR_SANITIZE === "") {
            $this->setFilterDefinition($input["name"], self::filterArray[$input["type"]]);
        } else {
            $this->setFilterDefinition($input["name"], $FILTER_VALIDATE_OR_SANITIZE);
        }
    }

    public function setMultipleInputs(array $inputs) {
        foreach ($inputs as $input) {
            $this->setSingleInput($input);
        }
    }

    public function setFilterDefinition($inputName, $FILTER_VALIDATE_OR_SANITIZE) {
        $this->inputsFilterDefinition[$inputName] = [];
        $this->inputsFilterDefinition[$inputName]["filter"] = $FILTER_VALIDATE_OR_SANITIZE;
    }

    public function deleteFilterDefinition($inputName) {
        unset($this->inputsFilterDefinition[$inputName]);
    }

    public function getDefinition() {
        return $this->inputsFilterDefinition;
    }

    public function getFilteredInputs() {
        return filter_var_array($this->inputs, $this->inputsFilterDefinition);
    }

    public function addFilterOptions($inputName, $optionName, $optionValue) {
        if (array_key_exists('options', $this->inputsFilterDefinition[$inputName])) {
            $this->inputsFilterDefinition[$inputName]["options"][$optionName] = $optionValue;
        } else {
            $this->inputsFilterDefinition[$inputName]["options"] = [];
            $this->inputsFilterDefinition[$inputName]["options"][$optionName] = $optionValue;
        }
    }

    public function addFilterFlags($inputName, $flagName, $flagValue) {
        if (array_key_exists('flags', $this->inputsFilterDefinition[$inputName])) {
            $this->inputsFilterDefinition[$inputName]["flags"][$flagName] = $flagValue;
        } else {
            $this->inputsFilterDefinition[$inputName]["flags"] = [];
            $this->inputsFilterDefinition[$inputName]["flags"][$flagName] = $flagValue;
        }
    }

}
