<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail\handle;

use lightRail\exception\ImageException;
use lightRail\Image;
use lightRail\Lang;

class Himg
{
    private $im;
    private $width;
    private $height;
    private $type;
    private $gif;
    public function open($img)
    {
        $ext = pathinfo($img, PATHINFO_EXTENSION);
        $ext = strtolower($ext);
        if($ext == 'png'){
            $this->im = imagecreatefrompng($img);
        }
        elseif($ext == 'gif'){
            $this->gif = new Hgif($img);
            $this->im  = @imagecreatefromstring($this->gif->image());
        }
        elseif($ext == 'webp'){
            $this->im = imagecreatefromwebp($img);
        }
        else{
            $this->im = imagecreatefromjpeg($img);
        }
        $this->type = $ext;
        $this->width = imagesx($this->im);
        $this->height = imagesy($this->im);
        return $this;
    }
    public function width()
    {
        return $this->width;
    }
    public function height()
    {
        return $this->height;
    }
    public function type()
    {
        return $this->type;
    }
    public function thumb($width, $height, $type = Image::THUMB_SCALING)
    {
        $w = $this->width;
        $h = $this->height;
        
        switch ($type) {
            
            case Image::THUMB_SCALING:
                if ($w < $width && $h < $height) {
                    return $this;
                }
                $scale = min($width / $w, $height / $h);
                $x = $y = 0;
                $width  = $w * $scale;
                $height = $h * $scale;
                break;
            
            case Image::THUMB_CENTER:
                $scale = max($width / $w, $height / $h);
                $w = $width / $scale;
                $h = $height / $scale;
                $x = ($this->width - $w) / 2;
                $y = ($this->height - $h) / 2;
                break;
            
            case Image::THUMB_NORTHWEST:
                $scale = max($width / $w, $height / $h);
                $x = $y = 0;
                $w = $width / $scale;
                $h = $height / $scale;
                break;
            
            case Image::THUMB_SOUTHEAST:
                $scale = max($width / $w, $height / $h);
                $w = $width / $scale;
                $h = $height / $scale;
                $x = $this->width - $w;
                $y = $this->height - $h;
                break;
            
            case Image::THUMB_FILLED:
                if ($w < $width && $h < $height) {
                    $scale = 1;
                } else {
                    $scale = min($width / $w, $height / $h);
                }
                $neww = $w * $scale;
                $newh = $h * $scale;
                $x    = $this->width - $w;
                $y    = $this->height - $h;
                $posx = ($width - $w * $scale) / 2;
                $posy = ($height - $h * $scale) / 2;
                do {
                    $img = imagecreatetruecolor($width, $height);
                    $color = imagecolorallocate($img, 255, 255, 255);
                    imagefill($img, 0, 0, $color);
                    imagecopyresampled($img, $this->im, $posx, $posy, $x, $y, $neww, $newh, $w, $h);
                    imagedestroy($this->im);
                    $this->im = $img;
                } while (!empty($this->gif) && $this->gifNext());
                $this->width  = (int) $width;
                $this->height = (int) $height;
                return $this;
            
            case Image::THUMB_FIXED:
                $x = $y = 0;
                break;
            default:
                throw new ImageException(Lang::get('Thumbnail crop type not supported'));
        }
        
        return $this->crop($w, $h, $x, $y, $width, $height);
    }
    public function crop($w, $h, $x = 0, $y = 0, $width = null, $height = null)
    {
        if(empty($width)){
            $width = $w;
        }
        if(empty($height)){
            $height = $h;
        }
        do {
            $img = imagecreatetruecolor($width, $height);
            $color = imagecolorallocate($img, 255, 255, 255);
            imagefill($img, 0, 0, $color);
            imagecopyresampled($img, $this->im, 0, 0, $x, $y, $width, $height, $w, $h);
            imagedestroy($this->im);
            $this->im = $img;
        } while (!empty($this->gif) && $this->gifNext());
        $this->width  = (int) $width;
        $this->height = (int) $height;
        return $this;
    }
    /**
     * 旋转图像
     * @param int $degrees 顺时针旋转的度数
     * @return $this
     */
    public function rotate($degrees = 90)
    {
        do {
            $img = imagerotate($this->im, -$degrees, imagecolorallocatealpha($this->im, 0, 0, 0, 127));
            imagedestroy($this->im);
            $this->im = $img;
        } while (!empty($this->gif) && $this->gifNext());
        $this->width  = imagesx($this->im);
        $this->height = imagesy($this->im);
        return $this;
    }

    /**
     * 翻转图像
     * @param integer $direction 翻转轴,X或者Y
     * @return $this
     */
    public function flip($direction = Image::FLIP_X)
    {
        $w = $this->width;
        $h = $this->height;
        do {
            $img = imagecreatetruecolor($w, $h);
            switch ($direction) {
                case Image::FLIP_X:
                    for ($y = 0; $y < $h; $y++) {
                        imagecopy($img, $this->im, 0, $h - $y - 1, 0, $y, $w, 1);
                    }
                    break;
                case Image::FLIP_Y:
                    for ($x = 0; $x < $w; $x++) {
                        imagecopy($img, $this->im, $w - $x - 1, 0, $x, 0, 1, $h);
                    }
                    break;
                default:
                    throw new ImageException(Lang::get('Flip type not supported'));
            }
            imagedestroy($this->im);
            $this->im = $img;
        } while (!empty($this->gif) && $this->gifNext());
        return $this;
    }
    /**
     * 添加水印
     *
     * @param  string $source 水印图片路径
     * @param int     $locate 水印位置
     * @param int     $alpha  透明度
     * @return $this
     */
    public function water($source, $locate = Image::WATER_SOUTHEAST, $alpha = 100)
    {
        if (!is_file($source)) {
            throw new ImageException(Lang::get('Watermark image does not exist'));
        }
        $info = getimagesize($source);
        if (false === $info || (IMAGETYPE_GIF === $info[2] && empty($info['bits']))) {
            throw new ImageException(Lang::get('Illegal watermark file'));
        }
        $fun   = 'imagecreatefrom' . image_type_to_extension($info[2], false);
        $water = $fun($source);
        imagealphablending($water, true);
        
        switch ($locate) {
            
            case Image::WATER_SOUTHEAST:
                $x = $this->width - $info[0];
                $y = $this->height - $info[1];
                break;
            
            case Image::WATER_SOUTHWEST:
                $x = 0;
                $y = $this->height - $info[1];
                break;
            
            case Image::WATER_NORTHWEST:
                $x = $y = 0;
                break;
            
            case Image::WATER_NORTHEAST:
                $x = $this->width - $info[0];
                $y = 0;
                break;
            
            case Image::WATER_CENTER:
                $x = ($this->width - $info[0]) / 2;
                $y = ($this->height - $info[1]) / 2;
                break;
            
            case Image::WATER_SOUTH:
                $x = ($this->width - $info[0]) / 2;
                $y = $this->height - $info[1];
                break;
            
            case Image::WATER_EAST:
                $x = $this->width - $info[0];
                $y = ($this->height - $info[1]) / 2;
                break;
            
            case Image::WATER_NORTH:
                $x = ($this->width - $info[0]) / 2;
                $y = 0;
                break;
            
            case Image::WATER_WEST:
                $x = 0;
                $y = ($this->height - $info[1]) / 2;
                break;
            default:
                
                if (is_array($locate)) {
                    list($x, $y) = $locate;
                } else {
                    throw new ImageException(Lang::get('Unsupported watermark location type'));
                }
        }
        do {
            $src = imagecreatetruecolor($info[0], $info[1]);
            $color = imagecolorallocate($src, 255, 255, 255);
            imagefill($src, 0, 0, $color);
            imagecopy($src, $this->im, 0, 0, $x, $y, $info[0], $info[1]);
            imagecopy($src, $water, 0, 0, 0, 0, $info[0], $info[1]);
            imagecopymerge($this->im, $src, $x, $y, 0, 0, $info[0], $info[1], $alpha);
            imagedestroy($src);
        } while (!empty($this->gif) && $this->gifNext());
        imagedestroy($water);
        return $this;
    }

    /**
     * 图像添加文字
     *
     * @param  string  $text   添加的文字
     * @param  string  $font   字体路径
     * @param  integer $size   字号
     * @param  string  $color  文字颜色
     * @param int      $locate 文字写入位置
     * @param  integer $offset 文字相对当前位置的偏移量
     * @param  integer $angle  文字倾斜角度
     *
     * @return $this
     * @throws ImageException
     */
    public function text($text, $font = '', $size = 20, $color = '#00000000', $locate = Image::WATER_SOUTHEAST, $offset = 0, $angle = 0)
    {
        if(empty($font)){
            $font = dirname(dirname(__DIR__)) . DS . 'ttfs' . DS . 'z.ttf';
        }
        if(!is_file($font)){
            throw new ImageException(Lang::get('Font file that does not exist') . '：' . $font);
        }
        $info = imagettfbbox($size, $angle, $font, $text);
        $minx = min($info[0], $info[2], $info[4], $info[6]);
        $maxx = max($info[0], $info[2], $info[4], $info[6]);
        $miny = min($info[1], $info[3], $info[5], $info[7]);
        $maxy = max($info[1], $info[3], $info[5], $info[7]);
        
        $x = $minx;
        $y = abs($miny);
        $w = $maxx - $minx;
        $h = $maxy - $miny;
        
        switch ($locate) {
            
            case Image::WATER_SOUTHEAST:
                $x += $this->width - $w;
                $y += $this->height - $h;
                break;
            
            case Image::WATER_SOUTHWEST:
                $y += $this->height - $h;
                break;
            
            case Image::WATER_NORTHWEST:
                break;
            
            case Image::WATER_NORTHEAST:
                $x += $this->width - $w;
                break;
            
            case Image::WATER_CENTER:
                $x += ($this->width - $w) / 2;
                $y += ($this->height - $h) / 2;
                break;
            
            case Image::WATER_SOUTH:
                $x += ($this->width - $w) / 2;
                $y += $this->height - $h;
                break;
            
            case Image::WATER_EAST:
                $x += $this->width - $w;
                $y += ($this->height - $h) / 2;
                break;
            
            case Image::WATER_NORTH:
                $x += ($this->width - $w) / 2;
                break;
            
            case Image::WATER_WEST:
                $y += ($this->height - $h) / 2;
                break;
            default:
                
                if (is_array($locate)) {
                    list($posx, $posy) = $locate;
                    $x += $posx;
                    $y += $posy;
                } else {
                    throw new ImageException(Lang::get('Unsupported text position type'));
                }
        }
        
        if (is_array($offset)) {
            $offset = array_map('intval', $offset);
            list($ox, $oy) = $offset;
        } else {
            $offset = intval($offset);
            $ox = $oy = $offset;
        }
        
        if (is_string($color) && 0 === strpos($color, '#')) {
            $color = str_split(substr($color, 1), 2);
            $color = array_map('hexdec', $color);
            if (empty($color[3]) || $color[3] > 127) {
                $color[3] = 0;
            }
        } elseif (!is_array($color)) {
            throw new ImageException(Lang::get('Wrong color value'));
        }
        do {
            
            $col = imagecolorallocatealpha($this->im, $color[0], $color[1], $color[2], $color[3]);
            imagettftext($this->im, $size, $angle, $x + $ox, $y + $oy, $col, $font, $text);
        } while (!empty($this->gif) && $this->gifNext());
        return $this;
    }
    public function save($pathname, $type = null, $quality = 80, $interlace = true)
    {
        if(is_null($type)){
            $type = $this->type;
        }
        else{
            $type = strtolower($type);
        }
        if($type == 'jpeg' || $type == 'jpg'){
            imageinterlace($this->im, $interlace);
            imagejpeg($this->im, $pathname, $quality);
        }
        elseif($type == 'gif' && !empty($this->gif)){
            $this->gif->save($pathname);
        }
        elseif($type == 'png'){
            imagesavealpha($this->im, true);
            imagepng($this->im, $pathname, min((int) ($quality / 10), 9));
        }
        elseif($type == 'webp'){
            imagewebp($this->im, $pathname, $quality);
        }
        else{
            $fun = 'image' . $type;
            $fun($this->im, $pathname);
        }
        imagedestroy($this->im);
        return $this;
    }
    /**
     * 切换到GIF的下一帧并保存当前帧
     */
    protected function gifNext()
    {
        ob_start();
        ob_implicit_flush(0);
        imagegif($this->im);
        $img = ob_get_clean();
        $this->gif->image($img);
        $next = $this->gif->nextImage();
        if ($next) {
            imagedestroy($this->im);
            $this->im = imagecreatefromstring($next);
            return $next;
        } else {
            imagedestroy($this->im);
            $this->im = imagecreatefromstring($this->gif->image());
            return false;
        }
    }
}