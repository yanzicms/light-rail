<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

use lightRail\exception\ArgumentException;
use lightRail\exception\ClassNotFoundException;
use lightRail\exception\FuncNotFoundException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class Container
{
    protected $instances = [];
    public function get($name, $namespace = '', $param = [])
    {
        $name = $this->toClassName($name, $namespace);
        if(!$this->hasInstance($name)){
            return $this->make($name, $param, $namespace);
        }
        else{
            return $this->instances[$name];
        }
    }
    public function set($name, $instance, $namespace = '')
    {
        $name = $this->toClassName($name, $namespace);
        if(!$this->hasInstance($name)){
            if(is_object($instance)){
                $this->instances[$name] = $instance;
            }
            else{
                $this->instances[$name] = $this->invokeClass($name);
            }
        }
    }
    public function has($name, $namespace = '')
    {
        $name = $this->toClassName($name, $namespace);
        return isset($this->instances[$name]);
    }
    public function hasInstance($name)
    {
        return isset($this->instances[$name]);
    }
    public function make($name, $param = [], $namespace = '')
    {
        $name = $this->toClassName($name, $namespace);
        if(isset($this->instances[$name])) {
            return $this->instances[$name];
        }
        $object = $this->invokeClass($name, $param);
        $this->instances[$name] = $object;
        return $object;
    }
    private function toClassName($name, $namespace = '')
    {
        $name = str_replace('/', '\\', trim($name));
        if(substr_count($name, '\\') == 0){
            if(empty($namespace)){
                $namespace = 'lightRail';
            }
            else{
                $namespace = str_replace('/', '\\', trim($namespace));
                $namespace = trim($namespace, '\\');
            }
            $name = $namespace . '\\' . ucfirst($name);
        }
        else{
            $names = explode('\\', $name);
            $last = count($names) - 1;
            $names[$last] = ucfirst($names[$last]);
            $name = implode('\\', $names);
        }
        return $name;
    }
    public function invokeClass($class, $param = [])
    {
        try {
            $reflect = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new ClassNotFoundException(Lang::get('Class not exists') . ': ' . $class, $e);
        }
        $constructor = $reflect->getConstructor();
        $args = $constructor ? $this->bindParams($constructor, $param) : [];
        $object = $reflect->newInstanceArgs($args);
        return $object;
    }
    public function invoke($array)
    {
        return $this->invokeMethod($array['class'], $array['method'], $array['parameter'], $array['namespace']);
    }
    public function invokeMethod($class, $method = '', $param = [], $namespace = '', $accessible = false)
    {
        if(is_string($class) && strpos($class, '::') !== false){
            $array = explode('::', $class);
            $class = $this->toClassName($array[0], $namespace);
            $method = $array[1];
        }
        elseif(!is_object($class)){
            $class = $this->toClassName($class, $namespace);
            $class = $this->invokeClass($class);
        }
        try{
            $reflect = new ReflectionMethod($class, $method);
        }
        catch(ReflectionException $e) {
            $class = is_object($class) ? get_class($class) : $class;
            throw new FuncNotFoundException(Lang::get('Method not exists') . ': ' . $class . '::' . $method . '()', $e);
        }
        $args = $this->bindParams($reflect, $param);
        if($accessible){
            $reflect->setAccessible($accessible);
        }
        return $reflect->invokeArgs(is_object($class) ? $class : null, $args);
    }
    protected function bindParams($pobj, $param = [])
    {
        if($pobj->getNumberOfParameters() == 0){
            return [];
        }
        reset($param);
        $type = (key($param) === 0) ? 1 : 0;
        $params = $pobj->getParameters();
        $args = [];
        foreach ($params as $item) {
            $name = $item->getName();
            if($this->isClassParam($item)){
                $args[] = $this->getObjectParam($this->getClassName($item), $param);
            }
            elseif($type == 1 && !empty($param)){
                $args[] = array_shift($param);
            }
            elseif($type == 0 && array_key_exists($name, $param)) {
                $args[] = $param[$name];
            }
            elseif($item->isDefaultValueAvailable()) {
                $args[] = $item->getDefaultValue();
            }
            else {
                throw new ArgumentException(Lang::get('Parameter mismatch') . ':' . $name);
            }
        }
        return $args;
    }
    private function isClassParam($param)
    {
        if(method_exists($param, 'getType')){
            return $param->getType() && !$param->getType()->isBuiltin();
        }
        else{
            return !is_null($param->getClass());
        }
    }
    private function getClassName($param)
    {
        if(method_exists($param, 'getType')){
            return $param->getType()->getName();
        }
        else{
            return $param->getClass()->getName();
        }
    }
    private function getObjectParam($className, &$param)
    {
        if(count($param) > 0){
            $array = $param;
            $value = array_shift($array);
            if($value instanceof $className){
                $result = $value;
                array_shift($param);
            }
            else{
                $result = $this->make($className);
            }
        }
        else{
            $result = $this->make($className);
        }
        return $result;
    }
    public function __get($name)
    {
        return $this->get($name);
    }
}