<?php

/**
 * 数据库模型基类
 */
class Model_CacheDb extends Model_Db
{
    CONST CACHE_TIME = 86400;
    CONST CACHE_KEY_PREFIX = 'SQL_';

    //是否开启更新cache的trigger,关闭后更新数据不会更新缓存
    public $enable_update_cache_trigger = TRUE;
    protected $_cache_key_prefix = self::CACHE_KEY_PREFIX;
    protected $_primary_id = NULL;
    protected $_cache = NULL;
    protected $_cache_time = self::CACHE_TIME;

    public function __construct($table = NULL, $db_zone_name = NULL)
    {
        parent::__construct($table, $db_zone_name);
        $this->_cache = $this->_getMemcache();
    }


    public function getItem($id)
    {
        $items = $this->getItems($id);
        if (!empty($items)) {
            return $items[$id];
        } else {
            return $items;
        }
    }

    /**
     * 根据主键获取值
     */
    public function getItems($ids, $use_cache = TRUE)
    {
        if (empty($ids)) {
            return array();
        }

        if (!is_array($ids)) {
            $ids = array($ids);
        }

        $select_ids = array();
        $items = array();
        if ($use_cache) {
            $cache_items = $this->_getItemsByCache($ids, $select_ids);
            if (empty($select_ids)) {
                return $cache_items;
            }
            if (!empty($cache_items)) {
                $items = $cache_items;
            }
        } else {
            $select_ids = $ids;
        }

        //数据库查询
        $select_items = $this->select(array(
            $this->_primary_id => $select_ids
        ));

        if (empty($select_items)) {
            return $items;
        }

        $select_items = Helper_Array::changeKey($select_items, $this->_primary_id);
        if ($use_cache) {
            $this->_setItemsCache($select_items);
        }
        $items = $items + $select_items;
        return $items;
    }

    public function clearItems($ids)
    {
        $this->_clearItemCache($ids);
    }

    /**
     * 查询
     * $cache_time = 0不使用缓存
     */
    public function select($where = array(), $attrs = array(), $cache_time = 0)
    {
        $use_cache = $cache_time > 0;
        $datas = array();
        $cacke_key = NULL;
        if ($use_cache) {
            ksort($where);
            ksort($attrs);
            $cache_key = $this->_conditionToCacheKey($where, $attrs);
            $datas = $this->_cache->get($cache_key);
            if (!empty($datas)) {
                return $datas;
            }
        }

        $datas = parent::select($where, $attrs);
        if (empty($datas)) {
            return $datas;
        }

        $datas = Helper_Array::changeKey($datas, $this->_primary_id);
        if ($use_cache) {
            $this->_cache->set($cache_key, $datas, $cache_time);
        }

        return $datas;
    }


    /**
     * 从缓存获取数据，并赋值哪些没能命中
     */
    protected function _getItemsByCache($ids, &$uncached_ids = array())
    {
        $items = array();
        $cache_keys = $this->_primaryIdToCacheKey($ids);
        foreach ($cache_keys as $id => $ck) {
            $item = $this->_cache->get($ck);
            if (empty($item)) {
                $uncached_ids[] = $id;
            } else {
                $items[$id] = $item;
            }
        }

        return $items;
    }

    protected function _clearCacheByCondtion($where)
    {
        if (isset($where[$this->_primary_id])) {
            $this->_clearItemCache($where[$this->_primary_id]);
            return;
        }

        //查询出id，清空缓存
        $items = $this->select($where, array(
            'select' => $this->_primary_id
        ));
        if (empty($items)) {
            return;
        }
        $ids = Helper_Array::getColumn($items, $this->_primary_id);
        $this->_clearItemCache($ids);
    }


    protected function _clearItemCache($ids)
    {
        $cache_keys = $this->_primaryIdToCacheKey($ids);
        foreach ($cache_keys as $id => $ck) {
            $this->_cache->delete($ck);
        }
    }

    protected function _setItemsCache($items)
    {
        $ids = array_keys($items);
        $cache_keys = $this->_primaryIdToCacheKey($ids);
        foreach ($cache_keys as $id => $ck) {
            $ret= $this->_cache->set($ck, $items[$id], $this->_cache_time);
        }
        return;
    }

    private function _primaryIdToCacheKey($ids)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }

        $ret = array();
        foreach ($ids as $id) {
            $ret[$id] = $this->_cache_key_prefix . sha1($this->_cache_key_prefix . $this->_table . $this->_db_zone_name . $id);
        }

        return $ret;
    }

    /**
     * 查询条件到缓存key
     */
    private function _conditionToCacheKey($where, $attr = array())
    {
        return $this->_cache_key_prefix . sha1($this->_cache_key_prefix . $this->_table . $this->_db_zone_name . $this->_sql_maker->where($where, $attr));
    }


    protected function _getMemcache()
    {
        static $cache = NULL;
        if (!is_object($cache)) {
            $cache = Cache_Memcached::getInstance();
        }
        return $cache;
    }

    protected function _afterUpdate($where)
    {
        $this->_clearCacheByCondtion($where);
    }

    protected function _afterDelete($where)
    {
        $this->_clearCacheByCondtion($where);
    }
}
