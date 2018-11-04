<?php
namespace R\Lib\Table\Plugin;

class StdRelResolverProvider extends BasePluginProvider
{
    /**
     * 経路を探索して指定した関係先テーブルの値を条件に指定する
     *
     * @param string $target_table_name 関係先テーブル名
     * @param mixed $value 条件に指定する値
     * @param string $col_name 値を対応づけるカラム名。指定がない場合、主キーを対応づける
     * @return bool
     */
    public function chain_findByRoute($query, $target_table_name, $value, $col_name=false)
    {
        // 経路を取得
        $self_table_name = $this->getAppTableName();
        $route = app("table.resolver")->getFkeyRoute($self_table_name, $target_table_name);
        // 経路が存在しない場合は処理を行わない
        if ( ! $route) {
            report_warning("無効なfindByRoute, 有効な経路がありません", array(
                "from_table"=>$self_table_name, "to_table"=>$target_table_name), "Error");
            return false;
        }
        // 目的関係先に近い順に登録する
        foreach (array_reverse($route) as $edge) {
            // 関係元からの参照であれば、テーブルの名前はクエリ内のものを使用する
            if ($edge[0] == $self_table_name) $edge[0] = $this->getQueryTableName();
            // 目的関係先の主キーを条件に登録する場合、経由先を使用するのでJOIN不要
            if ($edge[2] == $target_table_name && $col_name===false) {
                // 最終経由先の外部キー＝値を条件に指定する
                $query->where($edge[0].".".$edge[1], $value);
            // 経由関係先への参照は、JOINを指定する
            } else {
                $join_table = $this->releasable(table($edge[2]));
                // ASの解決
                if ($edge["as"]) $join_table->alias($edge["as"]);
                $join_table_name = $join_table->getQueryTableName();
                // Join済みであれば以降は対応付けを行わない
                if ($query->getJoinByName($join_table_name)) break;
                $on = array($edge[0].".".$edge[1]."=".$join_table_name.".".$edge[3]);
                // 目的関係先の主キー以外のカラム＝値を条件に指定する
                if ($edge[2] == $target_table_name && $col_name!==false) {
                    $on[] = array($join_table_name.".".$col_name, $value);
                }
                // 経由関係先をJoin登録する
                $query->join($join_table, $on);
            }
            // 追加条件を指定する
            if ($edge[4]) $query->where($edge[4]);
        }
        return true;
    }
    /**
     * @hook chain
     * JOIN句の設定 主テーブル側が持つ外部キーでJOIN
     */
    public function chain_joinBelongsTo ($query, $target_table_name, $fkey=false)
    {
        if (is_array($target_table_name)) list($target_table_name, $target_as) = $target_table_name;
        // 経路を取得
        $self_table_name = $this->getAppTableName();
        $route = app("table.resolver")->getFkeyRoute($self_table_name, $target_table_name);
        // 経路が存在しない場合は処理を行わない
        if ( ! $route) return false;
        // 目的関係先に近い順に登録する
        foreach (array_reverse($route) as $edge) {
            $join_table = $this->releasable(table($edge[2]));
            // 関係元からの参照であれば、テーブルの名前はクエリ内のものを使用する
            if ($edge[0] == $self_table_name) $edge[0] = $this->getQueryTableName();
            // ASの解決
            if ($edge[2] == $target_table_name && $target_as) $join_table->alias($target_table_as);
            elseif ($edge["as"]) $join_table->alias($edge["as"]);
            $join_table_name = $join_table->getQueryTableName();
            // Join済みであれば以降は対応付けを行わない
            if ($query->getJoinByName($join_table_name)) break;
            $on = array($edge[0].".".$edge[1]."=".$join_table_name.".".$edge[3]);
            // 経由関係先をJoin登録する
            $query->join($join_table, $on);
            // 追加条件を指定する
            if ($edge[4]) $query->where($edge[4]);
        }
        return true;
    }
    /**
     * 関係先テーブルに経路を持つ外部キーの値が所定のレコードを参照しているか確認する
     *
     * @param string $target_table_name 関係先テーブル名
     * @param mixed $value 条件に指定する値
     * @return bool
     */
    public function checkFkeyValuesByRoute($target_table_name, $value)
    {
        // 外部キーの値を逐次チェック
        foreach ($this->getValues() as $k=>$v) {
            if ($assoc_table_name = static::$cols[$k]["fkey_for"]) {
                $route = app("table.resolver")->getFkeyRoute($assoc_table_name, $target_table_name);
                // 関係先テーブルに関係しない外部キーはスキップ
                if ( ! $route) continue;
                // 関係先に指定したレコードが存在しない場合false
                $id_col_name = app("table.resolver")->getIdColName($target_table_name);
                $record = $this->releasable(table($assoc_table_name))
                    ->findByid($v)
                    ->findByRoute($target_table_name, $value, $id_col_name)
                    ->selectOne();
                if ( ! $record) return false;
            }
        }
        // 関係先の参照に問題がない場合true
        return true;
    }
}
