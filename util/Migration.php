<?php
namespace R\Util;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\DriverManager;

/**
 *
 */
class Migration
{
    /**
     * ディレクトリ以下で定義されているテーブル一覧を取得
     */
    public static function searchTableInDir ($schema_paths)
    {
        $tables = array();
        foreach ((array)$schema_paths as $schema_path) {
            foreach (glob($schema_path."/*") as $f) {
                if (preg_match('!^([0-9a-zA-Z_]+)(Table)\.php!',basename($f),$match)) {
                    $table_name = $match[1];
                    $tables[] = table($table_name);
                }
            }
        }
        return $tables;
    }

    /**
     * Migrate結果のSQLを取得
     */
    public static function getMigrateSQL ($ds_name, $tables)
    {
        // 接続情報の解決
        $db_config = registry("DB.source.".$ds_name);

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

        // Tableクラスファイルの読み込み
        $schema = new Schema;
        foreach ((array)$tables as $table) {
            $table->getTableSchema($schema);
        }

        // Schemaの比較
        $db = DriverManager::getConnection($db_config);
        $scm = $db->getSchemaManager();
        $queries = $schema->getMigrateFromSql($scm->createSchema(), $db->getDatabasePlatform());

        // 差分SQLの組み立て
        $sql = "";
        foreach ($queries as $q) {
            $sql .=$q."\n";
        }

        return $sql;
    }
}