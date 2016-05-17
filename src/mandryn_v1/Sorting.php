<?php
    class Sorting
    {
        private $numOfLoop;

        public $arrayToSort;
        public $orderOfArray;
        public $theFirstArray;
        public $theSecondArray;
        public $arrA;
        public $arrB;
        public $arrayOrElement;

        public function  __construct()
        {
            $this->theFirstArray = array();
            $this->theSecondArray = array();
        }

        public function setLooping( $totalLoop )
        {
            $this->numOfLoop = $totalLoop;
        }

        public function setArrayOrElement( $arrayOrElement )
        {
            $this->arrayOrElement = $arrayOrElement;
        }

        public function setArray( $firstArray,$secondArray )
        {
            if( $this->arrayOrElement == 'array' )
            {
                $this->theFirstArray = $firstArray;
                $this->theSecondArray = $secondArray;
            }
            else if( $this->arrayOrElement == 'element' )
            {
                $this->theFirstArray[] = array( 'value' => $firstArray );
                $this->theSecondArray[] = array( 'value' => $secondArray );
            }
        }

        public function setArrayToSort( $arrayWillBeSorted )
        {
            $this->arrayToSort = $arrayWillBeSorted;
        }

        public function setOrderOfArray( $arrayOrder )
        {
            $this->orderOfArray = $arrayOrder;
        }

        public function sortingOperation()
        {
            if( $this->arrayToSort == '1' )
            {
                $this->arrA = $this->theFirstArray;
                $this->arrB = $this->theSecondArray;
            }
            else if( $this->arrayToSort == '2' )
            {
                $this->arrA = $this->theSecondArray;
                $this->arrB = $this->theFirstArray;
            }

            if( sizeof( $this->arrA ) > 0 )
            {
                for( $i = 0; $i < $this->numOfLoop; $i++ )
                {
                    $j = $this->numOfLoop - 1;
                    while( $j > 0 )
                    {
                        if( $this->orderOfArray == 'AtoZ' )
                        {
                            if( $this->arrA[$j] < $this->arrA[$j-1] )
                            {
                                $tempArray = $this->arrA[$j-1];
                                $this->arrA[$j-1] = $this->arrA[$j];
                                $this->arrA[$j] = $tempArray;

                                $tempArray = $this->arrB[$j-1];
                                $this->arrB[$j-1] = $this->arrB[$j];
                                $this->arrB[$j] = $tempArray;
                            }
                        }
                        else if( $this->orderOfArray == 'ZtoA' )
                        {
                            if( $this->arrA[$j] > $this->arrA[$j-1] )
                            {
                                $tempArray = $this->arrA[$j-1];
                                $this->arrA[$j-1] = $this->arrA[$j];
                                $this->arrA[$j] = $tempArray;

                                $tempArray = $this->arrB[$j-1];
                                $this->arrB[$j-1] = $this->arrB[$j];
                                $this->arrB[$j] = $tempArray;
                            }
                        }
                        $j--;
                    }
                }
            }

            if( $this->arrayToSort == '1' )
            {
                $this->theFirstArray = $this->arrA;
                $this->theSecondArray = $this->arrB;
            }
            else if( $this->arrayToSort == '2' )
            {
                $this->theSecondArray = $this->arrA;
                $this->theFirstArray = $this->arrB;
            }
        }

        public function getSortOfFirstArray()
        {
            return $this->theFirstArray;
        }

        public function getSortOfSecondArray()
        {
            return $this->theSecondArray;
        }

        public function  __destruct()
        {
            unset( $this->theFirstArray );
            unset( $this->theSecondArray );
            unset( $this->arrA );
            unset( $this->arrB );
        }
    }

/*
 *
 * Version: 1.1 [ Modified Date: 30 June 2011 ]
 * Updated By: Mohd Fadil bin Md Sari
 * Remarks: add a destruct method
 *
 * Version: 1.0 [ Released Date: 30 June 2011 ]
 * Developer: Mohd Fadil bin Md Sari
 * Description/Remarks:
 * A Utility class for sorting an array.
 *
*/
?>