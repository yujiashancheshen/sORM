<?php

/**
 * DB
 *
 *    作者:   汪卓 (wangzhuo_hust@163.com)
 * 创建时间:   2017-12-07 15:29:32
 * 修改记录:
 *
 * $Id$
 */
class DB {

    protected static $_connects = [];

    protected static $_dbConfig = [];

    protected static $_tables = [];

    private $_dbh;

    private function __construct($dsn, $user, $password, $charset = null) {
        $this->_dbh = new PDO($dsn, $user, $password);
        $this->_dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        if ($charset) {
            $this->_dbh->exec("SET NAMES '$charset'");
        }
    }

    protected function _connect($dbConfig) {
        $dsn = $dbConfig['dsn'];
        if (self::$_connects[$dsn]) {

            return self::$_connects[$dsn];
        }

        $db = new db($dsn, $dbConfig['user'], $dbConfig['password']);
        self::$_connects[$dsn] = $db;
        return self::$_connects[$dsn];
    }

    public function query($sql, $params = array()) {
        $stmt = $this->_dbh->prepare($sql);
        $stmt->execute($params);
        
        if (stripos($sql, 'select') !== false) {
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $data = $stmt->rowCount();
        }

        return $data;
    }

    public function exec($sql) {
        $stmt = $this->_dbh->prepare($sql);
        $stmt->query($sql);
        $data = $stmt->fetchAll();

        return $data;
    }

    public static function instance($tableName) {
        if (!self::$_dbConfig) {
            self::_loadDbConfig();
        }

        $dbConfig = self::$_dbConfig[self::$_tables[$tableName]];

        return self::_connect($dbConfig);
    }

    private static function _loadDbConfig() {
        require_once __DIR__ . '/../config.php';

        if (!isset($TABLES) || !isset($DBS)) {
            throw new Exception("db config need tables and dbs");
        }

        self::$_tables = $TABLES;

        foreach ($TABLES as $tableName => $dbName) {

            self::$_dbConfig[$dbName] = [
                'dsn' => "mysql:dbname={$dbName};host={$DBS[$dbName]['host']}",
                'user' => $DBS[$dbName]['user'],
                'password' => $DBS[$dbName]['password'],
            ];
        }
    }
}

