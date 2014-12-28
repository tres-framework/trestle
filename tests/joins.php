<?php

require_once('inc/autoload.php');

set_exception_handler(function($e) {
    echo '<b>' . get_class($e) . ':</b> ' . $e->getMessage();
});

$dbInfo = include('config.php');

Trestle\Config::set($dbInfo);

$db = new Trestle\Database('MySQL1');

$users = $db->get(['posts', 'users'], ['posts.id', 'posts.title', 'users.username'])
            ->where('posts.author', '=', 'users.id', true)
            ->andWhere('date', '>', '2014-11-30')
            ->exec();
echo '<pre>'; print_r($users->debug()['query']); echo '</pre>';
echo '<pre>'; print_r($users->results()); echo '</pre>';
echo '<pre>'; print_r($users->debug()); echo '</pre>';
