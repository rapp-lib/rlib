<?php
namespace R\Lib\Table\Feature\Provider;
use R\Lib\Table\Feature\BaseFeatureProvider;

class SearchFeature extends BaseFeatureProvider
{
    /**
     * 検索フォームによる絞り込み
     * search_typeXxx($form, $field_def, $value)メソッドを呼び出す
     */
    public function chain_findBySearchFields ($query, $form, $search_fields)
    {
        // 適用済みフラグ
        $applied = false;
        // Yield集約対象
        $yields = array();
        foreach ($search_fields as $search_field) {
            // Yield集約対象は別Tableに対して処理するために一次待避
            if ($search_yield = $search_field["yield"]) {
                $yield_id = $search_yield["yield_id"] ?: count($yields);
                if ( ! is_array($yields[$yield_id])) $yields[$yield_id] = array();
                $yields[$yield_id] += $search_yield;
                unset($search_field["yield"]);
                $yields[$yield_id]["search_fields"][] = $search_field;
                continue;
            }
            $search_type = $search_field["type"];
            $field_def = $search_field["field_def"];
            $value = $search_field["value"];
            // search_typeXxx($form, $field_def, $value)メソッドを呼び出す
            $search_method_name = "search_type".str_camelize($search_type);
            if ( ! method_exists($this, $search_method_name)) {
                report_error("検索メソッドが定義されていません",array(
                    "search_method_name" => $search_method_name, "table" => $this,
                ));
            }
            $result = call_user_func(array($this,$search_method_name), $query, $form, $field_def, $value);
            if ($result!==false) $applied = true;
        }
        foreach ($yields as $yield) {
            // search_yieldXxx($form, $yield)メソッドを呼び出す
            $search_method_name = "search_yield".str_camelize($yield["type"]);
            if ( ! method_exists($this, $search_method_name)) {
                report_error("検索メソッドが定義されていません",array(
                    "search_method_name" => $search_method_name, "table" => $this,
                ));
            }
            $result = call_user_func(array($this,$search_method_name), $query, $form, $yield);
            if ($result!==false) $applied = true;
        }
        return $applied;
    }

// -- 基本的なsearch hookの定義

    /**
     * @hook search where
     * 一致、比較、IN（値を配列指定）
     */
    public function search_typeWhere ($query, $form, $field_def, $value)
    {
        if ( ! isset($value)) return false;
        // 対象カラムは複数指定に対応
        $target_cols = $field_def["target_col"];
        if ( ! is_array($target_cols)) $target_cols = array($target_cols);
        $conditions_or = array();
        foreach ($target_cols as $i => $target_col) {
            $conditions_or[$i] = array($target_col => $value);
        }
        if (count($conditions_or)==0) return false;
        if (count($conditions_or)==1) $conditions_or = array_pop($conditions_or);
        // 複数のカラムが有効であればはORで接続
        elseif (count($conditions_or)>1) $conditions_or = array("OR"=>$conditions_or);
        if ($field_def["having"]) $query->addHaving($conditions_or);
        else $query->addWhere($conditions_or);
    }
    /**
     * @hook search word
     */
    public function search_typeWord ($query, $form, $field_def, $value)
    {
        if ( ! isset($value)) return false;
        // 対象カラムは複数指定に対応
        $target_cols = $field_def["target_col"];
        if ( ! is_array($target_cols)) $target_cols = array($target_cols);
        // スペースで分割して複数キーワード指定
        $conditions_or = array();
        foreach ($target_cols as $i => $target_col) {
            foreach (preg_split('![\s　]+!u',$value) as $keyword) {
                if (strlen(trim($keyword))) {
                    $keyword = str_replace('%','\\%',trim($keyword));
                    $conditions_or[$i][] = array($target_col." LIKE" =>"%".$keyword."%");
                }
            }
        }
        if (count($conditions_or)==0) return false;
        if (count($conditions_or)==1) $conditions_or = array_pop($conditions_or);
        // 複数のカラムが有効であればはORで接続
        elseif (count($conditions_or)>1) $conditions_or = array("OR"=>$conditions_or);
        // HAVINGまたはWHEREに追加
        if ($field_def["having"]) $query->addHaving($conditions_or);
        else $query->addWhere($conditions_or);
    }
    /**
     * @deprecated search_yieldExists
     * @hook search exists
     * 別Tableをサブクエリとして条件指定する
     */
    public function search_typeExists ($query, $form, $field_def, $value)
    {
        if ( ! isset($value)) return false;
        $table = $this->releasable(table($field_def["search_table"]));
        $table->findBy($this->getQueryTableName().".".$this->getIdColName()."=".$table->getQueryTableName().".".$field_def["fkey"]);
        $table->findBySearchFields($form, $field_def["search_fields"]);
        $query->where("EXISTS(".$table->buildQuery("select").")");
    }
    /**
     * @hook search sort
     */
    public function search_typeSort ($query, $form, $field_def, $value)
    {
        $cols = array();
        // @deprecated 旧仕様との互換処理
        if (isset($field_def["default"])) array_unshift($cols, $field_def["default"]);
        // colsの解析
        foreach ((array)$field_def["cols"] as $k=>$v) {
            if ( ! isset($value)) $value = is_array($v) ? $k : $v;
            if (is_numeric($k) && is_string($v)) $cols[$v] = $v;
            else $cols[$k] = $v;
        }
        // DESC指定の取得
        $desc = false;
        if (preg_match('!^(.*?)(?:@(ASC|DESC))!', $value, $_)) {
            $value = $_[1];
            $desc = $_[2]=="DESC";
        }
        // ユーザ入力値の解析
        $value = $cols[$value];
        if ( ! isset($value)) return false;
        // DESC指定の反映
        if (is_string($value) && $desc) $value .= " DESC";
        elseif (is_array($value)) {
            if ($desc) $value = $value[1];
            else $value = $value[0];
        }
        // 複数指定に対応
        if ( ! is_array($value)) $value = array($value);
        foreach ($value as $a_value) $query->addOrder($a_value);
    }
    /**
     * @hook search page
     */
    public function search_typePage ($query, $form, $field_def, $value)
    {
        // 1ページの表示件数
        $volume = $field_def["volume"];
        // 指定済みのlimitにより補完, 指定が無ければ20件とみなす
        if ( ! $volume) $volume = $query->getLimit() ?: 20;
        // 1ページ目
        if ( ! $value) $value = 1;
        $query->setOffset(($value-1)*$volume);
        $query->setLimit($volume);
    }
    /**
     * @hook search_yield exists
     * 別Tableをサブクエリとして条件指定する
     */
    public function search_yieldExists ($query, $form, $yield)
    {
        $table = $this->releasable(table($yield["table"]));
        if ($yield["on"]) {
            $table->findBy($yield["on"]);
        } else {
            $fkey = $yield["fkey"] ?: $table->getColNameByAttr("fkey_for", $this->getAppTableName());
            $table->findBy($this->getQueryTableName().".".$this->getIdColName()."=".$table->getQueryTableName().".".$fkey);
        }
        if ($yield["joins"]) foreach ($yield["joins"] as $join) $table->join($join);
        if ($yield["where"]) $table->findBy($yield["where"]);
        $result = $table->findBySearchFields($form, $yield["search_fields"]);
        if ($result) $query->where("EXISTS(".$table->buildQuery("select").")");
    }

}

