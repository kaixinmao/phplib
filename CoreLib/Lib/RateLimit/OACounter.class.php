<?php
/**
 * OA 的计数器功能
 */
class OACounter
{
    /**
     * 计数存储engine，必须实现方法:
     * reset 重置为0
     * delete 删除某个计数
     * increment 增加
     * decrement 减少
     * set 设置某值
     */
    protected $_store_engine = NULL;

    protected $_space = 'oacount';//counter号的命名空间

    //步进值
    protected $_step = 1;


    public function __construct($engine, $space = 'oacount')
    {
        $this->_store_engine = $engine;
    }

    public function step($step = NULL)
    {
        if (!is_null($step)) {
            $this->_step = (int) $step;
        }

        return $this->_step;
    }

    //初始化为0
    public function reset($name)
    {
        return $this->_store_engine->reset($this->_getCounterName($name));
    }

    public function delete($name)
    {
        return $this->_store_engine->delete($this->_getCounterName($name));
    }

    public function increment($name, $val = NULL)
    {
        $val = is_null($val) ? $this->_step : $val;
        $name = $this->_getCounterName($name);
        return $this->_store_engine->increment($name, $val);
    }

    public function decrement($name, $val = NULL)
    {
        $val = is_null($val) ? $this->_step : $val;
        return $this->_store_engine->decrement($this->_getCounterName($name), $val);
    }

    public function set($name, $val = 0)
    {
        return $this->_store_engine->set($this->_getCounterName($name), $val);
    }

    protected function _getCounterName($name)
    {
        return $this->_space . $name;
    }
}
