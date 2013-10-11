<?php
date_default_timezone_set("Asia/Chongqing");
define("ROOT_APPLIB", realpath(dirname(__FILE__)));
define("ROOT_CORELIB", realpath(ROOT_APPLIB . "/../CoreLib"));//基础功能类库，不要放和项目相关
define("ROOT_BASECONFIG",  ROOT_APPLIB . '/Config');

//加载默认配置信息
require(ROOT_BASECONFIG . '/default.conf.php');

//注册默认自动加载
//{{{
function __autoload($class){
    $class_path = '/' . str_replace('_', '/', $class) . '.class.php';
    //自己WEB库优先检查
    if (defined('ROOT_APP')) {
        $file = ROOT_APP . $class_path;
        if(file_exists($file)) return require($file);
    }
    //应用库检查
    $coreapplib_file = ROOT_APPLIB . $class_path;
    if(file_exists($coreapplib_file)) return require($coreapplib_file);

    //优先核心库
    $corelib_file = ROOT_CORELIB . $class_path;
    if(file_exists($corelib_file)) return require($corelib_file);
}
spl_autoload_register('__autoload');

//加载默认的common文件，一些常用的方法放里边的
require(ROOT_CORELIB . '/common.php');
//}}}
//

//Yaf bootstrap
class Bootstrap extends Yaf_Bootstrap_Abstract
{
    public function _initView(Yaf_Dispatcher $dispatcher)
    {
        $view = new View();
        $view->setScriptPath(ROOT_APP . '/views');
        $dispatcher->setView($view);
    }
} 
