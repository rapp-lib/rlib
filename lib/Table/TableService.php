<?php
namespace R\Lib\Table;
use R\Lib\Core\ServiceProvider;

class TableService extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('table', '\R\Lib\Table\TableFactory');
        $this->app->bind('schema:diff', '\R\Lib\Table\Command\SchemaDiffCommand');
    }
    public function boot()
    {
        $this->commands(array(
            'schema:diff',
        ));
    }
}
