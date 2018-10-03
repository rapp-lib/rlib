<?php
namespace R\Lib\View;
use Illuminate\View\ViewServiceProvider as IlluminateViewServiceProvider;
use Illuminate\View\Factory;
use Illuminate\View\Engines\EngineResolver;
use R\Lib\View\Engines\SmartyEngine;

class ViewServiceProvider extends IlluminateViewServiceProvider
{
    public function register ()
    {
        $this->app->singleton("view.smarty", function($app){
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
		$this->app->bindShared('view.engine.resolver', function(){
            $resolver = new EngineResolver;
            $this->registerPhpEngine($resolver);
            $this->registerBladeEngine($resolver);
            $resolver->register('smarty', function() { return new SmartyEngine; });
            $resolver->register('smarty_mail', function() { return new SmartyEngine; });
            return $resolver;
        });
		$this->app->bindShared('view.finder', function($app){
			return new FileViewFinder($app['files']);
		});
		$this->app->bindShared('view', function($app)
		{
			$resolver = $app['view.engine.resolver'];
			$finder = $app['view.finder'];
			$factory = new Factory($resolver, $finder, $app['events']);
			$factory->setContainer($app);
			$factory->share('app', $app);
			$factory->addExtension("html", "smarty");
			$factory->addExtension("mail", "smarty_mail");
			return $factory;
		});
    }
}
