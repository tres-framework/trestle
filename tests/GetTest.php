<?php
require_once('includes/bootstrap.php');

class GetTest extends PHPUnit_Framework_TestCase {
    
    public function __construct() {
        $this->db = new Trestle\Database('MySQL1');
    }
    
    public function testGetAllColumns() {
        $expects['query'] = 'SELECT * FROM `users`';
        $expects['binds'] = [];
        $query            = $this->db->get('users')
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testGetSpecificColumns() {
        $expects['query'] = 'SELECT `username`, `firstname`, `email` FROM `users`';
        $expects['binds'] = [];
        $query            = $this->db->get('users', ['username', 'firstname', 'email'])
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testGetMultipleTables() {
        $expects['query'] = 'SELECT * FROM `users`, `posts`';
        $expects['binds'] = [];
        $query            = $this->db->get(['users', 'posts'])
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testGetWhere() {
        $expects['query'] = 'SELECT * FROM `users` WHERE `id` = ?';
        $expects['binds'] = [1 => 1];
        $query            = $this->db->get('users')
                                     ->where('id', '=', 1)
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testGetAndWhere() {
        $expects['query'] = 'SELECT * FROM `users` WHERE `id` = ? AND `firstname` = ?';
        $expects['binds'] = [1 => 1, 2 => 'julian'];
        $query            = $this->db->get('users')
                                     ->where('id', '=', 1)
                                     ->andWhere('firstname', '=', 'julian')
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testGetOrWhere() {
        $expects['query'] = 'SELECT * FROM `users` WHERE `id` = ? OR `firstname` = ?';
        $expects['binds'] = [1 => 1, 2 => 'julian'];
        $query            = $this->db->get('users')
                                     ->where('id', '=', 1)
                                     ->orWhere('firstname', '=', 'julian')
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testGetOrder() {
        $expects['query'] = 'SELECT * FROM `users` ORDER BY `firstname` ASC';
        $expects['binds'] = [];
        $query            = $this->db->get('users')
                                     ->order('firstname', 'ASC')
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testGetGroup() {
        $expects['query'] = 'SELECT * FROM `users` GROUP BY ?';
        $expects['binds'] = [1 => 'firstname'];
        $query            = $this->db->get('users')
                                     ->group('firstname')
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testGetLimit() {
        $expects['query'] = 'SELECT * FROM `posts` LIMIT ?';
        $expects['binds'] = [1 => 3];
        $query            = $this->db->get('posts')
                                     ->limit(3)
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testGetLimitAndOffset() {
        $expects['query'] = 'SELECT * FROM `posts` LIMIT ?,?';
        $expects['binds'] = [1 => 5, 2 => 3];
        $query            = $this->db->get('posts')
                                     ->limit(3)
                                     ->offset(5)
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
}