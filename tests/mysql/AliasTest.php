<?php
class MySQLAliasTest extends PHPUnit_Framework_TestCase {
    
    public function __construct() {
        $this->db = new Trestle\Database('MySQL1');
    }
    
    public function testFieldAlias() {
        $expects['query'] = 'SELECT `articles`.`id` AS `id`, `articles`.`title` AS `title`, `users`.`username` AS `author` FROM `articles`, `users` WHERE `articles`.`author` = users.id';
        $expects['binds'] = [];
        $tables = [
            'articles',
            'users'
        ];
        $fields = [
            'articles.id'    => 'id',
            'articles.title' => 'title',
            'users.username' => 'author'
        ];
        $query = $this->db->read($tables, $fields)
                          ->where('articles.author', '=', $this->db->raw('users.id'))
                          ->exec();
        
        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
}
