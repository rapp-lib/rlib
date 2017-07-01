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
        $this->checkClient();
        return $this->debug_level;
    }
    public function setDebugLevel ($debug_level)
    {
        $this->debug_level = $debug_level;
    }
    private $client_checked = false;
    private function checkClient ()
    {
        if ($this->client_checked || ! isset($_SESSION)) {
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
        }
        if (isset($_SESSION["__debug"])) {
            $this->setDebugLevel($_SESSION["__debug"]);
        }
        $this->client_checked = true;
    }
}
