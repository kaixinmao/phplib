<?php

//确定环境
//默认是生产环境
$default_env = 'production';
$current_env = $default_env;

$allowed_env = array('dev','test','pre','production');

if (defined('APP_ENV')) {
    $current_env = APP_ENV;
} else {
    $dev_hostnames = array('localhost.opendev', 'zhangzhan-zbj');//zhangzhan-zbjdev 张展本机
    $test_hostnames = array('');
    $pre_hostnames = array('');

    $current_host_name = php_uname('n');
    if (in_array($current_host_name, $dev_hostnames)) {
        $current_env = 'dev';
    } elseif (in_array($current_host_name, $test_hostnames)) {
        $current_env = 'test';
    } elseif (in_array($current_host_name, $pre_hostnames)) {
        $current_env = 'pre';
    }
    define ('APP_ENV', $current_env);
}
if (!in_array($current_env, $allowed_env)) {
    $current_env = $default_env;
}
if ($current_env == 'dev')
{
    error_reporting(E_ALL & ~E_NOTICE);
}
//定义配置文件环境路径
define('ROOT_CONFIG', ROOT_BASECONFIG . "/{$current_env}");

//加载默认的配置信息
require_once(ROOT_BASECONFIG . '/alldefault.conf.php');

//加载默认配置文件
if (file_exists(ROOT_CONFIG . '/db.conf.php')) {
    require_once(ROOT_CONFIG . '/db.conf.php');
}

if (file_exists(ROOT_CONFIG . '/default.conf.php')) {
    require_once(ROOT_CONFIG . '/default.conf.php');
}
