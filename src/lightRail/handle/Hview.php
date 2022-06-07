<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail\handle;

use lightRail\App;
use lightRail\Config;
use lightRail\Db;
use Closure;

class Hview
{
    private $structure = [];
    private $fields = [];
    private function get($name){
        return Db::get($name);
    }
    public function view($name, $field, $condition = null, $type = 'INNER')
    {
        if(is_null($condition)){
            $this->structure = [];
        }
        if(is_array($name)){
            foreach($name as $key => $val){
                $val = trim($val);
                $fieldarr = is_array($field) ? $field : toArrTrim($field);
                $this->structure['table'][] = [
                    'name' => $this->addPrefix($key),
                    'alias' => $val,
                    'field' => $fieldarr,
                    'condition' => $condition,
                    'type' => $type
                ];
                $this->tofield($val, $fieldarr);
                break;
            }
        }
        else{
            $name = trim($name);
            $prename = $this->addPrefix($name);
            $fieldarr = is_array($field) ? $field : toArrTrim($field);
            $this->structure['table'][] = [
                'name' => $prename,
                'alias' => ($prename == $name) ? '' : $name,
                'field' => $fieldarr,
                'condition' => $condition,
                'type' => $type
            ];
            $this->tofield($name, $fieldarr);
        }
        return $this;
    }
    private function tofield($name, $fieldarr)
    {
        $arr = [];
        foreach($fieldarr as $key => $val){
            if(is_numeric($key)){
                $arr[] = $val;
            }
            else{
                $arr[] = $key;
            }
        }
        if(isset($this->fields[$name])){
            $this->fields[$name] = array_unique(array_merge($this->fields[$name], $arr));
        }
        else{
            $this->fields[$name] = $arr;
        }
    }
    private function hasPrefix($name)
    {
        $prefix = $this->get('prefix');
        $prefixlen = strlen($prefix);
        if(substr($name, 0, $prefixlen) != $prefix){
            return false;
        }
        return true;
    }
    private function addPrefix($name)
    {
        $prefix = $this->get('prefix');
        $prefixlen = strlen($prefix);
        if(substr($name, 0, $prefixlen) != $prefix){
            $name = $prefix . $name;
        }
        return $name;
    }
    public function select()
    {
        list($where, $wharr) = $this->dealwhere();
        $table = $this->structure['table'];
        if(isset($this->structure['select'])){
            $select = $this->structure['select'];
        }
        else{
            $select = [];
        }
        if(isset($this->structure['cache'])){
            $cache = $this->structure['cache'];
        }
        else{
            $cache = [];
        }
        if(isset($this->structure['paginate'])){
            $paginate = $this->structure['paginate'];
        }
        else{
            $paginate = [];
        }
        if(!Db::isconnect()){
            Db::connect();
        }
        $app = App::instance();
        $result = $app->implement(ucfirst(trim($this->get('type'))), 'view', [$table, $where, $wharr, $select, $cache, $paginate], 'lightRail\db');
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
        $this->structure['select']['limit'] = $limit;
        $simple = false;
        if(is_bool($total)){
            $simple = $total;
            $total = 0;
        }
        $this->structure['paginate'] = [
            'total' => $total,
            'param' => $param,
            'page' => $page,
            'per' => $per,
            'simple' => $simple
        ];
        return $this->select();
    }
    public function where($key, $exp = null, $value = null, $method = 'and')
    {
        if($key instanceof Closure){
            $this->structure['where'][] = [
                'type' => 'sign',
                'exp' => '(',
                'method' => 'and'
            ];
            $key($this);
            $this->structure['where'][] = [
                'type' => 'sign',
                'exp' => ')',
                'method' => ''
            ];
        }
        elseif(is_null($exp) && is_null($value)){
            if(is_array($key)){
                foreach($key as $k => $v){
                    if(is_array($v)){
                        $this->structure['where'][] = [
                            'type' => 'exp',
                            'exp' => [trim($k), $v[0], $v[1]],
                            'method' => 'and'
                        ];
                    }
                    else{
                        $this->structure['where'][] = [
                            'type' => 'exp',
                            'exp' => [trim($k), '=', $v],
                            'method' => 'and'
                        ];
                    }
                }
            }
            else{
                $this->structure['where'][] = [
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
            $this->structure['where'][] = [
                'type' => 'sign',
                'exp' => '(',
                'method' => 'and'
            ];
            $first = array_shift($args);
            $this->structure['where'][] = [
                'type' => 'exp',
                'exp' => [trim($key), $first[0], $first[1]],
                'method' => 'and'
            ];
            foreach($args as $key => $val){
                $this->structure['where'][] = [
                    'type' => 'exp',
                    'exp' => [trim($key), $val[0], $val[1]],
                    'method' => $method
                ];
            }
            $this->structure['where'][] = [
                'type' => 'sign',
                'exp' => ')',
                'method' => ''
            ];
        }
        elseif(is_null($value) && is_array($exp)){
            $this->structure['where'][] = [
                'type' => 'prepro',
                'exp' => [trim($key), $exp],
                'method' => 'and'
            ];
        }
        else{
            if(is_null($value) && !is_array($exp)){
                $value = $exp;
                $exp = '=';
            }
            if(strpos($key, '|') !== false){
                $this->structure['where'][] = [
                    'type' => 'sign',
                    'exp' => '(',
                    'method' => 'and'
                ];
                $keyarr = explode('|', $key);
                $keyand = array_shift($keyarr);
                $this->structure['where'][] = [
                    'type' => 'exp',
                    'exp' => [trim($keyand), $exp, $value],
                    'method' => 'and'
                ];
                foreach($keyarr as $val){
                    $val = trim($val);
                    $this->structure['where'][] = [
                        'type' => 'exp',
                        'exp' => [$val, $exp, $value],
                        'method' => 'or'
                    ];
                }
                $this->structure['where'][] = [
                    'type' => 'sign',
                    'exp' => ')',
                    'method' => ''
                ];
            }
            elseif(strpos($key, '&') !== false){
                $this->structure['where'][] = [
                    'type' => 'sign',
                    'exp' => '(',
                    'method' => 'and'
                ];
                $keyarr = explode('&', $key);
                foreach($keyarr as $val){
                    $val = trim($val);
                    $this->structure['where'][] = [
                        'type' => 'exp',
                        'exp' => [$val, $exp, $value],
                        'method' => 'and'
                    ];
                }
                $this->structure['where'][] = [
                    'type' => 'sign',
                    'exp' => ')',
                    'method' => ''
                ];
            }
            else{
                $this->structure['where'][] = [
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
            $this->structure['where'][] = [
                'type' => 'sign',
                'exp' => '(',
                'method' => 'or'
            ];
            $key($this);
            $this->structure['where'][] = [
                'type' => 'sign',
                'exp' => ')',
                'method' => ''
            ];
        }
        elseif(is_null($exp) && is_null($value)){
            if(is_array($key)){
                foreach($key as $k => $v){
                    if(is_array($v)){
                        $this->structure['where'][] = [
                            'type' => 'exp',
                            'exp' => [trim($k), $v[0], $v[1]],
                            'method' => 'or'
                        ];
                    }
                    else{
                        $this->structure['where'][] = [
                            'type' => 'exp',
                            'exp' => [trim($k), '=', $v],
                            'method' => 'or'
                        ];
                    }
                }
            }
            else{
                $this->structure['where'][] = [
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
            $this->structure['where'][] = [
                'type' => 'sign',
                'exp' => '(',
                'method' => 'or'
            ];
            $first = array_shift($args);
            $this->structure['where'][] = [
                'type' => 'exp',
                'exp' => [trim($key), $first[0], $first[1]],
                'method' => 'or'
            ];
            foreach($args as $key => $val){
                $this->structure['where'][] = [
                    'type' => 'exp',
                    'exp' => [trim($key), $val[0], $val[1]],
                    'method' => $method
                ];
            }
            $this->structure['where'][] = [
                'type' => 'sign',
                'exp' => ')',
                'method' => ''
            ];
        }
        elseif(is_null($value) && is_array($exp)){
            $this->structure['where'][] = [
                'type' => 'prepro',
                'exp' => [trim($key), $exp],
                'method' => 'or'
            ];
        }
        else{
            if(is_null($value) && !is_array($exp)){
                $value = $exp;
                $exp = '=';
            }
            if(strpos($key, '&') !== false){
                $this->structure['where'][] = [
                    'type' => 'sign',
                    'exp' => '(',
                    'method' => 'or'
                ];
                $keyarr = explode('&', $key);
                $keyand = array_shift($keyarr);
                $this->structure['where'][] = [
                    'type' => 'exp',
                    'exp' => [trim($keyand), $exp, $value],
                    'method' => 'or'
                ];
                foreach($keyarr as $val){
                    $val = trim($val);
                    $this->structure['where'][] = [
                        'type' => 'exp',
                        'exp' => [$val, $exp, $value],
                        'method' => 'and'
                    ];
                }
                $this->structure['where'][] = [
                    'type' => 'sign',
                    'exp' => ')',
                    'method' => ''
                ];
            }
            elseif(strpos($key, '|') !== false){
                $this->structure['where'][] = [
                    'type' => 'sign',
                    'exp' => '(',
                    'method' => 'or'
                ];
                $keyarr = explode('|', $key);
                foreach($keyarr as $val){
                    $val = trim($val);
                    $this->structure['where'][] = [
                        'type' => 'exp',
                        'exp' => [$val, $exp, $value],
                        'method' => 'or'
                    ];
                }
                $this->structure['where'][] = [
                    'type' => 'sign',
                    'exp' => ')',
                    'method' => ''
                ];
            }
            else{
                $this->structure['where'][] = [
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
        if(isset($this->structure['where']) && count($this->structure['where']) > 0){
            foreach($this->structure['where'] as $key => $val){
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
                }
            }
        }
        return [$where, $wharr];
    }
    private function addName($name)
    {
        if(strpos($name, '.') === false){
            $sufix = '';
            if(false !== $pos = strpos($name, ' ')){
                $sufix = substr($name, $pos);
                $name = substr($name, 0, $pos);
            }
            foreach($this->fields as $key => $val){
                if(in_array($name, $val)){
                    $name = $key . '.' . $name;
                    break;
                }
            }
            if(!empty($sufix)){
                $name .= $sufix;
            }
        }
        return $name;
    }
    private function doexp($exp)
    {
        $exp[0] = $this->addName($exp[0]);
        $array = [];
        $exp[1] = strtolower(trim($exp[1]));
        $exp[1] = preg_replace('/ +/', ' ', $exp[1]);
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
    public function order($order)
    {
        if(is_array($order)){
            $orderarr = [];
            foreach($order as $key => $val){
                if(is_numeric($key)){
                    $orderarr[] = $this->addName($val) . ' ASC';
                }
                else{
                    $orderarr[] = $this->addName($key) . ' ' . strtoupper($val);
                }
            }
            $this->structure['select']['order'] = implode(',', $orderarr);
        }
        else{
            $orders = explode(',', $order);
            foreach($orders as $key => $val){
                $val = $this->addName(trim($val));
                if(strpos($val, ' ') === false){
                    $orders[$key] = $val . ' ASC';
                }
                else{
                    $orders[$key] = $val;
                }
            }
            $order = implode(',', $orders);
            $this->structure['select']['order'] = $order;
        }
        return $this;
    }
    public function limit($number, $count = null)
    {
        if(is_null($count)){
            if(isset($this->structure['select']['page']) && is_numeric($number)){
                $page = $this->structure['select']['page'];
                $number = intval($number);
                $offset = $page * $number - $number;
                $limit = $offset . ',' . $number;
                $this->structure['select']['limit'] = $limit;
            }
            else{
                $this->structure['select']['limit'] = $number;
            }
        }
        else{
            $limit = $number . ',' . $count;
            $this->structure['select']['limit'] = $limit;
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
                $this->structure['select']['limit'] = $limit;
            }
            else{
                if(isset($this->structure['select']['limit'])){
                    $per = $this->structure['select']['limit'];
                    $offset = $page * $per - $per;
                    $limit = $offset . ',' . $per;
                    $this->structure['select']['limit'] = $limit;
                }
                else{
                    $this->structure['select']['page'] = $page;
                }
            }
        }
        else{
            $offset = $page * $count - $count;
            $limit = $offset . ',' . $count;
            $this->structure['select']['limit'] = $limit;
        }
        return $this;
    }
    public function group($group)
    {
        $this->structure['select']['group'] = $this->addName($group);
        return $this;
    }
    public function having($having)
    {
        $this->structure['select']['having'] = $having;
        return $this;
    }
    public function distinct($distinct = true)
    {
        if($distinct){
            $this->structure['select']['distinct'] = true;
        }
        return $this;
    }
    public function whereTime($name, $opt, $inter = null)
    {
        $name = $this->addName(trim($name));
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
        $this->structure['where'][] = [
            'type' => 'exp',
            'exp' => [$name, $opt, $inter],
            'method' => 'and'
        ];
        return $this;
    }
    public function cache($name, $time = 0, $tag = null)
    {
        if(is_numeric($name)){
            $time = $name;
            $name = true;
        }
        $this->structure['cache'] = [
            'name' => $name,
            'time' => $time,
            'tag' => $tag
        ];
        return $this;
    }
    public function find()
    {
        list($where, $wharr) = $this->dealwhere();
        $table = $this->structure['table'];
        $this->structure['select']['limit'] = '0,1';
        $select = $this->structure['select'];
        if(isset($this->structure['cache'])){
            $cache = $this->structure['cache'];
        }
        else{
            $cache = [];
        }
        if(!Db::isconnect()){
            Db::connect();
        }
        $app = App::instance();
        $result = $app->implement(ucfirst(trim($this->get('type'))), 'view', [$table, $where, $wharr, $select, $cache], 'lightRail\db');
        if(isset($result[0])){
            return $result[0];
        }
        else{
            return null;
        }
    }
    public function count($column = '*')
    {
        list($where, $wharr) = $this->dealwhere();
        $table = $this->structure['table'];
        $this->structure['select']['count'] = $column;
        $select = $this->structure['select'];
        if(!Db::isconnect()){
            Db::connect();
        }
        $app = App::instance();
        return $app->implement(ucfirst(trim($this->get('type'))), 'viewCount', [$table, $where, $wharr, $select], 'lightRail\db');
    }
}