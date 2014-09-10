<?php


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