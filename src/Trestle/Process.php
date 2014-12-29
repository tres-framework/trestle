<?php

namespace Trestle {
    
    use PDO;
    use PDOException;
    use Trestle\Config;
    use Trestle\DatabaseException;
    use Trestle\QueryException;
    use Trestle\Log;
    
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
            $driver = strtolower($config['driver']);
            
            try {
                $this->_connection = new PDO(
                    $driver.':'.
                    'host='.$config['host'].';'.
                    'dbname='.$config['database'].';'.
                    (isset($config['port']) ? $config['port'].';' : '').
                    (isset($config['charset']) ? $config['charset'].';' : ''),
                    $config['username'],
                    $config['password']
                );
                
                $this->_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                return $this->_connection;
            } catch(PDOException $e) {
                $error  = 'Trestle could not connect to the database.';
                $msg  = $error.PHP_EOL;
                $msg .= "|-> " . $e->getMessage();
                Log::database($msg);
                throw new DatabaseException($error . '  Please check your database configuration and the logs for more information.');
            }
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
            try {
                Log::start('query');
                
                $this->_debug = $debug;
                $this->statement = $this->_connection->prepare($statement);
                
                $i = 1;
                if(count($binds)) {
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
                        $i++;
                    }
                }
                
                if($this->statement->execute()){
                    $this->status = true;
                } else {
                    $this->status = false;
                }
                
                // Logs
                $this->_debug['execution']['build'] = Log::end('build');
                $this->_debug['execution']['query'] = Log::end('query');
                $this->_debug['execution']['total'] = Log::end('total');
                
                $msg  = "Query Request".PHP_EOL;
                $msg .= "|-> ".$this->_debug['query'].PHP_EOL;
                $msg .= "|-> Query executed in {$this->_debug['execution']['total']} seconds.".PHP_EOL;
                $msg .= "|-> Called in {$this->_debug['called']['file']} on line {$this->_debug['called']['line']}".PHP_EOL;
                $msg .-" |-> {$this->_debug['query']}";
                
                Log::request($msg);
                
            } catch(PDOException $e) {
                $this->_debug['error'] = $e->getMessage();
                
                $msg  = "Query failed!".PHP_EOL;
                $msg .= "|-> " . $this->_debug['query'].PHP_EOL;
                $msg .= "|-> " . $this->_debug['error'].PHP_EOL;
                
                Log::query($msg);
                
                if(Config::get('display_errors/query')) {
                    throw new QueryException($this->_debug['error']);
                }
                
            }
            
            return $this;
        }
        
        /**
         * Returns all the results of a query.
         *
         * @return array|object
         */
        public function results() {
            return $this->statement->fetchAll(PDO::FETCH_OBJ);
        }
        
        /**
         * Returns the first result from the query.
         *
         * @return object
         */
        public function first() {
            return $this->statement->fetch(PDO::FETCH_OBJ);
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
        
    }

}
