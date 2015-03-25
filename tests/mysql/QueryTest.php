<?php
class MySQLQueryTest extends PHPUnit_Framework_TestCase {
    
    public function __construct() {
        $this->db = new Trestle\Database('MySQL1');
    }
    
    public function testQuery() {
        $db = new Trestle\Database('MySQL1');

        $expects['query'] = 'SELECT * FROM `users`';
        $expects['binds'] = [];
        $query            = $this->db->query('SELECT * FROM `users`')
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testQueryBind() {
        $db = new Trestle\Database('MySQL1');

        $expects['query'] = 'SELECT `username`, `firstname`, `email` FROM `users` WHERE `id` = ?';
        $expects['binds'] = [1 => 1];
        $query            = $this->db->query('SELECT `username`, `firstname`, `email` FROM `users` WHERE `id` = ?', [1])
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
}
