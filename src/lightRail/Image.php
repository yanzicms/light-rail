<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

use lightRail\handle\Himg;

class Image
{
    
    const THUMB_SCALING   = 1;
    const THUMB_FILLED    = 2;
    const THUMB_CENTER    = 3;
    const THUMB_NORTHWEST = 4;
    const THUMB_SOUTHEAST = 5;
    const THUMB_FIXED     = 6;
    
    const WATER_NORTHWEST = 1;
    const WATER_NORTH     = 2;
    const WATER_NORTHEAST = 3;
    const WATER_WEST      = 4;
    const WATER_CENTER    = 5;
    const WATER_EAST      = 6;
    const WATER_SOUTHWEST = 7;
    const WATER_SOUTH     = 8;
    const WATER_SOUTHEAST = 9;
    
    const FLIP_X = 1;
    const FLIP_Y = 2;
    /**
     * 打开文件
     * @access public
     * @param  string  $img 路径
     * @return Himg
     */
    public static function open($img)
    {
        return App::instance()->implement('Himg', 'open', [$img], 'lightRail\handle');
    }
}