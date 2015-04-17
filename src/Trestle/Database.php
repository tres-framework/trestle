<?php
/*                      ______                   __   __
                       /_  __/_____ ___   _____ / /_ / /___
                        / /  / ___// _ \ / ___// __// // _ \
                       / /  / /   /  __/(__  )/ /_ / //  __/
                      /_/  /_/    \___//____/ \__//_/ \___/

                            PHP PDO database wrapper
                  Supporting multiple connections and drivers.
                   https://github.com/tres-framework/Trestle
         ______________________________________________________________
        |_  _  ______  _  ______  _   _____  _  ______  _  ______  _  _|
         / / \ \    / / \ \    / / \ \    / / \ \    / / \ \    / / \ \
        / /   \ \  / /   \ \  / /   \ \  / /   \ \  / /   \ \  / /   \ \
       / /     \ \/ /     \ \/ /     \ \/ /     \ \/ /     \ \/ /     \ \
      / /       |  |       |  |       |  |       |  |       |  |       \ \
     / /       / /\ \     / /\ \     / /\ \     / /\ \     / /\ \       \ \
    / /       / /  \ \   / /  \ \   / /  \ \   / /  \ \   / /  \ \       \ \
 __/ /_______/ /____\ \_/ /____\ \_/ /____\ \_/ /____\ \_/ /____\ \_______\ \__
|______________________________________________________________________________|
*/
namespace Trestle {

    use Exception;
    use PDO;
    use PDOException;
    use ReflectionClass;
    use ReflectionMethod;
    use Trestle\Build;
    use Trestle\Config;
    use Trestle\Stopwatch;
    use Trestle\Process;
    use Trestle\TrestleException;
    use Trestle\DatabaseException;

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
         * Establishes the link to the database.
         *
         * @param  string $connection The connection name from the config.
         */
        public function __construct($connection = null) {
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
            
            $this->_process = new Process();
            $this->_process->connection($this->_config);
        }

        /**
         * __call()
         * 
         * Registers methods called on a database variable and routes the call to
         * the proper blueprint & driver.
         * 
         * @param  string $method The method name.
         * @param  mixed  $args   The arguments.
         * @return object
         */
        public function __call($method, $args) {
            $method = strtolower($method);
            
            $driver = "Trestle\blueprints\\{$this->_config['driver']}";
            
            $reflectionClass = new ReflectionClass($driver);
            
            $aliases = $reflectionClass->getProperty('aliases')->getValue(new $driver($this->_process));
            
            if($method == 'disconnect') {
                $this->_process->disconnect();
            }
            
            if(!$reflectionClass->hasMethod($method) && !in_array($method, array_keys($aliases))) {
                throw new DatabaseException('Trestle was unable to recognize your method or alias call for "' . $method . '()".');
            }
            
            if(in_array($method, array_keys($aliases))) {
                $method = $aliases[$method];
            }
            
            Stopwatch::start('total');
            
            $reflection = new ReflectionMethod($driver, $method);
            
            return $reflection->invokeArgs(new $driver($this->_process), $args);
        }

    }

}
