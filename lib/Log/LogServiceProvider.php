<?php
namespace R\Lib\Log;
use Illuminate\Log\LogServiceProvider as IlluminateLogServiceProvider;

class LogServiceProvider extends IlluminateLogServiceProvider
{
    public function register ()
    {
        parent::register();
        $this->app['events']->listen("app.handle_exception", function($exception){
            // Report\HandlableErrorの処理
            if ($exception instanceof \R\Lib\Report\HandlableError) {
                $message = $exception->getMessage();
                $params = $exception->getParams();
                $params["__"]["category"] = "Error";
                $level = $params["level"];
                app("log")->getMonolog()->log($level, $message, $params);
            }
        });
    }
}