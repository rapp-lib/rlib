<?php
namespace R\Lib\Exception;
use Illuminate\Exception\ExceptionServiceProvider as IlluminateExceptionServiceProvider;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\CallbackHandler;

class ExceptionServiceProvider extends IlluminateExceptionServiceProvider
{
    protected function registerHandler()
    {
        $this->app['exception'] = $this->app->share(function($app){
            return new Handler($app, $app['exception.plain'], $app['exception.debug']);
        });
    }
    protected function registerPlainDisplayer()
    {
        $this->app['exception.plain'] = $this->app->share(function($app){
            if ($app->runningInConsole()) return $app['exception.debug'];
            else return new PlainDisplayer;
        });
    }
    protected function registerDebugDisplayer()
    {
        $this->registerWhoops();
        $this->app['exception.debug'] = $this->app->share(function($app){
            return new WhoopsDisplayer($app['whoops'], $app->runningInConsole());
        });
    }
    protected function registerPrettyWhoopsHandler()
    {
        //
    }
    protected function registerWhoopsHandler()
    {
        $this->app['whoops.handler'] = $this->app->share(function(){
            if ($this->app->runningInConsole()) {
                return new CallbackHandler(function($e, $inspection, $whoops){
                });
            } elseif (app("request.fallback")->isAjax() || app("request.fallback")->wantsJson()) {
                return new JsonResponseHandler;
            } else {
                $handler = new PrettyPageHandler;
                $handler->setEditor('sublime');
                $handler->setPageTitle('Application Error.');
                $handler->addDataTableCallback("ERROR Data", function(){
                    return array();
                });
                return $handler;
            }
        });
    }
}
