<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail;

class Errors
{
    private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
        @set_error_handler([$this, 'error_handler']);
        @set_exception_handler([$this, 'exception_handler']);
    }
    public function exception_handler($exception)
    {
        if(Config::get('missed') == '404'){
            Url::redirect('index/Index/_empty');
        }
        else{
            ob_clean();
            if(Config::get('debug') == false){
                App::instance()->jump->exception(Lang::get('Wrong'));
            }
            else{
                if($this->request->isAjax()){
                    echo Lang::get('Uncaught exception'). ': ' . $exception->getMessage();
                }
                else{
                    echo '<div style="padding: 1.5rem;background-color: lightgoldenrodyellow">' . Lang::get('Uncaught exception'). ': ' . $exception->getMessage() . '</div>';
                }
            }
            ob_end_flush();
        }
        exit();
    }
    public function error_handler($errno, $errstr, $errfile, $errline)
    {
        $this->log(Lang::get('Error message'). ': ' . $errstr . '[' . Lang::get('Error'). ': ' . $errno . '] ' . Lang::get('File location'). ': ' . $errfile . '[' . Lang::get('Line'). ': ' . $errline . '] ' . Lang::get('Execution time'). ': ' . date("Y-m-d h:i:sa") . PHP_EOL);
        if(!(error_reporting() & $errno)){
            return false;
        }
        ob_clean();
        $intercept = strlen(ROOT_PATH);
        if($this->request->isAjax()){
            echo htmlspecialchars($errstr) . ' [' . Lang::get('Error'). ': ' . $errno . '] ' . substr($errfile, $intercept) . ' [' . Lang::get('Line'). ': ' . $errline . ']';
        }
        else{
            echo '<div style="padding: 1.5rem;background-color: lightgoldenrodyellow"><h4>' . htmlspecialchars($errstr) . ' [' . Lang::get('Error'). ': ' . $errno . ']</h4><div>' . substr($errfile, $intercept) . ' [' . Lang::get('Line'). ': ' . $errline . ']</div></div>';
            $file = file($errfile);
            echo '<div style="margin-top: 1rem; padding: 10px; border: solid 1px #eee">';
            foreach($file as $key => $val){
                $line = $key + 1;
                if($line < $errline - 10 || $line > $errline + 10){
                    unset($file[$key]);
                }
                else{
                    if($line == $errline){
                        echo '<div style="color: firebrick">[' . $line . '] ' . str_replace(' ', '&nbsp;', htmlspecialchars($val)) . '</div>';
                    }
                    else{
                        echo '<div style="color: grey">[' . $line . '] ' . str_replace(' ', '&nbsp;', htmlspecialchars($val)) . '</div>';
                    }
                }
            }
            echo '</div>';
        }
        ob_end_flush();
        exit();
    }
    private function log($message)
    {
        $errPath = ROOT_PATH . 'runtime' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . date('Ym');
        if(!is_dir($errPath)){
            @mkdir($errPath, 0777, true);
        }
        error_log($message, 3, $errPath . DIRECTORY_SEPARATOR . date('d') . '.log');
    }
}