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
            'display_errors' => false,
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
         * Sets the config.
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

            // Should we validate the connection?
            // This is recommended.
            if(self::$_config['validation'] === true) {
                // Make sure each connection is valid.
                foreach($config['connections'] as $name => $connection) {
                    self::validate($name, $connection);
                }
            }
        }

        /**
         * Gets the config.
         * 
         * @param  string $item Get a specific key from the config.
         * @return mixed  
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
         * Validates a configuration option.
         *
         * @param  string $name       Name of the connection.
         * @param  array  $connection Array of connection.
         */
        private static function validate($name, $connection) { // TODO: Improve DocBlock.
            $difference = array_diff(self::$_parameters, array_keys($connection));

            if(count($difference) == 2) {
                $difference = implode(' & ', $difference);
            } elseif(count($difference) > 2) {
                $last_diff = array_pop($difference);
                $difference = implode(', ', $difference) . ' & ' . $last_diff;
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
