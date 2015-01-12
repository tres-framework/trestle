Trestle
===========

*Note that Trestle is in development, it's usable but lacks some features.*

<b>Tres</b>tle is an objected oriented PHP 5.4+ PDO database wrapper that is designed to handle multiple database connections with different database types.

This is an independent database package that is being worked on for the [Tres Framework](https://github.com/tres-framework). It can be used without the main framework.

## Requirements
- PHP 5.4 +

## Supported DB Types
- MySql

## Supported DB Features
### MySql
- Raw Query
- SELECT
- UPDATE
- INSERT (create)
- DELETE
- WHERE
   - AND (andWhere)
   - OR (orWhere)
   - BETWEEN
   - NOT BETWEEN
   - LIKE
- ORDER BY
- GROUP BY
- LIMIT (limit, offset)
- JOINS

#### TO-DO
- LEFT JOINS
- RIGHT JOINS
- UNIONS

## Logs
Trestle automatically creates logs for query request which can help identify slow queries and possible abuse from users. Query failures and database failures are also logged, they show deeper information about an error like codes and examples. All logs are stored chronologically in their respect directory.

Example:
```
/src/Trestle/logs/
- database/
- - 2014-12-28.000.log
- query/
- - 2014-12-30.000.log
- request/
- - 2014-12-28.000.log
- - 2014-12-29.000.log
- - 2014-12-30.000.log
```

## Examples
### Start Trestle
```php
// Autoload
spl_autoload_register(function($class){
    $dirs = [
        dirname(dirname(__DIR__)).'/src/'
    ];

    foreach($dirs as $dir){
        $file = str_replace('\\', '/', rtrim($dir, '/').'/'.$class.'.php');

        if(is_readable($file)){
            require_once($file);
            break;
        }
    }
});

$dbInfo = [
    'display_errors' => [
        'query' => false,
    ],

    'default' => 'MySQL1',

    'connections' => [
        'MySQL1' => [
            'driver'    => 'MySQL',
            'database'  => 'trestle_1',
            'host'      => '127.0.0.1',
            'port'      => '3306',
            'charset'   => 'utf8',
            'username'  => 'root',
            'password'  => ''
        ],

        'MySQL2' => [
            'driver'    => 'MySQL',
            'database'  => 'trestle_2',
            'host'      => '127.0.0.1',
            'port'      => '3306',
            'charset'   => 'utf8',
            'username'  => 'root',
            'password'  => ''
        ]
    ],
    
    // Log settings are optional and will use most of the below by default
    // 'logs' => [
    //     'dir' => [
    //         'path'        => __DIR__ . '/logs',
    //         'permissions' => 0777,
    //     ],
    //     'file' => [
    //         'ext'         => 'log',
    //         'size'        => 2097152,
    //         'permissions' => 0775,
    //     ],
    // ],

];

set_exception_handler(function($e) {
    echo '<b>' . get_class($e) . ':</b> ' . $e->getMessage();
});

// Set Config
Trestle\Config::set($dbInfo);

// Load Database 1
$db = new Trestle\Database('MySQL1');

// Load another database
$db2 = new Trestle\Database('MySQL2');

// Build queries...
// Look below for examples
```

### Raw Query
```php
// Get a record with specific fields
// Return first result
// SELECT `username`, `firstname`, `email` FROM `users` WHERE `id` = ?
$users = $db->query('SELECT `username`, `firstname`, `email` FROM `users` WHERE `id` = ?', 1)
            ->exec();
echo '<pre>'; print_r($users->result()); echo '</pre>';
```

### Get (first or only row)
```php
// Get a record with specific fields
// Return all results
// SELECT username, firstname, email FROM `users` WHERE `id` = ?
$users = $db->get('users', ['username', 'firstname', 'email'])
            ->where('id', '=', 1)
            ->exec();
echo '<pre>'; print_r($users->result()); echo '</pre>';
```

### Get (all rows + count rows)
```php
// Get records with all existing fields
// Return all data
// SELECT * FROM `users` ORDER BY ? ASC LIMIT ?, ?
$users = $db->get('users')
            ->order('id', 'ASC')
            ->offset(0)
            ->limit(5)
            ->exec();
echo '<pre>'; print_r($users->results()); echo '</pre>';
echo '<pre>'; print_r($users->count()); echo '</pre>';
```

### Get (BETWEEN)
```php
// Get records with all existing fields
// Return all data
// SELECT * FROM `users` WHERE `id` BETWEEN ? AND ?
$users = $db->get('users')
			->where('id', 'BETWEEN', [1, 9])
			->exec();
echo '<pre>'; print_r($users->results()); echo '</pre>';
```

### Get (NOT BETWEEN)
```php
// Get records with all existing fields
// Return all data
// SELECT * FROM `users` WHERE `id` BETWEEN ? AND ?
$users = $db->get('users')
			->where('id', 'NOT BETWEEN', [1, 9])
			->exec();
echo '<pre>'; print_r($users->results()); echo '</pre>';
```

### Get (LIKE)
```php
// Get records with all existing fields
// Return all data
// SELECT * FROM `users` WHERE `id` BETWEEN ? AND ?
$posts = $db->get('posts')
            ->where('title', 'LIKE', 'foobar')
            ->exec();
echo '<pre>'; print_r($posts->results()); echo '</pre>';
```

### GET (The Kitchen Sink)
```php
// A ton of parameters
// Return data as object
// SELECT id, title FROM `posts` WHERE `date` > ? AND `id` BETWEEN ? AND ? AND `author` LIKE ? ORDER BY ? ASC LIMIT ?, ?
$posts = $db->get('posts', ['id', 'title'])
            ->where('date', '>', '2014-11-20')
            ->andWhere('id', 'BETWEEN', [1, 9])
            ->andWhere('author', 'LIKE', 1)
            ->order('date', 'ASC')
            ->limit(4)
            ->offset(1)
            ->exec();
echo '<pre>SELECT `id`, `title` FROM posts WHERE `date` > ? AND `id` BETWEEN ? AND ? AND `author` LIKE ? ORDER BY ? ASC  LIMIT ?, ?</pre>';
echo '<pre>'; print_r($posts->results()); echo '</pre>';
echo 'Debug:';
echo '<pre>'; print_r($posts->debug()); echo '</pre>';
```

### Update
```php
// Update row
// Return true/false of update
// UPDATE `users` SET `username` = ?, `email` = ?, `firstname` = ? WHERE `id` = ?
$update = $db->update('users', [
                'username'  => 'bar',
                'email'     => 'bar@foo.tld',
                'firstname' => 'bar',
                'lastname'  => 'foo'
            ])
            ->where('id', '=', 3)
            ->exec();
echo '<pre>'; print_r($update->status()); echo '</pre>';
```

### Create
```php
// Create a record
// Return true/false of create
// INSERT INTO `users` (`username`, `email`, `firstname`, `lastname`, `active`, `permissions`) VALUES (?, ?, ?, ?, ?, ?);
$register = $db->create('users', [
                    'username' => 'foobar',
                    'email' => 'foo@bar.tld',
                    'password' => 'cleartextwoot',
                    'firstname' => 'Foo',
                    'lastname' => 'Bar',
                    'active' => 0,
                    'permissions' => '{\'admin\': 0}'
                ])
                ->exec();
echo '<pre>'; print_r($register->status()); echo '</pre>';
```

### Delete
```php
// Delete
// Return true/false of delete
// DELETE FROM `users` WHERE `id` = ?
$delete = $db->delete('users')
             ->where('id', '=', 72)
             ->exec();
echo '<pre>'; print_r($delete->status()); echo '</pre>';
```

## Using the data
```php
$foobar = $db->query('...')
             ->exec();
// Get all
$foobar->results();
// Get first
$foobar->result();
// Get count
$foobar->count();
// Get status of query success (boolean)
$foobar->status();
```

## Debug
```php
$foobar = $db->query('...')
             ->exec();
// Full debug
$foobar->debug();
```