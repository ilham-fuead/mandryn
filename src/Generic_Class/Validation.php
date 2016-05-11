<?php
interface IInputFormat
{
    const TELEPHONE = "/^[0]\d{9}$/";
    const ALPHANUMERIC = '/^[^#\$%\^\*\+=\{\}\[\];\|\<\>"]+$/';
    const IC="/^\d{12,12}$/";
    const POSTCODE="/^\d{5,5}$/";
    const USERID="/^[^'|\s]{1}[A-Za-z0-9]{5,15}$/";
    const PASSWORD="/^[a-zA-Z1-9]{6,15}$/";
    const EMAIL="/^([a-zA-Z0-9])+([\.a-zA-Z0-9_-]*[a-zA-Z0-9]+)*@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)*\.([a-zA-Z]{2,6})$/";
}

class Validation implements IInputFormat
{
    public $errorList;
    public $day;
    public $month;
    public $year;
    public $fileName;
    public $fileExt;
    public $validAlpha;

    public function __construct()
    {
        $this->resetErrorList();
    }

    private function resetErrorList()
    {
        $this->errorList=array();
        $this->fieldFormats=array();
    }

    public function isEmpty( $field )
    {
        if ( trim($field) == "" || strlen( trim($field)) == 0 )
            return TRUE;
        else
            return FALSE;
    }

    public function isAlpha( $alphaField )
    {
        $this->validAlpha = preg_match( self::ALPHANUMERIC, $alphaField );
        if ( $this->validAlpha == TRUE )
            return TRUE;
        else
            return FALSE;
    }

    public function isNum( $numField )
    {
        if ( is_numeric($numField) == FALSE )
            return FALSE;
        else
            return TRUE;
    }

    public function isNumWithLimit( $numField, $min, $max )
    {
        if ( $this->checkNum($numField) )
        {
            if ( $numField >= $min && $numField <= $max )
                return TRUE;
            else
                return FALSE;
        }
        else
        {
            return FALSE;
        }
    }

    public function isDate( $dateField )
    {
        list( $this->day, $this->month, $this->year ) = preg_split( '[/.-]', $dateField );
        $validDate = checkdate( $this->month, $this->day, $this->year );
        if ( $validDate == TRUE )
            return TRUE;
        else
            return FALSE;
    }

    public function isDataFormat( $data, $typeOfData )
    {
        if ( $typeOfData == "myKad" )
        {
            if ( preg_match(self::IC, $data) )
                return TRUE;
            else
                return FALSE;
        }
        else if ( $typeOfData == "poskod" )
        {
            if ( preg_match(self::POSTCODE, $data) )
                return TRUE;
            else
                return FALSE;
        }
        else if ( $typeOfData == "telefon" )
        {
            if ( preg_match(self::TELEPHONE, $data) )
                return TRUE;
            else
                return FALSE;
        }
        else if ( $typeOfData == "emel" )
        {
            if ( preg_match(self::EMAIL, $data) )
                return TRUE;
            else
                return FALSE;
        }
        else
        {
            return FALSE;
        }
    }

    public function isFile( $fileField )
    {
        list( $this->fileName, $this->fileExt ) = split( '[.]', $fileField );

        if ( $this->fileExt == "csv" || $this->fileExt == "xls" || $this->fileExt == "xlsx" || $this->fileExt == "txt" )
            return TRUE;
        else
            return FALSE;
    }

    public function addErrorList( $field, $errorMessage )
    {
        $this->errorList[] = array( "field" => $field, "message" => $errorMessage );
    }

    public function isError()
    {
        if ( sizeof($this->errorList) > 0 )
            return TRUE;
        else
            return FALSE;
    }

    public function getErrorList()
    {
        if ( $this->isError() == TRUE )
            return $this->errorList;
    }

    public function __destruct()
    {
        unset( $this->errorList );
    }
}
?>