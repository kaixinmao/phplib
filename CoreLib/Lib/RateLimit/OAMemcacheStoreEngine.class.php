<?php
class OAMemcacheStoreEngine
{
    const DEFAULT_EXPIRE_TIME = 172800;//默认两天
    protected $_servers = array();

    protected $_conn = NULL;

    protected $_expire_time = self::DEFAULT_EXPIRE_TIME;

    /**
     * servers 参数,数组
     * array(
     *  '127.0.0.1:11211',''
     * )
     * 如果时字符串，逗号分隔
     */
    public function __construct($servers)
    {
        if (is_string($servers)) {
            $servers = explode(',', $servers);
        }

        if (!empty($servers)) {
            $this->_servers = $servers;
            $this->_connect();
        }
    }

    public function expireTime($time = NULL)
    {
        if (!is_null($time)) {
            $this->_expire_time = $time;
        }
        return $this->_expire_time;
    }

    private function _connect()
    {
        if (!is_null($this->_conn)) {
            $this->_conn->close();
        }

        $this->_conn = new Memcache;
        foreach ($this->_servers as $sv) {
            list($ip, $port) = explode(':', $sv);
            if(!$this->_conn->addServer($ip, $port)) {
                $this->_conn = NULL;
                break;
            }
        }
    }

    public function get($key)
    {
        return $this->_conn->get($key);
    }

    public function reset($key)
    {
        return $this->_conn->set($key, 0, 0, $this->_expire_time);
    }

    public function delete($key)
    {
        return $this->_conn->delete($key);
    }

    public function increment($key, $val = 1)
    {
        return $this->_conn->increment($key, $val);
    }

    public function decrement($key, $val)
    {
        return $this->_conn->decrement($key, $val);
    }

    public function set($key, $val = 0)
    {
        return $this->_conn->set($key, $val, 0, $this->_expire_time);
    }
}
