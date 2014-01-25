<?php

namespace Gini\Auth;

class Database implements \Gini\Auth\Driver {

    private $db_name;
    private $table;
    private $options;

    function __construct(array $opt){

        $this->options = $opt;
        $this->db_name = $opt['database.name'];
        $this->table = $opt['database.table'] ?: '_auth';

        $db = \Gini\Database::db($this->db_name);        
        $db->adjustTable(
            $this->table, 
            array(
                'fields' => array(
                    'username'=>array('type'=>'varchar(80)', 'null'=>false, 'default'=>''),
                    'password'=>array('type'=>'varchar(100)', 'null'=>false, 'default'=>''),                
                ),
                'indexes' => array(
                    'PRIMARY'=>array('type'=>'primary', 'fields'=>array('username')),
                ),
                'engine' => $opt['database.engine']
            )
        );

    }
    
    private static function encode($password){
        // crypt SHA512
        $salt = '$6$'.\Gini\Util::randPassword(8, 2).'$';
        return crypt($password, $salt);
    }
    
    function verify($username, $password){
        $db = \Gini\Database::db($this->db_name);
        $hash = $db->value('SELECT "password" FROM :table WHERE "username"=:username', 
                    [':table'=>$this->table], 
                    [':username'=>$username]);
        if ($hash) {
            return crypt($password, $hash) == $hash;
        }

        return false;    
    }
    
    function changePassword($username, $password){
        $db = \Gini\Database::db($this->db_name);
        return false !== $db->execute('UPDATE :table SET "password"=:password WHERE "username"=:username', 
                                [':table'=>$this->table], 
                                [':password'=>self::encode($password), ':username'=>$username]);
    }
    
    function changeUserName($username, $username_new){
        $db = \Gini\Database::db($this->db_name);
        return false !== $db->query('UPDATE :table SET "username"=:new_username WHERE "username"=:old_username', 
                            [':table'=>$this->table], 
                            [':new_username'=>$username_new, ':old_username'=>$username]);
    }
    
    function add($username, $password){
        $db = \Gini\Database::db($this->db_name);
        return false !== $db->query('INSERT INTO :table ("username", "password") VALUES(:username, :password)',
                            [':table'=>$this->table],
                            [':username'=>$username, ':password'=>self::encode($password)]);
    }
    
    function remove($username){
        $db = \Gini\Database::db($this->db_name);
        return false !== $db->query('DELETE FROM :table WHERE "username"=:username',
                            [':table'=>$this->table], 
                            [':username'=>$username]);
    }
    
}
