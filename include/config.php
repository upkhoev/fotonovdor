<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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