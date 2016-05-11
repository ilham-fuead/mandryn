<?php
include_once 'SQLQuery.php';
abstract class Login2 extends User2 {
    private $userName;
    private $authenticationStatus;
    private $dbQueryObj;
    private $tableName;
    private $idField;
    private $pwdField;
    protected $sqlStrObj;
    protected $afterLogOutPage;
    protected $afterLogInPage;
    protected $failedLogInPage;
    protected $notInSessionPage;
    protected $invalidRightRedirectPage;
    protected $noAuthorizationForModulPage;

    public function setDataSource( $userTableName, $idFieldName, $pwdFieldName ) { 

        $this->tableName = $userTableName;
        $this->idField = $idFieldName;
        $this->pwdField = $pwdFieldName;
        
    }
    
    protected function setDefaultLoginSQL($uName, $uPassword){
        $username=mysqli_real_escape_string($this->dbQueryObj->getLink(),$uName);
        $password=mysqli_real_escape_string($this->dbQueryObj->getLink(),$uPassword);
        
        $this->sqlStrObj->setSELECTQuery($this->tableName);
        $this->sqlStrObj->addReturnField($this->idField);
        $this->sqlStrObj->addConditionField($this->idField, $username, IFieldType::STRING_TYPE, IConditionOperator::NONE);
        $this->sqlStrObj->addConditionField($this->pwdField, $password, IFieldType::STRING_TYPE, IConditionOperator::AND_OPERATOR);
    }
    
    /**
     * setCustomLoginSQL
     * 
     * By default will set passed SQLQuery Object to current instance SQLQuery Object.<br>
     * To fully customize the login SQL do override this method.
     * 
     * @param SQLQuery $sqlStrObj
     * @author Mohd Ilhammuddin <ilham.fuead@gmail.com>
     */
    protected function setCustomLoginSQL($uName, $uPassword, SQLQuery $sqlStrObj){
        $this->sqlStrObj=$sqlStrObj;
    }

    public function login( $uName, $uPassword,SQLQuery $sqlStrObj=NULL ) {
        
        if($sqlStrObj!=NULL){
            $this->setCustomLoginSQL($uName, $uPassword, $sqlStrObj);
        }else{
            $this->setDefaultLoginSQL($uName, $uPassword);
        }

        $this->dbQueryObj->setSQL_Statement( $this->sqlStrObj->getSQLQuery() );
        $this->dbQueryObj->runSQL_Query();

        if($this->dbQueryObj->isHavingRecordRow()) {
            $row = mysqli_fetch_assoc( $this->dbQueryObj->getQueryResult() );
            $this->setAuthenticate( $row[$this->idField]);
            $this->redirect("in");
        }

    }

    private function setAuthenticate( $uName) {
        $this->userName = $uName;
        $this->authenticationStatus = TRUE;
        $_SESSION['IDPengguna']=$this->userName;
    }

    public function getLogInStatus() {
        return $this->authenticationStatus;
    }
}
?>