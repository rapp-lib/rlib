<?php
namespace R\Lib\DBAL;

class DBDriver
{
    public function __invoke($ds_name="default")
    {
        return $this->getConnection($ds_name);
    }
    private $connections = array();
    public function getConnection($ds_name="default")
    {
        if ( ! $this->connections[$ds_name]) {
            $config = app()->config("db.connection.".$ds_name);
            $class = $config["class"] ?: 'R\Lib\DBAL\DBConnectionDoctrine2';
            $this->connections[$ds_name] = new $class($ds_name, $config);
        }
        return $this->connections[$ds_name];
    }
}
