<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail\cache;

use FilesystemIterator;

class File
{
    public function get($key, $default = false)
    {
        $key = trim($key);
        $key = md5($key);
        $cachePath = $this->getPath($key);
        $cacheFile = $cachePath . DIRECTORY_SEPARATOR . $key . '.php';
        if(!is_file($cacheFile)){
            return $default;
        }
        $cacheArr = file($cacheFile);
        if(trim($cacheArr[2]) < time()){
            @unlink($cacheFile);
            return $default;
        }
        $cacheArr = array_slice($cacheArr, 3);
        $cache = implode('', $cacheArr);
        return unserialize(trim($cache));
    }
    public function set($key, $value, $ttl = 300, $tag = null)
    {
        $keytmp = trim($key);
        $key = md5($keytmp);
        $cachePath = $this->getPath($key);
        $cacheFile = $cachePath . DIRECTORY_SEPARATOR . $key . '.php';
        $cacheTime = time() + intval($ttl);
        $data = '<?php' . PHP_EOL;
        $data .= 'exit();' . PHP_EOL;
        $data .= $cacheTime . PHP_EOL;
        $data .= serialize($value);
        file_put_contents($cacheFile, $data);
        if(!empty($tag)){
            $this->tag($tag, $keytmp);
        }
    }
    private function tag($tag, $key)
    {
        $hastag = $this->get($tag);
        if(is_array($hastag)){
            if(!in_array($key, $hastag)){
                $hastag[] = $key;
            }
        }
        else{
            $hastag = [$key];
        }
        $this->set($tag, $hastag);
    }
    private function deleteTag($tag)
    {
        $dtag = $this->get($tag);
        if(is_array($dtag)){
            foreach($dtag as $val){
                $this->rm($val);
            }
        }
        $this->rm($tag);
    }
    public function rm($key)
    {
        $key = trim($key);
        $key = md5($key);
        $cachePath = $this->getPath($key);
        $cacheFile = $cachePath . DIRECTORY_SEPARATOR . $key . '.php';
        if(is_file($cacheFile)){
            @unlink($cacheFile);
        }
    }
    public function clear($tag = null)
    {
        if(is_null($tag)){
            $this->clearDir(ROOT_PATH . 'runtime' . DS . 'cache');
        }
        else{
            $this->deleteTag($tag);
        }
    }
    public function has($key)
    {
        $key = trim($key);
        $key = md5($key);
        $cachePath = $this->getPath($key);
        $cacheFile = $cachePath . DIRECTORY_SEPARATOR . $key . '.php';
        if(!is_file($cacheFile)){
            return false;
        }
        $cacheArr = file($cacheFile);
        if(intval(trim($cacheArr[2])) < time()){
            @unlink($cacheFile);
            return false;
        }
        return true;
    }
    private function getPath($key)
    {
        $cachePath = ROOT_PATH . 'runtime' . DS . 'cache' . DS . substr($key, 0, 2);
        if(!is_dir($cachePath)){
            @mkdir($cachePath, 0777, true);
        }
        return $cachePath;
    }
    private function clearDir($dirname)
    {
        if(!is_dir($dirname)){
            return false;
        }
        $items = new FilesystemIterator($dirname);
        foreach($items as $item){
            if($item->isDir() && !$item->isLink()){
                $this->clearDir($item->getPathname());
                @rmdir($item->getPathname());
            }
            else{
                @unlink($item->getPathname());
            }
        }
        return true;
    }
}