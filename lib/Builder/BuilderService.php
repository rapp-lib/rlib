<?php
namespace R\Lib\Builder;
use R\Lib\Core\ServiceProvider;

class BuilderService extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('builder', '\R\Lib\Builder\WebappBuilder');
        $this->app->bind('build:make', '\R\Lib\Builder\Command\BuildMakeCommand');
    }
    public function boot()
    {
        $this->commands(array(
            'build:make',
        ));
    }
}
