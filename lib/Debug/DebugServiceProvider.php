<?php
namespace R\Lib\Debug;
use Barryvdh\Debugbar\ServiceProvider as IlluminateDebugServiceProvider;
use Barryvdh\Debugbar\Console;

class DebugServiceProvider extends IlluminateDebugServiceProvider
{
    public function register()
    {
        $this->app['debugbar'] = $this->app->share(function($app){
            return new Debugbar($app);
        });
        if ( ! $this->app->debug->getDebugLevel()) return;
        // Loggerに関連付け
        $this->app->singleton("debug.logging_handler", "R\Lib\Debug\LoggingHandler");
        $this->app["log"]->getMonolog()->pushHandler($this->app["debug.logging_handler"]);
        // Http Global Injection
        if ( ! $this->app->runningInConsole()) {
            $this->app->config->push("http.global.response_filters", function($response){
                $response = app('debugbar')->modifyResponse($request, $response);
                return $response;
            });
            $this->app->config["http.global.controller_class.debugbar"] = 'R\Lib\Debug\DebugbarController';
            $routes = array(
                array("debugbar.open", "/.devel/debugbar/open"),
                array("debugbar.assets_css", "/.devel/debugbar/assets/stylesheets"),
                array("debugbar.assets_js", "/.devel/debugbar/assets/javascript"),
            );
            foreach ($routes as $route) $this->app->config->push("http.global.routes", $route);
        }
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
        }
        if ($this->app['config']["debug.enabled"]) {
            $this->app["debugbar"]->boot();
        }
    }
}
