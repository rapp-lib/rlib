<?php
namespace R\Lib\Table\Feature\Provider;
use R\Lib\Table\Feature\BaseFeatureProvider;

class QueryAccess extends BaseFeatureProvider
{
// -- 基本的なchain hookの定義

    /**
     * Insert/Update文のValues部を設定する
     */
    public function chain_values($query, $values)
    {
        $query->setValues($values);
    }
    /**
     * Select文のField部を指定する
     */
    public function chain_fields($query, $fields)
    {
        $query->setFields($fields);
    }
    /**
     * Select文のField部を追加する
     * 何も指定されていなければ"*"を追加する
     */
    public function chain_with($query, $col_name, $col_name_sub=false)
    {
        if ( ! $query->getFields()) $query->addField("*");
        if ($col_name_sub === false) $query->addField($col_name);
        else $query->addField($col_name, $col_name_sub);
    }
    /**
     * FROMに対するAS句の設定
     */
    public function chain_alias($query, $alias)
    {
        $query->setAlias($alias);
    }
    /**
     * JOIN句の設定
     */
    public function chain_join($query, $table, $on=array(), $type="LEFT")
    {
        // Tableに変換する
        $query->join($table, $on, $type);
    }
    /**
     * GROUP_BY句の設定
     */
    public function chain_groupBy($query, $col_name)
    {
        $query->addGroup($col_name);
    }
    /**
     * ORDER_BY句の設定
     */
    public function chain_orderBy($query, $col_name, $order=null)
    {
        if ($order==="DESC" || $order==="desc" || $order===false) $order = "DESC";
        else $order = null;
        $query->addOrder($col_name.(strlen($order) ? " ".$order : ""));
    }
    /**
     * OFFSET/LIMIT句の設定
     */
    public function chain_pagenate($query, $offset=false, $limit=false)
    {
        if ($offset !== false) {
            $query->setOffset($offset);
        }
        if ($limit !== false) {
            $query->setLimit($limit);
        }
    }
    /**
     * LIMIT句の設定
     */
    public function chain_limit ($query, $limit, $offset=0)
    {
        $query->setLimit($limit);
        $query->setOffset($offset);
    }
    /**
     * IDを条件に指定する
     */
    public function chain_findById($query, $id)
    {
        $id_col_name = $query->getDef()->getIdColName("id");
        $query->where($query->getTableName().".".$id_col_name, $id);
    }
    /**
     * 絞り込み条件を指定する
     */
    public function chain_findBy($query, $col_name, $value=false)
    {
        $query->where($col_name, $value);
    }
    /**
     * 絞り込み条件にEXSISTSを指定する
     */
    public function chain_findByExists($query, $table)
    {
        $query->where("EXISTS(".$table->buildQuery().")");
    }
    /**
     * 絞り込み結果を空にする
     */
    public function chain_findNothing($query)
    {
        $query->addWhere("0=1");
    }
    /**
     * @hook chain
     * offset/limit指定を削除する
     */
    public function chain_removePagenation($query)
    {
        $query->removeOffset();
        $query->removeLimit();
    }
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
        $self_table_name = $query->getDef()->getAppTableName();
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
            if ($edge[0] == $self_table_name) $edge[0] = $query->getTableName();
            // 目的関係先の主キーを条件に登録する場合、経由先を使用するのでJOIN不要
            if ($edge[2] == $target_table_name && $col_name===false) {
                // 最終経由先の外部キー＝値を条件に指定する
                $query->where($edge[0].".".$edge[1], $value);
            // 経由関係先への参照は、JOINを指定する
            } else {
                $join_table = app()->tables[$edge[2]];
                // ASの解決
                if ($edge["as"]) $join_table->alias($edge["as"]);
                $join_table_name = $join_table->getQuery()->getTableName();
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
        $self_table_name = $query->getDef()->getAppTableName();
        $route = app("table.resolver")->getFkeyRoute($self_table_name, $target_table_name);
        // 経路が存在しない場合は処理を行わない
        if ( ! $route) return false;
        // 目的関係先に近い順に登録する
        foreach (array_reverse($route) as $edge) {
            $join_table = app()->tables[$edge[2]];
            // 関係元からの参照であれば、テーブルの名前はクエリ内のものを使用する
            if ($edge[0] == $self_table_name) $edge[0] = $query->getTableName();
            // ASの解決
            $join_table_name = null;
            if ($edge[2] == $target_table_name && $target_as) $join_table_name = $target_table_as;
            elseif ($edge["as"]) $join_table_name = $edge["as"];
            if ($join_table_name) $join_table->alias($join_table_name);
            else $join_table_name = $join_table->getDeftableName();
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
}
