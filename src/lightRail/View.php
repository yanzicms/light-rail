<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class View
{
    private static $data = [];
    public static function fetch($template = '', $vars = [])
    {
        self::assign('LightRail', ['config' => Config::get()]);
        if(!empty($vars)){
            self::assign($vars);
        }
        return app::instance()->implement('template', 'fetch', $template);
    }
    public static function assign($name, $value = null)
    {
        if(is_array($name)){
            self::$data = array_merge(self::$data, $name);
        }
        else{
            self::$data[$name] = $value;
        }
    }
    public static function has($name)
    {
        return isset(self::$data[$name]);
    }
    public static function get($name = null)
    {
        if(is_null($name)){
            return self::$data;
        }
        else{
            if(self::has($name)){
                return self::$data[$name];
            }
            return null;
        }
    }
    public static function append($name, $value)
    {
        if(isset(self::$data[$name])){
            self::$data[$name] .= $value;
        }
        else{
            self::$data[$name] = $value;
        }
    }
}