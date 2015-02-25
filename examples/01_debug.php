<?php
require_once('includes/autoload.php');

// Debugging is done in two ways

// 1
// Code error like bad syntax, wrong methods, parsing errors, etc... Use \Exceptions
// They can be caught with set_exception_handler()
// This version below prints out the specific exception, example:
// Exception: some error message would display here
// Trestle\TrestleException: some error message would display here
// Trestle\QueryException: some error message would display here
// Trestle\LogException: some error message would display here
set_exception_handler(function($e) {
    echo '<b>' . get_class($e) . ':</b> ' . $e->getMessage();
});

// For exceptions to be thrown you must specify what exceptions you want to be 
// thrown. By default they are set to false so users do not see them and possibly 
// exploit them by gather clues of you database schema.
Trestle\Config::set([
    // Enable exceptions for...
    'throw' => [
        // General Trestle errors and database connections
        'database' => true,
        // Query syntax and error
        'query'    => true,
    ],
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

// 2
// The second way to debug Trestle and query errors is using debug(). The debug 
// method provides a lot of information of what is happening to the query including
// backtrack, syntax, pattern and speed.
echo '<pre>'; print_r($data->dubug()); echo '</pre>';

// Example debug() output
/*
Array
(
    [called] => Array
        (
            [file] => /var/www/trestle/examples/debug.php
            [line] => 51
        )

    [blueprint] => MySQL
    [backtrace] => Array
        (
            [0] => Trestle\blueprints\MySQL::query
        )

    [pattern] => Array
        (
            [0] => ~query
        )

    [error] => 
    [query] => SELECT * FROM `table_name` WHERE `column` = ?
    [binds] => Array
        (
            [1] => some_text_to_prepare
        )

    [execution] => Array
        (
            [build] => 0.0010001659393311
            [query] => 0.0010001659393311
            [total] => 0.002000093460083
        )

)
*/