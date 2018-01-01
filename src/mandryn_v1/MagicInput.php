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
 * @version    Release: 1.0.0
 * @since      Class available since Release 2.1.0
 */
class MagicInput extends MagicObject
{
    private $inputDefinition;
    
    public function __construct() {
        $this->inputDefinition=[];
        parent::__construct();
    }
    
    private function addInputDefinition($inputName,$inputType,$inputDefault,$requiredStatus=false){
        $this->inputDefinition[]=['name'=>$inputName,'type'=>$inputType,'default'=>$inputDefault,'required'=>$requiredStatus];
    }
    
    public function setInputsDefinition(array $InputsDefinition){
        foreach($InputsDefinition as $def){
            $this->addInputDefinition($def[0],$def[1],$def[2],$def[3]);
        }
    }
    
    public function isInputsConfirmedDefinition(){
        $confirmed=true;
        foreach (parent::toArray() as $key=>$val){
            foreach($this->inputDefinition as $def){
                
            }
        }
    }

    /**
     *
     * @param boolean $apply_sanitize sanitize input before assign to object. Default to true.
     */
    public function copy_GET_properties($apply_sanitize = true)
    {
        
        $GET_array=$apply_sanitize?filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING):$_GET;
          
        $this->copyArrayProperties($GET_array, true);
    }
    
    /**
     *
     * @param boolean $apply_sanitize sanitize input before assign to object. Default to true.
     */
    public function copy_POST_properties($apply_sanitize = true)
    {
        
        $POST_array=$apply_sanitize?filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING):$_POST;
          
        $this->copyArrayProperties($POST_array, true);
    }
    
    /**
     *
     * @param boolean $apply_sanitize sanitize input before assign to object. Default to true.
     */
    public function copy_RAW_JSON_properties($apply_sanitize = true)
    {
        $request = file_get_contents('php://input');
        
            /*2nd parameter supply true to convert request as input array, false as input object*/
            $input = json_decode($request, true);
        
        if ($apply_sanitize && is_array($input)) {
            $input=filter_var_array($input, FILTER_SANITIZE_STRING);
        }
            
        if (is_array($input)) {
            $this->copyArrayProperties($input);
        }
    }

    public function getJsonString() {
        if(count(parent::toArray())===0){
            return '{}';
        }else{
            return parent::getJsonString();
        }        
    }
}
