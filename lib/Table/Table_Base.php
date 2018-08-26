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
        // Tableに変換する
        if (is_array($table)) list($table, $alias) = $table;
        if (is_string($table)) $table = table($table);
        if ($alias) $table->alias($alias);
        $this->query->join($table, $on, $type);
    }
    /**
     * @hook chain
     * JOIN句の設定 主テーブル側が持つ外部キーでJOIN
     */
    public function chain_joinBelongsToOld ($table, $fkey=null, $type="LEFT")
    {
        // Tableに変換する
        if (is_array($table)) list($table, $alias) = $table;
        if (is_string($table)) $table = table($table);
        if ($alias) $table->setAlias($alias);
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
     * 絞り込み条件にEXSISTSを指定する
     */
    public function chain_findByExists ($table)
    {
        $this->query->where("EXISTS(".$table->buildQuery("select").")");
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
            $result = call_user_func(array($this,$search_method_name), $form, $field_def, $value);
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
            $result = call_user_func(array($this,$search_method_name), $form, $yield);
            if ($result!==false) $applied = true;
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
    /**
     * @hook result
     * Resultに対してassocに従って値を反映する
     */
    public function result_affectAssoc ($result, $col_name, $assoc, $values)
    {
        $assoc_table_name = $assoc["table"];
        $assoc_fkey = $assoc["fkey"];
        $assoc_extra_values = $assoc["extra_values"];
        $assoc_value_col = $assoc["value_col"];
        $assoc_single = (boolean)$assoc["single"];
        // assoc.fkeyの設定がなければ、assoc.tableのfkey_forを参照
        if ( ! isset($assoc_fkey)) {
            $table_name = $this->getAppTableName();
            $assoc_fkey = table($assoc_table_name)->getColNameByAttr("fkey_for", $table_name);
            if ( ! $assoc_fkey) {
                report_error("外部キーによる参照がないassoc関係", array(
                    "table_name"=>$this->getAppTableName(),
                    "assoc_table_name"=>$assoc_table_name,
                    "assoc"=>$assoc,
                ));
            }
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
     * @hook on_write
     * notnullでdefault値があるカラムにNULLを入れた際に値を補完する
     */
    protected function on_write_setDefaultValueInNotnull ()
    {
        $result = false;
        foreach ((array)$this->query->getValues() as $col_name=>$value) {
            if ($value === null) {
                if (static::$cols[$col_name]["notnull"] && isset(static::$cols[$col_name]["default"])) {
                    $this->query->setValue($col_name, static::$cols[$col_name]["default"]);
                    $result = true;
                }
            }
        }
        return $result;
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
        // resultを引数に呼び出し
        $values = call_user_func(array($this,$method_name), $this->result);
        foreach ($this->result as $key=>$a_record) $a_record[$col_name] = $values[$key];
        return true;
    }
    /**
     * @hook on_getBlankCol
     * aliasが定義されていたら参照する
     */
    protected function on_getBlankCol_alias ($record, $col_name)
    {
        $found = false;
        foreach ((array)static::$aliases as $src_col_name=>$aliases) {
            foreach ((array)$aliases as $alias_col_name=>$alias) {
                if ($alias_col_name===$col_name) {
                    if ($found) {
                        report_error("同名のaliasが重複して登録されています", array(
                            "table"=>$this->getAppTableName(),
                            "alias_col_name"=>$alias_col_name,
                            "src_col_name_1"=>$found[1],
                            "src_col_name_2"=>$src_col_name,
                        ));
                    }
                    $found = array($alias_col_name, $src_col_name, $alias);
                }
            }
        }
        // @deprecated 旧仕様に従ってcolのaliasを参照する機能も残す
        foreach ((array)static::$cols as $src_col_name=>$src_col) {
            foreach ((array)$src_col["alias"] as $alias_col_name=>$alias) {
                if ($alias_col_name===$col_name) {
                    if ($found) {
                        report_error("同名のaliasが重複して登録されています", array(
                            "table"=>$this->getAppTableName(),
                            "alias_col_name"=>$alias_col_name,
                            "src_col_name_1"=>$found[1],
                            "src_col_name_2"=>$src_col_name,
                        ));
                    }
                    $found = array($alias_col_name, $src_col_name, $alias);
                }
            }
        }
        if ( ! $found) return false;
        $this->mergeAlias($found[0], $found[1], $found[2]);
        return true;
    }
    /**
     * @hook result
     * aliasを適用する
     */
    protected function mergeAlias ($alias_col_name, $src_col_name, $alias)
    {
        if ( ! $alias["type"] && $alias["enum"]) $alias["type"] = "enum";
        $alias["src_col_name"] = $src_col_name;
        $alias["alias_col_name"] = $alias_col_name;
        $method_name = "retreive_alias".str_camelize($alias["type"]);
        if ( ! method_exists($this, $method_name)) {
            report_error("aliasに対応する処理がありません",array(
                "table"=>$this, "alias"=>$alias, "method_name"=>$method_name,
            ));
        }
        // 値を引数に呼び出し f({i=>v1})=>{v1=>v2}
        $src_values = $this->result->getHashedBy($src_col_name);
        $dest_values = self::mapReduce(array($this, $method_name), $src_values, $alias);
        // 結果を統合する
        foreach ($this->result as $record) {
            $key = self::encodeKey($record->getColValue($src_col_name));
            $record[$alias_col_name] = $dest_values[$key];
        }

        app("events")->fire("table.merge_alias", array($this, $this->statement,
            $this->result, $src_col_name, $alias_col_name, $dest_values));
    }
    protected static function mapReduce ($callback, $src_values, $alias)
    {
        // checklistのように対象の値が複数となっている
        if ($alias["array"] || $alias["glue"]) {
            $reduced = array_reduce($src_values, function($reduced, $src_value){
                return array_merge($reduced, array_values((array)$src_value));
            }, array());
            $map = call_user_func($callback, $reduced, $alias);
            $dest_values = array();
            foreach ($src_values as $src_value) {
                $key = self::encodeKey($src_value);
                $dest_values[$key] = array();
                foreach ((array)$src_value as $k=>$v) $dest_values[$key][$k] = $map[$v];
                if ($alias["glue"]) $dest_values[$key] = implode($glue, $dest_values[$key]);
            }
            return $dest_values;
        } else {
            $dest_values = call_user_func($callback, $src_values, $alias);
            return $dest_values;
        }
    }
    protected static function encodeKey($key)
    {
        return (is_array($key) || is_object($key)) ? json_encode($key) : "".$key;
    }
    /**
     * @hook retreive_alias
     * aliasにenum指定がある場合の処理
     */
    protected function retreive_aliasEnum ($src_values, $alias)
    {
        // 指定が不正
        if ( ! $alias["enum"] || ! app()->enum[$alias["enum"]]) {
            report_error("aliasで指定されるenumがありません",array(
                "enum"=>$alias["enum"], "table"=>$this, "alias"=>$alias,
            ));
        }
        return app()->enum[$alias["enum"]]->map($src_values);
    }
    /**
     * @hook retreive_alias
     * hasMany関係先テーブルの情報を1件のみ取得
     */
    protected function retreive_aliasHasOne ($src_values, $alias)
    {
        $alias["single"] = true;
        return $this->retreive_aliasHasMany($src_values, $alias);
    }
    /**
     * @hook retreive_alias
     * hasMany関係先テーブルの情報を取得
     */
    protected function retreive_aliasHasMany ($src_values, $alias)
    {
        if ( ! $alias["table"]) {
            report_error("aliasで指定されるtableがありません",array(
                "table"=>$this, "assoc_table"=>$alias["table"], "alias"=>$alias
            ));
        }
        $assoc_table = table($alias["table"]);
        $assoc_fkey = $alias["fkey"]
            ?: $assoc_table->getColNameByAttr("fkey_for", $this->getAppTableName());
        if ( ! $assoc_fkey) {
            report_error("Table間にHasMany関係がありません",array(
                "table"=>$this, "assoc_table"=>$assoc_table, "alias"=>$alias,
            ));
        }
        $assoc_table->findBy($assoc_fkey, $src_values);
        if ($alias["mine"]) $assoc_table->findMine();
        if ($alias["where"]) $assoc_table->findBy($alias["where"]);
        if ($alias["order"]) $assoc_table->orderBy($alias["order"]);
        if ($alias["limit"]) $assoc_table->limit($alias["limit"]);
        if ($alias["summary"]) return $assoc_table->selectSummary($alias["summary"], $assoc_fkey);
        $result = $assoc_table->select();
        if ($alias["single"]) return $result->getMappedBy($assoc_fkey);
        return $result->getGroupedBy($assoc_fkey);
    }
    /**
     * @hook retreive_alias
     * belongsTo関係先テーブルの情報を取得
     */
    protected function retreive_aliasBelongsTo ($src_values, $alias)
    {
        if ( ! $alias["table"]) {
            report_error("aliasで指定されるtableがありません",array(
                "table"=>$this, "assoc_table"=>$alias["table"], "alias"=>$alias
            ));
        }
        $assoc_table = table($alias["table"]);
        $assoc_table->findBy($assoc_table->getIdColName(), $src_values);
        if ($alias["mine"]) $assoc_table->findMine();
        if ($alias["where"]) $assoc_table->findBy($alias["where"]);
        return $assoc_table->select()->getMappedBy($assoc_table->getIdColName());
    }
    /**
     * @hook retreive_alias
     * alias type=summaryの処理 集計結果を対応づける
     *      - required table, key, value
     *      - optional joins, where, key_sub
     */
    public function retreive_aliasSummary ($src_values, $alias)
    {
        $q = table($alias["table"]);
        $q->findBy($alias["key"], $src_values);
        foreach ((array)$alias["joins"] as $join) $q->join($join);
        if ($alias["where"]) $q->findBy($alias["where"]);
        return $q->selectSummary($alias["value"], $alias["key"], $alias["key_sub"] ?: false);
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
        $result = false;
        foreach ($this->getColNamesByAttr("type", "geometry") as $col_name) {
            if (isset($record[$col_name])) {
                $unpacked = unpack('Lpadding/corder/Lgtype/dlatitude/dlongitude', $record[$col_name]);
                $record[$col_name] = $unpacked["latitude"]." , ".$unpacked["longitude"];
                $result = true;
            }
        }
        return $result;
    }

// -- on_* assoc仮想カラム処理 write+0/read-0

    /**
     * assoc指定されたFeieldに対応するValues
     */
    protected $assoc_values = null;
    /**
     * assoc hook処理の呼び出し
     */
    protected function callAssocHookMethod ($method_name, $col_name, $args=array())
    {
        $assoc = static::$cols[$col_name]["assoc"];
        $method_name .= "_".($assoc["type"] ?: "hasMany");
        if ( ! method_exists($this, $method_name)) return false;
        array_unshift($args, $col_name);
        return $this->callHookMethod($method_name, $args);
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
        return false;
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
        return $this->result->mergeAssoc($col_name, static::$cols[$col_name]["assoc"]);
    }
    protected function assoc_afterWrite_hasMany ($col_name)
    {
        $values = $this->assoc_values[$col_name];
        return $this->result->affectAssoc($col_name, static::$cols[$col_name]["assoc"], $values);
    }

// -- 基本的なsearch hookの定義

    /**
     * @hook search where
     * 一致、比較、IN（値を配列指定）
     */
    public function search_typeWhere ($form, $field_def, $value)
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
        $query_part = $field_def["having"] ? "having" : "where";
        if (count($conditions_or)==1) $this->query[$query_part][] = array_pop($conditions_or);
        // 複数のカラムが有効であればはORで接続
        elseif (count($conditions_or)>1) $this->query[$query_part][] = array("OR"=>$conditions_or);
    }
    /**
     * @hook search word
     */
    public function search_typeWord ($form, $field_def, $value)
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
        $query_part = $field_def["having"] ? "having" : "where";
        if (count($conditions_or)==1) $this->query[$query_part][] = array_pop($conditions_or);
        // 複数のカラムが有効であればはORで接続
        elseif (count($conditions_or)>1) $this->query[$query_part][] = array("OR"=>$conditions_or);
    }
    /**
     * @deprecated search_yieldExists
     * @hook search exists
     * 別Tableをサブクエリとして条件指定する
     */
    public function search_typeExists ($form, $field_def, $value)
    {
        if ( ! isset($value)) return false;
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
        foreach ($value as $a_value) $this->query->addOrder($a_value);
    }
    /**
     * @hook search page
     */
    public function search_typePage ($form, $field_def, $value)
    {
        // 1ページの表示件数
        $volume = $field_def["volume"];
        // 指定済みのlimitにより補完, 指定が無ければ20件とみなす
        if ( ! $volume) $volume = $this->query->getLimit() ?: 20;
        // 1ページ目
        if ( ! $value) $value = 1;
        $this->query->setOffset(($value-1)*$volume);
        $this->query->setLimit($volume);
    }
    /**
     * @hook search_yield exists
     * 別Tableをサブクエリとして条件指定する
     */
    public function search_yieldExists ($form, $yield)
    {
        $table = table($yield["table"]);
        if ($yield["on"]) {
            $table->findBy($yield["on"]);
        } else {
            $fkey = $yield["fkey"] ?: $table->getColNameByAttr("fkey_for", $this->getAppTableName());
            $table->findBy($this->getQueryTableName().".".$this->getIdColName()."=".$table->getQueryTableName().".".$fkey);
        }
        if ($yield["joins"]) foreach ($yield["joins"] as $join) $table->join($join);
        if ($yield["where"]) $table->findBy($yield["where"]);
        $result = $table->chain_findBySearchFields($form, $yield["search_fields"]);
        if ($result) $this->query->where("EXISTS(".$table->buildQuery("select").")");
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
        $result = app()->user->onFindMine($role, $this);
        if ( ! $result) $result = self::defaultOnFindMine($role, $this);
        if ( ! $result) {
            $this->findNothing();
        }
    }
    /**
     * 現在のRoleのTableに対して所有関係があることを前提にsaveを実行する
     */
    public function saveMine ()
    {
        $role = app()->user->getCurrentRole();
        $result = app()->user->onSaveMine($role, $this);
        if ( ! $result) $result = self::defaultOnSaveMine($role, $this);
        if ( ! $result) {
            report_error("無効なsaveMine", array("role_tabel"=>$role, "table"=>$this));
        } else {
            $this->save();
        }
    }
    protected static function defaultOnFindMine ($role, $table)
    {
        $user_id = app()->user->id($role);
        // ログイン中でなければ何も取得しない
        if ( ! $user_id) return false;
        $role_table_name = app()->user->getAuthTable($role);
        if ( ! $role_table_name) return false;
        // 自己参照の特定
        if ($table->getAppTableName() == $role_table_name) {
            // ログイン中のID = 主キーを条件に追加する
            $table->query->where($table->getQueryTableName().".".$table->getIdColName(), $user_id);
        // 関係先を経由して条件を指定
        } elseif ($table->chain_findByRoute($role_table_name, $user_id)) {
            //
        } else {
            report_warning("無効なfindMine, 所有関係を示す経路がありません",
                array("role_tabel"=>$role_table_name, "table"=>$table));
            return false;
        }
        return true;
    }
    protected static function defaultOnSaveMine ($role, $table)
    {
        $user_id = app()->user->id($role);
        $role_table_name = app()->user->getAuthTable($role);
        $id_col_name = $table->getIdColName();
        $fkey_col_name = $table->getColNameByAttr("fkey_for", $role_table_name);
        if ( ! $role_table_name) return false;
        if ( ! $user_id) {
            report_warning("非ログイン中のsaveMineの呼び出しは不正です", array("table"=>$table));
            return false;
        }
        // Roleのテーブル自身である場合は、主キーを指定
        if ($role_table_name == $table->getAppTableName()) {
            $table->query->setValue($id_col_name, $user_id);
        // 関係がある場合
        } elseif (app("table.resolver")->getFkeyRoute($table->getAppTableName(), $role_table_name)) {
            // 直接関係があればValueを上書き
            if ($fkey_col_name) $table->query->setValue($fkey_col_name, $user_id);
            // Updateが発行される場合、関係先を探索して条件に追加
            if ($table->query->getValue($id_col_name)) {
                $table->chain_findByRoute($role_table_name, $user_id);
            // Insertであり、直接関係がない場合エラー
            } elseif ( ! $fkey_col_name) {
                report_warning("無効なsaveMine, 直接関係がなければ新規作成を行う条件の指定は出来ません",
                    array("role_tabel"=>$role_table_name, "table"=>$table));
                return false;
            }
        } else {
            report_warning("無効なsaveMine, 所有関係を示す経路がありません",
                array("role_tabel"=>$role_table_name, "table"=>$table));
            return false;
        }
        return true;
    }
    /**
     * 経路を探索して指定した関係先テーブルの値を条件に指定する
     *
     * @param string $target_table_name 関係先テーブル名
     * @param mixed $value 条件に指定する値
     * @param string $col_name 値を対応づけるカラム名。指定がない場合、主キーを対応づける
     * @return bool
     */
    public function chain_findByRoute($target_table_name, $value, $col_name=false)
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
                $this->query->where($edge[0].".".$edge[1], $value);
            // 経由関係先への参照は、JOINを指定する
            } else {
                $join_table = table($edge[2]);
                // ASの解決
                if ($edge["as"]) $join_table->alias($edge["as"]);
                $join_table_name = $join_table->getQueryTableName();
                // Join済みであれば以降は対応付けを行わない
                if ($this->query->getJoinByName($join_table_name)) break;
                $on = array($edge[0].".".$edge[1]."=".$join_table_name.".".$edge[3]);
                // 目的関係先の主キー以外のカラム＝値を条件に指定する
                if ($edge[2] == $target_table_name && $col_name!==false) {
                    $on[] = array($join_table_name.".".$col_name, $value);
                }
                // 経由関係先をJoin登録する
                $this->query->join($join_table, $on);
            }
            // 追加条件を指定する
            if ($edge[4]) $this->query->where($edge[4]);
        }
        return true;
    }
    /**
     * @hook chain
     * JOIN句の設定 主テーブル側が持つ外部キーでJOIN
     */
    public function chain_joinBelongsTo ($target_table_name, $fkey=false)
    {
        if (is_array($target_table_name)) list($target_table_name, $target_as) = $target_table_name;
        // 経路を取得
        $self_table_name = $this->getAppTableName();
        $route = app("table.resolver")->getFkeyRoute($self_table_name, $target_table_name);
        // 経路が存在しない場合は処理を行わない
        if ( ! $route) return false;
        // 目的関係先に近い順に登録する
        foreach (array_reverse($route) as $edge) {
            $join_table = table($edge[2]);
            // 関係元からの参照であれば、テーブルの名前はクエリ内のものを使用する
            if ($edge[0] == $self_table_name) $edge[0] = $this->getQueryTableName();
            // ASの解決
            if ($edge[2] == $target_table_name && $target_as) $join_table->alias($target_table_as);
            elseif ($edge["as"]) $join_table->alias($edge["as"]);
            $join_table_name = $join_table->getQueryTableName();
            // Join済みであれば以降は対応付けを行わない
            if ($this->query->getJoinByName($join_table_name)) break;
            $on = array($edge[0].".".$edge[1]."=".$join_table_name.".".$edge[3]);
            // 経由関係先をJoin登録する
            $this->query->join($join_table, $on);
            // 追加条件を指定する
            if ($edge[4]) $this->query->where($edge[4]);
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
                $record = table($assoc_table_name)
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
