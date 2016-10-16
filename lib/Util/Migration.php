<?php
namespace R\Util;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\DriverManager;
use R\Lib\Table\TableFactory;

/**
 *
 */
class Migration
{
    /**
     * Migrate結果のSQLを取得
     */
    public static function getMigrateSQL ($ds_name)
    {
        if ( ! $ds_name) {
            $ds_name = "default";
        }

        // 接続情報の解決
        $db_config = registry("DBAL.source.".$ds_name);

        // [Deprecate] Cake2向け接続情報の記述方法の変換
        if ( ! $db_config) {
            $db_config = registry("DBI.connection.".$ds_name);
            if ($db_config["driver"]) {
                $db_config["driver"] = "pdo_".$db_config["driver"];
            }
            if ($db_config["database"]) {
                $db_config["dbname"] = $db_config["database"];
                unset($db_config["database"]);
            }
        }

        $schema = new Schema;
        $tables = TableFactory::collectTables();
        foreach ($tables as $table) {
            $table_def = table($table)->getTableDef();
            // table_nameの指定がない、ds_nameが一致しないテーブルは対象外
            if ( ! $table_def["table_name"]
                || ($table_def["ds_name"] && $ds_name != $table_def["ds_name"])) {
                continue;
            }
            // Tableクラスの定義からTableSchemaの組み立て
            $table_schema = self::converTableDefToSchema($schema, $table_def);
        }

        // Schemaの比較
        $db = DriverManager::getConnection($db_config);
        $scm = $db->getSchemaManager();
        $queries = $schema->getMigrateFromSql($scm->createSchema(), $db->getDatabasePlatform());

        return $queries;
    }

    /**
     * DoctrineTableSchemaの取得
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
        $table->setPrimaryKey($id_col_names);

        // Indexの作成
        foreach ((array)$table_def["indexes"] as $index) {
            $table->addIndex((array)$index[0],$index[1],(array)$index[2],(array)$index[3]);
        }

        return $table;
    }
}