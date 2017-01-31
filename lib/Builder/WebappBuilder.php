<?php
namespace R\Lib\Builder;

use R\Lib\Core\Contract\Provider;
use R\Lib\Builder\Element\SchemaElement;

class WebappBuilder extends SchemaElement implements Provider
{
    protected $config = array();
    public function __construct ()
    {
        $skel_dir = constant("R_LIB_ROOT_DIR")."/assets/builder/skel";
        $current_dir = constant("R_APP_ROOT_DIR");
        $work_dir = constant("R_APP_ROOT_DIR")."/tmp/builder/work-".date("Ymd-his");
        //$deploy_dir = app()->config("builder.overwrite") ? $current_dir : $work_dir."/deploy";
        $deploy_dir = constant("R_APP_ROOT_DIR")."/tmp/builder/test-01";
        $schema_csv_file = constant("R_APP_ROOT_DIR")."/config/schema.config.csv";
        array_add($this->config, array(
            "current_dir" => $current_dir,
            "work_dir" => $work_dir,
            "deploy_dir" => $deploy_dir,
            "schema_csv_file" => $schema_csv_file,
            "dryrun" => false,
            "show_source" => true,
        ));
        $this->addSkel($skel_dir);
    }
    /**
     * Configの取得
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
        array_add($this->config, $config);
    }
    /**
     * 所定のCSVを読み込んで記載されているSchema全体をdeploy
     */
    public function start ()
    {
        // CSV読み込み
        $this->initFromSchemaCsv($this->getConfig("schema_csv_file"));
        // Schema全体をdeploy
        $this->deploy(true);
    }
    /**
     * @override
     */
    public function getElementType ()
    {
        return "schema";
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
        try {
            extract($vars,EXTR_REFS);
            include($template_file);
        } catch (R\Lib\Core\Exception\ResponseException $e) {
            ob_end_clean();
            report_buffer_end();
            throw $e;
        }
        $source = ob_get_clean();
        $source = str_replace(array('<!?','<#?'),'<?',$source);
        report_buffer_end();
        // ファイルの配置
        if ($deploy) {
            $this->deploySource($deploy, $source);
        }
        return $source;
    }
    /**
     * ファイルの展開
     */
    protected function deploySource ($deploy_name, $source)
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
}
