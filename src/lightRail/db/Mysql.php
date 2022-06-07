<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail\db;

use lightRail\Cache;
use lightRail\exception\PDOExecutionException;
use lightRail\Lang;
use PDO;

class Mysql
{
    protected $dbh = [];
    private $current;
    private function dsn($connect)
    {
        $port = $connect['hostport'];
        $charset = $connect['charset'];
        $hostname = $connect['hostname'];
        if($port != 3306){
            return 'mysql:host=' . $hostname . ';port=' . $port . ';dbname=' . $connect['database'] . ';charset=' . $charset;
        }
        else{
            return 'mysql:host=' . $hostname . ';dbname=' . $connect['database'] . ';charset=' . $charset;
        }
    }
    public function connect($connect)
    {
        $constr = array_md5($connect);
        if(isset($this->dbh[$constr])){
            $this->current = $this->dbh[$constr];
            return $this;
        }
        else{
            try {
                $this->current = new PDO($this->dsn($connect), $connect['username'], $connect['password']);
                $this->current->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->dbh[$constr] = $this->current;
                return $this;
            }
            catch(\PDOException $e){
                throw new PDOExecutionException(Lang::get('Database connection failed') . ': ' . $e->getMessage(), $e);
            }
        }
    }
    public function execute($sql, $data = [])
    {
        $sql = trim($sql);
        $sqlnb = ltrim($sql, '(');
        $sth = $this->current->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        if($sth->execute($data)){
            if(strtolower(substr($sqlnb, 0, 6)) == 'insert'){
                $result = $this->current->lastInsertId();
            }
            else{
                $result = $sth->rowCount();
            }
            return $result;
        }
        else{
            throw new PDOExecutionException(Lang::get('Database execution failed') . ': ' . $sql);
        }
    }
    public function query($sql, $data = [])
    {
        $sth = $this->current->prepare($sql, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
        if($sth->execute($data)){
            return $sth->fetchAll(PDO::FETCH_ASSOC);
        }
        else{
            throw new PDOExecutionException(Lang::get('Database query failed') . ': ' . $sql);
        }
    }
    public function startTrans()
    {
        $this->current->beginTransaction();
    }
    public function commit()
    {
        $this->current->commit();
    }
    public function rollback()
    {
        $this->current->rollBack();
    }
    public function update($name, $set, $where = '', $array = [])
    {
        if(!empty($where)){
            $sql = 'UPDATE ' . $name  . ' SET ' . implode(',', $set) . ' WHERE ' . $where;
        }
        else{
            $sql = 'UPDATE ' . $name  . ' SET ' . implode(',', $set);
        }
        return $this->execute($sql, $array);
    }
    public function insert($name, $keyArray, $valueArray, $array = [])
    {
        $valstr = '';
        foreach($valueArray as $key => $val){
            $valstr .= empty($valstr) ? '(' . implode(',', $val) . ')' : ', (' . implode(',', $val) . ')';
        }
        $sql = 'INSERT INTO ' . $name  . ' (' . implode(',', $keyArray) . ') VALUES ' . $valstr;
        return $this->execute($sql, $array);
    }
    public function delete($name, $where, $array = [])
    {
        $sql = 'DELETE FROM ' . $name  . ' WHERE ' . $where;
        return $this->execute($sql, $array);
    }
    public function select($name, $where, $wharr, $field, $select, $cache, $paginate)
    {
        $ispaginate = !empty($paginate);
        if($ispaginate){
            if($paginate['total'] <= 0){
                list($sql, $array) = $this->selectString($name, $where, $wharr, $field, $select, true);
                if(!empty($cache)){
                    if(is_bool($cache['name'])){
                        $cache['name'] = md5($sql . array_md5($array));
                    }
                    $cachecount = $cache['name'] . '_lightrail_paginate_count';
                    if(false === $recount = Cache::get($cachecount)){
                        $recount = $this->query($sql, $array);
                        Cache::set($cachecount, $recount, $cache['time'], $cache['tag']);
                    }
                }
                else{
                    $recount = $this->query($sql, $array);
                }
                $paginate['total'] = $recount[0]['lr_count'];
            }
            $paginate['pages'] = ceil($paginate['total'] / $paginate['per']);
        }
        list($sql, $array) = $this->selectString($name, $where, $wharr, $field, $select);
        if(!empty($cache)){
            if(is_bool($cache['name'])){
                $cache['name'] = md5($sql . array_md5($array));
            }
            if(false === $result = Cache::get($cache['name'])){
                $result = $this->query($sql, $array);
                Cache::set($cache['name'], $result, $cache['time'], $cache['tag']);
            }
        }
        else{
            $result = $this->query($sql, $array);
        }
        if($ispaginate){
            $paginate['data'] = $result;
            return $paginate;
        }
        else{
            return $result;
        }
    }
    public function selectString($name, $where, $wharr, $field, $select, $count = false)
    {
        $sql = 'SELECT';
        if($count){
            $sql .= ' COUNT(1) AS lr_count FROM ' . $name;
        }
        else{
            if(isset($select['distinct'])){
                $sql .= ' DISTINCT';
            }
            $sql .= ' ' . $field . ' FROM ' . $name;
            $alias = '';
            if(isset($select['alias']) && isset($select['alias'][$name])){
                $alias = $select['alias'][$name];
            }
            if(!empty($alias)){
                $sql .= ' ' . $alias;
            }
        }
        if(!empty($where)){
            $sql .= ' WHERE ' . $where;
        }
        if(isset($select['order'])){
            $sql .= ' ORDER BY ' . $select['order'];
        }
        if(isset($select['group'])){
            $sql .= ' GROUP BY ' . $select['group'];
        }
        if(isset($select['having'])){
            $sql .= ' HAVING ' . $select['having'];
        }
        if(isset($select['limit']) && !$count){
            $sql .= ' LIMIT ' . $select['limit'];
        }
        return [$sql, $wharr];
    }
    public function count($name, $where, $wharr, $count)
    {
        if(empty($count)){
            $sql = 'SELECT COUNT(1) AS lr_count';
        }
        else{
            $sql = 'SELECT COUNT(' . $count . ') AS lr_count';
        }
        $sql .= ' FROM `' . $name . '`';
        if(!empty($where)){
            $sql .= ' WHERE ' . $where;
        }
        $sql .= ' LIMIT 1';
        $result = $this->query($sql, $wharr);
        return $result[0]['lr_count'];
    }
    public function max($name, $where, $wharr, $max)
    {
        $sql = 'SELECT MAX(' . $max . ') AS lr_max';
        $sql .= ' FROM `' . $name . '`';
        if(!empty($where)){
            $sql .= ' WHERE ' . $where;
        }
        $sql .= ' LIMIT 1';
        $result = $this->query($sql, $wharr);
        return $result[0]['lr_max'];
    }
    public function min($name, $where, $wharr, $min)
    {
        $sql = 'SELECT MIN(' . $min . ') AS lr_min';
        $sql .= ' FROM `' . $name . '`';
        if(!empty($where)){
            $sql .= ' WHERE ' . $where;
        }
        $sql .= ' LIMIT 1';
        $result = $this->query($sql, $wharr);
        return $result[0]['lr_min'];
    }
    public function avg($name, $where, $wharr, $avg)
    {
        $sql = 'SELECT AVG(' . $avg . ') AS lr_avg';
        $sql .= ' FROM `' . $name . '`';
        if(!empty($where)){
            $sql .= ' WHERE ' . $where;
        }
        $sql .= ' LIMIT 1';
        $result = $this->query($sql, $wharr);
        return $result[0]['lr_avg'];
    }
    public function sum($name, $where, $wharr, $sum)
    {
        $sql = 'SELECT SUM(' . $sum . ') AS lr_sum';
        $sql .= ' FROM `' . $name . '`';
        if(!empty($where)){
            $sql .= ' WHERE ' . $where;
        }
        $sql .= ' LIMIT 1';
        $result = $this->query($sql, $wharr);
        return $result[0]['lr_sum'];
    }
    public function join($name, $where, $wharr, $field, $select, $cache, $paginate)
    {
        $ispaginate = !empty($paginate);
        if($ispaginate){
            if($paginate['total'] <= 0){
                list($sql, $array) = $this->joinString($name, $where, $wharr, $field, $select, true);
                if(!empty($cache)){
                    if(is_bool($cache['name'])){
                        $cache['name'] = md5($sql . array_md5($array));
                    }
                    $cachecount = $cache['name'] . '_lightrail_paginate_count';
                    if(false === $recount = Cache::get($cachecount)){
                        $recount = $this->query($sql, $array);
                        Cache::set($cachecount, $recount, $cache['time'], $cache['tag']);
                    }
                }
                else{
                    $recount = $this->query($sql, $array);
                }
                $paginate['total'] = $recount[0]['lr_count'];
            }
            $paginate['pages'] = ceil($paginate['total'] / $paginate['per']);
        }
        list($sql, $array) = $this->joinString($name, $where, $wharr, $field, $select);
        if(!empty($cache)){
            if(is_bool($cache['name'])){
                $cache['name'] = md5($sql . array_md5($array));
            }
            if(false === $result = Cache::get($cache['name'])){
                $result = $this->query($sql, $array);
                Cache::set($cache['name'], $result, $cache['time'], $cache['tag']);
            }
        }
        else{
            $result = $this->query($sql, $array);
        }
        if($ispaginate){
            $paginate['data'] = $result;
            return $paginate;
        }
        else{
            return $result;
        }
    }
    public function joinString($name, $where, $wharr, $field, $select, $count = false)
    {
        $sql = 'SELECT';
        if($count){
            $sql .= ' COUNT(1) AS lr_count FROM ' . $name;
        }
        else{
            $sql .= ' ' . $field . ' FROM ' . $name;
        }
        $alias = '';
        if(isset($select['alias']) && isset($select['alias'][$name])){
            $alias = $select['alias'][$name];
        }
        if(!empty($alias)){
            $sql .= ' ' . $alias;
        }
        foreach($select['join'] as $key => $val)
        {
            if($val['type'] == 'RIGHT'){
                $type = 'RIGHT JOIN';
            }
            elseif($val['type'] == 'LEFT'){
                $type = 'LEFT JOIN';
            }
            else{
                $type = 'INNER JOIN';
            }
            $sql .= ' ' . $type . ' ' . $val['name'];
            $alias = '';
            if(isset($select['alias']) && isset($select['alias'][$val['name']])){
                $alias = $select['alias'][$val['name']];
            }
            if(!empty($alias)){
                $sql .= ' ' . $alias;
            }
            $sql .= ' ON ' . $val['on'];
        }
        if(!empty($where)){
            $sql .= ' WHERE ' . $where;
        }
        if(isset($select['order'])){
            $sql .= ' ORDER BY ' . $select['order'];
        }
        if(isset($select['group'])){
            $sql .= ' GROUP BY ' . $select['group'];
        }
        if(isset($select['having'])){
            $sql .= ' HAVING ' . $select['having'];
        }
        if(isset($select['limit']) && !$count){
            $sql .= ' LIMIT ' . $select['limit'];
        }
        return [$sql, $wharr];
    }
    public function joinCount($name, $where, $wharr, $field, $select)
    {
        list($sql, $array) = $this->joinString($name, $where, $wharr, $field, $select, true);
        $result = $this->query($sql, $array);
        return $result[0]['lr_count'];
    }
    public function union($name, $where, $wharr, $field, $select, $cache, $paginate)
    {
        $ispaginate = !empty($paginate);
        if($ispaginate){
            if($paginate['total'] <= 0){
                list($sql, $array) = $this->unionString($name, $where, $wharr, $field, $select, true);
                if(!empty($cache)){
                    if(is_bool($cache['name'])){
                        $cache['name'] = md5($sql . array_md5($array));
                    }
                    $cachecount = $cache['name'] . '_lightrail_paginate_count';
                    if(false === $recount = Cache::get($cachecount)){
                        $recount = $this->query($sql, $array);
                        Cache::set($cachecount, $recount, $cache['time'], $cache['tag']);
                    }
                }
                else{
                    $recount = $this->query($sql, $array);
                }
                $paginate['total'] = $recount[0]['lr_count'];
            }
            $paginate['pages'] = ceil($paginate['total'] / $paginate['per']);
        }
        list($sql, $array) = $this->unionString($name, $where, $wharr, $field, $select);
        if(!empty($cache)){
            if(is_bool($cache['name'])){
                $cache['name'] = md5($sql . array_md5($array));
            }
            if(false === $result = Cache::get($cache['name'])){
                $result = $this->query($sql, $array);
                Cache::set($cache['name'], $result, $cache['time'], $cache['tag']);
            }
        }
        else{
            $result = $this->query($sql, $array);
        }
        if($ispaginate){
            $paginate['data'] = $result;
            return $paginate;
        }
        else{
            return $result;
        }
    }
    public function unionString($name, $where, $wharr, $field, $select, $count = false)
    {
        if($count){
            $sql = 'SELECT COUNT(1) AS lr_count FROM (';
        }
        else{
            $sql = '';
        }
        $sql .= 'SELECT ' . $field . ' FROM ' . $name;
        if(!empty($where)){
            $sql .= ' WHERE ' . $where;
        }
        foreach($select['union'] as $key => $val){
            $sql .= ' UNION';
            if($val['all'] == true){
                $sql .= ' ALL';
            }
            $sql .= ' ' . $val['statement'];
            $wharr = concatarrays($wharr, $val['array']);
        }
        if(isset($select['order'])){
            $sql .= ' ORDER BY ' . $select['order'];
        }
        if(isset($select['group'])){
            $sql .= ' GROUP BY ' . $select['group'];
        }
        if(isset($select['having'])){
            $sql .= ' HAVING ' . $select['having'];
        }
        if(isset($select['limit']) && !$count){
            $sql .= ' LIMIT ' . $select['limit'];
        }
        if($count){
            $sql .= ')';
        }
        return [$sql, $wharr];
    }
    public function unionCount($name, $where, $wharr, $field, $select)
    {
        list($sql, $array) = $this->unionString($name, $where, $wharr, $field, $select, true);
        $result = $this->query($sql, $array);
        return $result[0]['lr_count'];
    }
    public function view($table, $where, $wharr, $select, $cache, $paginate = [])
    {
        $ispaginate = !empty($paginate);
        if($ispaginate){
            if($paginate['total'] <= 0){
                list($sql, $array) = $this->viewString($table, $where, $wharr, $select, true);
                if(!empty($cache)){
                    if(is_bool($cache['name'])){
                        $cache['name'] = md5($sql . array_md5($array));
                    }
                    $cachecount = $cache['name'] . '_lightrail_paginate_count';
                    if(false === $recount = Cache::get($cachecount)){
                        $recount = $this->query($sql, $array);
                        Cache::set($cachecount, $recount, $cache['time'], $cache['tag']);
                    }
                }
                else{
                    $recount = $this->query($sql, $array);
                }
                $paginate['total'] = $recount[0]['lr_count'];
            }
            $paginate['pages'] = ceil($paginate['total'] / $paginate['per']);
        }
        list($sql, $array) = $this->viewString($table, $where, $wharr, $select);
        if(!empty($cache)){
            if(is_bool($cache['name'])){
                $cache['name'] = md5($sql . array_md5($array));
            }
            if(false === $result = Cache::get($cache['name'])){
                $result = $this->query($sql, $array);
                Cache::set($cache['name'], $result, $cache['time'], $cache['tag']);
            }
        }
        else{
            $result = $this->query($sql, $array);
        }
        if($ispaginate){
            $paginate['data'] = $result;
            return $paginate;
        }
        else{
            return $result;
        }
    }
    private function viewString($table, $where, $wharr, $select, $count = false)
    {
        $sql = $this->viewtable($table, $count);
        if(!empty($where)){
            $sql .= ' WHERE ' . $where;
        }
        if(isset($select['order'])){
            $sql .= ' ORDER BY ' . $select['order'];
        }
        if(isset($select['group'])){
            $sql .= ' GROUP BY ' . $select['group'];
        }
        if(isset($select['having'])){
            $sql .= ' HAVING ' . $select['having'];
        }
        if(isset($select['limit']) && !$count){
            $sql .= ' LIMIT ' . $select['limit'];
        }
        return [$sql, $wharr];
    }
    private function viewtable($table, $count = false)
    {
        $reStr = 'SELECT';
        if($count){
            $field = 'COUNT(1) AS lr_count';
        }
        else{
            $field = '';
            foreach($table as $key => $val){
                $prefix = empty($val['alias']) ? $val['name'] : $val['alias'];
                foreach($val['field'] as $fkey => $fval){
                    if(is_numeric($fkey)){
                        $fname = $fval;
                    }
                    else{
                        $fname = $fkey . ' AS ' . $fval;
                    }
                    if(strpos($fname, '.') === false){
                        $fname = $prefix . '.' . $fname;
                    }
                    $field .= empty($field) ? $fname : ',' . $fname;
                }
            }
        }
        $reStr .= ' ' . $field;
        foreach($table as $key => $val){
            if($key == 0){
                $reStr .= ' FROM ' . $val['name'];
                if(!empty($val['alias'])){
                    $reStr .= ' ' . $val['alias'];
                }
            }
            else{
                $type = strtoupper(trim($val['type']));
                if($type == 'LEFT'){
                    $reStr .= ' LEFT JOIN';
                }
                elseif($type == 'RIGHT'){
                    $reStr .= ' RIGHT JOIN';
                }
                else{
                    $reStr .= ' INNER JOIN';
                }
                $reStr .= ' ' . $val['name'];
                if(!empty($val['alias'])){
                    $reStr .= ' ' . $val['alias'];
                }
                $reStr .= ' ON ' . $val['condition'];
            }
        }
        return $reStr;
    }
    public function viewCount($table, $where, $wharr, $select)
    {
        list($sql, $array) = $this->viewString($table, $where, $wharr, $select, true);
        $result = $this->query($sql, $array);
        return $result[0]['lr_count'];
    }
}