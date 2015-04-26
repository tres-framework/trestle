<?php

namespace Trestle {
    
    use Exception;
    
    class ConfigException extends TrestleException {}
    
    /*
    |--------------------------------------------------------------------------
    | Config
    |--------------------------------------------------------------------------
    | 
    | This is the configuration class. This class is used to set and retrieve 
    | configuration information.
    | 
    */
    class Config {

        /**
         * The database configuration; assuming defaults.
         *
         * @var private
         */
        private static $_config = [
            'validation'     => true,
        ];

        /**
         * Sets configs into the $_config array and if validation is true checks 
         * to see if a connection block has the required parameters with 
         * _validate().
         *
         * @param  array $config The complete configuration.
         */
        public static function set($config) {
            if(!is_array($config)) {
                throw new ConfigException('An array of data is expected.');
            }

            if(empty($config['connections'])) {
                throw new ConfigException('At least one connection is required.');
            }

            self::$_config = array_merge(self::$_config, $config);

            if(self::$_config['validation'] === true) {
                foreach($config['connections'] as $name => $connection) {
                    self::_validate($name, $connection);
                }
            }
        }

        /**
         * Returns the entire config array unless a specific config item is 
         * requested.
         * 
         * @param  string       $item Get a specific key from the config.
         * @return array|string 
         */
        public static function get($item = null) {
            if(isset($item)) {
                $keys = explode('/', $item);
                $config = self::$_config;
                
                foreach($keys as $key => $value) {
                    if(isset($config[$value])) {
                        $config = $config[$value];
                    } else {
                        return null;
                    }
                }
                return $config;
            } else {
                return self::$_config;
            }
        }

        /**
         * Validates a configuration block by comparing it to the required 
         * options array in the driver's DSN config file. 
         * 
         * This block does not validate that a connection will work, 
         * just that it has the proper arguments to attempt a connection.
         * 
         * @param  string $name       Name of the connection.
         * @param  array  $connection An array of connection.
         */
        private static function _validate($name, $connection) {
            if(!isset($connection['driver'])) {
                throw new ConfigException('Missing driver for "' . $name . '" connection.');
            }
            
            $dsnInfo = dirname(__FILE__) . '/dsn/' . $connection['driver'] . '.php';
            
            if(!is_readable($dsnInfo)) {
                throw new DatabaseException('Missing DSN information or is not readable for a ' . $connection['driver'] . ' connection.');
            }
            
            $dsnInfo   = require($dsnInfo);
            
            $optionSet = false;
            
            foreach($dsnInfo['required'] as $key => $option) {
                $difference = array_diff($option, array_keys($connection));
                if(empty($difference)) {
                    $optionSet = true;
                }
            }
            
            if($optionSet === false) {
                throw new ConfigException('Missing required argument(s) for "' . $name . '" connection.');
            }
        }

    }

}
