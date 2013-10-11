<?php
/**
 * 表单验证类
 * 通过配置，方便进行一系列的验证。
 * '验证组' => {
 *      '表单参数|表单别名' => 'required|validate[1,3,4]'
 * }
 * 默认方法
 *
 * 错误返回：
 * 支持全部检查和部分检查
 * '表单参数' => '错误信息'
 */

class Lib_FormValidation
{
    /**
     * 默认的验证器,标准方法都在这里边
     */
    protected $_default_validator = NULL;

    /*
     * 保存用户自定义的一些验证方法，在调用的时候优先处理
     * 方法名 callable
     */
    protected $_user_methods = array();


    /*
     * 要检查的from group
     * group_name => array(
     *      'param_name|别名' => array(
     *          'methodname' => array(
     *              默认参数
     *          )
     *      )
     * )
     */
    protected $_form_groups = array();


    protected $_errors = array();

    protected $_params = array();

    /**
     * 构造函数，一次初始化一个组
     */
    public function __construct($conf = array())
    {
        $this->_default_validator = new Lib_FormValidation_Validator();
        if (empty($conf)) {
            return;
        }

        foreach ($conf as $group_name => $group_conf) {
            $this->addFormGroup($group_name, $group_conf);
        }
    }

    /**
     * 验证请求并返回受验证的值
     * 成功返回组中设定的参数值，否则返回FALSE
     */
    public function validate($name, $data = NULL, $check_all = TRUE)
    {
        $this->_errors = array();
        $this->_params = array();
        if (empty($this->_form_groups[$name])) {
            return array();
        }

        $form_group = $this->_form_groups[$name];

        if (is_null($data)) {
            $data = $this->_getDefaultData();
        }

        $vals = array();
        $have_false = FALSE;
        foreach ($form_group as $param_name => $methods) {
            $val = $this->_callMethods($param_name, $methods, $data);
            if ($val === FALSE) {
                $have_false = TRUE;
                if (!$check_all) {
                    break;
                } else {
                    continue;
                }
            }

            list($param_name, $param_val) = $val;
            $vals[$param_name] = $param_val;
        }

        if ($have_false) {
            return FALSE;
        } else {
            return $vals;
        }
    }

    /**
     * 返回分解后的key, val 数组
     * array(key, val)
     */
    protected function _callMethods($param_name, $methods, $data)
    {
        $param_name_arr = explode('|', $param_name);
        $param_name = $param_name_arr[0];
        $alias_name = $param_name;
        if (count($param_name_arr) > 1) {
            $alias_name = $param_name_arr[1];
        }
        $val = @$data[$param_name];

        //后面会取数据值
        $this->_params[$param_name] = $val;

        if (empty($methods)) {
            return array($param_name, $val);
        }

        $have_error = FALSE;
        foreach ($methods as $method => $params) {
            //找可以执行的方法

            $method_callable = array($this->_default_validator, $method);
            if (isset($this->_user_methods[$method])) {
                $method_callable = $this->_user_methods[$method];
            }

            if (!is_callable($method_callable)) {
                $have_error = TRUE;
                $this->_errors[$param_name] = "{$method} 验证方法不存在";
                break;
            }

            $params = array_merge(array($alias_name, $val), $params);
            $ret = call_user_func_array($method_callable, $params);
            if (is_bool($ret) && $ret) {
                continue;
            }

            //检查发生了错误
            $have_error = TRUE;
            $this->_errors[$param_name] = $ret;
            break;
        }

        if ($have_error) {
            return FALSE;
        } else {
            return array($param_name, $val);
        }
    }

    protected function _getDefaultData()
    {
        return array_merge($_GET, $_POST);
    }

    /**
     * 返回最后一次验证的错误信息
     * param_name => error
     */
    public function errors()
    {
        return $this->_errors;
    }

    /**
     * 返回检查过的变量值
     */
    public function params()
    {
        return $this->_params;
    }

    public function addFormGroup($name, $conf)
    {
        if (empty($conf)) {
            return;
        }
        $group_conf = array();

        foreach ($conf as $param_name => $c) {
            if (!isset($group_conf[$param_name])) {
                $group_conf[$param_name] = array();
            }
            if (empty($c)) {
                continue;
            }
            $methods = explode('|' , $c);
            foreach($methods as $m) {
                $m = trim($m);
                $default_param_pos = strpos($m, '[');
                if ($default_param_pos === FALSE) {
                    //没有默认参数
                    $group_conf[$param_name][$m] = array();
                } else {
                    $params = array();
                    if ($m[$default_param_pos + 1] != ']') {
                        $params_str = substr($m, $default_param_pos + 1, -1);
                        $params = explode(',', $params_str);
                    }
                    $m = substr($m, 0, $default_param_pos);
                    $group_conf[$param_name][$m] = $params;
                }
            }
        }

        if (isset($this->_form_groups[$name])) {
            $this->_form_groups[$name] = array_merge($this->_form_groups[$name], $group_conf);
        } else {
            $this->_form_groups[$name] = $group_conf;
        }

        return;
    }

    /**
     * callable的参数为:
     * $name:字段名称
     * $data:具体的数据，如果没有传入值为NULL 
     * ...后面为具体定义参数
     */
    public function addValidateMethod($name, $callable)
    {
        if (!is_callable($callable)) {
            return;
        }

        $this->_user_methods[$name] = $callable;
    }
}

class Lib_FormValidation_Validator
{
    public function required($name, $data)
    {
        if (empty($data)) {
            return "'{$name}' 不能为空";
        } else {
            return TRUE;
        }
    }

    //最小值
    public function min($name, $data, $min)
    {
        if (!is_null($data) && $data >= $min) {
            return TRUE;
        } else {
            return "'{$name}' 最小值为{$min}";
        }
    }

    public function max($name, $data, $max)
    {
        if (!is_null($data) && $data <= $min) {
            return TRUE;
        } else {
            return "'{$name}' 最大值为{$man}";
        }
    }

    public function integer($name, $data)
    {
        $data = (int) $data;
        if (!empty($data)) {
            return TRUE;
        } else {
            return "'{$name}' 必须为整数";
        }
    }

    public function valid_url($name, $str){
        $pattern = "/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i";
        if (!preg_match($pattern, $str))
        {
            return "'{$name}' 不是有效的URL地址";
        }

        return TRUE;
    }

    /**
     * 匹配格式：
     * 11位手机号码
     * 3-4位区号，7-8位直播号码，1－4位分机号
     * 如：12345678901、1234-12345678-1234
     */
    public function valid_phone($name, $str) {
        $pattern = "((\d{11})|^((\d{7,8})|(\d{4}|\d{3})-(\d{7,8})|(\d{4}|\d{3})-(\d{7,8})-(\d{4}|\d{3}|\d{2}|\d{1})|(\d{7,8})-(\d{4}|\d{3}|\d{2}|\d{1}))$)";
        if (!preg_match($pattern, $str))
        {
            return "'{$name}' 不是有效的电话号码";
        }

        return TRUE;
    }

    //字符串最小长度
    public function min_length($name, $str, $len)
    {
        if (!empty($str) && strlen($str) >= $len)
        {
            return TRUE;
        } else {
            return "'{$name}'至少输入{$len}个字符";
        }
    }

    //字符串最大长度
    public function max_length($name, $str, $len)
    {
        if (empty($str) || strlen($str) <= $len)
        {
            return TRUE;
        } else {
            return "'{$name}'最多输入{$len}个字符";
        }
    }

    //字符串最小长度
    public function utf8min_length($name, $str, $len)
    {
        if (!empty($str) && mb_strlen($str, 'UTF-8') >= $len)
        {
            return TRUE;
        } else {
            return "'{$name}'至少输入{$len}个字符";
        }
    }

    //字符串最大长度
    public function utf8max_length($name, $str, $len)
    {
        if (empty($str) || mb_strlen($str, 'UTF-8') <= $len)
        {
            return TRUE;
        } else {
            return "'{$name}'最多输入{$len}个字符";
        }
    }
}

