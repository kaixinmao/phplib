<?php
class Helper_Form
{
    const FORM_HASH_NAME = 'form_hash_name';
    const FORM_HASH = 'form_hash';
    const FORM_HASH_SALT = '1qwyre^&*fh293gb^^&(HVjs';

    /**
     * 为表单产生一个供验证的hash
     */
    public static function formHash($name, $uid = 0, $html = TRUE)
    {
        if (empty($uid)) {
            $uid = '';
        }
        $key_str = date('Y-m-d') . $name . self::FORM_HASH_SALT . "-{$uid}";
        $hash_str = sha1($key_str);
        if ($html) {
            $html_code = '<input type="hidden" name="' . self::FORM_HASH_NAME . '" value="' . $name . '" />';
            $html_code .= '<input type="hidden" name="' . self::FORM_HASH. '" value="' . $hash_str . '" />';
            return $html_code;
        } else {
            return $hash_str;
        }
    }

    /**
     * 检查FormHash是否正确，防止跨站提交
     */
    public static function checkFormHash($name, $uid = 0)
    {
        $get_hash_str = isset($_GET[self::FORM_HASH]) ? $_GET[self::FORM_HASH] : '';
        $post_hash_str = isset($_POST[self::FORM_HASH]) ? $_POST[self::FORM_HASH] : '';
        $hash_str = !empty($get_hash_str) ? $get_hash_str : $post_hash_str;

        $now_hash_str = self::formHash($name, $uid, FALSE);

        return $hash_str == $now_hash_str;
    }
}
