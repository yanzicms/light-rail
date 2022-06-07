<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

use lightRail\exception\FileNotFoundException;

class Template
{
    private $app;
    private $request;
    private $foritem = [];
    public function __construct(App $app, Request $request)
    {
        $this->app = $app;
        $this->request = $request;
    }
    public function fetch($template = '')
    {
        $templatesuffix = '.' . trim(Config::get('templatesuffix'));
        if(empty($template)){
            $template = APP_PATH . $this->request->controllerName() . DS . 'view' . DS . $this->request->className() . DS . $this->request->methodName() . $templatesuffix;
        }
        else{
            if(substr($template, 0, strlen(ROOT_PATH)) != ROOT_PATH){
                $template = APP_PATH . $this->request->controllerName() . DS . 'view' . DS . $this->request->className() . DS . str_replace(['/', '\\'], DS, $template);
            }
            $suffixlen = strlen($templatesuffix);
            if(substr($template, - $suffixlen) != $templatesuffix){
                $template .= $templatesuffix;
            }
        }
        if(is_file($template)){
            return $this->get($template);
        }
        else{
            throw new FileNotFoundException(Lang::get('Template not exists') . ': ' . $template);
        }
    }
    private function get($template)
    {
        $folder = dirname($template);
        $content = file_get_contents($template);
        while(preg_match($this->preg('include_o'), $content, $match)){
            if(strpos($match[1], '/') !== false){
                $match[1] = trim(str_replace(['/', '\\'], DS, $match[1]), DS);
                if(false !== $pos = stripos($folder, DS . 'view' . DS)){
                    $foldersub = substr($folder, 0, $pos + 5);
                    $tplinc = $foldersub . DS . $match[1];
                }
                else{
                    $tplinc = ROOT_PATH . $match[1];
                }
            }
            else{
                $tplinc = $folder . DS . $match[1];
            }
            $templatesuffix = '.' . Config::get('templatesuffix');
            $templen = strlen($templatesuffix);
            if(substr($tplinc, - $templen) != $templatesuffix){
                $tplinc .= $templatesuffix;
            }
            if(is_file($tplinc)){
                $incfile = file_get_contents($tplinc);
                $content = str_replace($match[0], $incfile, $content);
                continue;
            }
            else{
                throw new FileNotFoundException(Lang::get('Template not exists') . ': ' . $tplinc);
                break;
            }
        }
        while(preg_match($this->preg('include'), $content, $match)){
            $match[1] = trim(str_replace(['/', '\\'], DS, $match[1]), DS);
            $tplinc = $folder . DS . $match[1];
            $templatesuffix = '.' . Config::get('templatesuffix');
            $templen = strlen($templatesuffix);
            if(substr($tplinc, - $templen) != $templatesuffix){
                $tplinc .= $templatesuffix;
            }
            if(is_file($tplinc)){
                $incfile = file_get_contents($tplinc);
                $content = str_replace($match[0], $incfile, $content);
                continue;
            }
            else{
                throw new FileNotFoundException(Lang::get('Template not exists') . ': ' . $tplinc);
                break;
            }
        }
        return $this->getContent($content);
    }
    private function preg($name)
    {
        $restr = str_replace('{', '\{', trim(Config::get('tagstart')));
        $restr = '(?<!'.$restr.')' . $restr;
        switch($name){
            case 'include':
                $restr .= 'include\s+((\.\.\/)*[A-Za-z][A-Za-z0-9_\-]*(\/[A-Za-z][A-Za-z0-9_\-]*)*)';
                break;
            case 'include_o':
                $restr .= 'include\s+file\s*\=\s*"(.+?)"\s*\/?';
                break;
            case 'if':
                $restr .= 'if *\((.+?)\)';
                break;
            case 'if_on':
                $restr .= 'if +(.+?)';
                break;
            case 'if_o':
                $restr .= 'if +condition *\= *"(.+?)"';
                break;
            case 'elseif':
                $restr .= 'elseif *\((.+?)\)';
                break;
            case 'elseif_on':
                $restr .= 'elseif +(.+?)';
                break;
            case 'elseif_o':
                $restr .= 'elseif +condition *\= *"(.+?)"\s*\/?';
                break;
            case 'empty':
                $restr .= 'empty +(.+?)';
                break;
            case 'empty_o':
                $restr .= 'empty +name\s*\=\s*"(.+?)"\s*\/?';
                break;
            case 'notempty':
                $restr .= 'notempty +(.+?)';
                break;
            case 'notempty_o':
                $restr .= 'notempty +name\s*\=\s*"(.+?)"\s*\/?';
                break;
            case 'eq':
                $restr .= 'eq +(name=.+? value=.+?)';
                break;
            case 'equal':
                $restr .= 'equal +(name=.+? value=.+?)';
                break;
            case 'neq':
                $restr .= 'neq +(name=.+? value=.+?)';
                break;
            case 'notequal':
                $restr .= 'notequal +(name=.+? value=.+?)';
                break;
            case 'gt':
                $restr .= 'gt +(name=.+? value=.+?)';
                break;
            case 'egt':
                $restr .= 'egt +(name=.+? value=.+?)';
                break;
            case 'lt':
                $restr .= 'lt +(name=.+? value=.+?)';
                break;
            case 'elt':
                $restr .= 'elt +(name=.+? value=.+?)';
                break;
            case 'heq':
                $restr .= 'heq +(name=.+? value=.+?)';
                break;
            case 'nheq':
                $restr .= 'nheq +(name=.+? value=.+?)';
                break;
            case 'each':
                $restr .= 'each +(.+? in .+?)';
                break;
            case 'volist':
                $restr .= 'volist +(name=.+? id=.+?)';
                break;
            case 'foreach':
                $restr .= 'foreach +(name=.+? item=.+?)';
                break;
            case 'endif':
                $restr .= '(endif|\/if)';
                break;
            case 'endempty':
                $restr .= '(endempty|\/empty)';
                break;
            case 'endnotempty':
                $restr .= '(endnotempty|\/notempty)';
                break;
            case 'endeach':
                $restr .= '(endeach|\/each)';
                break;
            case 'endvolist':
                $restr .= '(endvolist|\/volist)';
                break;
            case 'endforeach':
                $restr .= '(endforeach|\/foreach)';
                break;
            case 'endeq':
                $restr .= '(endeq|\/eq)';
                break;
            case 'endequal':
                $restr .= '(endequal|\/equal)';
                break;
            case 'endneq':
                $restr .= '(endneq|\/neq)';
                break;
            case 'endnotequal':
                $restr .= '(endnotequal|\/notequal)';
                break;
            case 'endgt':
                $restr .= '(endgt|\/gt)';
                break;
            case 'endegt':
                $restr .= '(endegt|\/egt)';
                break;
            case 'endlt':
                $restr .= '(endlt|\/lt)';
                break;
            case 'endelt':
                $restr .= '(endelt|\/elt)';
                break;
            case 'endheq':
                $restr .= '(endheq|\/heq)';
                break;
            case 'endnheq':
                $restr .= '(endnheq|\/nheq)';
                break;
            case 'else':
                $restr .= 'else\s*\/?';
                break;
            case 'variable':
                $restr .= '( *\$[^\$]+?)';
                break;
            case 'lang':
                $restr .= 'lang\s*\(\s*[\'|"]([\s\S]+?)[\'|"]\s*\)';
                break;
            case 'lang_o':
                $restr .= ':\s*lang\s*\(\s*[\'|"]([\s\S]+?)[\'|"]\s*\)';
                break;
            case 'lang_v':
                $restr .= 'lang\s*\(\s*(\$.+?)\s*\)';
                break;
            case 'lang_o_v':
                $restr .= ':\s*lang\s*\(\s*(\$.+?)\s*\)';
                break;
            case 'url':
                $restr .= 'url\s*\((.+?)\)\s*';
                break;
            case 'url_o':
                $restr .= ':\s*url\s*\((.+?)\)\s*';
                break;
            case 'captcha_src':
                $restr .= ':\s*captcha_src\s*\(()\)\s*';
                break;
            case 'captcha_img':
                $restr .= ':\s*captcha_img\s*\(()\)\s*';
                break;
            case 'func':
                $restr .= ': *(.+?) *';
                break;
        }
        $tag = str_replace('}', '\}', trim(Config::get('tagsend')));
        $restr .= $tag . '(?!'.$tag.')';
        return '/' . $restr . '/i';
    }
    private function getContent($content)
    {
        $file = md5($content);
        $path = ROOT_PATH . 'runtime' . DS . 'temp';
        if(!is_dir($path)){
            @mkdir($path, 0777, true);
        }
        $file = $path .DS . $file . '.php';
        if(!is_file($file)){
            $content = $this->generate($content);
            file_put_contents($file, $content);
        }
        return $this->content($file);
    }
    private function content($file)
    {
        if(PHP_VERSION > 8.0){
            ob_implicit_flush(false);
        }
        else{
            ob_implicit_flush(0);
        }
        $this->app->load($file, View::get());
        return ob_get_clean();
    }
    private function generate($content)
    {
        $deal = [
            'each' => 'endeach',
            'volist' => 'endvolist',
            'foreach' => 'endforeach',
            'if_o' => 'endif',
            'if' => 'endif',
            'if_on' => 'endif',
            'elseif_o' => 'endif',
            'elseif' => 'endif',
            'elseif_on' => 'endif',
            'empty_o' => 'endempty',
            'empty' => 'endempty',
            'notempty_o' => 'endnotempty',
            'notempty' => 'endnotempty',
            'eq' => 'endeq',
            'equal' => 'endequal',
            'neq' => 'endneq',
            'notequal' => 'endnotequal',
            'gt' => 'endgt',
            'egt' => 'endegt',
            'lt' => 'endlt',
            'elt' => 'endelt',
            'heq' => 'endheq',
            'nheq' => 'endnheq',
            'lang' => '',
            'lang_o' => '',
            'lang_v' => '',
            'lang_o_v' => '',
            'url' => '',
            'url_o' => '',
            'captcha_src' => '',
            'captcha_img' => '',
            'func' => '',
        ];
        foreach($deal as $key => $val){
            while(preg_match($this->preg($key), $content, $match)){
                if(in_array($key, ['lang', 'lang_o'])){
                    $subcont = $this->getsub($key, $match[1]);
                }
                else{
                    $subcont = $this->getsub($key, trim($match[1]));
                }
                $content = str_replace($match[0], $subcont, $content);
            }
            if(!empty($val)){
                $content = preg_replace($this->preg($val), '<?php } ?>', $content);
            }
        }
        $content = preg_replace($this->preg('else'), '<?php } else { ?>', $content);
        while(preg_match($this->preg('variable'), $content, $match)){
            $match[1] = $this->convert($match[1]);
            if(strpos($match[1], '|') !== false){
                $labelarr = explode('|', $match[1]);
                $funcstr = array_shift($labelarr);
                $funcstr = trim($funcstr);
                foreach($labelarr as $key => $val){
                    $val = trim($val);
                    if(false !== $eq = strpos($val, '=')){
                        $func = trim(substr($val, 0, $eq));
                        $params = trim(substr($val, $eq + 1));
                        $params = ltrim($params, '(');
                        $params = rtrim($params, ')');
                        $params = trim($params);
                        if(strpos($params, '###') !== false){
                            $params = str_replace('###', $funcstr, $params);
                            $funcstr = $func . '(' . $params . ')';
                        }
                        else{
                            $funcstr = $func . '(' . $funcstr . ', ' . $params . ')';
                        }
                    }
                    else{
                        $funcstr = $val . '(' . $funcstr . ')';
                    }
                }
                $content = str_replace($match[0], '<?php echo ' . $funcstr . '; ?>', $content);
            }
            if(strpos($match[1], '=') !== false){
                $content = str_replace($match[0], '<?php ' . $match[1] . '; ?>', $content);
            }
            else{
                $content = str_replace($match[0], '<?php echo ' . $match[1] . '; ?>', $content);
            }
        }
        return $content;
    }
    private function getsub($name, $statement)
    {
        $restr = '';
        switch($name){
            case 'if':
            case 'if_o':
            case 'if_on':
                $restr = '<?php if(' . $this->convert($statement) . ') { ?>';
                break;
            case 'elseif':
            case 'elseif_o':
            case 'elseif_on':
                $restr = '<?php } elseif(' . $this->convert($statement) . ') { ?>';
                break;
            case 'empty':
                $statement = $this->convert($statement);
                $restr = '<?php if(!isset(' . $statement . ') || empty(' . $statement . ')) { ?>';
                break;
            case 'empty_o':
                $statement = $this->convert('$' . $statement);
                $restr = '<?php if(!isset(' . $statement . ') || empty(' . $statement . ')) { ?>';
                break;
            case 'notempty':
                $statement = $this->convert($statement);
                $restr = '<?php if(isset(' . $statement . ') && !empty(' . $statement . ')) { ?>';
                break;
            case 'notempty_o':
                $statement = $this->convert('$' . $statement);
                $restr = '<?php if(isset(' . $statement . ') && !empty(' . $statement . ')) { ?>';
                break;
            case 'eq':
            case 'equal':
                list($lval, $rval) = $this->getNV($statement);
                $restr = '<?php if(' . $lval . ' == ' . $rval . ') { ?>';
                break;
            case 'neq':
            case 'notequal':
                list($lval, $rval) = $this->getNV($statement);
                $restr = '<?php if(' . $lval . ' != ' . $rval . ') { ?>';
                break;
            case 'gt':
                list($lval, $rval) = $this->getNV($statement);
                $restr = '<?php if(' . $lval . ' > ' . $rval . ') { ?>';
                break;
            case 'egt':
                list($lval, $rval) = $this->getNV($statement);
                $restr = '<?php if(' . $lval . ' >= ' . $rval . ') { ?>';
                break;
            case 'lt':
                list($lval, $rval) = $this->getNV($statement);
                $restr = '<?php if(' . $lval . ' < ' . $rval . ') { ?>';
                break;
            case 'elt':
                list($lval, $rval) = $this->getNV($statement);
                $restr = '<?php if(' . $lval . ' <= ' . $rval . ') { ?>';
                break;
            case 'heq':
                list($lval, $rval) = $this->getNV($statement);
                $restr = '<?php if(' . $lval . ' === ' . $rval . ') { ?>';
                break;
            case 'nheq':
                list($lval, $rval) = $this->getNV($statement);
                $restr = '<?php if(' . $lval . ' !== ' . $rval . ') { ?>';
                break;
            case 'each':
                $restr = $this->getEach($statement);
                break;
            case 'volist':
                $restr = $this->getVolist($statement);
                break;
            case 'foreach':
                $restr = $this->getForeach($statement);
                break;
            case 'lang':
            case 'lang_o':
                $restr = '<?php echo lang(\'' . $statement . '\'); ?>';
                break;
            case 'lang_v':
            case 'lang_o_v':
                $restr = '<?php echo lang(' . $this->convert($statement) . '); ?>';
                break;
            case 'url':
            case 'url_o':
                $restr = '<?php echo url(' . $this->convert($statement) . '); ?>';
                break;
            case 'captcha_src':
                $restr = '<?php echo captcha_src(); ?>';
                break;
            case 'captcha_img':
                $restr = '<?php echo \'<img src="\' . captcha_src() . \'" alt="captcha" />\'; ?>';
                break;
            case 'func':
                $restr = '<?php echo ' . $this->convert($statement) . '; ?>';
                break;
        }
        return $restr;
    }
    private function getNV($statement)
    {
        $name = '';
        $value = '';
        $arr = ['name', 'value'];
        foreach($arr as $key => $val){
            if(preg_match('/' . $val . '\s*=\s*("|\')\s*([^ ]+)\s*("|\')/i', $statement, $match)){
                $$val = trim($match[2]);
            }
        }
        if(substr($name, 0, 1) != '$'){
            $name = '$' . $name;
        }
        $name = $this->convert($name);
        if(!is_numeric($value)){
            $value = '\'' . $value . '\'';
        }
        return [$name, $value];
    }
    private function getForeach($statement)
    {
        $name = '';
        $item = '';
        $key = '';
        $arr = ['name', 'item', 'key'];
        foreach($arr as $key => $val){
            if(preg_match('/' . $val . '\s*=\s*("|\')\s*([^ ]+)\s*("|\')/i', $statement, $match)){
                $$val = trim($match[2]);
            }
        }
        if(substr($name, 0, 1) != '$'){
            $name = '$' . $name;
        }
        $name = $this->convert($name);
        $item = '$' . $item;
        if(!empty($key)){
            $key = '$' . $key;
        }
        $restr = '';
        if(empty($key)){
            $restr .= 'foreach(' . $name . ' as ' . $item . '){';
        }
        else{
            $restr .= 'foreach(' . $name . ' as ' . $key . ' => ' . $item . '){';
        }
        $restr = '<?php ' . $restr . ' ?>';
        return $restr;
    }
    private function getEach($statement)
    {
        $statement = preg_replace('/\s+/', ' ', $statement);
        $statement = preg_replace('/ *\= */', '=', $statement);
        $statearr = explode(' ', $statement);
        $item = array_shift($statearr);
        $in = '';
        $from = 0;
        $to = 0;
        $step = 1;
        $order = '$order';
        $mod = 0;
        $statearrlen = count($statearr);
        for($i = 0; $i < $statearrlen; $i += 2){
            switch($statearr[$i]){
                case 'in':
                    $in = $this->convert($statearr[$i + 1]);
                    break;
                case 'from':
                    $from = intval($statearr[$i + 1]);
                    break;
                case 'to':
                    $to = intval($statearr[$i + 1]);
                    break;
                case 'step':
                    $step = intval($statearr[$i + 1]);
                    break;
                case 'order':
                    $order = $statearr[$i + 1];
                    break;
                case 'mod':
                    $mod = intval($statearr[$i + 1]);
                    break;
            }
        }
        if($to > $from){
            $len = $to - $from;
        }
        else{
            $len = 0;
        }
        return $this->getFor($item, $in, $from, $len, $step, $order, md5($statement), $mod);
    }
    private function getVolist($statement)
    {
        $in = '';
        $item = '';
        $from = 0;
        $len = 0;
        $step = 1;
        $order = 'k';
        $mod = 0;
        $arr = [
            'name' => 'in',
            'id' => 'item',
            'offset' => 'from',
            'length' => 'len',
            'key' => 'order',
            'step' => 'step',
            'mod' => 'mod'
        ];
        foreach($arr as $key => $val){
            if(preg_match('/' . $key . '\s*=\s*("|\')\s*([^ ]+)\s*("|\')/i', $statement, $match)){
                $$val = trim($match[2]);
            }
        }
        if(substr($in, 0, 1) != '$'){
            $in = '$' . $in;
        }
        $in = $this->convert($in);
        $item = '$' . $item;
        $order = '$' . $order;
        $from = intval($from);
        if(!is_numeric($len)){
            if(substr($len, 0, 1) != '$'){
                $len = '$' . $len;
            }
            $len = $this->convert($len);
        }
        $step = intval($step);
        $mod = intval($mod);
        return $this->getFor($item, $in, $from, $len, $step, $order, md5($statement), $mod);
    }
    private function getFor($item, $in, $from, $len, $step, $order, $mark, $mod)
    {
        $restr = 'if(is_object(' . $in . ')){' . PHP_EOL;
        $restr .= '$arr_' . $mark . ' = ' . $in . '->items();' . PHP_EOL;
        $restr .= '$len_' . $mark . ' = count($arr_' . $mark . ');' . PHP_EOL;
        $restr .= '}else{' . PHP_EOL;
        $restr .= '$len_' . $mark . ' = count(' . $in . ');' . PHP_EOL;
        $restr .= '}' . PHP_EOL;
        if(!is_numeric($len) || $len > 0){
            $restr .= 'if($len_' . $mark . ' > ' . $len . ')
            $len_' . $mark . ' = ' . $len . ';' . PHP_EOL;
        }
        $itemmd = md5($item);
        $modttl = '$mod_' . $itemmd;
        if($mod > 0){
            $restr .= $modttl . ' = ' . $mod . ';' . PHP_EOL;
        }
        $itemd = '$item_' . $itemmd;
        $restr .= 'if(is_object(' . $in . ')){' . PHP_EOL;
        $restr .= $itemd . ' = array_values($arr_' . $mark . ');' . PHP_EOL;
        $restr .= '}else{' . PHP_EOL;
        $restr .= $itemd . ' = array_values(' . $in . ');' . PHP_EOL;
        $restr .= '}' . PHP_EOL;
        $this->foritem[$item] = $itemd . '[' . $item . ']';
        if($mod > 0){
            $restr .= 'for(' . $item . ' = ' . $from . ', ' . $order . ' = 1, $light_rail_mod = 0; ' . $item . ' < $len_' . $mark . '; ' . $item . ' += ' . $step . ', ' . $order . ' ++, $light_rail_mod ++){';
            $restr .= PHP_EOL . '$mod = $light_rail_mod % ' . $modttl . ';';
        }
        else{
            $restr .= 'for(' . $item . ' = ' . $from . ', ' . $order . ' = 1; ' . $item . ' < $len_' . $mark . '; ' . $item . ' += ' . $step . ', ' . $order . ' ++){';
        }
        $restr = '<?php ' . $restr . ' ?>';
        return $restr;
    }
    private function convert($statement)
    {
        if(count($this->foritem) > 0){
            foreach($this->foritem as $key => $val){
                $statement = str_replace($key . '.', $val . '.', $statement);
                if($statement == $key){
                    $statement = str_replace($key, $val, $statement);
                }
            }
        }
        $statement = preg_replace(['/ and /i', '/ or /i', '/ eq /i', '/ neq /i', '/ gt /i', '/ egt /i', '/ lt /i', '/ elt /i', '/ heq /i', '/ nheq /i', '/\.([A-Za-z][A-Za-z0-9_\-]*)/'], [' && ', ' || ', ' == ', ' != ', ' > ', ' >= ', ' < ', ' <= ', ' === ', ' !== ', '[\'$1\']'], $statement);
        return $statement;
    }
}