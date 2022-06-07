<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class Cookie
{
    protected static $config = [
        'expire' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => false
    ];
    public static function set($name, $value, $option = null)
    {
        if(!is_null($option)){
            $oparr = [];
            if(is_numeric($option)){
                $oparr['expire'] = $option;
            }
            elseif(is_string($option)){
                parse_str($option, $oparr);
            }
            self::$config = array_merge(self::$config, array_change_key_case($oparr));
        }
        setcookie($name, $value, time() + self::$config['expire'], self::$config['path'], self::$config['domain'], self::$config['secure'], self::$config['httponly']);
    }
    public static function get($name = null)
    {
        if(is_null($name)){
            return $_COOKIE;
        }
        if(isset($_COOKIE[$name])){
            return $_COOKIE[$name];
        }
        return null;
    }
    public static function has($name)
    {
        return isset($_COOKIE[$name]) ? true : false;
    }
    public static function delete($name)
    {
        setcookie($name, '', time() - 3600, self::$config['path'], self::$config['domain'], self::$config['secure'], self::$config['httponly']);
    }
}