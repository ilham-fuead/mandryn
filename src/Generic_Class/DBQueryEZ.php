<?php

include_once 'DBQuery.php';

class DBQueryEZ extends DB {

    const DEV_MODE = true;

    private $db_result;
    private $recordset;
    private $sqlStr;
    public $rows_length = 0;
    public $insert_id = 0;

    public function __construct($host, $username, $password, $database_name) {
        parent::__construct($host, $username, $password, $database_name);
        $this->recordset = array();
    }

    public function querySQL($sqlStr) {
        $this->sqlStr = $sqlStr;
        $this->recordset = array();

        $this->db_result = mysqli_query($this->db_link, $sqlStr);
        if (is_bool($this->db_result)) {
            $this->errorStatus(self::DEV_MODE);
        } else {
            if ($this->db_result) {
                $this->set_rows_length();
                while ($row = mysqli_fetch_assoc($this->db_result)) {
                    $this->recordset[] = $row;
                }
            }
            mysqli_free_result($this->db_result);
        }
        return ($this->recordset);
    }

    public function commandSQL($sqlStr) {
        $this->sqlStr = $sqlStr;
        $cmdStatus = FALSE;
        $cmdStatus = mysqli_query($this->db_link, $sqlStr);
        if (!$cmdStatus) {
            $this->errorStatus(self::DEV_MODE);
        }
        $this->insert_id = mysqli_insert_id($this->db_link);
        return $cmdStatus;
    }

    private function set_rows_length() {
        $this->rows_length = mysqli_num_rows($this->db_result);
    }

    public function __destruct() {
        parent::__destruct();
        unset($this->recordset);
    }

    private function errorStatus($devMode = FALSE) {
        echo '<p><b>Error</b>: ' . mysqli_error($this->db_link) . "\n";
        if ($devMode) {
            echo '<br><b>SQL</b>: ' . $this->sqlStr;
        }
    }

}

/*
 * Version    : 1.0 [ Released Date: 10 June 2015 ]
 * Developer  : Mohd Ilhammuddin Bin Mohd Fuead
 * Description/Remarks: Utility class for communicating with DB via SQL query 
 *                      i.  Method querySQL for sending SQL expecting recordset return.
 *                      ii. Method commandSQL for sending SQL executing insert, update & delete operation.
 */
?>
