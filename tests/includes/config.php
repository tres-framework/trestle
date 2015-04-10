<?php
return [
    'throw' => [
        'database' => true,
        'query'    => true,
    ],
    
    'default' => 'MySQL1',
    
    'connections' => [
        'MySQL1' => [
            'driver'    => 'MySQL',
            'database'  => 'trestle_1',
            'host'      => '127.0.0.1',
            'port'      => '3306',
            'charset'   => 'utf8',
            'username'  => 'root',
            'password'  => '',
            'timeout'   => 5,
        ],
        'MySQL2' => [
            'driver'    => 'MySQL',
            'database'  => 'trestle_2',
            'host'      => '127.0.0.1',
            'port'      => '3306',
            'charset'   => 'utf8',
            'username'  => 'root',
            'password'  => '',
            'timeout'   => 5,
        ],
        'SQLite1' => [
            'driver'    => 'SQLite',
            'database'  => dirname(__FILE__) . '/trestle_1.sqlite',
            'timeout'   => 5,
        ],
        'SQLite2' => [
            'driver'    => 'SQLite',
            'database'  => dirname(__FILE__) . '/trestle_2.sqlite',
            'timeout'   => 5,
        ],
    ],
];
