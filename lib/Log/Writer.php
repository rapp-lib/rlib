<?php
namespace R\Lib\Log;

use Illuminate\Log\Writer as IlluminateLogWriter;

class Writer extends IlluminateLogWriter
{
    public function write()
    {
        $level = head(func_get_args());

        return call_user_func_array(array($this, $level), array_slice(func_get_args(), 1));
    }
}
