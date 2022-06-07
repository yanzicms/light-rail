<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

use Closure;
use lightRail\exception\PDOExecutionException;

class Drive
{
    private $structure = [];
    private $current = [];
    private $closure = false;
    private $hastable = false;
    public function table($name)
    {
        if($this->closure){
            $this->hastable = true;
        }
        $name = $this->addPrefixToStr($name);
        $this->structure[$name] = [];
        array_unshift($this->current, $name);
        return $this;
    }
    private function addPrefixToStr($name)
    {
        $namearr = [];
        if(is_array($name)){
            foreach($name as $key => $val){
                $namearr[] = trim($key) . ' ' . trim($val);
            }
        }
        elseif(strpos($name, '(') !== false){
            $namearr[] = trim($name);
        }
        elseif(strpos($name, ',') !== false){
            $tmparr = explode(',', $name);
            foreach($tmparr as $key => $val){
                $namearr[] = trim($val);
            }
        }
        else{
            $namearr[] = trim($name);
        }
        $prefix = Db::get('prefix');
        $prefixlen = strlen($prefix);
        foreach($namearr as $key => $val){
            if(substr($val, 0, $prefixlen) != $prefix && strpos($val, '(') === false){
                $namearr[$key] = $prefix . $val;
            }
        }
        return implode(',', $namearr);
    }
    public function name($name)
    {
        return $this->table($name);
    }
    public function connect()
    {
        return $this;
    }
    public function execute($sql, $data = [])
    {
        return Db::execute($sql, $data);
    }
    public function query($sql, $data = [])
    {
        return Db::query($sql, $data);
    }
    public function builder($name, $exec = true)
    {
        $result = '';
        switch($name){
            case 'insert':
                $result = $this->doinsert();
                break;
            case 'delete':
                $result = $this->dodelete();
                break;
            case 'update':
                $result = $this->doupdate();
                break;
            case 'select':
                $result = $this->doselect($exec);
                break;
            case 'count':
                $result = $this->docount();
                break;
            case 'max':
                $result = $this->domax();
                break;
            case 'min':
                $result = $this->domin();
                break;
            case 'avg':
                $result = $this->doavg();
                break;
            case 'sum':
                $result = $this->dosum();
                break;
        }
        return $result;
    }
    private function dosum()
    {
        $where = $this->structure[$this->current[0]]['whereString'];
        $wharr = $this->structure[$this->current[0]]['whereArray'];
        $sum = $this->structure[$this->current[0]]['sum'];
        $name = $this->current[0];
        unset($this->structure[$name]);
        array_shift($this->current);
        if(!Db::isconnect()){
            Db::connect();
        }
        return App::instance()->implement(ucfirst(trim(Db::get('type'))), 'sum', [$name, $where, $wharr, $sum], 'lightRail\db');
    }
    private function doavg()
    {
        $where = $this->structure[$this->current[0]]['whereString'];
        $wharr = $this->structure[$this->current[0]]['whereArray'];
        $avg = $this->structure[$this->current[0]]['avg'];
        $name = $this->current[0];
        unset($this->structure[$name]);
        array_shift($this->current);
        if(!Db::isconnect()){
            Db::connect();
        }
        return App::instance()->implement(ucfirst(trim(Db::get('type'))), 'avg', [$name, $where, $wharr, $avg], 'lightRail\db');
    }
    private function domin()
    {
        $where = $this->structure[$this->current[0]]['whereString'];
        $wharr = $this->structure[$this->current[0]]['whereArray'];
        $min = $this->structure[$this->current[0]]['min'];
        $name = $this->current[0];
        unset($this->structure[$name]);
        array_shift($this->current);
        if(!Db::isconnect()){
            Db::connect();
        }
        return App::instance()->implement(ucfirst(trim(Db::get('type'))), 'min', [$name, $where, $wharr, $min], 'lightRail\db');
    }
    private function domax()
    {
        $where = $this->structure[$this->current[0]]['whereString'];
        $wharr = $this->structure[$this->current[0]]['whereArray'];
        $max = $this->structure[$this->current[0]]['max'];
        $name = $this->current[0];
        unset($this->structure[$name]);
        array_shift($this->current);
        if(!Db::isconnect()){
            Db::connect();
        }
        return App::instance()->implement(ucfirst(trim(Db::get('type'))), 'max', [$name, $where, $wharr, $max], 'lightRail\db');
    }
    private function docount()
    {
        $where = $this->structure[$this->current[0]]['whereString'];
        $wharr = $this->structure[$this->current[0]]['whereArray'];
        $count = $this->structure[$this->current[0]]['count'];
        if(isset($this->structure[$this->current[0]]['field'])){
            $field = $this->structure[$this->current[0]]['field'];
        }
        else{
            $field = '*';
        }
        if(isset($this->structure[$this->current[0]]['select'])){
            $select = $this->structure[$this->current[0]]['select'];
        }
        else{
            $select = [];
        }
        $type = 'normal';
        if(isset($this->structure[$this->current[0]]['select']['join'])){
            $type = 'join';
        }
        elseif(isset($this->structure[$this->current[0]]['select']['union'])){
            $type = 'union';
        }
        $name = $this->current[0];
        unset($this->structure[$name]);
        array_shift($this->current);
        if(!Db::isconnect()){
            Db::connect();
        }
        if($type == 'join'){
            return App::instance()->implement(ucfirst(trim(Db::get('type'))), 'joinCount', [$name, $where, $wharr, $field, $select], 'lightRail\db');
        }
        elseif($type == 'union'){
            return App::instance()->implement(ucfirst(trim(Db::get('type'))), 'unionCount', [$name, $where, $wharr, $field, $select], 'lightRail\db');
        }
        else{
            return App::instance()->implement(ucfirst(trim(Db::get('type'))), 'count', [$name, $where, $wharr, $count], 'lightRail\db');
        }
    }
    private function doselect($exec = true)
    {
        $where = $this->structure[$this->current[0]]['whereString'];
        $wharr = $this->structure[$this->current[0]]['whereArray'];
        if(isset($this->structure[$this->current[0]]['field'])){
            $field = $this->structure[$this->current[0]]['field'];
        }
        else{
            $field = '*';
        }
        if(isset($this->structure[$this->current[0]]['select'])){
            $select = $this->structure[$this->current[0]]['select'];
        }
        else{
            $select = [];
        }
        if(isset($this->structure[$this->current[0]]['cache'])){
            $cache = $this->structure[$this->current[0]]['cache'];
        }
        else{
            $cache = [];
        }
        if(isset($this->structure[$this->current[0]]['paginate'])){
            $paginate = $this->structure[$this->current[0]]['paginate'];
        }
        else{
            $paginate = [];
        }
        $name = $this->current[0];
        unset($this->structure[$name]);
        array_shift($this->current);
        if(!Db::isconnect() && $exec){
            Db::connect();
        }
        $app = App::instance();
        if($exec){
            if(isset($select['join'])){
                $result = $app->implement(ucfirst(trim(Db::get('type'))), 'join', [$name, $where, $wharr, $field, $select, $cache, $paginate], 'lightRail\db');
            }
            elseif(isset($select['union'])){
                $result = $app->implement(ucfirst(trim(Db::get('type'))), 'union', [$name, $where, $wharr, $field, $select, $cache, $paginate], 'lightRail\db');
            }
            else{
                $result = $app->implement(ucfirst(trim(Db::get('type'))), 'select', [$name, $where, $wharr, $field, $select, $cache, $paginate], 'lightRail\db');
            }
            if(empty($paginate)){
                return $result;
            }
            else{
                if(isset($result['param']['data_type'])){
                    $datatype = strtolower(trim($result['param']['data_type']));
                }
                else{
                    $datatype = strtolower(trim(Config::get('paginationdata')));
                }
                if($datatype == 'object'){
                    return $app->paginate->structure($result);
                }
                else{
                    $query = isset($result['param']['query']) ? $result['param']['query'] : [];
                    if(!isset($result['param']['type'])){
                        $type = trim(Config::get('pagination'));
                    }
                    else{
                        $type = trim($result['param']['type']);
                    }
                    $result['paginate'] = $app->paginate->paginateHtml($result['page'], $result['pages'], $query, $result['simple'], $type);
                    unset($result['param']);
                    unset($result['simple']);
                    return $result;
                }
            }
        }
        else{
            if(isset($select['join'])){
                list($sqlStr, $array) = $app->implement(ucfirst(trim(Db::get('type'))), 'joinString', [$name, $where, $wharr, $field, $select], 'lightRail\db');
            }
            elseif(isset($select['union'])){
                list($sqlStr, $array) = $app->implement(ucfirst(trim(Db::get('type'))), 'unionString', [$name, $where, $wharr, $field, $select], 'lightRail\db');
            }
            else{
                list($sqlStr, $array) = $app->implement(ucfirst(trim(Db::get('type'))), 'selectString', [$name, $where, $wharr, $field, $select], 'lightRail\db');
            }
            return $this->backfill($sqlStr, $array);
        }
    }
    private function backfill($sqlStr, $array)
    {
        if(empty($array)){
            return $sqlStr;
        }
        foreach($array as $key => $val){
            $val = is_numeric($val) ? $val : '\'' . $val . '\'';
            $first = strpos($sqlStr, '?');
            while(substr_count(substr($sqlStr, 0, $first), '\'') % 2 != 0 || substr_count(substr($sqlStr, 0, $first), '"') % 2 != 0){
                $first = strpos($sqlStr, '?', $first + 1);
            }
            if(false !== $first){
                $sqlStr = substr($sqlStr, 0, $first) . $val . substr($sqlStr, $first + 1);
            }
        }
        return $sqlStr;
    }
    private function doupdate()
    {
        $where = $this->structure[$this->current[0]]['whereString'];
        $wharr = $this->structure[$this->current[0]]['whereArray'];
        $data = $this->structure[$this->current[0]]['data'];
        $quarr = [];
        $varr = [];
        foreach($data as $key => $val){
            if(is_array($val)){
                if($val[0] == 'exp'){
                    $quarr[] = $key . '=' . $val[1];
                }
                else{
                    throw new PDOExecutionException(Lang::get('Grammatical errors'));
                }
            }
            else{
                $quarr[] = $key . '=?';
                $varr[] = $val;
            }
        }
        $name = $this->current[0];
        if(!empty($where)){
            $varr = concatarrays($varr, $wharr);
        }
        unset($this->structure[$name]);
        array_shift($this->current);
        if(!Db::isconnect()){
            Db::connect();
        }
        return App::instance()->implement(ucfirst(trim(Db::get('type'))), 'update', [$name, $quarr, $where, $varr], 'lightRail\db');
    }
    private function dodelete()
    {
        $where = $this->structure[$this->current[0]]['whereString'];
        $wharr = $this->structure[$this->current[0]]['whereArray'];
        $name = $this->current[0];
        unset($this->structure[$name]);
        array_shift($this->current);
        if(!Db::isconnect()){
            Db::connect();
        }
        return App::instance()->implement(ucfirst(trim(Db::get('type'))), 'delete', [$name, $where, $wharr], 'lightRail\db');
    }
    private function doinsert()
    {
        
        $result = $this->dealinsert($this->structure[$this->current[0]]['data']);
        return $result;
    }
    private function dealinsert($data)
    {
        $karr = [];
        $quarr = [];
        $varr = [];
        if(count($data) != count($data, true)){
            foreach($data[0] as $key => $val){
                $karr[] = $key;
            }
            foreach($data as $key => $val){
                $quarr[$key] = [];
                foreach($val as $skey => $sval){
                    $quarr[$key][] = '?';
                    $varr[] = $sval;
                }
            }
        }
        else{
            foreach($data as $key => $val){
                $karr[] = $key;
                $quarr[0][] = '?';
                $varr[] = $val;
            }
        }
        $name = $this->current[0];
        unset($this->structure[$name]);
        array_shift($this->current);
        if(!Db::isconnect()){
            Db::connect();
        }
        return App::instance()->implement(ucfirst(trim(Db::get('type'))), 'insert', [$name, $karr, $quarr, $varr], 'lightRail\db');
    }
    public function insert($data)
    {
        $this->structure[$this->current[0]]['data'] = $data;
        return $this->builder('insert');
    }
    public function insertGetId($data)
    {
        return $this->insert($data);
    }
    public function insertAll($data)
    {
        return $this->insert($data);
    }
    public function where($key, $exp = null, $value = null, $method = 'and')
    {
        if($key instanceof Closure){
            $this->structure[$this->current[0]]['where'][] = [
                'type' => 'sign',
                'exp' => '(',
                'method' => 'and'
            ];
            $this->closure = true;
            $key($this);
            if($this->hastable){
                list($cexp, $cques) = $this->doclosure();
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'prepro',
                    'exp' => [$cexp, $cques],
                    'method' => 'and'
                ];
                $this->hastable = false;
            }
            $this->closure = false;
            $this->structure[$this->current[0]]['where'][] = [
                'type' => 'sign',
                'exp' => ')',
                'method' => ''
            ];
        }
        elseif(is_null($exp) && is_null($value)){
            if(is_array($key)){
                foreach($key as $k => $v){
                    if(is_array($v)){
                        $this->structure[$this->current[0]]['where'][] = [
                            'type' => 'exp',
                            'exp' => [trim($k), $v[0], $v[1]],
                            'method' => 'and'
                        ];
                    }
                    else{
                        $this->structure[$this->current[0]]['where'][] = [
                            'type' => 'exp',
                            'exp' => [trim($k), '=', $v],
                            'method' => 'and'
                        ];
                    }
                }
            }
            else{
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'statement',
                    'exp' => trim($key),
                    'method' => 'and'
                ];
            }
        }
        elseif(is_array($exp) && is_array($value)){
            $args = func_get_args();
            $key = array_shift($args);
            $method = end($args);
            if(is_string($method)){
                $method = trim($method);
                array_pop($args);
            }
            else{
                $method = 'and';
            }
            $this->structure[$this->current[0]]['where'][] = [
                'type' => 'sign',
                'exp' => '(',
                'method' => 'and'
            ];
            $first = array_shift($args);
            $this->structure[$this->current[0]]['where'][] = [
                'type' => 'exp',
                'exp' => [trim($key), $first[0], $first[1]],
                'method' => 'and'
            ];
            foreach($args as $key => $val){
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'exp',
                    'exp' => [trim($key), $val[0], $val[1]],
                    'method' => $method
                ];
            }
            $this->structure[$this->current[0]]['where'][] = [
                'type' => 'sign',
                'exp' => ')',
                'method' => ''
            ];
        }
        elseif(is_null($value) && is_array($exp)){
            $this->structure[$this->current[0]]['where'][] = [
                'type' => 'prepro',
                'exp' => [trim($key), $exp],
                'method' => 'and'
            ];
        }
        elseif(!is_null($exp) && $value instanceof Closure){
            $this->closure = true;
            $value($this);
            if($this->hastable){
                list($cexp, $cques) = $this->doclosure();
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'closure',
                    'exp' => [$key, $exp, $cexp, $cques],
                    'method' => 'and'
                ];
                $this->hastable = false;
            }
            else{
                throw new PDOExecutionException(Lang::get('Grammatical errors'));
            }
            $this->closure = false;
        }
        else{
            if(is_null($value) && !is_array($exp)){
                $value = $exp;
                $exp = '=';
            }
            if(strpos($key, '|') !== false){
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'sign',
                    'exp' => '(',
                    'method' => 'and'
                ];
                $keyarr = explode('|', $key);
                $keyand = array_shift($keyarr);
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'exp',
                    'exp' => [trim($keyand), $exp, $value],
                    'method' => 'and'
                ];
                foreach($keyarr as $val){
                    $val = trim($val);
                    $this->structure[$this->current[0]]['where'][] = [
                        'type' => 'exp',
                        'exp' => [$val, $exp, $value],
                        'method' => 'or'
                    ];
                }
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'sign',
                    'exp' => ')',
                    'method' => ''
                ];
            }
            elseif(strpos($key, '&') !== false){
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'sign',
                    'exp' => '(',
                    'method' => 'and'
                ];
                $keyarr = explode('&', $key);
                foreach($keyarr as $val){
                    $val = trim($val);
                    $this->structure[$this->current[0]]['where'][] = [
                        'type' => 'exp',
                        'exp' => [$val, $exp, $value],
                        'method' => 'and'
                    ];
                }
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'sign',
                    'exp' => ')',
                    'method' => ''
                ];
            }
            else{
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'exp',
                    'exp' => [trim($key), $exp, $value],
                    'method' => 'and'
                ];
            }
        }
        return $this;
    }
    public function whereOr($key, $exp = null, $value = null, $method = 'or')
    {
        if($key instanceof Closure){
            $this->structure[$this->current[0]]['where'][] = [
                'type' => 'sign',
                'exp' => '(',
                'method' => 'or'
            ];
            $this->closure = true;
            $key($this);
            if($this->hastable){
                list($cexp, $cques) = $this->doclosure();
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'prepro',
                    'exp' => [$cexp, $cques],
                    'method' => 'or'
                ];
                $this->hastable = false;
            }
            $this->closure = false;
            $this->structure[$this->current[0]]['where'][] = [
                'type' => 'sign',
                'exp' => ')',
                'method' => ''
            ];
        }
        elseif(is_null($exp) && is_null($value)){
            if(is_array($key)){
                foreach($key as $k => $v){
                    if(is_array($v)){
                        $this->structure[$this->current[0]]['where'][] = [
                            'type' => 'exp',
                            'exp' => [trim($k), $v[0], $v[1]],
                            'method' => 'or'
                        ];
                    }
                    else{
                        $this->structure[$this->current[0]]['where'][] = [
                            'type' => 'exp',
                            'exp' => [trim($k), '=', $v],
                            'method' => 'or'
                        ];
                    }
                }
            }
            else{
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'statement',
                    'exp' => trim($key),
                    'method' => 'or'
                ];
            }
        }
        elseif(is_array($exp) && is_array($value)){
            $args = func_get_args();
            $key = array_shift($args);
            $method = end($args);
            if(is_string($method)){
                $method = trim($method);
                array_pop($args);
            }
            else{
                $method = 'or';
            }
            $this->structure[$this->current[0]]['where'][] = [
                'type' => 'sign',
                'exp' => '(',
                'method' => 'or'
            ];
            $first = array_shift($args);
            $this->structure[$this->current[0]]['where'][] = [
                'type' => 'exp',
                'exp' => [trim($key), $first[0], $first[1]],
                'method' => 'or'
            ];
            foreach($args as $key => $val){
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'exp',
                    'exp' => [trim($key), $val[0], $val[1]],
                    'method' => $method
                ];
            }
            $this->structure[$this->current[0]]['where'][] = [
                'type' => 'sign',
                'exp' => ')',
                'method' => ''
            ];
        }
        elseif(is_null($value) && is_array($exp)){
            $this->structure[$this->current[0]]['where'][] = [
                'type' => 'prepro',
                'exp' => [trim($key), $exp],
                'method' => 'or'
            ];
        }
        elseif(!is_null($exp) && $value instanceof Closure){
            $this->closure = true;
            $value($this);
            if($this->hastable){
                list($cexp, $cques) = $this->doclosure();
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'closure',
                    'exp' => [$key, $exp, $cexp, $cques],
                    'method' => 'or'
                ];
                $this->hastable = false;
            }
            else{
                throw new PDOExecutionException(Lang::get('Grammatical errors'));
            }
            $this->closure = false;
        }
        else{
            if(is_null($value) && !is_array($exp)){
                $value = $exp;
                $exp = '=';
            }
            if(strpos($key, '&') !== false){
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'sign',
                    'exp' => '(',
                    'method' => 'or'
                ];
                $keyarr = explode('&', $key);
                $keyand = array_shift($keyarr);
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'exp',
                    'exp' => [trim($keyand), $exp, $value],
                    'method' => 'or'
                ];
                foreach($keyarr as $val){
                    $val = trim($val);
                    $this->structure[$this->current[0]]['where'][] = [
                        'type' => 'exp',
                        'exp' => [$val, $exp, $value],
                        'method' => 'and'
                    ];
                }
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'sign',
                    'exp' => ')',
                    'method' => ''
                ];
            }
            elseif(strpos($key, '|') !== false){
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'sign',
                    'exp' => '(',
                    'method' => 'or'
                ];
                $keyarr = explode('|', $key);
                foreach($keyarr as $val){
                    $val = trim($val);
                    $this->structure[$this->current[0]]['where'][] = [
                        'type' => 'exp',
                        'exp' => [$val, $exp, $value],
                        'method' => 'or'
                    ];
                }
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'sign',
                    'exp' => ')',
                    'method' => ''
                ];
            }
            else{
                $this->structure[$this->current[0]]['where'][] = [
                    'type' => 'exp',
                    'exp' => [trim($key), $exp, $value],
                    'method' => 'or'
                ];
            }
        }
        return $this;
    }
    public function dealwhere()
    {
        $where = '';
        $wharr = [];
        if(isset($this->structure[$this->current[0]]['where']) && count($this->structure[$this->current[0]]['where']) > 0){
            foreach($this->structure[$this->current[0]]['where'] as $key => $val){
                switch($val['type']){
                    case 'sign':
                        if(!empty($where) && !empty($val['method'])){
                            $where .= ' ' . strtoupper($val['method']) . ' ' . $val['exp'];
                        }
                        else{
                            $where .= $val['exp'];
                        }
                        break;
                    case 'statement':
                        if(!empty($where) && substr($where, -1) != '('){
                            $where .= ' ' . strtoupper($val['method']) . ' ' . $val['exp'];
                        }
                        else{
                            $where .= $val['exp'];
                        }
                        break;
                    case 'prepro':
                        if(!empty($where) && substr($where, -1) != '('){
                            $where .= ' ' . strtoupper($val['method']) . ' ' . $val['exp'][0];
                        }
                        else{
                            $where .= $val['exp'][0];
                        }
                        $wharr = concatarrays($wharr, $val['exp'][1]);
                        break;
                    case 'exp':
                        list($string, $array) = $this->doexp($val['exp']);
                        if(!empty($where) && substr($where, -1) != '('){
                            $where .= ' ' . strtoupper($val['method']) . ' ' . $string;
                        }
                        else{
                            $where .= $string;
                        }
                        $wharr = concatarrays($wharr, $array);
                        break;
                    case 'closure':
                        $string = $val['exp'][0] . ' ' . strtoupper($val['exp'][1]) . ' (' . $val['exp'][2] . ')';
                        if(!empty($where) && substr($where, -1) != '('){
                            $where .= ' ' . strtoupper($val['method']) . ' ' . $string;
                        }
                        else{
                            $where .= $string;
                        }
                        $wharr = concatarrays($wharr, $val['exp'][3]);
                        break;
                }
            }
        }
        return [$where, $wharr];
    }
    private function doexp($exp)
    {
        $array = [];
        $exp[1] = strtolower(trim($exp[1]));
        if($exp[1] == 'exp'){
            $string = $exp[0] . ' ' . trim($exp[2]);
        }
        elseif($exp[1] == 'in'){
            $instr = '';
            if(!is_array($exp[2])){
                $inarr = explode(',', $exp[2]);
            }
            else{
                $inarr = $exp[2];
            }
            foreach($inarr as $val){
                if(empty($instr)){
                    $instr .= '?';
                }
                else{
                    $instr .= ', ?';
                }
                $array[] = trim($val);
            }
            $instr = '(' . $instr . ')';
            $string = $exp[0] . ' IN ' . $instr;
        }
        elseif($exp[1] == 'not in'){
            $instr = '';
            if(!is_array($exp[2])){
                $inarr = explode(',', $exp[2]);
            }
            else{
                $inarr = $exp[2];
            }
            foreach($inarr as $val){
                if(empty($instr)){
                    $instr .= '?';
                }
                else{
                    $instr .= ', ?';
                }
                $array[] = trim($val);
            }
            $instr = '(' . $instr . ')';
            $string = $exp[0] . ' NOT IN ' . $instr;
        }
        elseif($exp[1] == 'between' || $exp[1] == 'between time'){
            if(is_string($exp[2])){
                $bearr = explode(',', $exp[2]);
            }
            else{
                $bearr = $exp[2];
            }
            $string = $exp[0] . ' BETWEEN ? AND ?';
            $array[] = trim($bearr[0]);
            $array[] = trim($bearr[1]);
        }
        elseif($exp[1] == 'not between' || $exp[1] == 'notbetween time'){
            if(is_string($exp[2])){
                $bearr = explode(',', $exp[2]);
            }
            else{
                $bearr = $exp[2];
            }
            $string = $exp[0] . ' NOT BETWEEN ? AND ?';
            $array[] = trim($bearr[0]);
            $array[] = trim($bearr[1]);
        }
        elseif($exp[1] == 'null'){
            $string = $exp[0] . ' IS NULL';
        }
        elseif($exp[1] == 'not null'){
            $string = $exp[0] . ' IS NOT NULL';
        }
        elseif($exp[1] == '< time' || $exp[1] == '> time'){
            $string = $exp[0] . ' ' . substr($exp[1], 0, 1) . ' ?';
            $array[] = trim($exp[2]);
        }
        elseif($exp[1] == '<= time' || $exp[1] == '>= time'){
            $string = $exp[0] . ' ' . substr($exp[1], 0, 2) . ' ?';
            $array[] = trim($exp[2]);
        }
        else{
            if($exp[1] == 'eq'){
                $exp[1] = '=';
            }
            elseif($exp[1] == 'neq'){
                $exp[1] = '<>';
            }
            elseif($exp[1] == 'gt'){
                $exp[1] = '>';
            }
            elseif($exp[1] == 'egt'){
                $exp[1] = '>=';
            }
            elseif($exp[1] == 'lt'){
                $exp[1] = '<';
            }
            elseif($exp[1] == 'elt'){
                $exp[1] = '<=';
            }
            else{
                $exp[1] = strtoupper($exp[1]);
            }
            $string = $exp[0] . ' ' . $exp[1] . ' ?';
            $array[] = trim($exp[2]);
        }
        return [$string, $array];
    }
    public function delete()
    {
        list($where, $wharr) = $this->dealwhere();
        $this->structure[$this->current[0]]['whereString'] = $where;
        $this->structure[$this->current[0]]['whereArray'] = $wharr;
        return $this->builder('delete');
    }
    public function update($data)
    {
        list($where, $wharr) = $this->dealwhere();
        $this->structure[$this->current[0]]['whereString'] = $where;
        $this->structure[$this->current[0]]['whereArray'] = $wharr;
        $this->structure[$this->current[0]]['data'] = $data;
        return $this->builder('update');
    }
    public function setDec($field, $value = 1)
    {
        return $this->update([
            $field => ['exp', $field . '-' . $value]
        ]);
    }
    public function setInc($field, $value = 1)
    {
        return $this->update([
            $field => ['exp', $field . '+' . $value]
        ]);
    }
    public function order($order)
    {
        if(is_array($order)){
            $orderarr = [];
            foreach($order as $key => $val){
                if(is_numeric($key)){
                    $orderarr[] = $val . ' ASC';
                }
                else{
                    $orderarr[] = $key . ' ' . strtoupper($val);
                }
            }
            $this->structure[$this->current[0]]['select']['order'] = implode(',', $orderarr);
        }
        else{
            $orders = explode(',', $order);
            foreach($orders as $key => $val){
                $val = trim($val);
                if(strpos($val, ' ') === false){
                    $orders[$key] = $val . ' ASC';
                }
            }
            $order = implode(',', $orders);
            $this->structure[$this->current[0]]['select']['order'] = $order;
        }
        return $this;
    }
    public function limit($number, $count = null)
    {
        if(is_null($count)){
            if(isset($this->structure[$this->current[0]]['select']['page']) && is_numeric($number)){
                $page = $this->structure[$this->current[0]]['select']['page'];
                $number = intval($number);
                $offset = $page * $number - $number;
                $limit = $offset . ',' . $number;
                $this->structure[$this->current[0]]['select']['limit'] = $limit;
            }
            else{
                $this->structure[$this->current[0]]['select']['limit'] = $number;
            }
        }
        else{
            $limit = $number . ',' . $count;
            $this->structure[$this->current[0]]['select']['limit'] = $limit;
        }
        return $this;
    }
    public function field($field)
    {
        if(is_array($field)){
            $fieldarr = [];
            foreach($field as $key => $val){
                if(is_numeric($key)){
                    $fieldarr[] = $val;
                }
                else{
                    $fieldarr[] = $key . ' as ' . $val;
                }
            }
            $this->structure[$this->current[0]]['field'] = implode(',', $fieldarr);
        }
        else{
            $this->structure[$this->current[0]]['field'] = $field;
        }
        return $this;
    }
    public function alias($alias)
    {
        if(is_array($alias)){
            $prefix = Db::get('prefix');
            $prefixlen = strlen($prefix);
            $aliarr = [];
            foreach($alias as $key => $val){
                if(substr($key, 0, $prefixlen) != $prefix){
                    $key = $prefix . $key;
                }
                $aliarr[$key] = $val;
            }
            $this->structure[$this->current[0]]['select']['alias'] = $aliarr;
        }
        else{
            $this->structure[$this->current[0]]['select']['alias'] = [
                $this->current[0] => $alias
            ];
        }
        return $this;
    }
    public function page($page, $count = null)
    {
        if(is_null($count)){
            if(strpos($page, ',') !== false){
                $pagearr = explode(',', $page);
                $pagenum = intval(trim($pagearr[0]));
                $per = intval(trim($pagearr[1]));
                $offset = $pagenum * $per - $per;
                $limit = $offset . ',' . $per;
                $this->structure[$this->current[0]]['select']['limit'] = $limit;
            }
            else{
                if(isset($this->structure[$this->current[0]]['select']['limit'])){
                    $per = $this->structure[$this->current[0]]['select']['limit'];
                    $offset = $page * $per - $per;
                    $limit = $offset . ',' . $per;
                    $this->structure[$this->current[0]]['select']['limit'] = $limit;
                }
                else{
                    $this->structure[$this->current[0]]['select']['page'] = $page;
                }
            }
        }
        else{
            $offset = $page * $count - $count;
            $limit = $offset . ',' . $count;
            $this->structure[$this->current[0]]['select']['limit'] = $limit;
        }
        return $this;
    }
    public function group($group)
    {
        $this->structure[$this->current[0]]['select']['group'] = $group;
        return $this;
    }
    public function having($having)
    {
        $this->structure[$this->current[0]]['select']['having'] = $having;
        return $this;
    }
    public function distinct($distinct = true)
    {
        if($distinct){
            $this->structure[$this->current[0]]['select']['distinct'] = true;
        }
        return $this;
    }
    public function join($name, $on, $type = 'INNER')
    {
        $jname = '';
        $prefix = Db::get('prefix');
        $prefixlen = strlen($prefix);
        if(is_array($name)){
            foreach($name as $key => $val){
                if(substr($key, 0, $prefixlen) != $prefix){
                    $key = $prefix . $key;
                }
                $jname = $key . ' ' . $val;
                $this->structure[$this->current[0]]['select']['alias'][$key] = $val;
                break;
            }
        }
        else{
            if(false !== $space = strrpos($name, ' ')){
                $alias = trim(substr($name, $space));
                $pname = trim(substr($name, 0, $space));
                if(substr($pname, 0, 1) != '(' && substr($pname, 0, $prefixlen) != $prefix){
                    $pname = $prefix . $pname;
                }
                $this->structure[$this->current[0]]['select']['alias'][$pname] = $alias;
                $jname = $pname . ' ' . $alias;
            }
            else{
                $name = trim($name);
                if(substr($name, 0, 1) != '(' && substr($name, 0, $prefixlen) != $prefix){
                    $alias = $name;
                    $pname = $prefix . $name;
                }
                elseif(substr($name, 0, 1) != '('){
                    $alias = 'lr';
                    $pname = $name;
                }
                else{
                    $alias = substr($name, $prefixlen);
                    $pname = $name;
                }
                $this->structure[$this->current[0]]['select']['alias'][$pname] = $alias;
                $jname = $pname . ' ' . $alias;
            }
        }
        $this->structure[$this->current[0]]['select']['join'][] = [
            'name' => $jname,
            'on' => $on,
            'type' => strtoupper(trim($type))
        ];
        return $this;
    }
    public function whereTime($name, $opt, $inter = null)
    {
        $name = trim($name);
        $opt = trim($opt);
        switch($opt){
            case 'between':
            case 'not between':
            case '>':
            case '<':
            case '>=':
            case '<=':
                break;
            case 'today':
            case 'd':
                $name = 'to_days(' . $name . ')';
                $opt = '=';
                $inter = 'to_days(now())';
                break;
            case 'yesterday':
                $name = 'to_days(now())-to_days(' . $name . ')';
                $opt = '=';
                $inter = '1';
                break;
            case 'week':
            case 'w':
                $name = 'yearweek(date_format(' . $name . ',\'%Y-%m-%d\'))';
                $opt = '=';
                $inter = 'yearweek(now())';
                break;
            case 'last week':
                $name = 'yearweek(date_format(' . $name . ',\'%Y-%m-%d\'))';
                $opt = '=';
                $inter = 'yearweek(now())-1';
                break;
            case 'month':
            case 'm':
                $name = 'date_format(' . $name . ',\'%Y-%m\')';
                $opt = '=';
                $inter = 'date_format(now(),\'%Y-%m\')';
                break;
            case 'last month':
                $name = 'date_format(' . $name . ',\'%Y-%m\')';
                $opt = '=';
                $inter = 'date_format(date_sub(curdate(), interval 1 month),\'%Y-%m\')';
                break;
            case 'year':
            case 'y':
                $name = 'year(date_format(' . $name . ',\'%Y-%m-%d\'))';
                $opt = '=';
                $inter = 'year(now())';
                break;
            case 'last year':
                $name = 'year(date_format(' . $name . ',\'%Y-%m-%d\'))';
                $opt = '=';
                $inter = 'year(now())-1';
                break;
            default:
                $interval = $opt;
                if(substr($interval, 0, 1) == '-'){
                    $interval = trim(substr($interval, 1));
                }
                else{
                    $interval = '-' . $interval;
                }
                if(strtolower(substr($interval, -1)) == 's'){
                    $interval = substr($interval, 0, -1);
                }
                $opt = 'between';
                $inter = ['date_sub(date_format(now(),\'%Y-%m-%d %H:%i:%S\'),interval ' . $interval . ')', 'date_format(now(),\'%Y-%m-%d %H:%i:%S\')'];
                break;
        }
        $this->structure[$this->current[0]]['where'][] = [
            'type' => 'exp',
            'exp' => [$name, $opt, $inter],
            'method' => 'and'
        ];
        return $this;
    }
    public function union($statement, $array = [], $all = false)
    {
        if(is_bool($array)){
            $all = $array;
            $array = [];
        }
        if($statement instanceof Closure){
            $this->closure = true;
            $statement($this);
            if($this->hastable){
                list($cexp, $cques) = $this->doclosure();
                $this->structure[$this->current[0]]['select']['union'][] = [
                    'statement' => $cexp,
                    'array' => $cques,
                    'all' => $all
                ];
                $this->hastable = false;
            }
            $this->closure = false;
        }
        else{
            $this->structure[$this->current[0]]['select']['union'][] = [
                'statement' => $statement,
                'array' => $array,
                'all' => $all
            ];
        }
        return $this;
    }
    public function cache($name, $time = 0, $tag = null)
    {
        if(is_numeric($name)){
            $time = $name;
            $name = true;
        }
        $this->structure[$this->current[0]]['cache'] = [
            'name' => $name,
            'time' => $time,
            'tag' => $tag
        ];
        return $this;
    }
    public function select($exec = true)
    {
        list($where, $wharr) = $this->dealwhere();
        $this->structure[$this->current[0]]['whereString'] = $where;
        $this->structure[$this->current[0]]['whereArray'] = $wharr;
        return $this->builder('select', $exec);
    }
    public function buildSql()
    {
        list($where, $wharr) = $this->dealwhere();
        $this->structure[$this->current[0]]['whereString'] = $where;
        $this->structure[$this->current[0]]['whereArray'] = $wharr;
        return '(' . $this->builder('select', false) . ')';
    }
    public function find()
    {
        $this->structure[$this->current[0]]['select']['limit'] = '0,1';
        list($where, $wharr) = $this->dealwhere();
        $this->structure[$this->current[0]]['whereString'] = $where;
        $this->structure[$this->current[0]]['whereArray'] = $wharr;
        $result = $this->builder('select');
        if(isset($result[0])){
            return $result[0];
        }
        else{
            return null;
        }
    }
    public function count($column = '*')
    {
        $this->structure[$this->current[0]]['count'] = $column;
        list($where, $wharr) = $this->dealwhere();
        $this->structure[$this->current[0]]['whereString'] = $where;
        $this->structure[$this->current[0]]['whereArray'] = $wharr;
        return $this->builder('count');
    }
    public function max($column)
    {
        $this->structure[$this->current[0]]['max'] = $column;
        list($where, $wharr) = $this->dealwhere();
        $this->structure[$this->current[0]]['whereString'] = $where;
        $this->structure[$this->current[0]]['whereArray'] = $wharr;
        return $this->builder('max');
    }
    public function min($column)
    {
        $this->structure[$this->current[0]]['min'] = $column;
        list($where, $wharr) = $this->dealwhere();
        $this->structure[$this->current[0]]['whereString'] = $where;
        $this->structure[$this->current[0]]['whereArray'] = $wharr;
        return $this->builder('min');
    }
    public function avg($column)
    {
        $this->structure[$this->current[0]]['avg'] = $column;
        list($where, $wharr) = $this->dealwhere();
        $this->structure[$this->current[0]]['whereString'] = $where;
        $this->structure[$this->current[0]]['whereArray'] = $wharr;
        return $this->builder('avg');
    }
    public function sum($column)
    {
        $this->structure[$this->current[0]]['sum'] = $column;
        list($where, $wharr) = $this->dealwhere();
        $this->structure[$this->current[0]]['whereString'] = $where;
        $this->structure[$this->current[0]]['whereArray'] = $wharr;
        return $this->builder('sum');
    }
    public function paginate($per, $total = 0, $param = [])
    {
        $page = 1;
        if(isset($_GET['page'])){
            $page = intval($_GET['page']);
        }
        if($page < 1){
            $page = 1;
        }
        $offset = $page * $per - $per;
        $limit = $offset . ',' . $per;
        $this->structure[$this->current[0]]['select']['limit'] = $limit;
        $simple = false;
        if(is_bool($total)){
            $simple = $total;
            $total = 0;
        }
        $this->structure[$this->current[0]]['paginate'] = [
            'total' => $total,
            'param' => $param,
            'page' => $page,
            'per' => $per,
            'simple' => $simple
        ];
        list($where, $wharr) = $this->dealwhere();
        $this->structure[$this->current[0]]['whereString'] = $where;
        $this->structure[$this->current[0]]['whereArray'] = $wharr;
        return $this->builder('select');
    }
    private function doclosure()
    {
        list($where, $wharr) = $this->dealwhere();
        if(isset($this->structure[$this->current[0]]['field'])){
            $field = $this->structure[$this->current[0]]['field'];
        }
        else{
            $field = '*';
        }
        if(isset($this->structure[$this->current[0]]['select'])){
            $select = $this->structure[$this->current[0]]['select'];
        }
        else{
            $select = [];
        }
        $name = $this->current[0];
        unset($this->structure[$name]);
        array_shift($this->current);
        if(!Db::isconnect()){
            Db::connect();
        }
        list($exp, $ques) = App::instance()->implement(ucfirst(trim(Db::get('type'))), 'selectString', [$name, $where, $wharr, $field, $select], 'lightRail\db');
        $exp = '(' . $exp . ')';
        return [$exp, $ques];
    }
}