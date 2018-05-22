<?php
namespace R\Lib\Session;
use Zend\Session\SessionManager as ZendSessionManager;
use Zend\Session\Container as ZendSessionContainer;
use Zend\Session\Config\SessionConfig as ZendSessionConfig;
use Zend\Session\Storage\SessionArrayStorage as ZendSessionArrayStorage;
use Zend\Session\SaveHandler\Cache as ZendSaveHandlerCache;

class SessionDriver extends ZendSessionManager
{
    public function __invoke($container_name)
    {
        return $this->getContainer($container_name);
    }
    protected $config;
    public function __construct()
    {
        $this->config = app()->config("session");
        // session_configの展開
        $session_config = new ZendSessionConfig();
        $session_config->setOptions($this->config['config']['options'] ?: array());
        $session_storage = new ZendSessionArrayStorage();
        // session_save_handlerの展開
        $session_save_handler = null;
        if ($save_handler = $this->config['save_handler']) {
            if (is_array($save_handler) && $cache_name = $save_handler["cache"]) {
                $cache_ds = app()->cache($cache_name)->getDs();
                $session_save_handler = new ZendSaveHandlerCache($cache_ds);
            } elseif (is_callable($save_handler)) {
                $session_save_handler = call_user_func($save_handler);
            } else {
                report_error("Session save_handlerの指定が不正です", array("save_handler"=>$save_handler));
            }
        }
        parent::__construct($session_config, $session_storage, $session_save_handler);
        SessionContainer::setDefaultManager($this);
    }
    public function handle($request, $next)
    {
        $this->start();
        app()->debug->getDebugLevel();
        return $next($request);
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
