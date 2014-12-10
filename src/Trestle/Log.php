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
         * The log directory.
         *
         * @var string
         */
        private $_directory;
        
        /**
         * The permissions for the log directory.
         * 
         * @var int
         */
        private $_directoryPermissions = 0777;
        
        /**
         * The extension for the log file.
         * 
         * @var string
         */
        private $_fileExtension = 'log';
        
        /**
         * The permissions for log files.
         * 
         * @var int
         */
        private $_filePermissions = 0755;
        
        /**
         * The threshold for the file size.
         * 
         * @var int
         */
        private $_fileMaxSize = 2097152; // 2MB
        
        /**
         * The new line indicator.
         */
        const CRLF = PHP_EOL;
        
        /**
         * Sets the log directory.
         */
        public function __construct($config = array()) {
            // Set dir
            if(isset($config['dir']['path']) && !empty($config['dir']['path'])) {
                $dir = $config['dir']['path'];
            } else {
                $dir = __DIR__ . '/logs';
            }
            
            $this->_directory = rtrim($dir, '/').'/';
            
            // Make the dir
            $this->_generateDir();
            
            // Set Directory Permission
            if(isset($config['dir']['permissions']) &&
               !empty($config['dir']['permissions']) &&
               is_int($config['dir']['permissions'])
            ){
                $this->_directoryPermissionsPermissions = $config['dir']['permissions'];
            }
            
            // Set File Extension
            if(isset($config['file']['extension']) && !empty($config['file']['extension'])) {
                $this->_fileExtension = $config['file']['extension'];
            }
            
            // Set File Permission
            if(isset($config['file']['permissions']) &&
               !empty($config['file']['permissions']) &&
               is_int($config['file']['permissions'])
            ){
                $this->_filePermissions = $config['file']['permissions'];
            }
            
            // Set File Max Size
            if(isset($config['file']['size']) &&
               !empty($config['file']['size']) &&
               is_int($config['file']['size'])
            ){
                $this->_fileMaxSize = $config['file']['size'];
            }
        }
        
        /**
         * Starts global execution time of log.
         *
         * @param string $instance A special marker for creating multiple instances.
         * @return void
         */
        public function start($instance = null) {
            $mtime = microtime();
            $mtime = explode(" ", $mtime);
            $mtime = $mtime[1] + $mtime[0];
            if(isset($instance)) {
                $this->starttime[$instance] = $mtime;
            }
            $this->starttime['default_total'] = $mtime;
        }
        
        /**
         * Ends global execution time of log.
         *
         * @param string $instance  A special marker for creating multiple instances.
         * @return int   $totaltime The total amount of time from start() to end().
         */
        public function end($instance = null) {
            if(empty($this->starttime)) {
                throw new LogException('The Log class must have a start() method initiated.');
            }
            $mtime     = microtime();
            $mtime     = explode(" ",$mtime);
            $endtime   = $mtime[1] + $mtime[0];
            
            if(isset($instance)) {
                $totaltime = ($endtime - $this->starttime[$instance]);
            } else {
                $totaltime = ($endtime - $this->starttime['default_total']);
            }
            
            return $totaltime;
        }
        
        /**
         * Creates the log.
         *
         * @param  string $log The log message.
         * @return void
         */
        public function log($log) {
            $date = new DateTime('now');
            
            if(isset($log) && !empty($log)) {
                // Make dir if needed
                if(!is_dir($this->_directory) && !$this->_generateDir()){
                    throw new LogException('Could not find, nor create a log directory.');
                }
                // Make security file if needed
                $this->_generateSecurity();
                // Get current user ip
                $ip = $_SERVER['REMOTE_ADDR'];
                // Log the message
                $this->_logMessage(
                    $this->_generateFilename($date->format('Y-m-d')),
                    "[{$date->format('Y-m-d h:m:s')}] [{$ip}] " . $log
                );
            }
        }
        
        /**
         * Generates the directory to store the logs.
         *
         * @param  void    
         * @return boolean 
         */
        private function _generateDir() {
            // Do we need to make the dir?
            if(!file_exists($this->_directory)) {
                $status = mkdir($this->_directory, $this->_directoryPermissions, true);
                $this->_generateSecurity();
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
        private function _generateSecurity() {
            $file = $this->_directory . '.htaccess';
            
            if(is_writable($this->_directory)) {
                if(!file_exists($file) && $fileHandle = fopen($file, 'w')){
                    chmod($file, $this->_filePermissions);
                    fwrite($fileHandle, 'Options -Indexes');
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
        private function _generateFilename($date, $number = 0) {
            // Number safety: in case you are creating more than 999 files
            if($number > 999) {
                $float = 3 + floor($number / 999);
            } else {
                $float = 3;
            }
            
            // Ex. format: 2014-11-23.000.log
            $file  = $this->_directory;
            $file .= $date.'.'.str_pad($number, $float, '0', STR_PAD_LEFT);
            $file .= '.'.$this->_fileExtension;
            
            if(is_file($file)) {
                if(is_readable($file) && is_writable($file)) {
                    return (filesize($file) < $this->_fileMaxSize) ? $file : $this->_generateFilename($date, ++$number);
                } else {
                    throw new LogException('Unable to read or write to log file.');
                }
            } else {
                if(is_writable($this->_directory) && $fileHandle = fopen($file, 'w')){
                    chmod($file, $this->_filePermissions);
                    fclose($fileHandle);
                } else {
                    throw new LogException('Cannot create/write to file. Permission denied.');
                }
                
                return $this->_generateFilename($date, $number);
            }
        }
        
        /**
         * Appends the log to the log file.
         *
         * @param  string $path The file to log to.
         * @param  string $log  The message to log.
         */
        private function _logMessage($path, $log) {
            $file = fopen($path, "a");
            fwrite($file, $log.self::CRLF.self::CRLF);
            fclose($file);
        }
        
    }
    
}
