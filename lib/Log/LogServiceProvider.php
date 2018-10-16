<?php
namespace R\Lib\Log;
use Monolog\Logger;

use Illuminate\Log\LogServiceProvider as IlluminateLogServiceProvider;

class LogServiceProvider extends IlluminateLogServiceProvider
{
    public function register()
    {
        $logger = new Writer(
            new Logger($this->app['env']), $this->app['events']
        );

        $this->app->instance('log', $logger);

        if (isset($this->app['log.setup']))
        {
            call_user_func($this->app['log.setup'], $logger);
        }
    }
}
