
    //-------------------------------------
    // Action: フォーム 入力
    public function act_entry_form () {

        $this->context("c",1,true);

        // 完了後の再アクセス時にはデータ消去
        if ($this->c->session("complete")) {

            $this->c->session(false, false);
        }

        // id指定があれば既存のデータを読み込む
        if ($_REQUEST["id"]) {

            // idの指定
            $this->c->id($_REQUEST["id"]);

            // 既存データの取得
            $input =<?=$model_obj?>->get_by_id($this->c->id());

            // 既存データの取得ができない場合の処理
            if ( ! $input) {

                $this->c->id(false);

                redirect("page:.view_list");
            }

            // 既存の情報をフォームへ登録
            $this->c->input($input);
        }
    }