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

// Using multiple tables in get()
$users = $db->read(['posts', 'users'], ['posts.id', 'posts.title', 'users.username'])
            ->where('posts.author', '=', 'users.id', true)
            ->exec();

echo '<pre>'; print_r($users->results()); echo '</pre>';

// Using join() & on()
$users = $db->read('posts', ['posts.id', 'posts.title'])
            ->join('users', ['users.username'])
            ->on('posts.author', '=', 'users.id')
            ->exec();

echo '<pre>'; print_r($users->results()); echo '</pre>';