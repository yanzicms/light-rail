<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class App extends Container
{
    private static $instance;
    public function __construct()
    {
        $this->set('app', $this);
        self::$instance = $this;
    }
    public static function instance()
    {
        if(is_null(self::$instance)){
            new App();
        }
        return self::$instance;
    }
    public function load($file, $vars = [])
    {
        if(!is_array($file)){
            $file = explode(',', $file);
        }
        foreach($file as $key => $val){
            $val = str_replace(['/', '\\'], DS, trim($val));
            $val = trim($val, DS);
            if(substr_count($val, DS) == 0){
                $val = __DIR__ . DS . ucfirst($val);
            }
            else{
                if(substr($val, 0, strlen(ROOT_PATH)) != ROOT_PATH){
                    $val = ROOT_PATH . $val;
                }
            }
            if(substr($val, -4) != '.php'){
                $val .= '.php';
            }
            $file[$key] = $val;
        }
        if(!empty($vars)){
            extract($vars);
        }
        if(count($file) > 1){
            foreach($file as $key => $val){
                if(is_file($val)){
                    include $val;
                }
            }
        }
        else{
            if(is_file($file[0])){
                return include $file[0];
            }
        }
        return null;
    }
    public function implement($class, $method, $param = [], $namespace = '')
    {
        if(!is_array($param)){
            if(empty($param)){
                $param = [];
            }
            else{
                $param = [$param];
            }
        }
        $class = ucfirst($class);
        if(empty($namespace)){
            return call_user_func_array([$this->$class, $method], $param);
        }
        else{
            return call_user_func_array([$this->get($class, $namespace), $method], $param);
        }
    }
}