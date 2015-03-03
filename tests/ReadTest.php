<?php
require_once('includes/bootstrap.php');

class ReadTest extends PHPUnit_Framework_TestCase {
    
    public function __construct() {
        $this->db = new Trestle\Database('MySQL1');
    }
    
    public function testReadAllColumns() {
        $expects['query'] = 'SELECT * FROM `users`';
        $expects['binds'] = [];
        $query            = $this->db->read('users')
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testReadSpecificColumns() {
        $expects['query'] = 'SELECT `username`, `firstname`, `email` FROM `users`';
        $expects['binds'] = [];
        $query            = $this->db->read('users', ['username', 'firstname', 'email'])
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testReadMultipleTables() {
        $expects['query'] = 'SELECT * FROM `users`, `articles`';
        $expects['binds'] = [];
        $query            = $this->db->read(['users', 'articles'])
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testReadWhere() {
        $expects['query'] = 'SELECT * FROM `users` WHERE `id` = ?';
        $expects['binds'] = [1 => 1];
        $query            = $this->db->read('users')
                                     ->where('id', '=', 1)
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testReadAndWhere() {
        $expects['query'] = 'SELECT * FROM `users` WHERE `id` = ? AND `firstname` = ?';
        $expects['binds'] = [1 => 1, 2 => 'julian'];
        $query            = $this->db->read('users')
                                     ->where('id', '=', 1)
                                     ->andWhere('firstname', '=', 'julian')
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testReadOrWhere() {
        $expects['query'] = 'SELECT * FROM `users` WHERE `id` = ? OR `firstname` = ?';
        $expects['binds'] = [1 => 1, 2 => 'julian'];
        $query            = $this->db->read('users')
                                     ->where('id', '=', 1)
                                     ->orWhere('firstname', '=', 'julian')
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testReadOrder() {
        $expects['query'] = 'SELECT * FROM `users` ORDER BY `firstname` ASC';
        $expects['binds'] = [];
        $query            = $this->db->read('users')
                                     ->order('firstname', 'ASC')
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testReadGroup() {
        $expects['query'] = 'SELECT * FROM `users` GROUP BY ?';
        $expects['binds'] = [1 => 'firstname'];
        $query            = $this->db->read('users')
                                     ->group('firstname')
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testReadLimit() {
        $expects['query'] = 'SELECT * FROM `articles` LIMIT ?';
        $expects['binds'] = [1 => 3];
        $query            = $this->db->read('articles')
                                     ->limit(3)
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testReadLimitAndOffset() {
        $expects['query'] = 'SELECT * FROM `articles` LIMIT ?,?';
        $expects['binds'] = [1 => 5, 2 => 3];
        $query            = $this->db->read('articles')
                                     ->limit(3)
                                     ->offset(5)
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
}