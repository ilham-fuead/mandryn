<?php

/**
 * Created by PhpStorm.
 * User: Mohd Ilhammuddin Bin Mohd Fuead
 * Date: 28/9/2016
 * Time: 6:55 PM
 */
interface IRedirectType
{
    const AFTER_LOGIN_TYPE = "in";
    const AFTER_LOGOUT_TYPE = "out";
    const MISSING_SESSION_TYPE = "lost";
    const RESTRICT_ACCESS_TYPE = "restrict";
    const RESTRICT_RIGHT_ACCESS_TYPE = "noRight";
    const DEVELOPMENT_MODE = "debug";
}

interface ISecurityLevel
{
    CONST SESSION_ROLE_RIGHT = 1; //highest level
    CONST SESSION_AND_ROLE = 2;
    CONST SESSION_ONLY = 3;
    CONST DEVELOPMENT_NO_SECURITY = 4;
}

interface IRightLevel
{
    CONST ADM_LVL_1 = 0;
    CONST ADM_LVL_2 = 1;
    CONST ADM_LVL_3 = 2;
    CONST USR_ODR = 3;
}

interface IActionType
{
    CONST STOP_APP = 1;
    CONST GET_CLEARANCE_STATUS = 2;
    CONST REDIRECT_NO_RIGHT = 3;
}

interface IRedirectCode
{
    CONST SUCCESS_LOGIN = 600;
    CONST FAILED_LOGIN = 610;
    CONST SUCCESS_LOGOUT = 621;
    CONST INVALID_SESSION = 630;
    CONST INVALID_MODULE_ROLE = 640;
    CONST INVALID_MODULE_RIGHT = 650;
}

interface IAuthenticationAction
{
    const REDIRECT = 1;
    const SET_HTTP_RESPONSE_HEADER = 2;
}

abstract class Login implements IRedirectType, ISecurityLevel, IRightLevel, IActionType, IRedirectCode, IAuthenticationAction
{
    private $userName;
    private $nama;
    private $emel;
    private $unit;
    private $jawatan;
    private $userDetails;
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
    protected $httpResponseAction;

    public function __construct(DBQuery $DBQueryObj, $httpResponseAction = IAuthenticationAction::REDIRECT)
    {
        $this->dbQueryObj = $DBQueryObj;
        $this->authenticationStatus = FALSE;
        $this->afterLogOutPage = "none";
        $this->afterLogInPage = "none";
        $this->notInSessionPage = "none";
        $this->noAuthorizationForModulPage = "none";
        $this->invalidRightRedirectPage = "none";
        $this->byRightAuthorization = FALSE;
        $this->httpResponseAction = $httpResponseAction;
        $this->userDetails = [];
        session_start();
    }

    public function setDataSource($userTableName, $idFieldName, $pwdFieldName)
    {

        $this->tableName = $userTableName;
        $this->idField = $idFieldName;
        $this->pwdField = $pwdFieldName;

    }

    public function executeLogin($uName, $uPassword)
    {
        if ($this->isAuthenticated($uName, $uPassword)) {
            $this->setAuthenticate($uName);
            if ($this->httpResponseAction == IAuthenticationAction::REDIRECT) {
                $this->redirect("in");
            } else if ($this->httpResponseAction == IAuthenticationAction::SET_HTTP_RESPONSE_HEADER) {
                header("{$_SERVER['SERVER_PROTOCOL']} 200 OK");
                exit;
            }
        } else {
            header("{$_SERVER['SERVER_PROTOCOL']} 401 Unauthorized");
            exit;
        }
    }

    private function setAuthenticate($uName)
    {
        $this->userName = $uName;
        $this->authenticationStatus = TRUE;
        $_SESSION['IDPengguna'] = $this->userName;
    }

    public function getLogInStatus()
    {
        //return $this->authenticationStatus;
        if (isset($_SESSION['IDPengguna'])) {
            if ($this->httpResponseAction === IAuthenticationAction::SET_HTTP_RESPONSE_HEADER) {
                header("{$_SERVER['SERVER_PROTOCOL']} 200 OK");
                exit;
            } else {
                return TRUE;
            }
        } else {
            if ($this->httpResponseAction === IAuthenticationAction::SET_HTTP_RESPONSE_HEADER) {
                header("{$_SERVER['SERVER_PROTOCOL']} 401 Unauthorized");
                exit;
            } else {
                return FALSE;
            }
        }
    }

    /**
     * @return bool
     */
    protected function getInSessionStatus()
    {
        if (isset($_SESSION['IDPengguna'])) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function getUserPageAuthorization($uName, $pageID)
    {
        $sql = "SELECT AccessRolesID,AccessRightID
                    FROM AccessRight
                    WHERE IDpengguna = '$uName'
                    AND AccessRolesID = '$pageID'";

        $this->dbQueryObj->setSQL_Statement($sql);
//        echo $sql;
//        exit(0);
        $this->dbQueryObj->runSQL_Query();

        if (mysqli_num_rows($this->dbQueryObj->getQueryResult()) > 0) {
            $row = mysqli_fetch_assoc($this->dbQueryObj->getQueryResult());
            $this->initializeValidPageAuthorization($uName, $row['AccessRolesID'], $row['AccessRightID']);
            return TRUE;
        } else {
            return FALSE;
        }

    }

    private function initializeValidPageAuthorization($userName, $authorisedPageID, $authorisedPageRight)
    {
        $_SESSION['authorisedPageID'] = $authorisedPageID;
        $_SESSION['authorisedPageRight'] = $authorisedPageRight;
    }

    protected function addUserDetail($key, $value)
    {
        $this->userDetails[$key] = $value;
    }

    protected function sessionizeUserDetails(){
        foreach ($this->userDetails as $key=>$value){
            $_SESSION[$key]=$value;
        }
    }

    public function getIDpengguna()
    {
        return $this->userName;
    }

    public function getNamaPengguna()
    {
        return $this->nama;
    }

    public function getJawatanPengguna()
    {
        return $this->jawatan;
    }

    public function getUnitPengguna()
    {
        return $this->unit;
    }

    public function logout()
    {
        $this->authenticationStatus = FALSE;

        if ($this->getInSessionStatus() == TRUE) {
            session_regenerate_id(true);
            session_unset();
            session_destroy();
            session_write_close();
            setcookie(session_name(), '', 0, '/');
            $this->setRedirectFiles();
        }
        if ($this->httpResponseAction === IAuthenticationAction::REDIRECT) {
            $this->redirect("out");
        }

    }

    public function redirect($IRedirectType)
    {
        $this->setRedirectFiles();
        $pathFile = "";

        if ($this->afterLogOutPage != "none" && $IRedirectType == "out")
            header("Location:$this->afterLogOutPage");

        if ($this->afterLogInPage != "none" && $IRedirectType == "in")
            header("Location:$this->afterLogInPage");

        if ($this->notInSessionPage != "none" && $IRedirectType == "lost")
            header("Location:$this->notInSessionPage");

        if ($this->noAuthorizationForModulPage != "none" && $IRedirectType == "restrict")
            header("Location:$this->noAuthorizationForModulPage");

        if ($IRedirectType == "noRight") {
            if ($this->invalidRightRedirectPage == "none")
                $pathFile = $this->noAuthorizationForModulPage;
            else
                $pathFile = $this->invalidRightRedirectPage;
            header("Location:$pathFile");
        }
    }

    private function securingBySession()
    {
        if ($this->getInSessionStatus() == FALSE) {
            if($this->httpResponseAction == IAuthenticationAction::REDIRECT){
                $this->redirect(IRedirectType::MISSING_SESSION_TYPE);
            }else if($this->httpResponseAction == IAuthenticationAction::SET_HTTP_RESPONSE_HEADER){
                header("{$_SERVER['SERVER_PROTOCOL']} 401 Unauthorized");exit;
            }
        }
        return TRUE;
    }

    private function securingByRoles($pageID)
    {
        if ($this->getUserPageAuthorization($this->getIDpengguna(), $pageID) == FALSE) {
            if($this->httpResponseAction == IAuthenticationAction::REDIRECT){
                $this->redirect(IRedirectType::RESTRICT_ACCESS_TYPE);
            }else if($this->httpResponseAction == IAuthenticationAction::SET_HTTP_RESPONSE_HEADER){
                header("{$_SERVER['SERVER_PROTOCOL']} 403 Forbidden");exit;
            }
        }
        return TRUE;
    }

    private function securingByRight()
    {
        $this->byRightAuthorization = TRUE;
    }

    public function securingPage($pageID, $ISecurityLevel)
    {
        if ($ISecurityLevel <= 3) {
            if ($this->securingBySession() == TRUE) {
                if ($ISecurityLevel <= 2) {
                    if ($this->securingByRoles($pageID) == TRUE) {
                        if ($ISecurityLevel == 1) {
                            $this->securingByRight();
                        }
                    }
                }

            }

        }
    }

    private function getRightStatus($pageRight)
    {
        if ($_SESSION['authorisedPageRight'] <= $pageRight) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function imposeRightOnPage($pageRight, $IActionType)
    {
        if ($this->byRightAuthorization == TRUE) {
            $pageRightStatus = $this->getRightStatus($pageRight);
            if ($IActionType == 1) {
                exit(0);
            } else if ($IActionType == 2) {
                return $pageRightStatus;
            } else if ($IActionType == 3) {
                if ($pageRightStatus == FALSE)
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
    protected function setAfterLogInPage($filePath)
    {
        $this->afterLogInPage = $filePath;
    }

    protected function setAfterLogOutPage($filePath)
    {
        $this->afterLogOutPage = $filePath . '?status=' . IRedirectCode::SUCCESS_LOGOUT;
    }

    protected function setNotInSessionPage($filePath)
    {
        $this->notInSessionPage = $filePath . '?status=' . IRedirectCode::INVALID_SESSION;
    }

    protected function setNoAuthorizationForModulPage($filePath)
    {
        $this->noAuthorizationForModulPage = $filePath . '?status=' . IRedirectCode::INVALID_MODULE_ROLE;
    }

    protected function setInvalidRightRedirectPage($filePath)
    {
        $this->invalidRightRedirectPage = $filePath . '?status=' . IRedirectCode::INVALID_MODULE_RIGHT;
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

    abstract protected function isAuthenticated($usename, $password);

    abstract public function loadUserDetail($uName);

}