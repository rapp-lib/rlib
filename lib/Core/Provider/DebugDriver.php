<?php
namespace R\Lib\Core\Provider;

use R\Lib\Core\Contract\InvokableProvider;

class DebugDriver implements InvokableProvider
{
    /**
     * @override InvokableProvider
     */
    public function invoke ()
    {
        return $this->getDebugLevel();
    }
    protected $debug_level = false;
    protected $session_checked = false;
    protected $config_checked = false;
    public function getDebugLevel ()
    {
        $this->configCheck();
        $this->sessionCheck();
        return $this->debug_level;
    }
    public function setDebugLevel ($debug_level)
    {
        $this->debug_level = $debug_level;
    }
    private function configCheck ()
    {
        if ($this->config_checked) {
            return;
        }
        $debug_level = app()->config("debug.level");
        if (isset($debug_level)) {
            $this->debug_level = $debug_level;
            $this->config_checked = true;
        }
    }
    private function sessionCheck ()
    {
        if ($this->session_checked) {
            return;
        }
        if (app()->hasProvider("session") && app()->session && ! app()->session->isStarted()) {
            $this->session_checked = true;
        }
        if (app()->util("ServerVars")->ipCheck(app()->config("debug.dev_cidr"))) {
            if (isset($_POST["__ts"]) && isset($_POST["_"])) {
                for ($min = floor(time()/60), $i=-5; $i<=5; $i++) {
                    if ($_POST["__ts"] == substr(md5("_/".($min+$i)),12,12)) {
                        $_SESSION["__debug"] = $_POST["_"]["report"];
                    }
                }
            }
            if (isset($_SESSION["__debug"])) {
                $this->debug_level = $_SESSION["__debug"];
            }
        }
        $this->session_checked = true;
    }
}