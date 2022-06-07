<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class Controller
{
    public function __construct()
    {
        $this->_initialize();
    }
    public function _initialize()
    {
    }
    public function fetch($template = '', $vars = [])
    {
        return View::fetch($template, $vars);
    }
    public function assign($name, $value = null)
    {
        View::assign($name, $value);
    }
    public function redirect($url = '', $vars = '', $suffix = true, $domain = false)
    {
        Url::redirect($url, $vars, $suffix, $domain);
    }
    public function error($msg = '', $url = null, $data = '', $wait = 3, $header = [])
    {
        App::instance()->jump->error($msg, $url, $data, $wait, $header);
    }
    public function success($msg = '', $url = null, $data = '', $wait = 3, $header = [])
    {
        App::instance()->jump->success($msg, $url, $data, $wait, $header);
    }
}