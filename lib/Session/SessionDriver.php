<?php
namespace R\Lib\Session;

class SessionDriver
{
    private $managers = array();
    public function __invoke($container_name, $ds_name="default")
    {
        return $this->getManager($ds_name)->getContainer($container_name);
    }
    public function __call($func, $args)
    {
        return call_user_func_array(array($this->getManager("default"), $func), $args);
    }
    public function getManager($ds_name="default")
    {
        if ( ! $this->managers[$ds_name]) {
            $cache_config = app()->config("session.manager.".$ds_name);
            $this->managers[$ds_name] = new SessionManager($cache_config);
            if ($ds_name=="default") {
                $this->managers[$ds_name]->setDefaultManager();
            }
        }
        return $this->managers[$ds_name];
    }
}
