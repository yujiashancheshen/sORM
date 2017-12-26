<?php
/**
 * DB
 *
 * 作者 : 汪卓(wangzhuo_hust@163.com)
 * 创建时间 : 2017-12-24 20:28:57
 * 修改记录 :
 *
 * $Id$
 */
class model {

    public static $table;

    // 保存最后一次执行的sql和参数
    private static $lastQuery;

    // 表的主键
    public static $primaryKey;

    public function __construct($data) {
        $fields = static::$fields;
        foreach ($data as $key => $value) {
            $field = $fields[$key];
            $this->$field = $value; 
        }
    }

    /**
    * @desc find 
    * 根据主键获取
    *
    * @Param $id
    *
    * @return 
    */
    public static function find($id) {
        if (!static::$primaryKey) {
           return null; 
        }

        $condition = [
            'where' => [
                static::$primaryKey => $id,
            ],
            'limit' => 1
        ];

        $data = self::select($condition);
        if ($data) {
            $model = get_called_class();
            return new $model($data[0]);
        } else {
            return null;
        }
    }

    /**
    * @desc __callStatic 
    *
    * @Param $method
    * @Param $args
    *
    * @return 
    */
    public static function __callStatic($method, $args) {
        if (strpos($method, 'findBy') ===0) {
            $method = substr($method, 6);
            $params = explode('And', $method);
            
            $index = 0;
            $where = [];
            $fields = array_flip(static::$fields);
            foreach ($params as $param) {
                $where[$fields[lcfirst($param)]] = $args[$index];
                $index++;
            }
            
            $data = [];
            $model = get_called_class();

            $condition['where'] = $where;
            $records = self::select($condition);
            foreach ($records as $record) {   
                array_push($data, new $model($record));    
            }
            
        } else {
            return null;
        }

        return $data;
    }

    /**
     * $condition = [
     *  'select' => '*',
     *  'where' => [
     *       'id' => 123,
     *       'id' => [1,2],
     *       'id >=' => 123,
     *       'id is not null' => null,
     *       'or id' => 123
     *   ],
     *  'limit' => 1,
     *  'offset' => 1,
     *  'group by' => 'id',
     *  'order by' => 'auto_id',
     * ];
     * @param $condition
     * @return mixed
     */
    public static function select($condition) {
        $sql = '';

        if ($condition['select']) {
            $sql = 'select ' . $condition['select'] . ' from ' . static::$table;
        } else {
            $sql = 'select * from ' . static::$table;
        }

        if ($condition['where']) {
            $sqlWhere = self::_buildWhereSql($condition['where']);
            $sql = $sql . ' ' . $sqlWhere['sql'];
        }

        if ($condition['group by']) {
            $sql = $sql . ' group by ' . $condition['group by'];
        }

        if ($condition['order by']) {
            $sql = $sql . ' order by ' . $condition['order by'];
        }

        if ($condition['limit']) {
            if ($condition['offset']) {
                $sql = $sql . ' limit ' . $condition['offset'] . ', ' . $condition['limit'];
            } else {
                $sql = $sql . ' limit ' . $condition['limit'];
            }
        }

        return self::query($sql, $sqlWhere['params']);
    }

    /**
    * @desc update 
    *
    * @Param $where
    * @Param $data
    *
    * @return 
    */
    public static function update($where, $data) {
        $sql = 'update ' . static::$table . ' set';
        $params = [];

        $firstFlag = true;
        foreach ($data as $key => $value) {
            if ($firstFlag) {
                $sql = $sql . ' ' . $key . ' = ?'; 
                $firstFlag = false;
            } else {
                $sql = $sql . ', ' . $key . ' = ?'; 
            }
            
            $params = array_merge($params, [$value]);
        }

        $sqlWhere = self::_buildWhereSql($where);
        $sql = $sql . ' ' . $sqlWhere['sql'];
        $params = array_merge($params, $sqlWhere['params']);

        return self::query($sql, $params);
    }

    /**
    * @desc insert 
    *
    * @Param $data
    *
    * @return 
    */
    public static function insert($data) {
        $sql = 'insert into ' . static::$table;
        $params = [];

        // 一维数组
        if (count($data) == count($data, 1)) {
            $firstFlag = true;
            foreach ($data as $key => $value) {
                if ($firstFlag) {
                    $firstFlag = false;
                    $sql = $sql . '(' . $key; 
                } else {
                    $sql = $sql . ', ' . $key; 
                    $insertSql = $insertSql . ',?';
                }
                $params[] = $value;
            }
            $sql = $sql . ') values (?' . str_repeat(',?', count($params) -1 ) . ')';
        } else {
            // 如果是二维数组，以第一条记录为准，以后每条记录都必须有第一条记录的所有字段
            $columns = [];
            $recordNum = 1;
            $firstFlag = true;
            foreach ($data[0] as $key => $value) {
                if ($firstFlag) {
                    $firstFlag = false;
                    $sql = $sql . '(' . $key; 
                    $params[] = $value;
                } else {
                    $sql = $sql . ', ' . $key; 
                    $params[] = $value;
                }
                $columns[] = $key;
            }

            array_shift($data);
            foreach ($data as $record) {
                foreach ($columns as $column) { 
                    if (isset($record[$column])) {
                        $params[] = $record[$column];
                    } else {
                        throw new Exception('插入多行数据不正确');
                    }
                }
                $recordNum++;
            }
            $sql = $sql . ') values (?' . str_repeat(', ?', count($columns) -1 ) . ')' . 
                str_repeat(', (?' . str_repeat(', ?', count($columns) -1 ) . ')', $recordNum - 1);
        }

        return self::query($sql, $params);
    }

    /**
    * @desc delete 
    *
    * @Param $where
    *
    * @return 
    */
    public static function delete($where) {
        $sqlWhere = self::_buildWhereSql($where);
    
        $sql = 'delete from ' . static::$table . ' ' . $sqlWhere['sql'];

        return self::query($sql, $sqlWhere['params']);
    }

    /**
    * @desc _buildWhereSql 
    *
    * @Param $where
    *
    * @return 
    */
    private static function _buildWhereSql($where) {
       if (!$where) {
           return '';
       }

       $sql = 'where 1';
       $params = [];

       foreach ($where as $key => $value) {
           if (is_null($value)) {
               $sql = $sql . ' and ' . $key;
               continue;
           }
           
           if (!is_array($value)) {
               if (strpos($key, 'or ') === false) {
                   $sql = $sql . ' and ';
               }

               if (strpos($key, '>')
                   || strpos($key, '>=')
                   || strpos($key, '<=')
                   || strpos($key, '<')) {
                   $sql = $sql . ' ' . $key . ' ?';
               } else {
                   $sql = $sql . ' ' . $key . ' = ?';
               }

               $params = array_merge($params, [$value]);
           } else {
               if (strpos($key, 'or ') !== false) {
                   $sql = $sql . ' ' . $key . ' in(?' . str_repeat(',?', count($value) - 1) . ')';
               } else {
                   $sql = $sql . ' and ' . $key . ' in(?' . str_repeat(',?', count($value) - 1) . ')';
               }
               $params = array_merge($params, $value);
           }
        }

        return [
            'sql' => $sql,
            'params' => $params
        ];
    }

    /**
    * @desc execSql 
    * 提供对外接口，专门直接执行sql 
    *
    * @Param $sql
    *
    * @return 
    */
    public static function execSql($sql) {

    }
    
    /**
    * @desc query 
    *
    * @Param $sql
    * @Param $params
    *
    * @return 
    */
    public static function query($sql, $params) {
        self::$lastQuery[static::$table] = [
            'sql' => $sql,
            'params' => $params
        ];

        return DB::instance(static::$table)->query($sql, $params);
    }

    /**
    * @desc getLastQuery 
    * 获取最后一条sql 
    *
    * @return 
    */
    public static function getLastQuery() {
        
        return self::$lastQuery[static::$table];
    }

    /**
    * @desc count 
    *
    * @Param $where
    *
    * @return 
    */
    public static function count($where = []) {

        $sql = 'select count(*) from ' . static::$table;

        if ($where) {
            $sqlWhere = self::_buildWhereSql($where);
            $sql = $sql . ' ' . $sqlWhere['sql'];
        }

        $sql = $sql . ' limit 1';

        return intval(self::query($sql, $sqlWhere['params'])[0]['count(*)']);
    }
}

