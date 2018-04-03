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
     * Insert/Update文のValues部を設定する
     */
    public function chain_values ($values)
    {
        $this->query->setValues($values);
    }
    /**
     * @hook chain
     * Select文のField部を指定する
     */
    public function chain_fields ($fields)
    {
        $this->query->setFields($fields);
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
        foreach ($result as $record) $record[$merge_col_name] = $values[$record->getColValue($key_col_name)];
        return $result;
    }
    /**
     * @hook result
     * 各レコードの特定カラムのみの配列を取得する
     */
    public function result_getHashedBy ($result, $col_name, $col_name_sub=false, $col_name_sub_ex=false)
    {
        $hashed_result = array();
        foreach ($result as $record) {
            if ($col_name_sub === false) {
                $hashed_result[] = $record->getColValue($col_name);
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
     * @hook result
     * 各レコードを特定のユニークカラムで添え字を書き換えた配列を取得する
     */
    public function result_getMappedBy ($result, $col_name, $col_name_sub=false)
    {
        $mapped_result = array();
        foreach ($result as $record) {
            if ($col_name_sub === false) $mapped_result[$record->getColValue($col_name)] = $record;
            else $mapped_result[$record->getColValue($col_name)][$record->getColValue($col_name_sub)] = $record;
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
                $grouped_result[$record->getColValue($col_name)][] = $record;
            } else {
                $grouped_result[$record->getColValue($col_name)]
                    [$record->getColValue($col_name_sub)][]= $record;
            }
        }
        return $grouped_result;
    }
    /**
     * @hook result
     * 外部キーを対象の主キーでの検索条件に設定した状態で、BelongsTo関係にあるTableを取得する
     */
    public function result_getBelongsToTable ($result, $assoc_table_name, $assoc_fkey=false)
    {
        $assoc_table = table($assoc_table_name);
        $assoc_fkey = $assoc_fkey ?: $this->getColNameByAttr("fkey_for", $assoc_table_name);
        if ( ! $assoc_fkey) {
            report_error("Table間にBelongsTo関係がありません",array(
                "table"=>$this,
                "assoc_table"=>$assoc_table,
            ));
        }
        $assoc_ids = $result->getHashedBy($assoc_fkey);
        return $assoc_table->findById($assoc_ids);
    }
    /**
     * @hook result
     * 主キーを対象の外部キーでの検索条件に設定した状態で、HasMany関係にあるTableを取得する
     */
    public function result_getHasManyTable ($result, $assoc_table_name, $assoc_fkey=false)
    {
        $assoc_table = table($assoc_table_name);
        $assoc_fkey = $assoc_fkey ?: $assoc_table->getColNameByAttr("fkey_for", $this->getAppTableName());
        if ( ! $assoc_fkey) {
            report_error("Table間にHasMany関係がありません",array(
                "table"=>$this,
                "assoc_table"=>$assoc_table,
            ));
        }
        $assoc_ids = $result->getHashedBy($this->getIdColName());
        return $assoc_table->findBy($assoc_fkey, $assoc_ids);
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
        // 主テーブルの取得件数が0件であれば処理を行わない
        if (count($result) === 0) return false;
        // assoc.fkeyの設定がなければ、assoc.tableのfkey_forを参照
        if ( ! isset($assoc_fkey)) {
            $table_name = $this->getAppTableName();
            $assoc_fkey = table($assoc_table_name)->getColNameByAttr("fkey_for", $table_name);
        }
        $table = $result->getHasManyTable($assoc_table_name, $assoc_fkey);
        // ExtraValueを条件に設定
        if ($assoc_extra_values) $table->findBy($assoc_extra_values);
        // joinの指定があればJOINを接続
        if ($assoc_join) $table->join($assoc_join[0], $assoc_join[1]);
        // singleの指定があればレコード数に制限
        if ($assoc_single) $table->limit(count($result));
        $assoc_result_set = $table->select()->getGroupedBy($assoc_fkey);
        $pkey = $this->getIdColName();
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

// -- on_* 基本的な定義

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
                $this->query->setValue($col_name, app()->security->passwordHash($value));
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
     * @hook on_insert
     * ランダム文字列からIDを生成
     */
    protected function on_insert_generatorRandString ()
    {
        if ($col_name = $this->getColNameByAttr("generator", "randString")) {
            if ($this->query->getValue($col_name) !== null) return false;
            $col_def = $this->getColDef($col_name);
            $length = $col_def["length"] ?: 32;
            $chars = str_split('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
            $value = "";
            for ($i=0; $i<$length; $i++) $value .= $chars[array_rand($chars)];
            $this->query->setValue($col_name, $value);
        } else {
            return false;
        }
    }
    /**
     * @hook on_write
     * id_initの値からIDを生成
     */
    protected function on_write_generatorIdInit ()
    {
        if ($col_name = $this->getColNameByAttr("generator", "idInit")) {
            $col_def = $this->getColDef($col_name);
            $id_init_col_name = $col_def["id_init_col"] ?: "id_init";
            $value = $this->query["values"][$id_init_col_name];
            unset($this->query["values"][$id_init_col_name]);
            if ($this->query->getType() == "update") return true;
            if ($this->query->getValue($col_name) !== null) return true;
            $this->query->setValue($col_name, $value);
        } else {
            return false;
        }
    }
    /**
     * @hook on_getBlankCol
     * retreiveメソッドが定義されていたら参照する
     */
    protected function on_getBlankCol_retreive ($record, $col_name)
    {
        // メソッドの探索
        $method_name = "retreive_col".str_camelize($col_name);
        if ( ! method_exists($this, $method_name)) return false;
        // idsを引数に呼び出し
        $ids = $this->result->getHashedBy($this->getIdColName());
        $values = call_user_func(array($this,$method_name), $ids);
        // 結果を統合する
        $this->result->mergeBy($col_name, $values);
        // 値の設定漏れがあった場合はnullで埋める
        foreach ($this->result as $a_record) {
            if ( ! isset($a_record[$col_name])) $a_record[$col_name] = null;
        }
        return true;
    }
    /**
     * @hook on_getBlankCol
     * aliasが定義されていたら参照する
     */
    protected function on_getBlankCol_alias ($record, $col_name)
    {
        foreach (static::$cols as $src_col_name=>$src_col) {
            foreach ((array)$src_col["alias"] as $alias_col_name=>$alias) {
                if ($alias_col_name===$col_name) {
                    if ( ! $alias["type"] && $alias["enum"]) $alias["type"] = "enum";
                    $alias["src_col_name"] = $src_col_name;
                    $alias["alias_col_name"] = $alias_col_name;
                    $method_name = "retreive_alias".str_camelize($alias["type"]);
                    if ( ! method_exists($this, $method_name)) {
                        report_error("aliasに対応する処理がありません",array(
                            "table"=>$this, "alias"=>$alias, "method_name"=>$method_name,
                        ));
                    }
                    // 値を引数に呼び出し
                    $src_values = $this->result->getHashedBy($src_col_name);
                    $values = call_user_func(array($this,$method_name), $src_values, $alias);
                    // 結果を統合する
                    $this->result->mergeBy($alias_col_name, $values);
                    // 値の設定漏れがあった場合はnullで埋める
                    foreach ($this->result as $a_record) {
                        if ( ! isset($a_record[$col_name])) $a_record[$col_name] = null;
                    }
                    return true;
                }
            }
        }
    }
    /**
     * @hook retreive_alias
     * aliasにtype指定がない場合の処理
     */
    protected function retreive_aliasEnum ($src_values, $alias)
    {
        // 指定が不正
        if ( ! $alias["enum"] || ! app()->enum[$alias["enum"]]) {
            report_error("aliasで指定されるenumがありません",array(
                "enum"=>$alias["enum"], "table"=>$this, "alias"=>$alias,
            ));
        // checklistのように対象の値が複数となっている
        } elseif ($alias["glue"]) {
            $reduced = array_reduce($src_values, function($result, $item){
                return array_merge($result, array_values($item));
            }, array());
            app()->enum[$alias["enum"]]->retreive($reduced);
            $dest_values = array();
            foreach ($src_values as $k=>$v) {
                $dest_values[$k] = implode($alias["glue"], $app()->enum[$alias["enum"]]->map($v));
            }
            return $dest_values;
        } else {
            return app()->enum[$alias["enum"]]->map($src_values);
        }
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
        $result = false;
        foreach ($this->getColNamesByAttr("type", "geometry") as $col_name) {
            $value = $this->query->getValue($col_name);
            if (isset($value)) {
                if (preg_match('!\d+(\.\d+)?\s*,\s*\d+(\.\d+)?!', $value, $match)) {
                    $this->query->removeValue($col_name);
                    $this->query->setValue($col_name."=", 'POINT('.$match[0].')');
                    $result = true;
                } else {
                    $this->query->setValue($col_name, null);
                    $result = true;
                }
            }
        }
        return $result;
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

// -- on_* assoc仮想カラム処理 write+0/read-0

    /**
     * assoc指定されたFeieldに対応するValues
     */
    private $assoc_values = null;
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
     * assoc処理 未初期化のRecord値の取得時
     */
    protected function on_getBlankCol_assoc ($record, $col_name)
    {
        if (static::$cols[$col_name]["assoc"]) {
            $this->callAssocHookMethod("assoc_getBlankCol", $col_name);
            return true;
        }
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
        foreach ((array)$this->assoc_values as $col_name => $value) {
            $this->callAssocHookMethod("assoc_afterWrite", $col_name, array($value));
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
    protected function assoc_getBlankCol_hasMany ($col_name)
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
                foreach ((array)$assoc_extra_values as $k=>$v) $record[$k] = $v;
                if ($delete_assoc_ids) {
                    $record[$assoc_id_col] = current($delete_assoc_ids);
                    unset($delete_assoc_ids[key($delete_assoc_ids)]);
                }
                table($assoc_table_name)->save($record);
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
                        table($assoc_table_name)->save($record);
                    }
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
        $cols = array();
        // @deprecated 旧仕様との互換処理
        if (isset($field_def["default"])) array_unshift($cols, $field_def["default"]);
        // colsの解析
        foreach ((array)$field_def["cols"] as $k=>$v) {
            if ( ! isset($value)) $value = $v;
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
        $this->query->addOrder($value);
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

// -- 基本的な認証処理の定義

    /**
     * ログイン処理の実装
     */
    public function authByLoginIdPw ($login_id, $login_pw)
    {
        $login_id_col_name = $this->getColNameByAttr("login_id");
        $login_pw_col_name = $this->getColNameByAttr("login_pw");
        if ( ! $login_id_col_name || ! $login_pw_col_name) {
            report_error("login_id,login_pwカラムがありません",array(
                "table" => $this,
            ));
        }
        $this->query->where($this->getQueryTableName().".".$login_id_col_name, (string)$login_id);
        if (static::$cols[$login_pw_col_name]["hash_pw"]) {
            $this->with($this->getQueryTableName().".".$login_pw_col_name, $login_pw_col_name."_hash");
        }
        $record = $this->selectOne();
        if (static::$cols[$login_pw_col_name]["hash_pw"]) {
            if ( ! app()->security->passwordVerify($login_pw, $record[$login_pw_col_name."_hash"])) return null;
         } else {
             if ($login_pw != $record[$login_pw_col_name]) return false;
         }
         return $record;
    }
    /**
     * @hook chain
     * ログインID/PWを条件に指定する
     * @deprecated
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
            $login_pw = app()->security->passwordHash($login_pw);
        }
        $this->query->where($this->getQueryTableName().".".$login_id_col_name, (string)$login_id);
        $this->query->where($this->getQueryTableName().".".$login_pw_col_name, (string)$login_pw);
    }
    /**
     * @hook chain
     * 現在のRoleのTableに対して所有関係があることを条件として指定する
     */
    public function chain_findMine ()
    {
        $role = app()->user->getCurrentRole();
        $user_id = app()->user->id($role);
        $role_table_name = app()->user->getAuthTable($role);
        if ( ! $role_table_name) {
            report_error("Roleに対応するTableがありません", array("role"=>$role));
        }
        $col_name = $role_table_name == $this->getAppTableName()
            ? $this->getIdColName() : $this->getColNameByAttr("fkey_for", $role_table_name);
        if ( ! $col_name) {
            report_error("RoleのTableに対する所有関係を示すキーの設定がありません",
                array("role_tabel"=>$role_table_name, "table"=>$this));
        }
        if ($user_id) {
            // ログイン中のIDを条件を追加する
            $this->query->where($this->getQueryTableName().".".$col_name, $user_id);
        } else {
            // ログイン中でなければ何も取得しない
            $this->findNothing();
        }
    }
    /**
     * 現在のRoleのTableに対して所有関係があることを前提にsaveを実行する
     */
    public function saveMine ()
    {
        $role = app()->user->getCurrentRole();
        $user_id = app()->user->id($role);
        $role_table_name = app()->user->getAuthTable($role);
        $id_col_name = $this->getIdColName();
        if ( ! $role_table_name) {
            report_error("Roleに対応するTableがありません", array("role"=>$role));
        }
        if ( ! $user_id) {
            report_error("非ログイン中のsaveMineの呼び出しは不正です", array("table"=>$this));
        }
        // Roleのテーブル自身である場合は、主キーを指定
        if ($role_table_name == $this->getAppTableName()) {
            $this->query->setValue($id_col_name, $user_id);
        // 外部キーで参照されている場合
        } elseif ($fkey_col_name = $this->getColNameByAttr("fkey_for", $role_table_name)) {
            // Updateが発行される場合は、Whereを指定
            if ($this->query->getValue($id_col_name)) {
                $this->query->removeValue($fkey_col_name);
                $this->query->setWhere($fkey_col_name, $user_id);
            // Insertが発行される場合は、Valueに指定
            } else {
                $this->query->setValue($fkey_col_name, $user_id);
            }
        } else {
            report_error("RoleのTableに対する所有関係を示すキーの設定がありません",
                array("role_tabel"=>$role_table_name, "table"=>$this));
        }
        // saveを呼び出す
        return $this->save();
    }
}
