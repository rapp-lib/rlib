<?php
namespace R\Lib\Table\Feature\Provider;
use R\Lib\Table\Feature\BaseFeatureProvider;

/**
 * Assoc
 * パラメータ例:
 *     "type"=>"hasMany",
 *     "table"=>"Product", // 必須 関係先テーブル名
 *     "fkey"=>"owner_member_id", // 任意 関係先テーブル上のFK
 *     // fkeyの設定がない場合、assoc.tableの参照先のfkey_forの設定されたカラムを使用する
 *     "value_col"=>"status", // 任意 値を1項目に絞る場合
 *     "extra_values"=>array("type"=>1), // 任意 このassocで関係づけるレコードに設定する値
 *     "single"=>true, // 任意 trueが指定されると1レコードに対応付ける
 *  読み込み時の動作:
 *      Fetch完了後、結果全てのPKで関係先テーブルをSelectする
 *          extra_valuesの指定があれば絞り込みに使用する
 *          singleの指定があれば、既存1レコード目のみ取得する
 *  書き込み時の動作:
 *      ※書き込んだIDがわかることが必須なので、IDを指定しないUpdateではエラー
 *      書き込み完了後、対象のIDに関係する関係先のレコードで削除/更新/登録する
 *          assoc.tableのPKの一致で更新する
 *          value_col指定があれば、対象のカラムの一致で更新する
 *          singleの指定があれば、既存1レコード目を一致させて更新する
 *          extra_valuesの指定があれば絞り込みと、値の設定に使用する
 */
class AssocFeature extends BaseFeatureProvider
{
    /**
     * @hook result
     * Resultに対して定義とは別にassocの対応付けを行う
     */
    public function result_mergeAssoc ($result, $col_name, $assoc)
    {
        $def = $result->getStatement()->getQuery()->getDef();
        $assoc_table_name = $assoc["table"];
        $assoc_fkey = $assoc["fkey"];
        $assoc_extra_values = $assoc["extra_values"];
        $assoc_value_col = $assoc["value_col"];
        $assoc_single = (boolean)$assoc["single"];
        $assoc_join = $assoc["join"];
        // 主テーブルの取得件数が0件であれば処理を行わない
        if (count($result) === 0) return false;
        // assoc.fkeyの設定がなければ、assoc.tableのfkey_forを参照
        if ( ! isset($assoc_fkey)) {
            $table_name = $def->getAppTableName();
            $assoc_fkey = app()->tables[$assoc_table_name]->getColNameByAttr("fkey_for", $table_name);
        }
        $table = $result->getHasManyTable($assoc_table_name, $assoc_fkey);
        // ExtraValueを条件に設定
        if ($assoc_extra_values) $table->findBy($assoc_extra_values);
        // joinの指定があればJOINを接続
        if ($assoc_join) $table->join($assoc_join[0], $assoc_join[1]);
        // singleの指定があればレコード数に制限
        if ($assoc_single) $table->limit(count($result));
        $assoc_result_set = $table->select()->getGroupedBy($assoc_fkey);
        $pkey = $def->getIdColName();
        // 主テーブルのResultに関連づける
        foreach ($result as $i => $record) {
            // value_col指定=1項目の値のみに絞り込む場合
            if (isset($assoc_value_col)) {
                $values = array();
                foreach ((array)$assoc_result_set[$record[$pkey]] as $assoc_record) {
                    $values[] = $assoc_record[$assoc_value_col];
                }
                $record[$col_name] = $assoc_single ? current($values) : $values;
            } else {
                $record[$col_name] = (array)$assoc_result_set[$record[$pkey]];
            }
        }
    }
    /**
     * @hook result
     * Resultに対してassocに従って値を反映する
     */
    public function result_affectAssoc ($result, $col_name, $assoc, $values)
    {
        $query = $result->getStatement()->getQuery();
        $def = $query->getDef();
        $assoc_table_name = $assoc["table"];
        $assoc_fkey = $assoc["fkey"];
        $assoc_extra_values = $assoc["extra_values"];
        $assoc_value_col = $assoc["value_col"];
        $assoc_single = (boolean)$assoc["single"];
        // assoc.fkeyの設定がなければ、assoc.tableのfkey_forを参照
        if ( ! isset($assoc_fkey)) {
            $table_name = $def->getAppTableName();
            $assoc_fkey = app()->tables[$assoc_table_name]->getColNameByAttr("fkey_for", $table_name);
            if ( ! $assoc_fkey) {
                report_error("外部キーによる参照がないassoc関係", array(
                    "table_name"=>$table_name,
                    "assoc_table_name"=>$assoc_table_name,
                    "assoc"=>$assoc,
                ));
            }
        }
        // singleの指定があれば1レコードに制限
        if ($assoc_single) $values = array_slice((array)$values, 0, 1, true);
        // 書き込んだIDを確認
        $id = null;
        if ($query->getType() == "insert") {
            $id = $result->getLastInsertId();
        } elseif ($query->getType() == "update") {
            $id = $query->getWhere($query->getTableName().".".$def->getIdColName());
            if ( ! isset($id)) {
                $id = $query->getWhere($def->getIdColName());
            }
        }
        if ( ! isset($id)) {
            report_error("IDの特定できないUpdate/Insertに対してAssoc処理は実行できません",array(
                "query" => $query,
            ));
            return;
        }
        // 対象のIDに関係する関係先のレコードを差分削除
        $table = app()->tables[$assoc_table_name]->findBy($assoc_fkey, $id);
        // ExtraValueを条件に設定
        if ($assoc_extra_values) $table->findBy($assoc_extra_values);
        $assoc_result = $table->select();
        $assoc_id_col = $def->getIdColName();
        // value_col指定=1項目の値のみに絞り込む場合
        if (isset($assoc_value_col)) {
            // 既存の情報を値→IDでハッシュ
            $delete_assoc_ids = $assoc_result->getHashedBy($assoc_value_col, $assoc_id_col);
            // singleの指定があれば削除対象の1レコード目を更新対象とする
            if ($assoc_single) {
                $record = array($assoc_fkey=>$id, $assoc_value_col=>current($values));
                foreach ((array)$assoc_extra_values as $k=>$v) $record[$k] = $v;
                if ($delete_assoc_ids) {
                    $record[$assoc_id_col] = current($delete_assoc_ids);
                    unset($delete_assoc_ids[key($delete_assoc_ids)]);
                }
                app()->tables[$assoc_table_name]->save($record);
                $values = array();
            } else {
                foreach ((array)$values as $value) {
                    // 入力値が登録済みであれば、削除対象から除外
                    if (isset($delete_assoc_ids[$value])) {
                        unset($delete_assoc_ids[$value]);
                    // 入力値が未登録であれば、新規登録
                    } else {
                        $record = array($assoc_fkey=>$id, $assoc_value_col=>$value);
                        foreach ((array)$assoc_extra_values as $k=>$v) $record[$k] = $v;
                        app()->tables[$assoc_table_name]->save($record);
                    }
                }
            }
            // 削除
            if ($delete_assoc_ids) {
                app()->tables[$assoc_table_name]
                    ->findBy($assoc_fkey, $id)
                    ->findById($delete_assoc_ids)
                    ->deleteAll();
            }
        } else {
            // 既存の情報をID→IDでハッシュ
            $delete_assoc_ids = $assoc_result->getHashedBy($assoc_id_col, $assoc_id_col);
            // singleの指定があれば1レコード目を更新対象、その他を削除対象とする
            if ($assoc_single) {
                $values[key($values)][$assoc_id_col] = current($delete_assoc_ids);
            }
            foreach ((array)$values as $key => $record) {
                if ($record instanceof \ArrayObject) $record = $record->getArrayCopy();
                // 入力レコードのIDが空白で無ければ、削除対象から除外
                if (strlen($record[$assoc_id_col])) {
                    unset($delete_assoc_ids[$record[$assoc_id_col]]);
                }
                // 新規/上書き
                $record[$assoc_fkey] = $id;
                foreach ((array)$assoc_extra_values as $k=>$v) $record[$k] = $v;
                app()->tables[$assoc_table_name]->save($record);
            }
            // 削除
            if ($delete_assoc_ids) {
                app()->tables[$assoc_table_name]
                    ->findBy($assoc_fkey, $id)
                    ->findById($delete_assoc_ids)
                    ->deleteAll();
            }
        }
    }

// -- on_* assoc仮想カラム処理 write+0/read-0

    /**
     * assoc処理 未初期化のRecord値の取得時
     */
    public function pre_on_blankCol_assoc($record, $col_name)
    {
        $assoc = $record->getResult()->getStatement()
            ->getQuery()->getDef()->getColAttr($col_name, "assoc");
        return $assoc ?: false;
    }
    public function on_blankCol_assoc($record, $col_name, $assoc)
    {
        return $record->getResult()->mergeAssoc($col_name, $assoc);
    }
    /**
     * assoc処理 insert/updateの発行前
     */
    public function on_write_colAssoc ($query, $col_name)
    {
        // values→assoc_valuesに項目を移動
        $query->setAssocValues($col_name, $query->getValue($col_name));
        $query->removeValue($col_name);
    }
    /**
     * assoc処理 insert/updateの発行後
     */
    public function on_afterWrite_colAssoc ($result, $col_name)
    {
        $query = $result->getStatement()->getQuery();
        $value = $query->getAssocValues($col_name);
        $assoc = $query->getDef()->getColAttr($col_name, "assoc");
        return $result->affectAssoc($col_name, $assoc, $value);
    }
}

