<?php
namespace R\Lib\Farm\Command;
use R\Lib\Console\Command;
use R\Lib\Farm\FarmEngine;

class FarmPublishCommand extends Command
{
    protected $name = 'farm:publish';
    protected $description = 'Create Farm commit';
    protected function getOptions()
    {
        return array(
            // array('ds', "-d", InputOption::VALUE_OPTIONAL, 'Datasource name.', "default"),
            // array('apply', null, InputOption::VALUE_NONE, 'To apply to DB-real-schema.', null),
        );
    }
    public function fire()
    {
        //$config = include(constant("R_APP_ROOT_DIR")."/devel/farm/publish.config.php");
        // # パラメータ定義
        $config = array(
            //"app_root_dir" => constant("R_APP_ROOT_DIR"),
            "app_root_dir" => "/var/www/vhosts/d.fiar.jp/expert-staff/rapp_test/tmp/farm_test",
            "farm_dirname" => "devel/farm",
            "develop_branch" => "develop",
            "farm_branch" => "farm/build",
            "farm_mark" => array("-m", "<FARM>"),
            "farm_mark_find" => array("--grep=", "<FARM>"),
            "builder_callback" => function($farm){
                $farm_dir = $farm->getConfig("app_root_dir")."/".$farm->getConfig("farm_dirname");
                $schema_csv_file = $farm_dir."/schema.config.csv";
                $skel_dir = $farm_dir."/skel";

                // CSV読み込み
                $schema_loader = new \R\Lib\Builder\SchemaCsvLoader;
                $schema_data = $schema_loader->load($schema_csv_file);
                // SchemaElementを作成
                $schema = new \R\Lib\Builder\Element\SchemaElement();
                $schema->addSkel($skel_dir);
                $schema->loadSchemaData($schema_data);
                // 自動生成の実行
                $schema->registerDeployCallback(function($deploy_name, $source){
                    $deploy_file = constant("R_APP_ROOT_DIR")."/".$deploy_name;
                    $status = "create";
                    if (file_exists($deploy_file)) {
                        $current_source = file_get_contents($deploy_file);
                        $status = crc32($current_source)==crc32($source) ? "nochange" : "modify";
                    }
                    // \R\Lib\Util\File::write($deploy_file, $source);
                    if ($status != "nochange") {
                        print "Deploy ".$status." ".$deploy_name."\n";
                    }
                });
                $schema->deploy(true);
            },
        );

        $farm = new FarmEngine($config);
        $farm->checkState();
        $farm->prepareFarmBranch();
        $farm->resetFarmBranchCopy();
        $farm->prepareFarmDir();
        call_user_func($config["builder_callback"], $farm);
        $farm->cleanFarmDir();
        $farm->mergeFarmBranch();
        $farm->cleanup();
    }
}
