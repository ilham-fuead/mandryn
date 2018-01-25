<?php

/**
 * Create universal object and handle on-the-fly properties
 *
 * Create object with on-the-fly properties
 *
 * @category   Utility
 * @package    Mandryn/Mandryn
 * @author     Mohd Ilhammuddin Bin Mohd Fuead <ilham.fuead@gmail.com>
 * @copyright  2017-2022 The Mandryn Team
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: 1.1.1
 * @since      Class available since Release 2.0.0
 */
class MagicObject {

    protected $property;

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

    /**
     * 
     * @param array $array
     * @param boolean $disposeSource
     */
    public function copyArrayProperties(array &$array, $disposeSource = false) {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $this->property[$key] = $value;
            }

            if ($disposeSource) {
                unset($array);
            }
        }
    }

    public function renameProperty($oriKey, $newKey) {
        /* Skip if oriKey equal to newKey */
        if ($oriKey != $newKey) {
            if (array_key_exists($oriKey, $this->property)) {
                $this->property[$newKey] = $this->property[$oriKey];
                unset($this->property[$oriKey]);
            }
        }
    }

    /**
     * 
     * @param array $arrayKeySet ['oldKey1'=>'newKey1','oldKey2'=>'newKey2']
     * 
     */
    public function renamePropertyByArrayKeySet(array $arrayKeySet) {
        foreach ($arrayKeySet as $oldKey => $newKey) {
            $this->renameProperty($oldKey, $newKey);
        }
    }

    public function getJsonString() {
        return json_encode($this->property);
    }

    public function toArray() {
        return $this->property;
    }

    public function applyFilterDefinition($definition, $add_empty = false) {
        $this->property = filter_var_array($this->property, $definition, $add_empty);
    }

    public function __unset($name) {
        unset($this->property[$name]);
    }

    public function __destruct() {
        unset($this->property);
    }

}
