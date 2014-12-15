<?php

namespace Trestle\blueprints {

    use Trestle\Engineer;
    use Trestle\QueryException;
    use Trestle\Log;
    use Trestle\Process;

    /**
     *-------------------------------------------------------------------------
     * MySQL blueprint
     *-------------------------------------------------------------------------
     *
     * This is the blueprint for the MySQL database driver. There is a
     * blueprint for every supported database driver, because not all drivers
     * have the same SQL query syntax.
     *
     */
    class MySQL extends Engineer {
        
        /**
         * What should be wrapped around variables when using wrap methods.
         *
         * @var string
         */
        protected $_varWrapper = '`';
        
        /**
         * Loads in the database.
         *
         * @param  \Trestle\Process $db The Database instance for the query.
         */
        public function __construct(Process $db, Log $log) {
            parent::__construct($db, $log);
        }

        /**
         * Runs a raw query straight to the database instance.
         *
         * @param  string $query The query to run.
         * @param  array  $binds The binds to bind to the query.
         * @return object $this
         */
        public function query($query, $binds = []) {
            $this->_backtrace[] = __METHOD__;

            $this->_setStructure([
                "~query",
            ]);
            
            $this->_structure['query'] = $query;
            $this->_bind['query']      = $binds;
            return $this;
        }

        /**
         * Prepares to read from the database.
         *
         * @param  string       $table   The table to search.
         * @param  array|string $columns The fields to return.
         * @return object       $this
         */
        public function get($table, $columns = null) {
            $this->_backtrace[] = __METHOD__;

            $this->_setStructure([
                "SELECT",
                "~columns",
                "FROM",
                "~table",
                "~where",
                "~order",
                "~group",
                "~offset",
                "~limit",
            ]);
            
            if(!empty($columns)){
                $this->_structure['columns'] = $this->_generateWrapList($columns);
            } else {
                $this->_structure['columns'] = '*';
            }
            
            $this->_structure['table'] = $this->_generateWrapList($table, $this->_varWrapper);
            
            return $this;
        }

        /**
         * Inserts data into the database.
         *
         * @param  string $table The table to insert from.
         * @param  array  $sets  The data to set to the table.
         * @return object $this
         */
        public function create($table, array $sets) {
            $this->_backtrace[] = __METHOD__;

            $this->_setStructure([
                "INSERT INTO",
                "~table",
                "~set",
                "~where",
            ]);
            $this->_structure['table'] = $this->_generateWrapList($table, $this->_varWrapper);

            $keys   = $this->_generateWrapList(array_keys($sets), $this->_varWrapper);
            $values = $this->_generateBindList(count(array_values($sets)));

            $this->_structure['set'] = '(' . $keys . ') VALUES (' . $values . ')';
            $this->_bind['set']      = array_values($sets);

            return $this;
        }

        /**
         * Updates data in the database.
         *
         * @param  string $table The table to update from.
         * @param  array  $sets  The data to set to the table.
         * @return object $this
         */
        public function update($table, array $sets) {
            $this->_backtrace[] = __METHOD__;

            $this->_setStructure([
                "UPDATE",
                "~table",
                "SET",
                "~set",
                "~where",
            ]);

            if(empty($sets)) {
                throw new QueryException('The update method requires the second parameter to be set as an array.');
            }

            $this->_structure['table'] = $this->_generateWrapList($table, $this->_varWrapper);
            $this->_structure['set']   = $this->_generateSetList($sets);
            $this->_bind['set']        = array_values($sets);

            return $this;
        }

        /**
         * Deletes data from the database.
         *
         * @param  string $table The table to delete from.
         * @return object $this
         */
        public function delete($table) {
            $this->_backtrace[] = __METHOD__;

            $this->_setStructure([
                "DELETE FROM",
                "~table",
                "~where",
            ]);

            $this->_structure['table'] = $this->_generateWrapList($table, $this->_varWrapper);
            return $this;
        }
        
        /**
         * Multiple purpose method to simulate the WHERE condition. This method
         * can build:
         * WHERE `field` = ?
         * WHERE `field` > ?
         * WHERE `field` < ?
         * WHERE `field` >= ?
         * WHERE `field` <= ?
         * WHERE `field` BETWEEN ? AND ?
         * WHERE `field` NOT BETWEEN ? AND ?
         * WHERE `field` LIKE ?
         *
         * as well as extended WHERE statements with AND & OR.
         * WHERE `field` = ? AND `field` = ? OR `field` = ?
         *
         * @param  string       $field    The field to effect.
         * @param  string       $operator The operator to use:
         *                                =, >, <, >=, <=, BETWEEN, NOT BETWEEN,
         *                                LIKE
         * @param  array|string $value    The value(s) to pass.
         * @param  boolean      $rawBind  Bind the values immediately
         * @param  string       $prefix   If we need to pass a prefix like AND/OR.
         * @return object       $this
         */
        public function where($field, $operator, $value, $rawBind = false, $prefix = null) {
            // Avoid tracking andWhere/orWhere along side where
            if(!in_array(explode("::", end($this->_backtrace))[1], ['andWhere', 'orWhere'])) {
                $this->_backtrace[] = __METHOD__;
            }
            
            $operator = strtoupper($operator);

            if(!in_array($operator, ['=', '>', '<',  '>=', '<=', '!=', 'BETWEEN', 'NOT BETWEEN', 'LIKE'])) {
                throw new QueryException('Please use a valid operator.');
            }
            
            if(is_array($value) && !in_array($operator, ['BETWEEN', 'NOT BETWEEN'])) {
                throw new QueryException('The where method can not accept an array value if the operator is not "BETWEEN" & "NOT BETWEEN"');
            }
            
            if($rawBind === true) {
                if(in_array($operator, ['BETWEEN', 'NOT BETWEEN']) && is_array($value)) {
                    $binds = "{$value[0]} AND {$value[1]}";
                } else {
                    $binds = $value;
                }
            } else {
                if(in_array($operator, ['BETWEEN', 'NOT BETWEEN']) && is_array($value)) {
                    $binds = '? AND ?';
                } else {
                    $binds = '?';
                }
            }
            
            if($rawBind === true) {
                $field = $this->_generateWrapList($field);
            } else {
                $field = $this->_generateWrapList($field, $this->_varWrapper);
            }
            $this->_structure['where'] = (isset($prefix) && !empty($prefix) ? $this->_structure['where'] . ' ' . $prefix . ' ' : 'WHERE ') . "{$field} {$operator} " . $binds;
            
            if($rawBind === false) {
                if(is_array($value)) {
                    $this->_bind['where'] = array_merge($this->_bind['where'], $value);
                } else {
                    $this->_bind['where'][] = ($operator == 'LIKE' ? '%' . $value . '%' : $value);
                }
            }

            return $this;
        }

        /**
         * Used as a container for the where() method to add more where clauses.
         *
         * @param  string       $field    The field to effect.
         * @param  string       $operator The operator to use:
         *                                =, >, <, >=, <=, BETWEEN, NOT BETWEEN,
         *                                LIKE
         * @param  array|string $value    The value(s) to pass.
         * @param  boolean      $rawBind  Bind the values immediately
         * @return object       $this
         */
        public function andWhere($field, $operator, $value, $rawBind = false) {
            $this->_backtrace[] = __METHOD__;

            if(empty($this->_structure['where'])) {
                throw new QueryException('You can not call the andWhere() method before calling the where() method.');
            }
            $this->where($field, $operator, $value, $rawBind, 'AND');

            return $this;
        }

        /**
         * Used as a container for the where() method to add more where clauses.
         *
         * @param  string       $field    The field to effect.
         * @param  string       $operator The operator to use:
         *                                =, >, <, >=, <=, BETWEEN, NOT BETWEEN,
         *                                LIKE
         * @param  array|string $value    The value(s) to pass.
         * @param  boolean      $rawBind  Bind the values immediately
         * @return object       $this
         */
        public function orWhere($field, $operator, $value, $rawBind = false) {
            $this->_backtrace[] = __METHOD__;

            if(empty($this->_structure['where'])) {
                throw new QueryException('You can not call the andWhere() method before calling the where() method.');
            }
            $this->where($field, $operator, $value, $rawBind, 'OR');

            return $this;
        }

        /**
         * Sets the order of the returned data.
         *
         * @param  string $fields The field to base the order off.
         * @param  string $order  Either ASC|DESC; the order.
         * @return object $this
         */
        public function order($fields, $order){
            $this->_backtrace[] = __METHOD__;

            $this->_structure['order'] = "ORDER BY ";
            // We might want to consider validation
            if(is_array($fields)) {
                $this->_structure['order'] .= $this->_generateBindList(count($fields)) . ' ';
            } else {
                $this->_structure['order'] .= '? ';
            }
            $this->_bind['order'] = $fields;
            // Should we assume an order?
            if(in_array($order, ['ASC', 'DESC'])) {
                $this->_structure['order'] .= $order;
            } else {
                $this->_structure['order'] .= 'ASC';
            }
            return $this;
        }

        /**
         * Groups the returned data by a column.
         *
         * @param  string $value The field to group by.
         * @return object $this
         */
        public function group($value) {
            $this->_backtrace[] = __METHOD__;

            $this->_structure['group'] = 'GROUP BY ?';
            $this->_bind['group']      = $value;
            return $this;
        }

        /**
         * How much the returned data should be offset. This is used with the
         * limit() method to set the LIMIT clause.
         *
         * LIMIT <offset>, <limit>
         *
         * @param  int    $offset The amount to offset by.
         * @return object $this
         */
        public function offset($offset) {
            $this->_backtrace[] = __METHOD__;

            //  Better debugging should be supplied
            if(!is_int($offset)) {
                throw new QueryException('The offset method must be supplied an integer.');
            }
            if(isset($this->_bind['limit'])) {
                $this->_structure['offset'] = 'LIMIT ?,';
                $this->_structure['limit']  = "?";
            }
            
            $this->_bind['offset'] = $offset;
            return $this;
        }

        /**
         * How much of the returned data should be returned. This is used with the
         * offset() method to set the LIMIT clause.
         *
         * LIMIT <offset>, <limit>
         *
         * @param  int    $limit The amount to limit by.
         * @return object $this
         */
        public function limit($limit) {
            $this->_backtrace[] = __METHOD__;

            if(!is_int($limit)) {
                throw new QueryException('The limit method must be supplied an integer.');
            }
            if(isset($this->_bind['offset'])) {
                $this->_structure['offset'] = 'LIMIT ?,';
                $this->_structure['limit']  = "?";
            } else {
                $this->_structure['limit'] = "LIMIT ?";
            }
            
            $this->_bind['limit'] = $limit;
            
            return $this;
        }

    }

}
