<?php
namespace R\Lib\Exception;
use Illuminate\Exception\ExceptionServiceProvider as IlluminateExceptionServiceProvider;
use Whoops\Handler\PrettyPageHandler;

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
        $this->app['whoops.handler'] = $this->app->share(function(){
            $handler = new PrettyPageHandler;
            $handler->setEditor('sublime');
            $handler->setPageTitle('Application Error.');
            $handler->addDataTableCallback("ERROR Data", function(){
                return array();
            });
            return $handler;
        });
    }
    protected function requestWantsJson()
    {
        return false;
        //return app()->http->isAjax() || app()->http->wantsJson();
    }
}
