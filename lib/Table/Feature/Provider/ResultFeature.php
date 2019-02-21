<?php
namespace R\Lib\Table\Feature\Provider;
use R\Lib\Table\Feature\BaseFeatureProvider;

class ResultFeature extends BaseFeatureProvider
{
// -- resultに対するHook

    /**
     * 条件に対する件数の取得（Limit解除）
     */
    public function result_getTotal($result)
    {
        // 件数取得用にSQL再発行
        $query = clone($result->getStatement()->getQuery());
        $query->removeOffset();
        $query->removeLimit();
        $query->setSkipBeforeRender(true);
        return $query->makeBuilder()->selectCount();
    }
    /**
     * Pagerの取得
     */
    public function result_getPager($result)
    {
        $query = $result->getStatement()->getQuery();
        // limit指定のないSQLに対するPagerは発行不能
        if ( ! $query->getLimit()) return null;
        return app()->make("table.query_pager",
            array($result->getTotal(), $query->getOffset(), $query->getLimit()));
    }
    /**
     * Pagerの取得、但しレコードが1件もなければnullを返す
     */
    public function result_getPagerIfHasRecord($result)
    {
        $pager = $result->getPager();
        return $pager && $pager->get("count") > 0 ? $pager : null;
    }

// -- 基本的なresultに対するHook

    /**
     * 指定したカラムとしてKEYの値で対応付けて統合する
     */
    public function result_mergeBy($result, $merge_col_name, $values, $key_col_name=false)
    {
        $id_col_name = $result->getStatement()->getQuery()->getDef()->getIdColName();
        if ($key_col_name===false) $key_col_name = $id_col_name;
        foreach ($result as $record) $record[$merge_col_name] = $values[$record->getColValue($key_col_name)];
        return $result;
    }
    /**
     * 各レコードの特定カラムのみの配列を取得する
     */
    public function result_getHashedBy($result, $col_name, $col_name_sub=false, $col_name_sub_ex=false)
    {
        $hashed_result = array();
        foreach ($result as $i=>$record) {
            if ($col_name_sub === false) {
                $hashed_result[$i] = $record->getColValue($col_name);
            } elseif ($col_name_sub_ex === false) {
                $hashed_result[$record->getColValue($col_name)]
                    = $record->getColValue($col_name_sub);
            } else {
                $hashed_result[$record->getColValue($col_name)]
                    [$record->getColValue($col_name_sub)]
                    = $record->getColValue($col_name_sub_ex);
            }
        }
        return $hashed_result;
    }
    /**
     * 各レコードを特定のユニークカラムで添え字を書き換えた配列を取得する
     */
    public function result_getMappedBy($result, $col_name, $col_name_sub=false)
    {
        $mapped_result = array();
        foreach ($result as $record) {
            if ($col_name_sub === false) $mapped_result[$record->getColValue($col_name)] = $record;
            else $mapped_result[$record->getColValue($col_name)][$record->getColValue($col_name_sub)] = $record;
        }
        return $mapped_result;
    }
    /**
     * 各レコードを特定カラムでグループ化した配列を取得する
     */
    public function result_getGroupedBy($result, $col_name, $col_name_sub=false)
    {
        $grouped_result = array();
        foreach ($result as $key => $record) {
            if ($col_name_sub === false) {
                $grouped_result[$record->getColValue($col_name)][] = $record;
            } else {
                $grouped_result[$record->getColValue($col_name)]
                    [$record->getColValue($col_name_sub)][]= $record;
            }
        }
        return $grouped_result;
    }
    /**
     * 外部キーを対象の主キーでの検索条件に設定した状態で、BelongsTo関係にあるTableを取得する
     */
    public function result_getBelongsToTable($result, $assoc_table_name, $assoc_fkey=false)
    {
        $table = $result->getStatement()->getQuery()->getDef();
        $assoc_table = app()->tables[$assoc_table_name];
        $assoc_fkey = $assoc_fkey ?: $table->getColNameByAttr("fkey_for", $assoc_table_name);
        if ( ! $assoc_fkey) {
            report_error("Table間にBelongsTo関係がありません",array(
                "table"=>$table,
                "assoc_table"=>$assoc_table,
            ));
        }
        $assoc_ids = $result->getHashedBy($assoc_fkey);
        return $assoc_table
            ->setParentQuery($result->getStatement()->getQuery())
            ->findById($assoc_ids);
    }
    /**
     * 主キーを対象の外部キーでの検索条件に設定した状態で、HasMany関係にあるTableを取得する
     */
    public function result_getHasManyTable($result, $assoc_table_name, $assoc_fkey=false)
    {
        $table = $result->getStatement()->getQuery()->getDef();
        $assoc_table = app()->tables[$assoc_table_name];
        if ( ! $assoc_fkey) {
            $assoc_fkey = $assoc_table->getColNameByAttr("fkey_for", $table->getAppTableName());
        }
        if ( ! $assoc_fkey) {
            report_error("Table間にHasMany関係がありません",array(
                "table"=>$this,
                "assoc_table"=>$assoc_table,
            ));
        }
        $assoc_ids = $result->getHashedBy($table->getIdColName());
        return $assoc_table
            ->setParentQuery($result->getStatement()->getQuery())
            ->findBy($assoc_fkey, $assoc_ids);
    }
    /**
     * @hook result
     * save処理対象のRecordを取得
     */
    public function result_getSavedRecord($result)
    {
        $query = $result->getStatement()->getQuery();
        if ($query->getType()=="update") {
            $id_col_name = $query->getDef()->getIdColName();
            $id = $query->getWhere($id_col_name);
        } elseif ($query->getType()=="insert") {
            $id = $result->getLastInsertId();
        }
        return $id ? $query->getDef()
            ->setParentQuery($result->getStatement()->getQuery())
            ->selectById($id) : null;
    }
}
