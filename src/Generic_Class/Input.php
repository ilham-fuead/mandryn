<?php
class Input extends Field{
	private $input_type;
	private $input_value;
	public $input_size;
	public $input_maxlength;

	public function __construct($id,$label,$type){
		parent::__construct($id,$label);
		$this->input_type=$type;
	}

	public function setValue($value){
		$this->input_value=$value;
	}

	public function renderField(){
		$inputHTML="<input type='{$this->input_type}' id='{$this->field_id}' name='{$this->field_name}' ";

		if(isset($this->input_size))
			$inputHTML .= "size='{$this->input_size}' ";
		if(isset($this->input_maxlength))
			$inputHTML .= "maxlength='{$this->input_maxlength}' ";
		if(isset($this->input_value))
			$inputHTML .= "value='{$this->input_value}' ";
		$inputHTML .= $this->renderCss();
		$inputHTML .= $this->renderEvents();
		$inputHTML .= "/>";

		return array('fieldHTML'=>$inputHTML,'label'=>$this->field_label);
	}
}
?>