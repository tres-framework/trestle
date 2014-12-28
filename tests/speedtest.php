<?php

require_once('inc/autoload.php');

set_exception_handler(function($e) {
    echo '<b>' . get_class($e) . ':</b> ' . $e->getMessage();
});

$dbInfo = include('config.php');

Trestle\Config::set($dbInfo);

$db = new Trestle\Database('MySQL2');

// Deleting a record.
$id = 1;
$delete = $db->delete('users')
   ->where('id', '=', $id)
   ->exec();
echo '<pre>' . $delete->debug()['query'] . '</pre>';
echo ($delete->status() ? 'Successfully purged ' : 'Failed to purge') . ' existing row with id of '.$id.'.';

// Creating a record.
$create = $db->create('users', [
            'id'            => 1,
            'username'      => 'jdoe',
            'email'         => 'j.doe@example.com',
            'password'      => '$2y$14$.vGA1O9wmRjrwAVXD98HNOgsNpDczlqm3Jq7KnEd1rVAGv3Fykk1a',
            'firstname'     => 'John',
            'lastname'      => 'Doe',
            'active'        => 1,
            'permissions'   => "{'can_ban_users': true}"
        ])->exec();

echo '<hr />';
echo '<pre>' . $create->debug()['query'] . '</pre>';
echo ($create->status() ? 'Successfully' : 'Failed to') . ' created new user!';

if($create->status()) {
    echo ' Created with:';
    $users = $db->query('SELECT `username`, `firstname`, `email` FROM `users` WHERE `id` = ?', [1])->exec();
    echo '<pre>'; print_r($users->first()); echo '</pre>';
}

echo '<hr />';

// Updating a record.
$update = $db->update('users', [
           'username'  => 'jdoe',
           'email'     => 'john17@example.com',
           'firstname' => 'John Newname',
           'lastname'  => 'Doe'
        ])
        ->where('id', '=', 1)
        ->exec();

echo '<pre>' . $update->debug()['query'] . '</pre>';
echo ($update->status() ? 'Successfully ' : 'Failed to ') . 'updated user!';

// Checking query status.
if($update->status()) {
    echo ' Updated with:';
    $users = $db->query('SELECT `username`, `firstname`, `email` FROM `users` WHERE `id` = ?', [1])->exec();
    echo '<pre>'; print_r($users->first()); echo '</pre>';
}

echo '<hr />';

$delete = $db->delete('users')
             ->where('id', '=', 1)
             ->exec();
echo '<pre>' . $delete->debug()['query'] . '</pre>';
echo ($delete->status()) ? 'Successfully purged row.' : 'Failed to purge row.';
