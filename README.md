# Trestle

*Note that Trestle is in development, it's usable but lacks some features.*

Trestle is an objected-oriented database wrapper for the PHP programming language.
It's designed to handle multiple database connections with different kinds of database drivers.
The beauty of Trestle is that it uses the same syntax for all database drivers.

This is the database package used for [Tres Framework](https://github.com/tres-framework/Tres). 
This is a stand-alone package, which means that it can also be used without the framework.

## Requirements
- PHP 5.4+

## Supported drivers
- MySQL

## Supported DB Features
### MySQL
- SELECT (read)
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
- ON
    - AND (andOn)
    - OR (orOn)

And of course you can use raw queries. But note that its syntax depends on the 
driver you're using.

## Basic Usage
### Autoload files
Autoload all Trestle files however you wish. Here is some code to get you started:
```php
spl_autoload_register(function($class){
    $dirs = [
        dirname(__DIR__).'/Trestle/',
        // ...
    ];
    
    foreach($dirs as $dir){
        $file = str_replace('\\', '/', rtrim($dir, '/').'/'.$class.'.php');
        
        if(is_readable($file)){
            require_once($file);
            break;
        }
    }
});
```

### Configuring Trestle
```php
Trestle\Config::set([
    
    /*
    |--------------------------------------------------------------------------
    | Exceptions
    |--------------------------------------------------------------------------
    | 
    | The kind of exceptions to throw.
    |
    */
    'throw' => [
        'database' => true,
        'query'    => true,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Default database
    |--------------------------------------------------------------------------
    | 
    | The database to use if no database is provided.
    |
    */
    'default' => 'main',
    
    /*
    |--------------------------------------------------------------------------
    | Database connections
    |--------------------------------------------------------------------------
    | 
    | A list of databases to use.
    |
    */
    'connections' => [
        'main' => [
            /*
            |------------------------------------------------------------------
            | Driver
            |------------------------------------------------------------------
            | 
            | The kind of database driver to use.
            |
            | Supported drivers:
            | - MySQL
            |
            */
            'driver' => 'MySQL',
            
            /*
            |------------------------------------------------------------------
            | Database
            |------------------------------------------------------------------
            | 
            | The database to use.
            |
            */
            'database' => 'snippitz',
            
            /*
            |------------------------------------------------------------------
            | Host
            |------------------------------------------------------------------
            | 
            | The database host. Using an IP address is likely to be faster.
            |
            */
            'host' => '127.0.0.1',
            
            /*
            |------------------------------------------------------------------
            | Port
            |------------------------------------------------------------------
            | 
            | The database port.
            | 
            | MySQL default: 3306.
            |
            */
            'port' => '3306',
            
            /*
            |------------------------------------------------------------------
            | Username
            |------------------------------------------------------------------
            | 
            | This is the database user. It's recommended to limit the user's 
            | permissions to the minimum application requirement.
            |
            */
            'username' => 'root',
            
            /*
            |------------------------------------------------------------------
            | Password
            |------------------------------------------------------------------
            | 
            | This is the password for the database. Be sure not to commit a 
            | production password in version control.
            | 
            */
            'password' => 'password'
            
            /*
            |------------------------------------------------------------------
            | Charset
            |------------------------------------------------------------------
            | 
            | The character encoding to use.
            |
            */
            'charset' => 'utf8',
        ],
    ],
    
]);
```

### Starting a new database connection.
```php
// Uses the default database connection from the config.
$db = new Trestle\Database(); // "connection_name_1"
```

```php
$db = new Trestle\Database('connection_name_1');
```

```php
$db = new Trestle\Database('connection_name_2');
```

### Querying
#### Raw queries
Sometimes it's just not possible to use the fluent interface. In that case,
you can fall back to using raw queries. But note that the syntax will depend on
the database driver you're using.
```php
$sql = 'SELECT `username`,
               `firstname`,
               `email`
        FROM `users`
        WHERE `id` = :id
';
$bindings = [
    'id' => 1
];
$query = $db->query($sql, $bindings)
            ->exec();
```

#### Getting data
```php
// SELECT `username`, `firstname`, `email` FROM `users`
$query = $db->read('users', ['username', 'firstname', 'email'])
            ->exec();
```

```php
// SELECT `username`, `firstname`, `email` FROM `users` WHERE `id` = ?
$query = $db->read('users', ['username', 'firstname', 'email'])
            ->where('id', '=', 1)
            ->exec();
```

```php        
// SELECT * FROM `users` ORDER BY ? ASC LIMIT ?, ?
$query = $db->read('users')
            ->order('id', 'ASC')
            ->offset(0)
            ->limit(5)
            ->exec();
```

```php
// SELECT * FROM `users` WHERE `id` BETWEEN ? AND ?
$query = $db->read('users')
            ->where('id', 'BETWEEN', [1, 9])
            ->exec();
```

```php
// SELECT * FROM `users` WHERE `id` NOT BETWEEN ? AND ?
$query = $db->read('users')
            ->where('id', 'NOT BETWEEN', [1, 9])
            ->exec();
```

```php
// SELECT * FROM `users` WHERE `id` LIKE ?
$posts = $db->read('posts')
            ->where('title', 'LIKE', 'foobar')
            ->exec();
```

```php
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

##### JOINS
```php
// The following queries return the same results
// SELECT `users`.`id`, `users`.`username`, `articles`.`id`, `articles`.`title` FROM `users`, `articles`
$query = $db->read(['users.id', 'users.username', 'articles.id', 'articles.title'])
            ->exec();

$query = $db->read(['users', 'articles'], ['users.id', 'users.username', 'articles.id', 'articles.title'])
            ->exec();
```

###### JOIN ON
```php
$query = $db->read(['users.id', 'users.username', 'articles.id', 'articles.title'])
            ->join('users')
            ->on('articles.author', '=', 'users.id')
            ->exec();
```
In MySQL, above code gets translated to the following:
```sql
SELECT `users`.`id`, 
       `users`.`username`, 
       `articles`.`id`, 
       `articles`.`title` 
FROM `articles` 
JOIN `users` 
ON `articles`.`author` = `users`.`id`
```

###### MULTI JOIN ON
```php
$query = $db->read(['articles.id', 'articles.title', 'users.username', 'categories.name'])
            ->leftJoin('users')
            ->on('articles.author', '=', 'users.id')
            ->leftJoin('categories')
            ->on('articles.category', '=', 'categories.id')
            ->order('articles.id')
            ->exec();
```
In MySQL, above code gets translated to the following:
```sql
SELECT `articles`.`id`, 
       `articles`.`title`, 
       `users`.`username`, 
       `categories`.`name` 
FROM `articles` 
LEFT JOIN `users` 
ON `articles`.`author` = `users`.`id` 
LEFT JOIN `categories` 
ON `articles`.`category` = `categories`.`id`
ORDER BY `articles`.`id`
```

#### Updating data
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

#### Creating data
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

#### Deleting data
```php
// DELETE FROM `users` WHERE `id` = ?
$delete = $db->delete('users')
             ->where('id', '=', 72)
             ->exec();
```

### Getting results
#### Getting the first result
```php
$query->result()
```
#### Getting all of the results
```php
$query->results()
```

#### Getting the row count
```php
$query->count()
```

#### Getting debug information
```php
$query->debug()
```

#### Getting the query status
Shows whether the query executed successfully (true) or not (false).
```php
$query->status()
```
