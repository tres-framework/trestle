<?php
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

require_once('./tests/includes/autoload.php');

set_exception_handler(function($e) {
    echo '<b>' . get_class($e) . ':</b> ' . $e->getMessage();
});

$dbInfo = include('config.php');

Trestle\Config::set($dbInfo);