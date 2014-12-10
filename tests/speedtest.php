<?php
// Autoload
spl_autoload_register(function($class){
	$file = dirname(__DIR__).'/'.str_replace('\\', '/', $class.'.php');

	if(file_exists($file)){
		require_once($file);
	} else {
		if(!is_readable($file)){
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
	$db = new Trestle\Database('MySQL2');
	
	// Delete
	$delete = $db->delete('users')
	   ->where('id', '=', 1)
	   ->exec();
	echo '<pre>' . $delete->debug()['query'] . '</pre>';
	echo ($delete->status() ? 'Successfully' : 'Failed to') . ' purge existing row with id of 1.';
	
	// Create a record
	$create = $db->create('users', [
		'id'        => 1,
		'username' => 'foobar',
		'email' => 'foo@bar.tld',
		'password' => 'cleartextwoot',
		'firstname' => 'Foo',
		'lastname' => 'Bar',
		'active' => 1,
		'permissions' => '{\'admin\': 0}'
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
	
	// Update row
	$update = $db->update('users', [
		   'username'  => 'barfoo',
		   'email'     => 'bar@foo.tld',
		   'firstname' => 'bar',
		   'lastname'  => 'foo'
	   ])
	   ->where('id', '=', 1)
	   ->exec();
	echo '<pre>' . $update->debug()['query'] . '</pre>';
	echo ($update->status() ? 'Successfully' : 'Failed to') . ' updated user!';
	
	if($update->status()) {
		echo ' Updated with:';
		$users = $db->query('SELECT `username`, `firstname`, `email` FROM `users` WHERE `id` = ?', [1])->exec();
		echo '<pre>'; print_r($users->first()); echo '</pre>';
	}

	echo '<hr />';
	
	// Delete
	$delete = $db->delete('users')
	   ->where('id', '=', 1)
	   ->exec();
	echo '<pre>' . $delete->debug()['query'] . '</pre>';
	echo ($delete->status() ? 'Successfully' : 'Failed to') . ' purge row!';
	

} catch(\Exception $e) {
	die($e->getMessage());
}