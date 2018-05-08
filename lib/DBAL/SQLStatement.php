<?php
namespace R\Lib\DBAL;

class SQLStatement
{
    protected $sql_string;
    public function __construct($sql_string)
    {
        $this->sql_string = $sql_string;
    }
    public function __toString()
    {
        return $this->sql_string;
    }
    public function logStart()
    {
    }
    public function logEnd($error=null)
    {
    }
}
