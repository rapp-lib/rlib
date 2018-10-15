<?php
namespace R\Lib\Debug;
use Barryvdh\Debugbar\ServiceProvider as IlluminateDebugServiceProvider;
use Barryvdh\Debugbar\Console;

class DebugServiceProvider extends IlluminateDebugServiceProvider
{
    public function register()
    {
        $this->app->singleton('debugbar', function($app){
            return new Debugbar($app);
        });

        if ( ! $this->app->debug->getDebugLevel()) return;

        // メモリ消費量の計測
        $this->app->singleton('memory_usage', "R\Lib\Debug\MemoryUsageTracer");
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
        // ReportCollectorの登録
        $this->app["debugbar"]->addCollector(new DataCollector\ReportCollector());
        // EventCollectorの登録
        $start_time = defined('LARAVEL_START') ? LARAVEL_START : null;
        $event_collector = new DataCollector\EventCollector($start_time);
        $this->app["debugbar"]->addCollector($event_collector);
        $this->app['events']->subscribe($event_collector);
        $this->app['events']->listen("app.handle_exception", function($e){
            if ( ! $e instanceof \R\Lib\Report\HandlableError) {
                $e = \R\Lib\Report\ReportRenderer::createHandlableError(array("exception"=>$e));
            }
            $message = $e->getMessage();
            $params = $e->getParams();
            $level = $params["level"];
            app("log")->getMonolog()->log($level, $message, $params);
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
        }
        if ($this->app['config']["debug.enabled"]) {
            $this->app["debugbar"]->boot();
        }
    }
}
