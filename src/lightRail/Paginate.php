<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class Paginate
{
    private $total;
    private $page;
    private $per;
    private $pages;
    private $data;
    private $simple;
    private $query;
    private $type = null;
    private $var_page = 'page';
    public function structure($data)
    {
        $this->total = $data['total'];
        $this->page = $data['page'];
        $this->per = $data['per'];
        $this->pages = $data['pages'];
        $this->data = $data['data'];
        $this->simple = $data['simple'];
        if(isset($data['param']['query'])){
            $this->query = $data['param']['query'];
        }
        else{
            $this->query = [];
        }
        if(isset($data['param']['type'])){
            $this->type = trim($data['param']['type']);
        }
        if(isset($data['param']['var_page'])){
            $this->var_page = trim($data['param']['var_page']);
        }
        return $this;
    }
    public function currentPage()
    {
        return $this->page;
    }
    public function lastPage()
    {
        return $this->pages;
    }
    public function total()
    {
        return $this->total;
    }
    public function toArray()
    {
        $data = [
            'total' => $this->total,
            'per_page' => $this->per,
            'current_page' => $this->page,
            'data' => $this->data,
        ];
        return $data;
    }
    public function items()
    {
        return $this->data;
    }
    public function listRows()
    {
        return $this->per;
    }
    public function hasPages()
    {
        if($this->pages > 1){
            return true;
        }
        return false;
    }
    public function render()
    {
        if(is_null($this->type)){
            $this->type = trim(Config::get('pagination'));
        }
        return $this->paginateHtml($this->page, $this->pages, $this->query, $this->simple, $this->type);
    }
    public function paginateHtml($page, $pages, $query, $simple, $type)
    {
        if($pages <= 1){
            return '';
        }
        $numbers = Config::get('numbers');
        $previous = Config::get('previous');
        $next = Config::get('next');
        $containsul = Config::get('containsul');
        if(isset($query[$this->var_page])){
            unset($query[$this->var_page]);
        }
        $paramStr = http_build_query($query);
        $urlPath = parse_url(Url::uri(), PHP_URL_PATH);
        $pagingUrl = empty($paramStr) ? $urlPath . '?' . $this->var_page . '=' : $urlPath . '?' . $paramStr . '&' . $this->var_page . '=';
        if($simple){
            $pagingArr = $this->getSimpleArr($page, $pages, $pagingUrl, $previous, $next);
        }
        else{
            $pagingArr = $this->getArr($page, $pages, $pagingUrl, $numbers, $previous, $next);
        }
        return App::instance()->implement(ucfirst($type), 'getHtml', [$pagingArr, $containsul], 'lightRail\paginate');
    }
    private function getSimpleArr($page, $pages, $pagingUrl, $previous, $next)
    {
        $parr = [];
        for($i = 0; $i <= $pages + 1; $i ++){
            if($i == 0){
                $parr[] = [
                    'page' => $previous,
                    'url' => ($page == 1) ? '#' : $pagingUrl . ($page - 1),
                    'disabled' => ($page == 1) ? 'disabled' : '',
                    'active' => ''
                ];
                continue;
            }
            elseif($i > $pages){
                $parr[] = [
                    'page' => $next,
                    'url' => ($page == $pages) ? '#' : $pagingUrl . ($page + 1),
                    'disabled' => ($page == $pages) ? 'disabled' : '',
                    'active' => ''
                ];
                continue;
            }
            elseif($i == $page){
                $parr[] = [
                    'page' => $i,
                    'url' => $pagingUrl . $i,
                    'disabled' => '',
                    'active' => 'active'
                ];
                continue;
            }
            else{
                continue;
            }
        }
        return $parr;
    }
    private function getArr($page, $pages, $pagingUrl, $numbers, $previous, $next)
    {
        $parr = [];
        $left = false;
        $right = false;
        for($i = 0; $i <= $pages + 1; $i ++){
            if($i == 0){
                $parr[] = [
                    'page' => $previous,
                    'url' => ($page == 1) ? '#' : $pagingUrl . ($page - 1),
                    'disabled' => ($page == 1) ? 'disabled' : '',
                    'active' => ''
                ];
                continue;
            }
            if($i > $pages){
                $parr[] = [
                    'page' => $next,
                    'url' => ($page == $pages) ? '#' : $pagingUrl . ($page + 1),
                    'disabled' => ($page == $pages) ? 'disabled' : '',
                    'active' => ''
                ];
                continue;
            }
            if($i == 1){
                $parr[] = [
                    'page' => $i,
                    'url' => $pagingUrl . $i,
                    'disabled' => '',
                    'active' => ($page == $i) ? 'active' : ''
                ];
                continue;
            }
            if($i == $pages){
                $parr[] = [
                    'page' => $i,
                    'url' => $pagingUrl . $i,
                    'disabled' => '',
                    'active' => ($page == $i) ? 'active' : ''
                ];
                continue;
            }
            if($i > 1 && $i < $page - $numbers && $left == false){
                $parr[] = [
                    'page' => '...',
                    'url' => '#',
                    'disabled' => 'disabled',
                    'active' => ''
                ];
                $left = true;
                continue;
            }
            if($i < $pages && $i > $page + $numbers && $right == false){
                $parr[] = [
                    'page' => '...',
                    'url' => '#',
                    'disabled' => 'disabled',
                    'active' => ''
                ];
                $right = true;
                continue;
            }
            if($i >= $page - $numbers && $i <= $page + $numbers){
                $parr[] = [
                    'page' => $i,
                    'url' => $pagingUrl . $i,
                    'disabled' => '',
                    'active' => ($page == $i) ? 'active' : ''
                ];
                continue;
            }
        }
        return $parr;
    }
}