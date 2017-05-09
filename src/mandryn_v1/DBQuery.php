<?php

class ConnectionDetail
{

    public $host;
    public $username;
    public $password;
    public $database_name;

    public function __construct($host, $username, $password, $database_name)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database_name = $database_name;
    }

}

class DB
{

    private $db_host;
    private $db_username;
    private $db_password;
    private $db_name;
    protected $db_link;
    private $connInfo;

    public function __construct($host, $username, $password, $database_name)
    {
        $this->setLinkProperties($host, $username, $password, $database_name);
        $this->openLink();
        //$this->setMinum();
    }

    public function setLinkProperties($host, $username, $password, $database_name)
    {
        $this->db_host = $host;
        $this->db_username = $username;
        $this->db_password = $password;
        $this->db_name = $database_name;
    }

    public function getConnectionDetail()
    {
        $this->connInfo = new ConnectionDetail($this->db_host, $this->db_username, $this->db_password, $this->db_name);
        return $this->connInfo;
    }

    private function openLink()
    {
        $this->closeLink();
        try {
            if (!($this->db_link = mysqli_connect($this->db_host, $this->db_username, $this->db_password, $this->db_name))) {
                throw new Exception("Connection Error : " . mysqli_connect_errno() . " - " . mysqli_connect_error());
            }
        } catch (Exception $e) {
            header("{$_SERVER['SERVER_PROTOCOL']} 500 {$e->getMessage()}");
            exit;
            //die($e->getMessage());
        }
    }

    public function getLink()
    {
        return $this->db_link;
    }

    private function closeLink()
    {
        if ($this->db_link)
            mysqli_close($this->db_link);
    }

    public function __destruct()
    {
        $this->closeLink();
    }

    private function setMinum()
    {
        echo '<h4>Error occured while trying to connect mysql resource pooling.</h4><br>
          Please review below mentioned recommendation for rectification action:<p>
          
          1. Go to setting, click Sana\'a<br>
          2. In the menu, browse last page<br>
          3. Please order ADANI MILK POT, system operation will resume as normal!
          ';
        exit(0);
    }

}

class DBQuery extends DB
{
    const MODE = 'DEV'; // DEV||PROD
    const MAX_TOTAL_INSTANCE=5;
    
    private static $totalInstance=0;

    private $db_sql;
    private $db_sql_params;
    private $db_result;
    private $commandType;
    
    public function __construct($host, $username, $password, $database_name, $limitTotalInstance=true)
    {        
        try{
            if(DBQuery::$totalInstance>=DBQuery::MAX_TOTAL_INSTANCE && $limitTotalInstance){
                $errMsg = 'DBQuery error : Maximum connection limit exceeded!';
                throw new Exception($errMsg);
            }
        } catch (Exception $e) {
            header("{$_SERVER['SERVER_PROTOCOL']} 500 {$e->getMessage()}");
            exit;
        }
        
        parent::__construct($host, $username, $password, $database_name);
        $this->db_sql_params = array();
        DBQuery::$totalInstance+=1;
    }
    
    public function setSQL_Statement($sql)
    {
        $this->freeRecordset();
        $this->db_sql = $sql;
    }

    private function performQuery()
    {
        $this->db_result = mysqli_query($this->db_link, $this->db_sql);
    }

    public function runSQL_Query()
    {
        $this->performQuery();
        try {
            if (is_bool($this->db_result)) {
                $this->commandType = "non query";
                if (DBQuery::MODE == 'DEV' && $this->db_result === FALSE) {
                    $errMsg = 'Database error: ' . mysqli_error($this->db_link);
                    throw new Exception($errMsg);
                } else if ($this->db_result === FALSE) {
                    $errMsg = 'Database error';
                    throw new Exception($errMsg);
                } else {
                    $this->db_result = mysqli_query($this->db_link, $this->db_sql);
                }
            } else {
                $this->commandType = "query";
            }
        }catch (Exception $e) {
            header("{$_SERVER['SERVER_PROTOCOL']} 500 {$e->getMessage()}");
            exit;
        }
    }

    public function executeNon_Query()
    {
        $this->performQuery();
        $this->commandType = "non query";
    }

    public function getQueryResult()
    {
        if ($this->commandType == "query") {
            return $this->db_result;
        } else {
            throw new Exception('No recordset returned!');
        }
    }

    public function getCommandStatus()
    {
        if ($this->commandType == "non query") {
            return $this->db_result;
        } else {
            throw new Exception('No command status returned, instead a recordset is returned');
        }
    }

    public function yieldRow($resulttype = MYSQLI_ASSOC)
    {
        while ($row = mysqli_fetch_array($this->db_result, $resulttype)) {
            yield $row;
        }
    }

    public function fetchRow($resulttype = MYSQLI_ASSOC)
    {
        return mysqli_fetch_array($this->db_result, $resulttype);
    }

    public function getRowsInJSON()
    {
        $rows = [];
        foreach ($this->yieldRow() as $row) {
            $rows[] = $row;
        }
        $rowsJSON = json_encode($rows);
        unset($rows);
        return $rowsJSON;
    }

    public function isHavingRecordRow()
    {
        if ($this->commandType == "query") {
            if (mysqli_num_rows($this->db_result) > 0) {
                return TRUE;
            } else {
                return FALSE;
            }
        }
    }

    public function getSqlString()
    {
        return $this->db_sql;
    }

    private function freeRecordset()
    {
        if ($this->commandType == "query") {
            if ($this->db_result && !is_bool($this->db_result)) {
                mysqli_free_result($this->db_result);
            }
        }
    }

    public function __destruct()
    {
        DBQuery::$totalInstance-=1;
        $this->freeRecordset();
        parent::__destruct();
        unset($this->db_sql_params);
    }

}

/*
 * 
 * 
 * Version    : 1.3 [ Released Date: 10 June 2015 ]
 * Updated By : Mohd Ilhammuddin Bin Mohd Fuead
 * Remarks    : Added Error handling(Try Catch Exception) statement 
 *              when executing mysqli_connect and display error accordingly        
 *
 * Version    : 1.2 [ Released Date: 08 June 2012 ]
 * Updated By : Mohd Ilhammuddin Bin Mohd Fuead
 * Remarks    : i)  Solved bug on freeing a non return resultset query.
 *              ii) Set generic error regarding mysqli error due to incorrect SQL
 *
 * Version    : 1.1 [ Released Date: 27 Oct 2011 ]
 * Updated By : Mohd Ilhammuddin Bin Mohd Fuead
 * Remarks    : Added new helper method (freeRecordset) for better memory management
 *              where returned and used recordset will immediately free from memory 
 *              when new SQL statement set to DBQuery object sql property.
 *
 * Version    : 1.0 [ Released Date: 5 May 2011 ]
 * Developer  : Mohd Ilhammuddin Bin Mohd Fuead
 * Description/Remarks:
 *                     A Utility class for constructing valid SQL statement to
 *                     be consume by other main classes. This version support all
 *                     standard SQL Command with limitation on single table only operation.
 *
 */
?>