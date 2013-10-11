<?php
class Helper_View
{
    /**
     * 对字符串输出做html相关转义
     */
    public static function tr($string)
    {
        $s = htmlspecialchars($string);
        return $s;
    }

    public static function toSelectOptions($rows, $default_value = 0, $attr = NULL)
    {
        $html = '';
        if (empty($rows)) {
            return $html;
        }
        foreach ($rows as $val => $k) {
            $selected = '';
            if ($val == $default_value) {
                $selected = 'selected';
            }
            $attr_str = '';
            if (!empty($attr)) {
                $attr_str = "{$attr}";
            }

            $k = self::tr($k);
            $html .= "<option value=\"{$val}\" {$attr} {$selected}>{$k}</option>\n";
        }
        return $html;
    }
}
