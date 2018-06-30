<?php
namespace R\Lib\Core;
use DebugBar\Storage\FileStorage;

class Debug
{
    public function __invoke ()
    {
        return $this->getDebugLevel();
    }
    public function getDebugLevel ()
    {
        return app()->config["app.debug"];
    }
    public function setDebugLevel ($debug_level)
    {
        app()->config["app.debug"] = $debug_level;
        //app("exception")->setDebug($debug_level);
    }

    protected $name = "DSESSID";
    protected $ttl = 604800;
    protected $verify_secret_url = "http://verify-secret.rapp-lib.com/?__ts=";
    protected $storage = null;
    public function check()
    {
        // Consoleの場合自動的にapp.debug有効化
        if (app()->runningInConsole()) {
            app()->config["app.debug"] = true;
        // 新規クライアント設定
        } elseif (isset($_POST["__ts"]) && file_get_contents($this->verify_secret_url.$_POST["__ts"])==="OK") {
            $debug_enabled = (boolean)$_POST["_"]["report"];
            app()->config["app.debug"] = $debug_enabled;
            $this->getStorage()->save($this->getId(), array(
                "debug.dev_client"=>true,
                "debug.enabled"=>$debug_enabled,
            ));
        // クライアント設定済みの場合
        } elseif ($this->isStarted()) {
            $data = $this->getStorage()->get($this->getId());
            if ($data["debug.dev_client"]) {
                app("debug")->setDebugLevel($data["debug.enabled"]);
            }
        }
    }
    protected function isStarted()
    {
        return strlen($_COOKIE[$this->name]) ? true : false;
    }
    protected function start()
    {
        if ( ! $this->isStarted()) {
            $id = str_random(16);
            $_COOKIE[$this->name] = $id;
            setcookie($this->name, $id, time() + $this->ttl, "/");
        }
    }
    protected function getId()
    {
        return $this->isStarted() ? $_COOKIE[$this->name] : '';
    }
    protected function getStorage()
    {
        $this->start();
        if ( ! $this->storage) {
            $session_dir = constant("R_APP_ROOT_DIR")."/tmp/debug/session";
            $this->storage = new FileStorage($session_dir);
        }
        return $this->storage;
    }
}
