<?php
namespace R\Lib\Session;
use Zend\Session\SessionManager as ZendSessionManager;
use Zend\Session\Container as ZendSessionContainer;
use Zend\Session\Config\SessionConfig as ZendSessionConfig;
use Zend\Session\Storage\SessionArrayStorage as ZendSessionArrayStorage;

class SessionDriver extends ZendSessionManager
{
    public function __invoke($container_name)
    {
        return $this->getContainer($container_name);
    }
    protected $config;
    public function __construct()
    {
        $this->config = app()->config("session.manager.default");
        $session_config = null;
        if ($this->config['config']) {
            $session_config = new ZendSessionConfig();
            $session_config->setOptions($this->config['config']['options'] ?: array());
        }
        $session_storage = null;
        // class should be fetched from service manager since it will require constructor arguments
        $session_save_handler = null;
        if ($this->config['save_handler']) {
            $class = $this->config['save_handler']['class'];
            $session_save_handler = new $class();
        }
        parent::__construct($session_config, $session_storage, $session_save_handler);
        ZendSessionContainer::setDefaultManager($this);
    }

// --

    protected $containers = array();
    public function getContainer($container_name)
    {
        if ( ! $this->containers[$container_name]) {
            $this->containers[$container_name] = new SessionContainer($container_name, $this);
        }
        return $this->containers[$container_name];
    }

// --

    protected $flash = null;
    public function getFlash()
    {
        if ( ! $this->flash) $this->flash = new FlashMessageQueue();
        return $this->flash;
    }
}
