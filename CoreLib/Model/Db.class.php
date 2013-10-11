<?php

/**
 * 数据库模型基类
 */
class Model_Db extends Model_Base 
{
    protected static $_forceReadOnMaster = FALSE;

    protected $_readOnMaster = FALSE;
    protected $_table = NULL;
    protected $_db_zone_name = NULL;//数据库配置名称
    protected $_db_master_name = 'main';
    protected $_db_read_name = 'query';

    protected $_dberrcode = 0;
    protected $_dberrinfo = array();

    private $_db_instance = array();//数据库操作类

    protected $_sql_maker = NULL;


    public function __construct($table = NULL, $db_zone_name = NULL)
    {
        parent::__construct();
        $this->_table = $table;
        $this->_db_zone_name = $db_zone_name;
        $this->_sql_maker = Lib_PDOSqlMaker::getInstance();
    }

    public static function setForceReadOnMaster($bool = TRUE)
    {
        Model_Db::$_forceReadOnMaster = $bool;
    }

    //返回当前PDO数据库链接
    public function getConnection($is_read = TRUE)
    {
        return $this->_getDbInstance($is_read);
    }

    public function table($table = NULL)
    {
        if (!empty($table)) {
            $this->_table = $table;
        }

        return $this->_table;
    }

    public function getLastId()
    {
        $db = $this->_getDbInstance(FALSE);//write
        if (!$db) {
            return FALSE;
        }

        return $db->lastInsertId(); 
    }

    public function insert($ins_arr, $return_last_id = TRUE)
    {
        if (empty($this->_table)) {
            return FALSE;
        }

        $db = $this->_getDbInstance(FALSE);//write
        if (!$db) {
            return FALSE;
        }
        list($insert_sql, $bind_attrs) = $this->_sql_maker->insert($ins_arr);
        $sql = "INSERT {$this->_table}" . $insert_sql;

        $log_sql = 'Sql:' . $sql . '***' . json_encode($bind_attrs);
        Logging::logMessage($log_sql, Logging::LEVEL_DEBUG);

        $sth = $db->prepare($sql);
        $ret = $sth->execute($bind_attrs);
        $this->_dberrcode = $sth->errorCode();
        $this->_dberrinfo = $sth->errorInfo();
        if ($ret === FALSE) {
            $err = $this->_dberrinfo;
            Logging::logMessage('DB_ERROR code:' . $this->_dberrcode . ' error:' . $err[2], Logging::LEVEL_ERROR);
            Logging::logMessage($log_sql, Logging::LEVEL_ERROR);
            return FALSE;
        }

        $last_id = 0;
        if ($return_last_id) {
            $last_id = $db->lastInsertId();
        }

        if ($return_last_id) {
            $ins_arr['last_id'] = $last_id;
        }
        $this->_afterInsert($ins_arr, $return_last_id);

        if ($return_last_id) {
            return $last_id;
        } else {
            return $ret;
        }
    }

    public function update($where, $upt_arr)
    {
        if (empty($this->_table)) {
            return FALSE;
        }

        $db = $this->_getDbInstance(FALSE);//write
        if (!$db) {
            return FALSE;
        }

        list($update_sql, $update_bind_attrs) = $this->_sql_maker->update($upt_arr);
        list($where_sql, $where_bind_attrs) = $this->_sql_maker->where($where);
        $sql = 'UPDATE ' . $this->_table . $update_sql . $where_sql;

        $log_sql = 'Sql:' . $sql . '***' . json_encode($update_bind_attrs) . '***' . json_encode($where_bind_attrs);
        Logging::logMessage($log_sql, Logging::LEVEL_DEBUG);
        $sth = $db->prepare($sql);
        $ret = $sth->execute(array_merge($update_bind_attrs, $where_bind_attrs));

        $this->_dberrcode = $sth->errorCode();
        $this->_dberrinfo = $sth->errorInfo();

        if ($ret) {
            $this->_afterUpdate($where);
        } else {
            $err = $this->_dberrinfo;
            Logging::logMessage('DB_ERROR code:' . $this->_dberrcode . ' error:' . $err[2], Logging::LEVEL_ERROR);
            Logging::logMessage($log_sql, Logging::LEVEL_ERROR);
        }

        return $ret;
    }

    public function delete ($where)
    {
        if (empty($this->_table)) {
            return FALSE;
        }
        
        $db = $this->_getDbInstance(FALSE);
        if (! $db) {
            return FALSE;
        }
        
        list($where_sql, $where_bind_attrs) = $this->_sql_maker->where($where);
        $sql = 'DELETE FROM ' . $this->_table . $where_sql;

        $log_sql = 'Sql:' . $sql . '***' . json_encode($where_bind_attrs);
        
        Logging::logMessage($log_sql, Logging::LEVEL_DEBUG);
        $sth = $db->prepare($sql);
        $ret = $sth->execute($where_bind_attrs);

        $this->_dberrcode = $sth->errorCode();
        $this->_dberrinfo = $sth->errorInfo();

        if ($ret) {
            $this->_afterDelete($where);
        } else {
            $err = $this->_dberrinfo;
            Logging::logMessage('DB_ERROR code:' . $this->_dberrcode . ' error:' . $err[2], Logging::LEVEL_ERROR);
            Logging::logMessage($log_sql, Logging::LEVEL_ERROR);
        }

        return $ret;
    }

    public function select ($where = array(), $attrs = array())
    {
        if (empty($this->_table)) {
            return FALSE;
        }
        
        $db = $this->_getDbInstance(TRUE);//READ
        if (! $db) {
            return FALSE;
        }
        
        $select_fields = isset($attrs['select']) ? $attrs['select'] : '*';
        
        list($where_sql, $where_bind_attrs) = $this->_sql_maker->where($where, $attrs);
        $sql = "SELECT {$select_fields} FROM " . $this->_table . $where_sql;
        
        $log_sql = 'Sql:' . $sql . '***' . json_encode($where_bind_attrs);
        Logging::logMessage($log_sql, Logging::LEVEL_DEBUG);

        $sth = $db->prepare($sql);
        $ret = $sth->execute($where_bind_attrs);
        $this->_dberrcode = $sth->errorCode();
        $this->_dberrinfo = $sth->errorInfo();
        if ($ret === FALSE) {
            $err = $this->_dberrinfo;
            Logging::logMessage('DB_ERROR code:' . $this->_dberrcode . ' error:' . $err[2], Logging::LEVEL_ERROR);
            Logging::logMessage($log_sql, Logging::LEVEL_ERROR);
            return FALSE;
        } else {
            return $sth->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function selectOne ($where = array(), $attrs = array())
    {
        $attrs['limit'] = 1;
        $attrs['offset'] = 0;
        
        $res = $this->select($where, $attrs);
        if ($res === FALSE) {
            return FALSE;
        }

        if (empty($res)) {
            return NULL;
        }

        return array_pop($res);
    }

    public function selectCount ($where = array(), $attrs = array())
    {
        if (! isset($attrs['select'])) {
            $attrs['select'] = 'COUNT(0)';
        }
        $attrs['select'] .= ' AS `total`';
        
        $res = $this->selectOne($where, $attrs);
        if ($res === FALSE) {
            return FALSE;
        }
        return intval($res['total']);
    }

    protected function _statementBindParams($sth, $bind_attrs)
    {
        if (empty($bind_attrs)) {
            return;
        }
        foreach ($bind_attrs as $name => $val) {
            $sth->bindValue($name, $val);
        }
    }

    protected function _getDbInstance($is_read = TRUE)
    {
        $db_choose_name = $this->_db_master_name;

        if (Model_Db::$_forceReadOnMaster || $this->_readOnMaster) {
            $is_read = FAlSE;
        }

        if ($is_read) {
            $db_choose_name = $this->_db_read_name;
        }

        if (isset($this->_db_instance[$db_choose_name]) && is_object($this->_db_instance[$db_choose_name])) {
            return $this->_db_instance[$db_choose_name];
        }

        //获得一个db
        if (empty($this->_db_zone_name)) {
            return NULL;
        }

        $db_config = Config::get('DB_CONFIG'); 
        if (!isset($db_config[$this->_db_zone_name][$db_choose_name])) {
            return NULL;
        }
        $db_config = $db_config[$this->_db_zone_name][$db_choose_name];
        if ($db_choose_name == $this->_db_read_name) {
            $query_count = count($db_config);
            if ($query_count > 1) {
                $i = mt_rand(0, count($db_config) - 1);
                $db_config = $db_config[$i];
            } else {
                $db_config = $db_config[0];
            }
        }
        $dsn = "mysql:host={$db_config['host']};dbname={$db_config['database']};";
        if (isset($db_config['charset'])) {
            $dsn .= "charset={$db_config['charset']}";
        }

        $db = FALSE;
        try {
            $db = new PDO($dsn, $db_config['user'], $db_config['password']);
        } catch (Exception $e) {
            $db = FALSE; 
        }

        if (!empty($db)) {
            $this->_db_instance[$db_choose_name] = $db;
            return $db;
        } else {
            Logging::logMessage('DB_ERROR new PDO error! dsn:' . $dsn, Logging::LEVEL_FATAL);
            return FALSE;
        }
    }

    //根据错误号判断是否出现了重复的错误
    public function isDuplicateError()
    {
        $duplicate_dberrcode = 1062;
        $err = $this->dbError();
        if ($this->_dberrcode == $duplicate_dberrcode) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    //返回数据库错误
    public function dbError($is_read = TRUE)
    {
        $db = $this->_getDbInstance($is_read);
        $err = $db->errorInfo();
        $ret = array(
            'code' => $this->_dberrcode,
            'msg' => implode(',', $this->_dberrinfo),
        );
        return $ret;
    }

    protected function _afterInsert($ins, $return_last_id = TRUE)
    {
    }

    protected function _afterUpdate($where)
    {
    }

    protected function _afterDelete($where)
    {
    }
}
