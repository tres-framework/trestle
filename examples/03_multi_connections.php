<?php
require_once('includes/autoload.php');

set_exception_handler(function($e) {
    echo '<b>' . get_class($e) . ':</b> ' . $e->getMessage();
});

Trestle\Config::set([
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
]);

//
// Connection 1
// 
$database = new Trestle\Database('connection_name_1');
// / \
//  |-------|
//         \ /
$data = $database->query('SELECT * FROM `table_name` WHERE `column` = ?', ['some_text_to_prepare'])
           ->exec();

echo '<pre>'; print_r($data->result()); echo '</pre>';



//
// Connection 2
// 
$database_2 = new Trestle\Database('connection_name_2');
// / \
//  |-------|
//         \ /
$data = $database_2->query('SELECT * FROM `table_name` WHERE `column` = ?', ['some_text_to_prepare'])
                   ->exec();

echo '<pre>'; print_r($data->result()); echo '</pre>';