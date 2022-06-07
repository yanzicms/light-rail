<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class Jump
{
    public function error($msg = '', $url = null, $data = '', $wait = 3, $header = [])
    {
        ob_clean();
        if(is_null($url)){
            $url = 'javascript:history.go(-1);';
        }
        elseif($url !== '') {
            if(!preg_match('/^(https?:|\/)/', $url) && substr_count($url, '/') == 2){
                $url = Url::build($url);
            }
        }
        $re = View::fetch(__DIR__ . DS . 'template' . DS . 'error.html', ['_lightrail_msg' => $msg, '_lightrail_url' => $url, '_lightrail_wait' => $wait, '_lightrail_data' => $data]);
        $response = App::instance()->response;
        if(!empty($header)){
            $response->header($header);
        }
        $response->exhibit($re);
    }
    public function success($msg = '', $url = null, $data = '', $wait = 3, $header = [])
    {
        ob_clean();
        if(is_null($url) && isset($_SERVER['HTTP_REFERER'])){
            $url = $_SERVER['HTTP_REFERER'];
        }
        elseif($url !== '') {
            if(!preg_match('/^(https?:|\/)/', $url) && substr_count($url, '/') == 2){
                $url = Url::build($url);
            }
        }
        if($wait <= 0){
            Url::redirect($url, $data);
        }
        else{
            $re = View::fetch(__DIR__ . DS . 'template' . DS . 'success.html', ['_lightrail_msg' => $msg, '_lightrail_url' => $url, '_lightrail_wait' => $wait, '_lightrail_data' => $data]);
            $response = App::instance()->response;
            if(!empty($header)){
                $response->header($header);
            }
            $response->exhibit($re);
        }
    }
    public function exception($msg = '', $header = [])
    {
        ob_clean();
        $re = View::fetch(__DIR__ . DS . 'template' . DS . 'exception.html', ['_lightrail_msg' => $msg]);
        $response = App::instance()->response;
        if(!empty($header)){
            $response->header($header);
        }
        $response->exhibit($re);
    }
}