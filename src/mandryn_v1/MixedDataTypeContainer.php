<?php
class MixedDataTypeContainer {

    private $mixedDataTypeArray;

    public function __construct() {
        $this->mixedDataTypeArray = array();
    }

    public function addMixedDataTypeToArray($mixedDataType) {
        $this->mixedDataTypeArray[] = $mixedDataType;
    }
    
    public function setMixedDataTypeValueByIndex($arrayIndex,$newValue){
        if($arrayIndex<sizeof($this->mixedDataTypeArray)){
            $this->mixedDataTypeArray[$arrayIndex]=$newValue;
        }
    }

    public function getMixedDataTypeArray() {
        return $this->mixedDataTypeArray;
    }

    public function getMixedDataTypeValueByIndex($arrayIndex){
        if($arrayIndex<sizeof($this->mixedDataTypeArray)){
            return $this->mixedDataTypeArray[$arrayIndex];
        }
    }
    
    public function __destruct() {
        unset($this->mixedDataTypeArray);
    }
}
?>
