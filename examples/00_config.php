<?php
require_once('includes/autoload.php');

// Options 1
// Load configs directly into method
Trestle\Config::set([
    'throw' => [
        'database' => true,
        'query'    => true,
    ],
    
    'default' => 'connecton_name_1',
    
    'connections' => [
        'connection_name_1' => [
            'driver'    => 'MySQL',
            'database'  => 'database_name',
            'host'      => '127.0.0.1',
            'port'      => '3306',
            'charset'   => 'utf8',
            'username'  => 'root',
            'password'  => 'password'
        ],
        'connection_name_2' => [
            'driver'    => 'MySQL',
            'database'  => 'database_name_2',
            'host'      => '127.0.0.1',
            'port'      => '3306',
            'charset'   => 'utf8',
            'username'  => 'root',
            'password'  => 'password'
        ],
    ]
    
    'logs' => [
        'dir' => [
            'path'        => __DIR__.'/logs',
            'permissions' => '775',
        ],
        
        'file' => [
            'ext'         => 'log',
            'size'        => '100',
            'permissions' => '775',
        ],
    ],
]);

$db = new Trestle\Database('connection_name_1');

$data = $db->query('SELECT * FROM `table_name` WHERE `column` = ?', ['some_text_to_prepare'])
           ->exec();

echo '<pre>'; print_r($data->result()); echo '</pre>';

// Option 2
// Load configs from another file
$dbInfo = include('config.php');

Trestle\Config::set($dbInfo);

$db = new Trestle\Database('connection_name_1');

$data = $db->query('SELECT * FROM `table_name` WHERE `column` = ?', ['some_text_to_prepare'])
           ->exec();

echo '<pre>'; print_r($data->result()); echo '</pre>';