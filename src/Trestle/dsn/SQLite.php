<?php
return [
    'defaults' => [
        
    ],
    'required' => [
        [
            'driver',
            'database',
        ],
    ],
    'pattern' => [
        'driver'   => [
            'prefix' => '',
            'value'  => 'sqlite',
            'suffix' => ':',
        ],
        'database' => [
            'prefix' => '',
            'suffix' => ';',
        ],
    ],
];
