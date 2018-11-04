<?php
namespace R\Lib\Table\Query;

class Builder
{
    protected $query;
    public function __construct($table_name)
    {
        $this->query = app()->make("table.query_payload", array($table_name));
    }
    public function __call($method_name, $args)
    {
        array_unshift($args, $this->query);
        return app("table.features")->call("chain", $method_name, $args);
    }
    public function getQuery()
    {
        return $this->query;
    }
}
