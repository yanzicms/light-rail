<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class Url
{
    private static $url = null;
    public static function build($url = '', $vars = '', $suffix = true, $domain = false)
    {
        $url = trim($url);
        if(!empty($vars)){
            if(!is_array($vars)){
                $varr = explode('&', $vars);
                $vars = [];
                foreach($varr as $key => $val){
                    $kv = explode('=', $val);
                    $vars[$kv[0]] = $kv[1];
                }
            }
        }
        else{
            $vars = [];
        }
        $route = Route::get();
        $path = [];
        $param = [];
        $metch = false;
        foreach($route as $key => $val){
            $val = trim($val);
            if($url != $val){
                continue;
            }
            $kpath = [];
            $kparam = [];
            $karr = explode('/', $key);
            foreach($karr as $kakey => $kaval){
                $kaval = trim($kaval);
                if(substr($kaval, 0, 1) == ':'){
                    $kaval = trim(substr($kaval, 1));
                    $kparam[] = $kaval;
                }
                else{
                    $kpath[] = $kaval;
                }
            }
            $varskey = array_keys($vars);
            if(equalArray($varskey, $kparam)){
                $path = $kpath;
                $param = $vars;
                $metch = true;
                break;
            }
        }
        if($metch){
            $reurl = implode('/', $path);
            foreach($param as $key => $val){
                $reurl .= '/' . $val;
            }
        }
        else{
            $rurl = rtrim($url, '/');
            if(substr($url, 0, 1) != '/' && substr_count($rurl, '/') != 2){
                if($suffix && substr($url, -1) != '/'){
                    $url .= '.' . Config::get('suffix');
                }
                return $url;
            }
            $reurl = ltrim($url, '/');
            foreach($vars as $key => $val){
                $reurl .= '/' . $key . '/' . $val;
            }
        }
        if(strpos(self::uri(), '/index.php/') === false){
            $reurl = self::base(false) . $reurl;
        }
        else{
            $reurl = self::base() . $reurl;
        }
        if($suffix && substr($reurl, -1) != '/'){
            $reurl .= '.' . Config::get('suffix');
        }
        if($domain){
            $reurl = self::domain() .$reurl;
        }
        return $reurl;
    }
    public static function domain()
    {
        return (self::isSsl() ? 'https://' : 'http://') . self::host();
    }
    public static function host()
    {
        if(isset($_SERVER['HTTP_X_FORWARDED_HOST'])){
            return $_SERVER['HTTP_X_FORWARDED_HOST'];
        }
        elseif(isset($_SERVER['HTTP_HOST'])){
            return $_SERVER['HTTP_HOST'];
        }
        else{
            return $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] == '80' ? '' : ':' . $_SERVER['SERVER_PORT']);
        }
    }
    private static function base($consider = true)
    {
        $siteroot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
        $webroot = rtrim(str_replace('\\', '/', ROOT_PATH), '/');
        $subroot = trim(substr($webroot, strlen($siteroot)), '/');
        if($consider && (Config::get('rewrite') == false || strpos(self::uri(), '/index.php/') !== false)){
            $subroot .= '/index.php';
        }
        if(empty($subroot)){
            $subroot = '/';
        }
        elseif($subroot == '/index.php'){
            $subroot .= '/';
        }
        else{
            $subroot = '/' . $subroot . '/';
        }
        return $subroot;
    }
    public static function uri()
    {
        if(isset($_SERVER['REQUEST_URI'])){
            return $_SERVER['REQUEST_URI'];
        }
        elseif(isset($_SERVER['HTTP_X_REWRITE_URL'])){
            return $_SERVER['HTTP_X_REWRITE_URL'];
        }
        elseif(isset($_SERVER['REDIRECT_URL'])){
            return $_SERVER['REDIRECT_URL'];
        }
        elseif(isset($_SERVER['ORIG_PATH_INFO'])){
            $requestUri = $_SERVER['ORIG_PATH_INFO'];
            if(!empty($_SERVER['QUERY_STRING'])){
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
            return $requestUri;
        }
        return false;
    }
    public static function isSsl()
    {
        if(isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 1 || strtolower($_SERVER['HTTPS']) == 'on')){
            return true;
        }
        elseif(isset($_SERVER['HTTP_X_CLIENT_SCHEME']) && strtolower($_SERVER['HTTP_X_CLIENT_SCHEME']) == 'https'){
            return true;
        }
        elseif(isset($_SERVER['REQUEST_SCHEME']) && strtolower($_SERVER['REQUEST_SCHEME']) == 'https'){
            return true;
        }
        elseif(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443){
            return true;
        }
        elseif(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https'){
            return true;
        }
        elseif(isset($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off'){
            return true;
        }
        return false;
    }
    public static function redirect($url = '', $vars = '', $suffix = true, $domain = false, $code = null)
    {
        if(substr($url, 0, 1) == '/' || substr_count($url, '/') != 2 || strpos($url, '.') !== false){
            $to = $url;
        }
        else{
            $to = self::build($url, $vars, $suffix, $domain);
        }
        if(!is_null($code) && is_numeric($code)){
            header("Location: $to", true, $code);
        }
        else{
            header("Location: $to");
        }
        exit();
    }
    public static function url($url = null)
    {
        if(!is_null($url) && true !== $url){
            self::$url = $url;
            return true;
        }
        elseif(is_null(self::$url)){
            if(IS_CLI){
                self::$url = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
            }
            elseif(isset($_SERVER['HTTP_X_REWRITE_URL'])){
                self::$url = $_SERVER['HTTP_X_REWRITE_URL'];
            }
            elseif(isset($_SERVER['REQUEST_URI'])){
                self::$url = $_SERVER['REQUEST_URI'];
            }
            elseif(isset($_SERVER['ORIG_PATH_INFO'])){
                self::$url = $_SERVER['ORIG_PATH_INFO'] . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
            }
            else {
                self::$url = '';
            }
        }
        return true === $url ? self::domain() . self::$url : self::$url;
    }
}