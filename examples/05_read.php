<?php
require_once('includes/autoload.php');

set_exception_handler(function($e) {
    echo '<b>' . get_class($e) . ':</b> ' . $e->getMessage();
});

Trestle\Config::set([
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
    ]
]);

$db = new Trestle\Database('connection_name_1');

// Purist form
$data = $db->read('table_name')
           ->exec();

echo '<pre>'; print_r($data->result()); echo '</pre>';


// All methods
$data = $db->read('posts', ['id', 'title'])
           ->where('date', '>', '2014-11-20')
           ->andWhere('id', 'BETWEEN', [1, 9])
           ->andWhere('author', 'LIKE', 1)
           ->order('date', 'ASC')
           ->limit(4)
           ->offset(1)
           ->exec();

echo '<pre>'; print_r($data->result()); echo '</pre>';