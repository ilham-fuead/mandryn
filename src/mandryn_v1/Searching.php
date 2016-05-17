<?php
    class Searching
    {
        private $searchingFound;
        private $dbQueryObj;
        private $searchResultRow;
        public $sqlSelect;
        public $sqlFrom;
        public $sqlWhere;
        public $sqlOrder;
        public $sql;

        public function  __construct()
        {
            $this->searchingFound = FALSE;
            $this->sqlSelect = "SELECT ";
            $this->sqlFrom = "FROM ";
            $this->sqlWhere = "WHERE ";
            $this->sqlOrder = "ORDER BY ";
        }

        public function setDataSource( DBQuery $myDBQuery )
        {
            $this->dbQueryObj = $myDBQuery;
        }

        public function addSearchColumn( $selectedColumn, $numOfCols )
        {
            if( $numOfCols == "A" )
            {
                $this->sqlSelect = $this->sqlSelect . "* ";
            }
            else if( $numOfCols == "M" )
            {
                $this->sqlSelect = $this->sqlSelect . $selectedColumn . ",";
            }
            else if( $numOfCols == "L" )
            {
                $this->sqlSelect = $this->sqlSelect . $selectedColumn . " ";
            }
        }

        public function setSearchTable( $searchingTable )
        {
            $this->sqlFrom = $this->sqlFrom . " " . $searchingTable . " ";
        }

        public function addSearchCondition( $conditionColumn, $findingNemo, $numOfCons )
        {
            if( $numOfCons == "A" )
            {
                $this->sqlWhere = $this->sqlWhere . $conditionColumn . " LIKE'%" . $findingNemo . "%' ";
            }
            else if( $numOfCons == "M" )
            {
                $this->sqlWhere = $this->sqlWhere . $conditionColumn . " LIKE'%" . $findingNemo . "%' OR ";
            }
            else if( $numOfCons == "L" )
            {
                $this->sqlWhere = $this->sqlWhere . $conditionColumn . " LIKE'%" . $findingNemo . "%' ";
            }
        }

        public function setOrder( $orderColumn, $searchingOrder )
        {
            $this->sqlOrder = $this->sqlOrder . $orderColumn . " " . $searchingOrder;
        }

        private function displaySQL()
        {
            echo $this->sqlSelect . "<br>";
            echo $this->sqlFrom . "<br>";
            echo $this->sqlWhere . "<br>";
            echo $this->sqlOrder . "<br>";
        }

        public function find( )
        {
            $this->sql = $this->sqlSelect . $this->sqlFrom . $this->sqlWhere . $this->sqlOrder;

            $this->dbQueryObj->setSQL_Statement( $this->sql );
            $this->dbQueryObj->runSQL_Query();

            if( mysqli_num_rows( $this->dbQueryObj->getQueryResult() ) > 0 )
            {
                $this->searchResultRow = $this->dbQueryObj->getQueryResult();
                $this->setFound( );
            }
            else
            {
                $this->searchingFound = FALSE;
            }
        }

        public function getSQL()
        {
            return $this->sql;
        }

        public function getSearchResult()
        {
            return $this->searchResultRow;
        }

        private function setFound( )
        {
            $this->searchingFound = TRUE;
        }

        public function isFound()
        {
            return $this->searchingFound;
        }
    }
?>