<?php

class Config {

    protected static $config;

    public static function load($config) {
        self::$config = $config;
    }

    public static function get($key = null) {

        if($key === null) {
            return self::$config;
        }
        else {
            $keyBits = explode('.', $key);
            $lastEntry = self::$config;

            foreach($keyBits as $keyBit) {
                if(array_key_exists($keyBit, $lastEntry)) {
                    $lastEntry = $lastEntry[$keyBit];
                }
                else {
                    return null;
                }
            }

            return $lastEntry;
        }
    }

}