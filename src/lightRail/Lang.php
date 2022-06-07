<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class Lang
{
    private static $lang = [];
    private static $langCookieVar = 'light_rail_lang_var';
    private static $allowLangList = [];
    private static $range = '';
    public static function load($file = '', $range = '')
    {
        if(empty($range) && !empty(self::$range)){
            $range = self::$range;
        }
        if(empty($range)){
            $range = trim(Config::get('language'));
        }
        if(empty($file)){
            $file = __DIR__ . DS . 'lang' . DS . $range;
        }
        elseif(substr($file, 0, strlen(ROOT_PATH)) != ROOT_PATH){
            $file = ROOT_PATH . ltrim($file, DS);
        }
        if(substr($file, -4) != '.php'){
            $file .= '.php';
        }
        if(is_file($file)){
            $lang = include $file;
            if(isset(self::$lang[$range])){
                self::$lang[$range] = array_merge(self::$lang[$range], $lang);
            }
            else{
                self::$lang[$range] = $lang;
            }
        }
    }
    public static function get($name = '', $range = '')
    {
        if(empty($range) && !empty(self::$range)){
            $range = self::$range;
        }
        if(empty($range)){
            $range = trim(Config::get('language'));
        }
        if(empty($name)){
            return self::$lang[$range];
        }
        else{
            if(isset(self::$lang[$range][$name])){
                return self::$lang[$range][$name];
            }
            else{
                return $name;
            }
        }
    }
    public static function set($name, $value = null, $range = '')
    {
        if(empty($range) && !empty(self::$range)){
            $range = self::$range;
        }
        if(empty($range)){
            $range = trim(Config::get('language'));
        }
        if(is_array($name)){
            foreach($name as $key => $val){
                self::$lang[$range][$key] = $val;
            }
        }
        else{
            self::$lang[$range][$name] = $value;
        }
    }
    public static function detect()
    {
        $lang = trim(Config::get('language'));
        $langstr = Config::get('langstring');
        if(isset($_GET[$langstr])){
            $lang = strtolower($_GET[$langstr]);
        }
        elseif(isset($_COOKIE[self::$langCookieVar])){
            $lang = strtolower($_COOKIE[self::$langCookieVar]);
        }
        elseif(isset($_COOKIE[$langstr])){
            $lang = strtolower($_COOKIE[$langstr]);
        }
        elseif($_SERVER['HTTP_ACCEPT_LANGUAGE']){
            $browser = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            $barr = explode(';', $browser);
            $barr = explode(',', $barr[0]);
            if(isset($barr[1]) && (strlen($barr[1]) >= strlen($barr[0]) || $barr[1] == 'zh-cn')){
                $barr[0] = $barr[1];
            }
            $lang = $barr[0];
        }
        if(!empty(self::$allowLangList) && !in_array($lang, self::$allowLangList)){
            if(!empty(self::$range)){
                $lang = self::$range;
            }
            else{
                $lang = trim(Config::get('language'));
            }
        }
        return $lang;
    }
    public static function setLangCookieVar($var)
    {
        self::$langCookieVar = $var;
    }
    public static function setAllowLangList($list)
    {
        self::$allowLangList = $list;
    }
    public static function range($range = '')
    {
        if(!empty($range)){
            self::$range = $range;
        }
        return self::$range;
    }
}