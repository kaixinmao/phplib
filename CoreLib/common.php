<?php
/**
 * 应该被默认include，一些常用方法的别名方法
 */


/**
 * 记录log，默认级别是info, 可以配置文件配置，sys_id默认'-'，可全局配置
 */
function log_message($log_msg, $level = NULL, $sys_id = NULL)
{
    return Logging::logMessage($log_msg, $level, $sys_id);
}
