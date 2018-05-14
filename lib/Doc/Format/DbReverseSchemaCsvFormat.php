<?php
namespace R\Lib\Doc\Format;
use R\Lib\DBAL\DBSchemaDoctrine2;
use R\Lib\Doc\Content\SchemaCsv;

class DbReverseSchemaCsvFormat extends Format_Base
{
    public function getContents()
    {
        $contents = array();
        foreach ((array)app()->config("db.connection") as $ds_name=>$_) {
            $contents[$ds_name.".schema.config.csv"] = $this->getContent($ds_name);
        }
        return $contents;
    }
    private function getContent($ds_name)
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
        // SchemaCsv形式のデータを作成
        $content = new SchemaCsv($schema);
        $content->setHeader(array(
            "tables"=>array(
                "#tables", "table", "col", "label", "def.type", "type",
                "def.notnull", "def.length", "def.default", "def.fkey_for",
                "def.assoc", "other",
            ),
            "pages"=>array(
                "#pages", "controller", "label", "type",
            ),
        ));
        return $content;
    }
}
