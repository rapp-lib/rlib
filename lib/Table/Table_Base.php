<?php
namespace R\Lib\Table;

/**
 * Tableクラスの継承元
 */
class Table_Base extends Table_Core
{

// -- 基本的なchain hookの定義

    /**
     * @hook chain
     * Select文のField部を指定する
     */
    public function chain_fields ($col_name, $col_name_sub=false)
    {
        if ($col_name_sub === false) {
            $this->query->addField($col_name);
        } else {
            $this->query->addField($col_name, $col_name_sub);
        }
    }
    /**
     * @hook chain
     * Select文のField部を追加する
     * 何も指定されていなければ"*"を追加する
     */
    public function chain_with ($col_name, $col_name_sub=false)
    {
        if ( ! $this->query->getFields()) $this->query->addField("*");
        if ($col_name_sub === false) {
            $this->query->addField($col_name);
        } else {
            $this->query->addField($col_name, $col_name_sub);
        }
    }
    /**
     * @hook chain
     * FROMに対するAS句の設定
     */
    public function chain_alias ($alias)
    {
        $this->query->setAlias($alias);
    }
    /**
     * @hook chain
     * JOIN句の設定
     */
    public function chain_join ($table, $on=array(), $type="LEFT")
    {
        if (is_string($table)) $table = table($table);
        $this->query->join($table, $on, $type);
    }
    /**
     * @hook chain
     * JOIN句の設定 主テーブル側が持つ外部キーでJOIN
     */
    public function chain_joinBelongsTo ($table, $fkey=null, $type="LEFT")
    {
        if (is_string($table)) $table = table($table);
        // fkeyの設定がなければ、tableのfkey_forを参照
        if ( ! isset($fkey)) $fkey = $this->getColNameByAttr("fkey_for", $table->getAppTableName());
        $on = $this->getQueryTableName().".".$fkey
            ."=".$table->getQueryTableName().".".$table->getIdColName();
        $this->chain_join($table, $on, $type);
    }
    /**
     * @hook chain
     * JOIN句の設定 JOIN先テーブル側が持つ外部キーでJOIN
     */
    public function chain_joinHasMany ($table, $fkey=null, $type="LEFT")
    {
        if (is_string($table)) $table = table($table);
        // fkeyの設定がなければ、tableのfkey_forを参照
        if ( ! isset($fkey)) $fkey = $table->getColNameByAttr("fkey_for", $this->getAppTableName());
        $on = $this->getQueryTableName().".".$this->getIdColName()
            ."=".$table->getQueryTableName().".".$fkey;
        $this->chain_join($table, $on, $type);
    }
    /**
     * @hook chain
     * GROUP_BY句の設定
     */
    public function chain_groupBy ($col_name)
    {
        $this->query->addGroup($col_name);
    }
    /**
     * @hook chain
     * ORDER_BY句の設定
     */
    public function chain_orderBy ($col_name, $order=null)
    {
        if ($order==="ASC" || $order==="asc" || $order===true) {
            $order = "ASC";
        } elseif ($order==="DESC" || $order==="desc" || $order===false) {
            $order = "DESC";
        } else {
            $order = null;
        }
        $this->query->addOrder($col_name.(strlen($order) ? " ".$order : ""));
    }
    /**
     * @hook chain
     * OFFSET/LIMIT句の設定
     */
    public function chain_pagenate ($offset=false, $limit=false)
    {
        if ($offset !== false) {
            $this->query->setOffset($offset);
        }
        if ($limit !== false) {
            $this->query->setLimit($limit);
        }
    }
    /**
     * @hook chain
     * LIMIT句の設定
     */
    public function chain_limit ($limit, $offset=0)
    {
        $this->query->setLimit($limit);
        $this->query->setOffset($offset);
    }
    /**
     * @hook chain
     * IDを条件に指定する
     */
    public function chain_findById ($id)
    {
        $this->query->where($this->getQueryTableName().".".$this->getIdColName("id"), $id);
    }
    /**
     * @hook chain
     * 絞り込み条件を指定する
     */
    public function chain_findBy ($col_name, $value=false)
    {
        $this->query->where($col_name, $value);
    }
    /**
     * @hook chain
     * ログインID/PWを条件に指定する
     */
    public function chain_findByLoginIdPw ($login_id, $login_pw)
    {
        $login_id_col_name = $this->getColNameByAttr("login_id");
        $login_pw_col_name = $this->getColNameByAttr("login_pw");
        if ( ! $login_id_col_name || ! $login_pw_col_name) {
            report_error("login_id,login_pwカラムがありません",array(
                "table" => $this,
            ));
        }
        if (static::$cols[$login_pw_col_name]["hash_pw"]) {
            $login_pw = md5($login_pw);
        }
        $this->query->where($this->getQueryTableName().".".$login_id_col_name, (string)$login_id);
        $this->query->where($this->getQueryTableName().".".$login_pw_col_name, (string)$login_pw);
    }
    /**
     * @hook chain
     * 絞り込み結果を空にする
     */
    public function chain_findNothing ()
    {
        $this->query->addWhere("0=1");
    }
    /**
     * 検索フォームによる絞り込み
     * search_typeXxx($form, $field_def, $value)メソッドを呼び出す
     */
    public function chain_findBySearchFields ($form, $search_fields)
    {
        $applied = false;
        foreach ($search_fields as $search_field) {
            $search_type = $search_field["type"];
            $field_def = $search_field["field_def"];
            $value = $search_field["value"];
            // search_typeXxx($form, $field_def, $value)メソッドを呼び出す
            $search_method_name = "search_type".str_camelize($search_type);
            if ( ! method_exists($this, $search_method_name)) {
                report_error("検索メソッドが定義されていません",array(
                    "search_method_name" => $search_method_name,
                    "table" => $this,
                ));
            }
            $result = call_user_func(array($this,$search_method_name), $form, $field_def, $value);
            if ($result!==false) {
                $applied = true;
            }
        }
        return $applied;
    }
    /**
     * @hook chain
     * offset/limit指定を削除する
     */
    public function chain_removePagenation ()
    {
        $this->query->removeOffset();
        $this->query->removeLimit();
    }
    /**
     * @hook chain
     * Queryを操作する関数を指定する
     */
    public function chain_modifyQuery ($query)
    {
        call_user_func($query, $this->query);
    }
    /**
     * @hook chain
     * attr属性値を設定する
     */
    public function chain_attr ($name, $value)
    {
        $this->setAttr($name, $value);
    }

// -- 基本的なresultに対するHook

    /**
     * @hook result
     * 指定したカラムとしてKEYの値で対応付けて統合する
     */
    public function result_mergeBy ($result, $merge_col_name, $values, $key_col_name=false)
    {
        if ($key_col_name===false) $key_col_name = $this->getIdColName();
        foreach ($result as $record) $result[$merge_col_name] = $values[$result[$key_col_name]];
        return $result;
    }
    /**
     * @hook result
     * 各レコードの特定カラムのみの配列を取得する
     */
    public function result_getHashedBy ($result, $col_name, $col_name_sub=false, $col_name_sub_ex=false)
    {
        $hashed_result = array();
        foreach ($result as $key => $record) {
            if ($col_name_sub === false) {
                $hashed_result[$key] = $record[$col_name];
            } elseif ($col_name_sub_ex === false) {
                $hashed_result[$record[$col_name]] = $record[$col_name_sub];
            } else {
                $hashed_result[$record[$col_name]][$record[$col_name_sub]] = $record[$col_name_sub_ex];
            }
        }
        return $hashed_result;
    }
    /**
     * @hook result
     * 各レコードを特定のユニークカラムで添え字を書き換えた配列を取得する
     */
    public function result_getMappedBy ($result, $col_name, $col_name_sub=false)
    {
        $mapped_result = array();
        foreach ($result as $key => $record) {
            if ($col_name_sub === false) $mapped_result[$record[$col_name]] = $record;
            else $mapped_result[$record[$col_name][$col_name_sub]] = $record;
        }
        return $mapped_result;
    }
    /**
     * @hook result
     * 各レコードを特定カラムでグループ化した配列を取得する
     */
    public function result_getGroupedBy ($result, $col_name, $col_name_sub=false)
    {
        $grouped_result = array();
        foreach ($result as $key => $record) {
            if ($col_name_sub === false) {
                $grouped_result[$record[$col_name]][$key] = $record;
            } else {
                $grouped_result[$record[$col_name][$col_name_sub]][$key] = $record;
            }
        }
        return $grouped_result;
    }
    /**
     * @hook result
     * Resultに対して定義とは別にassocの対応付けを行う
     */
    public function result_mergeAssoc ($result, $col_name, $assoc)
    {
        $assoc_table_name = $assoc["table"];
        $assoc_fkey = $assoc["fkey"];
        $assoc_extra_values = $assoc["extra_values"];
        $assoc_value_col = $assoc["value_col"];
        $assoc_single = (boolean)$assoc["single"];
        $assoc_join = $assoc["join"];
        // assoc.fkeyの設定がなければ、assoc.tableのfkey_forを参照
        if ( ! isset($assoc_fkey)) {
            $table_name = $this->getAppTableName();
            $assoc_fkey = table($assoc_table_name)->getColNameByAttr("fkey_for", $table_name);
        }
        // 深度と循環参照の確認処理
        $assoc_depth = $this->getAttr("assoc_depth") !== null ? $this->getAttr("assoc_depth") : 1;
        $assoc_stack = (array)$this->getAttr("assoc_stack");
        $assoc_identity = $this->getDefTableName().".".$col_name;
        if (in_array($assoc_identity, $assoc_stack)) return false;
        if ($assoc_depth === 0) return false;
        $assoc_depth--;
        $assoc_stack[] = $assoc_identity;
        // 主テーブルの取得件数が0件であれば処理を行わない
        if (count($this->result) === 0) return false;
        // 主テーブルのIDを取得
        $pkey = $this->getIdColName();
        $ids = $this->result->getHashedBy($pkey);
        // 関連テーブルをFkeyでSELECT
        $table = table($assoc_table_name)->findBy($assoc_fkey, $ids);
        // 深度と循環参照の条件設定
        $table->setAttr("assoc_depth", $assoc_depth);
        $table->setAttr("assoc_stack", $assoc_stack);
        // ExtraValueを条件に設定
        if ($assoc_extra_values) $table->findBy($assoc_extra_values);
        // joinの指定があればJOINを接続
        if ($assoc_join) $table->join($assoc_join[0], $assoc_join[1]);
        // singleの指定があれば1レコードに制限
        if ($assoc_single) $table->limit(1);
        $assoc_result_set = $table->select()->getGroupedBy($assoc_fkey);
        // 主テーブルのResultに関連づける
        foreach ($this->result as $i => $record) {
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

// -- 基本的なon_*の定義

    /**
     * @hook on_fetch
     * ハッシュされたパスワードを関連づける
     */
    protected function on_fetch_hashPw ($record)
    {
        if ($col_name = $this->getColNameByAttr("hash_pw")) {
            $record[$col_name] = "";
        } else {
            return false;
        }
    }

    /**
     * @hook on_write
     * ハッシュされたパスワードを関連づける
     */
    protected function on_write_hashPw ()
    {
        if ($col_name = $this->getColNameByAttr("hash_pw")) {
            $value = $this->query->getValue($col_name);
            if (strlen($value)) {
                $this->query->setValue($col_name, md5($value));
            } else {
                $this->query->removeValue($col_name);
            }
        } else {
            return false;
        }
    }
    /**
     * @hook on_read
     * 削除フラグを関連づける
     */
    protected function on_read_attachDelFlg ()
    {
        if ($col_name = $this->getColNameByAttr("del_flg")) {
            $this->query->where($this->getQueryTableName().".".$col_name, 0);
        } else {
            return false;
        }
    }
    /**
     * @hook on_update
     * 削除フラグを関連づける
     */
    protected function on_update_attachDelFlg ()
    {
        if (($col_name = $this->getColNameByAttr("del_flg")) && $this->query->getDelete()) {
            $this->query->setDelete(false);
            $this->query->setValue($col_name, 1);
        } else {
            return false;
        }
    }
    /**
     * @hook on_insert
     * 登録日を関連づける
     */
    protected function on_insert_attachRegDate ()
    {
        if ($col_name = $this->getColNameByAttr("reg_date")) {
            $this->query->setValue($col_name, date("Y/m/d H:i:s"));
        } else {
            return false;
        }
    }
    /**
     * @hook on_write
     * 更新日を関連づける
     */
    protected function on_write_attachUpdateDate ()
    {
        if ($col_name = $this->getColNameByAttr("update_date")) {
            $this->query->setValue($col_name, date("Y/m/d H:i:s"));
        } else {
            return false;
        }
    }
    /**
     * @hook on_write
     * 認証が必要な領域でのテーブル操作について、認証中のアカウントのIDを上書きする
     */
    protected function on_write_forOwner ()
    {
        if ($col_name = $this->getColNameByAttr("owner_role")) {
            $owner_role = static::$cols[$col_name]["owner_role"];
            if ($owner_id = app()->user->id($owner_role)) {
                $this->query->setValue($col_name, $owner_id);
                return true;
            }
        }
        return false;
    }
    /**
     * @hook on_read
     * 認証が必要な領域でのテーブル操作について、認証中のアカウントのIDを上書きする
     */
    protected function on_read_forOwner ()
    {
        if ($col_name = $this->getColNameByAttr("owner_role")) {
            $owner_role = static::$cols[$col_name]["owner_role"];
            if ($owner_id = app()->user->id($owner_role)) {
                $this->query->where($this->getQueryTableName().".".$col_name, $owner_id);
                return true;
            }
        }
        return false;
    }
    /**
     * @hook on_insert
     * INSERT時のID生成ルールを対応づける
     */
    protected function on_insert_attachGenerator ()
    {
        $col_name = $this->getColNameByAttr("generator");
        $col_def = $this->getColDef($col_name);
        $value = $this->query->getValue($col_name);
        if (isset($value) || ! $col_def["generator"]) {
            return false;
        }
        $value = call_user_func(array($this, "generator_".$col_def["generator"]), $col_name);
        $this->query->setValue($col_name, $value);
    }

// -- on_* ストレージ型変換 write+200/read-200

    /**
     * @hook on_write
     * JSON形式で保存するカラムの処理
     */
    protected function on_write_jsonFormat_700 ()
    {
        if ($col_names = $this->getColNamesByAttr("format", "json")) {
            foreach ($col_names as $col_name) {
                $value = $this->query->getValue($col_name);
                if (is_array($value)) {
                    $this->query->setValue($col_name, json_encode((array)$value));
                }
            }
        } else {
            return false;
        }
    }
    /**
     * @hook on_fetch
     * JSON形式で保存するカラムの処理
     */
    protected function on_fetch_jsonFormat_300 ($record)
    {
        if ($col_names = $this->getColNamesByAttr("format", "json")) {
            foreach ($col_names as $col_name) {
                $value = $record[$col_name];
                if (strlen($value)) {
                    $record[$col_name] = (array)json_decode($record[$col_name]);
                }
            }
        } else {
            return false;
        }
    }
    /**
     * @hook on_write
     * GEOMETRY型の入出力変換
     */
    protected function on_write_geometryType_700 ()
    {
        foreach ($this->getColNamesByAttr("type", "geometry") as $col_name) {
            $value = $this->query->getValue($col_name);
            if (isset($value)) {
                if (preg_match('!\d+(\.\d+)?\s*,\s*\d+(\.\d+)?!', $value, $match)) {
                    $this->query->removeValue($col_name);
                    $this->query->setValue($col_name."=", 'POINT('.$match[0].')');
                } else {
                    $this->query->setValue($col_name, null);
                }
            }
        }
    }
    /**
     * @hook on_fetch
     * GEOMETRY型の入出力変換
     */
    protected function on_fetch_geometryType_300 ($record)
    {
        foreach ($this->getColNamesByAttr("type", "geometry") as $col_name) {
            if (isset($record[$col_name])) {
                $unpacked = unpack('Lpadding/corder/Lgtype/dlatitude/dlongitude', $record[$col_name]);
                $record[$col_name] = $unpacked["latitude"]." , ".$unpacked["longitude"];
            }
        }
    }

// -- assoc hookを呼び出すためのon hookの定義

    /**
     * assoc指定されたFeieldに対応するValues
     */
    private $assoc_values = null;
    /**
     * assoc指定されたFieldに対応するFields
     */
    private $assoc_fields = null;
    /**
     * assoc hook処理の呼び出し
     */
    protected function callAssocHookMethod ($method_name, $col_name, $args=array())
    {
        $assoc = static::$cols[$col_name]["assoc"];
        $method_name .= "_".($assoc["type"] ?: "hasMany");
        if ( ! method_exists($this, $method_name)) return false;
        array_unshift($args, $col_name);
        return call_user_func_array(array($this, $method_name), $args);
    }
    /**
     * assoc処理 selectの発行前
     */
    protected function on_select_assoc ()
    {
        // Select対象となっているcol_nameの特定
        $fields = (array)$this->query->getFields();
        if ( ! $fields) $fields = array("*");
        $col_names = array();
        foreach ($fields as $i => $col_name) {
            if ( ! is_numeric($i)) $col_name = $i;
            if ($col_name == "*") {
                foreach (static::$cols as $def_col_name  => $def_col) {
                    if ( ! static::$cols[$col_name]["assoc"]["except"]) {
                        $col_names[$def_col_name] = $def_col_name;
                    }
                }
            } else {
                $col_names[$col_name] = $col_name;
            }
        }
        $this->assoc_fields = array();
        foreach ($col_names as $col_name) {
            if (static::$cols[$col_name]["assoc"]) {
                // fields→assoc_fieldsに項目を移動
                $this->query->removeField($col_name);
                $this->assoc_fields[] = $col_name;
                // assoc処理の呼び出し
                $this->callAssocHookMethod("assoc_select", $col_name);
            }
        }
        return $this->assoc_fields ? true : false;
    }
    /**
     * assoc処理 各レコードfetch後
     */
    protected function on_fetch_assoc ($record)
    {
        foreach ((array)$this->assoc_fields as $col_name) {
            $this->callAssocHookMethod("assoc_fetch", $col_name);
        }
        return $this->assoc_fields ? true : false;
    }
    /**
     * assoc処理 fetch完了後
     */
    protected function on_fetchEnd_assoc ()
    {
        foreach ((array)$this->assoc_fields as $col_name) {
            $this->callAssocHookMethod("assoc_fetchEnd", $col_name);
        }
        return $this->assoc_fields ? true : false;
    }
    /**
     * assoc処理 insert/updateの発行前
     */
    protected function on_write_assoc ()
    {
        $this->assoc_values = array();
        foreach ((array)$this->query->getValues() as $col_name => $value) {
            if (static::$cols[$col_name]["assoc"]) {
                // values→assoc_valuesに項目を移動
                $this->query->removeValue($col_name);
                $this->assoc_values[$col_name] = $value;
                // assoc処理の呼び出し
                $this->callAssocHookMethod("assoc_write", $col_name);
            }
        }
        return $this->assoc_values ? true : false;
    }
    /**
     * assoc処理 insert/updateの発行後
     */
    protected function on_afterWrite_assoc ()
    {
        foreach ((array)$this->assoc_values as $col_name => $values) {
            $this->callAssocHookMethod("assoc_afterWrite", $col_name, array($values));
        }
        return $this->assoc_values ? true : false;
    }

// -- 基本的なassoc hookの定義

    /**
     * @hook assoc hasMany
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
    protected function assoc_fetchEnd_hasMany ($col_name)
    {
        $this->result->mergeAssoc($col_name, static::$cols[$col_name]["assoc"]);
    }
    protected function assoc_afterWrite_hasMany ($col_name, $values)
    {
        $assoc = static::$cols[$col_name]["assoc"];
        $assoc_table_name = $assoc["table"];
        $assoc_fkey = $assoc["fkey"];
        $assoc_extra_values = $assoc["extra_values"];
        $assoc_value_col = $assoc["value_col"];
        $assoc_single = (boolean)$assoc["single"];
        // assoc.fkeyの設定がなければ、assoc.tableのfkey_forを参照
        if ( ! isset($assoc_fkey)) {
            $table_name = $this->getAppTableName();
            $assoc_fkey = table($assoc_table_name)->getColNameByAttr("fkey_for", $table_name);
        }
        // singleの指定があれば1レコードに制限
        if ($assoc_single) $values = array_slice((array)$values, 0, 1, true);
        // 書き込んだIDを確認
        $id = null;
        if ($this->query->getType() == "insert") {
            $id = $this->result->getLastInsertId();
        } elseif ($this->query->getType() == "update") {
            $id = $this->query->getWhere($this->getQueryTableName().".".$this->getIdColName());
            if ( ! isset($id)) {
                $id = $this->query->getWhere($this->getIdColName());
            }
        }
        if ( ! isset($id)) {
            report_error("IDの特定できないUpdate/Insertに対してAssoc処理は実行できません",array(
                "table" => $this,
            ));
            return;
        }
        // 対象のIDに関係する関係先のレコードを差分削除
        $table = table($assoc_table_name)->findBy($assoc_fkey, $id);
        // ExtraValueを条件に設定
        if ($assoc_extra_values) $table->findBy($assoc_extra_values);
        $assoc_result = $table->select();
        $assoc_id_col = $table->getIdColName();
        // value_col指定=1項目の値のみに絞り込む場合
        if (isset($assoc_value_col)) {
            // 既存の情報を値→IDでハッシュ
            $delete_assoc_ids = $assoc_result->getHashedBy($assoc_value_col, $assoc_id_col);
            // singleの指定があれば削除対象の1レコード目を更新対象とする
            if ($assoc_single) {
                $record = array($assoc_fkey=>$id, $assoc_value_col=>current($values));
                if ($delete_assoc_ids) {
                    $record[$assoc_id_col] = current($delete_assoc_ids);
                    unset($delete_assoc_ids[key($delete_assoc_ids)]);
                }
                table($assoc_table_name)->save($record);
                $values = array();
            }
            foreach ((array)$values as $value) {
                // 入力値が登録済みであれば、削除対象から除外
                if (isset($delete_assoc_ids[$value])) {
                    unset($delete_assoc_ids[$value]);
                // 入力値が未登録であれば、新規登録
                } else {
                    $record = array($assoc_fkey=>$id, $assoc_value_col=>$value);
                    foreach ((array)$assoc_extra_values as $k=>$v) $record[$k] = $v;
                    table($assoc_table_name)->save($record);
                }
            }
            // 削除
            if ($delete_assoc_ids) {
                table($assoc_table_name)->findBy($assoc_fkey, $id)->findById($delete_assoc_ids)->deleteAll();
            }
        } else {
            // 既存の情報をID→IDでハッシュ
            $delete_assoc_ids = $assoc_result->getHashedBy($assoc_id_col, $assoc_id_col);
            // singleの指定があれば1レコード目を更新対象、その他を削除対象とする
            if ($assoc_single) {
                $values[key($values)][$assoc_id_col] = current($delete_assoc_ids);
            }
            foreach ((array)$values as $key => $record) {
                // 入力レコードのIDが空白で無ければ、削除対象から除外
                if (strlen($record[$assoc_id_col])) {
                    unset($delete_assoc_ids[$record[$assoc_id_col]]);
                }
                // 新規/上書き
                $record[$assoc_fkey] = $id;
                foreach ((array)$assoc_extra_values as $k=>$v) $record[$k] = $v;
                table($assoc_table_name)->save($record);
            }
            // 削除
            if ($delete_assoc_ids) {
                table($assoc_table_name)->findBy($assoc_fkey, $id)->findById($delete_assoc_ids)->deleteAll();
            }
        }
    }

// -- 基本的なsearch hookの定義

    /**
     * @hook search where
     * 一致、比較、IN（値を配列指定）
     */
    public function search_typeWhere ($form, $field_def, $value)
    {
        if ( ! isset($value)) {
            return false;
        }
        // 対象カラムは複数指定に対応
        $target_cols = $field_def["target_col"];
        if ( ! is_array($target_cols)) {
            $target_cols = array($target_cols);
        }
        $conditions_or = array();
        foreach ($target_cols as $i => $target_col) {
            $conditions_or[$i] = array($target_col => $value);
        }
        if (count($conditions_or)==0) {
            return false;
        }
        if (count($conditions_or)==1) {
            $this->query->where(array_pop($conditions_or));
        // 複数のカラムが有効であればはORで接続
        } elseif (count($conditions_or)>1) {
            $this->query->where(array("OR"=>$conditions_or));
        }
    }
    /**
     * @hook search word
     */
    public function search_typeWord ($form, $field_def, $value)
    {
        if ( ! isset($value)) {
            return false;
        }
        // 対象カラムは複数指定に対応
        $target_cols = $field_def["target_col"];
        if ( ! is_array($target_cols)) {
            $target_cols = array($target_cols);
        }
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
        if (count($conditions_or)==0) {
            return false;
        }
        if (count($conditions_or)==1) {
            $this->query->where(array_pop($conditions_or));
        // 複数のカラムが有効であればはORで接続
        } elseif (count($conditions_or)>1) {
            $this->query->where(array("OR"=>$conditions_or));
        }
    }
    /**
     * @hook search exists
     * 別Tableをサブクエリとして条件指定する
     */
    public function search_typeExists ($form, $field_def, $value)
    {
        if ( ! isset($value)) {
            return false;
        }
        $table = table($field_def["search_table"]);
        $table->findBy($this->getQueryTableName().".".$this->getIdColName()."=".$table->getQueryTableName().".".$field_def["fkey"]);
        $table->findBySearchFields($form, $field_def["search_fields"]);
        $this->query->where("EXISTS(".$table->buildQuery("select").")");
    }
    /**
     * @hook search sort
     */
    public function search_typeSort ($form, $field_def, $value)
    {
        if ( ! isset($value) && isset($field_def["default"])) {
            $value = $field_def["default"];
        }
        if (preg_match('!^(\w+(?:\.\w+)?)(?:@(ASC|DESC))?!',$value,$match)) {
            $col_name = $match[1];
            $col_name .= $match[2]=="DESC" ? " DESC" : "";
            $this->query->addOrder($col_name);
        } else {
            return false;
        }
    }
    /**
     * @hook search page
     */
    public function search_typePage ($form, $field_def, $value)
    {
        // 1ページの表示件数
        $volume = $field_def["volume"];
        if ( ! $volume) {
            // 指定済みのlimitにより補完
            if ($limit = $this->query->getLimit()) {
                $volume = $limit;
            // 指定が無ければ20件とみなす
            } else {
                $volume = 20;
            }
        }
        // 1ページ目
        if ( ! $value) {
            $value = 1;
        }
        $this->query->setOffset(($value-1)*$volume);
        $this->query->setLimit($volume);
    }

// -- 基本的なID生成ルールの定義

    /**
     * ランダム文字列で生成
     */
    protected function generator_randString ($col_name)
    {
        $col_def = $this->getColDef($col_name);
        $length = $col_def["length"] ?: 32;
        $chars = str_split('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $value = "";
        for ($i=0; $i<$length; $i++) $value .= $chars[array_rand($chars)];
        return $value;
    }

// -- 基本的な認証処理の定義

    /**
     * @ref R\Lib\Auth\ConfigBasedLogin::authenticate
     * ログイン処理の実装
     */
    public function authenticate ($params)
    {
        if ($params["type"]=="idpw" && strlen($params["login_id"]) && strlen($params["login_pw"])) {
            $t = $this->findByLoginIdPw($params["login_id"], $params["login_pw"])->selectOne();
            return $t ? (array)$t : false;
        }
        return false;
    }
}
