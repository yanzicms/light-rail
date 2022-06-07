<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class Route
{
    private static $route = [];
    public static function load($file = null)
    {
        if(is_null($file)){
            $file = ROOT_PATH . 'config' . DS . 'route.php';
        }
        else{
            if(substr($file, 0, strlen(ROOT_PATH)) != ROOT_PATH){
                $file = ROOT_PATH . ltrim($file, DS);
            }
        }
        if(substr($file, -4) != '.php'){
            $file .= '.php';
        }
        if(is_file($file)){
            $route = include $file;
            self::$route = array_merge(self::$route, $route);
        }
    }
    public static function get()
    {
        return self::$route;
    }
    public static function rule($route, $controller)
    {
        if(is_array($route)){
            foreach($route as $key => $val){
                self::$route[$val] = $controller;
            }
        }
        else{
            self::$route[$route] = $controller;
        }
    }
}