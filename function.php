<?php
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);
date_default_timezone_set("Asia/Irkutsk");
class config {
    private static $configDir = 'config';
    protected static $config = NULL;
    
    private function __construct() {}
    private function __clone() {}
    
    public static function load($param = array()) {
        if ( self::$config !== NULL ) {
            return self::$config;
        }
        
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
            self::$config = $config;
            return self::$config;
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
    
    private $threadsTbl = 'threads';

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
    
    public function setThread($fileName)
    {
        $tbl = $this->threadsTbl;
        $fileName = $this->db->real_escape_string($fileName);
        echo $fileName;
        $this->db->query("INSERT INTO `{$tbl}`(filepath, datetime) VALUES ('" . $fileName . "', '" . time() . "')");
        return $this->db->affected_rows;
    }
    
    public function searchInThread($fileName, $uptime = NULL)
    {
        $tbl = $this->threadsTbl;
        $fileName = $this->db->real_escape_string($fileName);
        $query = "SELECT * FROM `{$tbl}` WHERE filepath='" . $fileName . "'";
        if ($uptime !== NULL) {
            $query .= " AND datetime >= ".$uptime;
        }
        
        $result = $this->db->query($query);
        
        return ($result->num_rows > 0 ? TRUE : FALSE);
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

function sendEmailLogData() {
    $appConfig = config::load();
    include  $appConfig['path_to_swift'] . 'swift_required.php';
    $emailDataFile = 'emaildata.txt';
    
    $efile = New File($emailDataFile);
    $efile->open("r+");
    // Отправляем содержимое файла на почту через определенный промежуток
    $email_send_file = New File('email_send_time.txt');
    $email_send_file->open("r");
    $send_file_time = $email_send_file->readData();
    $fileModDate = intval($send_file_time);
    $email_send_file->closeFp();
    $interval = $appConfig['email_send_interval'];

    if (
        time() - $fileModDate > $interval
            &&
        $appConfig['send_email'] == 1
            &&
        class_exists('Swift_Message')
    ) {
        $msg = $efile->readData();
        if (!empty($msg)) {
            $transport = Swift_SmtpTransport::newInstance($appConfig['smtp_transport']['smtp_server'], $appConfig['smtp_transport']['port'], 'ssl')
              ->setUsername($appConfig['smtp_transport']['username'])
              ->setPassword($appConfig['smtp_transport']['password']);
            // Create the Mailer using your created Transport
            $mailer = Swift_Mailer::newInstance($transport);
            // Create the message
            $message = Swift_Message::newInstance()
                // Give the message a subject
                ->setSubject('Лог обработки фотографий ' . date("r"))

                // Set the From address with an associative array
                ->setFrom(array('it@novdor.ru' => 'Admin'))
                ->setTo(array($appConfig['report_email']))
                // Give it a body
                ->setBody($msg);
            // Send the message
            $result = $mailer->send($message);
            
            /* Записываем время отправки письма */
            $email_send_file = New File('email_send_time.txt');
            if ($email_send_file) {
                $email_send_file->open("w+");
                $email_send_file->writeToFile(time());
                $email_send_file->closeFp();
            }
        }
        /* Очищаем файл */
        $newFile = New File($emailDataFile);
        $newFile->open("w+");
        $newFile->closeFp();
    }
    $efile->closeFp();
}

class File {
    public $file = NULL;

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
        if ($this->file !== NULL) {
            $this->fp = fopen($this->file, $r);
        }
    }


    public function writeToFile($msg)
    {
        fwrite($this->fp, $msg . "\n");
    }
    
    public function countLines()
    {
        return count($this->readData(TRUE));
    }

    public function readData($toArray = FALSE)
    {
        if ($this->fp) {
            $data = ($toArray ? array() : "");
            while (!feof($this->fp)) {
                $line = fgets($this->fp, 999);
                if (!empty($line)) {
                    if ($toArray) {
                        $data[] = $line;
                    } else {
                        $data .= $line;
                    } 
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

function getRenderFileName($filename) {
    return str_replace(" ", "_", $filename) . '_' . date('d-m-Y') . '_' . uniqid() . '.jpg';
}

/**
 * Обработка изображения
 * 
 * @param array $imageVal
 * @param array $appConfig
 * @param string $fioText
 * @return array
 */
function imgProcessing($imageVal, $appConfig, $fioText, $regionNameRu) {
    //include 'WideImage/WideImage.php';
    if (!class_exists('WideImage')) {
        exit('Class WideImage not found!');
    }
    $image = WideImage::load($imageVal['src']);
    list($width, $height, $type, $attr) = getimagesize($imageVal['src']);
    $imgRatio = round($width / $height, 2);
    $widescreen = 1.77;
    if ($appConfig['crop_img_to_widescreen'] && $imgRatio < $widescreen) {
        $cropedImg = $image->crop('center', 'bottom', $width, round($width / $widescreen) - $appConfig['footer_bg_color']);
        unset($image);
    } else {
        $cropedImg = $image;
    }

    $resizedImg = $cropedImg->resize(NULL, $appConfig['down_scaling_height']);

    $rgb = hex2rgb($appConfig['footer_bg_color']);
    $white = $resizedImg->allocateColor(
            $rgb[0],
            $rgb[1],
            $rgb[2]
        );

    // Добавляем внизу плашку
    $cropImg = $resizedImg->resizeCanvas('100%', '100%+' . $appConfig['footer_height'], 0, 0, $white);

    // обрезаем
    $newImage = $cropImg->crop(0, $appConfig['footer_height'], '100%', '100%');

    $choord1 = $imageVal['geolat'];
    $choord2 = $imageVal['geolong'];
    $text = $imageVal['road'] . ' '
         . mb_substr($imageVal['kmtitle'], mb_strpos($imageVal['kmtitle'], 'км')) .' '. ($imageVal['plus'] ? '+' : '-') . " {$imageVal['dist']} м.";
    if (isset($appConfig['color_text'])) {
        $hexTxtColor = hex2rgb($appConfig['color_text']);
        $textColor = $newImage->allocateColor($hexTxtColor[0], $hexTxtColor[1], $hexTxtColor[2]);
    } else {
        $textColor = $newImage->allocateColor(0, 0, 0);
    }

    $bottom = 15;
    /* 1 */
    $text = 'км';
    $canvas = $newImage->getCanvas();
    $canvas->useFont($appConfig['font'], 24, $textColor);
    $canvas->writeText('left + 10', 'bottom - ' . $bottom, $text);
    //$canvas->writeText('right - 220', 'bottom - 40', $text2);

    /* Столб */
    //$text = mb_substr($imageVal['km'], mb_strpos($imageVal['km'], 'км'));
    $text = $imageVal['km'];
    $count = 1;
    //$text = str_replace('км', '', $text, $count);
    $canvas = $newImage->getCanvas();
    $canvas->useFont($appConfig['font'], $appConfig['font_size'], $textColor);
    $canvas->writeText('left + 60', 'bottom - ' . $bottom, $text);

    /* Расстояние до столба */
    $text = '+' . $imageVal['dist'];
    $canvas = $newImage->getCanvas();
    $canvas->useFont($appConfig['font'], 24, $textColor);
    $canvas->writeText('left + 185', 'bottom - ' . $bottom, $text);

    /* Название дороги */
    $canvas = $newImage->getCanvas();
    $canvas->useFont($appConfig['font'], 12, $textColor);
    $canvas->writeText('left + 280', 'bottom - ' . $bottom, $imageVal['road']);

    /* Дата */
    $time = strtotime($imageVal['date']);
    $month = getRuMonth(date('m', $time));
    $text = date('d', $time) .'-'. mb_strtoupper(mb_substr($month, 0, 1)) . mb_substr($month, 1) .'-'. date('Y', $time);
    $canvas = $newImage->getCanvas();
    $canvas->useFont($appConfig['font'], 32, $textColor);
    $canvas->writeText('left + 565', 'bottom - ' . $bottom, $text);

    /* Время */
    $text = date('H:i:s');
    $canvas = $newImage->getCanvas();
    $canvas->useFont($appConfig['font'], 12, $textColor);
    $canvas->writeText('left + 950', 'bottom - ' . $bottom, $text);
    

    // Координаты
    $choordCanvas = $newImage->getCanvas();
    $choordCanvas->useFont($appConfig['font'], 12, $textColor);
    $choordCanvas->writeText( 'left + 1025', 'bottom - ' . $bottom,  "($choord1, $choord2)");

    $authorCanvas = $newImage->getCanvas();
    $authorCanvas->useFont($appConfig['font'], 12, $textColor);
    $authorCanvas->writeText( 'left + 950', 'bottom - 40', $fioText);

    $mask = WideImage::load($appConfig['logo_path']);
    $bigMask = $mask->resize('35%', '35%');

    $logoOffSet = 5;
    $maskImage = $newImage->merge($bigMask, 'right - ' . $logoOffSet, 'bottom', 100);
    
    $regionCanvas = $maskImage->getCanvas();
    $regionCanvas->useFont($appConfig['font'], 12, $textColor);
    $regionCanvas->writeText( 'left + 280', 'bottom - 50', $regionNameRu);
    return $maskImage;
}

function convertToCp1251($str) {
    return mb_convert_encoding($str, 'Windows-1251', 'UTF-8');
}

function convertToUtf8($str) {
    return mb_convert_encoding($str, 'UTF-8', 'Windows-1251');
}

/**
 * Функция вычисляет ближайший столб и определяет расстояние до ближ. точки
 * 
 * @param array $distance Массив точек(столбцов)
 * @param array $point Точка искомая
 */
function prepareDistance($distance, $roads, &$point) {
    
        $newdistance = array();
        foreach ($distance as $row){

            // Вычисляем расстояние между двумя точками
            $newdistance[$row['kmid']] = getDistance(
                    $row['gpslatitude'],
                    $row['gpslongitude'],
                    $point['gpslatitude'],
                    $point['gpslongitude']
                );
        }
        $notsort = $newdistance;

        asort($newdistance);

        reset($newdistance);
        $kmid = key($newdistance);
        $minValue = $newdistance[$kmid];
        next($newdistance);
        $seckmId = key($newdistance);
        // Вычисляем какой столб от начала(дальше или ближе)
        if ($kmid - $seckmId > 0) {
            // столб дальний
            $point['plus'] = TRUE;
        } else {
            // столб ближний
            $point['plus'] = FALSE;
        }

        $km = "";
        foreach ($distance as $row) {
            if ($kmid == $row['kmid']) {
                $roadId = $row['roadid'];
                $kmValue = $row['km'];
                $km = $row['title'];
            }
        }
        if (!$point['plus']) {
            $kmValue = $kmValue - 1;
        }

        $roadName = "";
        foreach ($roads as $road) {
            if ($roadId == $road['roadid']) {
                $roadName = $road['title'];
            }
        }

        if (floor($minValue) < 1000) {
            if (!$point['plus']) {
                $point['dist'] = 1000 - floor($minValue);
            } else {
                $point['dist'] = floor($minValue);
            }
        } else {
            $point['dist'] = '';
        }

        $point['kmtitle'] = $km;
        $point['km'] = $kmValue;
        $point['roadid'] = $roadId;
        $point['road'] = $roadName;
        
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
        return $filelist;
    } else {
        return FALSE;
        // Ошибка открытия директории
    }
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