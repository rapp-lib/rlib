
    //-------------------------------------
    // Action: 削除 確認
    public function act_delete_confirm () {

        $this->context("c",1,true);

        // idの指定
        $this->c->id($_REQUEST["id"]);

        // 既存のデータを確認
        $input =<?=$model_obj?>->get_by_id($this->c->id());

        // 既存データの確認ができない場合の処理
        if ( ! $input) {

            $this->c->id(false);

            redirect("page:.view_list");
        }

        redirect("page:.delete_exec");
    }