<?php
namespace R\Lib\Analyzer;

class AppDesc
{
    private $config;
    public function __construct()
    {
        $this->config = include(constant("R_APP_ROOT_DIR")."/.analyze.php");
    }
    public function getAnalyzeConfig($name)
    {
        return array_get($this->config, $name);
    }
    public function getWebrootDir($webroot_name)
    {
    }
    public function getFileDescs()
    {
    }
    public function getIgnoreFiles()
    {
    }
    public function getAppDir($webroot_name)
    {
    }
    public function getShelfDir($webroot_name)
    {
    }
}
