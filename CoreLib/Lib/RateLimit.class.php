<?php
require_once('RateLimit/OACounter.class.php');
require_once('RateLimit/OAMemcacheStoreEngine.class.php');

class Lib_RateLimit
{
    protected $_counter_store_engine = NULL;
    protected $_limit_store_engine = NULL;


    protected $_counter = NULL;

    protected $_time_scope = 1800;//默认半小时
    protected $_count_limit = 18000;//在time_scope范围内限制的次数

    protected $_page_cache_limit_data = NULL;

    //当前频率限制名称
    protected $_name = NULL;

    /**
     * limit_store_engine 必须实现以下方法
     * delete
     * set
     */
    public function __construct($name, $_limit_store_engine, $_counter_store_engine)
    {
        $this->_name = $this->_getRateLimitName($name);
        $this->_limit_store_engine = $_limit_store_engine;
        $this->_counter_store_engine = $_counter_store_engine;
        $this->_counter = new OACounter($this->_counter_store_engine);
    }

    public function timeScope($time_scope = NULL)
    {
        if (!is_null($time_scope)) {
            $this->_time_scope = (int) $time_scope;
        }
        return $this->_time_scope;
    }

    public function countLimit($count_limit = NULL)
    {
        if (!is_null($count_limit)) {
            $this->_count_limit = (int) $count_limit;
        }
        return $this->_time_scope;
    }

    /**
     * 增加值,如果超出count_limit 返回 FALSE
     * 返回当前值
     */
    public function add($val = 1)
    {
        $name = $this->_name;
        $limit_data = $this->_getLimitDataWithAutoResetCounter();
        if (empty($limit_data)) {
            return 0;//有问题不会导致前面出错误
        }

        list($update_time, $count_limit) = $limit_data;
        $time = time();
        if ($update_time <= $time) {
            //该更新数据了
            $this->_resetLimitData();
            $this->_counter->reset($name);
            return 0;
        } else {
            //检查是否超值
            $count = $this->_counter->increment($name);
            if ($count === FALSE) {
                $this->_counter->reset($name);
                return 0;
            }

            if ($this->_count_limit < $count) {
                return FALSE;
            }else {
                return $count;
            }
        }
    }

    /**
     * 增加值,如果小于count_limit 返回 FALSE
     * 返回当前值
     */
    public function sub($val = 1)
    {
        $name = $this->_name;
        $limit_data = $this->_getLimitDataWithAutoResetCounter();
        if (empty($limit_data)) {
            return 0;//有问题不会导致前面出错误
        }

        list($update_time, $count_limit) = $limit_data;
        $time = time();
        if ($update_time <= $time) {
            //该更新数据了
            $this->_resetLimitData();
            $this->_counter->reset($name);
            return 0;
        } else {
            //检查是否超值
            $count = $this->_counter->decrement($name);
            if ($count === FALSE) {
                $this->_counter->reset($name);
                return 0;
            }
            if ($count_limit > $count) {
                return FALSE;
            }else {
                return $count;
            }
        }
    }

    /**
     * 重新计数
     */
    public function reset()
    {
        $this->_resetLimitData();
        $this->_counter->reset($this->_name);
    }


    /**
     *
     * 如果存储获取limitdata失败，resetlimitdata后，重置counter,
     * 保证第一次使用时counter有值，不会出现inc错误
     */
    protected function _getLimitDataWithAutoResetCounter()
    {
        if (is_array($this->_page_cache_limit_data)) {
            $this->_page_cache_limit_data;
        }

        $limit_data = $this->_getLimitData();
        if (!is_array($limit_data)) {
            $limit_data = $this->_resetLimitData();
            //同时重置counter
            $this->_counter->reset($this->_name);
        }

        if ($limit_data) {
            $this->_page_cache_limit_data = $limit_data;
        } else {
            return FALSE;
        }

        return $limit_data;
    }

    /**
     * 获得限制相关信息
     * array(
     *   更新时间,
     *   频率限制
     * )
     */
    protected function _getLimitData()
    {
        $limit_data = $this->_limit_store_engine->get($this->_name);
        return $limit_data;
    }

    protected function _resetLimitData()
    {
        $this->_page_cache_limit_data = NULL;
        $t = time();
        $update_t = $t + $this->_time_scope;
        $count_limit = $this->_count_limit;
        $data = array(
            $update_t, $count_limit
        );
        $ret = $this->_limit_store_engine->set($this->_name, $data);
        if ($ret) {
            return $data;
        } else {
            return FALSE;
        }
    }

    protected function _getRateLimitName($name)
    {
        return '.rl' . $name;
    }
}
