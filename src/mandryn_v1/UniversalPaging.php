<?php

class PagingInfo {

    public $pagingType;
    public $totalRow;
    public $totalRowPerPaging;
    public $totalPage;
    
    public function __construct() {
        $this->pagingType=0;
        $this->totalRow=0;
        $this->totalRowPerPaging=0;
        $this->totalPage=0;
    }

}

interface IPagingType {
    const AUTO_VIRTUAL=1;
    const MANUAL=2;
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
 * @copyright Copyright(c) 2011, e-SILA Team 2011, SD, BPM 
 */
abstract class UniversalPaging implements IPagingType {

    private $connectionDetailObj;
    private $pagingInfoObj;
    private $pageDelayInSecond;
    private $sqlStatement;
    protected $mixedDataTypeArray;

    public function __construct(DBQuery $DBQueryObj) {
        $this->connectionDetailObj = $DBQueryObj->getConnectionDetail();
        $this->pagingInfoObj = new PagingInfo();
        $this->mixedDataTypeArray = new MixedDataTypeContainer();
        unset($DBQueryObj);
    }
    
    public function getPagingInfo(){
        $this->initPageProperty();
        return $this->pagingInfoObj;
    }

    public function setSQLStatement($sqlStatement) {
        $this->sqlStatement = $sqlStatement;
    }
    
    public function setUseTmpMemEng(){
        
    }

    public function setPagingProperty($IPagingType, $rowPerPage) {
        $this->pagingInfoObj->pagingType = $IPagingType;
        $this->pagingInfoObj->totalRowPerPaging = $rowPerPage;
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
        $DBQueryObj->setSQL_Statement($this->sqlStatement);
        $DBQueryObj->runSQL_Query();

        $TotalRowsPerSQL = 0;
        $TotalPage = 0;

        $TotalRowPerPage = $this->pagingInfoObj->totalRowPerPaging;

        if (mysqli_num_rows($DBQueryObj->getQueryResult()) > 0) {
            $TotalRowsPerSQL = mysqli_num_rows($DBQueryObj->getQueryResult());
        }

        $modValue = 0;

        if ($TotalRowsPerSQL > 0) {
            if ($TotalRowsPerSQL > $TotalRowPerPage) {
                $TotalPage = intval($TotalRowsPerSQL / $TotalRowPerPage);
                $modValue = $TotalRowsPerSQL % $TotalRowPerPage;
                if ($modValue != 0) {
                    $TotalPage++;
                }
            }else{
                $TotalPage=1;
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

    public function setPageProperty($obj){
        $this->pagingInfoObj->totalRow = $obj->totalRow;
        $this->pagingInfoObj->totalPage = $obj->totalPage;
    }

    public function startPaging($setCurrentPage) {
        // Paging Type : 1 auto/virtual || 2 manual
        //$this->initPageProperty();
        if ($this->pagingInfoObj->pagingType == 1){
            $this->initPageProperty(); //automatic calculation bit slow
            $this->renderPaging(1);
        }else{            
            $this->renderPaging($setCurrentPage); //no initPagePropety but setPageProperty
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

        $setCurrentPage+=1;        

        if ($setCurrentPage <= $this->pagingInfoObj->totalPage && $this->pagingInfoObj->pagingType == 1) {
            if (isset($this->pageDelayInSecond)){
                sleep($this->pageDelayInSecond);
            }else{
                sleep(1);
            }
            
            $this->renderPaging($setCurrentPage);
        }
    }

    public function setPagingDelay($seconds=0) {
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


