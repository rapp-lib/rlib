<?php
namespace R\Lib\Debug;
use Zend\Cache\StorageFactory;

class DebugSession
{
    protected $name = "DEVSESSID";
    protected $verify_secret_url = "http://verify-secret.rapp-lib.com/?__ts=";
    protected $ttl = 604800;
    public function __construct()
    {
        if ($this->isActive() && $this->get("debug.dev_client")) {
            app("debug")->setDebugLevel($this->get("debug.enabled"));
        }
        if (isset($_POST["__ts"]) && file_get_contents($this->verify_secret_url.$_POST["__ts"]) === "OK") {
            $debug_enabled = (boolean)$_POST["_"]["report"];
            app("debug")->setDebugLevel($debug_enabled);
            $this->put("debug.dev_client", true);
            $this->put("debug.client_enabled", $debug_enabled);
        }
    }

// -- activate

    public function isActive()
    {
        return strlen($_COOKIE[$this->name]) ? true : false;
    }
    public function getId()
    {
        return $this->isActive() ? $_COOKIE[$this->name] : '';
    }
    public function activate()
    {
        if ( ! $this->isActive()) {
            $id = str_random(16);
            $_COOKIE[$this->name] = $id;
            setcookie($this->name, $id, time() + $this->ttl);
        }
    }

// -- storage

    protected $storage = null;
    protected function getStorage()
    {
        if ( ! $this->storage) {
            $cache_config = array(
                'adapter' => array(
                    'name'    => 'filesystem',
                    'options' => array(
                        'cache_dir' => constant("R_APP_ROOT_DIR")."/tmp/debug",
                        'dir_level' => 3,
                        'dir_permission' => 0777,
                        'file_permission' => 0777,
                        'ttl' => $this->ttl,
                    ),
                ),
                'plugins' => array(
                    'Serializer',
                    'exception_handler' => array('throw_exceptions' => false),
                ),
            );
            $this->storage = StorageFactory::factory($cache_config);
        }
    }
    public function put($key, $value)
    {
        $this->getStorage()->putItem($key, $value);
    }
    public function get($key)
    {
        $this->getStorage()->getItem($key);
    }
}
