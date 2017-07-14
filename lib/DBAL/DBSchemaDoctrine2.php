<?php
namespace R\Lib\DBAL;
use Doctrine\DBAL\Schema\Schema;

class DBSchemaDoctrine2
{
    /**
     * 対象のDB接続先について、Tableでの定義との差分をMigrateするSQLを取得する
     */
    public static function getMigrateSql($ds_name, $table_dirs)
    {
        // Doctrine2ベースでの実行準備
        $db = new DBConnectionDoctrine2($ds_name, app()->config("db.connection.".$ds_name));
        $doctrine = $db->getDoctrineConnection();
        // Tableクラスの定義取得
        $table_defs = app()->table->collectTableDefs($table_dirs);
        $class_schema = self::getSchemaFromTableDefs($table_defs);
        // DB上の定義取得
        $db_schema = $doctrine->getSchemaManager()->createSchema();
        // 差分抽出
        return $class_schema->getMigrateFromSql($db_schema, $doctrine->getDatabasePlatform());
    }
    /**
     * Table定義からSchemaを作成する
     */
    public static function getSchemaFromTableDefs($defs)
    {
        $schema = new Schema;
        foreach ($defs as $def) {
            $table = $schema->createTable($def["table_name"]);
            $id_col_names = array();
            foreach ((array)$def["cols"] as $col_name => $col) {
                $options = $col;
                // 型の指定のないカラムは定義されていないものと見なす
                if ( ! $options["type"]) continue;
                // idが指定されたカラムは主キーに登録
                if ($col["id"]) $id_col_names[] = $col_name;
                // notnulが標準でtrueなので反転
                $options["notnull"] = (bool)$options["notnull"];
                // 型は別途指定
                unset($options["type"]);
                $table->addColumn($col_name, $col["type"], $options);
            }
            // 主キーの登録
            if ($id_col_names) $table->setPrimaryKey($id_col_names);
            // Indexの作成
            foreach ((array)$def["indexes"] as $index) {
                $table->addIndex($index["cols"], $index["name"], (array)$index["flags"], (array)$index["options"]);
            }
        }
        return $schema;
    }
}
