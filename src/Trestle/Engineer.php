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
            $this->_global['raw']      = false;
            $this->_global['raw_temp'] = false;
            Log::start('aggregation');
        }

        /**
         * Determines the end of the query.
         *
         * @return object The database object; to access methods like results(), first(), etc.
         */
        public function exec() {
            if(isset($this->pattern)) {
                $execution['aggregation'] = Log::end('aggregation');
                Log::start('build');
                $query = $this->_buildQuery();
                $execution['build'] = Log::end('build');
                
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
                    'execution' => $execution,
                ]);
            }
        }

        /**
         * Determines the end of the query.
         *
         * @return object The database object; to access methods like results(), first(), etc.
         */
        public function execRaw() {
            $this->_global['raw'] = true;
            return $this->exec();
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
         * Sets the pattern the query should be build into.
         *
         * @param  array        $pattern    The pattern of the query.
         * @param  array|string $parameters The properties each element should have.
         * @param  array|string $content    The content to join to the query and format.
         * @return void
         */
        protected function _setStructureContents($pattern, $parameters, $contents) {
            if(!is_array($parameters)) {
                $parameters = [0 => $parameters];
            }
            if(!is_array($contents)) {
                $contents = [0 => $contents];
            }
            $this->_structure[$pattern][] = [
                'parameters' => $parameters,
                'contents'   => $contents
            ];
        }

        /**
         * Resets the structure content for a specific pattern.
         *
         * @param  array $pattern    The pattern of the query.
         * @return void
         */
        protected function _resetStructureContents($pattern) {
            $this->_structure[$pattern] = [];
        }

        /**
         * Checks to see an already defined structure has data in it for the 
         * pattern value.
         *
         * @param  array   $pattern The pattern of the query.
         * @return boolean
         */
        protected function _checkStructureExist($pattern) {
            if(isset($this->_structure[$pattern])) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * Checks to see an already defined structure exists for the pattern value.
         *
         * @param  array   $pattern The pattern of the query.
         * @return boolean
         */
        protected function _checkStructureEmpty($pattern) {
            if(empty($this->_structure[$pattern])) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * Adds the proper syntax for the content
         * 
         * @param  array $pattern           The pattern of the query.
         * @param  array|string $parameters The properties each element should have.
         * @param  array|string $content    The content to join to the query and format.
         * @return string                   Formatted string.
         */
        private function _addStructureContentsParamters($pattern, $parameters, $contents) {
            if(!is_array($contents)) {
                $contents = [0 => $contents];
            }
            foreach($contents as $key => $content) {
                // Set individual parameters
                $params[$key] = $parameters;
                $rawKey       = false;
                $rawValue     = false;
                $raw          = false;
                if(in_array('noquote', $params[$key])) {
                    $quote    = false;
                } else {
                    $quote    = true;
                }
                if(strpos($content, '::') !== false) {
                    preg_match(
                        '/trestle::(.*?)::(.*?)$/', 
                        $content, 
                        $matches
                    );
                    switch($matches[1]) {
                        case 'raw':
                            if(array_search('column', $params[$key]) !== false) {
                                unset($params[$key][array_search('column', $params[$key])]);
                            }
                            if(array_search('bind', $params[$key]) !== false) {
                                unset($params[$key][array_search('bind', $params[$key])]);
                            }
                            $rawValue = true;
                            $quote    = false;
                            break;
                    }
                    $content = $matches[2];
                }
                if(in_array(['command', 'operator', 'bind'], $params[$key])) {
                    $contents[$key] = $content;
                } elseif(in_array('column', $params[$key])) {
                    $contents[$key] = $this->_generateWrapList($content, $raw, $quote);
                } elseif(in_array('bind', $params[$key])) {
                    $contents[$key] = $this->_generateBindList($pattern, $content, $raw, $quote);
                } elseif(in_array('set', $params[$key])) {
                    $contents[$key] = $this->_generateSetList($pattern, [$key => $content], $rawKey, $rawValue, $quote);
                } else {
                    $contents[$key] = $content;
                }
            }
            // Group parameters
            if(in_array('comma', $parameters)) {
                $contents = $this->_generateList($contents);
            } else {
                $contents = implode(' ', $contents);
            }
            if(in_array('parentheses', $parameters)) {
                $contents = '(' . $contents . ')';
            }
            return $contents;
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
                        foreach($this->_structure[$patternBit] as $segment) {
                            $query .= $this->_addStructureContentsParamters(
                                $patternBit,
                                $segment['parameters'],
                                $segment['contents']
                            ) . ' ';
                        }
                        $this->_addBind($patternBit);
                    }
                } else {
                    $query .= $bit . ' ';
                }
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
                            $checkNamed             = true;
                            $this->_bindings[$k]    = $v;
                        } else {
                            $checkPositional = true;
                            // Start at 1 for bindParam
                            if(empty($this->_bindings)) {
                                $this->_bindings[1] = $v;
                            } else {
                                $this->_bindings[]  = $v;
                            }
                        }
                        if($checkNamed == $checkPositional) {
                            throw new QueryException(
                                'You can not mix named (:example) and positional (?) bindings together.'
                            );
                        }
                    }
                } else {
                    if(empty($this->_bindings)) {
                        $this->_bindings[1] = $this->_bind[$value];
                    } else {
                        $this->_bindings[]  = $this->_bind[$value];
                    }
                }
            }
        }

        /**
         * Builds a list of value(s) into a string.
         *
         * @param  array|string $values The value(s) to generate.
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
                if(strstr($string, '.', true)) {
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
         * @param  string  $pattern Current pattern being used.
         * @param  int     $count   Number of binds you want.
         * @return string           The bind list
         */
        protected function _generateBindList($pattern, $values, $raw = false, $quotes = true) {
            $data = '';
            if(is_array($values)) {
                foreach($values as $key => $value) {
                    if($this->_global['raw'] === true OR $this->_global['raw_temp'] === true) {
                        $data .= $value;
                    } else {
                        $data .= '?';
                    }
                    if(isset($values[$key + 1])) {
                        $data .= ', ';
                    }
                    $this->_bind[$pattern][] = $values;
                }
            } else {
                if($this->_global['raw'] === true OR $raw === true) {
                    if($quotes === false) {
                        $data .= $values;
                    } else {
                        $data .= '\'' . addslashes($values) . '\'';
                    }
                } else {
                    $data .= '?';
                    $this->_bind[$pattern][] = $values;
                }
            }
            return $data;
        }

        /**
         * Creates a list of variables used when setting data in the database.
         * ex. `username` = ?, `email` = ?
         * 
         * @param  string       $pattern    Current pattern being used.
         * @param  array|string $values     The value(s) to wrap & set.
         * @return string                   The wrapped content.
         */
        protected function _generateSetList($pattern, $values, $rawKey = false, $rawValue = false, $quote = true) {
            if(is_array($values)) {
                $count = count($values);
                $data  = '';
                $i     = 1;
                foreach($values as $key => $value) {
                    $data .= $this->_generateWrapList($key, $rawKey, $quote) . ' = ' . $this->_generateBindList($pattern, $value, $rawValue, $quote);
                    if($i < $count) {
                        $data .= ', ';
                    }
                    $i++;
                }
            } else {
                $data = $this->_generateWrapList($values, $rawKey, $quote) . ' = ' . $this->_generateBindList($pattern, $value, $rawValue, $quote);
            }
            return $data;
        }
        
        /**
         * Checks if the array has a table present.
         *
         * @param  array   $values
         * @return boolean True = Has table & column | False = Only table 
         */
        protected function _checkForTablesAndColumns($values) {
            if(is_array($values)) {
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
            $this->_global['tables'] = array_values($this->_global['tables']);
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
        
        public function raw($value) {
            return 'trestle::raw::' . $value;
        }
        
    }
    
}
