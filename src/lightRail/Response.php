<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class Response
{
    private $header = [];
    private $code = 200;
    private $contentType = 'text/html';
    private $charset = 'UTF-8';
    private $isSetType = false;
    private $isJson = false;
    public function exhibit($string)
    {
        if(is_bool($string) && !$string){
            exit();
        }
        if($this->isSetType){
            $this->header['Content-Type'] = $this->contentType . '; charset=' . $this->charset;
        }
        if($this->isJson && is_array($string)){
            $this->header['Content-Type'] = 'application/json; charset=' . $this->charset;
            $string = json_encode($string);
        }
        if(!headers_sent() && !empty($this->header)){
            http_response_code($this->code);
            foreach ($this->header as $key => $val) {
                if(is_null($val)){
                    header($key);
                }
                else{
                    header($key . ':' . $val);
                }
            }
        }
        echo $string;
    }
    public function json()
    {
        $this->isJson = true;
    }
    public function header($name, $value = null)
    {
        if(is_array($name)){
            $this->header = array_merge($this->header, $name);
        }
        else{
            $this->header[$name] = $value;
        }
        return $this;
    }
    public function code($code)
    {
        $this->code = $code;
        return $this;
    }
    public function contentType($contentType, $charset = 'utf-8')
    {
        $this->contentType = $contentType;
        $this->charset = $charset;
        $this->isSetType = true;
        return $this;
    }
}