<?php
namespace R\Lib\Farm\Command;
use R\Lib\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use R\Lib\Farm\FarmEngine;

class FarmPublishCommand extends Command
{
    protected $name = 'farm:publish';
    protected $description = 'Create Farm commit';
    protected function getOptions()
    {
        return array(
            array('config', "-c", InputOption::VALUE_REQUIRED, 'Config file path.'),
            array('test', "-t", InputOption::VALUE_NONE, 'Run without merge.'),
            array('flags', "-f", InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Free flag options.'),
        );
    }
    public function fire()
    {
        // 設定ファイルの読み込み
        $config_path = $this->option("config") ?: "devel/builder/farm_config.php";
        $config_file = constant("R_APP_ROOT_DIR")."/".$config_path;
        if ( ! file_exists($config_file)) {
            report_error("Farm configファイルがありません", array("config_file"=>$config_file));
        }
        $config = include($config_file);
        $config["flags"] = $this->option("flags");

        // 展開処理
        $farm = new FarmEngine($config);
        $farm->prepare();
        call_user_func($config["build_callback"], $farm);
        $farm->apply();
        if ( ! $this->option("test")) $farm->merge();
    }
}
