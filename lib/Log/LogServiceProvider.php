<?php
namespace R\Lib\Log;
use Illuminate\Log\LogServiceProvider as IlluminateLogServiceProvider;

class LogServiceProvider extends IlluminateLogServiceProvider
{
    public function register ()
    {
        parent::register();
        app("log")->getMonolog()->pushHandler(app("report")->getLoggingHandler());
        //app("report")->getLoggingHandler();
        //app("report")->raiseError($msg, $params, $type);
        //app("report")->captureError($e);
        //$response = app("report")->modifyResponse($response);
        //app("report")->beforeShutdown();
    }
}