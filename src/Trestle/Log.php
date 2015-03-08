<?php

namespace Trestle {
    
    use DateTime;
    use Exception;
    
    class LogException extends TrestleException {}
    
    /*
    |-------------------------------------------------------------------------
    | Logger
    |-------------------------------------------------------------------------
    | 
    | This class calculates execution time and makes reports. They will be
    | stored as logs.
    | 
    */
    class Log {
        
        /**
         * The active log directory.
         *
         * @var string
         */
        private static $_directory;
        
        /**
         * The base log directory.
         *
         * @var string
         */
        private static $_baseDirectory;
        
        /**
         * The permissions for the log directory.
         * 
         * @var int
         */
        private static $_directoryPermissions = 0777;
        
        /**
         * The extension for the log file.
         * 
         * @var string
         */
        private static $_fileExtension = 'log';
        
        /**
         * The permissions for log files.
         * 
         * @var int
         */
        private static $_filePermissions = 0755;
        
        /**
         * The threshold for the file size.
         * 
         * @var int
         */
        private static $_fileMaxSize = 2097152; // 2MB
        
        /**
         * Registered log types
         */
        private static $_registered = [];
        
        /**
         * Start times for timing
         * 
         * @var array
         */
        private static $_starttime;
        
        /**
         * Sets the log directory.
         * 
         * @param  array Array of config options
         * @return void  
         */
        public static function init($config = array()) {
            // Set dir
            if(isset($config['dir']['path']) && !empty($config['dir']['path'])) {
                $dir = $config['dir']['path'];
            } else {
                $dir = __DIR__ . '/logs';
            }
            
            self::$_directory     = rtrim($dir, '/').'/';
            self::$_baseDirectory = self::$_directory;
            
            // Make the dir
            self::_generateDir();
            
            // Set Directory Permission
            if(isset($config['dir']['permissions']) &&
               !empty($config['dir']['permissions']) &&
               is_int($config['dir']['permissions'])
            ){
                self::$_directoryPermissionsPermissions = $config['dir']['permissions'];
            }
            
            // Set File Extension
            if(isset($config['file']['extension']) && !empty($config['file']['extension'])) {
                self::$_fileExtension = $config['file']['extension'];
            }
            
            // Set File Permission
            if(isset($config['file']['permissions']) &&
               !empty($config['file']['permissions']) &&
               is_int($config['file']['permissions'])
            ){
                self::$_filePermissions = $config['file']['permissions'];
            }
            
            // Set File Max Size
            if(isset($config['file']['size']) &&
               !empty($config['file']['size']) &&
               is_int($config['file']['size'])
            ){
                self::$_fileMaxSize = $config['file']['size'];
            }
        }
        
        /**
         * Starts global execution time of log.
         *
         * @param string $instance A special marker for creating multiple instances.
         * @return void
         */
        public static function start($instance = null) {
            $mtime = microtime();
            $mtime = explode(" ", $mtime);
            $mtime = $mtime[1] + $mtime[0];
            
            if(isset($instance)) {
                self::$_starttime[$instance] = $mtime;
            }
            
            self::$_starttime['default_total'] = $mtime;
        }
        
        /**
         * Ends global execution time of log.
         *
         * @param string $instance  A special marker for creating multiple instances.
         * @return int   $totaltime The total amount of time from start() to end().
         */
        public static function end($instance = null) {
            if(empty(self::$_starttime)) {
                throw new LogException('The Log class must have a start() method initiated.');
            }
            
            $mtime     = microtime();
            $mtime     = explode(" ",$mtime);
            $endtime   = $mtime[1] + $mtime[0];
            
            if(isset($instance)) {
                $totaltime = ($endtime - self::$_starttime[$instance]);
            } else {
                $totaltime = ($endtime - self::$_starttime['default_total']);
            }
            
            return $totaltime;
        }
        
        /**
         * Creates the log.
         *
         * @param  string $log The log message.
         * @return void
         */
        public static function msg($log) {
            $date = new DateTime('now');
            
            if(isset($log) && !empty($log)) {
                if(!is_dir(self::$_directory) && !self::_generateDir()){
                    throw new LogException('Could not find, nor create a log directory.');
                }

                self::_generateSecurity();

                $ip = $_SERVER['REMOTE_ADDR'];
                
                if(array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
                    $ip = array_pop(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
                }
                
                self::_logMessage(
                    self::_generateFilename($date->format('Y-m-d')),
                    "[{$date->format('Y-m-d h:m:s')}] [{$ip}] " . $log
                );
            }
        }
        
        /**
         * Catches special logs that use sub directories.
         *
         * @param string $log The type of log
         * @param array  $msg The message to report
         */
        public static function __callStatic($log, $msg) {
            if(in_array($log, self::$_registered)) {
                self::$_directory = self::$_baseDirectory . strtolower($log) . '/';
                self::msg($msg[0]);
                self::$_directory = self::$_baseDirectory;
            } else {
                $debug = debug_backtrace()[0];
                throw new LogException('"' . $log . '" is not a register log type; used in ' . $debug['file'] . ' on ' . $debug['line']);
            }
        }
        
        /**
         * Registers a name in the allowed type of logs.
         *
         * @param string $register The type of log to register
         */
        public static function register($register) {
            self::$_registered[] = strtolower($register);
        }
        
        /**
         * Generates the directory to store the logs.
         * 
         * @return boolean 
         */
        private static function _generateDir() {
            if(!file_exists(self::$_directory)) {
                $status = mkdir(self::$_directory, self::$_directoryPermissions, true);
                self::_generateSecurity();
            } else {
                $status = true;
            }
            
            return $status;
        }
        
        /**
         * Generates security file (.htaccess) to disable directory listing.
         * Nginx users should do this in their .conf file by adding autoindex off;
         * 
         * @return bool
         */
        private static function _generateSecurity() {
            $file = self::$_directory . '.htaccess';
            
            if(is_writable(self::$_directory)) {
                if(!file_exists($file) && $fileHandle = fopen($file, 'w')){
                    chmod($file, self::$_filePermissions);
                    fwrite($fileHandle, 'Options -Indexes'. PHP_EOL .'Deny from all');
                    fclose($fileHandle);
                }
            } else {
                throw new LogException('Cannot create/write to file. Permission denied.');
            }
        }
        
        /**
         * Generates a log file and makes sub files if the original one gets
         * too big in size.
         *
         * @param  string $date   The current date.
         * @param  int    $number The file sub number.
         * @return string         The filename.
         */
        private static function _generateFilename($date, $number = 0) {
            if($number > 999) {
                $float = 3 + floor($number / 999);
            } else {
                $float = 3;
            }
            
            // Ex. format: YYYY-MM-DD.xxx.log
            $file  = self::$_directory;
            $file .= $date.'.'.str_pad($number, $float, '0', STR_PAD_LEFT);
            $file .= '.'.self::$_fileExtension;
            
            if(is_file($file)) {
                if(is_readable($file) && is_writable($file)) {
                    return (filesize($file) < self::$_fileMaxSize) ? $file : self::_generateFilename($date, ++$number);
                } else {
                    throw new LogException('Unable to read or write to log file.');
                }
            } else {
                if(is_writable(self::$_directory) && $fileHandle = fopen($file, 'w')){
                    chmod($file, self::$_filePermissions);
                    fclose($fileHandle);
                } else {
                    throw new LogException('Cannot create/write to file. Permission denied.');
                }
                
                return self::_generateFilename($date, $number);
            }
        }
        
        /**
         * Appends the log to the log file.
         *
         * @param  string $path The file to log to.
         * @param  string $log  The message to log.
         */
        private static function _logMessage($path, $log) {
            $file = fopen($path, "a");
            fwrite($file, $log . PHP_EOL . PHP_EOL);
            fclose($file);
        }
        
    }
    
}
