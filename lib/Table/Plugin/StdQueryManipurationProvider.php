<?php
namespace R\Lib\Table\Plugin;

class StdQueryManipurationProvider extends BasePluginProvider
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
}
