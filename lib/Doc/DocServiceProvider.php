<?php
namespace R\Lib\Doc;
use R\Lib\Core\ServiceProvider;

class DocServiceProvider extends ServiceProvider
{
    public function register ()
    {
        if ( ! $this->app->debug->getDebugLevel()) return;
        if ( ! $this->app->runningInConsole()) {
            $this->app->config["http.global.controller_class._docs"] = 'R\Lib\Doc\DocsController';
            $routes = array(
                array("_docs.index", "/.devel/docs/"),
            );
            foreach ($routes as $route) $this->app->config->push("http.global.routes", $route);
        }
    }
}
