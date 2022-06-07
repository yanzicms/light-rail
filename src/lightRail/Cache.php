<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

use lightRail\handle\Hcache;

class Cache
{
    public static function get($key, $default = false)
    {
        return app::instance()->implement(ucfirst(trim(Config::get('cache'))), 'get', [$key, $default], 'lightRail\cache');
    }
    public static function set($key, $value, $ttl = 300, $tag = null)
    {
        app::instance()->implement(ucfirst(trim(Config::get('cache'))), 'set', [$key, $value, $ttl, $tag], 'lightRail\cache');
    }
    /**
     * @return Hcache
     */
    public static function tag($tag)
    {
        return app::instance()->implement('Hcache', 'tag', [$tag], 'lightRail\handle');
    }
    public static function rm($key)
    {
        app::instance()->implement(ucfirst(trim(Config::get('cache'))), 'rm', [$key], 'lightRail\cache');
    }
    public static function clear($tag = null)
    {
        app::instance()->implement(ucfirst(trim(Config::get('cache'))), 'clear', [$tag], 'lightRail\cache');
    }
    public static function has($key)
    {
        return app::instance()->implement(ucfirst(trim(Config::get('cache'))), 'has', [$key], 'lightRail\cache');
    }
}