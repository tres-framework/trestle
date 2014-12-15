<?php

require_once('inc/autoload.php');

$dbInfo = include('config.php');

try {
    Trestle\Config::set($dbInfo);
    
    $db = new Trestle\Database('MySQL1');
    
    $sql = "SELECT `title`
            FROM `posts`
            WHERE `author` = :id
            AND `title` LIKE :like
            AND `date` > :date
    ";
    $args = [
        ':id' => 1, 
        ':like' => 'Post%', 
        ':date' => '2014-11-23'
    ];
    
    $users = $db->query($sql, $args)
                ->exec();
    echo '<pre>'; print_r($users->debug()['query']); echo '</pre>';
    echo '<pre>'; print_r($users->results()); echo '</pre>';
    echo '<pre>'; print_r($users->debug()); echo '</pre>';
    echo '<hr />';
} catch(TrestleException $e) {
    echo $e->getMessage();
} catch(Exception $e) {
    echo $e->getMessage();
}