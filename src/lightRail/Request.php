<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class Request
{
    protected static $instance;
    private $realIP;
    private $method = 'get';
    private $currentClass = '';
    private $currentMethod = '';
    private $currentController = '';
    protected $proxyServerIp = [];
    protected $proxyServerIpHeader = ['HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP'];
    protected $filter;
    protected $input;
    /**
     * @var array 请求参数
     */
    protected $param = [];
    protected $get = [];
    protected $post = [];
    protected $request = [];
    protected $route = [];
    protected $put;
    protected $session = [];
    protected $file = [];
    protected $cookie = [];
    protected $server = [];
    protected $header = [];
    /**
     * 是否合并Param
     * @var bool
     */
    protected $mergeParam = false;
    public function __construct()
    {
        $this->judgment();
        if(is_null($this->filter)){
            $this->filter = Config::get('default_filter');
        }
        $this->input = file_get_contents('php://input');
    }
    public static function instance()
    {
        if(is_null(self::$instance)){
            self::$instance = App::instance()->request;
        }
        return self::$instance;
    }
    private function judgment()
    {
        if(isset($_POST['_method'])){
            $_POST['_method'] = strtolower($_POST['_method']);
            if(in_array($_POST['_method'], ['post', 'get', 'put', 'delete'])){
                $this->method = $_POST['_method'];
            }
        }
        elseif(isset($_SERVER['REQUEST_METHOD'])){
            $this->method = strtolower($_SERVER['REQUEST_METHOD']);
        }
    }
    private function uriarr()
    {
        $uristr = trim(parse_url(Url::uri(), PHP_URL_PATH), '/');
        $suffix = Config::get('suffix');
        $position = - (strlen($suffix) + 1);
        if(substr($uristr, $position) == '.' . $suffix){
            $uristr = substr($uristr, 0, $position);
        }
        $uriarr = explode('/', $uristr);
        $siteroot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
        $webroot = rtrim(str_replace('\\', '/', ROOT_PATH), '/');
        $subroot = trim(substr($webroot, strlen($siteroot)), '/');
        if(!empty($subroot)){
            $uriarr = array_slice($uriarr, substr_count($subroot, '/') + 1);
        }
        if(isset($uriarr[0]) && $uriarr[0] == 'index.php'){
            array_shift($uriarr);
        }
        return $uriarr;
    }
    public function handle()
    {
        $routing = [];
        $uriarr = $this->uriarr();
        if(isempty($uriarr)){
            $routing['class'] = 'Index';
            $routing['method'] = 'index';
            $routing['parameter'] = [];
            $routing['namespace'] = 'app\index\controller';
            $this->currentController = 'index';
        }
        elseif(count($uriarr) == 1 && strtolower($uriarr[0]) == 'captcha'){
            call_user_func([app::instance()->get('captcha'), 'generate']);
            exit();
        }
        else{
            $urilen = count($uriarr);
            $route = Route::get();
            $ismatch = false;
            $uristr = '';
            $uparam = [];
            foreach($route as $key => $val){
                $leftArr = explode('/', trim($key));
                if(count($leftArr) != $urilen){
                    continue;
                }
                $match = true;
                $param = [];
                foreach($leftArr as $k => $item){
                    $item = trim($item);
                    $first = substr($item, 0, 1);
                    if($first != ':' && $item != $uriarr[$k]){
                        $match = false;
                        break;
                    }
                    if($first == ':'){
                        $param[trim(substr($item, 1))] = urldecode($uriarr[$k]);
                    }
                }
                if($match){
                    $ismatch = true;
                    $uristr = $val;
                    $uparam = $param;
                    break;
                }
                else{
                    continue;
                }
            }
            if($ismatch){
                $uarr = explode('/', $uristr);
                $routing['class'] = ucfirst($uarr[1]);
                $routing['method'] = $uarr[2];
                $routing['parameter'] = $uparam;
                $routing['namespace'] = 'app\\' . $uarr[0] . '\controller';
                $this->currentController = $uarr[0];
            }
            else{
                $param = [];
                if($urilen == 1){
                    $uriarr[1] = 'Index';
                    $uriarr[2] = 'index';
                }
                elseif($urilen == 2){
                    $uriarr[2] = 'index';
                }
                elseif($urilen > 3){
                    for($i = 3; $i < $urilen; $i += 2){
                        if(isset($uriarr[$i + 1])){
                            $param[$uriarr[$i]] = urldecode($uriarr[$i + 1]);
                        }
                        else{
                            $param[$uriarr[$i]] = '';
                        }
                    }
                }
                $routing['class'] = ucfirst($uriarr[1]);
                $routing['method'] = $uriarr[2];
                $routing['parameter'] = $param;
                $routing['namespace'] = 'app\\' . $uriarr[0] . '\controller';
                $this->currentController = $uriarr[0];
            }
            if(!empty($_GET)){
                foreach($_GET as $key => $val){
                    $routing['parameter'][$key] = $val;
                }
            }
        }
        $this->currentClass = lcfirst($routing['class']);
        $this->currentMethod = $routing['method'];
        $this->route = $routing['parameter'];
        $lang = APP_PATH . $this->currentController . DS . 'lang' . DS . Lang::detect() . '.php';
        Lang::load($lang);
        return $routing;
    }
    public function controller()
    {
        return $this->currentController;
    }
    public function className()
    {
        return $this->currentClass;
    }
    public function methodName()
    {
        return $this->currentMethod;
    }
    public function controllerName()
    {
        return $this->currentController;
    }
    public function isPost()
    {
        if($this->method == 'post'){
            return true;
        }
        return false;
    }
    public function isGet()
    {
        if($this->method == 'get'){
            return true;
        }
        return false;
    }
    public function isPut()
    {
        if($this->method == 'put'){
            return true;
        }
        return false;
    }
    public function isDelete()
    {
        if($this->method == 'delete'){
            return true;
        }
        return false;
    }
    public function isSsl()
    {
        return Url::isSsl();
    }
    public function isJson()
    {
        return false !== stripos($_SERVER['HTTP_ACCEPT'], 'json');
    }
    public function isAjax()
    {
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'){
            return true;
        }
        return false;
    }
    public function isPjax()
    {
        return !empty($_SERVER['HTTP_X_PJAX']) ? true : false;
    }
    private function isValidIP($ip, $type = '')
    {
        $type = strtolower($type);
        switch($type){
            case 'ipv4':
                $flag = FILTER_FLAG_IPV4;
                break;
            case 'ipv6':
                $flag = FILTER_FLAG_IPV6;
                break;
            default:
                $flag = 0;
                break;
        }
        $re = true;
        if(filter_var($ip, FILTER_VALIDATE_IP, $flag) === false){
            $re = false;
        }
        return $re;
    }
    private function ip2bin($ip)
    {
        if($this->isValidIP($ip, 'ipv6')){
            $IPHex = str_split(bin2hex(inet_pton($ip)), 4);
            foreach($IPHex as $key => $value){
                $IPHex[$key] = intval($value, 16);
            }
            $IPBin = vsprintf('%016b%016b%016b%016b%016b%016b%016b%016b', $IPHex);
        }
        else{
            $IPHex = str_split(bin2hex(inet_pton($ip)), 2);
            foreach ($IPHex as $key => $value) {
                $IPHex[$key] = intval($value, 16);
            }
            $IPBin = vsprintf('%08b%08b%08b%08b', $IPHex);
        }
        return $IPBin;
    }
    public function ip()
    {
        if(!empty($this->realIP)){
            return $this->realIP;
        }
        $this->realIP = $_SERVER['REMOTE_ADDR'];
        $proxyIp = $this->proxyServerIp;
        $proxyIpHeader = $this->proxyServerIpHeader;
        if(count($proxyIp) > 0 && count($proxyIpHeader) > 0){
            foreach ($proxyIpHeader as $header) {
                $tempIP = $_SERVER[$header];
                if(empty($tempIP)){
                    continue;
                }
                $tempIP = trim(explode(',', $tempIP)[0]);
                if(!$this->isValidIP($tempIP)){
                    $tempIP = null;
                }
                else{
                    break;
                }
            }
            if(!empty($tempIP)){
                $realIPBin = $this->ip2bin($this->realIP);
                foreach($proxyIp as $ip){
                    $serverIPElements = explode('/', $ip);
                    $serverIP = $serverIPElements[0];
                    $serverIPPrefix = isset($serverIPElements[1]) ? $serverIPElements[1] : 128;
                    $serverIPBin = $this->ip2bin($serverIP);
                    if(strlen($realIPBin) !== strlen($serverIPBin)){
                        continue;
                    }
                    if(strncmp($realIPBin, $serverIPBin, (int) $serverIPPrefix) === 0){
                        $this->realIP = $tempIP;
                        break;
                    }
                }
            }
        }
        if(!$this->isValidIP($this->realIP)){
            $this->realIP = '0.0.0.0';
        }
        return $this->realIP;
    }
    public function isMobile()
    {
        if(!empty($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap")){
            return true;
        }
        elseif(!empty($_SERVER['HTTP_ACCEPT']) && strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML")){
            return true;
        }
        elseif(!empty($_SERVER['HTTP_X_WAP_PROFILE']) || !empty($_SERVER['HTTP_PROFILE'])){
            return true;
        }
        elseif(!empty($_SERVER['HTTP_USER_AGENT']) && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $_SERVER['HTTP_USER_AGENT'])){
            return true;
        }
        return false;
    }
    /**
     * 文件
     * @access public
     * @param  string   $name  名称
     * @return File
     */
    public function file($name)
    {
        return App::instance()->file->name($name);
    }
    /**
     * 获取变量 支持过滤和默认值
     * @param array        $data    数据源
     * @param string|false $name    字段名
     * @param mixed        $default 默认值
     * @param string|array $filter  过滤函数
     * @return mixed
     */
    public function input($data = [], $name = '', $default = null, $filter = '')
    {
        if (false === $name) {
            return $data;
        }
        $name = (string) $name;
        if ('' != $name) {
            if (strpos($name, '/')) {
                list($name, $type) = explode('/', $name);
            } else {
                $type = 's';
            }
            foreach (explode('.', $name) as $val) {
                if (isset($data[$val])) {
                    $data = $data[$val];
                } else {
                    return $default;
                }
            }
            if (is_object($data)) {
                return $data;
            }
        }
        $filter = $this->getFilter($filter, $default);
        if (is_array($data)) {
            array_walk_recursive($data, [$this, 'filterValue'], $filter);
            reset($data);
        } else {
            $this->filterValue($data, $name, $filter);
        }
        if (isset($type) && $data !== $default) {
            $this->typeCast($data, $type);
        }
        return $data;
    }
    private function getFilter($filter, $default)
    {
        if (is_null($filter)) {
            $filter = [];
        } else {
            $filter = $filter ?: $this->filter;
            if (is_string($filter) && false === strpos($filter, '/')) {
                $filter = explode(',', $filter);
            } else {
                $filter = (array) $filter;
            }
        }

        $filter[] = $default;
        return $filter;
    }
    /**
     * 递归过滤给定的值
     * @param mixed $value   键值
     * @param mixed $key     键名
     * @param array $filters 过滤方法+默认值
     * @return mixed
     */
    private function filterValue(&$value, $key, $filters)
    {
        $default = array_pop($filters);
        foreach ($filters as $filter) {
            if (is_callable($filter)) {
                $value = call_user_func($filter, $value);
            } elseif (is_scalar($value)) {
                if (false !== strpos($filter, '/')) {
                    if (!preg_match($filter, $value)) {
                        $value = $default;
                        break;
                    }
                } elseif (!empty($filter)) {
                    $value = filter_var($value, is_int($filter) ? $filter : filter_id($filter));
                    if (false === $value) {
                        $value = $default;
                        break;
                    }
                }
            }
        }
        $this->filterExp($value);
    }
    /**
     * 过滤表单中的表达式
     * @param string $value
     * @return void
     */
    public function filterExp(&$value)
    {
        if (is_string($value) && preg_match('/^(EXP|NEQ|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT LIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOT EXISTS|NOTEXISTS|EXISTS|NOT NULL|NOTNULL|NULL|BETWEEN TIME|NOT BETWEEN TIME|NOTBETWEEN TIME|NOTIN|NOT IN|IN)$/i', $value)) {
            $value .= ' ';
        }
    }
    /**
     * 强制类型转换
     * @param string $data
     * @param string $type
     * @return mixed
     */
    private function typeCast(&$data, $type)
    {
        switch (strtolower($type)) {
            case 'a':
                $data = (array) $data;
                break;
            case 'd':
                $data = (int) $data;
                break;
            case 'f':
                $data = (float) $data;
                break;
            case 'b':
                $data = (boolean) $data;
                break;
            case 's':
            default:
                if (is_scalar($data)) {
                    $data = (string) $data;
                } else {
                    throw new \InvalidArgumentException(Lang::get('Variable type error') . '：' . gettype($data));
                }
        }
    }
    /**
     * 设置获取POST参数
     * @access public
     * @param string       $name    变量名
     * @param mixed        $default 默认值
     * @param string|array $filter  过滤方法
     * @return mixed
     */
    public function post($name = '', $default = null, $filter = '')
    {
        if (empty($this->post)) {
            $content = $this->input;
            if (empty($_POST) && false !== strpos($this->contentType(), 'application/json')) {
                $this->post = (array) json_decode($content, true);
            } else {
                $this->post = $_POST;
            }
        }
        if (is_array($name)) {
            $this->param       = [];
            $this->mergeParam  = false;
            return $this->post = array_merge($this->post, $name);
        }
        return $this->input($this->post, $name, $default, $filter);
    }
    /**
     * 当前请求 HTTP_CONTENT_TYPE
     * @access public
     * @return string
     */
    public function contentType()
    {
        $contentType = $this->server('CONTENT_TYPE');
        if ($contentType) {
            if (strpos($contentType, ';')) {
                list($type) = explode(';', $contentType);
            } else {
                $type = $contentType;
            }
            return trim($type);
        }
        return '';
    }
    /**
     * 获取server参数
     * @access public
     * @param string|array $name    数据名称
     * @param string       $default 默认值
     * @param string|array $filter  过滤方法
     * @return mixed
     */
    public function server($name = '', $default = null, $filter = '')
    {
        if (empty($this->server)) {
            $this->server = $_SERVER;
        }
        if (is_array($name)) {
            return $this->server = array_merge($this->server, $name);
        }
        return $this->input($this->server, false === $name ? false : strtoupper($name), $default, $filter);
    }
    /**
     * 设置获取GET参数
     * @access public
     * @param string|array $name    变量名
     * @param mixed        $default 默认值
     * @param string|array $filter  过滤方法
     * @return mixed
     */
    public function get($name = '', $default = null, $filter = '')
    {
        if (empty($this->get)) {
            $this->get = $_GET;
        }
        if (is_array($name)) {
            $this->param      = [];
            $this->mergeParam = false;
            return $this->get = array_merge($this->get, $name);
        }
        return $this->input($this->get, $name, $default, $filter);
    }
    /**
     * 设置获取PUT参数
     * @access public
     * @param string|array $name    变量名
     * @param mixed        $default 默认值
     * @param string|array $filter  过滤方法
     * @return mixed
     */
    public function put($name = '', $default = null, $filter = '')
    {
        if (is_null($this->put)) {
            $content = $this->input;
            if (false !== strpos($this->contentType(), 'application/json')) {
                $this->put = (array) json_decode($content, true);
            } else {
                parse_str($content, $this->put);
            }
        }
        if (is_array($name)) {
            $this->param      = [];
            $this->mergeParam = false;
            return $this->put = is_null($this->put) ? $name : array_merge($this->put, $name);
        }

        return $this->input($this->put, $name, $default, $filter);
    }
    /**
     * 设置获取DELETE参数
     * @access public
     * @param string|array $name    变量名
     * @param mixed        $default 默认值
     * @param string|array $filter  过滤方法
     * @return mixed
     */
    public function delete($name = '', $default = null, $filter = '')
    {
        return $this->put($name, $default, $filter);
    }
    /**
     * 设置获取PATCH参数
     * @access public
     * @param string|array $name    变量名
     * @param mixed        $default 默认值
     * @param string|array $filter  过滤方法
     * @return mixed
     */
    public function patch($name = '', $default = null, $filter = '')
    {
        return $this->put($name, $default, $filter);
    }
    /**
     * 是否存在某个请求参数
     * @access public
     * @param string $name       变量名
     * @param string $type       变量类型
     * @param bool   $checkEmpty 是否检测空值
     * @return mixed
     */
    public function has($name, $type, $checkEmpty = false)
    {
        if (empty($this->$type)) {
            $param = $this->$type();
        } else {
            $param = $this->$type;
        }
        foreach (explode('.', $name) as $val) {
            if (isset($param[$val])) {
                $param = $param[$val];
            } else {
                return false;
            }
        }
        return ($checkEmpty && '' === $param) ? false : true;
    }
    public function url($url = null)
    {
        return Url::url($url);
    }
    /**
     * 设置获取路由参数
     * @access public
     * @param string|array $name    变量名
     * @param mixed        $default 默认值
     * @param string|array $filter  过滤方法
     * @return mixed
     */
    public function route($name = '', $default = null, $filter = '')
    {
        if (is_array($name)) {
            $this->param        = [];
            $this->mergeParam   = false;
            return $this->route = array_merge($this->route, $name);
        }
        return $this->input($this->route, $name, $default, $filter);
    }
    public function param($name = '', $default = null, $filter = '')
    {
        if(empty($this->mergeParam)){
            switch ($this->method) {
                case 'post':
                    $vars = $this->post(false);
                    break;
                case 'put':
                case 'delete':
                case 'patch':
                    $vars = $this->put(false);
                    break;
                default:
                    $vars = [];
            }
            $this->param = array_merge($this->param, $this->get(false), $vars, $this->route(false));
            $this->mergeParam = true;
        }
        if(true === $name){
            return $this->input($this->param, '', $default, $filter);
        }
        return $this->input($this->param, $name, $default, $filter);
    }
}