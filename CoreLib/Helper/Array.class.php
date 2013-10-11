<?php
class Helper_Array
{

    public static function changeKey($arr, $column)
    {
        if (empty($arr)) {
            return $arr;
        }
        $newArr = array();

        foreach ($arr as &$val) {
            $newArr[$val[$column]] = &$val;
        }
        $arr = $newArr;
        return $arr;
    }

    public static function partialCopy($source, $keys)
    {
        $dest = array();
        foreach ($source as $key => $val) {
            if (in_array($key, $keys)) {
                $dest[$key] = $val;
            }
        }

        return $dest;
    } 

    public static function getColumn($arr, $column)
    {
        if (empty($arr)) {
            return array();
        }

        $res = array();
        foreach ($arr as &$val) {
            $res[] = $val[$column];
        }
        return $res;
    }

    /**
     * 去掉指定值, 必须连类型都相同才能去掉
     */
    public static function strip(&$arr, $s = NULL)
    {
        if (empty($arr) || !is_array($arr)) {
            return;
        }

        $unset_keys = array();

        foreach ($arr as $k => $v) {
            if ($v === $s) {
                $unset_keys[] = $k;
            }
        }

        foreach ($unset_keys as $k) {
            unset($arr[$k]);
        }

        return $arr;
    }

    /**
     * 检查是否全部的数据都是数字的
     * 空数组会返回TRUE
     */
    public static function isNumeric($arr)
    {
        if (!is_array($arr)) {
            return FALSE;
        }

        foreach ($arr as $v) {
            if (!is_numeric($v)) {
                return FALSE;
            }
        }
        return TRUE;
    }

    public static function sum($arr = array())
    {
        $ret = 0;
        foreach ($arr as $a)
        {
            $ret += (int) $a;
        }

        return $ret;
    }

    /**
     * 简单的数组获取值
     */
    public static function get($arr, $key, $default = NULL)
    {
        if (isset($arr[$key])) {
            return $arr[$key];
        } else {
            return $default;
        }
    }

    /**
     * 有时候希望数组值变为key，方便检测是否存在，能够加快查找速度
     * 极端情况下in_array可能性能会时O(N) N是array大小
     *
     * XXX 不对val做检查
     */
    public static function valToKeyWithDefaultVal($arr, $default = TRUE)
    {
        $ret = array();
        foreach($arr as $v) {
            $ret[$v] = $default;
        }

        return $ret;
    }
}
