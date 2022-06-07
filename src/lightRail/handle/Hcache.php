<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail\handle;

use lightRail\Cache;

class Hcache
{
    private $tag = null;
    public function tag($tag)
    {
        $this->tag = $tag;
        return $this;
    }
    public function set($key, $value, $ttl = 300)
    {
        Cache::set($key, $value, $ttl, $this->tag);
    }
}