<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail\paginate;

class Bootstrap4
{
    public function getHtml($pagingArr, $containsul)
    {
        $html = '';
        if($containsul){
            $html .= '<ul class="pagination">';
        }
        foreach($pagingArr as $key => $val){
            $class = !empty($val['active']) ? ' active' : (!empty($val['disabled']) ? ' disabled' : '');
            $html .= '<li class="page-item'.$class.'"><a class="page-link" href="'.$val['url'].'">'.$val['page'].'</a></li>';
        }
        if($containsul){
            $html .= '</ul>';
        }
        return $html;
    }
}