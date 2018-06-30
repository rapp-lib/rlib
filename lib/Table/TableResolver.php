<?php
namespace R\Lib\Table;

class TableResolver
{
    protected $table_defs = array();
    public function getTableDef($table_name)
    {
        if (isset($this->table_defs[$table_name])) return $this->table_defs[$table_name];
        return $this->table_defs[$table_name] = app("table")->getTableDef($table_name);
    }
    public function getIdColName($table_name)
    {
        $class = app("table")->getClassByAppTableName($table_name);
        return $class::getIdColName();
    }

    protected $fkey_routes = array();
    public function getFkeyRoute($from_table, $to_table)
    {
        // 探索中または探索済みであれば中断
        if (isset($this->fkey_routes[$from_table][$to_table])) {
            return $this->fkey_routes[$from_table][$to_table];
        }
        // 探索中は経路がないことを示す（ループバック時には探索終了）
        $this->fkey_routes[$from_table][$to_table] = array();
        $from_table_def = $this->getTableDef($from_table);
        // 明示的に指定された経路の探索
        foreach ((array)$from_table_def["fkey_routes"] as $assoc_table=>$route) {
            if ( ! $this->fkey_routes[$from_table][$assoc_table]) {
                $this->fkey_routes[$from_table][$assoc_table] = $route;
            }
            // 直接関係先が探索対象であれば完了
            if ($assoc_table==$to_table) {
                return $this->fkey_routes[$from_table][$assoc_table];
            }
        }
        // 全ての外部キーを逐次探索
        foreach ($from_table_def["cols"] as $fkey_col_name=>$from_col_def) {
            if ($assoc_table = $from_col_def["fkey_for"]) {
                if ( ! $this->fkey_routes[$from_table][$assoc_table]) {
                    // 直接関係先への経路の作成
                    $this->fkey_routes[$from_table][$assoc_table] = array(
                        array(
                            $from_table, // 関係元Table名
                            $fkey_col_name, // 関係元テーブル上の外部キー
                            $assoc_table, // 関係先テーブル名
                            $this->getIdColName($assoc_table), // 関係先テーブル上の主キー
                            // "where"=>array(), // 追加条件
                            // "as"=>null, // Join時の別名
                        ),
                    );
                }
                // 直接関係先が探索対象であれば完了
                if ($assoc_table==$to_table) {
                    return $this->fkey_routes[$from_table][$assoc_table];
                }
                // 直接関係先経由の経路を探索
                if ($assoc_route = $this->getFkeyRoute($assoc_table, $to_table)) {
                    return $this->fkey_routes[$from_table][$to_table] = array_merge(
                        $this->fkey_routes[$from_table][$assoc_table], $assoc_route);
                }
            }
        }
        return $this->fkey_routes[$from_table][$to_table];
    }
}
