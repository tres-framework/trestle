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
         * Set MySQL specific global flags.
         *
         * @var boolean
         */
        protected $_global = ['raw' => false];
        
        /**
         * Loads in the database.
         *
         * @param  \Trestle\Process $db The Database instance for the query.
         */
        public function __construct(Process $db) {
            parent::__construct($db);
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
            
            $this->_setStructureContents('query', ['query'], $query);
            $this->_bind['query'] = $binds;
            
            return $this;
        }

        /**
         * Prepares to read from the database.
         *
         * @param  string       $table  The table to search.
         * @param  array|string $column The fields to return.
         * @return object       $this
         */
        public function read($table, $column = null) {
            $this->_backtrace[] = __METHOD__;
            
            $this->_setStructure([
                "~column",
                "~table",
                "~join",
                "~on",
                "~where",
                "~order",
                "~group",
                "~limit",
            ]);
            
            if($this->_checkForTablesAndColumns($table)) {
                $column = $table;
                $table  = $this->_parseTables($table);
            }
            
            $this->_addTablesToGlobalTables($table);
            
            $this->_setStructureContents('column', ['command'], 'SELECT');
            
            if(!empty($column)){
                $this->_setStructureContents('column', ['column', 'comma'], $column);
            } else {
                $this->_setStructureContents('column', [], '*');
            }
            
            $this->_setStructureContents('table', ['command'], 'FROM');
            $this->_setStructureContents('table', ['column', 'comma'], $table);
            
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
                "~table",
                "~set",
                "~where",
            ]);
            
            $this->_setStructureContents('table', ['command'], 'INSERT INTO');
            $this->_setStructureContents('table', ['column', 'comma'], $table);
            
            $this->_setStructureContents('set', ['column', 'comma', 'parentheses'], array_keys($sets));
            $this->_setStructureContents('set', ['command'], 'VALUES');
            $this->_setStructureContents('set', ['bind', 'comma', 'parentheses'], array_values($sets));
            
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
                "~table",
                "~set",
                "~where",
            ]);

            if(empty($sets)) {
                throw new QueryException('The update method requires the second parameter to be set as an array.');
            }
            
            $this->_setStructureContents('table', ['command'], 'UPDATE');
            $this->_setStructureContents('table', ['column', 'comma'], $table);
            
            $this->_setStructureContents('set', ['command'], 'SET');
            
            $this->_setStructureContents('set', ['set', 'comma'], $sets);
            
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
                "~table",
                "~where",
            ]);
            
            $this->_setStructureContents('table', ['command'], 'DELETE FROM');
            $this->_setStructureContents('table', ['column', 'comma'], $table);
            
            return $this;
        }

        /**
         * Specifics what tables, columns and data should be joined.
         *
         * @param  string       $field    The field to effect.
         * @param  string       $operator The operator to use:
         *                                =, >, <, >=, <=, BETWEEN, NOT BETWEEN,
         *                                LIKE
         * @param  array|string $value    The value(s) to pass.
         * @return object $this
         */
        public function join($table, $type = 'JOIN') {
            if(!in_array(explode("::", end($this->_backtrace))[1], ['innerJoin', 'leftJoin', 'rightJoin'])) {
                $this->_backtrace[] = __METHOD__;
            }
            
            $type = strtoupper($type);
            
            if(!in_array($type, ['JOIN', 'INNER JOIN', 'LEFT JOIN', 'RIGHT JOIN', 'FULL OUTER JOIN'])) {
                throw new QueryException('Please use a valid JOIN type.');
            }
            
            $this->_removeTablesFromGlobalTables($table);
            
            $this->_resetStructureContents('table');
            
            $this->_setStructureContents('table', ['command'], 'FROM');
            $this->_setStructureContents('table', ['column', 'comma'], $this->_getGlobalTables());
            
            $this->_setStructureContents('join', ['command'], $type);
            $this->_setStructureContents('join', ['column', 'comma'], $table);
            
            
            $this->_global['on'] = false;
            
            return $this;
        }
        
        /**
         * Create an INNER JOIN
         *
         * @param  string $table The table to join
         * @return object $this
         */
        public function innerJoin($table) {
            $this->_backtrace[] = __METHOD__;
            
            $this->join($table, 'INNER JOIN');
            
            return $this;
        }
        
        /**
         * Create an LEFT JOIN
         *
         * @param  string $table The table to join
         * @return object $this
         */
        public function leftJoin($table) {
            $this->_backtrace[] = __METHOD__;
            
            $this->join($table, 'LEFT JOIN');
            
            return $this;
        }
        
        /**
         * Create an RIGHT JOIN
         *
         * @param  string $table The table to join
         * @return object $this
         */
        public function rightJoin($table) {
            $this->_backtrace[] = __METHOD__;
            
            $this->join($table, 'RIGHT JOIN');
            
            return $this;
        }
        
        /**
         * Create an FULL OUTER JOIN
         *
         * @param  string $table The table to join
         * @return object $this
         */
        public function fullOuterJoin($table) {
            $this->_backtrace[] = __METHOD__;
            
            $this->join($table, 'FULL OUTER JOIN');
            
            return $this;
        }
        
        /**
         * Joins table column A to column B
         *
         * @param  string       $field    The field to effect.
         * @param  string       $operator The operator to use:
         *                                =, >, <, >=, <=, BETWEEN, NOT BETWEEN,
         *                                LIKE
         * @param  array|string $value    The value(s) to pass.
         * @param  boolean      $rawBind  Bind the values immediately
         * @param  string       $prefix   If we need to pass a prefix like AND/OR.
         * @return object $this
         */
        public function on($field, $operator, $value, $rawBind = false, $prefix = null) {
            if(!in_array(explode("::", end($this->_backtrace))[1], ['andOn', 'orOn'])) {
                $this->_backtrace[] = __METHOD__;
            }
            
            if($this->_checkStructureEmpty('join')) {
                throw new QueryException('You can not call the on() method before calling the join() method.');
            }
            
            $operator = strtoupper($operator); 

            if(!in_array($operator, ['=', '>', '<',  '>=', '<=', '!=', 'BETWEEN', 'NOT BETWEEN', 'LIKE'])) {
                throw new QueryException('Please use a valid operator.');
            }
            
            if($this->_global['on'] === true) {
                if($prefix == null) {
                    $this->_setStructureContents('join', ['command'], 'AND');
                } elseif($prefix != null) {
                    $this->_setStructureContents('join', ['command'], $prefix);
                }
            } else {
                $this->_global['on'] = true;
                $this->_setStructureContents('join', ['command'], 'ON');
            }
            
            $this->_setStructureContents('join', ['column', 'comma'], $field);
            $this->_setStructureContents('join', ['operator'], $operator);
            $this->_setStructureContents('join', ['column'], $value);
            
            return $this;
        }
        
        /**
         * Joins an additional table column A to column B
         *
         * @param  string       $field    The field to effect.
         * @param  string       $operator The operator to use:
         *                                =, >, <, >=, <=, BETWEEN, NOT BETWEEN,
         *                                LIKE
         * @param  boolean      $rawBind  Bind the values immediately
         * @param  array|string $value    The value(s) to pass.
         * @return object $this
         */
        public function andOn($field, $operator, $value, $rawBind = false) {
            $this->_backtrace[] = __METHOD__;
            
            $this->on($field, $operator, $value, $rawBind, 'AND');
            
            return $this;
        }
        
        /**
         * Joins an additional table column A to column B
         *
         * @param  string       $field    The field to effect.
         * @param  string       $operator The operator to use:
         *                                =, >, <, >=, <=, BETWEEN, NOT BETWEEN,
         *                                LIKE
         * @param  boolean      $rawBind  Bind the values immediately
         * @param  array|string $value    The value(s) to pass.
         * @return object $this
         */
        public function orOn($field, $operator, $value, $rawBind = false) {
            $this->_backtrace[] = __METHOD__;
            
            $this->on($field, $operator, $value, $rawBind, 'OR');
            
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
            
            if(!$this->_checkStructureExist('where')) {
                $this->_setStructureContents('where', ['command'], 'WHERE');
            }
            
            if(isset($prefix)) {
                $this->_setStructureContents('where', ['command'], $prefix);
            }
            
            $this->_setStructureContents('where', ['column', 'comma'], $field);
            $this->_setStructureContents('where', ['operator'], $operator);
            
            if(in_array($operator, ['BETWEEN', 'NOT BETWEEN']) && is_array($value)) {
                $this->_setStructureContents('where', ['bind'], $value[0]);
                $this->_setStructureContents('where', ['operator'], 'AND');
                $this->_setStructureContents('where', ['bind'], $value[1]);
            } else {
                if($rawBind === true) {
                    $params = 'column';
                } else {
                    $params = 'bind';
                }
                if($operator == 'LIKE') {
                    $value = '%' . $value . '%';
                }
                $this->_setStructureContents('where', [$params], $value);
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
            
            if($this->_checkStructureEmpty('where')) {
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

            if($this->_checkStructureEmpty('where')) {
                throw new QueryException('You can not call the orWhere() method before calling the where() method.');
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
        public function order($fields, $order = 'ASC'){
            $this->_backtrace[] = __METHOD__;

            if(in_array($order, ['ASC', 'DESC'])) {
                $order = $order;
            } else {
                $order = 'ASC';
            }
            
            $this->_setStructureContents('order', ['command'], 'ORDER BY');
            $this->_setStructureContents('order', ['column', 'comma'], $fields);
            $this->_setStructureContents('order', ['operator'], $order);
            
            return $this;
        }

        /**
         * Groups the returned data by a column.
         *
         * @param  string $fields The field(s) to group by.
         * @return object $this
         */
        public function group($fields) {
            $this->_backtrace[] = __METHOD__;

            $this->_setStructureContents('order', ['command'], 'GROUP BY');
            $this->_setStructureContents('order', ['column', 'comma'], $fields);
            
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

            $this->_global['offset'] = true;
            
            if(!is_int($offset)) {
                throw new QueryException('The offset method must be supplied an integer.');
            }
            
            $this->_global['offset'] = $offset;
            
            if(isset($this->_global['limit'])) {
                $this->_resetStructureContents('limit');
                return $this->limit($this->_global['limit']);
            } else {
                return $this;
            }
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
            if(!in_array(__METHOD__, $this->_backtrace)) {
                $this->_backtrace[] = __METHOD__;
            }
            
            if(!is_int($limit)) {
                throw new QueryException('The limit method must be supplied an integer.');
            }
            
            $this->_global['limit'] = $limit;
            
            if(!isset($this->_global['offset'])) {
                $offset = 0;
            } else {
                $offset = $this->_global['offset'];
            }
            
            $this->_setStructureContents('limit', ['command'], 'LIMIT');
            $this->_setStructureContents('limit', ['bind', 'comma', 'noquote'], [$offset, $limit]);
            
            return $this;
        }

    }

}
