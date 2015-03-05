Trestle
===========

*Note that Trestle is in development, it's usable but lacks some features.*

<b>Tres</b>tle is an objected oriented PHP 5.4+ PDO database wrapper that is designed to handle multiple database connections with different database types.

This is an independent database package that is being worked on for the [Tres Framework](https://github.com/tres-framework). It can be used without the main framework.

## Requirements
- PHP 5.4 +

## Supported DB Types
- MySQL

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
- JOIN
    - INNER JOIN
    - LEFT JOIN
    - RIGHT JOIN

## Examples
### Basic Usage
```php
// Include your custom autoload
require_once('includes/autoload.php');

// Catch any exceptions
set_exception_handler(function($e) {
    echo '<b>' . get_class($e) . ':</b> ' . $e->getMessage();
});

// Load configs directly into method
Trestle\Config::set([
    'throw' => [
        'database' => true,
        'query'    => true,
    ],
    
    'default' => 'connecton_name_1',
    
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
        'connection_name_2' => [
            'driver'    => 'MySQL',
            'database'  => 'database_name_2',
            'host'      => '127.0.0.1',
            'port'      => '3306',
            'charset'   => 'utf8',
            'username'  => 'root',
            'password'  => 'password'
        ],
    ],
    
    'logs' => [
        'dir' => [
            'path'        => __DIR__.'/logs',
            'permissions' => '775',
        ],
        
        'file' => [
            'ext'         => 'log',
            'size'        => '100',
            'permissions' => '775',
        ],
    ],
]);

// Select database connection
$db = new Trestle\Database('connection_name_1');

// Run a query
$query = $db->query(...)
            ->exec();

// Return results
echo '<pre>'; print_r($query->result()); echo '</pre>';

// Count results
echo '<pre>'; print_r($query->count()); echo '</pre>';

// Debug results
echo '<pre>'; print_r($query->debug()); echo '</pre>';

// Return true/false query success
echo '<pre>'; print_r($query->status()); echo '</pre>';
```

### Raw Query
```php
// SELECT `username`, `firstname`, `email` FROM `users` WHERE `id` = ?
$query = $db->query('SELECT `username`, `firstname`, `email` FROM `users` WHERE `id` = ?', [1])
            ->exec();
```

### read
```php
// SELECT `username`, `firstname`, `email` FROM `users`
$query = $db->read('users', ['username', 'firstname', 'email'])
            ->exec();


// SELECT `username`, `firstname`, `email` FROM `users` WHERE `id` = ?
$query = $db->read('users', ['username', 'firstname', 'email'])
            ->where('id', '=', 1)
            ->exec();

            
// SELECT * FROM `users` ORDER BY ? ASC LIMIT ?, ?
$query = $db->read('users')
            ->order('id', 'ASC')
            ->offset(0)
            ->limit(5)
            ->exec();

            
// SELECT * FROM `users` WHERE `id` BETWEEN ? AND ?
$query = $db->read('users')
			->where('id', 'BETWEEN', [1, 9])
			->exec();

            
// SELECT * FROM `users` WHERE `id` NOT BETWEEN ? AND ?
$query = $db->read('users')
			->where('id', 'NOT BETWEEN', [1, 9])
			->exec();


// SELECT * FROM `users` WHERE `id` LIKE ?
$posts = $db->read('posts')
            ->where('title', 'LIKE', 'foobar')
            ->exec();

            
// SELECT `id`, `title` FROM `posts` WHERE `date` > ? AND `id` BETWEEN ? AND ? AND `author` LIKE ? ORDER BY ? ASC LIMIT ?, ?
$posts = $db->read('posts', ['id', 'title'])
            ->where('date', '>', '2014-11-20')
            ->andWhere('id', 'BETWEEN', [1, 9])
            ->andWhere('author', 'LIKE', 1)
            ->order('date', 'ASC')
            ->limit(4)
            ->offset(1)
            ->exec();
```

### Update
```php
// UPDATE `users` SET `username` = ?, `email` = ?, `firstname` = ? WHERE `id` = ?
$query = $db->update('users', [
                'username'  => 'bar',
                'email'     => 'bar@foo.tld',
                'firstname' => 'bar',
                'lastname'  => 'foo'
            ])
            ->where('id', '=', 3)
            ->exec();
```

### Create
```php
// INSERT INTO `users` (`username`, `email`, `firstname`, `lastname`, `active`, `permissions`) VALUES (?, ?, ?, ?, ?, ?);
$query = $db->create('users', [
                'username' => 'foobar',
                'email' => 'foo@bar.tld',
                'password' => 'cleartextwoot',
                'firstname' => 'Foo',
                'lastname' => 'Bar',
                'active' => 0,
                'permissions' => '{\'admin\': 0}'
            ])
            ->exec();
```

### Delete
```php
// DELETE FROM `users` WHERE `id` = ?
$delete = $db->delete('users')
             ->where('id', '=', 72)
             ->exec();
```

### JOINS
```php
// The following queryes return the same results
// SELECT `users`.`id`, `users`.`username`, `articles`.`id`, `articles`.`title` FROM `users`, `articles`
$query = $db->read(['users.id', 'users.username', 'articles.id', 'articles.title'])
            ->exec();

$query = $db->read(['users', 'articles'], ['users.id', 'users.username', 'articles.id', 'articles.title'])
            ->exec();
```

#### JOIN ON
```php
$query = $db->read(['users.id', 'users.username', 'articles.id', 'articles.title'])
            ->join('users')
            ->on('articles.author', '=', 'users.id')
            ->exec();
```
Returns
```sql
SELECT 
    `users`.`id`, 
    `users`.`username`, 
    `articles`.`id`, 
    `articles`.`title` 
FROM 
    `articles` 
JOIN 
    `users` 
ON 
    `articles`.`author` = `users`.`id`
```

#### MULTI JOIN ON
```php
$query = $db->read(['articles.id', 'articles.title', 'users.username', 'categories.name'])
            ->leftJoin('users')
            ->on('articles.author', '=', 'users.id')
            ->leftJoin('categories')
            ->on('articles.category', '=', 'categories.id')
            ->order('articles.id')
            ->exec();
```
Returns
```sql
SELECT 
    `articles`.`id`, 
    `articles`.`title`, 
    `users`.`username`, 
    `categories`.`name` 
FROM 
    `articles` 
LEFT JOIN 
    `users` 
ON 
    `articles`.`author` = `users`.`id` 
LEFT JOIN 
    `categories` 
ON 
    `articles`.`category` = `categories`.`id`
```

## Using the retuned data
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