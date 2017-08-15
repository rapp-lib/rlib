<?php
namespace R\Lib\Console\Command;
use R\Lib\DBAL\DBSchemaDoctrine2;

class SchemaCommand extends \R\Lib\Console\Command
{
    public function act_diff()
    {
        $apply = isset($this->console["apply"]);
        $ds_name = $this->console["ds"] ?: "default";
        $sqls = DBSchemaDoctrine2::getMigrateSql($ds_name, array(constant("R_APP_ROOT_DIR")."/app/Table"));
        if ($sqls) {
            foreach ($sqls as $sql) app()->console->output($sql.";\n\n");
            // 差分の適応
            if ($apply) {
                $dump_dir = constant("R_APP_ROOT_DIR")."/tmp/dump";
                if ( ! file_exists($dump_dir)) mkdir($dump_dir, 0775);
                $dump_filename = $dump_dir."/".$ds_name.date("_Ymd-His").".sql.gz";
                $dump_result = app()->db($ds_name)->dumpData($dump_filename);
                if ( ! $dump_result) {
                    report_error("DBのダンプデータが作成できませんでした", array(
                        "dump_filename" => $dump_filename,
                    ));
                }
                app()->console->output("* Backup: ".$dump_filename."\n\n");
                app()->console->output("* Apply"."\n\n");
                app()->db($ds_name)->exec(implode('; ', $sqls));
            }
        }
    }
}
