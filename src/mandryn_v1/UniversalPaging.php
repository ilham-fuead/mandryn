<?php

class PagingInfo {

    public $pagingType;
    public $totalRow;
    public $totalRowPerPaging;
    public $totalPage;
    public $memTableName;
    public $lastPage;

    public function __construct() {
        $this->pagingType = 0;
        $this->totalRow = 0;
        $this->totalRowPerPaging = 0;
        $this->totalPage = 0;
        $this->memTableName = '';
        $this->lastPage=0;
    }

}

interface IPagingType {

    const AUTO_VIRTUAL = 1;
    const MANUAL = 2;

}

/**
 * An abstract class to facilitate recordset paging. This ABSTRACT class MUST be extended.
 * 
 * Support two type of recordset paging:
 * <ol>
 * <li>Virtual/In-memory/Automatic recursion paging : paging will start from 0 index to last page index.</li>
 * <li>Conventional/Manual setting paging : using page index no as input.</li>
 * </ol> 
 * Support two abstract method implementation:
 * <ol>
 * <li>Method performTaskOnEachPage : will perform customized instruction when each page rendered.</li>
 * <li>Method handleTaskOnNoPaging : will perform customized instruction when no recordset available to page.</li>
 * </ol>
 * 
 * @version 1.0
 * @category Database, Recordset Manipulation
 * @author Mohd Ilhammuddin Bin Mohd Fuead <ilham.fuead@gmail.com> 
 */
abstract class UniversalPaging implements IPagingType {

    private $connectionDetailObj;
    private $pagingInfoObj;
    private $pageDelayInSecond;
    private $sqlStatement;
    protected $mixedDataTypeArray;
    private $useMemoryTable;

    const USE_MEM_ENG = true;
    const USE_DISK_ENG = false;

    public function __construct(DBQuery $DBQueryObj) {
        $this->connectionDetailObj = $DBQueryObj->getConnectionDetail();
        $this->pagingInfoObj = new PagingInfo();
        $this->mixedDataTypeArray = new MixedDataTypeContainer();
        unset($DBQueryObj);
    }

    public function getPagingInfo() {
        $this->initPageProperty();
        return $this->pagingInfoObj;
    }
    
    public function getPagingInfoObj(){
        return $this->pagingInfoObj;
    }

    public function setSQLStatement($sqlStatement) {
       $this->sqlStatement = $sqlStatement;
    }

    public function setUseTmpMemEng() {
        
    }

    public function setPagingProperty($IPagingType, $rowPerPage, $UNIVERSAL_PAGING_USE_MEMORY_TABLE = false) {
        $this->pagingInfoObj->pagingType = $IPagingType;
        $this->pagingInfoObj->totalRowPerPaging = $rowPerPage;
        $this->useMemoryTable = $UNIVERSAL_PAGING_USE_MEMORY_TABLE;
    }

    public function setPagingType($IPagingType) {
        $this->pagingInfoObj->pagingType = $IPagingType;
    }

    public function setRowPerPage($noOfRowsPerPage) {
        $this->pagingInfoObj->totalRowPerPaging = $noOfRowsPerPage;
    }

    private function initPageProperty() {
        $testing = FALSE;
        $conn = $this->connectionDetailObj;

        $DBQueryObj = new DBQuery($conn->host, $conn->username, $conn->password, $conn->database_name);
        
        // TODO: Construct Memory Table
        if ($this->useMemoryTable && $this->pagingInfoObj->memTableName=='') {
            


            $arr = ['i', 'l', 'h', 'a', 'm', '29', '01', '1979'];
            $tblName = 'tbl_mem_' . $arr[rand(0, 7)] . $arr[rand(0, 7)];
            $generate_tbl = false;

            $cmdSetting1 = 'SET GLOBAL tmp_table_size = 40894464 * 4'; //40MB * 4
            $cmdSetting2 = 'SET GLOBAL max_heap_table_size = 16777216 * 4'; //16MB * 4

            if ($this->executeTableLevelCommand($DBQueryObj, $cmdSetting1, 'cmdSetting1 err')) {
                if ($this->executeTableLevelCommand($DBQueryObj, $cmdSetting2, 'cmdSetting2 err')) {
                    $generate_tbl = true;
                }
            }

            if ($generate_tbl) {
                $cmd1 = "CREATE TABLE {$tblName} SELECT * FROM {$this->sqlStatement} AS tbl_used WHERE 1=2;";
                $cmd2 = "ALTER TABLE {$tblName} ENGINE=MEMORY;";
                $cmd3 = "INSERT INTO {$tblName} SELECT * FROM {$this->sqlStatement} AS tbl_used;";                
                
                if ($this->executeTableLevelCommand($DBQueryObj, $cmd1, 'cmd1 err : ' . $cmd1)) {
                    if ($this->executeTableLevelCommand($DBQueryObj, $cmd2, 'cmd2 err')) {
                        if ($this->executeTableLevelCommand($DBQueryObj, $cmd3, 'cmd3 err')) {
                            $this->pagingInfoObj->memTableName = $tblName;
                            $this->sqlStatement="SELECT * FROM {$tblName}";
                        }
                    }
                }
            }
        }

        /*         * TODO: Original return rows count * */
        $DBQueryObj->setSQL_Statement($this->sqlStatement);

        /*         * TODO: Experimental faster return rows count * */
        //$DBQueryObj->setSQL_Statement('SELECT COUNT(*) as totalRows FROM (' . $this->sqlStatement . ') as joined_tbl');

        $DBQueryObj->runSQL_Query();

        $TotalRowsPerSQL = 0;
        $TotalPage = 0;

        $TotalRowPerPage = $this->pagingInfoObj->totalRowPerPaging;

        if (mysqli_num_rows($DBQueryObj->getQueryResult()) > 0) {
            /*             * TODO: Original return rows count * */
            $TotalRowsPerSQL = mysqli_num_rows($DBQueryObj->getQueryResult());

            /*             * TODO: Experimental faster return rows count * */
            //$scalar=mysqli_fetch_assoc($DBQueryObj->getQueryResult());
            //$TotalRowsPerSQL=$scalar['totalRows'];
        }

        $modValue = 0;

        if ($TotalRowsPerSQL > 0) {
            if ($TotalRowsPerSQL > $TotalRowPerPage) {
                $TotalPage = intval($TotalRowsPerSQL / $TotalRowPerPage);
                $modValue = $TotalRowsPerSQL % $TotalRowPerPage;
                if ($modValue != 0) {
                    $TotalPage++;
                }
            } else {
                $TotalPage = 1;
            }
            if ($testing) {
                echo 'Total Record: ' . $TotalRowsPerSQL . '<p>';
                echo 'Total Row/Page: ' . $TotalRowPerPage . '<p>';
                echo 'Total Page: ' . $TotalPage . '<p>';
                echo 'Mod Value(extra field): ' . $modValue . '<p>';
            }
            $this->pagingInfoObj->totalRow = $TotalRowsPerSQL;
            $this->pagingInfoObj->totalPage = $TotalPage;
        } else {
            $this->pagingInfoObj->totalRow = 0;
            $this->pagingInfoObj->totalPage = 0;
        }
        
        unset($DBQueryObj);
    }

    private function executeTableLevelCommand(DBQuery $DBQueryObj, $cmdSql, $customErrorString = 'DB setup failed execution.') {
        $DBQueryObj->setSQL_Statement($cmdSql);
        $DBQueryObj->executeNon_Query();

        if ($DBQueryObj->getCommandStatus()) {
            return true;
        } else {
            throw new Exception($customErrorString);
        }
    }

    public function setPageProperty($obj) {
        $this->pagingInfoObj->totalRow = $obj->totalRow;
        $this->pagingInfoObj->totalPage = $obj->totalPage;
        if(isset($obj->lastPage)){
            $this->pagingInfoObj->lastPage=$obj->lastPage;
        }else{
            $this->pagingInfoObj->lastPage=0;
        }
        if($obj->memTableName!=''){
            $this->pagingInfoObj->memTableName=$obj->memTableName;
            $this->sqlStatement="SELECT * FROM {$obj->memTableName}";
        }
    }

    public function startPaging($setCurrentPage) {
        // Paging Type : 1 auto/virtual || 2 manual
        //$this->initPageProperty();
        if ($this->pagingInfoObj->pagingType == 1) {
            $this->initPageProperty(); //automatic calculation bit slow
            $this->renderPaging(1);
        } else {
            $this->renderPaging($setCurrentPage); //no initPagePropety but setPageProperty
            //$this->renderPagingWithoutPageProperty($setCurrentPage);
        }
    }
    
    private function renderPaging($setCurrentPage) {
        $testing = 0;
        $conn = $this->connectionDetailObj;
        $currentPage = $setCurrentPage;
        $offSetToZeroIndex = 1;
        $TotalPage = $this->pagingInfoObj->totalPage;

        if ($currentPage == 0 || ($currentPage) > $TotalPage) {
            $currentPage = 1;
        }

        $TotalRowPerPage = $this->pagingInfoObj->totalRowPerPaging;

        if ($currentPage != $TotalPage) {
            if ($currentPage == 1) {
                $startRow = ($currentPage - 1) * $TotalRowPerPage;
            } else {
                $startRow = ($currentPage - 1) * $TotalRowPerPage;
            }
            $endRow = ($currentPage * $TotalRowPerPage) - $offSetToZeroIndex;
        } else {
            $startRow = ($currentPage - 1) * $TotalRowPerPage;
            $endRow = $this->pagingInfoObj->totalRow - $offSetToZeroIndex;
        }

        if ($testing == 1) {
            echo 'Current Page: ' . $currentPage . '<p>';
            echo 'start:' . $startRow;
            echo '<br>end:' . $endRow . '<p><p>';
        }

        $DBQueryObj = new DBQuery($conn->host, $conn->username, $conn->password, $conn->database_name);

        $limitRow = $this->pagingInfoObj->totalRowPerPaging;

        if ($currentPage == $TotalPage) {
            $limitRow = $this->pagingInfoObj->totalRow - $startRow;
        }

        $DBQueryObj->setSQL_Statement($this->sqlStatement . " limit $startRow,$limitRow");

        if ($testing == 1) {
            echo 'SQL: ' . $this->sqlStatement . " limit $startRow,$limitRow";
            echo "<p>";
        }

        $DBQueryObj->runSQL_Query();

        if ($testing == 1) {
            echo '<p>Page ' . $currentPage . '<p>';
        }

        $rowCounter = $startRow + 1;

        if (mysqli_num_rows($DBQueryObj->getQueryResult()) > 0) {
            $this->performTaskOnEachPage($DBQueryObj, $startRow, $endRow);
            unset($DBQueryObj);
        } else {
            $this->handleTaskOnNoPaging();
        }
        
        //TODO:Clear memory table
        if ($setCurrentPage == $this->pagingInfoObj->totalPage) {
            if ($this->pagingInfoObj->memTableName !== '') {
                $cmdSql="DROP TABLE {$this->pagingInfoObj->memTableName};";
                $ok=$this->executeTableLevelCommand(new DBQuery($conn->host, $conn->username, $conn->password, $conn->database_name), $cmdSql, 'Error clean up mem engine');
            }
        }
        
        $setCurrentPage+=1;

        if ($setCurrentPage <= $this->pagingInfoObj->totalPage && $this->pagingInfoObj->pagingType == 1) {
            if (isset($this->pageDelayInSecond)) {
                sleep($this->pageDelayInSecond);
            } else {
                sleep(1);
            }

            $this->renderPaging($setCurrentPage);
        }
    }
    
    private function renderPagingWithoutPageProperty($setCurrentPage) {
        
        $conn = $this->connectionDetailObj;
        $currentPage = $setCurrentPage;
        $offSetToZeroIndex = 1;
        
        //BETA
        $TotalPage =100;
        
        $TotalRowPerPage = $this->pagingInfoObj->totalRowPerPaging;

        if ($this->pagingInfoObj->lastPage!=1) {
            if ($currentPage == 1) {
                $startRow = ($currentPage - 1) * $TotalRowPerPage;
            } else {
                $startRow = ($currentPage - 1) * $TotalRowPerPage;
            }
            $endRow = ($currentPage * $TotalRowPerPage) - $offSetToZeroIndex;
        } else {
            $startRow = ($currentPage - 1) * $TotalRowPerPage;
            //$endRow = $this->pagingInfoObj->totalRow - $offSetToZeroIndex;
            $endRow = 0;
        }


        $DBQueryObj = new DBQuery($conn->host, $conn->username, $conn->password, $conn->database_name);

        $limitRow = $this->pagingInfoObj->totalRowPerPaging;

        $DBQueryObj->setSQL_Statement($this->sqlStatement . " limit $startRow,$limitRow");
        
        $DBQueryObj->runSQL_Query();

        $rowCnt=mysqli_num_rows($DBQueryObj->getQueryResult());
        
        if ($rowCnt>0) {
        
            $this->performTaskOnEachPage($DBQueryObj, $startRow, $endRow);
            
            if($rowCnt<$this->pagingInfoObj->totalRowPerPaging){
                $this->pagingInfoObj->lastPage=1;
            }
            /*
            elseif($rowCnt==$this->pagingInfoObj->totalRowPerPaging){
                $startRow=$startRow+$this->pagingInfoObj->totalRowPerPaging;
                $DBQueryObj->setSQL_Statement($this->sqlStatement . " limit $startRow,$limitRow");
                $DBQueryObj->runSQL_Query();
            
                if(!$DBQueryObj->isHavingRecordRow()){
                    $this->pagingInfoObj->lastPage=1;
                }
            }
             * 
             */
            unset($DBQueryObj);
        } else {
            $this->pagingInfoObj->lastPage=1;
            $this->handleTaskOnNoPaging();
        }
        
    }
    
    public function getLastPageStatus(){
        return $this->pagingInfoObj->lastPage;
    }

    public function setPagingDelay($seconds = 0) {
        $this->pageDelayInSecond = $seconds;
    }

    public function storeAdditionalDataToArray($data) {
        $this->mixedDataTypeArray->addMixedDataTypeToArray($data);
    }

    public function getAdditionalDataArray() {
        return $this->mixedDataTypeArray->getMixedDataTypeArray();
    }

    public function getAdditionalDataArrayByIndex($indexNo) {
        return $this->mixedDataTypeArray->getMixedDataTypeValueByIndex($indexNo);
    }

    public function setAdditionalDataArrayByIndex($indexNo, $newValue) {
        return $this->mixedDataTypeArray->setMixedDataTypeValueByIndex($indexNo, $newValue);
    }

    abstract protected function performTaskOnEachPage(DBQuery $DBQueryObj, $startRowIndex, $lastRowIndex);

    abstract protected function handleTaskOnNoPaging();
}

/*
 * Version: 1.1 [ Modified Date: XX XXX XXXX ]
 * Updated By: X
 * Remarks: X
 * Enhancement 1) X             
 *
 * Version: 1.0 [ Released Date: 17 Aug 2011 ]
 * Developer: Mohd Ilhammuddin Bin Mohd Fuead
 * Description/Remarks:
 * An abstract class to facilitate recordset paging. This ABSTRACT class MUST be extended.
 * 
 * Support two type of recordset paging:
 * 1)Virtual/In-memory/Automatic recursion paging : paging will start from 0 index to last page index.
 * 2)Conventional/Manual setting paging : using page index no as input.
 * 
 * Support 2 abstract method implementation:
 * 1)Method performTaskOnEachPage : will perform customized instruction when each page rendered.
 * 2)Method handleTaskOnNoPaging : will perform customized instruction when no recordset available to page.
 */
?>


