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
        protected $_global = [];
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
            $i = 0;
            $query = '';
            foreach($this->pattern as $bit) {
                if(substr($bit, 0, 1) == '~') {
                    if(isset($this->_structure[substr($bit, 1)])) {
                        
                        $query .= $this->_structure[substr($bit, 1)];
                        $query .= (count($this->_structure) > $i ? ' ' : '');
                        
                        $this->_addBind(substr($bit, 1));
                    }
                    $i++;
                } else {
                    $query .= $bit;
                    $query .= (count($this->_structure) > $i ? ' ' : '');
                    $i--;
                }
                
            }
            return $query;
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
                        // Check named vs positional status
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
         * Builds a list of value(s) wrapped in the $varWrapper. Is not exclusive
         * to arrays.
         *
         * @param  array|string $values The value(s) to wrap.
         * @return string       The wrapped content.
         */
        protected function _stringWrapper($values) {
            foreach((array)$values as $string) {
                if($table = strstr($string, '.', true)) {
                    if(in_array($table, $this->_global['tables'])) {
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
         * @param  string       $varWrapper The string to wrap around values.
         * @return string       The wrapped content.
         */
        protected function _generateSetList($values, $varWrapper = '') {
            if(is_array($values)) {
                $count = count($values);
                $data  = '';
                $i     = 1;
                foreach($values as $key => $value) {
                    $data .= $this->_stringWrapper($key) . ' = ?';
                    if($i < $count) {
                        $data .= ', ';
                    }
                    $i++;
                }
            } else {
                $data = $this->_stringWrapper($key) . ' = ?';
            }
            return $data;
        }
        
    }

}
