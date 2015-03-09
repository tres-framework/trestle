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
         * Mandatory parameters for each connection.
         *
         * @var private
         */
        private static $_parameters = [
            'driver',
            'database',
            'host',
            'username',
            'password',
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
         * Validates a configuration block by comparing it to the class variable 
         * $_parameters. This block does not validate that a connection will work, 
         * just that it has the proper parameters to attempt a connection.
         * 
         * @param  string $name       Name of the connection.
         * @param  array  $connection An array of connection.
         */
        private static function _validate($name, $connection) {
            $difference = array_diff(self::$_parameters, array_keys($connection));

            if(count($difference) == 2) {
                $difference = implode(' & ', $difference);
            } elseif(count($difference) > 2) {
                $lastDiff = array_pop($difference);
                $difference = implode(', ', $difference) . ' & ' . $lastDiff;
            } else {
                $difference = implode(', ', $difference);
            }

            if(!empty($difference)) {
                throw new ConfigException(
                    'Missing required parameter(s) ' . $difference . ' from the "' . $name . '" connection.'
                );
            }
        }

    }

}
