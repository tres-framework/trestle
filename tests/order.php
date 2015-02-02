<?php

require_once('inc/autoload.php');

set_exception_handler(function($e) {
    echo '<b>' . get_class($e) . ':</b> ' . $e->getMessage();
});

// Load an array of connection info from config.php
// This allows for easy testing as .gitnore will not upload individual configs
$dbInfo = include('config.php');

Trestle\Config::set($dbInfo);

$db = new Trestle\Database('MySQL1');

// The Kitchen Sink
// Return data as object
// SELECT `id`, `title` FROM posts WHERE `date` > ? AND
// `id` BETWEEN ? AND ? AND `author` LIKE ? ORDER BY ? ASC  LIMIT ?, ?
$users = $db->get('posts', ['id', 'title'])
        ->order('date', 'ASC')
        ->exec();
echo '<pre>'; print_r($users->debug()['query']); echo '</pre>';
echo '<pre>'; print_r($users->results()); echo '</pre>';
echo 'Debug:';
echo '<pre>'; print_r($users->debug()); echo '</pre>';
echo '<hr />';

