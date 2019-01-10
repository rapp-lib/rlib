<?php
namespace R\Lib\Table;
use R\Lib\Core\ServiceProvider;

class TableServiceProvider extends ServiceProvider
{
    public function register()
    {
        $ns = '\R\Lib\Table';
        $this->app->singleton("tables", $ns.'\Def\TableDefCollection');
        $this->app->singleton("table.def_resolver", $ns.'\Def\TableDefResolver');
        $this->app->singleton("table.relation_resolver", $ns.'\Def\TableRelationResolver');
        $this->app->bind("table.def", $ns.'\Def\TableDef');
        $this->app->bind("table.query_builder", $ns.'\Query\Builder');
        $this->app->bind("table.query_payload", $ns.'\Query\Payload');
        $this->app->bind("table.query_pager", $ns.'\Query\Pager');
        $this->app->bind("table.query_statement", $ns.'\Query\Statement');
        $this->app->bind("table.query_result", $ns.'\Query\Result');
        $this->app->bind("table.query_record", $ns.'\Query\Record');
        $this->app->bind("table.query_result_pager", $ns.'\Query\ResultPager');
        $this->app->bind("table.query_executer", $ns.'\Query\Executer');
        $this->app->singleton("table.features", $ns.'\Feature\FeatureCollection');
        $this->app->bind("table.feature_std_provider", $ns.'\Feature\StdFeatureProvider');
    }
    public function boot()
    {
        $provider = $this->app["table.feature_std_provider"];
        $this->app["table.features"]->registerProvider($provider);
    }
}
