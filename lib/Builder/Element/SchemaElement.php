<?php
namespace R\Lib\Builder\Element;

use R\Lib\Builder\SchemaCsvLoader;
use R\Lib\Builder\Element\Element_Base;

class SchemaElement extends Element_Base
{
    /**
     * @override
     */
    public function getSchema ()
    {
        return $this;
    }
    /**
     * @getter Controllers
     */
    public function getControllers ()
    {
        return (array)$this->children["controller"];
    }
    public function getControllerByName ($name)
    {
        return $this->children["controller"][$name];
    }
    /**
     * @getter Table
     */
    public function getTables ()
    {
        return (array)$this->children["table"];
    }
    public function getTableByName ($name)
    {
        return $this->children["table"][$name];
    }
    /**
     * @getter Roles
     */
    public function getRoles ()
    {
        return (array)$this->children["role"];
    }
    public function getRoleByName ($name)
    {
        return $this->children["role"][$name];
    }

// --

    /**
     *
     */
    public function __construct ()
    {
    }
    /**
     * CSVファイルを解析したデータを読み込む
     */
    public function loadSchemaData ($schema)
    {
        $controllers = $schema["controller"];
        $tables = $schema["tables"];
        $cols = $schema["cols"];
        // Role登録
        foreach ($controllers as $controller_name => $controller_attrs) {
            $role_name = $controller_attrs["access_as"];
            $role_attrs = array();
            $this->children["role"][$role_name] = new RoleElement($role_name, $role_attrs, $this);
        }
        // Table登録
        foreach ($tables as $table_name => $table_attrs) {
            $table_attrs["cols"] = (array)$cols[$table_name];
            $this->children["table"][$table_name] = new TableElement($table_name, $table_attrs, $this);
        }
        // Controller登録
        foreach ($controllers as $controller_name => $controller_attrs) {
            $this->children["controller"][$controller_name] = new ControllerElement($controller_name, $controller_attrs, $this);
        }
    }

    protected $config = array();
    /**
     * Skel（Configセット）の配置ディレクトリを追加
     */
    public function addSkel ($skel_dir)
    {
        $config_file = $skel_dir."/.build_skel.php";
        if ( ! file_exists($config_file)) {
            report_error("設定ファイルがありません",array(
                "config_file" => $config_file,
            ));
        }
        $config = (array)include($config_file);
        \R\Lib\Util\Arr::array_add($this->config, $config);
    }
    /**
     * Configの取得
     */
    public function getConfig ($key)
    {
        if ( ! \R\Lib\Util\Arr::array_isset($this->config, $key)) {
            report_error("設定がありません",array(
                "key" => $key,
                "config" => $this->config,
            ));
        }
        return \R\Lib\Util\Arr::array_get($this->config, $key);
    }

    protected $deploy_callbacks = array();
    /**
     * ファイル展開処理の登録
     */
    public function registerDeployCallback ($deploy_callback)
    {
        $this->deploy_callbacks[] = $deploy_callback;
    }
    /**
     * テンプレートファイルの読み込み
     */
    public function fetch ($config_entry, $vars=array(), $deploy=false)
    {
        // if ( ! ini_get("short_open_tag")) {
        //     report_error("short_open_tag=On設定が必須です");
        // }
        $template_file = $this->getConfig($config_entry.".template_file");
        if ( ! file_exists($template_file)) {
            report_error("テンプレートファイルが読み込めません",array(
                "template_file" => $template_file,
                "config_entry" => $config_entry,
            ));
        }
        // テンプレートファイルの読み込み
        ob_start();
        try {
            extract($vars,EXTR_REFS);
            include($template_file);
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
        $source = ob_get_clean();
        $source = str_replace(array('<!?','<#?'),'<?',$source);
        // ファイルの配置
        if ($deploy) $this->deploySource($deploy, $source);
        return $source;
    }
    /**
     * ファイルの展開
     */
    protected function deploySource ($deploy_name, $source)
    {
        foreach ((array)$this->deploy_callbacks as $deploy_callback) {
            call_user_func($deploy_callback, $deploy_name, $source, $config_entry, $vars);
        }
    }
}
