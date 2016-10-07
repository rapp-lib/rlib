R\Lib\Frontend
========================================

CONCEPT
--------

- テーブルの定義
```php
    /**
     * @table
     */
    class MemberTable extends Table_App
    {
        /**
         * テーブル定義
         */
        protected static $table_name = "Member";
        protected static $cols = array(
            "name" => array("type"=>"text", "comment"=>"氏名"),
            "mail" => array("type"=>"text", "comment"=>"メールアドレス",
                "login_id"=>true),
            "login_pw" => array("type"=>"text", "comment"=>"パスワード",
                "login_pw"=>true, "hash_pw"=>true),
            "imgs" => array("type"=>"text", "comment"=>"画像"),
            "id" => array("type"=>"integer", "id"=>true, "autoincrement"=>true, "comment"=>"ID",
                "admin_owner_key"=>true),
            "reg_date" => array("type"=>"datetime", "comment"=>"登録日時",
                "reg_date"=>true),
            "del_flg" => array("type"=>"integer", "default"=>0, "comment"=>"削除フラグ",
                "del_flg"=>true),
            "own_products" => array("assoc"=>"hasMany",
                "table"=>"Product", "fkey"=>"owner_member_id"),
            "favorite_product_ids" => array("assoc"=>"hasManyValues",
                "table"=>"FavoriteProduct", "fkey"=>"member_id"),
            "favorite_products" => array("assoc"=>"hasManyHasOne",
                "table"=>"FavoriteProduct", "fkey"=>"member_id",
                "extra_table"=>"Product", "extra_fkey"=>"product_id"),
            "rep_product" => array("assoc"=>"belongsTo",
                "table"=>"Product", "fkey"=>"rep_product_id"),
        );
        protected static $def = array(
            "indexes" => array(),
        );
```

- SQLの発行
```
    $ts = table("Member")
        ->findBySearchForm($list_setting, $input)
        ->select();
    $p = $ts->getPager();
    report("検索結果",array("ts"=>$ts, "p"=>$p));

    $result = table("Member")
        ->selectById(1);
    report("ID指定",$result);

    $result = table("Member")
        ->findBy("name","AAA")
        ->select();
    report("条件指定",$result);

    $result = table("Member")
        ->findMine()
        ->selectOne();
    report("ログイン中",$result);

    $result = table("Member")
        ->findByLoginIdPw("test", "cftyuhbvg")
        ->selectOne();
    report("ログイン結果",$result);

    // FieldsにSQL直でのサブクエリ
    $result = table("Member")
        ->with("(SELECT COUNT(*) AS count FROM Product WHERE Product.id=id)","own_product_count")
        ->select();

    // 集計結果をFieldsサブクエリで取る
    $sub = table("Product")
        ->with("COUNT(*)","count")
        ->findBy("Product.owner_member_id=id");
    $result = table("Member")
        ->with($sub, "own_product_count")
        ->select();

    // 集計結果をJoinサブクエリで取る
    $sub = table("Product")
        ->with("COUNT(*)","count")
        ->with("owner_member_id")
        ->groupBy("owner_member_id");
    table("Member")
        ->join($sub,"own_product_count",array("own_product_count.owner_member_id=Member.id"))
        ->select();

    // Assoc解決
    $result = table("Member")
        ->with("own_products")
        ->select();
    $result = table("Member")
        ->with("favorite_products")
        ->select();
    report("多→多ID参照",$result);
    $result = table("Member")
        ->with("rep_product")
        ->select();
    report("JOIN 1→1参照",$result);
```


- フック機能
```

    /**
     * @hook on_fetch
     * Adminでログインしていなければ
     */
    public function on_read_checkEnable ()
    {
        if (auth()->getAccount()->getRole() != "admin") {
            $this->query->where("enable_flg", 1);
            $this->query->where(array("enable_start_date<NOW()","enable_end_date>NOW()"));
        } else {
            return false;
        }
    }

    /**
     * @hook on_fetch
     * Insert/Update/Delete文を発行するとログに追記
     */
    public function on_writeAsAdmin_putAdminLog ()
    {
        table("AdminLog")->insert(array(
            "action" => $this->query->getType(),
            "delete" => $this->query->getDelete(),
            "table" => self::$table_name,
            "admin_id" => auth("admin")->getId(),
        ));
    }

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
            $this->query->where($col_name, 0);
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
        if ($col_name = $this->getColNameByAttr("del_flg") && $this->query->getDelete()) {
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
        if ($col_name = $this->getColNameByAttr("del_date") && $this->query->getDelete()) {
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
```