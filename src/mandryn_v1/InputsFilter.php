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

}

$mgObj = new MagicObject();

$inputs = [
    ["name" => "txtAge", "value" => "9", "type" => "int"],
    ["name" => "txtMail", "value" => "kl@kl.>>>com", "type" => "str"],
    ["name" => "txtWages", "value" => "10000.98", "type" => "flo"]
];

$fields = [
    "txtAge" => "9",
    "txtMail" => "kl@kl.>>>com",
    "txtWages" => "10000.98"
];

$filterArray = [
    "int" => FILTER_VALIDATE_INT,
    "flo" => FILTER_VALIDATE_FLOAT,
    "str" => FILTER_SANITIZE_STRING
];

/**
  foreach ($inputs as $input) {
  $mgObj->{$input["name"]} = ["filter" => $filterArray[$input["type"]]];
  }

  echo '<pre>';

  print_r($fields);

  echo '<p>';

  print_r($mgObj->toArray());

  echo '<p>';

  var_dump(filter_var_array($fields, $mgObj->toArray()));
 * 
 */
$def = new InputFilter($inputs);

echo '<pre>';

print_r($def->getDefinition());

echo '<p>';

print_r($def->getFilteredInputs());