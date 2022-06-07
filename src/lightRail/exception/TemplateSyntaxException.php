<?php
/**
 * Project: lightRail.
 * Author: A.J <804644245@qq.com>
 * Copyright: A.J <804644245@qq.com> All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/light-rail-app
 */
namespace lightRail\exception;

use RuntimeException;

class TemplateSyntaxException extends RuntimeException
{
    public function __construct($message, $previous = null)
    {
        $this->message = $message;
        parent::__construct($message, 0, $previous);
    }
}