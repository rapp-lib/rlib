<?php
namespace R\Lib\Test;
use R\Lib\Core\ServiceProvider;

class TestServiceProvider extends ServiceProvider
{
    public function register ()
    {
        if ( ! $this->app->debug->getDebugLevel()) return;
        if ( ! $this->app->runningInConsole()) {
            $this->app->config["http.global.controller_class._tester"] = 'R\Lib\Test\TesterController';
            $routes = array(
                array("_tester.sandbox", "/.devel/sandbox/"),
            );
            foreach ($routes as $route) $this->app->config->push("http.global.routes", $route);
        }
    }
}
