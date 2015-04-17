<?php

namespace Trestle {
    
    use PDO;
    use PDOException;
    use Trestle\Config;
    use Trestle\Stopwatch;
    use Trestle\DatabaseException;
    use Trestle\QueryException;
    
    /*
    |--------------------------------------------------------------------------
    | Database connection
    |--------------------------------------------------------------------------
    |
    | Establishes the connection to the database.
    |
    */
    class Process {
        
        /**
         * The database connection instance.
         *
         * @var \PDO
         */
        protected $_connection = null;
        
        /**
         * Current database we are working with.
         *
         * @var string
         */
        private $_activeDB;
        
        /**
         * The debug parameters.
         *
         * @var array
         */
        private $_debug = [];
        
        /**
         * Instantiates the connection of the database.
         *
         * @param  array  $config The database config.
         * @return object
         */
        public function connection($config) {
            $this->_activeDB = $config['database'];
            
            try {
                $this->_connection = new PDO(
                    $this->_generateDSN($config),
                    (isset($config['username']) ? $config['username'] : null),
                    (isset($config['password']) ? $config['password'] : null)
                );
                
                $this->_connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
                $this->_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->_connection->setAttribute(PDO::ATTR_TIMEOUT, (isset($config['timeout']) ? $config['timeout'] : 25));
                
                return $this->_connection;
                
            } catch(PDOException $e) {
                if(Config::get('throw/database')) {
                    throw new DatabaseException("Connection failed for \"{$this->_activeDB}\" database.");
                }
                
            }
            
        }
        
        /**
         * Generates a DSN string for a PDO connection.
         *
         * @param  array  $config Config values for DSN string.
         * @return string A valid PDO DSN string.
         */
        private function _generateDSN($config) {
            $dsnPattern = dirname(__FILE__) . '/dsn/' . $config['driver'] . '.dsn';
            if(!is_readable($dsnPattern)) {
                throw new DatabaseException('Missing DSN pattern or is not readable for a ' . $config['driver'] . ' connection.');
            }
            
            $dsnTemplateString = file_get_contents($dsnPattern);
            
            return $this->_parseDSNString($config, $dsnTemplateString);
        }
        
        /**
         * Converts the template dsn string like this:
         * Creates dsn string: mysql:host={~host};dbname={~database};{~port};{~charset};
         * 
         * To this:
         * Creates dsn string: mysql:host=127.0.0.1;dbname=example;3306;utf-8;
         * 
         * @param  array  $config Config values for DSN string.
         * @param  string $string The DSN string.
         * @return string A valid PDO DSN string.
         */
        protected function _parseDSNString($config, $string){
            $placeholders = array_map(
                function($value){
                    return '{~' . $value . '}';
                },
                array_keys($config)
            );
            $binds = array_values($config);

            $dsn = str_replace($placeholders, $binds, $string);
            $dsn = rtrim($dsn);

            return $dsn;
        }
        
        /**
         * Prepares the query and executes it.
         *
         * @param  string $statement The query to parse.
         * @param  array  $binds     The values to bind to the string.
         * @param  array  $debug     The debug information from the blueprint.
         * @return object
         */
        public function query($statement, array $binds = array(), array $debug = array()) {
            if(!$this->_connection instanceof PDO) {
                throw new DatabaseException('No database connection detected, does the variable have a Trestle instance or has the connection been disconnected?');
            }
            
            try {
                Stopwatch::start('request');
                
                $this->_debug    = $debug;
                $this->statement = $this->_connection->prepare($statement);
                
                foreach($binds as $key => $bind) {
                    if(is_int($bind)) {
                        $param = PDO::PARAM_INT;
                    } elseif(is_bool($bind)) {
                        $param = PDO::PARAM_BOOL;
                    } elseif(is_null($bind)) {
                        $param = PDO::PARAM_NULL;
                    } elseif(is_string($bind)) {
                        $param = PDO::PARAM_STR;
                    } else {
                        $param = null;
                    }
                    
                    $this->statement->bindValue($key, $bind, $param);
                }
                
                if($this->statement->execute()){
                    $this->status = true;
                } else {
                    $this->status = false;
                }
                
            } catch(PDOException $e) {
                $this->_debug['error'] = $e->getMessage();
                
                if(Config::get('throw/query')) {
                    throw new QueryException($this->_debug['error']);
                }
                
            }
            
            $this->_debug['execution']['request'] = Stopwatch::stop('request');
            $this->_debug['execution']['total']   = Stopwatch::stop('total');
            
            return $this;
        }
		
        /**
         * Returns all the results of a query.
         * 
		 * @param string $fetch The fetch mode
         * @return array|object
         */
        public function results($fetch = PDO::FETCH_OBJ) {
            return $this->statement->fetchAll($fetch);
        }
        
        /**
         * Returns the first result from the query.
         * 
		 * @param string $fetch The fetch mode
         * @return object
         */
        public function result($fetch = PDO::FETCH_OBJ) {
            return $this->statement->fetch($fetch);
        }
        
        /**
         * Returns the number of rows queried.
         *
         * @return int
         */
        public function count() {
            return $this->statement->rowCount();
        }
        
        /**
         * The status of the query.
         *
         * @return bool Tells whether the query succeeded or not.
         */
        public function status() {
            return $this->statement->rowCount();
        }
        
        /**
         * The debug data from the blueprint.
         *
         * @return array
         */
        public function debug() {
            return $this->_debug;
        }
        
        /**
         * Disconnects the pdo connection
         *
         * @return void
         */
        public function disconnect() {
            $this->_connection = null;
        }
    }
}
