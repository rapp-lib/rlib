<?php
namespace R\Lib\Doc\Format;
use R\Lib\DBAL\DBSchemaDoctrine2;
use R\Lib\Doc\Content\SchemaCsv;

class DbReverseSchemaCsvFormat
{
    public function writeAll($prefix)
    {
        $files = array();
        foreach ((array)app()->config("db.connection") as $ds_name=>$_) {
            $files[] = $file = $prefix."/".$ds_name.".schema.config.csv";
            $this->writeDs($file, $ds_name);
        }
        return $files;
    }
    private function writeDs($file, $ds_name)
    {
        // DB接続先から定義を読み込む
        $db_schema = DBSchemaDoctrine2::getDbSchema($ds_name);
        $defs = DBSchemaDoctrine2::getTableDefsFromSchema($db_schema);
        // SchemaCsv形式に変換する
        $schema = array();
        foreach ($defs as $table_name=>$table) {
            $_table = array();
            if ($table["comment"]) $_table["label"] = $table["comment"];
            foreach ($table["cols"] as $col_name=>$col) {
                $_col = array();
                if ($col["comment"]) {
                    $_col["label"] = $col["comment"];
                    unset($col["comment"]);
                }
                $_col["type"] = "text";
                $_col["def"] = $col;
                $_table["cols"][$col_name] = $_col;
            }
            $schema["tables"][$table_name] = $_table;
        }
        $this->touchFile($file);
        // SchemaCsv形式のデータをファイルに書き込む
        $content = new SchemaCsv($schema);
        $content->write($file);
    }
    public function touchFile($file)
    {
        $dir = dirname($file);
        if ( ! is_dir($dir)) mkdir($dir, 0777, true);
        touch($file);
        chmod($file, 0777);
        return $file;
    }
}
