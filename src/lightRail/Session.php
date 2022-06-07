<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class Session
{
    private static $isstart = false;
    private static function start()
    {
        if(self::$isstart == false){
            session_start();
            self::$isstart = true;
        }
    }
    public static function set($name, $value)
    {
        self::start();
        $_SESSION[$name] = $value;
    }
    public static function get($name = null)
    {
        self::start();
        if(is_null($name)){
            return $_SESSION;
        }
        if(isset($_SESSION[$name])){
            return $_SESSION[$name];
        }
        return null;
    }
    public static function has($name)
    {
        self::start();
        return isset($_SESSION[$name]) ? true : false;
    }
    public static function delete($name)
    {
        self::start();
        if(isset($_SESSION[$name])){
            unset($_SESSION[$name]);
        }
    }
}