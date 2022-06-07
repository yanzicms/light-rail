<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

use lightRail\handle\Hview;

class Db
{
    protected static $instance;
    private static $database = [];
    private static $connect = null;
    private static $slaveConnect = null;
    public static function get($name = null)
    {
        $nameArr = explode('.', trim($name));
        $database = self::$database;
        foreach($nameArr as $item){
            if(isset($database[$item])){
                $database = $database[$item];
            }
            else{
                return null;
            }
        }
        return $database;
    }
    public static function set($name, $value)
    {
        $name = trim($name);
        if(strpos($name, '.') === false){
            self::$database[$name] = $value;
        }
        else{
            $nameArr = explode('.', $name, 2);
            self::$database[$nameArr[0]][$nameArr[1]] = $value;
        }
    }
    public static function load($file = null)
    {
        if(is_null($file)){
            $file = ROOT_PATH . 'config' . DS . 'database.php';
        }
        else{
            if(substr($file, 0, strlen(ROOT_PATH)) != ROOT_PATH){
                $file = ROOT_PATH . ltrim($file, DS);
            }
        }
        if(substr($file, -4) != '.php'){
            $file .= '.php';
        }
        if(is_file($file)){
            $database = include $file;
            self::$database = array_merge(self::$database, $database);
            Config::set('database', self::$database);
        }
    }
    /**
     * @access public
     * @param  string  $name 表名
     * @return Drive
     */
    public static function table($name)
    {
        return App::instance()->drive->table($name);
    }
    /**
     * @access public
     * @param  string  $name 表名
     * @param  string  $field 字段
     * @return Hview
     */
    public static function view($name, $field)
    {
        return App::instance()->implement('Hview', 'view', [$name, $field], 'lightRail\handle');
    }
    /**
     * @access public
     * @param  string  $name 表名
     * @return Drive
     */
    public static function name($name)
    {
        return App::instance()->drive->table($name);
    }
    /**
     * 连接
     * @access public
     * @param  string  $connect 连接配置
     * @return Drive
     */
    public static function connect($connect = [])
    {
        if(empty($connect)){
            $connect = self::connstr();
        }
        else{
            self::$database = array_merge(self::$database, $connect);
        }
        self::$connect = App::instance()->implement(ucfirst(trim(self::get('type'))), 'connect', [$connect], 'lightRail\db');
        return App::instance()->drive->connect();
    }
    public static function slaveConnect($connect = [])
    {
        if(empty($connect)){
            $connect = self::slaveConnstr();
        }
        else{
            self::$database = array_merge(self::$database, $connect);
        }
        self::$slaveConnect = App::instance()->implement(ucfirst(trim(self::get('type'))), 'connect', [$connect], 'lightRail\db');
        return App::instance()->drive->connect();
    }
    public static function isconnect()
    {
        if(is_null(self::$connect)){
            return false;
        }
        return true;
    }
    public static function execute($sql, $data = [])
    {
        if(is_null(self::$connect)){
            self::connect();
        }
        return App::instance()->implement(ucfirst(trim(self::get('type'))), 'execute', [$sql, $data], 'lightRail\db');
    }
    public static function query($sql, $data = [])
    {
        if(Db::get('deploy') == 1){
            if(is_null(self::$slaveConnect)){
                self::slaveConnect();
            }
        }
        else{
            if(is_null(self::$connect)){
                self::connect();
            }
        }
        return App::instance()->implement(ucfirst(trim(self::get('type'))), 'query', [$sql, $data], 'lightRail\db');
    }
    private static function connstr()
    {
        return [
            'type' => Db::get('type'),
            'hostname' => Db::get('hostname'),
            'database' => Db::get('database'),
            'username' => Db::get('username'),
            'password' => Db::get('password'),
            'hostport' => Db::get('hostport'),
            'charset' => Db::get('charset'),
            'prefix' => Db::get('prefix')
        ];
    }
    private static function slaveConnstr()
    {
        if(Db::get('deploy') == 1){
            $hostname =  Db::get('slave.hostname');
            $hostarr = toArrTrim($hostname, ',');
            $hostmax = count($hostarr) - 1;
            $usehost = rand(0, $hostmax);
            $hostname = $hostarr[$usehost];
            $database = Db::get('slave.database');
            $dataarr = toArrTrim($database, ',');
            if(isset($dataarr[$usehost])){
                $database = $dataarr[$usehost];
            }
            else{
                $database = $dataarr[0];
            }
            $username = Db::get('slave.username');
            $userarr = toArrTrim($username, ',');
            if(isset($userarr[$usehost])){
                $username = $userarr[$usehost];
            }
            else{
                $username = $userarr[0];
            }
            $password = Db::get('slave.password');
            $passarr = toArrTrim($password, ',');
            if(isset($passarr[$usehost])){
                $password = $passarr[$usehost];
            }
            else{
                $password = $passarr[0];
            }
            $hostport = Db::get('slave.hostport');
            $portarr = toArrTrim($hostport, ',');
            if(isset($portarr[$usehost])){
                $hostport = $portarr[$usehost];
            }
            else{
                $hostport = $portarr[0];
            }
            return [
                'type' => Db::get('slave.type'),
                'hostname' => $hostname,
                'database' => $database,
                'username' => $username,
                'password' => $password,
                'hostport' => $hostport,
                'charset' => Db::get('charset'),
                'prefix' => Db::get('prefix')
            ];
        }
        else{
            return [
                'type' => Db::get('type'),
                'hostname' => Db::get('hostname'),
                'database' => Db::get('database'),
                'username' => Db::get('username'),
                'password' => Db::get('password'),
                'hostport' => Db::get('hostport'),
                'charset' => Db::get('charset'),
                'prefix' => Db::get('prefix')
            ];
        }
    }
    public static function raw($exp)
    {
        return ['exp', $exp];
    }
    public static function startTrans()
    {
        if(is_null(self::$connect)){
            self::connect();
        }
        return App::instance()->implement(ucfirst(trim(self::get('type'))), 'startTrans', [], 'lightRail\db');
    }
    public static function commit()
    {
        return App::instance()->implement(ucfirst(trim(self::get('type'))), 'commit', [], 'lightRail\db');
    }
    public static function rollback()
    {
        return App::instance()->implement(ucfirst(trim(self::get('type'))), 'rollback', [], 'lightRail\db');
    }
    public static function transaction($statement)
    {
        self::startTrans();
        try{
            $statement();
            self::commit();
        }
        catch(\Exception $e){
            self::rollback();
        }
    }
    public function select($exec = true)
    {
        return [];
    }
    public function buildSql()
    {
        return '';
    }
}