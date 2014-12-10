<?php
// Autoload
spl_autoload_register(function($class){
	$file = dirname(__DIR__).'/'.str_replace('\\', '/', $class.'.php');

	if(is_readable($file)){
		require_once($file);
	} else {
		if(is_file($file)){
			die($file.' is not readable.');
		} else {
			die($file.' does not exist.');
		}
	}
});

// Load an array of connection info from config.php
// This allows for easy testing as .gitnore will not upload individual configs
$dbInfo = include('config.php');


try {
	// Set Config
	Trestle\Config::set($dbInfo);

	// Load Database 1
	$db = new Trestle\Database('MySQL1');
    
    $users = $db->query(
                "SELECT `title` FROM `posts` WHERE `author` = :id AND `title` LIKE :like AND `date` > :date", 
                    [
                        ':id' => 1, 
                        ':like' => '%Post%', 
                        ':date' => '2014-11-23'
                    ]
                )
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
