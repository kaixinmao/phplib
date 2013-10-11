<?php
class Helper_Date
{
    public static function Str($time_stamp, $format = 'Y-m-d H:i:s')
    {
        return date($format, $time_stamp);
    }
}
