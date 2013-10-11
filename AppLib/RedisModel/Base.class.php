<?php
/**
 * Redis存储模型，方便统一做index处理。
 *
 * 模型使用hash进行存储。
 * 索引字段也使用hash进行存储，格式为:
 * {
 * 'primary_field' : expire 没有就为0否则为过期的timestamp
 * }
 */
class RedisModel_Base
{
    /**
     * 在Redis中的DB
     */
    protected $_db = 0;

    /**
     * redis key 前缀
     */
    protected $_prefix = NULL;

    /**
     * 该Model在Redis的名称
     */
    protected $_primary_field = NULL;

    /**
     * 用来建立索引的字段
     * 慎用，使用hash存储索引，读取的时候是一次读取完
     * 只能做一些小的归类查询，如果太大，请使用其它方法
     */
    protected $_index_field = array();

    protected $_expire = 0;

    public function __construct($primary_field_name, $prefix = NULL)
    {
        $this->_primary_field = $primary_field_name;
        if (is_null($prefix) || !is_string($prefix)) {
            $prefix = '';
        }
        $this->_prefix = $prefix;
    }

    /**
     * 设置值
     */
    public function set($primary_value, $datas = array(), $expire = NULL)
    {
        if (empty($primary_value) || empty($datas) || !is_array($datas)) {
            return FALSE;
        }

        $expire = is_null($expire) ? $this->_expire : $expire;
        $redis = $this->_getRedisClient();

        if ($expire) {
            $redis->multi();
        }

        //默认将主键加进去
        if (!isset($datas[$this->_primary_field])) {
            $datas[$this->_primary_field] = $primary_value;
        }

        $key = $this->_getPrimaryFieldKey($primary_value);
        $res = $redis->hMSet($key, $datas);
        if ($expire) {
            $redis->expire($key, $expire);
            $res = $redis->exec();
            $tmp_res = TRUE;
            foreach ($res as $bool) {
                $tmp_res = $tmp_res && $bool;
            }
            $res = $tmp_res;
        }
        if ($res) {
            //设置索引值
            $this->_setIndex($primary_value, $datas, $expire);
        }

        return $res;
    }

    /**
     * 获取值
     */
    public function get($primary_value, $fields = array())
    {
        if (empty($primary_value)) {
            return array();
        }

        $key = $this->_getPrimaryFieldKey($primary_value);
        $redis = $this->_getRedisClient();
        $ret = array();

        if (empty($fields)) {
            $ret = $redis->hGetAll($key);
        } else {
            $ret = $redis->hMGet($key, $fields);
        }

        return $ret;
    }


    /**
     * 根据索引字段获取数据
     */
    public function getByIndexField($field, $field_value)
    {
        $key = $this->_getIndexFieldKey($field, $field_value);
        $redis = $this->_getRedisClient();
        $primary_values = $redis->hGetAll($key);
        var_dump($primary_values);
        if (empty($primary_values)) {
            return array();
        }
        $time = time();
        $del_primary_values = array();
        $get_primary_values = array();

        //读取数据
        foreach ($primary_values as $pv => $expire) {
            if ($expire != 0 && $expire < $time) {
                $del_primary_values[] = $pv;
            } else {
                $get_primary_values[] = $pv;
            }
        }

        $ret = array();
        if (count($get_primary_values) > 0) {
            $redis->multi();
            foreach ($get_primary_values as $pv) {
                $key = $this->_getPrimaryFieldKey($pv);
                $redis->hGetAll($key);
            }
            $ret = $redis->exec();
            $ret = Helper_Array::changeKey($ret, $this->_primary_field);
        }

        if (count($del_primary_values) > 0) {
            foreach ($del_primary_values as $pv) {
                unset($primary_values[$pv]);
            }

            $redis->multi();
            $redis->delete($key);
            $redis->hMSet($key, $primary_values);
            $redis->exec();
        }

        return $ret;
    }

    protected function _setIndex($primary_value, $datas, $expire = 0)
    {
        $time = time();
        if ($expire > 0) {
            $expire = $expire + $time;
        }

        foreach ($this->_index_field as $index_field) {
            if (!isset($datas[$index_field])) {
                continue;
            }
            $index_data = $datas[$index_field];
            $key = $this->_getIndexFieldKey($index_field, $index_data);
            $redis = $this->_getRedisClient();
            $res = $redis->hSet($key, $primary_value, $expire);
        }
        return;
    }

    protected function _getPrimaryFieldKey($primary_value)
    {
        $key = $this->_prefix . $this->_primary_field . $primary_value;
        return $key;
    }

    protected function _getIndexFieldKey($index_field, $index_data)
    {
        $key = $this->_prefix . $this->_primary_field . $index_field . $index_data;
        return $key;
    }

    protected function _getRedisClient()
    {
        return Cache_Redis::getInstanceFromConfigByDB($this->_db);
    }
}
