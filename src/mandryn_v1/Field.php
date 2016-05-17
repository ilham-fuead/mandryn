<?php
class Field{
	protected $field_id;
	protected $field_name;
	protected $field_label;
	protected $field_style;
	protected $field_class;
	protected $field_events;
        protected $field_html_properties;

	public function getId(){
		return $this->field_id;
	}

	public function getName(){
		return $this->field_name;
	}

	public function __construct($id,$label){
		$this->field_events=array();
		$this->field_id=$id;
		$this->field_name=$id;
		$this->field_label=$label;
	}

	public function addEvent($event,$handler){
		$this->field_events[]=array($event=>$handler);
	}
        
        public function add_HTML_property($property,$value){
		$this->field_html_properties[]=array($property=>$value);
	}

        protected function renderEvents(){
		$eventsHTML="";
		if(sizeof($this->field_events)>0){
			foreach($this->field_events as $events){
				foreach($events as $evt=>$hdl){
					$eventsHTML .= "$evt='$hdl' ";
				}
			}
		}
		return $eventsHTML;
	}
        
        protected function render_HTML_properties(){
		$HTML_properties="";
		if(sizeof($this->field_html_properties)>0){
			foreach($this->field_html_properties as $properties){
				foreach($properties as $property=>$value){
					$HTML_properties .= "$property='$value' ";
				}
			}
		}
		return $HTML_properties;
	}

	public function setStyle($style){
		$this->field_style=$style;
	}

	public function setClass($class){
		$this->field_class=$class;
	}

	protected function renderCss(){
		$cssHTML="";
		if(isset($this->field_style))
			$cssHTML .= "style='$this->field_style' ";
		if(isset($this->field_class))
			$cssHTML .= "class='$this->field_class' ";

		return $cssHTML;
	}

	public function __destruct(){
		unset($this->field_events);
	}

}
?>