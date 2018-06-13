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
        $db_schema = self::getDbSchema($ds_name);
        // Tableクラスの定義取得
        $table_defs = app()->table->collectTableDefs($table_dirs);
        $class_schema = self::getSchemaFromTableDefs($table_defs, $db_schema);
        // 差分抽出
        $doctrine = app()->db($ds_name)->getDoctrineConnection();
        return $class_schema->getMigrateFromSql($db_schema, $doctrine->getDatabasePlatform());
    }
    /**
     * 対象のDB接続先について、DbSchemaを取得する
     */
    public static function getDbSchema($ds_name)
    {
        // DB上の定義取得
        $doctrine = app()->db($ds_name)->getDoctrineConnection();
        $schema = $doctrine->getSchemaManager()->createSchema();
        // Optionsの付与
        foreach ($doctrine->fetchAll('SHOW TABLE STATUS;') as $stat) {
            $schema->getTable($stat["Name"])->addOption("comment", $stat["Comment"]);
        }
        return $schema;
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
    /**
     * SchemaからTable定義を作成する
     */
    public static function getTableDefsFromSchema($db_schema)
    {
        // tables
        $_tables = $db_schema->getTables();
        $tables = array();
        foreach ($_tables as $_table) {
            $table = array();
            $table["name"] = $_table->getName();
            $table["comment"] = $_table->getOption("comment");
            // cols
            $_cols = $_table->getColumns();
            $table["cols"] = array();
            foreach ($_cols as $_col) {
                $col = array();
                $col["name"] = $_col->getName();
                $col["type"] = $_col->getType()->getName();
                $col["length"] = $_col->getLength();
                $col["precision"] = $_col->getPrecision();
                $col["scale"] = $_col->getScale();
                $col["unsigned"] = $_col->getUnsigned();
                $col["fixed"] = $_col->getFixed();
                $col["notnull"] = $_col->getNotnull();
                $col["autoincrement"] = $_col->getAutoincrement();
                $col["default"] = $_col->getDefault();
                $col["comment"] = $_col->getComment();
                // 標準値の削除
                if ( ! $col["length"]) unset($col["length"]);
                if ( ! $col["precision"]) unset($col["precision"]);
                if ($col["precision"]==10) unset($col["precision"]);
                if ( ! $col["scale"]) unset($col["scale"]);
                if ( ! $col["unsigned"]) unset($col["unsigned"]);
                if ( ! $col["fixed"]) unset($col["fixed"]);
                if ( ! $col["notnull"]) unset($col["notnull"]);
                if ( ! $col["autoincrement"]) unset($col["autoincrement"]);
                if ( ! strlen($col["default"])) unset($col["default"]);
                if ( ! strlen($col["comment"])) unset($col["comment"]);
                $table["cols"][$col["name"]] = $col;
            }
            // pk
            $_pk_index = $_table->getPrimaryKey();
            if ($_pk_index) foreach ($_pk_index->getColumns() as $pk_col_name) {
                $table["cols"][$pk_col_name]["id"] = true;
            }
            // indexes
            $_indexes = $_table->getIndexes();
            foreach ($_indexes as $_index) {
                $index = array();
                $index["name"] = $_index->getName();
                $index["cols"] = $_index->getColumns();
                $index["flgs"] = $_index->getFlags();
                //$index["options"] = $_index->getOptions();
                // pkのスキップ
                if ($index["name"]==="PRIMARY") continue;
                // 標準値の削除
                if (preg_match('!^IDX_[A-Z0-9]{16}!', $index["name"])) unset($index["name"]);
                if ( ! $index["flgs"]) unset($index["flgs"]);
                if ( ! $index["options"]) unset($index["options"]);
                $table["indexes"][] = $index;
            }
            $tables[$table["name"]] = $table;
        }
        // 相互参照関係の登録
        foreach ($_tables as $_table) {
            // fk_for
            $_fks = $_table->getForeignKeys();
            foreach ($_fks as $_fk) {
                $fkey_col_names = $_fk->getColumns();
                $fkey_for_table_name = $_fk->getForeignTableName();
                $fkey_for_col_names = $_fk->getForeignColumns();
                // pkに対する参照の場合テーブル名のみ
                $fkey_for_col_is_id = $tables[$fkey_for_table_name]["cols"][$fkey_for_col_names[0]]["id"];
                $fkey_for = $fkey_for_table_name;
                if ( ! $fkey_for_col_is_id) $fkey_for .= ".".$fkey_for_col_names[0];
                $tables[$_table->getName()]["cols"][$fkey_col_names[0]]["fkey_for"] = $fkey_for;
            }
        }
        return $tables;
    }
}
