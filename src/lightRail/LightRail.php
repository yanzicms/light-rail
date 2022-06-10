<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class LightRail
{
    public function run()
    {
        define('IS_CLI', PHP_SAPI == 'cli' ? true : false);
        define('VERSION', '1.3.0');
        Debug::remark('begin', 'both');
        Config::load();
        date_default_timezone_set(Config::get('timezone'));
        error_reporting(Config::get('debug') ? E_ALL : 0);
        ob_start();
        Route::load();
        Lang::load();
        Db::load();
        $app = App::instance();
        $app->load('helper,config/common');
        $app->get('errors');
        $app->response->exhibit($app->invoke($app->request->handle()));
    }
}