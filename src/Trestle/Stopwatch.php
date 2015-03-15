<?php

namespace Trestle {
    
    class StopwatchException extends TrestleException {}
    
    /*
    |-------------------------------------------------------------------------
    | Stopwatch
    |-------------------------------------------------------------------------
    | 
    | This class calculates execution time.
    | 
    */
    class Stopwatch {
        
        /**
         * Start times for timing
         * 
         * @var array
         */
        private static $_starttime;
        
        /**
         * Starts logging execution time.
         *
         * @param  string $instance A special marker for creating multiple 
         *                          instances.
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
         * Stops calculating time and returns total time of code execution.
         *
         * @param  string $instance  A special marker for creating multiple 
         *                           instances.
         * @return int    $totaltime The total amount of time from start() to 
         *                           stop().
         */
        public static function stop($instance = null) {
            if(isset($instance) && !isset(self::$_starttime[$instance])) {
                throw new StopwachException("The \"{$instance}\" instance can not use the stop() method before using start() method.");
            } elseif(!isset($instance) && !isset(self::$_starttime['default_total'])) {
                throw new StopwachException("The stop() method can not be used before start() method.");
            }
            
            $mtime   = microtime();
            $mtime   = explode(" ", $mtime);
            $endtime = $mtime[1] + $mtime[0];
            
            if(isset($instance)) {
                $totaltime = ($endtime - self::$_starttime[$instance]);
            } else {
                $totaltime = ($endtime - self::$_starttime['default_total']);
            }
            
            return $totaltime;
        }
        
    }
    
}
