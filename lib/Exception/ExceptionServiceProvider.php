<?php
namespace R\Lib\Exception;
use Illuminate\Exception\ExceptionServiceProvider as IlluminateExceptionServiceProvider;
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
                $handler->addDataTableCallback("Error Context", function() {
                    $e = app('whoops.handler')->getInspector()->getException();
                    if ($e instanceof \R\Lib\Report\HandlableError) {
                        $values = $e->getParams();
                        unset($values["__"], $values["level"]);
                    }
                    return $values;
                });
                $handler->addDataTableCallback("Http Request URI", function(){
                    $uri = app("request")->getUri();
                    $values["uri_string"] = (string)$uri;
                    if (method_exists($uri, "__report")) {
                        $values["page_id"] = $uri->getPageId();
                        $uri_values = $uri->__report();
                        foreach ($uri_values["parsed"] as $k=>$v) $values[$k] = $v;
                    }
                    return $values;
                });
                $handler->setResourcesPath(constant("R_LIB_ROOT_DIR")."/assets/whoops");
                return $handler;
            }
        });
    }
}
