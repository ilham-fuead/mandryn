<?php
namespace Mandryn;

class MagicObject {

    private $property;

    public function __construct() {
        $this->property = [];
    }

    public function __set($name, $value) {
        $this->property[$name] = $value;
    }

    public function __get($name) {
        if (array_key_exists($name, $this->property)) {
            return $this->property[$name];
        }
    }

    public function __isset($name) {
        return isset($name);
    }

    public function getJsonString() {
        $jsonStr='{';
        foreach ($this->property as $key=>$val){
            if($jsonStr!='{'){
                $jsonStr.=',';
            }
            $jsonStr.="\"$key\":\"$val\"";
        }
        $jsonStr.='}';
        
        return $jsonStr;
    }

    public function __unset($name) {
        unset($name);
    }

}
