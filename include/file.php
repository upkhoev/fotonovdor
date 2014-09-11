<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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

        if (!file_exists($pathToFile)) {
            $this->createFile($pathToFile);
        }
        $this->file = $pathToFile;
    }
    
    private function createFile($fileName)
    {
        $fp = fopen($fileName, "w");
        fwrite($fp, "");
        fclose($fp);
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
        if ( !$this->fp ) {
            return FALSE;
        }
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