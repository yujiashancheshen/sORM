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

    private static $lastQuery;

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

    public static function count($where) {

    }

    public function getByIdAndNameOrSex($id, $name, $sex) {

    }

    public function listByIdAndNameOrSex($id, $name, $sex) {

    }
}

