<?php
require_once('includes/bootstrap.php');

class JoinTest extends PHPUnit_Framework_TestCase {
    
    public function __construct() {
        $this->db = new Trestle\Database('MySQL1');
    }
    
    public function testJoinMultipleTables() {
        $expects['query'] = 'SELECT * FROM `users`, `posts`';
        $expects['binds'] = [];
        $query            = $this->db->get(['users', 'posts'])
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testJoinMethod() {
        $expects['query'] = 'SELECT * FROM `users` JOIN `posts`';
        $expects['binds'] = [];
        $query            = $this->db->get('users')
                                     ->join('posts')
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testJoinOn() {
        $expects['query'] = 'SELECT `posts`.`id`, `posts`.`title`, `users`.`username` FROM `posts` JOIN `users` ON `posts`.`author` = `users`.`id`';
        $expects['binds'] = [];
        $query            = $this->db->get('posts', ['posts.id', 'posts.title'])
                                     ->join('users', ['users.username'])
                                     ->on('posts.author', '=', 'users.id')
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
     
}