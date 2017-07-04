<?php
namespace R\Lib\Session;
use Zend\Session\SessionManager as ZendSessionManager;
use Zend\Session\Container as ZendSessionContainer;

class SessionManager
{
    private $config;
    private $ds;
    private $containers = array();
    public function __construct($config)
    {
        $this->config = $config;
        $session_config = null;
        if ($this->config['config']) {
            $class = $this->config['config']['class'] ?: 'Zend\Session\Config\SessionConfig';
            $options = $this->config['config']['options'] ?: array();
            $session_config = new $class();
            $session_config->setOptions($options);
        }
        $session_storage = null;
        if ($this->config['storage']) {
            $class = $this->config['storage']['class'];
            $session_storage = new $class();
        }
        // class should be fetched from service manager since it will require constructor arguments
        $session_save_handler = null;
        if ($this->config['save_handler']) {
            $class = $this->config['save_handler']['class'];
            $session_save_handler = new $class();
        }
        $this->ds = new ZendSessionManager($session_config, $session_storage, $session_save_handler);
    }
    public function setDefaultManager()
    {
        ZendSessionContainer::setDefaultManager($this->ds);
    }
    public function __call($func, $args)
    {
        if ( ! is_callable(array($this->ds, $func))) {
            report_error("SessionDatasourceのメソッドが呼び出せません", array(
                "ds" => $this->ds,
                "func" => $func,
            ));
        }
        return call_user_func_array(array($this->ds, $func), $args);
    }
    public function getDs()
    {
        return $this->ds;
    }
    public function getContainer($container_name)
    {
        if ( ! $this->containers[$container_name]) {
            $this->containers[$container_name] = new ZendSessionContainer($container_name);
        }
        return $this->containers[$container_name];
    }
}
