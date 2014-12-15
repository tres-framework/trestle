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
    
    /**
     * Information about this package.
     */
    class PackageInfo {
        
        /**
         * The package information.
         * 
         * @var array
         */
        protected static $_info = [
            'version' => '0.1',
            
            'contributors' => [
                
                'FireDart' => [
                    'role' => 'creator, maintainer',
                    'profile' => 'https://github.com/FireDart/'
                ],
                
                'pedzed' => [
                    'profile' => 'https://github.com/pedzed/'
                ],
                
            ]
        ];
        
        /**
         * Gets the info.
         * 
         * @return array
         */
        public static function get(){
            return self::$_info;
        }
        
    }
    
}
