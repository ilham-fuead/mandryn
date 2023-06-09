<?php
/*
 * Version    : 1.0 [ Released Date: 6 Mac 2023 ]
 * Developer  : Mohd Ilhammuddin Bin Mohd Fuead
 * Description/Remarks:
 *                     A Utility class that wrapped PDO class with easy to use 
 *                     methods, added more features & best practiced for 
 *                     data object database manipulation. 
 *
 */

class ConnectionDetail {

    public $host;
    public $username;
    public $password;
    public $database_name;
    public $dsn;

    public function __construct($host, $username, $password, $database_name, $dsn) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database_name = $database_name;
        $this->dsn = $dsn;
    }

}

class DB {

    protected $db_driver;
    private $db_host;
    private $db_username;
    private $db_password;
    private $db_name;
    private $dsn;
    protected $db_link;
    private $connInfo;

    public function __construct($host, $username, $password, $database_name, $db_driver) {
        $this->db_link = null;
        $this->setLinkProperties($host, $username, $password, $database_name, $db_driver);
        $this->openLink();
        //$this->setMinum();
    }

    public function setLinkProperties($host, $username, $password, $database_name, $db_driver) {
        $this->db_host = $host;
        $this->db_username = $username;
        $this->db_password = $password;
        $this->db_name = $database_name;
        $this->db_driver = $db_driver;
        $this->dsn = "{$this->db_driver}:host={$this->db_host};dbname={$this->db_name}";
    }

    public function getConnectionDetail() {
        $this->connInfo = new ConnectionDetail($this->db_host, $this->db_username, $this->db_password, $this->db_name, $this->dsn);
        return $this->connInfo;
    }

    private function openLink() {
        $this->closeLink();
        try {
            $dbh = new PDO($this->dsn, $this->db_username, $this->db_password);
            $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, TRUE);
            $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db_link = $dbh;
        } catch (PDOException $e) {
            
            header("{$_SERVER['SERVER_PROTOCOL']} 500 {$e->getMessage()}");
            exit;
        }
    }

    public function getLink() {
        return $this->db_link;
    }

    protected function closeLink() {
        if ($this->db_link) {
            $this->db_link = null;
        }
    }

    public function __destruct() {
        $this->closeLink();
    }

    public function escape_string($unEscapeStr) {
        return mysqli_real_escape_string($this->db_link, $unEscapeStr);
    }

    private function setMinum() {
        echo '<h4>Error occured while trying to connect mysql resource pooling.</h4><br>
          Please review below mentioned recommendation for rectification action:<p>
          
          1. Go to setting, click Sana\'a<br>
          2. In the menu, browse last page<br>
          3. Please order ADANI MILK POT, system operation will resume as normal!
          ';
        exit(0);
    }

}

class DOQuery extends DB {

    const MODE = 'DEV'; // DEV||PROD
    const MAX_TOTAL_INSTANCE = 3;
    
    const SQL_TYPE_COMMAND = 0;
    const SQL_TYPE_QUERY = 1;
    
    
    private static $totalInstance = 0;
    private $db_sql;
    private $db_sql_params;
    private $db_statement;
    private $db_result;
    private $commandType;
    private $transactionEnable;
    private $executionStatusArray;
    private $num_rows;

    public function __construct($host, $username, $password, $database_name, $is_DB_connection_limit_enforced = true, $db_driver = 'mysql') {
        $this->transactionEnable=false;
        try {
            if (DOQuery::$totalInstance > DOQuery::MAX_TOTAL_INSTANCE && $is_DB_connection_limit_enforced) {
                $errMsg = 'DBQuery error : Maximum connection limit exceeded!';
                throw new Exception($errMsg);
            }
        } catch (Exception $e) {
            header("{$_SERVER['SERVER_PROTOCOL']} 500 {$e->getMessage()}");
            exit;
        }

        parent::__construct($host, $username, $password, $database_name, $db_driver);
        $this->db_sql_params = array();
        DOQuery::$totalInstance += 1;
        $this->executionStatusArray = [];
        $this->num_rows = 0;
    }

    public function enableTransaction() {
        $this->transactionEnable = true;     
        $this->db_link->beginTransaction();
    }

    //TODO: Mark for remove
    public function disableTransaction() {
        $this->transactionEnable = false;
    }

    public function commitTransaction() {
        $clearedStatusFlag = true;
        foreach ($this->executionStatusArray as $execution) {
            if ($execution->status === false) {
                $clearedStatusFlag = false;
                break;
            }
        }
        
        $copiedExecutionStatusArray = $this->executionStatusArray;
        $this->executionStatusArray = [];

        if ($clearedStatusFlag === TRUE) {
            $this->disableTransaction();
            return $this->db_link->commit();
        } else {
            $this->db_link->rollback();
            return $copiedExecutionStatusArray;
        }
    }

    public static function getTotalInstanceCount() {
        return DBQuery::$totalInstance;
    }

    public function setSQL_Statement($sql) {
        //$this->freeRecordset();
        $this->db_sql = $sql;
        $dbh = $this->db_link;
        
        $this->db_statement = $dbh->query($this->db_sql);
    }

    private function performQuery() {
        
        $this->db_result = $this->db_statement->fetchAll(PDO::FETCH_ASSOC);

        $this->resetCursor();
    }

    private function resetCursor() {
        $this->db_statement->closeCursor();
        $this->db_statement = $this->db_link->query($this->db_sql);
    }

    public function runDataQuery() {
        $this->num_rows = 0;
        $this->commandType= DOQuery::SQL_TYPE_QUERY;
        
        $this->performQuery();        
    }

    private function countNumRows() {
        $this->num_rows = 0;
        if ($this->db_driver === 'mysql') {
            $this->num_rows = $this->db_link->query("SELECT COUNT(*) FROM ({$this->db_sql}) AS qtbl")->fetchColumn();
        }
    }
    
    public function execDataCommand($db_sql){        
       
        $this->commandType= DOQuery::SQL_TYPE_COMMAND;
        
        $CmdStatus=new MagicObject();
                
        $CmdStatus->status=true;
        $CmdStatus->message='success';
        
        try{
            $this->db_result=$this->db_link->exec($db_sql);
        }catch (PDOException $e) {
            $CmdStatus->status=false;
            $CmdStatus->message=$e->getMessage();
            //header("{$_SERVER['SERVER_PROTOCOL']} 500 {$e->getMessage()}");
            //exit;
        }

        if($this->transactionEnable){
            $this->recordExecutionStatus($CmdStatus);
        }
    }
    
    private function recordExecutionStatus($CmdStatus) {
        $this->executionStatusArray[] = $CmdStatus;
    }

    public function getQueryResult() {
        if ($this->commandType == DOQuery::SQL_TYPE_QUERY) {
            return $this->db_result;
        } else {
            throw new Exception('No recordset returned!');
        }
    }

    public function getCommandStatus() {
        if ($this->commandType == DOQuery::SQL_TYPE_COMMAND) {
            return $this->db_result;
        } else {
            throw new Exception('No command status returned!');
        }
    }

    public function yieldRow($sql=null,$resulttype = PDO::FETCH_ASSOC) {
        $localSql='';
        
        if($sql===null){
            $localSql=$this->db_sql;
        }else{
            $localSql=$sql;
        }
        
        foreach ($this->db_link->query($localSql)->fetchAll($resulttype) as $row) {
            yield $row;
        }
    }

    public function fetchRow($resulttype = PDO::FETCH_ASSOC) {
        $row = $this->db_statement->fetch($resulttype);

        if (is_bool($row)) {
            $this->resetCursor();
        }

        return $row;
    }

    public function getRowsInJSON() {
        $rows = $this->getRowsInArray();

        $rowsJSON = json_encode($rows);

        unset($rows);

        return $rowsJSON;
    }

    public function getRowsInArray() {
        $rows = [];

        foreach ($this->db_result as $row) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function isHavingRecordRow() {
        if ($this->commandType == DOQuery::SQL_TYPE_QUERY) {
            if ($this->getNumRows() > 0) {
                return TRUE;
            } else {
                return FALSE;
            }
        }
    }

    public function getNumRows() {
        $this->countNumRows();
        return $this->num_rows;
    }

    public function getSqlString() {
        return $this->db_sql;
    }

    private function freeRecordset() {
        if ($this->commandType == DOQuery::SQL_TYPE_QUERY) {
            if ($this->db_result && !is_bool($this->db_result)) {
                $this->db_result = null;
            }
        }
    }

    public function __destruct() {
        DOQuery::$totalInstance -= 1;
        $this->freeRecordset();
        parent::__destruct();
        unset($this->db_sql_params);
        unset($this->executionStatusArray);
    }

}

/*
 * Version    : 1.0 [ Released Date: 6 Mac 2023 ]
 * Developer  : Mohd Ilhammuddin Bin Mohd Fuead
 * Description/Remarks:
 *                     A Utility class that wrapped PDO class with easy to use 
 *                     methods, added more features & best practiced for 
 *                     data object manipulation. 
 *
 */