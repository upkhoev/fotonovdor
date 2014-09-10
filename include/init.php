<?php
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);
date_default_timezone_set("Asia/Irkutsk");

include_once 'include/function.php';

function __autoload($class_name) {
    include strtolower($class_name) . '.php';
}

$appConfig = config::load();