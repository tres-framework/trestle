<?php
class SQLiteCreateUpdateDeleteTest extends PHPUnit_Framework_TestCase {
    
    public function __construct() {
        $this->db = new Trestle\Database('SQLite1');
        
        // Clean the row
        $this->db->delete('users')->where('id', '=', 1337)->exec();
    }
    
    public function testCreate() {
        $db = new Trestle\Database('SQLite1');

        $expects['query'] = 'INSERT INTO `users` (`id`, `username`, `email`, `password`, `firstname`, `lastname`, `active`, `permissions`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $expects['binds'] = [
            1 => 1337,
            2 => 'foobar',
            3 => 'foo@bar.tld',
            4 => 'cleartextwoot',
            5 => 'Foo',
            6 => 'Bar',
            7 => 0,
            8 => '{\'admin\': 0}',
        ];
        $query            = $this->db->create('users', [
                                         'id'        => 1337,
                                         'username' => 'foobar',
                                         'email' => 'foo@bar.tld',
                                         'password' => 'cleartextwoot',
                                         'firstname' => 'Foo',
                                         'lastname' => 'Bar',
                                         'active' => 0,
                                         'permissions' => '{\'admin\': 0}'
                                     ])
                                     ->exec();

        $this->assertTrue($query->status() > 0);
        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testUpdate() {
        $db = new Trestle\Database('SQLite1');

        $expects['query'] = 'UPDATE `users` SET `username` = ?, `email` = ?, `firstname` = ?, `lastname` = ? WHERE `id` = ?';
        $expects['binds'] = [
            1 => 'bar',
            2 => 'bar@foo.tld',
            3 => 'bar',
            4 => 'foo',
            5 => 1337
        ];
        $query            = $this->db->update('users', [
                                         'username'  => 'bar',
                                         'email'     => 'bar@foo.tld',
                                         'firstname' => 'bar',
                                         'lastname'  => 'foo'
                                     ])
                                     ->where('id', '=', 1337)
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
    public function testDelete() {
        $db = new Trestle\Database('SQLite1');

        $expects['query'] = 'DELETE FROM `users` WHERE `id` = ?';
        $expects['binds'] = [1 => 1337];
        $query            = $this->db->delete('users')
                                     ->where('id', '=', 1337)
                                     ->exec();

        $this->assertEquals($expects['query'], $query->debug()['query']);
        $this->assertEquals($expects['binds'], $query->debug()['binds']);
    }
    
}
