<?php
class InputsFilter {

    private $inputsFilterDefinition;
    private $inputs;

    const filterArray = [
        "int" => FILTER_VALIDATE_INT,
        "flo" => FILTER_VALIDATE_FLOAT,
        "str" => FILTER_SANITIZE_STRING
    ];

    public function __construct(array $inputs) {
        $this->inputsFilterDefinition = new MagicObject();
        $this->inputs=new MagicObject();

        foreach ($inputs as $input) {
            $this->inputs->{$input["name"]}=$input["value"];
            $this->inputsFilterDefinition->{$input["name"]} = ["filter" => self::filterArray[$input["type"]]];
        }
    }

    public function getDefinition() {
        return $this->inputsFilterDefinition->toArray();
    }

    public function getFilteredInputs() {
        return filter_var_array($this->inputs->toArray(), $this->inputsFilterDefinition->toArray());
    }
    
    public function addFilterOption($fieldName,array $option){
        if(array_key_exists('options', $this->inputsFilterDefinition->{$fieldName})){
            array_push($this->inputsFilterDefinition->{$fieldName}["options"],$option);
        } else {
            array_push($this->inputsFilterDefinition->{$fieldName},["options"=>$option]);
        }
    }

}
