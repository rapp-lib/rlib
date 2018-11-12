<?php
namespace R\Lib\Table\Query;

class Builder
{
    protected $query;
    public function __construct($query)
    {
        $this->query = $query;
    }
    public function __call($method_name, $args)
    {
        array_unshift($args, $this);
        return app("table.features")->call("chain", $method_name, $args);
    }
    public function getQuery()
    {
        return $this->query;
    }
    public function getDef()
    {
        return $this->query->getDef();
    }
    public function __report()
    {
        return $this->query->__report();
    }
}
