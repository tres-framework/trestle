# Trestle

*Note that Trestle is in development, it's usable but lacks some features.*

Trestle is an objected-oriented database wrapper for the PHP programming language.
It's designed to handle multiple database connections with different kinds of database drivers.
The beauty of Trestle is that it uses the same syntax for all database drivers.

This is the security package used for [Tres Framework](https://github.com/tres-framework/Tres). 
This is a stand-alone package, which means that it can also be used without the framework.

## Requirements
- PHP 5.4+

## Supported drivers
- MySQL

## Supported DB Features
### MySQL
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

And of course you can use raw queries. But note that its syntax depends on the 
driver you're using.

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
]);

// The database connection to use.
$db = new Trestle\Database('connection_name_1');

// Perform a raw query against the database
$sql = 'SELECT `username`,
               `firstname`,
               `email`
        FROM `users`
        WHERE `id` = ?
';
$bindings = [1];
$query = $db->query($sql, $bindings)
            ->exec();

// Get results
$query->result()

// Get row count
$query->count()

// Get debug information
$query->debug()

// Return true/false query success
$query->status()
```

### Getting data
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

### Updating data
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

### Creating data
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

### Deleting data
```php
// DELETE FROM `users` WHERE `id` = ?
$delete = $db->delete('users')
             ->where('id', '=', 72)
             ->exec();
```

### JOINS
```php
// The following queries return the same results
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
translates to the following (MySQL) syntax:
```sql
SELECT `users`.`id`, 
       `users`.`username`, 
       `articles`.`id`, 
       `articles`.`title` 
FROM `articles` 
JOIN `users` 
ON `articles`.`author` = `users`.`id`
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
