<?php
Config::Add(array(
    //计数器的Memcache服务器
    'CounterMemcacheServers' => '127.0.0.1:11211',

    //默认的Memcache缓存服务
    'DefaultMemcacheServers' => '127.0.0.1:11211',

    'Logging' => array(
        'level' => Logging::LEVEL_DEBUG,
    ),
));
