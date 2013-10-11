<?php
$db_zones = array(
//oauth相关库 ,main 只有一个配置， query可以有多个配置,随机获取一个进行读查询
'oa_oauth' => array(
    'main' => array(
        'host' => '127.0.0.1',
        'user' => '',
        'password' => '',
        'database' => '',
        'charset' => 'utf8'
    ),
    'query' => array(
        array(
            'host' => '127.0.0.1',
            'user' => '',
            'password' => '',
            'database' => '',
            'charset' => 'utf8'
        ),
    ), //end query
),//end oa_oauth
//end
);


$redis_conf = array(
    array(
        'host' => '127.0.0.1',
        'port' => '6379',
    ),
);
Config::Add(array(
    'DB_CONFIG' => $db_zones,
    'REDIS_CONFIG' => $redis_conf,
));

unset($db_zones);
unset($redis_conf);
