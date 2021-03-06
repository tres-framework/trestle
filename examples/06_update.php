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

$data = $db->update('users', [
   'username'  => 'foo',
   'email'     => 'foo@bar.tld',
   'firstname' => 'foo',
   'lastname'  => 'bar'
])
->where('id', '=', 3)
->exec();

echo '<pre>'; print_r($data->status()); echo '</pre>';
echo '<pre>'; print_r($data->result()); echo '</pre>';