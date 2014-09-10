<?php

/* 
 * Контроллер вывода шаблона
 */

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

    /**
     * Display template
     * 
     * @param string $tmplFileName Template file name
     * @param array $data data
     */
    public function load($tmplFileName, $data = array()) {

        include self::$tmplDir . '/' . $tmplFileName . self::$ext;
    }
}