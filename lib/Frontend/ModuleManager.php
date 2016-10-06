<?php
namespace R\Lib\Frontend;

/*
    CONCEPT:
        $js = 'alert($("#msg").text());';
        frontend()->appendScriptCode($js)
            ->required("jquery","2.*")
            ->wrap("onready");
    CONCEPT:
        frontend()->addScriptModule("jquery", "2.2.4", '//code.jquery.com/jquery-2.2.4.min.js');
    CONCEPT:
        frontend()->addScriptModule("mi", "1.0.0", frontend()->getAssetUrl("lib").'/js_rui/rui.mi/index.js')
            ->required("rui","*");
        frontend()->addScriptModule("mi", "2.0.0", frontend()->getAssetUrl("lib").'/js_rui/jquery.mi/index.js')
            ->required("jquery","2.*");
    CONCEPT:
        print '<script src="//code.jquery.com/jquery-2.2.4.min.js"></script>';
        frontend()->markLoadedJsModule("jquery","2.2.4");
    CONCEPT:
        print frontend()->flushCode("js");
 */

/**
 *
 */
class FrontendResourceManager
{
    private static $resource_manager = null;

    private $requires = array();
    private $modules = array();
    private $codes = array();
    private $asset_urls = array();

    /**
     *
     */
    public static function load ()
    {
        if ( ! self::$resource_manager) {
            self::$resource_manager = new FrontendResourceManager();
        }
        return self::$resource_manager;
    }

    public function setAssetUrl ($asset_group_name, $url)
    {
        $this->asset_urls[$asset_group_name] = $url;
    }
    public function getAssetUrl ($asset_group_name)
    {
        return $this->asset_urls[$asset_group_name];
    }
    public function appendScriptCode ($data)
    {
        $code = new Code($this,$data);
        array_push($this->codes["script"], $code);
        $code->setType("script_code");
        return $code;
    }
    public function addScriptModule ($module_name, $version, $data)
    {
        $module = new JsModule($this, $module_name, $version, $data);
        array_push($this->modules, $module);
        $module->setType("script_url");
        return $module;
    }

    public function required ($module_name, $require_version="*")
    {
        //TODO: 要求Version解析の実装
        //TODO: ModuleLoaderクラスの読み込みの実装
        $this->markLoadedJsModule($module_name, $version_name);
    }
    public function markLoadedJsModule ($module_name, $version_name)
    {

    }
    public function flushCode ($code_type)
    {
        //TODO: 依存解決
    }
}
// Module+Code=Resource registerModule -> Buffer
class Module
{
    private $resource_manager;
    private $module_name;
    private $versions;
    public function __construct ($resource_manager, $module_name, $versions) {
        $this->resource_manager = $resource_manager;
        $this->module_name = $module_name;
        $this->versions = $versions;
    }
    public function required ($module_name, $require_version="*") {
        return $resource_manager->require($module_name, $require_version);

    }
}
class Code
{
    private $resource_manager;
    private $code_src;
    public function __construct ($resource_manager, $code_src) {
        $this->resource_manager = $resource_manager;
        $this->code_src = $code_src;
    }
    public function required ($module_name, $require_version="*") {
        return $resource_manager->require($module_name, $require_version);
    }
    public function wrap ($wrap_type) {}
}
class ResourceFile {
}