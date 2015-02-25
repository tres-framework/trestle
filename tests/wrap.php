<?php

require_once('inc/autoload.php');

set_exception_handler(function($e) {
    echo '<b>' . get_class($e) . ':</b> ' . $e->getMessage();
});

$dbInfo = include('config.php');

Trestle\Config::set($dbInfo);

$db = new Trestle\Database('MySQL3');

$users = $db->get('bugs', ['bugs.id', 'bug.title', 'post.title', 'bugs.example.test'])
            ->exec();
echo '<pre>'; print_r($users->debug()['query']); echo '</pre>';
echo '<pre>'; print_r($users->results()); echo '</pre>';
echo '<pre>'; print_r($users->debug()); echo '</pre>';