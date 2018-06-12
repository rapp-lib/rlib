<?php
namespace R\Lib\Log;
use Illuminate\Log\LogServiceProvider as IlluminateLogServiceProvider;

class LogServiceProvider extends IlluminateLogServiceProvider
{
    public function register ()
    {
        parent::register();
    }
}