<?php
/**
 * Memcache类的二次封装，方便从配置中直接拿到
 */
class Cache_Memcached
{
    protected static $_objList;
    private $_memcache;
    private $_config_name;
    

    private function __construct($config_name)
    {
        $this->_config_name = $config_name;
    }

    private function init()
    {

        $memcacheHosts = Config::get($this->_config_name);
        $memcacheHosts = explode(',', $memcacheHosts);

        if (empty($memcacheHosts)) {
            return FALSE;
        } else {
            $this->_memcache = new Memcache;
            foreach ($memcacheHosts as $m) {
                list($host, $port) = explode(':', $m);
                $this->_memcache->addServer($host, $port);
            }
        }
    }
    
    /**
     * 获取一个Cache_Memcache实例
     *
     * @param integer $clusterId cluster id
     * @return object
     */
    public static function getInstance($config_name = 'DefaultMemcacheServers')
    {
        if (empty(self::$_objList[$config_name])) {
            $obj = new self($config_name);
            $obj->init();
            self::$_objList[$config_name] = &$obj;
        }

        return self::$_objList[$config_name];
    }
    
    /**
     * 获取缓存的静态方法
     *
     * @param string|array $key
     * @param integer $clusterId cluster id
     * @return mixed
     */
    public static function sGet($key)
    {
        $instance = self::getInstance();
        return $instance->get($key);
    }
    
    /**
     * 设置缓存的静态方法
     *
     * @param string $key
     * @param mixed $val
     * @param integer $expires 过期时间（秒），0为永不过期
     * @param integer $clusterId cluster id
     * @return boolean
     */
    public static function sSet($key, $val, $expires = 0)
    {
        $instance = self::getInstance();
        return $instance->set($key, $val, $expires);
    }
    
    
    /**
     * 删除缓存数据的静态方法
     * 
     * @param string $key
     * @param integer $clusterId
     * @return boolean
     */
    public static function sDelete($key)
    {
        $instance = self::getInstance();
        return $instance->delete($key);    
    }
    
    /**
     * 增加缓存的静态方法
     * 
     * @param string $key
     * @param mixed $val
     * @param integer $expires
     * @param integer $clusterId
     * @return boolean
     */
    public static function sAdd($key, $val, $expires = 0)
    {
        $instance = self::getInstance();
        return $instance->add($key, $val, $expires);
    }
    
    /**
     * 获取缓存数据
     *
     * @param string $key
     * @return mixed 
     */
    public function get($key)
    {
        if (empty($this->_memcache)) {
            if ( ! $this->init()) {
                return FALSE;
            }
        }

        $res = $this->_memcache->get($key);
        return $res;    
    }
    
    /**
     * 写入缓存
     *
     * @param string  $key  数据对应的键名
     * @param mixed   $val  数据
     * @param integer $expires 缓存的时间（秒），设置为0表示永不过期
     * @return boolean
     */
    public function set($key, $val, $expires = 0)
    {
        if (empty($this->_memcache)) {
            if ( ! $this->init()) {
                return FALSE;
            }
        }
        if (is_numeric($val)) {
            $val = (string) $val;
        }

        $ret = $this->_memcache->set($key, $val, 0, $expires);
        
        return $ret;
    }
    
    /**
     * 删除缓存
     *
     * @param string $key 数据的键名
     * @return boolean
     */
    public function delete($key)
    {
        if (empty($this->_memcache)) {
            if ( ! $this->init()) {
                return FALSE;
            }
        }
        
        $ret = $this->_memcache->delete($key, 0);

        return $ret;
    }
    
    /**
     * 增加item的值
     *
     * @param string $key
     * @param integer $val
     * @return boolean
     */
    public function increment($key, $val = 1)
    {
        if (empty($this->_memcache)) {
            if ( ! $this->init()) {
                return FALSE;
            }
        }

        $ret = $this->_memcache->increment($key, $val);
        return $ret;
    }
    
    /**
     * 写入缓存当且仅当$key对应缓存不存在的时候
     *
     * @param string $key
     * @param mixed  $val
     * @param integer $expires
     * @return boolean
     */
    public function add($key, $val, $expires = 0)
    {
        if (empty($this->_memcache)) {
            if ( ! $this->init()) {
                return FALSE;
            }
        }

        $ret = $this->_memcache->add($key, $val, $expires);
        return $ret;
    }
    
    /**
     * 刷新
     *
     * @return boolean
     */
    public function flush()
    {
        if (empty($this->_memcache)) {
            if ( ! $this->init()) {
                return FALSE;
            }
        }

        $ret = $this->_memcache->flush();
        return $ret;
    }
}
