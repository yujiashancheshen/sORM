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

    // 保存着各种连接
    protected static $_connects = [];

    // 保存着db的各种配置
    protected static $_dbConfig = [];

    // 保存着各种表
    protected static $_tables = [];

    // 实例化时的当前连接
    private $_dbh;

    private function __construct($dsn, $user, $password, $charset = null) {
        $this->_dbh = new PDO($dsn, $user, $password);
        $this->_dbh->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        if ($charset) {
            $this->_dbh->exec("SET NAMES '$charset'");
        }
    }
    
	/**
	* @desc _connect 
	* 连接数据库，如果存在已经有的连接就返回连接，否则新建连接 
	*
	* @Param $dbConfig
	*
	* @return 
	*/
    protected function _connect($dbConfig) {
        $dsn = $dbConfig['dsn'];
        if (self::$_connects[$dsn]) {

            return self::$_connects[$dsn];
        }

        $db = new db($dsn, $dbConfig['user'], $dbConfig['password']);
        self::$_connects[$dsn] = $db;
        return self::$_connects[$dsn];
    }

	/**
	* @desc query 
	* 查询数据库 
	*
	* @Param $sql
	* @Param $params
	*
	* @return 
	*/
    public function query($sql, $params = array()) {
        $stmt = $this->_dbh->prepare($sql);
        $stmt->execute($params);
        
        if (stripos($sql, 'select') !== false) {
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else if ((stripos($sql, 'update') !== false) 
            || (stripos($sql, 'delete') !== false))  {
            $data = $stmt->rowCount();
        } else if (stripos($sql, 'insert') !== false) {
            $data = $this->_dbh->lastInsertId();
        }

        return $data;
    }

	/**
	* @desc exec 
	* 查询数据库 
	*
	* @Param $sql
	*
	* @return 
	*/
    public function exec($sql) {
        $stmt = $this->_dbh->prepare($sql);
        $stmt->query($sql);
        $data = $stmt->fetchAll();

        return $data;
    }

	/**
	* @desc instance 
	* 获取实例 
	*
	* @Param $tableName
	*
	* @return 
	*/
    public static function instance($tableName) {
        if (!self::$_dbConfig) {
            self::_loadDbConfig();
        }

        $dbConfig = self::$_dbConfig[self::$_tables[$tableName]];

        return self::_connect($dbConfig);
    }

	/**
	* @desc _loadDbConfig 
	* @desc 导入数据库配置 
	*
	* @return 
	*/
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

