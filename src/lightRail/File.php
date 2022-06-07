<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class File
{
    private $name = 'file';
    private $pass = true;
    private $error = '';
    private $rule = 'date';
    private $fileName = '';
    private $filePath = '';
    private $pathname = '';
    private $hasfile = false;
    public function name($name)
    {
        $this->name = $name;
        if(isset($_FILES[$this->name]) && !empty($_FILES[$this->name]['name'])){
            $this->pathname = $_FILES[$this->name]['tmp_name'];
            $this->hasfile = true;
            return $this;
        }
        else{
            $this->hasfile = false;
            return false;
        }
    }
    public function validate($validate)
    {
        if($this->hasfile){
            if(isset($validate['size'])){
                if($_FILES[$this->name]['size'] > $validate['size']){
                    $this->pass = false;
                    $this->error = Lang::get('File size cannot exceed: ') . $validate['size'];
                }
            }
            if(isset($validate['type'])){
                $type = is_array($validate['type']) ? arrTrimLower($validate['type']) : toArrTrimLower($validate['type'], ',');
                if(!in_array(strtolower($_FILES[$this->name]['type']), $type)){
                    $this->pass = false;
                    $this->error = Lang::get('File types are not allowed, only the following file types can be uploaded: ') . implode(', ', $type);
                }
            }
            if(isset($validate['ext'])){
                if(!$this->checkExt($validate['ext'])){
                    $this->pass = false;
                    $ext = is_array($validate['ext']) ? implode(', ', $validate['ext']) : $validate['ext'];
                    $this->error = Lang::get('File types are not allowed, only the following file types can be uploaded: ') . $ext;
                }
            }
        }
        return $this;
    }
    public function checkExt($ext)
    {
        $ext = is_array($ext) ? arrTrimLower($ext) : toArrTrimLower($ext, ',');
        $filext = strtolower(pathinfo($_FILES[$this->name]['name'], PATHINFO_EXTENSION));
        if(!in_array($filext, $ext)){
            return false;
        }
        return true;
    }
    public function getPathname()
    {
        return $this->pathname;
    }
    public function rule($rule)
    {
        $this->rule = $rule;
        return $this;
    }
    /**
     * 移动文件
     * @access public
     * @param  string      $path     保存路径
     * @param  string|bool $savename 保存的文件名 默认自动生成
     * @param  boolean     $replace  同名文件是否覆盖
     * @return false|File
     */
    public function move($path, $savename = true, $replace = true, $prefix = '')
    {
        if(!$this->hasfile || !$this->pass){
            return false;
        }
        if(is_string($savename) && $savename === ''){
            $fileName = $prefix . $_FILES[$this->name]['name'];
        }
        elseif(is_string($savename)){
            $fileName = $prefix . trim($savename);
        }
        elseif($savename){
            if($this->rule == 'date'){
                $fileName = date('Ymd') . DS . $prefix . md5(microtime(true));
            }
            elseif(in_array($this->rule, hash_algos())){
                $hash = hash_file($this->rule, $_FILES[$this->name]['tmp_name']);
                $fileName = substr($hash, 0, 2) . DS . $prefix . substr($hash, 2);
            }
            else{
                $fileName = date('Ymd') . DS . $prefix . md5(microtime(true));
            }
            $fileName .= '.' . pathinfo($_FILES[$this->name]['name'], PATHINFO_EXTENSION);
        }
        else{
            $fileName = $prefix . $_FILES[$this->name]['name'];
        }
        $this->fileName = str_replace(DS, '/', $fileName);
        $path = str_replace(['/', '\\'], DS, $path);
        $path = rtrim($path, DS) . DS;
        $filePath = $path . $fileName;
        $this->filePath = $filePath;
        $rpath = dirname($filePath);
        if(!is_dir($rpath)){
            @mkdir($rpath, 0777, true);
        }
        if(!$replace && is_file($filePath)){
            $this->error = Lang::get('The file already exists.');
            return false;
        }
        if(move_uploaded_file($_FILES[$this->name]['tmp_name'], $filePath) == false){
            $this->error = Lang::get('File upload failed.');
            return false;
        }
        else{
            $this->pathname = $filePath;
        }
        return $this;
    }
    public function getSaveName()
    {
        return $this->fileName;
    }
    public function getInfo($name = null)
    {
        $info = $_FILES[$this->name];
        unset($info['tmp_name']);
        unset($info['error']);
        if(is_null($name)){
            return $info;
        }
        else{
            return isset($info[$name]) ? $info[$name] : '';
        }
    }
    public function md5()
    {
        return md5_file($this->filePath);
    }
    public function sha1()
    {
        return sha1_file($this->filePath);
    }
    public function hash($hash)
    {
        return hash_file($hash, $this->filePath);
    }
    public function getError()
    {
        return $this->error;
    }
}