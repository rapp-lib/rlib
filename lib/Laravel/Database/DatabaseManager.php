<?php
namespace R\Lib\Laravel\Database;

use Illuminate\Database\DatabaseManager as IlluminateDatabaseManager;

class DatabaseManager extends IlluminateDatabaseManager
{
    public function __construct()
    {
        $this->app = app();
        $this->factory = app("db.factory");
        parent::__construct($this->app, $this->factory);
    }
    protected $_connections = array();
    public function getConnection($name=null)
    {
        if ($name===null || $name==="default") {
            $name = $this->getDefaultConnection();
        }
        if ( ! $this->_connections[$name]) {
            $conn = $this->connection($name);
            $doctrine = $conn->getDoctrineConnection();
            $config = app()->config["database.connections.".$name];
            $ds_name = $name===$this->getDefaultConnection() ? "default" : $name;
            $this->_connections[$name] = new DBConnectionDoctrine2($ds_name, array(
                "dbname"=>$config["database"],
                "host"=>$config["host"],
                "port"=>$config["port"],
                "user"=>$config["username"],
                "password"=>$config["password"],
                "driver"=>$doctrine->getDriver()->getName(),
                "ds"=>$doctrine,
            ));
        }
        return $this->_connections[$name];
    }
}
