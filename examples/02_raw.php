<?php
require_once('includes/autoload.php');

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

$data = $db->query('SELECT * FROM `table_name` WHERE `column` = ?', ['some_text_to_prepare'])
           ->exec();

echo '<pre>'; print_r($data->result()); echo '</pre>';