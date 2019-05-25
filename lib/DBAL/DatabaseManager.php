<?php
namespace R\Lib\DBAL;
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
            $this->_connections[$name] = new DBConnectionDoctrine2($doctrine, array(
                "dbname"=>$conn->getConfig('database'),
                "driver"=>$conn->getDoctrineDriver()->getName(),
            ));
        }
        return $this->_connections[$name];
    }
}
