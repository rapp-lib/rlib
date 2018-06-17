<?php
namespace R\Lib\View;
use R\Lib\Core\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function register ()
    {
        $this->app->singleton("view", function($app){
            $class = 'R\App\View\View_App';
            if ( ! class_exists($class)) $class = 'R\Lib\View\SmartyView';
            return new $class;
        });
        $this->app->singleton("view.assets", function($app){
            $assets = new FrontAssets();
            $repo_uri = $app["request"]->getUri()->getWebroot()->uri("path://.assets");
            $assets->addRepo($repo_uri);
            return $assets;
        });
    }
}
