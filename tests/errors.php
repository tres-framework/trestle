<?php

require_once('inc/autoload.php');

set_exception_handler(function($e) {
    echo '<b>' . get_class($e) . ':</b> ' . $e->getMessage();
});

$dbInfo = include('config.php');

Trestle\Config::set($dbInfo);

$db = new Trestle\Database('MySQL1');
$db2 = new Trestle\Database('MySQL2');


$users = $db->query('SELECT * FROM table_no_exists WHERE user = 1')
            ->exec();

echo '<pre>'; print_r($users->result()); echo '</pre>';
echo '<pre>'; print_r($users->debug()); echo '</pre>';