<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class Captcha
{
    private $app;
    private $session;
    private $bg = [243, 251, 254];
    public function __construct(App $app, Session $session)
    {
        $this->app = $app;
        $this->session = $session;
    }
    public function generate()
    {
        $width = @intval(trim(Config::get('imagewidth')));
        $height = @intval(trim(Config::get('imageheight')));
        $image = imagecreatetruecolor($width, $height);
        $bgcolor = imagecolorallocate($image, $this->bg[0], $this->bg[1], $this->bg[2]);
        imagefill($image, 0, 0, $bgcolor);
        $data = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $datalen = strlen($data) - 1;
        $interferencepoints = @intval(trim(Config::get('interferencepoints')));
        for ($i = 0; $i < $interferencepoints; $i++) {
            $fontcolor = imagecolorallocate($image, rand(150, 225), rand(150, 225), rand(150, 225));
            $fontcontent = substr($data, rand(0, $datalen), 1);
            $angle = rand(0, 35);
            $x = rand(0, $width);
            $y = rand(0, $height);
            $fontsize = rand(5, 8);
            imagettftext($image, $fontsize, $angle, $x, $y, $fontcolor, dirname(__DIR__) . DS . 'ttfs' . DS . rand(1, 6) . '.ttf', $fontcontent);
        }
        $captchcode = '';
        $fontsize = @intval(trim(Config::get('fontsize')));
        $quantity = @intval(trim(Config::get('quantity')));
        $per = ceil($width / $quantity);
        $wto = ceil(($per - $fontsize * 2 / 3) / 2);
        $hfo = ceil(($height - $fontsize) / 4 + $fontsize);
        $hto = ceil(($height - $fontsize) * 3 / 4 + $fontsize);
        $fcolor = [rand(0, 120), rand(0, 120), rand(0, 120)];
        $ttf = rand(1, 6);
        $fontcolor = imagecolorallocate($image, $fcolor[0], $fcolor[1], $fcolor[2]);
        $fx = 0;
        for ($i = 0; $i < $quantity; $i++) {
            $fontcontent = substr($data, rand(0, $datalen), 1);
            $captchcode .= $fontcontent;
            $angleabs = $angle = rand(0, 35);
            if (rand(1, 100) % 2 == 0) {
                $x = @intval(($i * $per) + $wto + rand(0, $wto * 2 / 5));
            } else {
                $x = @intval(($i * $per) + $wto - rand(0, $wto * 2 / 5));
                $angle = -$angle;
            }
            if ($x < 0) {
                $x = 0;
            }
            if ($x > $width - $fontsize - $angleabs) {
                $x = $width - $fontsize - $angleabs;
            }
            if($fx > 0 && $x - $fx < $fontsize){
                $x = $fx + $fontsize;
            }
            $fx = $x;
            $y = rand($hfo, $hto);
            imagettftext($image, $fontsize, $angle, $x, $y, $fontcolor, dirname(__DIR__) . DS . 'ttfs' . DS . $ttf . '.ttf', $fontcontent);
        }
        $this->session->set('_lightRail_captcha', $captchcode);
        $px = $py = 0;
        $A = mt_rand(1, $height / 2);
        $b = mt_rand(intval(-$height / 4), intval($height / 4));
        $f = mt_rand(intval(-$height / 4), intval($height / 4));
        $T = mt_rand($height, $width * 2);
        $w = (2 * M_PI) / $T;
        $px1 = 0;
        $px2 = mt_rand($width / 2, $width * 0.8);
        for($px = $px1; $px <= $px2; $px = $px + 1){
            if(0 != $w){
                $py = intval($A * sin($w * $px + $f) + $b + $height / 2);
                $i  = (int)($fontsize / 5);
                while($i > 0){
                    imagesetpixel($image, $px + $i, $py + $i, $fontcolor);
                    $i--;
                }
            }
        }
        $A = mt_rand(1, $height / 2);
        $f = mt_rand(intval(-$height / 4), intval($height / 4));
        $T = mt_rand($height, $width * 2);
        $w = (2 * M_PI) / $T;
        $b = $py - $A * sin($w * $px + $f) - $height / 2;
        $px1 = $px2;
        $px2 = $width;
        for($px = $px1; $px <= $px2; $px = $px + 1){
            if(0 != $w){
                $py = intval($A * sin($w * $px + $f) + $b + $height / 2);
                $i = (int)($fontsize / 5);
                while($i > 0){
                    imagesetpixel($image, $px + $i, $py + $i, $fontcolor);
                    $i--;
                }
            }
        }
        header('content-type:image/png');
        imagepng($image);
        imagedestroy($image);
    }
    public function clear()
    {
        $this->session->delete('_lightRail_captcha');
    }
    public function check($value)
    {
        $result = strtolower(Session::get('_lightRail_captcha')) == strtolower($value);
        if(Config::get('useonce')){
            Session::delete('_lightRail_captcha');
        }
        return $result;
    }
}