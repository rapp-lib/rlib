<?php
namespace R\Lib\Table\Query;

class Statement
{
    protected $sql;
    protected $query;
    public function __construct($sql, $query)
    {
        $this->sql = $sql;
        $this->query = $query;
    }
    public function getQuery()
    {
        return $this->query;
    }
    public function getSql()
    {
        return $this->sql;
    }
    public function __toString()
    {
        return $this->getSql();
    }
}
