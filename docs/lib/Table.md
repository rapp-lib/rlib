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
        // テーブルの定義名を記述
        protected static $table_name = "Member";
        // テーブル上のカラムの定義を記述
        protected static $cols = array(
            "name" => array("type"=>"text", "comment"=>"氏名"),
            "mail" => array("type"=>"text", "comment"=>"メールアドレス",
                // table("Member")->getNameByAttr("login_id")で"mail"を参照するために宣言
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
            // このテーブルのINDEXの定義を記述
            "indexes" => array(),
        );

        // ここまでの記述によって、schema.phpはテーブル定義の作成を行う
    }
```

- SQLの発行

```php

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

    // JOIN
    table("Member")
        ->join(table("FavoritePosts"), "Member.id=FavoritePosts.member_id")
        ->select();
    table("Member")
        ->join(array(table("FavoritePosts"), "Member.id=FavoritePosts.member_id"))
        ->select();

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
        ->join(array($sub,"own_product_count"),array("own_product_count.owner_member_id=Member.id"))
        ->select();
```


- 各種フック機能

```php
    /**
     * @table
     */
    class Table_App extends Table_Base
    {
        // chain_から始まるメソッドは->findById(1)->findById(1)のように続けて呼び出せる
        // 主にQueryの書き換えを行う目的の機能を定義する

        /**
         * @hook chain
         * IDを条件に指定する
         */
        public function chain_findById ($id)
        {
            $this->query->where($this->getQueryTableName().".".$this->getIdColName("id"), $id);
        }

        // on_*で始まるメソッドはtableのSELECTやINSERT、Fetchが実行されたタイミングで自動的呼び出される
        // テーブルの各操作が発生したときに自動的にQueryや結果の書き換え、他Tableへの反映を行うことができる
        // 以下のようなパターンでフック可能
        // ・SQL発生時に呼び出されるもの: select, insert, update, write(insert or update), read(select or update)
        // ・SQL実行完了時に呼び出されるもの: afterWrite(insert or updateの完了時)
        // ・Fetch時: fetch(1件のFetch時), fetchEnd(全件のFetch完了時)

        /**
         * @hook on_write
         * JSON形式で保存するカラムの処理
         */
        protected function on_write_jsonFormat ()
        {
            if ($col_names = $this->getColNamesByAttr("format", "json")) {
                foreach ($col_names as $col_name) {
                    $value = $this->query->getValue($col_name);
                    $this->query->setValue($col_name, json_encode((array)$value));
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
                    $record[$col_name] = (array)json_decode($record[$col_name]);
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

        // formから入力値に従った検索を行うときのsearch typeの定義

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

        // SQL実行結果に対するメソッド呼び出し時に呼び出されるメソッドの定義
        // 以下の例はtable("Member")->select()->getHashedBy()で呼び出される

        /**
         * @hook result
         * 各レコードの特定カラムのみの配列を取得する
         */
        public function result_getHashedBy ($result, $col_name, $col_name_sub=false)
        {
            $hashed_result = array();
            foreach ($result as $key => $record) {
                if ($col_name_sub === false) {
                    $hashed_result[$key] = $record[$col_name];
                } else {
                    $hashed_result[$key] = $record[$col_name][$col_name_sub];
                }
            }
            return $hashed_result;
        }

        // SELECT結果1件に対するメソッド呼び出し時に呼び出されるメソッドの定義
        // 以下の例はtable("Member")->selectOne()->getHashedBy()で呼び出される

        /**
         * @hook record
         * IDの設定によりInsert/Update処理
         */
        public function record_save ($record)
        {
            $values =(array)$record;
            // IDが指定されていれば削除してIDを条件に指定する
            $id_col_name = $this->getIdColName();
            $id = $values[$id_col_name];
            unset($values[$id_col_name]);
            $table = $this->createTable();
            // IDが指定されていればUpdate、指定が無ければInsert
            return $id ? $table->updateById($id,$values) : $table->insert($values);
        }
    }
```
