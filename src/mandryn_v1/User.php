<?php
include_once 'SQLQuery.php';

interface IRedirectType {
    const AFTER_LOGIN_TYPE="in";
    const AFTER_LOGOUT_TYPE="out";
    const MISSING_SESSION_TYPE="lost";
    const RESTRICT_ACCESS_TYPE="restrict";
    const RESTRICT_RIGHT_ACCESS_TYPE="noRight";
    const DEVELOPMENT_MODE="debug";
}

interface ISecurityLevel {
    CONST SESSION_ROLE_RIGHT=1; //highest level
    CONST SESSION_AND_ROLE=2;
    CONST SESSION_ONLY=3;
    CONST DEVELOPMENT_NO_SECURITY=4;
}

interface IRightLevel{
    CONST ADM_LVL_1=0;
    CONST ADM_LVL_2=1;
    CONST ADM_LVL_3=2;
    CONST USR_ODR=3;
}

interface IActionType {
    CONST STOP_APP=1;
    CONST GET_CLEARANCE_STATUS=2;
    CONST REDIRECT_NO_RIGHT=3;
}

interface IRedirectCode{
    CONST SUCCESS_LOGIN=600;
    CONST FAILED_LOGIN=610;
    CONST SUCCESS_LOGOUT=621;
    CONST INVALID_SESSION=630;
    CONST INVALID_MODULE_ROLE=640;
    CONST INVALID_MODULE_RIGHT=650;
}

abstract class User {
    private $userName;
    private $nama;
    private $emel;
    private $unit;
    private $jawatan;
    private $authenticationStatus;
    private $dbQueryObj;
    private $tableName;
    private $idField;
    private $pwdField;
    private $byRightAuthorization;
    protected $afterLogOutPage;
    protected $afterLogInPage;
    protected $notInSessionPage;
    protected $invalidRightRedirectPage;
    protected $noAuthorizationForModulPage;

    public function __construct(DBQuery $DBQueryObj) {
        $this->dbQueryObj = $DBQueryObj;
        $this->authenticationStatus = FALSE;
        $this->afterLogOutPage="none";
        $this->afterLogInPage="none";
        $this->notInSessionPage="none";
        $this->noAuthorizationForModulPage="none";
        $this->invalidRightRedirectPage="none";
        $this->byRightAuthorization=FALSE;
        session_start();
    }

    public function setDataSource( $userTableName, $idFieldName, $pwdFieldName ) { 

        $this->tableName = $userTableName;
        $this->idField = $idFieldName;
        $this->pwdField = $pwdFieldName;
        
    }

    public function login( $uName, $uPassword ) {
        $sql = "SELECT $this->idField
                    FROM $this->tableName
                    WHERE $this->idField = '$uName'
                    AND $this->pwdField = '$uPassword' 
                    AND jenis_ubah='1'"; //baru tambah 07052014

        $this->dbQueryObj->setSQL_Statement( $sql );
        $this->dbQueryObj->runSQL_Query();

        if( mysqli_num_rows( $this->dbQueryObj->getQueryResult() ) > 0 ) {
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

    public function getInSessionStatus() {
        if(isset($_SESSION['IDPengguna'])) {
            $this->loadUserDetail($_SESSION['IDPengguna']);
            return TRUE;
        }else
            return FALSE;
    }

    public function getUserPageAuthorization($uName,$pageID) {
        $sql = "SELECT AccessRolesID,AccessRightID
                    FROM AccessRight
                    WHERE IDpengguna = '$uName'
                    AND AccessRolesID = '$pageID'";

        $this->dbQueryObj->setSQL_Statement( $sql );
//        echo $sql;
//        exit(0);
        $this->dbQueryObj->runSQL_Query();

        if( mysqli_num_rows( $this->dbQueryObj->getQueryResult() ) > 0 ) {
            $row=mysqli_fetch_assoc($this->dbQueryObj->getQueryResult());
            $this->initializeValidPageAuthorization($uName,$row['AccessRolesID'],$row['AccessRightID']);
            return TRUE;
        }else {
            return FALSE;
        }

    }

    private function initializeValidPageAuthorization($userName,$authorisedPageID,$authorisedPageRight) {
        $_SESSION['authorisedPageID']=$authorisedPageID;
        $_SESSION['authorisedPageRight']=$authorisedPageRight;
    }

    public function loadUserDetail($uName) {
        $SQLQueryObj=new SQLQuery();
        $SQLQueryObj->setSELECTQuery('pengguna');
        $SQLQueryObj->addReturnField('IDpengguna');
        $SQLQueryObj->addReturnField('katalaluan');
        $SQLQueryObj->addReturnField('nama');
        $SQLQueryObj->addReturnField('emel');
        $SQLQueryObj->addReturnField('unit');
        $SQLQueryObj->addReturnField('jawatan');
        $SQLQueryObj->addConditionField('IDpengguna', $uName, IFieldType::STRING_TYPE, IConditionOperator::NONE);

        $this->dbQueryObj->setSQL_Statement($SQLQueryObj->getSQLQuery());
        $this->dbQueryObj->runSQL_Query();

        if(mysqli_num_rows ($this->dbQueryObj->getQueryResult())>0) {

            $row=mysqli_fetch_assoc($this->dbQueryObj->getQueryResult());

            $this->userName=$row['IDpengguna'];
            $this->nama=$row['nama'];
            $this->emel=$row['emel'];
            $this->unit=$row['unit'];
            $this->jawatan=$row['jawatan'];
        }

    }

    public function getIDpengguna() {
        return $this->userName;
    }

    public function getNamaPengguna() {
        return $this->nama;
    }
    
    public function getJawatanPengguna() {
        return $this->jawatan;
    }
    
    public function getUnitPengguna() {
        return $this->unit;
    }

    public function logout() {
        $this->authenticationStatus = FALSE;

        if($this->getInSessionStatus()==TRUE) {
            session_unset();
            session_destroy();
            session_write_close();
            setcookie(session_name(),'',0,'/');
            session_regenerate_id(true);
            $this->setRedirectFiles();
        }
        $this->redirect("out");
    }

    public function redirect($IRedirectType) {
        $this->setRedirectFiles();
        $pathFile="";

        if($this->afterLogOutPage!="none" && $IRedirectType=="out")
            header( "Location:$this->afterLogOutPage" );

        if($this->afterLogInPage!="none" && $IRedirectType=="in")
            header( "Location:$this->afterLogInPage" );

        if($this->notInSessionPage!="none" && $IRedirectType=="lost")
            header( "Location:$this->notInSessionPage" );

        if($this->noAuthorizationForModulPage!="none" && $IRedirectType=="restrict")
            header( "Location:$this->noAuthorizationForModulPage" );

        if($IRedirectType=="noRight") {
            if($this->invalidRightRedirectPage=="none")
                $pathFile=$this->noAuthorizationForModulPage;
            else
                $pathFile=$this->invalidRightRedirectPage;
            header( "Location:$pathFile" );
        }
    }

    private function securingBySession() {
        if($this->getInSessionStatus()==FALSE) {
            $this->redirect(IRedirectType::MISSING_SESSION_TYPE);
        }
        return TRUE;
    }

    private function securingByRoles($pageID) {
        if($this->getUserPageAuthorization($this->getIDpengguna(), $pageID)==FALSE) {
            $this->redirect(IRedirectType::RESTRICT_ACCESS_TYPE);
        }
        return TRUE;
    }

    private function securingByRight() {
        $this->byRightAuthorization=TRUE;
    }

    public function securingPage($pageID,$ISecurityLevel) {
        if($ISecurityLevel<=3) {
            if($this->securingBySession()==TRUE) {
                if($ISecurityLevel<=2) {
                    if($this->securingByRoles($pageID)==TRUE) {
                        if($ISecurityLevel==1) {
                            $this->securingByRight();
                        }
                    }
                }

            }

        }
    }

    private function getRightStatus($pageRight) {
        if($_SESSION['authorisedPageRight']<=$pageRight) {
            return TRUE;
        }else {
            return FALSE;
        }
    }

    public function imposeRightOnPage($pageRight,$IActionType) {
        if($this->byRightAuthorization==TRUE) {
            $pageRightStatus=$this->getRightStatus($pageRight);
            if($IActionType==1) {
                exit(0);
            }else if($IActionType==2) {
                return $pageRightStatus;
            }else if($IActionType==3) {
                if($pageRightStatus==FALSE)
                    $this->redirect(IRedirectType::RESTRICT_RIGHT_ACCESS_TYPE);
            }
        }
    }

    /**
    *
    * To set up which file to redirect after successful login
    * 
    * $filepath must be relative to the file in effect which invoke 
    * this class object and may not necessarily relative to THIS file.
    * 
    */
    protected function setAfterLogInPage($filePath){
        $this->afterLogInPage=$filePath;        
    }
    
    protected function setAfterLogOutPage($filePath){
        $this->afterLogOutPage=$filePath . '?status=' . IRedirectCode::SUCCESS_LOGOUT;
    }
    
    protected function setNotInSessionPage($filePath){
        $this->notInSessionPage=$filePath . '?status=' . IRedirectCode::INVALID_SESSION;
    }
    
    protected function setNoAuthorizationForModulPage($filePath){
        $this->noAuthorizationForModulPage=$filePath . '?status=' . IRedirectCode::INVALID_MODULE_ROLE;
    }
    
    protected function setInvalidRightRedirectPage($filePath){
        $this->invalidRightRedirectPage=$filePath . '?status=' . IRedirectCode::INVALID_MODULE_RIGHT;
    }
    
    /**
     * To set up proper redirection file
     * 
     * An abstract method that must be implemented in order to set up proper 
     * redirection to support security feature in User class.     
     * 
     * Utility Method for setting up redirection file (use $this-> to invoke):<br>
     * i.   setAfterLogInPage<br>
     * ii.  setAfterLogoutInPage<br>
     * iii. setNotInSessionPage<br>
     * iv.  setNoAuthorizationForModulPage<br>
     * v.   setInvalidRightRedirectPage<br>
     * 
     */
    abstract protected function setRedirectFiles();

}

/*
 * Version: 1.0 [ Released Date: 18 Jun 2012 ]
 * Developer: Mohd Ilhammuddin Bin Mohd Fuead
 * Description/Remarks:
 * A module level class for implementing
 * page level security based on user roles & right.
 * Class cover from authentication (login process), authorization (user roles & right),
 * security process handling to safely logging out of system.
 *
*/

/*
 * Version: 1.0.1 [ Released Date: 8 May 2014 ]
 * Developer: Mohd Asyraf Bin Mohd Azmi
 * Description/Remarks: Public method login() : SQL "jenis_ubah='1'" condition
 * Alter the login check, whether the user is active or not active.
 * Field jenis_ubah on pengguna table, 1 - Active, 0 - Not Active
 *
*/

/*
 * Version: 1.0.2 [ Released Date: 4 June 2014 ]
 * Developer: Mohd Ilhammuddin Bin Mohd Fuead
 * Description/Remarks: 
 * 
 * Added utility methods for setting redirection pages and 
 * utility interface for redirection code.
 * 
 * Utility Method:
 * i.   setAfterLogInPage
 * ii.  setAfterLogoutInPage
 * iii. setNotInSessionPage
 * iv.  setNoAuthorizationForModulPage
 * v.   setInvalidRightRedirectPage
 *
 * Interface for Constant:
 * i    IRedirectCode
*/
?>