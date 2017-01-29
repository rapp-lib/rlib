<?php
namespace R\Lib\Util;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\DriverManager;

/**
 *
 */
class Migration
{
    /**
     * schemaコマンドの実行
     */
    public static function execSchemaCommand ()
    {
        try {
            $params = get_cli_params();
            foreach (util("Migration")->getMigrateSQL($params["ds"]) as $statement) {
                print $statement.";\n\n";
            }
        } catch (R\Lib\Core\Exception\ResponseException $e) {
            print "error\n";
        } catch (\Exception $e) {
            print "# ".$e->getMessage()."\n";
            print "error\n";
        }
    }
    /**
     * Migrate結果のSQLを取得
     */
    public static function getMigrateSQL ($ds_name)
    {
        if ( ! $ds_name) {
            $ds_name = "default";
        }
        $db_config = null;
        // [Deprecate] Cake2向け接続情報の記述方法の変換
        if ( ! $db_config) {
            $db_config = registry("db.connection.".$ds_name);
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
        }

        $tables = self::collectTables();
        $schema = new Schema;
        foreach ($tables as $table) {
            $table_def = app()->table->getDef($table);
            // table_nameの指定がない、ds_nameが一致しないテーブルは対象外
            if ( ! $table_def["table_name"] || ($table_def["ds_name"] && $ds_name != $table_def["ds_name"])) {
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
    private static function collectTables ()
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