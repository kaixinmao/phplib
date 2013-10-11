<?php
Config::Add(array(
    //默认Logging配置
    'Logging' => array(
        'save_path' => '/data/oauth/logs/oauth',
        'default_wirte_level' => Logging::LEVEL_DEBUG,
        'level' => Logging::LEVEL_ERROR,
        'filename_prefix' => '',
        'sys_id' => '',//系统id号，用于标识某个系统的日志信息
    ),
));

