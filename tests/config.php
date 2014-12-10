<?php
return [
    
    'display_errors' => true,
    
    'default' => 'MySQL1',
    
    'connections' => [
        
        'MySQL1' => [
            'driver'    => 'MySQL',
            'database'  => 'trestle_1',
            'host'      => '127.0.0.1',
            'port'      => '3306',
            'charset'   => 'utf8',
            'username'  => 'root',
            'password'  => 'password'
        ],
        
        'MySQL2' => [
            'driver'    => 'MySQL',
            'database'  => 'trestle_2',
            'host'      => '127.0.0.1',
            'port'      => '3306',
            'charset'   => 'utf8',
            'username'  => 'root',
            'password'  => 'password'
        ]
        
    ],
    
    'logs' => [
        'dir' => [
            'path'        => __DIR__.'/logs',
            //'permissions' => '',
        ],
        
        //'file' => [
            //'ext'         => '',
            //'size'        => '',
            //'permissions' => '',
        //],
    ],
    
];
