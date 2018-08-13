<?php
namespace R\Lib\Table\Command;
use R\Lib\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use R\Lib\DBAL\DBSchemaDoctrine2;

class SchemaDiffCommand extends Command
{
    protected $name = 'schema:diff';
    protected $description = 'Diff DB-real-schema from Table-defined-schema';
    public function fire()
    {
        $apply = $this->option("apply");
        $ds_name = $this->option("ds");
        // 差分の取得
        $sqls = DBSchemaDoctrine2::getMigrateSql($ds_name, array(constant("R_APP_ROOT_DIR")."/app/Table"));
        // 差分の表示
        $config = app()->config("db.connection.".$ds_name);
        $this->line("-- ".count($sqls)." differences from ".$config["dbname"]." on ".$config["host"]);
        foreach ($sqls as $sql) $this->line($sql.";");
        // 差分の適応
        if ($sqls && $apply) {
            $db = app()->db($ds_name);
            $dump_dir = constant("R_APP_ROOT_DIR")."/tmp/dump";
            if ( ! file_exists($dump_dir)) mkdir($dump_dir, 0775);
            $dump_filename = $dump_dir."/".$ds_name.date("_Ymd-His").".sql.gz";
            $dump_result = $db->dumpData($dump_filename);
            if ( ! $dump_result) {
                report_error("DBのダンプデータが作成できませんでした", array(
                    "dump_filename" => $dump_filename,
                ));
            }
            $this->line("-- Backup: ".$dump_filename);
            $this->line("-- Apply start");
            foreach ($sqls as $sql) {
                $result = $db->exec($sql);
                if ( ! $result && $error = $db->getErrorInfo()) {
                    $this->line("ERROR : ".implode(' , ',$error));
                    $this->line("SKIP : ".$sql.";");
                }
            }
        }
    }
    protected function getOptions()
    {
        return array(
            array('ds', "-d", InputOption::VALUE_OPTIONAL, 'Datasource name.', "default"),
            array('apply', null, InputOption::VALUE_NONE, 'To apply to DB-real-schema.', null),
        );
    }
}
