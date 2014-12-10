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
         * The new line indicator.
         */
        const CRLF = PHP_EOL;
        
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
                
                if(Config::get('display_errors')){
                    $this->_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                }
                
                return $this->_connection;
            } catch(PDOException $e){
                throw new DatabaseException($e);
            }
        }
        
        /**
         * Prepares the query and executes it.
         *
         * @param  string $statement The query to parse.
         * @param  array  $binds     The values to bind to the string.
         * @param  array  $debug     The debug information from the blueprint.
         * @param  object $log       The Log instance of the current query.
         * @return object
         */
        public function query($statement, array $binds = array(), array $debug = array(), Log $log = null) {
            try {
                if(isset($log) && $log instanceof Log) {
                    $log->start('query');
                }
                   
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
                            $param = false; // NOTE: Must log and throw exception or default to string(?)
                        }
                        
                        $this->statement->bindValue($key, $bind, $param);
                        $i++;
                    }
                }
                
                if($this->statement->execute()){
                    $this->status = true;
                } else {
                    $this->status = false;
                    if(isset($log) && $log instanceof Log) {
                        $log->log("Query failed!\r\n|-> {$this->_debug['query']}");
                    }
                    throw new QueryException('Query failed?!?');
                }
                
                // Logs
                if(isset($log) && $log instanceof Log) {
                    $this->_debug['execution']['build'] = $log->end('build');
                    $this->_debug['execution']['query'] = $log->end('query');
                    $this->_debug['execution']['total'] = $log->end('total');
                    
                    $msg  = self::CRLF;
                    $msg .= "|-> Query executed in {$this->_debug['execution']['total']} seconds.".self::CRLF;
                    $msg .= "|-> Called in {$this->_debug['called']['file']}"
                           ." on line {$this->_debug['called']['line']}".self::CRLF;
                    $msg .-" |-> {$this->_debug['query']}";
                    
                    $log->log($msg);
                }
            } catch(PDOException $e) {
                $this->_debug['error'] = $e->getMessage();
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
