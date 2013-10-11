<?php
/**
 * client选择算法：
 * key,md5取hex值，最后一个字的ascii码 与服务器数量取mod
 */
class Cache_Redis
{
    //保存现有的链接
    protected $_clients = array();

    protected $_server_configs  = array();

    protected $_db = 0;//默认的db

    public function __construct($db = NULL)
    {
        if (is_int($db)) {
            $this->_db = $db;
        }
    }

    public function addServer($host, $port)
    {
        $this->_server_configs[] = array(
            'host' => $host,
            'port' => $port,
        );
    }

    public function addServers($servers)
    {
        foreach ($servers as $s) {
            if (!isset($s['host']) || !isset($s['port'])) {
                continue;
            }
            $this->addServer($s['host'], $s['port']);
        }
    }

    public function __call($name, $params)
    {
        $key = NULL;
        if (!empty($params)) {
            $key = $params[0];
        }
        $client = $this->_getClientByKey($key);
        if ($client && is_callable(array($client, $name))) {
            return call_user_func_array(array($client, $name), $params);
        } else {
            return FALSE;
        }
    }

    //根据key算出一个int,方便获取client,mod是取模的值，默认时client数量
    protected function _keyToClientIndex($key)
    {
        $mod = count($this->_server_configs);

        $index = ord(substr(md5($key, TRUE), -1));
        if ($index == 0) {
            return 0;
        }

        $index = ord(substr(md5($key, TRUE), -1)) % $mod;

        //获取真正server_config中的index
        $keys = array_keys($this->_server_configs);
        $index = $keys[$index];
        return $index;
    }

    protected function _getClientByKey($key = NULL)
    {
        $index = 0;
        if (!is_null($key)) {
            $index = $this->_keyToClientIndex($key);
        } else {
            $index = mt_rand(0, count($this->_server_configs) - 1);
        }

        $client = $this->_getClient($index);
        if (!$client && count($this->_server_configs) > 0) {
            $client = $this->_getClientByKey($key);//递归直到配置没有了
        }

        return $client;
    }

    protected function _getClient($index)
    {
        if (isset($this->_clients[$index]) && is_object($this->_clients[$index])) {
            return $this->_clients[$index];
        }

        //连接redis
        if (!isset($this->_server_configs[$index])) {
            return FALSE;
        }

        $server_config = $this->_server_configs[$index];
        $client = new Redis();
        if (!$client->connect($server_config['host'], $server_config['port'] , 2.5)) {
            //XXX redis 挂了，应该报警的,去掉该配置，后面请求就用其它的配置了，如果没有就会返回FALSE
            unset($this->_server_configs[$index]);
            return FALSE;
        }

        $client->select($this->_db);
        $this->_clients[$index] = $client;

        return $client;
    }


    public function __destruct()
    {
        if (!empty($this->_clients)) {
            foreach ($this->_clients as $client) {
                $client->close();
            }
        }
    }

    public static function getInstanceFromConfigByDB($db = NULL)
    {
        static $instances = array();
        if (!isset($instances[$db])) {
            $instances[$db] = new Cache_Redis($db);
            $server_configs = Config::get('REDIS_CONFIG');
            if (!empty($server_configs)) {
                $instances[$db]->addServers($server_configs);
            }
        }

        return $instances[$db];
    }
}
