<?php
class Helper_Array
{
    static function partialCopy($source, $keys)
    {
        $dest = array();
        foreach ($source as $key => $val) {
            if (in_array($key, $keys)) {
                $dest[$key] = $val;
            }
        }

        return $dest;
    } 
}
