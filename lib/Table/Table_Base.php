<?php
namespace R\Lib\Table;

/**
 * Tableクラスの継承元
 */
class Table_Base extends Table_Core
{

// -- 基本的なassoc hookの定義

    /**
     * @hook assoc hasMany
     * パラメータ例:
     *     "assoc"=>"hasMany",
     *     "table"=>"Product", // 必須 関係先テーブル名
     *     "fkey"=>"owner_member_id", // 必須 関係先テーブル上のFK
     *  読み込み時の動作:
     *      Fetch完了後、結果全てのPKで関係先テーブルをSelectする
     *  書き込み時の動作:
     *      ※書き込んだIDがわかることが必須なので、IDを指定しないUpdateではエラー
     *      対象のIDに関係する関係先のレコードを全件削除
     *      登録対象のレコードを順次Insert
     */
    public function assoc_fetch_hasMany ($col_name)
    {
        $assoc_table_name = static::$cols[$col_name]["table"];
        $assoc_fkey = static::$cols[$col_name]["fkey"];
        if ( ! $assoc_table_name || ! $assoc_fkey) {
            report_error("パラメータの指定が不足しています",array(
                "col_name" => $col_name,
                "params" => static::$cols[$col_name],
                "required" => array("table","fkey"),
                "table" => $this,
            ));
        }
        // 主テーブルのIDを取得
        $pkey = $this->getIdColName();
        $ids = $this->result->getHashedBy($pkey);
        // 関連テーブルをFkeyでSELECT
        $assoc_result_set = table($assoc_table_name)
            ->findBy($assoc_fkey, $ids)
            ->select()
            ->getGroupedBy($assoc_fkey);
        // 主テーブルのResultに関連づける
        foreach ($this->result as $i => $record) {
            $this->result[$i][$col_name] = (array)$assoc_result_set[$record[$pkey]];
        }
    }
    public function assoc_afterWrite_hasMany ($col_name, $values)
    {
        $assoc_table_name = static::$cols[$col_name]["table"];
        $assoc_fkey = static::$cols[$col_name]["fkey"];
        if ( ! $assoc_table_name || ! $assoc_fkey) {
            report_error("パラメータの指定が不足しています",array(
                "col_name" => $col_name,
                "params" => static::$cols[$col_name],
                "required" => array("table","fkey"),
                "table" => $this,
            ));
        }
        // 書き込んだIDを確認
        $id = $this->result->getLastSaveId();
        // 対象のIDに関係する関係先のレコードを全件削除
        table($assoc_table_name)
            ->findBy($assoc_fkey, $id)
            ->deleteAll();
        // 複数レコードの同時Updateに対応
        if ( ! is_array($id)) {
            $id = array($id);
        }
        foreach ($id as $id_one) {
            // 登録対象のレコードを順次Insert
            foreach ($values as $i => $record) {
                $record[$assoc_fkey] = $id_one;
                table($assoc_table_name)->insert($record);
            }
        }
    }

    /**
     * @hook assoc hasManyValue
     * パラメータ例:
     *     "assoc"=>"hasMany",
     *     "table"=>"Product", // 必須 関係先テーブル名
     *     "fkey"=>"owner_member_id", // 必須 関係先テーブル上のFK
     *  読み込み時の動作:
     *  書き込み時の動作:
     */
    public function assoc_fetchEnd_hasManyValues ($col_name)
    {
    }

// -- 基本的ななchain hookの定義

    /**
     * @hook chain
     * Select文のField部を指定する
     * ※何も指定されていない場合は全て取得する
     */
    public function chain_with ($col_name, $col_name_sub=false)
    {
        if ($col_name_sub === false) {
            $this->query->addField($col_name);
        } else {
            $this->query->addField($col_name, $col_name_sub);
        }
    }

    /**
     * @hook chain
     * JOIN句の設定
     */
    public function chain_join ($table, $alias=null, $on=array(), $type="LEFT")
    {
        $this->query->join($table, $alias, $on, $type);
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
    public function chain_orderBy ($col_name, $asc=true)
    {
        $this->query->addOrder($col_name.($asc ? " ASC" : " DESC"));
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
     * IDを条件に指定する
     */
    public function chain_findById ($id)
    {
        $this->query->where($this->getIdColName("id"), $id);
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
     * ログイン中のアカウントを条件に指定する
     */
    public function chain_findMine ()
    {
        $account = auth()->getAccount();
        if ( ! $account->isLogin()) {
            report_warning("ログイン中ではありません",array(
                "account" => $account,
                "table" => $this,
            ));
            $this->query->where("0=1");
            return;
        }
        // 関係先を条件に指定
        $owner_key_attr = $account->getRole()."_owner_key";
        $owner_key_col_name = $this->getColNameByAttr($owner_key_attr);
        if ( ! $owner_key_col_name) {
            report_error("ログイン中のアカウントに関係づけるキーが設定されていません",array(
                "attr" => $owner_key_attr,
                "table" => $this,
            ));
        }
        $this->query->where($owner_key_col_name, $account->getId());
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
        $this->query->where($this->query->getTableName().".".$login_id_col_name, (string)$login_id);
        $this->query->where($this->query->getTableName().".".$login_pw_col_name, md5($login_pw));
    }

    /**
     * @hook chain
     * 絞り込み結果を空にする
     */
    public function chain_findNothing ()
    {
        $this->query->where("0=1");
    }

    /**
     * @deprecated
     * @hook chain
     * 旧仕様のlist_settingによる絞り込み
     */
    public function chain_findBySearchForm ($list_setting, $input)
    {
        $query_array = $this->getModel()->get_list_query($list_setting, $input);
        $this->query->merge($query_array);
    }

    /**
     * @hook chain
     * 検索フォームによる絞り込み
     * search_typeXxx($form, $field_def, $value)メソッドを呼び出す
     */
    public function chain_findBySearchFields ($form, $field_defs)
    {
        $values = $form->getValues();
        foreach ((array)$field_defs as $field_name => $field_def) {
            if (isset($field_def["search"])) {
                $value = $values[$field_name];
                // search_colの補完
                if ( ! isset($field_def["target_col"])) {
                    $field_def["target_col"] = $field_name;
                }
                // search_typeXxx($form, $field_def, $value)メソッドを呼び出す
                $search_method_name = "search_type".str_camelize($field_def["search"]);
                if ( ! method_exists($this, $search_method_name)) {
                    report_error("検索メソッドが定義されていません",array(
                        "search_method_name" => $search_method_name,
                        "table" => $this,
                    ));
                }
                call_user_func(array($this,$search_method_name), $form, $field_def, $value);
            }
        }
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
    public function chain_query ($query)
    {
        if (is_callable($func)) {
            call_user_func($query, $this->query);
        } elseif (is_arraylike($query)) {
            $this->query->merge($query);
        }
    }

// -- on_*_*処理の定義

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
     * @hook on_fetch
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
            $this->query->where($this->query->getTableName().".".$col_name, 0);
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
     * 削除日を関連づける
     */
    protected function on_update_attachDelDate ()
    {
        if (($col_name = $this->getColNameByAttr("del_date")) && $this->query->getDelete()) {
            $this->query->setValue($col_name, date("Y/m/d H:i:s"));
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

// -- assoc hookを呼び出すためのon hookの定義

    /**
     * assoc処理 selectの発行前
     */
    private function on_select_assoc ()
    {
        foreach ((array)$this->query->getFields() as $i => $col_name) {
            if ( ! is_numeric($i)) {
                $col_name = $i;
            }
            if ($assoc = static::$cols[$col_name]["assoc"]) {
                // fields→assoc_fieldsに項目を移動
                $this->query->removeField($col_name);
                $this->query->addAssocField($col_name);
                // assoc処理の呼び出し
                $this->callHookMethod("assoc_select_".$assoc, array($col_name));
            }
        }
        return false;
    }

    /**
     * assoc処理 各レコードfetch後
     */
    private function on_fetch_assoc ($record)
    {
        foreach ((array)$this->query->getAssocFields() as $col_name) {
            $assoc = static::$cols[$col_name]["assoc"];
            // assoc処理の呼び出し
            $this->callHookMethod("assoc_fetch_".$assoc, array($col_name, $record));
        }
        return false;
    }

    /**
     * assoc処理 fetch完了後
     */
    private function on_fetchEnd_assoc ()
    {
        foreach ((array)$this->query->getAssocFields() as $col_name) {
            $assoc = static::$cols[$col_name]["assoc"];
            // assoc処理の呼び出し
            $this->callHookMethod("assoc_fetchEnd_".$assoc, array($col_name));
        }
        return false;
    }

    /**
     * assoc処理 insert/updateの発行前
     */
    private function on_write_assoc ()
    {
        foreach ((array)$this->query->getValues() as $col_name => $value) {
            if ($assoc = static::$cols[$col_name]["assoc"]) {
                // values→assoc_valuesに項目を移動
                $this->query->removeValue($col_name);
                $this->query->setAssocValue($col_name,$value);
                // assoc処理の呼び出し
                $this->callHookMethod("assoc_write_".$assoc, array($col_name,$value));
            }
        }
        return false;
    }

    /**
     * assoc処理 insert/updateの発行後
     */
    private function on_afterWrite_assoc ()
    {
        foreach ((array)$this->query->getAssocValues() as $col_name => $value) {
            $assoc = static::$cols[$col_name]["assoc"];
            // assoc処理の呼び出し
            $this->callHookMethod("assoc_afterWrite_".$assoc, array($col_name,$value));
        }
        return false;
    }

// -- 基本的なsearch hookの定義

    /**
     * @hook search where
     * 一致、比較（）、IN（値を配列指定）
     */
    public function search_typeWhere ($form, $field_def, $value)
    {
        if (isset($value)) {
            // 対象カラムは複数指定に対応
            $target_cols = $field_def["target_col"];
            if ( ! is_array($target_cols)) {
                $target_cols = array($target_cols);
            }
            $conditions_or = array();
            foreach ($target_cols as $i => $target_col) {
                $conditions_or[$i] = array($target_col => $value);
            }
            if (count($conditions_or)==1) {
                $this->query->where(array_pop($conditions_or));
            // 複数のカラムが有効であればはORで接続
            } elseif (count($conditions_or)>1) {
                $this->query->where(array("OR"=>$conditions_or));
            }
        }
    }

    /**
     * @hook search word
     */
    public function search_typeWord ($form, $field_def, $value)
    {
        if (isset($value)) {
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
            if (count($conditions_or)==1) {
                $this->query->where(array_pop($conditions_or));
            // 複数のカラムが有効であればはORで接続
            } elseif (count($conditions_or)>1) {
                $this->query->where(array("OR"=>$conditions_or));
            }
        }
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
}
