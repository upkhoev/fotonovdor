<?php
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);
date_default_timezone_set("Asia/Irkutsk");
class config {
    private static $configDir = 'config';

    public static function load($param = array()) {
        $configDir = self::$configDir;
        include_once $configDir . '/configuration.php';
        include_once $configDir . '/db.php';
        include_once $configDir . '/smtp.php';
        if (isset($config)) {

            if (isset($config['db'])) {
                $mysqli = db::getInstance($config['db']);
                $roadsModel = new Roads(array('db' => $config['db']));
                $dbsetting = $roadsModel->getSetting();

                /*foreach ($config as $key => $val) {
                    foreach ($setting as $val2) {
                        if ($key == $val2['settingKey']) {
                            $config[$key] = $val2['settingVal'];
                        }
                    }
                }*/

                foreach ($dbsetting as $key => $val) {
                    $config[$val['settingKey']] = $val['settingVal'];
                }
            }

            return $config;
        } else {
            exit('Config file not found!');
            return FALSE;
        }
    }

    public static function getPhotographer() {
        $pathToFile = self::$configDir . '/photographer.php';
        if (file_exists($pathToFile)) {
            include_once $pathToFile;
            return $data;
        } else {
            echo '<p>Отсутсвует файл с фотографами</p>';
            return FALSE;
        }
    }
}
$appConfig = config::load();

class View {
    protected static $instance;

    private static $tmplDir = 'templates';
    private static $ext = '.php';

    private function __construct() {}
    private function __clone() {}

    /**
     *
     * @param array $params
     * @return View[]
     */
    public static function getInstance($params = array()) {
        if (self::$instance !== NULL) {
            return self::$instance;
        }
        self::$instance = new self();
        return self::$instance;
    }

    public function load($tmplFileName, $data = NULL) {

        include self::$tmplDir . '/' . $tmplFileName . self::$ext;
    }
}

class Roads {
    private $db = FALSE;

    public function __construct($appConfig) {

        $this->db = db::getInstance($appConfig['db']);
    }

    public function roads()
    {
        if (!$this->db) {
            return FALSE;
        }
        if ($result = $this->db->query("SELECT * FROM roads;")) {
            $roads = array();
            while( $row = $result->fetch_assoc() ){
                $roads[] = $row;
            }

            $result->free_result();
            return $roads;
        }
    }


    public function getKilometrs()
    {
        if (!$this->db) {
            return FALSE;
        }
        if ($result = $this->db->query("SELECT * FROM kilometrs;")) {
            $distance = array();
            while( $row = $result->fetch_assoc() ){
                $distance[] = $row;
            }

            $result->free_result();
            return $distance;
        }
    }

    public function updateKilometrs($kmId, $km, $data = NULL)
    {
        $query = "UPDATE kilometrs SET km='$km' WHERE kmid='$kmId'";
        $this->db->query($query);
    }

    public function getSetting()
    {
        if (!$this->db) {
            return FALSE;
        }
        if ($result = $this->db->query("SELECT * FROM setting;")) {
            $setting = array();
            while( $row = $result->fetch_assoc() ){
                $setting[] = $row;
            }

            $result->free_result();
            return $setting;
        }
    }

    /**
     * Список папок
     * @param integer $km
     * @return boolean|array
     */
    public function getDirectory($km = NULL)
    {
        $query = "SELECT * FROM `directory`";
        if ($km !== NULL) {
            $query .= " WHERE start<='$km' AND end>='$km'";
        }
        $result = $this->db->query($query);
        if ($result->num_rows == 0) {
            return FALSE;
        }
        while( $row = $result->fetch_assoc() ){
            $data[] = $row;
        }

        $result->free_result();
        return $data;
    }

    public function setSetting($Id, $value)
    {
        if (is_array($value)) {
            $value = serialize($value);
        }
        $this->db->query("UPDATE setting SET settingVal='{$value}' WHERE settingKey='{$Id}';");
    }

    public function getMaxLogId()
    {
        $result = $this->db->query("SELECT MAX(logid) AS maxid FROM logs;");
        $row = $result->fetch_assoc();
        return ($row['maxid'] === NULL ? 1 : $row['maxid'] + 1);

    }

    public function writeLog($lodId, $filename, $km)
    {
        //$values = array($lodId, $filename, time(), $km);
        $this->db->query("INSERT INTO logs(logid, filename, datetime, km) VALUES ($lodId, '$filename', " . time() . ", $km)");
        //var_dump($this->db->error);
        return $this->db->affected_rows;
    }
}

/**
 * DB
 */

class db {
    private $id = FALSE;

    protected static $host = 'localhost';
    protected static $user = '';
    protected static $password = '';
    protected static $database = '';

    protected static $instance;

    private function __construct() {}
    private function __clone() {}

    /**
     *
     * @param type $params
     * @return db
     */
    public static function getInstance($params = array())
    {
        if (self::$instance !== NULL) {
            return self::$instance->id;
        }
        if (isset($params['host'])) self::$host = $params['host'];
        if (isset($params['user'])) self::$user = $params['user'];
        if (isset($params['password'])) self::$password = $params['password'];
        if (isset($params['database'])) self::$database = $params['database'];

        self::$instance = new self();

        self::$instance->id = new mysqli(self::$host, self::$user, self::$password, self::$database);

        /* проверяем соединение */
        if (self::$instance->id->connect_errno) {
            printf("Ошибка соединения: %s\n", self::$instance->id->connect_error());
            return FALSE;
        }

        self::$instance->id->set_charset("utf8");
        return self::$instance->id;
    }
}

class File {
    public $file;

    /**
     * resource fopen
     * @var resource
     */
    private $fp = FALSE;

    private $error_msg = '';

    public function __construct($pathToFile)
    {
        if (!function_exists('exif_imagetype')) {
            exit('Include exif.dll library and restart web server!');
            return FALSE;
        }

        if (file_exists($pathToFile)) {
            $this->file = dirname(__FILE__) . '/' . ltrim($pathToFile, '/');

        } else {
            return FALSE;
        }
    }

    public function setError($msg)
    {
        $this->error_msg = $msg;
    }

    public function displayError()
    {
        return $this->error_msg;
    }

    public static function isFile($pathToFile)
    {
        if ($pathToFile == "." && $pathToFile == ".." && is_dir($pathToFile)) {
            return FALSE;
        } else {
            return TRUE;
        }
    }


    public function getModTime()
    {
        return filemtime($this->file);
    }

    public function isImage() {
        return (@getimagesize($this->file) !== NULL ? TRUE : FALSE);
    }

    public function open($r = "a+")
    {
        $this->fp = fopen($this->file, $r);
    }


    public function writeToFile($msg)
    {
        fwrite($this->fp, $msg . "\n");
    }

    public function readData($arrayList = FALSE)
    {
        if ($this->fp) {
            $data = ($arrayList ? array() : "");
            while (!feof($this->fp)) {
                $line = fgets($this->fp, 999);
                if ($arrayList) {
                    if (!empty($line)) {
                        $data[] = $line;
                    }
                } else {
                    $data .= $line;
                }
            }
            return $data;
        }
        return FALSE;
    }

    public function closeFp()
    {
        if ($this->fp) {
            fclose($this->fp);
        }
    }

    public function getImgDataAttr() {
        //$imgPath = dirname(__FILE__) . $imgPath;
        if (!function_exists('exif_imagetype')) {
            $errorMsg = 'Include exif.dll library and restart web server!';
            $this->setError($errorMsg);
            exit($errorMsg);
        }

        if ($this->isImage() && @exif_imagetype($this->file)) {
            $exif = exif_read_data($this->file);
            if (!isset($exif['GPSLatitude'])) {
                $errorMsg = 'В файле отсутвует GPS координаты';
                $this->setError($errorMsg);
                return FALSE;
            }
            // Широта
            $latitude['degrees'] = getCoord( $exif['GPSLatitude'][0] );
            $latitude['minutes'] = getCoord( $exif['GPSLatitude'][1] );
            $latitude['seconds'] = getCoord( $exif['GPSLatitude'][2] );

            if ($latitude['degrees'] == 0 && $latitude['minutes'] == 0 && $latitude['seconds'] == 0) {
                //echo 'Координаты не найдены' . "\n";
				$errorMsg = 'В файле отсутвует GPS координаты';
				$this->setError($errorMsg);
                return FALSE;
            } else {
                $latitude['minutes'] += 60 * ($latitude['degrees'] - floor($latitude['degrees']));
                $latitude['degrees'] = floor($latitude['degrees']);

                $latitude['seconds'] += 60 * ($latitude['minutes'] - floor($latitude['minutes']));
                $latitude['minutes'] = floor($latitude['minutes']);

                // Долгота
                $longitude['degrees'] = getCoord( $exif['GPSLongitude'][0] );
                $longitude['minutes'] = getCoord( $exif['GPSLongitude'][1] );
                $longitude['seconds'] = getCoord( $exif['GPSLongitude'][2] );

                $longitude['minutes'] += 60 * ($longitude['degrees'] - floor($longitude['degrees']));
                $longitude['degrees'] = floor($longitude['degrees']);

                $longitude['seconds'] += 60 * ($longitude['minutes'] - floor($longitude['minutes']));
                $longitude['minutes'] = floor($longitude['minutes']);
                $degreesChar = "°";
                //$degrees = '&deg;';
                $m1 = ($exif['GPSLatitudeRef'] == 'S' ? '-' : '')
                        . $latitude['degrees'] . $degreesChar
                        . $latitude['minutes']
                        . "'" . $latitude['seconds'] . "\"N";
                $m2 = ($exif['GPSLongitudeRef'] == 'W' ? '-' : '')
                        . $longitude['degrees'] . $degreesChar
                        . $longitude['minutes'] . "'"
                        . $longitude['seconds'] . "\"E";
                return array(
                    'date'         => isset($exif['DateTimeOriginal']) ? $exif['DateTimeOriginal'] : FALSE,
                    'src'          => $this->file,
                    'gpslatitude'  => degreesToDecimal($latitude['degrees'], $latitude['minutes'], $latitude['seconds']),
                    'gpslongitude' => degreesToDecimal($longitude['degrees'], $longitude['minutes'], $longitude['seconds']),
                    'geolat'       => $m1,
                    'geolong'      => $m2
                );
            }
        } else {
            $errorMsg = 'Файл не является изображением';
            $this->setError($errorMsg);
            return FALSE;
        }
    }
}

/**
 * Функция проверяет кол-во файлов в директории
 * Если кол-во больше лимита удаляет старые
 * @param string $pathToDir
 * @return int
 */
function clearCopyDir($pathToDir, $limitCountFiles) {
    $files = getFiles($pathToDir);
    $i = 0;
    $data = array();
    if (count($files) > $limitCountFiles) {
        $diff = count($files) - $limitCountFiles;
        foreach ($files as $key => $item) {
            $file = new File($item);
            $date = $file->getModTime();
            if ($date) {
                $data[] = array('file' => $item, 'date' => $date);
                $volume[$key] = $item;
                $edition[$key] = $date;
            }
        }
        array_multisort($edition, SORT_ASC, $volume, SORT_ASC, $data);
        unset($files);
        while ($diff > 0) {
            unlink($data[$i]['file']);
            $diff--;
            $i++;
        }

    }
    return $i;
}

/*
 * Список файло из директории
 */
function getFiles($dir , $absPath = FALSE) {
    $filelist = array();

    //$folder = dirname(__FILE__);
    $folder = "{$dir}/";
    $i = 0;
    if ($handle = opendir($folder)) {
        while ($cv_file = readdir($handle)) {
            if (is_file($folder . $cv_file)) {
                $filelist[] = ($absPath ? $folder : "{$dir}/") . $cv_file;
            } elseif ($cv_file != "." && $cv_file != ".." && is_dir($folder . $cv_file)) {
                $filesArray = getFiles($folder . $cv_file);
                $filelist = array_merge($filelist, $filesArray);
            }

        }
        closedir($handle);
    } else {
        // Ошибка открытия директории
    }
    return $filelist;
}



function getCountDirFiles($pathToDir) {
    $dir = new DirectoryIterator($pathToDir);
    $x = 0;
    foreach($dir as $file ){
        //$x += (isImage($pathToDir . '/' . $file)) ? 1 : 0;
        $x += ($file->isFile()) ? 1 : 0;
    }
    return $x;
}

function isImage($filename) {
    $result = getimagesize($filename);
    return ($result !== NULL ? TRUE : FALSE);
}

function getPreparedFiles() {
    $filelist = array();

    $folder = "/upload/";
    if ($handle = opendir(dirname(__FILE__) . $folder)) {
        while ($entry = readdir($handle)) {
            if (is_file(dirname(__FILE__) . $folder . $entry)) {
                $filelist[] = $folder . $entry;
            }

        }
        closedir($handle);
    }
    return $filelist;
}

function redirect($url) {
    header("Location: " . $url);
    die();
}

function getAttrDataImg($imgPath) {
    //$imgPath = dirname(__FILE__) . $imgPath;
    if (!function_exists('exif_imagetype')) {
        exit('Include exif.dll library and restart web server!');
    }

    if (exif_imagetype($imgPath)) {
        $exif = exif_read_data($imgPath);
        if (!isset($exif['GPSLatitude'])) {
            return FALSE;
        }
        // Широта
        $latitude['degrees'] = getCoord( $exif['GPSLatitude'][0] );
        $latitude['minutes'] = getCoord( $exif['GPSLatitude'][1] );
        $latitude['seconds'] = getCoord( $exif['GPSLatitude'][2] );

        if ($latitude['degrees'] == 0 && $latitude['minutes'] == 0 && $latitude['seconds'] == 0) {
            //echo 'Координаты не найдены' . "\n";
            return FALSE;
        } else {
            $latitude['minutes'] += 60 * ($latitude['degrees'] - floor($latitude['degrees']));
            $latitude['degrees'] = floor($latitude['degrees']);

            $latitude['seconds'] += 60 * ($latitude['minutes'] - floor($latitude['minutes']));
            $latitude['minutes'] = floor($latitude['minutes']);

            // Долгота
            $longitude['degrees'] = getCoord( $exif['GPSLongitude'][0] );
            $longitude['minutes'] = getCoord( $exif['GPSLongitude'][1] );
            $longitude['seconds'] = getCoord( $exif['GPSLongitude'][2] );

            $longitude['minutes'] += 60 * ($longitude['degrees'] - floor($longitude['degrees']));
            $longitude['degrees'] = floor($longitude['degrees']);

            $longitude['seconds'] += 60 * ($longitude['minutes'] - floor($longitude['minutes']));
            $longitude['minutes'] = floor($longitude['minutes']);
            $degreesChar = "°";
            //$degrees = '&deg;';
            $m1 = ($exif['GPSLatitudeRef'] == 'S' ? '-' : '')
                    . $latitude['degrees'] . $degreesChar
                    . $latitude['minutes']
                    . "'" . $latitude['seconds'] . "\"N";
            $m2 = ($exif['GPSLongitudeRef'] == 'W' ? '-' : '')
                    . $longitude['degrees'] . $degreesChar
                    . $longitude['minutes'] . "'"
                    . $longitude['seconds'] . "\"E";
            return array(
                'date'         => isset($exif['DateTimeOriginal']) ? $exif['DateTimeOriginal'] : FALSE,
                'src'          => $imgPath,
                'gpslatitude'  => degreesToDecimal($latitude['degrees'], $latitude['minutes'], $latitude['seconds']),
                'gpslongitude' => degreesToDecimal($longitude['degrees'], $longitude['minutes'], $longitude['seconds']),
                'geolat'       => $m1,
                'geolong'      => $m2
            );
        }
    } else {
        return FALSE;
    }
}

/**
 * Возвращает расстояние между двумя точками
 * http://wiki.gis-lab.info/w/%D0%92%D1%8B%D1%87%D0%B8%D1%81%D0%BB%D0%B5%D0%BD%D0%B8%D0%B5_%D1%80%D0%B0%D1%81%D1%81%D1%82%D0%BE%D1%8F%D0%BD%D0%B8%D1%8F_%D0%B8_%D0%BD%D0%B0%D1%87%D0%B0%D0%BB%D1%8C%D0%BD%D0%BE%D0%B3%D0%BE_%D0%B0%D0%B7%D0%B8%D0%BC%D1%83%D1%82%D0%B0_%D0%BC%D0%B5%D0%B6%D0%B4%D1%83_%D0%B4%D0%B2%D1%83%D0%BC%D1%8F_%D1%82%D0%BE%D1%87%D0%BA%D0%B0%D0%BC%D0%B8_%D0%BD%D0%B0_%D1%81%D1%84%D0%B5%D1%80%D0%B5
 *
 * @param type $lat1
 * @param type $lon1
 * @param type $lat2
 * @param type $lon2
 * @return float
 */
function getDistance($lat1, $lon1, $lat2, $lon2) {
  $lat1 *= M_PI / 180;
  $lat2 *= M_PI / 180;
  $lon1 *= M_PI / 180;
  $lon2 *= M_PI / 180;

  $d_lon = $lon1 - $lon2;

  $slat1 = sin($lat1);
  $slat2 = sin($lat2);
  $clat1 = cos($lat1);
  $clat2 = cos($lat2);
  $sdelt = sin($d_lon);
  $cdelt = cos($d_lon);

  $y = pow($clat2 * $sdelt, 2) + pow($clat1 * $slat2 - $slat1 * $clat2 * $cdelt, 2);
  $x = $slat1 * $slat2 + $clat1 * $clat2 * $cdelt;

  return atan2(sqrt($y), $x) * 6372795;
}

function getCoord( $expr ) {
    $expr_p = explode( '/', $expr );
    if ($expr_p[0] == 0) {
        return 0;
    }
    return $expr_p[0] / $expr_p[1];
}

function degreesToDecimal($degrees, $minute, $seconds) {
    return round($degrees + $minute / 60 + $seconds / 3600, 6);
}

function hex2rgb($hex) {
   $hex = str_replace("#", "", $hex);

   if(strlen($hex) == 3) {
      $r = hexdec(substr($hex,0,1).substr($hex,0,1));
      $g = hexdec(substr($hex,1,1).substr($hex,1,1));
      $b = hexdec(substr($hex,2,1).substr($hex,2,1));
   } else {
      $r = hexdec(substr($hex,0,2));
      $g = hexdec(substr($hex,2,2));
      $b = hexdec(substr($hex,4,2));
   }
   $rgb = array($r, $g, $b);
   //return implode(",", $rgb); // returns the rgb values separated by commas
   return $rgb; // returns an array with the rgb values
}

function getRuMonth($mId) {
    switch ($mId){
        case 1: $m='января'; break;
        case 2: $m='февраля'; break;
        case 3: $m='марта'; break;
        case 4: $m='апреля'; break;
        case 5: $m='мая'; break;
        case 6: $m='июня'; break;
        case 7: $m='июля'; break;
        case 8: $m='августа'; break;
        case 9: $m='сентября'; break;
        case 10: $m='октября'; break;
        case 11: $m='ноября'; break;
        case 12: $m='декабря'; break;
    }
    return $m;
}