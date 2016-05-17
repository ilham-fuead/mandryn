<?php
    /*
     * @category UI, HTML Input Generator
     * @copyright [Closed Distribution] e-SILA,Logic & Engine Dev Team May,2011
     * @author Mohd Fadil bin Md Sari & Fernandez Christie Jassil
    */

    class RadioCheckBox extends Field
    {

        private $rc_options;
        private $rc_selectedValue;
        private $dbQueryObj;
        private $sql;
        /*
         * @param string $id ID for the html radio button field to generate
         * @param string $label Label for the html select field to generate
        */        
        public function __construct( $id, $label )
        {
            parent::__construct( $id, $label );
            $this->rc_options = '';
            $this->rc_options = array();
        }

        public function setDataSource( DBQuery $dbQueryObj )
        {
            $this->dbQueryObj = $dbQueryObj;
        }

        /*
         * @param string $tableName Name of the reference table
         * @param string $keyColumnName Name of column that store key or id value
         * @param string $valueColumnName Name of column that store description or label
        */
        public function setDataSourceTable( $tableName, $keyColumnName, $valueColumnName )
        {
            $sql = new SQLQuery();
            $sql->setSELECTQuery( $tableName );
            $sql->addReturnField( $keyColumnName );
            $sql->addReturnField( $valueColumnName );
            $this->sql = $sql->getSQLQuery();
            $this->addOptionsFromDataSource( $keyColumnName, $valueColumnName );
        }

        public function setDataSourceSQL($sql,$keyColumnName, $valueColumnName){
            $this->sql=$sql;
            $this->addOptionsFromDataSource($keyColumnName, $valueColumnName);
        }

        private function addOptionsFromDataSource( $keyColumnName, $valueColumnName )
        {
            $this->dbQueryObj->setSQL_Statement( $this->sql );
            $this->dbQueryObj->runSQL_Query();
            if( mysqli_num_rows($this->dbQueryObj->getQueryResult() ) > 0 )
            {
                while( $field = mysqli_fetch_assoc( $this->dbQueryObj->getQueryResult() ) )
                {
                    $this->rc_options[] = array( 'value' => $field[$keyColumnName], 'text' => $field[$valueColumnName] );
                }
            }
        }

        public function setSelectedValue( $selectedValue )
        {
            $this->rc_selectedValue = $selectedValue;
        }

        public function renderField( $checkBoxOrRadio, $verticalOrHorizontal )
        {
            $radioHTML = '';
            $type = '';
            $position = '';
            
            if( $checkBoxOrRadio == 'R' )
            {
                $type = 'radio';
                $i = '';
            }
            else if( $checkBoxOrRadio == 'C' )
            {
                $type = 'checkbox';
                $i = 0;
            }

            if( $verticalOrHorizontal == 'H' )
                $position = '';
            else if( $verticalOrHorizontal == 'V' )
                $position = '<br>';

            if ( sizeof( $this->rc_options ) > 0 )
            {
                foreach ( $this->rc_options as $option )
                {
                    if( $checkBoxOrRadio == 'C' )
                        $i++;

                    if ( $option['value'] == $this->rc_selectedValue )
                        $radioHTML .= "<input type='$type' id='$this->field_id$i' name='$this->field_name$i' value='{$option['value']}' checked ";
                    else
                        $radioHTML .= "<input type='$type' id='$this->field_id$i' name='$this->field_name$i' value='{$option['value']}' ";

                    $radioHTML .= $this->renderCss();
                    $radioHTML .= $this->renderEvents();
                    $radioHTML .= ">{$option['text']}$position";
                }
            }

            return array( 'fieldHTML' => $radioHTML, 'label' => $this->field_label );
        }

        public function addValueLabel( $rdbValue, $rdbText )
        {
            $this->rc_options[] = array( 'value' => $rdbValue, 'text' => $rdbText );
        }
    }

/*
 *
 * Version: 1.1
 * Updated By: Fernandez Christie Jassil
 * Date: 15 Oct 2012
 * Remarks: Add function setDataSourceSQL, to be use for hardcode query.
 *
 * Version: 1.0 [ Released Date: 2011 ]
 * Developer: Mohd Fadil bin Md Sari & Fernandez Christie Jassil
 * Description/Remarks:
 * A UI Utility class for displaying record in radiobutton and checkbox.
 *
*/
?>