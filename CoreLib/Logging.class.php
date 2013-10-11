<?php
/**
 * 基础日志类
 * 保存格式为
 * [日期 时间 级别 请求ip 系统标记]数据字符串
 *
 * 日志级别:
 * debug
 * info
 * warning
 * error
 * fatal
 *
 * 
 * 默认保存路径:
 * /tmp/
 *
 * 默认录入级别:
 * info
 *
 * 默认允许录入级别
 * info
 *
 * 默认存储方式:
 * 按天文件保存
 * 前缀20130802.log
 *
 */
class Logging
{
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_FATAL = 'fatal';

    public static $_LEVEL_INDEX = array(
        self::LEVEL_DEBUG => 20,
        self::LEVEL_INFO => 30,
        self::LEVEL_WARNING => 40,
        self::LEVEL_ERROR => 50,
        self::LEVEL_FATAL => 60,
    );

    protected $_level = 'info';
    protected $_level_index = 30;//默认error index
    protected $_default_wirte_level = 'info';
    protected $_sys_id = '';//系统标志

    /**
     * 实现write方法
     */
    protected $_writer = NULL;//日志写入器实例

    /**
     * 钩子保存
     * 记录logger后执行哪些方法
     * 传入参数为
     * 最终生成的msg, level, level_index, sys_id
     */
    protected $_hooker = array();
    /**
     * 不允许外部直接new对象
     * 使用getInstance标记
     *
     *
     */
    protected function __construct()
    {
        $conf = Config::get('Logging');
        if (empty($conf)) {
            $this->_writer = new Logging_DayFileWriter();
        } else {
            if (isset($conf['default_write_level'])) {
                $default_write_level = $conf['default_write_level'];
                if (isset(self::$_LEVEL_INDEX[$default_write_level])) {
                    $this->_default_wirte_level = $default_write_level;
                }
            }

            if (isset($conf['level'])) {
                $level = $conf['level'];
                if (isset(self::$_LEVEL_INDEX[$level])) {
                    $this->_level = $level;
                    $this->_level_index = self::$_LEVEL_INDEX[$level];
                }
            }

            if (isset($conf['sys_id'])) {
                $this->_sys_id = $conf['sys_id'];
            }

            $save_path = NULL;
            if (isset($conf['save_path'])) {
                $save_path = $conf['save_path'];
            }

            if (isset($conf['filename_prefix'])) {
                $filename_prefix = $conf['filename_prefix'];
            }

            $this->_writer = new Logging_DayFileWriter($save_path, $filename_prefix);
        }
    }

    public function setHooker($callable)
    {
        if (is_callable($callable)) {
            $this->_hooker[] = $callable;
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function setWriter($writer)
    {
        if (is_object($write) && is_callable(array(
            $writer, 'write'))) {
            $this->_writer = $writer;
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function write($log_msg, $level = NULL, $sys_id = NULL)
    {
        if (empty($log_msg)) {
            return;
        }

        if (is_null($level) || !isset(self::$_LEVEL_INDEX[$level])) {
            $level = $this->_default_wirte_level;
        }

        $level_index = self::$_LEVEL_INDEX[$level];
        if ($level_index < $this->_level_index) {
            return;
        }

        if (is_null($sys_id)) {
            $sys_id = $this->_sys_id;
        }

        //开始生成要写入的msg
        $msg = '';
        //必要时把这里抽象出去，可以自己组合
        $time_str = date('Y-m-d H:i:s');
        $client_ip = Helper_Http::clientIp();
        if (!$client_ip) {
            $client_ip = '-';
        }
        if (empty($sys_id)) {
            $sys_id = '-';
        }

        $msg = "[{$time_str} {$level} {$client_ip} {$sys_id}]{$log_msg}\n";

        $this->_writer->write($msg);
        if (!empty($this->_hooker)) {
            foreach ($this->_hooker as $call) {
                call_user_func_array($call, array($msg, $level, $level_index, $sys_id));
            }
        }

        return;
    }

    /**
     * 记录日志
     */
    public static function logMessage($log_msg, $level = NULL, $sys_id = NULL, $log_id = 'default')
    {
        $logging = self::getInstance($log_id);
        return $logging->write($log_msg, $level, $sys_id);
    }

    public static function getInstance($id = 'default')
    {
        static $loggings = array();
        if (!isset($loggings[$id])) {
            $loggings[$id] = new Logging();
        }

        return $loggings[$id];
    }
}

class Logging_DayFileWriter
{
    protected $_prefix = '';
    protected $_save_dir = '/tmp';

    public function __construct($save_dir = '/tmp', $prefix = '')
    {
        if (!is_dir($save_dir)) {
            mkdir($save_dir, 0777, TRUE);//尝试创建一次目录
        }

        if (is_dir($save_dir) && is_writable($save_dir)) {
            $this->_save_dir = rtrim($save_dir, "/\\");
        }

        if (!empty($prefix)) {
            $this->_prefix = $prefix;
        }
    }

    public function write($msg)
    {
        if (empty($msg)) {
            return;
        }

        $log_path = $this->_save_dir . DIRECTORY_SEPARATOR . $this->_curFileName();

        error_log($msg, 3, $log_path);
        return;
    }

    protected function _curFileName()
    {
        //在一次请求里边即使跨天也写到一个文件里边
        static $cur_file_name = NULL;
        if (is_null($cur_file_name)) {
            $cur_file_name = $this->_prefix . date('Ymd') . '.log';
        }
        return $cur_file_name;
    }
}


