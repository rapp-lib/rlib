<?php
namespace R\Lib\DBAL;

class SQLStatement
{
    private $query;
    private $sql_string = null;
    public function __construct($query)
    {
        $this->query = $query;
    }
    public function __toString()
    {
        return $this->render();
    }
    public function render()
    {
        if ($this->sql_string === null) {
            $this->sql_string = "";
        }
        return $this->sql_string;
    }
}
