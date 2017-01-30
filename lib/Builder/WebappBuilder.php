<?php
namespace R\Lib\Builder;

use R\Lib\Contract\Provider;
use R\Lib\Builder\Element\SchemaElement;

class WebappBuilder extends SchemaElement implements Provider
{
    private $config = array();
    public function __construct ()
    {
        $dir = constant("R_LIB_ROOT_DIR")."/assets/builder/skel";
        $current_dir = constant("R_APP_ROOT_DIR");
        $work_dir = constant("R_APP_ROOT_DIR")."/tmp/builder/work-".date("Ymd-his");
        $deploy_dir = app()->config("builder.overwrite") ? $current_dir : $work_dir."/deploy";
        $schema_csv_file = constant("R_APP_ROOT_DIR")."/config/schema.config.csv";
        $this->config = array(
            "template_dir" => $dir,
            "current_dir" => $current_dir,
            "work_dir" => $work_dir,
            "deploy_dir" => $deploy_dir,
            "schema_csv_file" => $schema_csv_file,
            "dryrun" => false,
            "show_source" => true,
            "pageset.blank" => array(
                "index_page" => "blank",
                "controller.template_file" => $dir."/pageset/blank/controller.php",
                "pages.blank.template_file" => $dir."/pageset/blank/blank.html",
            ),
            "pageset.login" => array(
                "index_page" => "form",
                "controller.template_file" => $dir."/pageset/login/controller.php",
                "pages.form.template_file" => $dir."/pageset/login/form.html",
                "pages.logout.template_file" => null,// $dir."/pageset/login/logout.html",
            ),
            "pageset.show" => array(
                "index_page" => "list",
                "controller.template_file" => $dir."/pageset/show/controller.php",
                "pages.list.template_file" => $dir."/pageset/show/list.html",
                "pages.detail.template_file" => $dir."/pageset/show/detail.html",
            ),
            "pageset.form" => array(
                "index_page" => "form",
                "controller.template_file" => $dir."/pageset/form/controller.php",
                "pages.form.template_file" => $dir."/pageset/form/form.html",
                "pages.confirm.template_file" => $dir."/pageset/master/confirm.html",
                "pages.complete.template_file" => $dir."/pageset/master/complete.html",
            ),
            "pageset.delete" => array(
                "index_page" => "delete",
                "controller.template_file" => $dir."/pageset/change/controller.php",
                "pages.delete.template_file" => null,// $dir."/pageset/change/delete.html",
            ),
            "pageset.csv_import" => array(
                "index_page" => "form",
                "controller.template_file" => $dir."/pageset/csv_import/controller.php",
                "pages.form.template_file" => $dir."/pageset/csv_import/form.html",
                "pages.confirm.template_file" => null,// $dir."/pageset/csv_import/confirm.html",
                "pages.complete.template_file" => null,// $dir."/pageset/csv_import/complete.html",
            ),
            "pageset.csv_export" => array(
                "index_page" => "download",
                "controller.template_file" => $dir."/pageset/csv_export/controller.php",
                "pages.download.template_file" => null,// $dir."/pageset/csv_export/download.html",
            ),
            "include_html" => array(
                "header.template_file" => $dir."/include_html/header.html",
                "footer.template_file" => $dir."/include_html/footer.html",
            ),
            "classes" => array(
                "role_controller.template_file" => $dir."/classes/RoleControllerClass.php",
                "controller.template_file" => $dir."/classes/ControllerClass.php",
                "table.template_file" => $dir."/classes/TableClass.php",
                "enum.template_file" => $dir."/classes/EnumClass.php",
                "role.template_file" => $dir."/classes/RoleClass.php",
            ),
            "config" => array(
                "routing.template_file" => $dir."/config/routing.config.php",
            ),
            "frame" => array(
                "page.template_file" => $dir."/frame/page.html",
            ),
        );
    }
    /**
     *
     */
    public function start ()
    {
        // CSV読み込み
        $this->initFromSchemaCsv($this->getConfig("schema_csv_file"));
        // routing.config
        builder()->fetch("config.routing", array("schema"=>$this),
            "/config/routing.config.php");
        // Tableに関わるファイルの展開
        foreach (builder()->getTables() as $table) {
            if ($table->hasDef()) {
                // Tableクラス
                builder()->fetch("classes.table", array("table"=>$table),
                    "/app/Table/".$table->getClassName().".php");
            }
            if ($table->getEnum()) {
                // Enumクラス
                builder()->fetch("classes.enum", array("table"=>$table),
                    "/app/Enum/".$table->getEnum()->getClassName().".php");
            }
        }
        // Roleに関わるファイル
        foreach (builder()->getRoles() as $role) {
            // ヘッダー/フッターHTMLファイル
            builder()->fetch("include_html.header", array("role"=>$role),
                "/html/".$role->getHeaderPath());
            builder()->fetch("include_html.footer", array("role"=>$role),
                "/html/".$role->getFooterPath());
            // Roleクラス
            builder()->fetch("classes.role", array("role"=>$role),
                "/app/Role/".$role->getClassName().".php");
            // RoleControllerクラス
            builder()->fetch("classes.role_controller", array("role"=>$role),
                "/app/Controller/".$role->getRollControllerClassName().".php");
        }
        // Controllerに関わるファイル
        foreach (builder()->getControllers() as $controller) {
            // Controllerクラス
            builder()->fetch("classes.controller", array("controller"=>$controller),
                "/app/Enum/".$table->getClassName().".php");
            foreach ($controller->getPagesets() as $pageset) {
                foreach ($pageset as $page) {
                    if ($page->hasHtml()) {
                        // pageのHtmlファイル
                        builder()->fetch("frame.page", array("page"=>$page),
                            "/html/".$page->getPath());
                    }
                }
            }
        }
    }
    /**
     * @getter
     */
    public function getSchema ()
    {
        report("@deprecated");
        return $this;
    }
    /**
     * @getter
     */
    public function getConfig ($key)
    {
        if ( ! array_isset($this->config, $key)) {
            report_error("設定がありません",array(
                "key" => $key,
            ));
        }
        return array_get($this->config, $key);
    }
    /**
     * テンプレートファイルの読み込み
     */
    public function fetch ($config_entry, $vars=array(), $deploy=false)
    {
        if ( ! ini_get("short_open_tag")) {
            report_error("short_open_tag=On設定が必須です");
        }
        $template_file = $this->getConfig($config_entry.".template_file");
        if ( ! file_exists($template_file)) {
            report_error("テンプレートファイルが読み込めません",array(
                "template_file" => $template_file,
                "config_entry" => $config_entry,
            ));
        }
        // テンプレートファイルの読み込み
        report_buffer_start();
        ob_start();
        extract($vars,EXTR_REFS);
        include($template_file);
        $source = ob_get_clean();
        $source = str_replace(array('<!?','<#?'),'<?',$source);
        report_buffer_end();
        // ファイルの配置
        if ($deploy) {
            $this->deploy($deploy, $source);
        }
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
        report("Deploy ".$status." ".$deploy_name);
        if ($status != "nochange" && $this->getConfig("show_source")) {
            print '<pre>'.htmlspecialchars($source)."</pre>";
        }
    }
    /**
     * @deprecated
     */
    public function filter_fields ($fields, $type)
    {
        report_warning("@deprecated");
        $fields = (array)$fields;
        foreach ($fields as $tc_name => $tc) {
            if ($type == "search"
                    && ($tc["type"] != "textarea"
                    && $tc["type"] != "text")) {
                unset($fields[$tc_name]);
            }
            if ($type == "sort"
                    && ($tc["type"] == "textarea"
                    || $tc["type"] == "file"
                    || $tc["type"] == "password")) {
                unset($fields[$tc_name]);
            }
            if ($type == "list"
                    && ($tc["type"] == "textarea"
                    || $tc["type"] == "file"
                    || $tc["type"] == "password")) {
                unset($fields[$tc_name]);
            }
            if ($type == "save"
                    && (false)) {
                unset($fields[$tc_name]);
            }
        }
        if ($type == "search" || $type == "sort") {
            $fields =array_slice($fields,0,3);
        }
        if ($type == "list") {
            $fields =array_slice($fields,0,6);
        }
        return $fields;
    }
}
