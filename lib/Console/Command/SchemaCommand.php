<?php
namespace R\Lib\Console\Command;

use R\Lib\Console\Command;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\DriverManager;

class SchemaCommand extends Command
{
    public function act_diff ()
    {
        $ds_name = $this->console["ds"];
        if ( ! $ds_name) {
            $ds_name = "default";
        }
        // Tableの定義からDoctrineSchemaオブジェクトを構築
        $schema = new Schema;
        foreach ($this->collectTables() as $table) {
            $table_def = app()->table->getDef($table);
            // table_nameの指定がない、ds_nameが一致しないテーブルは対象外
            if ( ! $table_def["table_name"] || ($table_def["ds_name"] && $ds_name != $table_def["ds_name"])) {
                continue;
            }
            // Tableクラスの定義からTableSchemaの組み立て
            $this->converTableDefToSchema($schema, $table_def);
        }
        // Schemaの比較
        $db = $this->getConnection($ds_name);
        $scm = $db->getSchemaManager();
        $queries = $schema->getMigrateFromSql($scm->createSchema(), $db->getDatabasePlatform());
        // SQL出力
        foreach ($queries as $statement) {
            $this->console->output($statement.";\n\n");
        }
    }
    /**
     * DoctrineDB接続の取得
     */
    private function getConnection ($ds_name)
    {
        $db_config = app()->config("db.connection.".$ds_name);
        if ($db_config["driver"]) {
            $db_config["driver"] = "pdo_".$db_config["driver"];
        }
        if ($db_config["database"]) {
            $db_config["dbname"] = $db_config["database"];
            unset($db_config["database"]);
        }
        if ($db_config["login"]) {
            $db_config["user"] = $db_config["login"];
            unset($db_config["login"]);
        }
        $db = DriverManager::getConnection($db_config);
        return $db;
    }
    /**
     * DoctrineTableSchemaへのテーブル追加
     */
    private function converTableDefToSchema ($schema, $table_def)
    {
        $table = $schema->createTable($table_def["table_name"]);
        $id_col_names = array();
        foreach ((array)$table_def["cols"] as $col_name => $col) {
            $options = $col;
            // カラムの型
            $col_type = $options["type"];
            unset($options["type"]);
            // 型の指定のないカラムは定義されていないものと見なす
            if ( ! $col_type) {
                continue;
            }
            // idが指定されたカラムは主キー
            if ($col["id"]) {
                $id_col_names[] = $col_name;
            }
            // notnulが標準でtrueなので反転
            $options["notnull"] = (bool)$options["notnull"];
            $table->addColumn($col_name, $col["type"], $options);
        }
        // 主キー
        if ($id_col_names) {
            $table->setPrimaryKey($id_col_names);
        }
        // Indexの作成
        foreach ((array)$table_def["indexes"] as $index) {
            $table->addIndex((array)$index["cols"],$index["name"],(array)$index["flags"],(array)$index["options"]);
        }
        return $table;
    }
    /**
     * Tableクラスを全て取得
     */
    private function collectTables ()
    {
        $tables = array();
        $classes = util("ClassFinder")->findClassInNamespace("R\\App\\Table\\");
        foreach ($classes as $i => $class) {
            if (preg_match('!^R\\\\App\\\\Table\\\\([a-zA-Z0-9]+)Table!',$class,$match)) {
                $tables[] = $match[1];
            }
        }
        return $tables;
    }
}
