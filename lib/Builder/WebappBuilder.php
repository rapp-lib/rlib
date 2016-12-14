<?php
namespace R\Lib\Builder;

use R\Lib\Builder\Element\SchemaElement;

/**
 *
 */
class WebappBuilder
{
    private static $instance = null;

    private $config = array();

    public static function getInstance ()
    {
        if ( ! static::$instance) {
            static::$instance = new WebappBuilder();
        }
        return static::$instance;
    }
    /**
     *
     */
    public function start ()
    {
        $schema_csv_file = $this->getConfig("schema_csv_file");
        $create_schema = new R\Lib\Builder\Regacy\CreateSchema;
        $schema = $create_schema->load_schema_csv($schema_csv_file);
        $deploy_files = new R\Lib\Builder\Regacy\DeployFiles;
        $deploy_files->deploy_files($schema);
    }
    /**
     * SchemaRegistryからSchemaElementを構築する
     */
    public function initSchemaFromRegistry ()
    {
        $this->schema = new SchemaElement();
        $controllers = (array)registry("Schema.controller");
        $tables = (array)registry("Schema.tables");
        $this->schema->loadFromSchema($controllers, $tables);
    }
    /**
     * 全ファイルを展開する
     */
    public function deployAll ()
    {
        // Roleに関わるファイルの展開
        foreach (builder()->getSchema()->getRole() as $role) {
            // ヘッダーHTMLファイルの展開
            $source = builder()->fetch("wrapper/default_header.html",array("role"=>$role));
            builder()->deploy("/html/include/".$role->getName()."_header.html", $source);
            // フッターHTMLファイルの展開
            $source = builder()->fetch("wrapper/default_footer.html",array("role"=>$role));
            builder()->deploy("/html/include/".$role->getName()."_footer.html", $source);
            // Roleクラスの展開
            $source = builder()->fetch("login/MemberRole.php",array("role"=>$role));
            builder()->deploy("/app/Role/".$role->getClassName().".php", $source);
        }
        // Tableに関わるファイルの展開
        foreach (builder()->getSchema()->getTable() as $table) {
            if ($table->hasDef()) {
                // Tableクラスの展開
                $source = builder()->fetch("table/MemberTable.php",array("table"=>$table));
                builder()->deploy("/app/Table/".$table->getClassName().".php", $source);
            }
        }
        // Enumに関わるファイルの展開
        foreach (builder()->getSchema()->getEnum() as $enum) {
            // Enumクラスの展開
            $source = builder()->fetch("table/MemberEnum.php",array("enum"=>$enum));
            builder()->deploy("/app/Enum/".$enum->getClassName().".php", $source);
        }
        // routing.config.phpの構築
        $source = builder()->fetch("config/routing.config.php",array("schema"=>builder()->getSchema()));
        builder()->deploy("/config/routing.config.php", $source);
        // Controllerに関わるファイルの展開
        foreach (builder()->getSchema()->getController() as $controller) {
            $source = builder()->fetch("master/ProductMasterController.class.php",
                array("controller"=>$controller));
            builder()->deploy("/app/Controller/".$controller->getClassName().".php", $source);
            // HTMLの構築
            foreach ($controller->getAction() as $action) {
                if ($action->getAttr("has_html")) {
                    $source = builder()->fetch("master/product_master.".$action->getName().".html",
                        array("action"=>$action));
                    builder()->deploy("/html".$action->getPath(), $source);
                }
            }
        }
    }
    /**
     * @getter
     */
    public function getSchema ()
    {
        return $this->schema;
    }
    /**
     * @setter
     */
    public function setConfig ($config)
    {
        foreach ($config as $key => $value) {
            $this->config[$key] = $value;
        }
    }
    /**
     * @getter
     */
    public function getConfig ($key)
    {
        if ($key == "template_dir") {
            return __DIR__."/../../assets/builder/skel";
        } elseif ($key == "current_dir") {
            return registry("Path.webapp_dir");
        } elseif ($key == "deploy_dir") {
            return registry("Path.webapp_dir");
        } elseif ($key == "schema_csv_file") {
            return registry("Path.webapp_dir")."/config/schema.config.csv";
        } elseif ($key == "dryrun") {
            return ! app()->config("Config.auto_deploy");
        } elseif ($key == "show_source") {
            return true;
        }
        if ( ! isset($this->config[$key])) {
            report_error("設定がありません",array(
                "key" => $key,
                "config" => $this->config,
            ));
        }
        return $this->config[$key];
    }
    /**
     * テンプレートファイルの読み込み
     */
    public function fetch ($template_name, $vars=array())
    {
        $template_file = $this->getConfig("template_dir")."/".$template_name;
        extract($vars,EXTR_REFS);
        ob_start();
        include($template_file);
        $source = ob_get_clean();
        $source = str_replace(array('<!?','<#?'),'<?',$source);
        return $source;
    }
    /**
     * ファイルの展開
     */
    public function deploy ($deploy_name, $source)
    {
        $current_file = $this->getConfig("current_dir")."/".$deploy_name;
        $deploy_file = $this->getConfig("deploy_dir")."/".$deploy_name;
        $status = "new";
        if (file_exists($current_file)) {
            $current_source = file_get_contents($current_file);
            $status = crc32($current_source)==crc32($source) ? "nochange" : "overwrite";
        }
        if ( ! $this->getConfig("dryrun")) {
            util("File")->write($deploy_file, $source);
        }
        report("ファイルの展開 ".$status." ".$deploy_name);
        if ($status != "nochange" && $this->getConfig("show_source")) {
            print '<pre>'.htmlspecialchars($source)."</pre>";
        }
    }
}
