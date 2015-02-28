<?php
require_once('includes/bootstrap.php');

class JoinTest extends PHPUnit_Framework_TestCase {
    
    public function __construct() {
        $this->db = new Trestle\Database('MySQL1');
    }
    
    public function testJoinMultipleTables() {
        $expects['query'] = 'SELECT * FROM `users`, `articles`';
        $expects['binds'] = [];
        $query            = $this->db->get(['users', 'articles'])
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testJoinWhere() {
        $expects['query'] = 'SELECT `articles`.`id`, `articles`.`title`, `users`.`username` FROM `articles`, `users` WHERE `articles`.`author` = `users`.`id`';
        $expects['binds'] = [];
        $query            = $this->db->get(['articles.id', 'articles.title', 'users.username'])
                                     ->where('articles.author', '=', 'users.id', true)
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
        
        $query            = $this->db->get(['articles', 'users'], ['articles.id', 'articles.title', 'users.username'])
                                     ->where('articles.author', '=', 'users.id', true)
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testJoinMethod() {
        $expects['query'] = 'SELECT `articles`.`id`, `articles`.`title`, `users`.`username` FROM `articles` JOIN `users` ON `articles`.`author` = `users`.`id`';
        $expects['binds'] = [];
        $query            = $this->db->get(['articles.id', 'articles.title', 'users.username'])
                                     ->join('users')
                                     ->on('articles.author', '=', 'users.id')
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);

        $query            = $this->db->get(['articles', 'users'], ['articles.id', 'articles.title', 'users.username'])
                                     ->join('users')
                                     ->on('articles.author', '=', 'users.id')
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testInnerJoinMethod() {
        $expects['query'] = 'SELECT `articles`.`id`, `articles`.`title`, `users`.`username` FROM `articles` INNER JOIN `users` ON `articles`.`author` = `users`.`id`';
        $expects['binds'] = [];
        $query            = $this->db->get(['articles.id', 'articles.title', 'users.username'])
                                     ->innerJoin('users')
                                     ->on('articles.author', '=', 'users.id')
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testLeftJoinMethod() {
        $expects['query'] = 'SELECT `articles`.`id`, `articles`.`title`, `users`.`username` FROM `articles` LEFT JOIN `users` ON `articles`.`author` = `users`.`id`';
        $expects['binds'] = [];
        $query            = $this->db->get(['articles.id', 'articles.title', 'users.username'])
                                     ->leftJoin('users')
                                     ->on('articles.author', '=', 'users.id')
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testRightJoinMethod() {
        $expects['query'] = 'SELECT `articles`.`id`, `articles`.`title`, `users`.`username` FROM `articles` RIGHT JOIN `users` ON `articles`.`author` = `users`.`id`';
        $expects['binds'] = [];
        $query            = $this->db->get(['articles.id', 'articles.title', 'users.username'])
                                     ->rightJoin('users')
                                     ->on('articles.author', '=', 'users.id')
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
}