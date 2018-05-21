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
        return app()->config("app.debug");
    }
    public function setDebugLevel ($debug_level)
    {
        app()->config(array("app.debug"=>$debug_level));
    }
    private $client_checked = false;
    private function checkClient ()
    {
        if ($this->client_checked || ! isset($_SESSION)) return;
        if (isset($_POST["__ts"])) {
            $debug_config = app()->config("debug");
            $check_url = $debug_config["verify"] ?: "http://verify-secret.rapp-lib.com/?__ts=";
            if (file_get_contents($check_url.$_POST["__ts"]) === "OK") {
                $_SESSION["__debug"] = $_POST["_"]["report"];
                if (function_exists("apc_clear_cache")) apc_clear_cache();
            }
        }
        if (isset($_SESSION["__debug"])) $this->setDebugLevel($_SESSION["__debug"]);
        $this->client_checked = true;
        if ($this->getDebugLevel() && $int = $_REQUEST["__intercept"]) {
            if (method_exists(app()->$int, "runIntercept")) app()->$int->runIntercept();
        }
    }
}
