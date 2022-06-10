<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class Debug
{
    private static $time = [];
    private static $memory = [];
    /**
     * 记录时间（微秒）和内存使用情况
     * @access public
     * @param  string $name  标记位置
     * @param  mixed  $target 记录目标(可以取值time，memory，both)
     * @return void
     */
    public static function remark($name, $target = 'time')
    {
        $name = trim($name);
        if(in_array($target, ['time', 'both'])){
            self::$time[$name] = microtime(true);
        }
        if(in_array($target, ['memory', 'both'])){
            self::$memory[$name] = memory_get_usage();
        }
    }
    /**
     * 统计某个区间的时间（微秒）使用情况 返回值以秒为单位
     * @access public
     * @param  string  $start 开始标签
     * @param  string  $end   结束标签
     * @param  integer $dec   小数位
     * @return string
     */
    public static function getRangeTime($start = '', $end = '', $dec = 6)
    {
        if(empty($end) || !isset(self::$time[$end])){
            $end = microtime(true);
        }
        else{
            $end = self::$time[$end];
        }
        if(isset(self::$time[$start])){
            $start = self::$time[$start];
        }
        else{
            $start = self::$time['begin'];
        }
        return number_format($end - $start, $dec);
    }
    /**
     * 统计从开始到统计时的时间（微秒）使用情况 返回值以秒为单位
     * @access public
     * @param  integer $dec 小数位
     * @return string
     */
    public static function getUseTime($dec = 6)
    {
        return number_format((microtime(true) - self::$time['begin']), $dec);
    }
    /**
     * 记录区间的内存使用情况
     * @access public
     * @param  string  $start 开始标签
     * @param  string  $end   结束标签
     * @param  integer $dec   小数位
     * @return string
     */
    public static function getRangeMem($start = '', $end = '', $dec = 2)
    {
        if(empty($end) || !isset(self::$memory[$end])){
            $end = memory_get_usage();
        }
        else{
            $end = self::$memory[$end];
        }
        if(isset(self::$memory[$start])){
            $start = self::$memory[$start];
        }
        else{
            $start = self::$memory['begin'];
        }
        $range = $end - $start;
        $unit = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pos = 0;
        while ($range >= 1024) {
            $range /= 1024;
            $pos++;
        }
        return round($range, $dec) . $unit[$pos];
    }
    /**
     * 统计从开始到统计时的内存使用情况
     * @access public
     * @param  integer $dec 小数位
     * @return string
     */
    public static function getUseMem($dec = 2)
    {
        $range = memory_get_usage() - self::$memory['begin'];
        $unit = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pos = 0;
        while ($range >= 1024) {
            $range /= 1024;
            $pos++;
        }
        return round($range, $dec) . $unit[$pos];
    }
    public static function dump($string)
    {
        header("Content-type:text/html;charset=utf-8");
        echo '<pre>';
        var_dump($string);
        echo '</pre>';
    }
}