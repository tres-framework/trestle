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
$db2 = new Trestle\Database('MySQL2');


// Get a record with specific fields
// Return first result
// SELECT `username`, `firstname`, `email` FROM `users` WHERE `id` = 1
$users = $db->query('SELECT `username`, `firstname`, `email` FROM `users` WHERE `id` = ?', [1])
            ->exec();
echo '<pre>'; print_r($users->debug()['query']); echo '</pre>';
echo '<pre>'; print_r($users->result()); echo '</pre>';
echo '<hr />';


// Get a record with specific fields
// Return all results
// SELECT `username`, `firstname`, `email` FROM `users` WHERE `id` = ?
$users = $db->get('users', ['username', 'firstname', 'email'])
            ->where('id', '=', 1)
            ->exec();
echo '<pre>'; print_r($users->debug()['query']); echo '</pre>';
echo '<pre>'; print_r($users->results()); echo '</pre>';
echo '<hr />';


// Get records with all existing fields
// Return all data
// SELECT * FROM users ORDER BY ? ASC LIMIT ?, ?
$users = $db->get('users')
            ->order('id', 'ASC')
            ->offset(0)
            ->limit(5)
            ->exec();
echo '<pre>'; print_r($users->debug()['query']); echo '</pre>';
echo '<pre>'; print_r($users->results(PDO::FETCH_ASSOC)); echo '</pre>';
echo 'test';
echo '<pre>COUNT: ' . $users->count() . ' results found...</pre>';
echo '<hr />';


// The Kitchen Sink
// Return data as object
// SELECT `id`, `title` FROM posts WHERE `date` > ? AND
// `id` BETWEEN ? AND ? AND `author` LIKE ? ORDER BY ? ASC  LIMIT ?, ?
$users = $db->get('posts', ['id', 'title'])
        ->where('date', '>', '2014-11-20')
        ->andWhere('id', 'BETWEEN', [1, 9])
        ->andWhere('author', 'LIKE', 1)
        ->order('date', 'ASC')
        ->limit(4)
        ->offset(1)
        ->exec();
echo '<pre>'; print_r($users->debug()['query']); echo '</pre>';
echo '<pre>'; print_r($users->results()); echo '</pre>';
echo 'Debug:';
echo '<pre>'; print_r($users->debug()); echo '</pre>';
echo '<hr />';


// Update row
// Return true/false of update
// UPDATE `users` SET `username` = ?, `email` = ?, `firstname` = ? WHERE `id` = ?
// $update = $db->update('users', [
       // 'username'  => 'bar',
       // 'email'     => 'bar@foo.tld',
       // 'firstname' => 'bar',
       // 'lastname'  => 'foo'
   // ])
   // ->where('id', '=', 3)
   // ->exec();
// echo '<pre>'; print_r($users->debug()['query']); echo '</pre>';
// echo '<pre>'; print_r($update->status()); echo '</pre>';


// Create a record
// Return true/false of create
// INSERT INTO `users` (`username`, `email`, `firstname`, `lastname`, `active`, `permissions`)
// VALUES (?, ?, ?, ?, ?, ?);
// $register = $db->create('users', [
    // 'id'        => 3,
    // 'username' => 'foobar',
    // 'email' => 'foo@bar.tld',
    // 'password' => 'cleartextwoot',
    // 'firstname' => 'Foo',
    // 'lastname' => 'Bar',
    // 'active' => 0,
    // 'permissions' => '{\'admin\': 0}'
// ])->exec();
// echo '<pre>'; print_r($users->debug()['query']); echo '</pre>';
// echo '<pre>'; print_r($register->status()); echo '</pre>';


// Delete
// Return true/false of delete
// DELETE FROM `users` WHERE `id` = ?
// $delete = $db->delete('users')
   // ->where('id', '=', 3)
   // ->exec();
// echo '<pre>'; print_r($users->debug()['query']); echo '</pre>';
// echo '<pre>'; print_r($delete->status()); echo '</pre>';
