<?php
class Form{
	private $formName;
	private $formId;
	private $formAction;
	private $formMethod;
	private $formStyle;
	private $formClass;
	private $formFields;
	private $formContainer;

	public function __construct($name,$action,$method,$container){
		$this->formName=$name;
		$this->formId=$name;
		$this->formMethod=$method;
		$this->formContainer=$container;
		$this->formFields=array();
	}

	public function addField($field){
		$this->formFields[]=$field;
	}

	private function renderFieldRows(){
		$fieldRows="";
		if($this->formContainer=='div'){
			foreach($this->formFields as $field){
				$field_id=$field->getId();
				$fieldPair=$field->renderField();
				$fieldRows .= "<div id='field'>\n";
				$fieldRows .= "<label for='$field_id'>{$fieldPair['label']}</label>\n";
				$fieldRows .= "{$fieldPair['fieldHTML']}";
				$fieldRows .= "\n</div>\n";
			}
		}else if($this->formContainer=='table'){
			/********************************/
			/******Not yet implemented*******/
			/********************************/
		}

		return $fieldRows;
	}

	private function renderCss(){
		$cssHTML="";
		if(isset($this->formStyle))
			$cssHTML .= "style='$this->formStyle' ";
		if(isset($this->formClass))
			$cssHTML .= "class='$this->formClass' ";

		return $cssHTML;
	}

	public function renderForm(){
		$formBlock = "<form name='$this->formName' id='$this->formId' action='$this->formAction' method='$this->formMethod' ";
		$formBlock .= $this->renderCss();
		$formBlock .= ">\n";
		$formBlock .= $this->renderFieldRows();
		$formBlock .= "</form>";
		return $formBlock;
	}

	public function __destruct(){
		unset($this->formFields);
	}
}
?>