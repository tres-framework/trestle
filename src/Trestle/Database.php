<?php

namespace Trestle {

    use Exception;
    use PDO;
    use PDOException;
    use ReflectionMethod;
    use Trestle\Build;
    use Trestle\Config;
    use Trestle\TrestleException;
    use Trestle\DatabaseException;
    use Trestle\Log;
    use Trestle\Process;

    /**
     *-------------------------------------------------------------------------
     * Database
     *-------------------------------------------------------------------------
     *
     * This is where you start a new database connection. It's where the
     * method chain begins.
     *
     */
    class Database {

        /**
         * Holds database configuration.
         *
         * @var array
         */
        protected $_config = [];

        /**
         * Holds database connection.
         *
         * @var protected
         */
        protected $_connection = []; // TODO: ??; Improve DocBlock.

        /**
         * Establishes the link to the database.
         *
         * @param  string $connection The connection name from the config.
         */
        public function __construct($connection = null) {
            try {
                $this->_config = Config::get();

                if(empty($this->_config)){
                    throw new DatabaseException('Database configuration not set.');
                }

                if(!isset($connection)) {
                    $connection = $this->_config['default'];
                }

                if(isset($this->_config['connections'][$connection])) {
                    $this->_config = $this->_config['connections'][$connection];
                } else {
                    throw new DatabaseException('Unable to locate "' . $connection . '" config.');
                }

                $this->log = new Log(Config::get('logs'));

                $this->_process = new Process();
                $this->_process->connection($this->_config);
            } catch(Exception $e) {
                throw new TrestleException($e);
            }
        }

        /**
         * __call()
         *
         * @param  string $method The method name.
         * @param  mixed  $args   The arguments.
         * @return object
         */
        public function __call($method, $args) { // TODO: Improve DocBlock.
            try {
                $method = strtolower($method);
                if(in_array($method, ['query', 'create', 'get', 'update', 'delete'])) {

                    // Start new log count
                    $this->log->start('total');

                    // Build blueprint
                    $driver = "Trestle\blueprints\\{$this->_config['driver']}";

                    $reflection = new ReflectionMethod($driver, $method);

                    return $reflection->invokeArgs(new $driver($this->_process, $this->log), $args);

                } else {
                    throw new DatabaseException('Trestle was unable to recognize your database call.');
                }
            } catch(Exception $e) {
                throw new TrestleException($e);
            }
        }

    }

}
