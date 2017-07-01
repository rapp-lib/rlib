<?php
namespace R\Lib\Core;

class Debug
{
    public function __invoke ()
    {
        return $this->getDebugLevel();
    }
    private $debug_level = false;
    public function getDebugLevel ()
    {
        $this->sessionCheck();
        return $this->debug_level;
    }
    public function setDebugLevel ($debug_level)
    {
        $this->session_checked = true;
        $this->debug_level = $debug_level;
    }
    private $session_checked = false;
    private function sessionCheck ()
    {
        if ($this->session_checked || ! isset($_SESSION)) {
            return;
        }
        if (app()->util("ServerVars")->ipCheck(app()->config("debug.dev_cidr") ?: "0.0.0.0/0")) {
            if (isset($_POST["__ts"]) && isset($_POST["_"])) {
                for ($min = floor(time()/60), $i=-5; $i<=5; $i++) {
                    if ($_POST["__ts"] == substr(md5("_/".($min+$i)),12,12)) {
                        $_SESSION["__debug"] = $_POST["_"]["report"];
                        if (function_exists("apc_clear_cache")) {
                            apc_clear_cache();
                        }
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
