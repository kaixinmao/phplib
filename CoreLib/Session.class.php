<?php
/**
 * 使用Cookie维护用户会话
 * 支持用户唯一标识和扩展数据和少量存储
 */
class Session
{
    protected $_domain = NULL;
    protected $_cookie_name = NULL;
    
    protected $_user_id = NULL;
    protected $_ext_data = NULL;

    protected $_salt = 'vjnlsd/woiNCOqhjkd&%<>?"';


    public function __construct($config = array())
    {
        $this->_domain = isset($config['domain']) ? $config['domain'] : NULL;
        $this->_cookie_name = isset($config['cookie_name']) ? $config['cookie_name'] : NULL;
        $this->_salt = isset($config['salt']) ? $config['salt'] : NULL;

        $this->_checkCookie();
    }

    /**
     * 是否当前会话有效
     */
    public function isValid()
    {
        if ($this->_user_id === NULL) {
            $this->_parseCookie();
        }

        if ($this->_user_id) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * 返回用户当前id
     */
    public function userID()
    {
        return $this->_user_id;
    }

    /**
     * 最终cookie生成结构为
     * 注意ext_data不能有'-'字符
     * userid-ext_data-ext_data-expires-time-hashstr
     */
    public function setUserID($user_id, $expire_time = NULL, $ext_data = NULL)
    {
        $this->_user_id = $user_id;
        $this->_ext_data = $ext_data;


        $cookie_data = array($user_id);
        if (!empty($ext_data)) {
            if (!is_array($ext_data)) {
                $ext_data = array($ext_data);
            }
        }
        $cookie_data = array_merge($cookie_data, $ext_data);
        if ($expire_time === NULL) {
            $expire_time = 86400;//one day
        }

        $expires = time() + $expire_time;
        $cookie_data[] = $expires;

        $this->_setCookie($cookie_data, $expires);
    }

    /**
     * 获取除用户ID外，的扩展数据
     */
    public function extData()
    {
        return $this->_ext_data;
    }

    protected function _parseCookie()
    {
        $cookie = $this->getCookie();
        if (empty($cookie)) {
            return FALSE;
        }
        $datas = explode('-', $cookie);
        if (count($datas) < 4) {
            return FALSE;
        } 

        $hash_str = array_pop($datas);
        $time = array_pop($datas);

        return array($datas, $time, $hash_str);
    }

    protected function _checkCookie()
    {
        $cookie = $this->_parseCookie();
        if (empty($cookie)) {
            return FALSE;
        }

        list($datas, $time, $hash_str) = $cookie;
        $key_str = implode('-', $datas) . $this->_salt;
        $now_hash_str = md5($key_str);
        if ($now_hash_str != $hash_str) {
            $this->deleteCookie();
            return FALSE;
        }
        $expires = array_pop($datas);
        if ($expires < time()) {
            $this->deleteCookie(); 
            return FALSE;
        }

        $this->_user_id = array_shift($datas);
        $this->_ext_data = $datas;

        return TRUE;
    }

    protected function _setCookie($data, $expires)
    {
        if (empty($this->_domain) || empty($this->_cookie_name)) {
            return FALSE;
        }
        $data = array_values($data);
        $data = implode('-', $data);
        $key_str = $data . $this->_salt;
        $hash_str = md5($key_str);
        $time = time();
        $data .= "-{$time}-{$hash_str}";
        setcookie($this->_cookie_name, $data, $expires, '/', $this->_domain);
        $_COOKIE[$this->_cookie_name] = $data;

        return $data;
    }

    public function deleteCookie()
    {
        $expires = strtotime('one year ago');
        setcookie($this->_cookie_name, '', $expires, '/', $this->_domain);
        unset($_COOKIE[$this->_cookie_name]);
    }

    /**
     * 获取原始的cookie信息
     */
    public function getCookie()
    {
        return $_COOKIE[$this->_cookie_name];
    }
}
