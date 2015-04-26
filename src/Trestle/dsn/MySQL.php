<?php
return [
    'defaults' => [
        'host'      => '127.0.0.1',
        'port'      => '3306',
        'charset'   => 'utf8',
    ],
    'required' => [
        [
            'driver',
            'host',
            'username',
        ],
        [
            'driver',
            'socket',
            'username',
        ],
    ],
    'pattern' => [
        'driver'   => [
            'prefix' => '',
            'value'  => 'mysql',
            'suffix' => ':',
        ],
        'socket'   => [
            'prefix' => 'unix_socket=',
            'suffix' => ';',
        ],
        'host'     => [
            'prefix' => 'host=',
            'suffix' => ';',
        ],
        'database' => [
            'prefix' => 'dbname=',
            'suffix' => ';',
        ],
        'port'     => [
            'prefix' => 'port=',
            'suffix' => ';',
        ],
        'charset'  => [
            'prefix' => 'charset=',
            'suffix' => ';'
        ],
    ],
];
