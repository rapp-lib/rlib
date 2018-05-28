<?php
namespace R\Lib\Debug;
use Barryvdh\Debugbar\ServiceProvider as IlluminateDebugServiceProvider;
use Barryvdh\Debugbar\Console;

class DebugServiceProvider extends IlluminateDebugServiceProvider
{
    public function register()
    {
        $this->app->alias(
            'DebugBar\DataFormatter\DataFormatter',
            'DebugBar\DataFormatter\DataFormatterInterface'
        );
        $this->app['debugbar'] = $this->app->share(function($app){
            return new Debugbar($app);
        });
    }
    public function boot()
    {
        if ( ! $this->app->debug->getDebugLevel()) return;
        if ($this->app->runningInConsole()) {
            if ($this->app->config["debug.capture_console"] && method_exists($this->app, 'shutdown')) {
                $this->app->shutdown(function ($app) {
                    app('debugbar')->collectConsole();
                });
            } else {
                $this->app['config']["debug.enabled"] = false;
            }
            $this->app['command.debugbar.publish'] = $this->app->share(function ($app) {
                return new Console\PublishCommand();
            });
            $this->app['command.debugbar.clear'] = $this->app->share(function ($app) {
                return new Console\ClearCommand($app['debugbar']);
            });
            $this->commands(array('command.debugbar.publish', 'command.debugbar.clear'));
        } else {
            $this->app->config["http.global.middlewares.200"] = function($request, $next){
                $response = $next($request);
                return app('debugbar')->modifyResponse($request, $response);
            };
            $this->app->config["http.global.controller_class.debugbar"] = 'R\Lib\Debug\DebugbarController';
            $routes = (array)$this->app->config["http.global.routes"];
            $this->app->config["http.global.routes"] = array_merge($routes, array(
                array("debugbar.open", "/_debugbar/open"),
                array("debugbar.assets_css", "/_debugbar/assets/stylesheets"),
                array("debugbar.assets_js", "/_debugbar/assets/javascript"),
            ));
        }
        if ($this->app['config']["debug.enabled"]) {
            $this->app["debugbar"]->boot();
        }
    }
}
