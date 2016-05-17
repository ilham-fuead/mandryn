<?php
    class Pagination
    {
        public $totalPage;
        public $totalRecord;
        public $recordPerPage;
        public $floorNumber;
        public $ceilingNumber;
        public $modulus;

        public $setOfLink;
        public $currentLink;
        public $spreadLink;
        public $startLink;
        public $endLink;
        public $linking;
        public $linkOfWord;

        private $myDBQuery;
        private $mySQLQuery;

        public function  __construct()
        {
            $this->mySQLQuery = new SQLQuery();
        }

        public function setDataSource( DBQuery $dbObject )
        {
            $this->myDBQuery = $dbObject;
        }

        public function setFloorCeiling( $flo,$ceil )
        {
            $this->floorNumber = $flo;
            $this->ceilingNumber = $ceil;
        }

        public function setRecordPerPage( $recordPerPage )
        {
            $this->recordPerPage = $recordPerPage;
        }

        private function goToFirstPage()
        {
            $this->floorNumber = 0;
            $this->ceilingNumber = $this->recordPerPage - 1;
        }

        private function goToLastPage()
        {
            $this->floorNumber = ( $this->totalPage - 1 ) * $this->recordPerPage;
            $this->ceilingNumber = $this->floorNumber + ( $this->recordPerPage - 1 );
        }

        private function goToBeforeCurrentPage()
        {
            $this->floorNumber = $this->floorNumber - $this->recordPerPage;
            $this->ceilingNumber = $this->ceilingNumber - $this->recordPerPage;
        }

        private function goToNextCurrentPage()
        {
            $this->floorNumber = $this->ceilingNumber + 1;
            $this->ceilingNumber = $this->recordPerPage + $this->ceilingNumber;
            
        }

        private function goToNumberPage( $LP )
        {
            $this->ceilingNumber = ( $LP * $this->recordPerPage ) - 1;
            $this->floorNumber = $this->ceilingNumber - $this->recordPerPage + 1;
        }

        public function countPages()
        {
            $this->totalPage = $this->totalRecord / $this->recordPerPage;
            $this->modulus = $this->totalRecord % $this->recordPerPage;
            if( $this->modulus > 0 )
            {
                $this->totalPage = intval( $this->totalPage );
                $this->totalPage = $this->totalPage + 1;
            }
        }

        public function countRecords( $records )
        {
            $this->totalRecord = mysqli_num_rows( $records );
        }

        public function generateRecords( $SQL )
        {
            $this->myDBQuery->setSQL_Statement( $SQL ); //$this->mySQLQuery->getSQLQuery() . " LIMIT " . $this->getFloorNumber() . "," . $this->getRecordPerPage()
            $this->myDBQuery->runSQL_Query();
        }

        public function setViewLinkPage( $setOfLink )
        {
            $this->setOfLink = $setOfLink;
        }

        public function setCurrentPage( $LP )
        {
            if( is_int( $LP ) )
                $this->currentLink = $LP;
            else
                $this->currentLink = ( $this->ceilingNumber + 1 ) / $this->recordPerPage;
        }

        public function setSpreadPage()
        {
            $this->spreadLink = $this->setOfLink / 2;
            $modSpread = $this->setOfLink % 2;
            if( $modSpread > 0 )
            {
                $this->spreadLink = intval( $this->spreadLink );
                $this->spreadLink = $this->spreadLink + 1;
            }
        }

        public function createLinkPage()
        {
//            $pageMinusSpread = $this->totalPage - $this->spreadLink;
//
//            if( $pageMinusSpread < $this->spreadLink )
//            {
//                $this->spreadLink = 1;
//            }
            
            if( $this->currentLink >= $this->spreadLink )
            {
                $this->startLink = $this->currentLink - $this->spreadLink + 1;
                $this->endLink = $this->currentLink + $this->spreadLink;
            }
            else
            {
                $this->startLink = 1;
                $this->endLink = $this->setOfLink;
            }

            $runningLink = $this->startLink;
            while( $runningLink <= $this->endLink )
            {
                if( $runningLink <= $this->totalPage )
                {
                    if( $this->currentLink == $runningLink )
                    {
                        $openHREF = "";
                        $onClick = "";
                        $bracket = "";
                        $closeTag = "";
                        $closeHREF = "";
                        $bracketStart = "<span class='pgDim'>";
                        $bracketEnd = "</span>";
                    }
                    else
                    {
                        $openHREF = '<a class="pgNum" href="#" ';
                        $onClick = 'onClick="javascript:goTo';
                        $bracket = "('$runningLink')";
                        $closeTag = '">';
                        $closeHREF = "</a>";
                        $bracketStart = "";
                        $bracketEnd = "";
                    }
                    $space = " ";
                    $this->linking = $this->linking . $openHREF . $onClick . $bracket . $closeTag . $bracketStart . $runningLink . $bracketEnd . $closeHREF . $space;
                }

                $runningLink++;
            }
        }

        public function createLinkWord( $flo, $bil, $anchor, $word )
        {
            if( ( $flo == 0 && $anchor == "B" )||( $flo == 0 && $anchor == "F" ) )
            {
                $openHREF = "";
                $onClick = "";
                $bracket = "";
                $closeTag = "<span class='pgDisabled'>";
                $closeHREF = "</span>";
            }
            else
            {
                if( ( $bil == $this->totalRecord && $anchor == "N" )||( $bil == $this->totalRecord && $anchor == "L" ) )
                {
                    $openHREF = "";
                    $onClick = "";
                    $bracket = "";
                    $closeTag = "<span class='pgDisabled'>";
                    $closeHREF = "</span>";
                }
                else
                {
                    $openHREF = '<a class="pgWord" href="#" ';
                    $onClick = 'onClick="javascript:goTo';
                    $bracket = "('$anchor')";
                    $closeTag = '">';
                    $closeHREF = "</a>";
                }
            }

            $this->linkOfWord = $openHREF . $onClick . $bracket . $closeTag . $word . $closeHREF;
        }

        public function getLinkWord()
        {
            return $this->linkOfWord;
        }

        public function goToSelectedPage( $LP )
        {
            if( $LP )
            {
                if( $LP == "F" )
                    $this->goToFirstPage();
                else if( $LP == "N" )
                    $this->goToNextCurrentPage();
                else if( $LP == "B" )
                    $this->goToBeforeCurrentPage();
                else if( $LP == "L" )
                    $this->goToLastPage();
                else
                    $this->goToNumberPage( $LP );
            }
            else
            {
                $this->goToFirstPage();
            }
        }

        public function getLinkPage()
        {
            return $this->linking;
        }

        public function getCountRecords()
        {
            return $this->totalRecord;
        }

        public function getFloorNumber()
        {
            return $this->floorNumber;
        }

        public function getCeilingNumber()
        {
            return $this->ceilingNumber;
        }

        public function getRecordPerPage()
        {
            return $this->recordPerPage;
        }

        public function getTotalPage()
        {
            return $this->totalPage;
        }
        
/**
 * Penyataan/method ini perlu di letak di bahagian bawah halaman dibahagian mana paparan halaman hendak dipaparkan.
 * Selain itu ia juga menulis kod javascript bagi membolehkan halaman bernombor diklik. Perlu menggunakan variable <b>$bil</b> serta ++$bil di dalam looping while (fetch_array?)
 * @access Public
 * @author Rizuwan <rizuwan.saar@jpa.gov.my>
 * @return Void echo <b>javascript</b> code dan <b>html</b> untuk paparan paging
 * @param Object $myPagination - Object Pagination ini sendiri.
 */
        public function paging( $myPagination )
        {
           echo '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" name="frmPagination">';
           $pgVar = array('linkPagination','flo','bil','ceil');
                //echo '<input type="hidden" name="linkPagination">';
                if(!array_key_exists('flo',$_POST))
                    foreach ($pgVar AS $kunci)
                    {
                        global ${$kunci};
                        echo '<input type="hidden" name="'.$kunci.'" value="'.${$kunci}.'">';
                    }

                foreach (array_keys($_REQUEST) AS $kunci)
                {
                    if(strstr($kunci,'btn') == false)
                    {
                        global ${$kunci};
                        if(array_search($kunci, $pgVar) == NULL)
                            ${$kunci} = $_REQUEST[$kunci];
                        echo '<input type="hidden" name="'.$kunci.'" value="'.${$kunci}.'">';
                    }
                }

            echo '</form>';

            echo '<script type="text/javascript" language="javascript">
                        function goTo (n) {
                            document.frmPagination.linkPagination.value = n;
                            document.frmPagination.action = "'.$_SERVER['PHP_SELF'].'";
                            document.frmPagination.submit();
                        }    
            </script>';

            echo '<div align="center">
                    <table width="100%">
                        <tr align="center">
                            <td>';
                                    $myPagination->createLinkWord( $flo, $bil, 'F', 'Mula' );
                                    echo $myPagination->getLinkWord().'
                            </td>
                            <td class="pgSeperator">&nbsp;</td>
                            <td>';
                                    $myPagination->createLinkWord( $flo, $bil, 'B', 'Sebelumnya' );
                                    echo $myPagination->getLinkWord().'
                            </td>
                            <td class="pgSeperator">&nbsp;</td>
                            <td>'.$myPagination->getLinkPage().'</td>
                            <td class="pgSeperator">&nbsp;</td>
                            <td>';
                                    $myPagination->createLinkWord( $flo, $bil, 'N', 'Seterusnya' );
                                    echo $myPagination->getLinkWord().'
                            </td>
                            <td class="pgSeperator">&nbsp;</td>
                            <td>';
                                    $myPagination->createLinkWord( $flo, $bil, 'L', 'Akhir' );
                                    echo $myPagination->getLinkWord().'
                            </td>
                        </tr>
                    </table>
                </div>';
        }        
        
/**
 * Penyataan/method ini perlu ditulis di bawah declaration objek Pagination dimana objek tersebut perlu dideclare selepas pernyataan $dbQueryObj->runSQLquery()
 * @access Public
 * @author Rizuwan <rizuwan.saar@jpa.gov.my>
 * @return Object $DBQuery Objek DBQuery
 * @return Object $myPagination Objek Pagination
 * @param Object $myPagination Objek Pagination ini sendiri
 * @param Object $DBQuery Objek DBQuery yang telah dideclare
 * @param String $SQLquery Query SQL, bukannya Objek SQLquery
 * @param Int $recPerPage berapa rekod yang hendak dipaparkan pada setiap page?
 * @param Int $viewLinkPage berapa no. page(link) yang hendak dipaparkan pada setiap page.
 */
        public function initPaging($myPagination, $DBQuery, $SQLquery, $recPerPage, $viewLinkPage)
        {
            global $linkPagination,$bil, $flo, $ceil;
            $linkPagination = isset($_POST["linkPagination"]) ? $_POST["linkPagination"] : 1;
            $flo = isset($_POST["flo"]) ? $_POST["flo"] : '';
            $bil = isset($_POST["bil"]) ? $_POST["bil"] : '';
            $ceil = isset($_POST["ceil"]) ? $_POST["ceil"] : '';
            
            $myPagination->setDataSource( $DBQuery );
            $myPagination->countRecords( $DBQuery->getQueryResult() );
            $myPagination->setRecordPerPage( $recPerPage );
            $myPagination->countPages();
            $myPagination->setFloorCeiling($flo, $ceil);
            $myPagination->goToSelectedPage($linkPagination);
            $flo = $myPagination->getFloorNumber();
            $ceil = $myPagination->getCeilingNumber();
            $rec = $myPagination->getRecordPerPage();

            //jana pautan bernombor
            $myPagination->setViewLinkPage( $viewLinkPage );
            $myPagination->setCurrentPage( $linkPagination );
            $myPagination->setSpreadPage();
            $myPagination->createLinkPage( $flo );
            
            $myPagination->generateRecords( $SQLquery . " LIMIT " . $flo . "," . $rec );
            if( $bil )
            {
                if( $linkPagination == "F" )
                    $bil = 0;
                elseif( $linkPagination == "N" )
                    $bil = $bil;
                else
                    $bil = $ceil + 1 - $rec;
            }
            else
            {
                $bil = 0;
            }
            return array($DBQuery, $myPagination);
        }
    }

/*
 *
 * Version: 2.1
 * Updated By: Mohd Rizuwan bin Sa'ar @ Idris
 * Remarks: tambah 2 methods supaya penggunaan paging lebih mudah, cepat dan efisyen
 * 
 * Version: 2.0
 * Updated By: Mohd Fadil bin Md Sari & Fernandez Christie Jassil
 * Remarks: add a few methods
 *
 * Version: 1.0 [ Released Date: 8 May 2011 ]
 * Developer: Mohd Fadil bin Md Sari & Fernandez Christie Jassil
 * Description/Remarks:
 * A UI Utility class for displaying record in paging.
 *
*/
?>
