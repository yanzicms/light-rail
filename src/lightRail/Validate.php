<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class Validate
{
    private $rule;
    private $msg;
    private $data;
    private $error;
    private $rulename = [];
    private $defaultMsg = [
        'require'     => ':attribute require',
        'number'      => ':attribute must be numeric',
        'integer'     => ':attribute must be integer',
        'float'       => ':attribute must be float',
        'boolean'     => ':attribute must be bool',
        'email'       => ':attribute not a valid email address',
        'array'       => ':attribute must be a array',
        'accepted'    => ':attribute must be yes,on or 1',
        'date'        => ':attribute not a valid datetime',
        'file'        => ':attribute not a valid file',
        'image'       => ':attribute not a valid image',
        'alpha'       => ':attribute must be alpha',
        'alphaNum'    => ':attribute must be alpha-numeric',
        'alphaDash'   => ':attribute must be alpha-numeric, dash, underscore',
        'activeUrl'   => ':attribute not a valid domain or ip',
        'chs'         => ':attribute must be chinese',
        'chsAlpha'    => ':attribute must be chinese or alpha',
        'chsAlphaNum' => ':attribute must be chinese,alpha-numeric',
        'chsDash'     => ':attribute must be chinese,alpha-numeric,underscore, dash',
        'url'         => ':attribute not a valid url',
        'ip'          => ':attribute not a valid ip',
        'dateFormat'  => ':attribute must be dateFormat of :rule',
        'in'          => ':attribute must be in :rule',
        'notIn'       => ':attribute be notin :rule',
        'between'     => ':attribute must between :1 - :2',
        'notBetween'  => ':attribute not between :1 - :2',
        'length'      => 'size of :attribute must be :rule',
        'max'         => 'max size of :attribute must be :rule',
        'min'         => 'min size of :attribute must be :rule',
        'after'       => ':attribute cannot be less than :rule',
        'before'      => ':attribute cannot exceed :rule',
        'afterWith'   => ':attribute cannot be less than :rule',
        'beforeWith'  => ':attribute cannot exceed :rule',
        'expire'      => ':attribute not within :rule',
        'allowIp'     => 'access IP is not allowed',
        'denyIp'      => 'access IP denied',
        'confirm'     => ':attribute out of accord with :2',
        'different'   => ':attribute cannot be same with :2',
        'egt'         => ':attribute must greater than or equal :rule',
        'gt'          => ':attribute must greater than :rule',
        'elt'         => ':attribute must less than or equal :rule',
        'lt'          => ':attribute must less than :rule',
        'eq'          => ':attribute must equal :rule',
        'unique'      => ':attribute has exists',
        'regex'       => ':attribute not conform to the rules',
        'captcha'       => 'invalid captcha',
    ];
    public function __construct($rule, $msg = [])
    {
        $this->rule = [];
        foreach($rule as $key => $val){
            if(false !== $pos = strpos($key, '|')){
                $name = trim(substr($key, 0, $pos));
                $this->rule[$name] = $val;
                $this->rulename[$name] = trim(substr($key, $pos + 1));
            }
            else{
                $this->rule[$key] = $val;
            }
        }
        $this->msg = $msg;
    }
    public function check($data)
    {
        $this->data = $data;
        $re = true;
        foreach($data as $key => $val){
            $rule = $this->rule[$key];
            if(!$this->checkRule($rule, $val, $key)){
                $re = false;
                break;
            }
        }
        return $re;
    }
    public function getError()
    {
        return $this->error;
    }
    private function checkRule($rule, $value, $key)
    {
        if(!is_array($rule)){
            $rulearr = toArrTrim($rule, '|');
            $rule = [];
            foreach($rulearr as $val){
                if(false !== $pos = strpos($val, ':')){
                    $rule[substr($val, 0, $pos)] = substr($val, $pos + 1);
                }
                else{
                    $rule[] = $val;
                }
            }
        }
        foreach($rule as $rkey => $rval){
            if(is_numeric($rkey)){
                list($result, $userule, $rearr) = $this->pass($value, $rval);
                $rulere = '';
            }
            else{
                list($result, $userule, $rearr) = $this->pass($value, $rkey, $rval);
                $rulere = $rval;
            }
            if(!$result){
                if(isset($this->msg[$key . '.' . $userule])){
                    $this->error = $this->msg[$key . '.' . $userule];
                }
                else{
                    $error = Lang::get($this->defaultMsg[$userule]);
                    $error = str_replace(':attribute', isset($this->rulename[$key]) ? $this->rulename[$key] : $key, $error);
                    $error = str_replace([':rule', ':1', ':2'], [$rulere, $rearr[0], $rearr[1]], $error);
                    $this->error = $error;
                }
                return false;
            }
        }
        return true;
    }
    private function pass($value, $rule, $rv = '')
    {
        $tmparr = ['', ''];
        switch($rule){
            case 'require':
                $result = !empty($value) || '0' == $value;
                break;
            case 'accepted':
                $result = in_array($value, ['1', 'on', 'yes']);
                break;
            case 'date':
                $result = false !== strtotime($value);
                break;
            case 'alpha':
                $result = $this->regex($value, '/^[A-Za-z]+$/');
                break;
            case 'alphaNum':
                $result = $this->regex($value, '/^[A-Za-z0-9]+$/');
                break;
            case 'alphaDash':
                $result = $this->regex($value, '/^[A-Za-z0-9\-\_]+$/');
                break;
            case 'chs':
                $result = $this->regex($value, '/^[\x{4e00}-\x{9fa5}]+$/u');
                break;
            case 'chsAlpha':
                $result = $this->regex($value, '/^[\x{4e00}-\x{9fa5}a-zA-Z]+$/u');
                break;
            case 'chsAlphaNum':
                $result = $this->regex($value, '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$/u');
                break;
            case 'chsDash':
                $result = $this->regex($value, '/^[\x{4e00}-\x{9fa5}a-zA-Z0-9\_\-]+$/u');
                break;
            case 'activeUrl':
                $result = checkdnsrr($value);
                break;
            case 'ip':
                if(filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) === false){
                    $result = false;
                }
                else{
                    $result = true;
                }
                break;
            case 'url':
                if(filter_var($value, FILTER_VALIDATE_URL) === false){
                    $result = false;
                }
                else{
                    $result = true;
                }
                break;
            case 'float':
                if(filter_var($value, FILTER_VALIDATE_FLOAT) === false){
                    $result = false;
                }
                else{
                    $result = true;
                }
                break;
            case 'number':
                $result = is_numeric($value);
                break;
            case 'integer':
                if(filter_var($value, FILTER_VALIDATE_INT) === false){
                    $result = false;
                }
                else{
                    $result = true;
                }
                break;
            case 'email':
                if(filter_var($value, FILTER_VALIDATE_EMAIL) === false){
                    $result = false;
                }
                else{
                    $result = true;
                }
                break;
            case 'boolean':
                $result = in_array($value, [true, false, 0, 1, '0', '1'], true);
                break;
            case 'array':
                $result = is_array($value);
                break;
            case 'dateFormat':
                $result = date($rv, strtotime($value)) == $value;
                break;
            case 'in':
                $result = in_array($value, toArrTrim($rv, ','));
                break;
            case 'notIn':
                $result = !in_array($value, toArrTrim($rv, ','));
                break;
            case 'between':
                $tmparr = toArrTrim($rv, ',');
                $result = $value >= $tmparr[0] && $value <= $tmparr[1];
                break;
            case 'notBetween':
                $tmparr = toArrTrim($rv, ',');
                $result = !($value >= $tmparr[0] && $value <= $tmparr[1]);
                break;
            case 'length':
                $tmparr = toArrTrim($rv, ',');
                $vallen = mb_strlen($value);
                if(count($tmparr) == 1){
                    $result = $vallen == $tmparr[0];
                }
                else{
                    $result = $vallen >= $tmparr[0] && $vallen <= $tmparr[1];
                }
                break;
            case 'max':
                $result = mb_strlen($value) <= $rv;
                break;
            case 'min':
                $result = mb_strlen($value) >= $rv;
                break;
            case 'after':
                $result = strtotime($value) > strtotime($rv);
                break;
            case 'before':
                $result = strtotime($value) < strtotime($rv);
                break;
            case 'expire':
                $tmparr = toArrTrim($rv, ',');
                $valtime = strtotime($value);
                $result = $valtime >= strtotime($tmparr[0]) && $valtime <= strtotime($tmparr[1]);
                break;
            case 'allowIp':
                $tmparr = toArrTrim($rv, ',');
                $ip = Request::instance()->ip();
                $result = in_array($ip, $tmparr);
                break;
            case 'denyIp':
                $tmparr = toArrTrim($rv, ',');
                $ip = Request::instance()->ip();
                $result = !in_array($ip, $tmparr);
                break;
            case 'confirm':
                $result = $value == $this->data[$rv];
                $tmparr = ['', $rv];
                break;
            case 'different':
                $result = $value != $this->data[$rv];
                $tmparr = ['', $rv];
                break;
            case 'eq':
            case '=':
            case 'same':
                if(isset($this->data[$rv])){
                    $result = $value == $this->data[$rv];
                    $tmparr = ['', $rv];
                }
                else{
                    $result = $value == $rv;
                }
                $rule = 'eq';
                break;
            case 'egt':
            case '>=':
                if(isset($this->data[$rv])){
                    $result = $value >= $this->data[$rv];
                    $tmparr = ['', $rv];
                }
                else{
                    $result = $value >= $rv;
                }
                $rule = 'egt';
                break;
            case 'gt':
            case '>':
                if(isset($this->data[$rv])){
                    $result = $value > $this->data[$rv];
                    $tmparr = ['', $rv];
                }
                else{
                    $result = $value > $rv;
                }
                $rule = 'gt';
                break;
            case 'elt':
            case '<=':
                if(isset($this->data[$rv])){
                    $result = $value <= $this->data[$rv];
                    $tmparr = ['', $rv];
                }
                else{
                    $result = $value <= $rv;
                }
                $rule = 'elt';
                break;
            case 'lt':
            case '<':
                if(isset($this->data[$rv])){
                    $result = $value < $this->data[$rv];
                    $tmparr = ['', $rv];
                }
                else{
                    $result = $value < $rv;
                }
                $rule = 'lt';
                break;
            case 'captcha':
                $result = App::instance()->captcha->check($value);
                break;
            default:
                if(empty($rv)){
                    $result = $this->regex($value, $rule);
                }
                else{
                    $result = $this->regex($value, $rv);
                }
                $rule = 'regex';
                break;
        }
        if(count($tmparr) == 1){
            $tmparr[] = '';
        }
        return [$result, $rule, $tmparr];
    }
    private function regex($value, $rule)
    {
        if(strpos($rule, '/') !== 0 && !preg_match('/\/[imsU]{0,4}$/', $rule)){
            $rule = '/^' . $rule . '$/';
        }
        return is_scalar($value) && preg_match($rule, (string)$value) === 1;
    }
}