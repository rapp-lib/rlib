<?php
namespace R\Lib\Table;

/**
 * Tableクラスの継承元
 */
class Table_Base extends Table_Core
{
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
    /**
     * 本登録URL押下時の処理（仮）
     * 仮登録パラメータとの完全一致レコードを取得する
     * 記述場所は正しいか
     * 拡張性を持たせるため共通でCredだが、
     * +で仮メールアドレスの完全一致が必要??
     */
    public function selectByCred ($key,$val, $fields=array())
    {
        $this->findByCred($key,$val);
        return $this->selectOne($fields);
    }
    public function selectByMail ($key,$val, $fields=array())
    {
        $this->findByMail($key,$val);
        return $this->selectOne($fields);
    }

// -- 基本的なassoc hookの定義

    /**
     * @hook assoc hasMany
     * パラメータ例:
     *     "type"=>"hasMany",
     *     "table"=>"Product", // 必須 関係先テーブル名
     *     "fkey"=>"owner_member_id", // 必須 関係先テーブル上のFK
     *     "value_col"=>"status", // 任意 値を1項目に絞る場合
     *  読み込み時の動作:
     *      Fetch完了後、結果全てのPKで関係先テーブルをSelectする
     *  書き込み時の動作:
     *      ※書き込んだIDがわかることが必須なので、IDを指定しないUpdateではエラー
     *      対象のIDに関係する関係先のレコードを全件削除
     *      登録対象のレコードを順次Insert
     */
    protected function assoc_fetchEnd_hasMany ($col_name)
    {
        $assoc_table_name = static::$cols[$col_name]["assoc"]["table"];
        $assoc_fkey = static::$cols[$col_name]["assoc"]["fkey"];
        $assoc_value_col = static::$cols[$col_name]["assoc"]["value_col"];
        $assoc_join = static::$cols[$col_name]["assoc"]["join"];
        if ( ! $assoc_table_name || ! $assoc_fkey) {
            report_error("パラメータの指定が不足しています",array(
                "col_name" => $col_name,
                "params" => static::$cols[$col_name]["assoc"],
                "required" => array("table","fkey"),
                "table" => $this,
            ));
        }
        // 主テーブルのIDを取得
        $pkey = $this->getIdColName();
        $ids = $this->result->getHashedBy($pkey);
        // 関連テーブルをFkeyでSELECT
        $table = table($assoc_table_name)
            ->findBy($assoc_fkey, $ids);
        // joinの指定があればJOINを接続
        if ($assoc_join) {
            $table->join($assoc_join[0], $assoc_join[1]);
        }
        $assoc_result_set = $table
            ->select()
            ->getGroupedBy($assoc_fkey);
        // 主テーブルのResultに関連づける
        foreach ($this->result as $i => $record) {
            // value_col指定=1項目の値のみに絞り込む場合
            if (isset($assoc_value_col)) {
                $values = array();
                foreach ((array)$assoc_result_set[$record[$pkey]] as $assoc_record) {
                    $values[] = $assoc_record[$assoc_value_col];
                }
                $this->result[$i][$col_name] = $values;
            } else {
                $this->result[$i][$col_name] = (array)$assoc_result_set[$record[$pkey]];
            }
        }
    }
    protected function assoc_afterWrite_hasMany ($col_name, $values)
    {
        $assoc_table_name = static::$cols[$col_name]["assoc"]["table"];
        $assoc_fkey = static::$cols[$col_name]["assoc"]["fkey"];
        $assoc_value_col = static::$cols[$col_name]["assoc"]["value_col"];
        if ( ! $assoc_table_name || ! $assoc_fkey) {
            report_error("パラメータの指定が不足しています",array(
                "col_name" => $col_name,
                "params" => static::$cols[$col_name]["assoc"],
                "required" => array("table","fkey"),
                "table" => $this,
            ));
        }
        // 書き込んだIDを確認
        $id = null;
        if ($this->query->getType() == "update") {
            $id = $this->query->getCondition($this->getIdColName());
        } elseif ($this->query->getType() == "insert") {
            $id = $this->result->getLastInsertId();
        }
        if ( ! isset($id)) {
            report_warning("IDの特定できないUpdate/Insertに対してAssoc処理は実行できません",array(
                "table" => $table,
            ));
            return;
        }
        // 対象のIDに関係する関係先のレコードを全件削除
        table($assoc_table_name)
            ->findBy($assoc_fkey, $id)
            ->deleteAll();
        // 登録対象のレコードを順次Insert
        foreach ((array)$values as $i => $record) {
            // value_col指定=1項目の値のみに絞り込む場合
            if (isset($assoc_value_col)) {
                $record = array($assoc_fkey=>$id, $assoc_value_col=>$record);
                table($assoc_table_name)->insert($record);
            } else {
                $record[$assoc_fkey] = $id;
                table($assoc_table_name)->insert($record);
            }
        }
    }

// -- 基本的なchain hookの定義

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
        $this->query->join($table, $on, $type);
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
     * @hook on_write
     * JSON形式で保存するカラムの処理
     */
    protected function on_write_jsonFormat ()
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
    protected function on_fetch_jsonFormat ($record)
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

// -- assoc hookを呼び出すためのon hookの定義

    /**
     * assoc処理 selectの発行前
     */
    protected function on_select_assoc ()
    {
        // Select対象となっているcol_nameの特定
        $fields = (array)$this->query->getFields();
        if ( ! $fields) {
            $fields = array("*");
        }
        $col_names = array();
        foreach ($fields as $i => $col_name) {
            if ( ! is_numeric($i)) {
                $col_name = $i;
            }
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
        foreach ($col_names as $col_name) {
            if ($assoc = static::$cols[$col_name]["assoc"]) {
                // fields→assoc_fieldsに項目を移動
                $this->query->removeField($col_name);
                $this->query->addAssocField($col_name);
                // assoc処理の呼び出し
                $this->callHookMethod("assoc_select_".$assoc["type"], array($col_name));
            }
        }
        return false;
    }

    /**
     * assoc処理 各レコードfetch後
     */
    protected function on_fetch_assoc ($record)
    {
        foreach ((array)$this->query->getAssocFields() as $col_name) {
            $assoc = static::$cols[$col_name]["assoc"];
            // assoc処理の呼び出し
            $this->callHookMethod("assoc_fetch_".$assoc["type"], array($col_name, $record));
        }
        return false;
    }

    /**
     * assoc処理 fetch完了後
     */
    protected function on_fetchEnd_assoc ()
    {
        foreach ((array)$this->query->getAssocFields() as $col_name) {
            $assoc = static::$cols[$col_name]["assoc"];
            // assoc処理の呼び出し
            $this->callHookMethod("assoc_fetchEnd_".$assoc["type"], array($col_name));
        }
        return false;
    }

    /**
     * assoc処理 insert/updateの発行前
     */
    protected function on_write_assoc ()
    {
        foreach ((array)$this->query->getValues() as $col_name => $value) {
            if ($assoc = static::$cols[$col_name]["assoc"]) {
                // values→assoc_valuesに項目を移動
                $this->query->removeValue($col_name);
                $this->query->setAssocValue($col_name,$value);
                // assoc処理の呼び出し
                $this->callHookMethod("assoc_write_".$assoc["type"], array($col_name,$value));
            }
        }
        return false;
    }

    /**
     * assoc処理 insert/updateの発行後
     */
    protected function on_afterWrite_assoc ()
    {
        foreach ((array)$this->query->getAssocValues() as $col_name => $value) {
            $assoc = static::$cols[$col_name]["assoc"];
            // assoc処理の呼び出し
            $this->callHookMethod("assoc_afterWrite_".$assoc["type"], array($col_name,$value));
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
}
