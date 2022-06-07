<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class Hook
{
    private static $tags = [];
    public static function add($tag, $behavior, $first = false)
    {
        if(!isset(self::$tags[$tag])){
            self::$tags[$tag] = [];
        }
        if(is_array($behavior)){
            if($first){
                $behavior = array_reverse($behavior);
                foreach($behavior as $key => $val){
                    array_unshift(self::$tags[$tag], $val);
                }
            }
            else{
                foreach($behavior as $key => $val){
                    self::$tags[$tag][] = $val;
                }
            }
        }
        else{
            if($first){
                array_unshift(self::$tags[$tag], $behavior);
            }
            else{
                self::$tags[$tag][] = $behavior;
            }
        }
    }
    public static function listen($tag, &$params = null, $extra = null, $once = false)
    {
        $result = [];
        if(isset(self::$tags[$tag])){
            $tags = self::$tags[$tag];
        }
        else{
            $tags = [];
        }
        foreach($tags as $key => $val){
            $result[] = self::exec($val, $tag, $params, $extra);
            if($once){
                break;
            }
        }
        if($once){
            return end($result);
        }
        else{
            return $result;
        }
    }
    public static function exec($class, $tag = '', &$params = null, $extra = null)
    {
        $class = str_replace('/', '\\', $class);
        $classarr = explode('\\', $class);
        $class = array_pop($classarr);
        $namespace = implode('\\', $classarr);
        $object = App::instance()->get($class, $namespace);
        if(method_exists($object, $tag)){
            return $object->$tag($params, $extra);
        }
        return null;
    }
}