<?php

class Utilities {
    
/**
 * Method ni untuk mendapatkan path bagi value filename yang diterima. Berapa kali perlu keluar/undo folder semasa
 * @access Public Static
 * @author Rizuwan <rizuwan.saar@jpa.gov.my>
 * @return String Ex: "../../../library/javascripts/datepicker/php/datepicker.php"
 * @param String $filename Nama fail beserta path yang bermula daripada root folder.
 */
    public static function getFilePath($filename){
        $dotted = '';
        while(!file_exists($dotted.$filename)){
                $dotted .= '../';
        }
        return $dotted;
    }
    
/**
 * Method ni untuk mendapatkan full path beserta filename bagi value filename yang diterima.
 * @access Public Static
 * @author Rizuwan <rizuwan.saar@jpa.gov.my>
 * @return String Ex: "../../../library/javascripts/datepicker/php/datepicker.php"
 * @param String $filename Nama fail beserta path yang bermula daripada root folder.
 */
    public static function getFullFilePath($filename){        
        $i = 0;
        $dotted = '';
        while(!file_exists($dotted.$filename) && ($i < 20)){ // 20 is max looping to prevent unlimited looping if wrong filename.
                $dotted .= '../';
                $i++;
        }
        return $dotted.$filename;
    }
    
/**
 * Method ni untuk mendapatkan tarikh mengikut format general seperti dalam pangkalan data.
 * @access Public Static
 * @author Rizuwan <rizuwan.saar@jpa.gov.my>
 * @return String date format. Ex: <br>"default" will be returned <b>2011-12-31 18:20:21</b> <br/>"display" will be returned <b>06:20:31pm 31/12/2011</b>
 * @param String $time [optional] kalau tak set, masa terkini akan dikembalikan.* @param String $date "31/12/2011 9:13:07" | "31.12.2011 9:13:07" | "31-12-2011 9:13:07"<br>[optional] kalau tak set, tarikh terkini akan dikembalikan.<br>"none" = default / now
 * @param String $format "display" | "default" 
 */
    public static function getDatetime($time = "none", $format = "default") {   
        $timezone = "Asia/Kuala_Lumpur";
        if(function_exists('date_default_timezone_set')) date_default_timezone_set($timezone);
        
        $time = $time == "none" ? date('Y-m-d G:i:s') : $time;        
        
        if ((strpos($time, '/') != FALSE) || (strpos($time, '-') != FALSE) || (strpos($time, '.') != FALSE))
        {
            list($str1, $str2) = explode(' ', "$time ");
            list($a, $b, $c) = preg_split('/[\/\.-]/', $str1);
            
            if($a > 1000)
                $time = $a.'-'.$b.'-'.$c.' '.$str2;
            
            if($c > 1000)
                $time = $c.'-'.$b.'-'.$a.' '.$str2;            
        }         
        
        if(date('Y',  strtotime($time)) != 1970)
        {
            if($format == "default") //format to save in database
                return date('Y-m-d G:i:s',  strtotime($time));
            if($format == "display") //format to display in UI
                return date('g:i:sa d/m/Y',  strtotime($time));
        }
        else
            return "-";
    } 
    
/**
 * Method ni untuk mendapatkan tarikh mengikut format general seperti dalam pangkalan data.
 * @access Public Static
 * @author Rizuwan <rizuwan.saar@jpa.gov.my>
 * @return String date format. Ex: "2009-11-26"
 * @param String $date "31/12/2011" | "31.12.2011" | "31-12-2011"<br>[optional] kalau tak set, tarikh terkini akan dikembalikan.<br>"none" = default / now
 * @param String $for "<b>db</b>" will return 2011-12-31<br>"<b>/</b>" will return 31/12/2011<br>"<b>dp</b>" will return 12/31/2011 for datepicker initial value<br>"<b>month</b>" will return <b>31 Disember 2011</b>
 */
    public static function getDate($date = "none", $for = "db") {        
        $date = $date == "none" ? date('Y-m-d') : $date;
        
        if($date != null && $date != "0000-00-00")
        {
            if ((strpos($date, '/') != FALSE) || (strpos($date, '-') != FALSE) || (strpos($date, '.') != FALSE))
            {
                list($a, $b, $c) = preg_split('/[\/\.-]/', $date);

                if($a > 1000)
                    $date = $a.'-'.$b.'-'.$c;

                if($c > 1000)
                    $date = $c.'-'.$b.'-'.$a;            
            }            

            $timezone = "Asia/Kuala_Lumpur";

            if(function_exists('date_default_timezone_set'))
                date_default_timezone_set($timezone);

            if ($for == '/')
                $date = date('d/m/Y',  strtotime($date));
            elseif ($for == 'dp')
                $date = date('m/d/Y',  strtotime($date));
            elseif ($for == 'month')
                $date = date('d',  strtotime($date)).' '. Utilities::namaBulan(date('n',  strtotime($date)),'M').' '.date('Y',  strtotime($date));
            else
                $date = date('Y-m-d',  strtotime($date));        
        }
        else
            $date = "-";
        return $date;
    }    
    
    
/**
 * Method ni untuk mendapatkan / tukar nama Bulan kepada Bahasa Melayu
 * @access Public Static
 * @author Rizuwan <rizuwan.saar@jpa.gov.my>
 * @return String date format. Ex: "Julai"
 * @param int $bulan <0-12>
 * @param String $type "<b>M</b>" will return <b>Julai</b><br>"<b>m</b>" will return <b>Jul</b>
 */
    public static function namaBulan($bulan = 0,$type = "M")
    {
        $bulan = $bulan == 0 ? date('m') : $bulan;
        
        $namaBulan = array (
            '1' => 'Januari',
            '2' => 'Februari',
            '3' => 'Mac',
            '4' => 'April',
            '5' => 'Mei',
            '6' => 'Jun',
            '7' => 'Julai',
            '8' => 'Ogos',
            '9' => 'September',
           '10' => 'Oktober',
           '11' => 'November',
           '12' => 'Disember',
        );
        
        return $type == 'M' ? $namaBulan[$bulan] : substr($namaBulan[$bulan], 0, 3);
    }
    
    
/**
 * Method ni untuk mendapatkan / tukar nama Hari kepada Bahasa Melayu
 * @access Public Static
 * @author Rizuwan <rizuwan.saar@jpa.gov.my>
 * @return String date format. Ex: "Sabtu"
 * @param date $date date() if not set
 * @param int $type "<b>1</b>" will return <b>Sabtu</b><br>"<b>2</b>" will return <b>Sab</b>
 */
    public static function namaHari($date = 0, $type = 1)
    {
        $timezone = "Asia/Kuala_Lumpur";

            if(function_exists('date_default_timezone_set'))
                date_default_timezone_set($timezone);
            
        $namaHari = array (
            '1' => 'Isnin',
            '2' => 'Selasa',
            '3' => 'Rabu',
            '4' => 'Khamis',
            '5' => 'Jumaat',
            '6' => 'Sabtu',
            '7' => 'Ahad'
        );
        
        if($date != 0)
            $hari = $namaHari[date('N',strtotime($date))];
        else
            $hari = $namaHari[date('N')];
        
        return $type == 1 ? $hari : substr($hari, 0, 3);
    }
    
/**
 * Method ni untuk mendapatkan tarikh mengikut format general seperti dalam pangkalan data. tambah baik funtion yang rizuwan buat
 * @access Public Static
 * @author khairul <anuar.hassan@jpa.gov.my>
 * @return String date format. Ex: "2009-11-26"
 * @param String $date "31/12/2011" | "31.12.2011" | "31-12-2011"<br>[optional] kalau tak set, tarikh terkini akan dikembalikan dengan value TIADA<br>"none" = default / now
 * @param String $for "<b>db</b>" will return 2011-12-31<br>"<b>/</b>" will return 31/12/2011<br>"<b>month</b>" will return <b>31 Disember 2011</b>
 */
    
    public static function getDates($date = "none", $for = "db") {        
        $date = $date == "none" ? date('Y-m-d') : $date;
        
        if($date != null && $date != "0000-00-00")
        {
            if ((strpos($date, '/') != FALSE) || (strpos($date, '-') != FALSE) || (strpos($date, '.') != FALSE))
            {
                list($a, $b, $c) = preg_split('/[\/\.-]/', $date);

                if($a > 1000)
                    $date = $a.'-'.$b.'-'.$c;

                if($c > 1000)
                    $date = $c.'-'.$b.'-'.$a;            
            }            

            $timezone = "Asia/Kuala_Lumpur";

            if(function_exists('date_default_timezone_set'))
                date_default_timezone_set($timezone);

            if ($for == '/')
                $date = date('d/m/Y',  strtotime($date));
            elseif ($for == 'month')
                $date = date('d',  strtotime($date)).' '. Utilities::namaBulan(date('n',  strtotime($date)),'M').' '.date('Y',  strtotime($date));
            else
                $date = date('Y-m-d',  strtotime($date));        
        }
        else
            $date = "TIADA";
        return $date;
    }
    
    
}



/*
 *
 * Version: 1.1
 * Updated By:
 * Remarks:
 *
 * Version: 1.0 [ Released Date: 1 Ogos 2011 ]
 * Developer: Mohd Rizuwan bin Sa'ar @ Idris
 * Description/Remarks:
 * getFilePath($filename) - method untuk mencari path bagi satu file dan akan mengembalikan nilai '../'
 *       Contoh penggunaan boleh rujuk /library/javascripts/datepicker/php/datepicker.php line 7 sehingga line 11
 * 
 * getFullFilePath($filename) -  method untuk mencari path bagi satu file dan akan mengembalikan nilai '../' beserta nama fail
 *       Contoh penggunaan boleh rujuk /modul/300_kewangan/301_penyata/01_penyata_berkelompok.php line 32
*/
?>
