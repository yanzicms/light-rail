<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
use lightRail\Debug;
use lightRail\Lang;
use lightRail\Url;
use lightRail\Request;
use lightRail\App;
use lightRail\Db;

function dump($string){
    Debug::dump($string);
}
function isempty($obj)
{
    return empty($obj) || (is_array($obj) && count($obj) == 1 && empty($obj[0]));
}
function equalArray($arr1, $arr2)
{
    if(count($arr1) != count($arr2)){
        return false;
    }
    if(count($arr1) == 0 && count($arr2) == 0){
        return true;
    }
    $equal = true;
    foreach($arr1 as $key => $val){
        if($val != $arr2[$key]){
            $equal = false;
            break;
        }
    }
    return $equal;
}
function lang($string)
{
    return Lang::get($string);
}
function url($url = '', $vars = '')
{
    return Url::build($url, $vars);
}
function json($data)
{
    App::instance()->response->json();
    return $data;
}
function captcha_src()
{
    return Url::build('/captcha');
}
function captcha_check($captcha)
{
    return App::instance()->captcha->check($captcha);
}
function subtext($text, $length)
{
    if(mb_strlen($text, 'utf8') > $length)
        return mb_substr($text, 0, $length, 'utf8').'...';
    return $text;
}
function array_md5($array)
{
    foreach($array as $key => $val){
        if(is_array($val)){
            $array[$key] = implode(',', $val);
        }
    }
    return implode(',', $array);
}
function concatarrays($arr1, $arr2)
{
    foreach($arr2 as $val){
        $arr1[] = $val;
    }
    return $arr1;
}
function toArrTrim($string, $delimiter = ',')
{
    $reArr = explode($delimiter, $string);
    return array_map(function($v){
        return trim($v);
    },$reArr);
}
function toArrTrimLower($string, $delimiter = ',')
{
    $reArr = explode($delimiter, $string);
    return array_map(function($v){
        return strtolower(trim($v));
    },$reArr);
}
function arrTrimLower($array)
{
    return array_map(function($v){
        return strtolower(trim($v));
    },$array);
}
function request()
{
    return Request::instance();
}
function db($name)
{
    return Db::table($name);
}