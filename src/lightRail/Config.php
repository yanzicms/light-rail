<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class Config
{
    private static $config = [];
    public static function get($name = null)
    {
        if(is_null($name)){
            return self::$config;
        }
        $nameArr = explode('.', trim($name));
        $config = self::$config;
        foreach($nameArr as $item){
            if(isset($config[$item])){
                $config = $config[$item];
            }
            else{
                return null;
            }
        }
        return $config;
    }
    public static function set($name, $value)
    {
        $name = trim($name);
        if(strpos($name, '.') === false){
            self::$config[$name] = $value;
        }
        else{
            $nameArr = explode('.', $name, 2);
            self::$config[$nameArr[0]][$nameArr[1]] = $value;
        }
    }
    public static function load($file = null)
    {
        if(is_null($file)){
            $file = ROOT_PATH . 'config' . DS . 'config.php';
            self::loadfile($file);
            $file = ROOT_PATH . 'config' . DS . 'customize.php';
            if(is_file($file)){
                self::loadfile($file);
            }
        }
        else{
            if(substr($file, 0, strlen(ROOT_PATH)) != ROOT_PATH){
                $file = ROOT_PATH . ltrim($file, DS);
            }
            self::loadfile($file);
        }
    }
    private static function loadfile($file)
    {
        if(substr($file, -4) != '.php'){
            $file .= '.php';
        }
        if(is_file($file)){
            $config = include $file;
            self::$config = array_merge(self::$config, $config);
        }
    }
}