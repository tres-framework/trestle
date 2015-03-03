<?php

namespace Trestle {

    use ReflectionClass;
    use Trestle\QueryException;
    use Trestle\Log;
    use Trestle\Process;

    /*
    |--------------------------------------------------------------------------
    | Database engineer
    |--------------------------------------------------------------------------
    | 
    | This class is used to build the queries.
    | 
    */
    class Engineer {

        /**
         * Whether the master method is set.
         * (query, get, create, update, delete)
         *
         * @var bool
         */
        private $_master = false;

        /**
         * The base method used in the query
         *
         * @var string
         */
        private $_masterMethod;
        
        /**
         * The database instance.
         *
         * @var \Trestle\Process
         */
        protected $_db;
        
        /**
         * A variable to store global data that can be used anywere in the blueprint.
         *
         * @var array
         */
        protected $_global = [];
        
        /**
         * The values to bind to the query.
         *
         * @var array
         */
        protected $_bindings  = [];

        /**
         * List of binds from blueprint.
         *
         * @var array
         */
        protected $_bind  = [];
        /**
         * The structure of the query to construct.
         *
         * @var array
         */
        protected $_structure = [];

        /**
         * A chronological list of all the methods called to create the query.
         *
         * @var array
         */
        protected $_backtrace = [];
        
        /**
         * Loads in the database.
         *
         * @param \Trestle\Process $db  The database instance.
         * @param \Trestle\Log     $log The logger.
         */
        public function __construct(Process $db) {
            $this->_db = $db;
            Log::start('build');
        }

        /**
         * Determines the end of the query.
         *
         * @return object The database object; to access methods like results(), first(), etc.
         */
        public function exec() {
            if(isset($this->pattern)) {
                $query = $this->_buildQuery();

                $debug = debug_backtrace();
                
                return $this->_db->query($query, $this->_bindings, [
                    'called'    => [
                        'file' => $debug[0]['file'],
                        'line' => $debug[0]['line'],
                    ],
                    'blueprint' => (new ReflectionClass($this))->getShortName(),
                    'backtrace' => $this->_backtrace,
                    'pattern'   => $this->pattern,
                    'error'     => null,
                    'query'     => (empty(trim($query)) ? 'No query' : $query),
                    'binds'     => $this->_bindings,
                    'execution' => [],
                ]);
            }
        }

        /**
         * Sets the pattern the query should be build into.
         *
         * @param array $pattern The pattern of the query.
         */
        protected function _setStructure(array $pattern) {
            if($this->_master) {
                throw new QueryException('Your query is already using the "' . $this->_masterMethod . '" method.');
            } else {
                $this->_master = true;
                $this->_masterMethod = debug_backtrace()[1]['function'];
            }
            $this->pattern = $pattern;
        }

        /**
         * Builds the query from the setStructure() pattern.
         *
         * @return string The query string
         */
        protected function _buildQuery() {
            if(!isset($this->pattern)) {
                throw new QueryException('Can\'t build query, no query structure set in master method!');
            }
            $query = '';
            foreach($this->pattern as $bit) {
                $patternBit = substr($bit, 1);
                if(substr($bit, 0, 1) == '~') {
                    if(isset($this->_structure[$patternBit])) {
                        $structureBit = $this->_structure[$patternBit];
                        if(is_array($structureBit)) {
                            $structureBit = $this->_buildSubQuery($structureBit);
                        }
                        $query .= $structureBit . ' ';
                        $this->_addBind($patternBit);
                    }
                } else {
                    $query .= $bit . ' ';
                }
            }
            return rtrim($query);
        }

        /**
         * Builds part of the query from an array
         * 
         * @param  array  $queryArray The query to parse in an array
         * @return string A part of the whole query
         */
        private function _buildSubQuery($queryArray) {
            $query = '';
            foreach($queryArray as $bit) {
                if(is_array($bit)) {
                    $query .= $this->_buildSubQuery($bit);
                }
                $query .= $bit . ' ';
            }
            return rtrim($query);
        }
        
        /**
         * Adds a bind to the bindings var to be used later in the query.
         * Also checks for valid binds, filters out blanks and merges the
         * arrays together
         * 
         * @param  string|array $value An array of the binds for the query
         * @return void
         */
        private function _addBind($value) {
            $checkNamed      = false;
            $checkPositional = false;
            if(isset($this->_bind[$value])) {
                if(is_array($this->_bind[$value])) {
                    foreach($this->_bind[$value] as $k => $v) {
                        // Is the key named or positional?
                        if(substr($k, 0, 1) == ':') {
                            $checkPositional = true;
                        } else {
                            $checkNamed      = true;
                        }
                        if($checkNamed != $checkPositional) {
                            $this->_bindings[$k] = $v;
                        } else {
                            throw new QueryException(
                                'You can not mix named (:example) and positional (?) bindings together.'
                            );
                        }
                    }
                } else {
                    $this->_bindings[] = $this->_bind[$value];
                }
            }
            // Bind value starts at 1 rather then 0, so we need to force the array
            // to start at 1 rather then 0; because you know... standards.
            array_unshift($this->_bindings, null);
            unset($this->_bindings[0]);
        }

        /**
         * Builds a list of value(s) Is not exclusive to arrays.
         *
         * @param  array|string $values The value(s) to generat.
         * @return string       The wrapped content.
         */
        protected function _generateList($values) {
            if(is_array($values)) {
                return implode(', ', $values);
            } else {
                return $values;
            }
        }
        
        /**
         * Builds a list of value(s) wrapped in the $varWrapper. Is not exclusive
         * to arrays.
         *
         * @param  array|string $values The value(s) to wrap.
         * @return string       The wrapped content.
         */
        protected function _generateWrapList($values) {
            foreach((array)$values as $string) {
                if($table = strstr($string, '.', true)) {
                    $pos = strpos($string, '.');
                    if($pos !== false) {
                        $string = substr_replace(
                            $string,
                            $this->_varWrapper . "." . $this->_varWrapper,
                            $pos,
                            strlen('.')
                        );
                    }
                }
                
                $allStrings[] = $this->_varWrapper . $string . $this->_varWrapper;
            }
            return implode(', ', $allStrings);
        }
        
        /**
         * Builds a list of placeholders ("?, ?, ?").
         *
         * @param  int    $count Number of binds you want.
         * @return string The bind list
         */
        protected function _generateBindList($count) {
            $data = '';
            for($i = 0; $i < $count; $i++) {
                $data .= '?';
                if($i < $count - 1) {
                    $data .= ', ';
                }
            }
            return $data;
        }

        /**
         * Creates a list of variables used when setting data in the database.
         * ex. `username` = ?, `email` = ?
         *
         * @param  array|string $values     The value(s) to wrap & set.
         * @return string       The wrapped content.
         */
        protected function _generateSetList($values) {
            if(is_array($values)) {
                $count = count($values);
                $data  = '';
                $i     = 1;
                foreach($values as $key => $value) {
                    $data .= $this->_generateWrapList($key) . ' = ?';
                    if($i < $count) {
                        $data .= ', ';
                    }
                    $i++;
                }
            } else {
                $data = $this->_generateWrapList($key) . ' = ?';
            }
            return $data;
        }
        
        /**
         * Checks if the array h
         *
         * @param  array   $values
         * @return boolean True = Has table & column | False = Only table 
         */
        protected function _checkForTablesAndColumns($values) {
            if(is_array($values)) {
                // We only need to check the first value
                $checkForColumns = $values[1];
            } else {
                $checkForColumns = $values;
            }
            
           if(strpos($checkForColumns, '.') !== false) {
                return true;
           } else {
               return false;
           }
        }
        
        /**
         * Gets all the tables from an array of join tables
         *
         * @param  array|string $values
         * @return array        Array of tables and columns
         */
        protected function _parseTables($values) {
            $tables = [];
            if(is_array($values)) {
                foreach($values as $string) {
                    if($table = strstr($string, '.', true)) {
                        $tables[] = $table;
                    }
                }
            } else {
                $tables[] = strstr($values, '.', true);
            }
            
            return array_unique($tables);
        }
        
        /**
         * Adds a table or an array of tables to the global $this->_global['table']
         * 
         * @param  array|string $table The table(s) to add to global tables
         * @return void
         */
        protected function _addTablesToGlobalTables($table) {
            if(is_array($table)) {
                foreach($table as $t) {
                    $this->_addTablesToGlobalTables($t);
                }
            } else {
                $this->_global['tables'][] = $table;
                $this->_global['tables'] = array_unique($this->_global['tables']);
            }
        }
        
        /**
         * Removes a table or an array of tables from the global $this->_global['table']
         * 
         * @param  array|string $table The table(s) to remove from global tables
         * @return void
         */
        protected function _removeTablesFromGlobalTables($table) {
            if(is_array($table)) {
                foreach($table as $t) {
                    $this->_removeTablesFromGlobalTables($t);
                }
            } else {
                if(($key = array_search($table, $this->_global['tables'])) !== false) {
                    unset($this->_global['tables'][$key]);
                }
            }
        }
        
        /**
         * Returns a list of unique tables from $this->_global['table']
         * 
         * @param  void
         * @return array List of tables
         */
        protected function _getGlobalTables() {
            return array_unique($this->_global['tables']);
        }
        
    }
    
}
