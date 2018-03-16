<?php
namespace R\Lib\DBAL;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaException;

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
        // DB上の定義取得
        $db_schema = $doctrine->getSchemaManager()->createSchema();
        // Tableクラスの定義取得
        $table_defs = app()->table->collectTableDefs($table_dirs);
        $class_schema = self::getSchemaFromTableDefs($table_defs, $db_schema);
        // 差分抽出
        return $class_schema->getMigrateFromSql($db_schema, $doctrine->getDatabasePlatform());
    }
    /**
     * Table定義からSchemaを作成する
     */
    public static function getSchemaFromTableDefs($defs, $db_schema)
    {
        // Class上の定義を反映しない指定がある場合、DB上の定義をコピー
        $ignore_tables = array();
        foreach ($defs as $i=>$def) {
            if ($def["ignore_schema"]) {
                if ($db_schema->hasTable($def["table_name"])) $ignore_tables[] = $db_schema->getTable($def["table_name"]);
                unset($defs[$i]);
            }
        }

        $schema = new Schema($ignore_tables);
        try {
            foreach ($defs as $def) {
                $table = $schema->createTable($def["table_name"]);
                // テーブルのコメント
                if ($def["comment"]) $table->addOption("comment", $def["comment"]);
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
                $tables[$def["table_name"]] = $table;
            }
            // 相互参照関係の登録
            foreach ($defs as $def) {
                $table = $schema->getTable($def["table_name"]);
                foreach ((array)$def["cols"] as $col_name => $col) {
                    // 型の指定のないカラムは定義されていないものと見なす
                    if ( ! $col["type"]) continue;
                    // 外部キー制約
                    if ($fkey_for = $col["fkey_for"]) {
                        $fkey_for_table = $schema->getTable($defs[$fkey_for]["table_name"]);
                        $index = $fkey_for_table->getPrimaryKey();
                        $fkey_for_ids = $index->getColumns();
                        $table->addForeignKeyConstraint($fkey_for_table, array($col_name), array($fkey_for_ids[0]));
                    }
                }
            }
        } catch (SchemaException $e) {
            report_error("DoctrineException ".$e->getMessage(), array(
                "def"=>$def,
            ));
        }
        return $schema;
    }
}
